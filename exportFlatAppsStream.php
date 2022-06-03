<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');

$navCategory = 'export';

$pim = new pim();
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exportFlatAppsStream.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid']))
{
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
 exit;
}

$vcdb=new vcdb();
$pcdb=new pcdb();
$user=new user();
$qdb=new qdb();
$logs=new logs();

$receiverprofileid=intval($_GET['receiverprofile']);
$format=''; if($_GET['format']=='human1'){$format='human1';}

$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$profile=$pim->getReceiverprofileById($receiverprofileid);
$profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';

$profileelements=explode(';',$profiledata);
$keyedprofile=array();
foreach($profileelements as $profileelement)
{
 $bits=explode(':',$profileelement);
 if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}
}

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$appscount=$pim->countAppsByPartcategories($partcategories);
$randomstring=$pim->newoid();
$clientfilename='FlatApps_'.$keyedprofile['DocumentTitle'].'_'.$randomstring.'_FULL_'.date('Y-m-d').'.txt';

if($appscount>5000)
{ // dataset is too big - this export will be handled by the housekeeper (cron) and written to a temp directory for download
 
 $localfilename=__DIR__.'/ACESexports/'.$randomstring;
 $token=$pim->createBackgroundjob('ACESflatExport','started',$_SESSION['userid'],'',$localfilename,'receiverprofile:'.$receiverprofileid.';DocumentTitle:'.$keyedprofile['DocumentTitle'].';',date('Y-m-d H:i:s'),'text',$clientfilename);
 $logs->logSystemEvent('Export', 0, 'Flat apps file ['.$clientfilename.'] export setup for houskeeper; apps:'.$appscount.' by:'.$_SERVER['REMOTE_ADDR']);
 echo 'This export will contain '.$appscount.' apps. It will be processed by the houskeeper (CLI execution of processFlatAppsExport.php by cron) and be available in a few minutes at <a href="./downloadBackgroundExport.php?token='.$token.'">this link</a>';
 echo '<br/><br/><a href="./index.php">Home</a><br/><a href="./backgroundJobs.php">Manage background import/export jobs</a>';
}
else
{// dataset is small enough to stream it on-the-fly without kicking-off background processing
 $apps=$pim->getAppsByPartcategories($partcategories);

 $filecontent='';
 foreach($apps as $app)
 {
  $vcdbattributesstring=''; $qdbattributesstring=''; $notesstring='';
     foreach($app['attributes'] as $attribute)
   {
       switch ($attribute['type']) {
           case 'vcdb':
               $vcdbattributesstring.=$attribute['name'].'|'.$attribute['value'].'|'.$attribute['sequence'].'|'.$attribute['cosmetic'].'~';
               break;

           case 'qdb':
               break;

           case 'note':
               $notesstring.=$attribute['value'].'|'.$attribute['sequence'].'|'.$attribute['cosmetic'].'~';
               break;

           default:
               break;
       } 
   }

  $filecontent.=$app['cosmetic']."\t".$app['basevehicleid']."\t".$app['partnumber']."\t".$app['parttypeid']."\t".$app['positionid']."\t".$app['positionid']."\t".$app['quantityperapp']."\t".$app['partnumber']."\t".$vcdbattributesstring."\t".$qdbattributesstring."\t".$notesstring."\r\n";
 }
 
 header('Content-Disposition: attachment; filename="'.$clientfilename.'"');
 header('Content-Type: text');
 header('Content-Length: ' . strlen($filecontent));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $filecontent;

 $logs->logSystemEvent('Export', 0, 'Flat apps file ['.$clientfilename.'] exported; apps:'.count($apps).' by:'.$_SERVER['REMOTE_ADDR']);
}
?>
