<?php
include_once('./class/logsClass.php');
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/assetClass.php');
include_once('./class/pricingClass.php');
include_once('./class/interchangeClass.php');
$navCategory = 'search';
session_start();

$logs=new logs();
$pim=new pim();
$vcdb=new vcdb();
$pcdb=new pcdb();
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
  $expis=$pim->getPartEXPIs($partnumber);
  $connectedassets=$asset->getAssetsConnectedToPart($partnumber);
  $primaryphotouri=$asset->primaryPhotoURIofPart($partnumber);
 }
}

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        
        <!-- Header -->
            
            
            
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-10 my-col colMain">

                    <h1><?php echo $partnumber;?></h1>
                    <h4><?php echo $title;?></h4>
                    
                    <?php if($primaryphotouri!==false){?><div><a href="./showAsset.php?assetid=<?php echo $primaryphotouri;?>"><img class="img-thumbnail" src="<?php echo $primaryphotouri;?>" /></a></div><?php }?>



                    
                    <?php 
                    if(count($apps)>0){
                    echo '<div id="apps" style="text-align:left; padding-top:30px;">';
                    echo '<div class="card shadow-sm">';
                    echo '<h4 class="card-header text-start">Vehicle Applications</h4>';
                    echo '<div class="card-body" style="display:block;">';


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
                        echo '<div>'.$app['niceappdescription'].'</div>';
                        //echo '<div style="display:none;" data-appid="'.$app['id'].'" data-description-app="'. base64_encode($app['niceappdescription']).'">'.$app['id'].'</div>';
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