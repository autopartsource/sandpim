<?php
include_once('./class/pcdbClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/packagingClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
include_once('./class/configGetClass.php');
$navCategory = 'utilities';

$pim = new pim;

//bundling scenarios 
// front pads given, get vehilces (mmy+qualifiers) that the given part fits
// consume the apps list into a structure that has place-holders for front rotors, rear pads and rear rotors
// for each vehilce in the structure, pull in 


//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'bundlePart.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
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
$packaging = new packaging;
$logs=new logs;
$user=new user;
$configGet=new configGet;

$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');

$partcategories = $pim->getPartCategories();
$favoriteparttypes=$pim->getFavoriteParttypes();

$inputpartnumber=''; 
$outputs=array();
$matrix=array();

$basevehicleids=array();
$categories=array();
$errormessage='';

if(isset($_GET['submit']) && $_GET['submit']=='Search' && $part=$pim->getPart($_GET['partnumber']))
{
 $inputpartnumber=$part['partnumber'];
 $pairmode=$_GET['pairmode'];
 switch($pairmode)
 {
  case 'star-met': $categories=array(104,108); break;
  case 'star-cer': $categories=array(103,108); break;
  case 'pro-met-coated': $categories=array(99,108); break;
  case 'pro-cer-coated': $categories=array(101,108); break;
  case 'pro-met-platinum': $categories=array(99,97); break;
  case 'pro-cer-platinum': $categories=array(101,97); break;
  case 'platinum-met-coated': $categories=array(100,108); break;
  case 'platinum-cer-coated': $categories=array(102,108); break;
  case 'platinum-met-platinum': $categories=array(100,97); break;
  case 'platinum-cer-platinum': $categories=array(102,97); break;
  default: break;
 }



 $frontpadapps=$pim->getAppsByPartnumber($inputpartnumber);
 
 foreach($frontpadapps as $app)
 {
  if($app['positionid']!=22 || $app['parttypeid']!=1684){continue;}

  if(!in_array($app['basevehicleid'], $basevehicleids)){$basevehicleids[]=$app['basevehicleid'];}

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
  $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$nicefitmentstring;
   
  $matrix[$linestring]['frontpads'][]=$app;   
 }

 
 
 if(count($basevehicleids)>0)
 {// the provided part is applicated as a front pad to at least one vehilce
  // get front rotors
  foreach($basevehicleids as $basevehicleid)
  {
   $tempapps=$pim->getAppsByBasevehicleid($basevehicleid, array());
   foreach($tempapps as $app)
   {
    if($app['partnumber']==$inputpartnumber || $app['positionid']!=22 || $app['parttypeid']!=1896){continue;}
    $part=$pim->getPart($app['partnumber']);
    if(!in_array($part['partcategory'],$categories)){continue;}

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
    $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$nicefitmentstring;
    $matrix[$linestring]['frontrotors'][]=$app;
   }     
  }

  // get rear pads
  foreach($basevehicleids as $basevehicleid)
  {
   $tempapps=$pim->getAppsByBasevehicleid($basevehicleid, array());
   foreach($tempapps as $app)
   {
    if($app['partnumber']==$inputpartnumber || $app['positionid']!=30 || $app['parttypeid']!=1684){continue;}
    $part=$pim->getPart($app['partnumber']);
    if(!in_array($part['partcategory'],$categories)){continue;}
      
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
    $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$nicefitmentstring;
   
    $matrix[$linestring]['rearpads'][]=$app;
   }     
  }

  // get rear rotors
  foreach($basevehicleids as $basevehicleid)
  {
   $tempapps=$pim->getAppsByBasevehicleid($basevehicleid, array());
   foreach($tempapps as $app)
   {
    if($app['partnumber']==$inputpartnumber || $app['positionid']!=30 || $app['parttypeid']!=1896){continue;}
    $part=$pim->getPart($app['partnumber']);
    if(!in_array($part['partcategory'],$categories)){continue;}
   
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
    $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$nicefitmentstring;
   
    $matrix[$linestring]['rearrotors'][]=$app;
   }     
  }
 }
 else
 {//the provided part is not applicated as a front pad to vehilces
       
  $errormessage=$inputpartnumber.' is not applied to any vehicle as a front pad';
 }
 
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
                        <h3 class="card-header text-start">Full-Vehilce brake kitting</h3>
                        <div class="card-body">

                            <form>
                                <?php if($errormessage!=''){?>
                                <div style="padding:5px;"><?php echo $errormessage;?></div>
                                
                                <?php }?>
                                <div style="padding:5px;">Front Pads Part Number <input type="text" name="partnumber" value="<?php echo $inputpartnumber?>"/></div>
                                <div style="padding:5px;">Match with <select name="pairmode">
                                        <option value="star-met"<?php if($pairmode=='star-met'){echo ' selected';}?>>Star Metalic and coated rotors</option>
                                        <option value="star-cer"<?php if($pairmode=='star-cer'){echo ' selected';}?>>Star Ceramic and coated rotors</option>
                                        <option value="pro-met-coated"<?php if($pairmode=='pro-met-coated'){echo ' selected';}?>>Pro Metalic and coated rotors</option>
                                        <option value="pro-cer-coated"<?php if($pairmode=='pro-cer-coated'){echo ' selected';}?>>Pro Ceramic and coated rotors</option>
                                        <option value="pro-met-platinum"<?php if($pairmode=='pro-met-platinum'){echo ' selected';}?>>Pro Metalic and Platinum rotors</option>
                                        <option value="pro-cer-platinum"<?php if($pairmode=='pro-cer-platinum'){echo ' selected';}?>>Pro Ceramic and Platinum rotors</option>
                                        <option value="platinum-met-coated"<?php if($pairmode=='platinum-met-coated'){echo ' selected';}?>>Platinum Metalic and coated rotors</option>
                                        <option value="platinum-cer-coated"<?php if($pairmode=='platinum-cer-coated'){echo ' selected';}?>>Platinum Ceramic and coated rotors</option>
                                        <option value="platinum-met-platinum"<?php if($pairmode=='platinum-met-platinum'){echo ' selected';}?>>Platinum Metalic and Platinum rotors</option>
                                        <option value="platinum-cer-platinum"<?php if($pairmode=='platinum-cer-platinum'){echo ' selected';}?>>Platinum Ceramic and Platinum rotors</option>
                                    </select></div>
                                <div style="padding:5px;"><input type="submit" name="submit" value="Search"/></div>
                            </form>
                            
                        </div>
                    </div>
                    
                    <?php if(isset($_GET['submit']) && $part){?>
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start">Kitting options for <?php echo $inputpartnumber;?></h3>
                        <div class="card-body">

                            <?php
                            $distinctpartcombos=array();
                            foreach($matrix as $vehiclestring => $groups)
                            {
                                if(
                                        isset($groups['frontpads'][0]['partnumber']) &&
                                        isset($groups['frontrotors'][0]['partnumber']) &&
                                        isset($groups['rearpads'][0]['partnumber']) &&
                                        isset($groups['rearrotors'][0]['partnumber'])
                                )
                                {
                                    
                                    $distinctpartcombos[$groups['frontpads'][0]['partnumber']."\t".$groups['frontrotors'][0]['partnumber']."\t".$groups['rearpads'][0]['partnumber']."\t".$groups['rearrotors'][0]['partnumber']][]=$vehiclestring;
                                }
                                
                            }
                            
                            $packagesa=$packaging->getPackagesByPartnumber($inputpartnumber);
                            $packagea=false; foreach($packagesa as $package){if($package['packageuom']=='EA'){$packagea=$package; break;}}

                            foreach($distinctpartcombos as $combo=>$fitments)
                            {
                                $partbits=explode("\t",$combo);
                                $partb=$pim->getPart($partbits[1]);
                                $partc=$pim->getPart($partbits[2]);
                                $partd=$pim->getPart($partbits[3]);
                                
                                $packagesb=$packaging->getPackagesByPartnumber($partbits[1]);
                                $packageb=false; foreach($packagesb as $package){if($package['packageuom']=='EA'){$packageb=$package; break;}}
                                $packagesc=$packaging->getPackagesByPartnumber($partbits[2]);
                                $packagec=false; foreach($packagesc as $package){if($package['packageuom']=='EA'){$packagec=$package; break;}}                                
                                $packagesd=$packaging->getPackagesByPartnumber($partbits[3]);
                                $packaged=false; foreach($packagesd as $package){if($package['packageuom']=='EA'){$packaged=$package; break;}}

                                
                               
                                
                                echo '<div style="padding-bottom:20px;">';
                                echo '<div style="text-align:left;font-weight:bold;">'.$combo.'</div>';
                                echo '<div style="padding-left:5px;text-align:left">';
                                $totalweight=0;
                                echo 'weights: ';
                                if($packagea){echo $packagea['weight'].' + '; $totalweight+=$packagea['weight'];}
                                if($packageb){echo $packageb['weight'].' + '; $totalweight+=$packageb['weight'];}
                                if($packaged){echo $packagec['weight'].' + '; $totalweight+=$packagec['weight'];}
                                if($packagea){echo $packaged['weight']; $totalweight+=$packaged['weight'];}
                                
                                echo ' = '.$totalweight; 
                                
                                echo '</div>';
                                
                                
                                foreach($fitments as $fitment)
                                {
                                    echo '<div style="text-align:left;padding-left:10px;">'.$fitment.'</div>';                                   
                                }
                                echo '</div>';
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
