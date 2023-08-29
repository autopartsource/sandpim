<?php

include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/ebayClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');

// login check is intentionally left out so that this page can stand alone as an un-authenticaeted utility
$navCategory = 'utilities';

$pim=new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ebayFeed.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}


$configGet = new configGet();

$submodelnametransforms=array('+'=>'Plus','!'=>'Exclaim');

$vcdb=new vcdb();
$pcdb=new pcdb;
$qdb=new qdb;

$output=array();
$output[]="SKU\tMake\tModel\tYear\tEngine\tTrim\tNotes";

if(isset($_GET['submit']) && $_GET['submit']=='Go') 
{
 $receiverprofileid=intval($_GET['receiverprofile']);
 $profile=$pim->getReceiverprofileById($receiverprofileid);
 $profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';
 $profilename=$profile['name'];
 
 $lifecyclestatuslist=array();
 $lifecyclestatusestemp=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
 foreach ($lifecyclestatusestemp as $status){$lifecyclestatuslist[]=$status['lifecyclestatus'];}

 
 $partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
 $apps=$pim->getAppsByPartcategories($partcategories,$lifecyclestatuslist);
 $parttranslations=$pim->getReceiverprofileParttranslations($receiverprofileid);
    
 foreach($apps as $app)
 {
  $generalattributes=array(); $specialattributes=array();
  foreach($app['attributes'] as $attribute)
  {
   if($attribute['name']=='EngineBase' || $attribute['name']=='EngineBlock' || $attribute['name']=='FuelType' || $attribute['name']=='CylinderHeadType' || $attribute['name']=='Aspiration' || $attribute['name']=='SubModel' || $attribute['name']=='BodyType' || $attribute['name']=='BodyNumDoors')
   {
    $specialattributes[]=$attribute;
   }else{$generalattributes[]=$attribute;}
  }   
      
  $ebayvehicles=$vcdb->getEbayVehicleStuff($app['basevehicleid'], 1);
   
  foreach($ebayvehicles as $ebayvehicle)
  {
   $matchedcount=0;
   foreach($specialattributes as $specialattribute)
   {
    switch($specialattribute['name'])
    {
     case 'EngineBase': if($specialattribute['value']==$ebayvehicle['enginebaseid']){$matchedcount++;} break;
     case 'EngineBlock': if($specialattribute['value']==$ebayvehicle['engineblockid']){$matchedcount++;} break;
     case 'FuelType': if($specialattribute['value']==$ebayvehicle['fueltypeid']){$matchedcount++;} break;
     case 'CylinderHeadType': if($specialattribute['value']==$ebayvehicle['cylinderheadtypeid']){$matchedcount++;} break;
     case 'Aspiration': if($specialattribute['value']==$ebayvehicle['aspirationid']){$matchedcount++;} break;
     case 'SubModel': if($specialattribute['value']==$ebayvehicle['submodelid']){$matchedcount++;} break;
     case 'BodyType': if($specialattribute['value']==$ebayvehicle['bodytypeid']){$matchedcount++;} break;
     case 'BodyNumDoors': if($specialattribute['value']==$ebayvehicle['bodyNumdoorsid']){$matchedcount++;} break;
     default: break;         
    }
   }
    
   if($matchedcount==count($specialattributes))
   {// all ebay-specific attributes match this source app 
    $niceattributes=array();
    if($app['positionid']>1){$niceattributes[]=$pcdb->positionName($app['positionid']);}
    foreach($generalattributes as $appattribute)
    {
     if($appattribute['type']=='vcdb'){$niceattributes[]=$vcdb->niceVCdbAttributePair($appattribute);}
     if($appattribute['type']=='qdb'){$niceattributes[]=$qdb->qualifierText(intval($appattribute['name']), explode('~', str_replace('|', '', $appattribute['value'])));}
     if($appattribute['type']=='note'){$niceattributes[]=$appattribute['value'];} 
    }
    $nicefitmentstring='';
    if(count($niceattributes)>0){$nicefitmentstring=implode('; ',$niceattributes);}
    
    $enginebits=array();
    if($ebayvehicle['liter']!='-'){$enginebits[]=$ebayvehicle['liter'].'L';}
    if($ebayvehicle['cc']!='-'){$enginebits[]=$ebayvehicle['cc'].'CC';} 
    if($ebayvehicle['cid']!='-'){$enginebits[]=$ebayvehicle['cid'].'Cu. In.';} 
    if($ebayvehicle['blocktype']!='-'){$enginebits[]=$ebayvehicle['blocktype'].$ebayvehicle['clyinders'];}
    if($ebayvehicle['fueltypename']!='-'){$enginebits[]=$ebayvehicle['fueltypename'];}
    if($ebayvehicle['cylinderHeadtypename']!='N/R'){$enginebits[]=$ebayvehicle['cylinderHeadtypename'];}
    if($ebayvehicle['aspirationname']!='-'){$enginebits[]=$ebayvehicle['aspirationname'];}
    $niceengine=implode(' ',$enginebits);
    $nicesubmodelname=$ebayvehicle['submodelname']; if(array_key_exists($ebayvehicle['submodelname'], $submodelnametransforms)){$nicesubmodelname=$submodelnametransforms[$ebayvehicle['submodelname']];}
    $output[]=$app['partnumber']."\t".$ebayvehicle['makename']."\t".$ebayvehicle['modelname']."\t".$ebayvehicle['year']."\t".$niceengine."\t".$nicesubmodelname.' '.$ebayvehicle['bodytypename'].' '.$ebayvehicle['bodynumdoors'].'-Door'."\t".$nicefitmentstring;
   }
  }
 }
}


?>
<!DOCTYPE html>
<html lang="en" xml:lang="en">
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
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
                        
                        <form method="get">                            
                            <input type="text" name="receiverprofile"/>
                            <input name="submit" type="submit" value="Go"/>
                        </form>
                        <h5 class="card-header text-start"></h5>
                        <textarea style="height: 600px;"><?php foreach($output as $record){echo $record."\r\n";}?></textarea>
                    </div>
                </div>
                <!-- End of Main Content -->
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
   
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->

        <!-- Footer -->
        
    </body>
</html>