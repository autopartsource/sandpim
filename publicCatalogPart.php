<?php
include_once('./class/logsClass.php');
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/padbClass.php');
include_once('./class/assetClass.php');
include_once('./class/pricingClass.php');
include_once('./class/interchangeClass.php');
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






if(isset($_GET['partnumber']))
{
 $part=$pim->getPart($pim->sanitizePartnumber($_GET['partnumber']));
 if($part)
 {
  $partnumber=$part['partnumber'];
  $descriptions=$pim->getPartDescriptions($partnumber);
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
  $connectedassets=$asset->getAssetsConnectedToPart($partnumber);
  
  $primaryphotouri=''; $nonprimaryphotouris=[];  
  foreach($connectedassets as $connectedasset)
  {
   if($connectedasset!='')
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
        <!-- Navigation Bar -->
        
        <!-- Header -->            
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-12 my-col colMain">

                    <h1><div style="padding:20px;"><?php echo $partnumber;?></div></h1>
                    <h4><div style="padding:20px;"><?php echo $title;?></div></h4>
                    
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
                    foreach($niceapps as $app)
                    {
                        echo '<div style="padding-left:15px;">'.$app['niceappdescription'].'</div>';
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
                <!-- End of Main Content -->
                
            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
    </body> 
</html>