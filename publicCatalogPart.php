<?php
include_once('./class/logsClass.php');
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/padbClass.php');
include_once('./class/assetClass.php');
include_once('./class/pricingClass.php');
include_once('./class/packagingClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/configGetClass.php');
$navCategory = 'search';
session_start();

$logs=new logs();
$pim=new pim();
$vcdb=new vcdb();
$pcdb=new pcdb();
$padb=new padb();
$asset = new asset();
$pricing = new pricing();
$interchange=new interchange();
$packaging = new packaging;
$configGet = new configGet();

$contacturi=$configGet->getConfigValue('publicCatalogContactURI');
$copyrightname=$configGet->getConfigValue('publicCatalogCopyrightName');
$logouri=$configGet->getConfigValue('publicCatalogLogoURI');



$results=false;
$qsanitized='';


$title='Not Found';
$subtitle='Not Found';
$showAppAttributesInSummary=true;

function niceAppAttributes($appattributes)
{
    $vcdb = new vcdb;
    $niceattributes = array();
    foreach ($appattributes as $appattribute) {
        if ($appattribute['type'] == 'vcdb') {
            $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']);
        }
        if ($appattribute['type'] == 'note') {
            $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']);
        }
    }
    $nicefitmentstring = '';
    $nicefitmentarray = array();
    foreach ($niceattributes as $niceattribute) {
        // exclude cosmetic elements from the compiled list
        $nicefitmentarray[] = $niceattribute['text'];
    }
    return implode('; ', $nicefitmentarray);
}



$mmy=false;
if(isset($_GET['basevid']))
{
 $mmy=$vcdb->getMMYforBasevehicleid(intval($_GET['basevid']));
}

if(isset($_GET['partnumber']))
{
 $part=$pim->getPart($pim->sanitizePartnumber($_GET['partnumber']));
 if($part)
 {
  $partnumber=$part['partnumber'];
  $descriptions=$pim->getPartDescriptions($partnumber);
  $logs->logSystemEvent('info', 0, 'publicCatalogPart queried for ['.$_GET['partnumber'].'] by client ['.$_SERVER['REMOTE_ADDR'].']');
    
  foreach($descriptions as $description)
  {
   if($description['descriptioncode']=='EXT' && $description['languagecode']=='EN')
   {
    $title=$description['description']; 
    break;
   }
   
   if($description['descriptioncode']=='DES' && $description['languagecode']=='EN')
   {
    $title=$description['description']; 
    break;
   }
   
   if($description['descriptioncode']=='SHO' && $description['languagecode']=='EN')
   {
    $title=$description['description']; 
    break;
   }   
  }
  
  
  
  $apps = $pim->getAppsByPartnumber($partnumber);
  $attributes = $pim->getPartAttributes($partnumber);
  $packages=$packaging->getPackagesByPartnumber($partnumber);
  
  $allinterchangeparts=$interchange->getInterchangeByPartnumber($partnumber);
  $competitorparts=[]; $oemparts=[];
  
  foreach($allinterchangeparts as $allinterchangepart)
  {
   if($allinterchangepart['brandAAIAID']=='FLQR')
   {
    $oemparts[]=$allinterchangepart['competitorpartnumber'];
   }
   else
   {
    $competitorparts[]=$interchange->brandsubbrandName($allinterchangepart['brandAAIAID'],$allinterchangepart['subbrandAAIAID']).': '.$allinterchangepart['competitorpartnumber'];
   }      
  }
  asort($oemparts); asort($competitorparts);
  
  $expis=$pim->getPartEXPIs($partnumber);
  $connectedassets=$asset->getAssetsConnectedToPart($partnumber,true);
  
  $primaryphotouri=''; $nonprimaryphotouris=[];  
  foreach($connectedassets as $connectedasset)
  {
   if($connectedasset['uri']!='' && in_array($connectedasset['filetype'],['JPG','PNG']))
   {
    if($connectedasset['assettypecode']=='P04')
    {
     $primaryphotouri=$connectedasset['uri'];
    }
    else
    {
     $nonprimaryphotouris[]=$connectedasset['uri'];
    }
   }
  }
 }
 else
 {// part in GET is no valid
  $logs->logSystemEvent('security', 0, 'publicCatalogPart queried for invalid partnumber (base64encoded for safty) ['.base64_ecode($_GET['partnumber']).'] by client ['.$_SERVER['REMOTE_ADDR'].']');
 }
}

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
                
        <script>
            function showhideApplicationDetail()
            {
             var x = document.getElementById("applicationdetail");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
            }
            
            function showhideAttributeDetail()
            {
             var x = document.getElementById("attributedetail");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
            }
            
            function showhideInterchangeDetail()
            {
             var x = document.getElementById("interchangedetail");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
            }
            
            function showhideOEMdetail()
            {
             var x = document.getElementById("oemdetail");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
            }
            
        </script>
        
    </head>
    <body>
        
        <div class="row">

            <!-- hidden in mobile mode, left content in desktop mode-->
            <div class="d-none d-md-block col-md-6 col-lg-4 " style="">
                <img src="<?php echo $logouri;?>" width="150px" alt="logo"/>
                <div style="padding:10px;"><a href="<?php echo $primaryphotouri;?>"><img class="img-thumbnail" src="<?php echo $primaryphotouri;?>" /></a></div>
                <?php foreach($nonprimaryphotouris as $uri){?>
                <div style="padding:10px;"><a href="<?php echo $uri;?>"><img class="img-thumbnail" src="<?php echo $uri;?>" /></a></div>
                <?php }?>
            </div>

            <!-- main content in mobile mode, right content in desktop mode-->
            <div class="col-12 col-md-5 col-lg-7">

                
                <?php if($mmy!==false){?>                                
                <h3 class="card-header text-start"><a href="./publicCatalogBasevehicle.php?makeid=<?php echo $mmy['MakeID'];?>&modelid=<?php echo $mmy['ModelID'];?>&yearid=<?php echo $mmy['year'];?>"><< <?php echo $mmy['makename'].' '.$mmy['modelname'].' '.$mmy['year'];?></a></h3>
                <?php }?>                
                
                <h1><div style="padding:20px;"><?php echo $partnumber;?></div></h1>
                <h4><div style="padding:20px;"><?php echo $title;?></div></h4>


                <!-- carousel for mobile mode -->

                <?php if($primaryphotouri!=''){?>
                <div class="d-block d-md-none">
                    <div id="imageCarousel" class="carousel slide" data-bs-interval="false">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="<?php echo $primaryphotouri;?>" class="d-block w-100" alt="First Slide">
                            </div>

                            <?php foreach($nonprimaryphotouris as $uri){?>
                            <div class="carousel-item">
                                <img src="<?php echo $uri;?>" class="d-block w-100">
                            </div>
                            <?php }?>

                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#imageCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#imageCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                </div>
                <?php }?> 
                <!-- end of carousel for mobile mode -->

                <div style="font-size: 1.75em;padding:20px;text-align: left;">
                GTIN: <?php echo $part['GTIN'];?><br/>
                <?php if(count($packages)){echo $packages[0]['nicepackage'];}?>
                </div>
                
                
                
                
                
                <?php if(count($apps)>0){
                echo '<div id="apps" style="text-align:left; padding-top:30px;">';
                echo '<div class="card shadow-sm">';
                echo '<h4 class="card-header text-start" onclick="showhideApplicationDetail()">Vehicle Applications</h4>';
                echo '<div id="applicationdetail" class="card-body" style="display:none;">';

                $niceapps=array(); $makesindex=array(); $modelsindex=array(); $yearsindex=array();
                foreach($apps as $rowid=>$app)
                {
                 $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
                 $makesindex[$rowid]=$mmy['makename'];
                 $modelsindex[$rowid]=$mmy['modelname'];
                 $yearsindex[$rowid]=$mmy['year'];

                 $niceattributes='';
                 if($showAppAttributesInSummary){ $niceattributes=' '.niceAppAttributes($app['attributes']);}
                 $niceappdescription=$vcdb->niceMMYofBasevid($app['basevehicleid']).' '.$niceattributes;

                 $niceapps[$rowid]=array('id'=>$app['id'],'niceappdescription'=>$niceappdescription,'makename'=>$mmy['makename'],'modelname'=>$mmy['modelname'],'year'=>$mmy['year']);
                }

                array_multisort($makesindex,SORT_ASC,$modelsindex,SORT_ASC,$yearsindex,SORT_DESC,$niceapps);
                
                
                $distinctniceapps=[];
                foreach($niceapps as $app)
                {
                    if(array_key_exists($app['niceappdescription'], $distinctniceapps)){continue;}
                    echo '<div style="padding-left:15px;">'.$app['niceappdescription'].'</div>';
                    $distinctniceapps[$app['niceappdescription']]='';
                }

                echo '</div></div></div>';
                }

                if(count($attributes)>0)
                {
                    echo '<div id="attributes" style="text-align:left; padding-top:30px;">';
                    echo '<div class="card shadow-sm">';
                    echo '<h4 class="card-header text-start" onclick="showhideAttributeDetail()">Part Attributes</h4>';
                    echo '<div id="attributedetail" class="card-body" style="display:none;">';
                    foreach($attributes as $attribute)
                    {
                        $niceattributename=$attribute['name']; if(intval($attribute['PAID'])>0){$niceattributename=$padb->PAIDname($attribute['PAID']);}
                        echo '<div style="padding-left:15px;">'.$niceattributename.': <strong>'.$attribute['value'].'</strong> '.$attribute['uom'].'</div>';
                    }
                    echo '</div></div></div>';
                }

                if(count($competitorparts)>0)
                {
                    echo '<div id="interchange" style="text-align:left; padding-top:30px;">';
                    echo '<div class="card shadow-sm">';
                    echo '<h4 class="card-header text-start" onclick="showhideInterchangeDetail()">Equivalent Competitor Parts</h4>';
                    echo '<div id="interchangedetail" class="card-body" style="display:none;">';
                    foreach($competitorparts as $competitorpart)
                    {
                        echo '<div style="padding-left:15px;">'.$competitorpart.'</div>';
                    }
                    echo '</div></div></div>';
                }

                if(count($oemparts)>0)
                {
                    echo '<div id="oem" style="text-align:left; padding-top:30px;">';
                    echo '<div class="card shadow-sm">';
                    echo '<h4 class="card-header text-start" onclick="showhideOEMdetail()">Equivalent OEM Parts</h4>';
                    echo '<div id="oemdetail" class="card-body" style="display:none;">';
                    foreach($oemparts as $oempart)
                    {
                        echo '<div style="padding-left:15px;">'.$oempart.'</div>';
                    }
                    echo '</div></div></div>';
                }?>


            </div>

        </div>
        
        <hr/>
        <footer class="footer"><div>© <?php echo $copyrightname;?> <?php echo date('Y');?></div>
            <div><a href="<?php echo $contacturi;?>">Contact us</a></div>
        </footer>
        
    </body>

</html>



