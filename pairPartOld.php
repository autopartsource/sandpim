<?php
include_once('./class/pcdbClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
include_once('./class/configGetClass.php');
$navCategory = 'parts';

$pim = new pim;

//pairing scenarios 
//  different type on same position (front pads with front rotor)
//  same type on different position (front pads with rear pads)
//  4-corners? 



//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'newPart.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb = new pcdb;
$qdb = new qdb;
$vcdb = new vcdb;
$asset = new asset;
$logs=new logs;
$user=new user;
$configGet=new configGet;

$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');

$partcategories = $pim->getPartCategories();
$favoriteparttypes=$pim->getFavoriteParttypes();

$partnumber='';  $pairwithparttypeid=''; $partcategory='';  $positionmode='';

$outputs=array();

if(isset($_GET['submit']) && $_GET['submit']=='Search' && $part=$pim->getPart($_GET['partnumber']))
{
 $pairwithparttypeid=intval($_GET['pairwith']);
 $positionmode=$_GET['positionmode'];
 $partcategory=$_GET['partcategory'];
 
 if($partcategory=='any')
 {
  $partcategory=array();
 }
 else
 {
  $partcategory=array(intval($partcategory));
 } 
 
 $partnumber=$part['partnumber'];
 $leftapps=$pim->getAppsByPartnumber($partnumber);
    
 // given part is "leftapps"
 //  output is a list of possible mates in the given "pairwith" parttype and their 
 //  compatibility score (VIO of the apps they have in common)
 //  
 // 1 - roll through given part's apps and compile a list of basevids, positions
 // 2 
 



 $basevidspositions=array();
 foreach($leftapps as $leftapp)
 {
  if(array_key_exists($leftapp['basevehicleid'], $basevidspositions))
  {
   if(!in_array($leftapp['positionid'], $basevidspositions[$leftapp['basevehicleid']]))
   {
    $basevidspositions[$leftapp['basevehicleid']][]=$leftapp['positionid'];   
   }           
  }
  else
  {
   $basevidspositions[$leftapp['basevehicleid']][]=$leftapp['positionid'];         
  }
 }
 
 $lefthashes=array();
 foreach($leftapps as $app)
 {
  $niceattributes=array();
  foreach($app['attributes'] as $appattribute)
  {
   switch ($appattribute['type']) 
   {
    case 'vcdb': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']); break;
    case 'qdb': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $qdb->qualifierText($appattribute['name'], explode('~', str_replace('|','',$appattribute['value']))), 'cosmetic' => $appattribute['cosmetic']);                 break;
    case 'note': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']); break;
    default:break;
   } 
  }

  $nicefitmentarray = array(); foreach ($niceattributes as $niceattribute){$nicefitmentarray[] = $niceattribute['text'];}
  $nicefitmentstring=implode('; ', $nicefitmentarray);
  $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
  
  if($positionmode=='same')
  {
   $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$pcdb->positionName($app['positionid'])."\t".$nicefitmentstring."\r\n";
  }
  else
  {
   $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$nicefitmentstring."\r\n";
  }
  $lefthashes[]= md5($linestring);
 }


 $partkeyedcandidateapps=array();
 
// disqualify parts not in "available" status or having no P04 assets or not of "pairwith" parttype
 $qualifyingpartstemp=array(); 
 foreach($basevidspositions as $basevid=>$positions)
 {
  $appstemp=$pim->getAppsByBasevehicleid($basevid, $partcategory);
  foreach($positions as $position)
  {
   foreach($appstemp as $app)
   {
    if(!in_array($app['partnumber'], $qualifyingpartstemp)){$qualifyingpartstemp[]=$app['partnumber'];}
   }
  }
 }

 $qualifyingparts=array(); 
 foreach($qualifyingpartstemp as $qualifyingpart)
 {
  $parttemp=$pim->getPart($qualifyingpart);
  if($parttemp['lifecyclestatus']=='2' && $parttemp['parttypeid']==$pairwithparttypeid)
  {
   $digitassets=$asset->getAssetsConnectedToPart($qualifyingpart, true);
   $foundprimaryimage=false; foreach($digitassets as $digitalasset){if($digitalasset['assettypecode']=='P04'){$foundprimaryimage=true; break;}}
   if($foundprimaryimage){$qualifyingparts[]= $qualifyingpart;}
  }
 }
 
 
 foreach($basevidspositions as $basevid=>$positions)
 {
  $appstemp=$pim->getAppsByBasevehicleid($basevid, $partcategory);
  foreach($positions as $position)
  {
   foreach($appstemp as $apptemp)
   {
    if($positionmode=='same')
    {// we are interested in stuff at the same position as the input part
     if($apptemp['positionid']==$position && $apptemp['parttypeid']==$pairwithparttypeid && $apptemp['partnumber']!=$partnumber)
     {
      $partkeyedcandidateapps[$apptemp['partnumber']][]=$apptemp;
     }
    }
    else
    {// we are interested in stuff at other positions than the given part
     if($apptemp['positionid']!=$position && $apptemp['parttypeid']==$pairwithparttypeid && $apptemp['partnumber']!=$partnumber)
     {
      $partkeyedcandidateapps[$apptemp['partnumber']][]=$apptemp;
     }        
    }
   }
  }   
 }
 
 $finalcandidateapplines=array();
 $tempscores=array();

 foreach($partkeyedcandidateapps as $candidatepartnumber=>$apps)
 {
  foreach($apps as $app)
  {
   $niceattributes=array();
   foreach($app['attributes'] as $appattribute)
   {
    switch ($appattribute['type']) 
    {
     case 'vcdb': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']); break;
     case 'qdb': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $qdb->qualifierText($appattribute['name'], explode('~', str_replace('|','',$appattribute['value']))), 'cosmetic' => $appattribute['cosmetic']); break;
     case 'note': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']); break;
     default:break;
    } 
   }
   $nicefitmentarray = array(); foreach ($niceattributes as $niceattribute){$nicefitmentarray[] = $niceattribute['text'];}
   $nicefitmentstring=implode('; ', $nicefitmentarray);

   $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);

   if($positionmode=='same')
   {
    $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$pcdb->positionName($app['positionid'])."\t".$nicefitmentstring."\r\n";
   }
   else
   {
    $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$nicefitmentstring."\r\n";
   }
   if(!in_array(md5($linestring), $lefthashes)){continue;}

   $finalcandidateapplines[$candidatepartnumber][]=$linestring;

   $vio=$pim->appVIOexperian($app['id'], $viogeography, $vioyearquarter, $app['attributes']);
   if(array_key_exists($candidatepartnumber, $tempscores))
   {
    $tempscores[$candidatepartnumber]+=$vio;
   }
   else
   {
    $tempscores[$candidatepartnumber]=$vio;
   }
  }
 }

 foreach($finalcandidateapplines as $candidatepartnumber=>$fitmentlines)
 {
  $outputs[]=array('partnumber'=>$candidatepartnumber,'fitmentlines'=>$fitmentlines,'score'=>$tempscores[$candidatepartnumber]);
 }

 $scoreindex=array();
 foreach($outputs as $rowid=>$output)
 {
  $scoreindex[$rowid]=$output['score'];     
 }
 
 array_multisort($scoreindex,SORT_DESC,$outputs);
 
 
}


?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        
        <script>
        </script>
    </head>
    <body onload="populatePasteDiv()">
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
                
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">

                </div>

                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
                        <!-- Header -->
                        <h3 class="card-header text-start">Part Pairing</h3>
                        <div class="card-body">

                            <form>
                                <div style="padding:5px;">Part Number <input type="text" name="partnumber" value="<?php echo $partnumber?>"/></div>
                                <div style="padding:5px;">Pair with <select name="pairwith"><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"<?php if($parttype['id']==$pairwithparttypeid){echo ' selected';} ?>><?php echo $parttype['name'];?></option><?php }?></select></div>
                                <div style="padding:5px;">Position <select name="positionmode"><option value="different"<?php if($positionmode=='different'){echo ' selected';}?>>Different</option><option value="same"<?php if($positionmode=='same'){echo ' selected';}?>>Same</option></select></div>
                                <div style="padding:5px;">Part Category <select name="partcategory"><option value="any">any</option><?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"<?php if(isset($_GET['partcategory']) && $partcategory['id']==$_GET['partcategory']){echo ' selected';}?>><?php echo $partcategory['name']; ?></option><?php } ?></select></div>
                                <div style="padding:5px;"><input type="submit" name="submit" value="Search"/></div>
                            </form>
                            
                        </div>
                    </div>
                    
                    <?php if(isset($_GET['submit'])){?>
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start">Parts that share vehicle fitment with <?php echo $partnumber;?></h3>
                        <div class="card-body">

                            <?php 
                            if(count($outputs))
                            {
                             foreach($outputs as $output)
                             {
                              echo '<div style="padding:15px; text-align:left">';
                              echo $partnumber.'  + '.$output['partnumber'].' (common VIO: '.number_format($output['score']).')'."\r\n";
                              foreach($output['fitmentlines'] as $fitmentline)
                              {
                               echo '<div style="font-size:80%;padding:5px 0px 0px 30px;">';
                               echo $fitmentline;
                               echo '</div>';
                              }
                              echo '</div>';
                             }
                            }
                            ?>
                        </div>
                    </div>
                    <?php }?>
                    
                    
                    
                    
                </div>
                <!-- End of Main Content -->

                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>
