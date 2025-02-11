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
$exportformat='default'; if($_GET['format']=='decoded'){$exportformat='decoded';}

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
 $token=$pim->createBackgroundjob('ACESflatExport','started',$_SESSION['userid'],'',$localfilename,'receiverprofile:'.$receiverprofileid.';DocumentTitle:'.$keyedprofile['DocumentTitle'].';exportformat:'.$exportformat,date('Y-m-d H:i:s'),'text',$clientfilename);
 $logs->logSystemEvent('Export', 0, 'Flat apps file ['.$clientfilename.'] export setup for houskeeper; apps:'.$appscount.' by:'.$_SERVER['REMOTE_ADDR']);
 echo 'This export will contain '.$appscount.' apps. It will be processed by the houskeeper (CLI execution of processFlatAppsExport.php by cron) and be available in a few minutes at <a href="./downloadBackgroundExport.php?token='.$token.'">this link</a>';
 echo '<br/><br/><a href="./index.php">Home</a><br/><a href="./backgroundJobs.php">Manage background import/export jobs</a>';
}
else
{// dataset is small enough to stream it on-the-fly without kicking-off background processing
 $apps=$pim->getAppsByPartcategories($partcategories);

 $filecontent='';

 if($exportformat=='default')
 {
  foreach($apps as $app)
  {
   $vcdbattributesstring=''; $qdbattributesstring=''; $notesstring='';   
   foreach($app['attributes'] as $attribute)
   {
    switch ($attribute['type']) 
    {
     case 'vcdb': $vcdbattributesstring.=$attribute['name'].'|'.$attribute['value'].'|'.$attribute['sequence'].'|'.$attribute['cosmetic'].'~'; break;
     case 'qdb': $qdbattributesstring.=$qdb->qualifierText($attribute['name'], explode('~', str_replace('|','',$attribute['value']))); break;
     case 'note':$notesstring.=$attribute['value'].'|'.$attribute['sequence'].'|'.$attribute['cosmetic'].'~'; break;
     default: break;
    } 
   }
   $filecontent.=$app['cosmetic']."\t".$app['basevehicleid']."\t".$app['partnumber']."\t".$app['parttypeid']."\t".$app['positionid']."\t".$app['quantityperapp']."\t".$app['partnumber']."\t".$vcdbattributesstring."\t".$qdbattributesstring."\t".$notesstring."\r\n";
  }
 }
     
 if($exportformat=='decoded')
 {
  $filecontent.='Make'."\t".'Model'."\t".'Year'."\t".'Partnumber'."\t".'Part-Type'."\t".'Position'."\t".'App-Quantity'."\t".'Fitment Qualifiers'."\r\n";
  foreach($apps as $app)    
  {
   $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
   $niceattributes = array();
   foreach ($app['attributes'] as $appattribute) 
   {
    if ($appattribute['type'] == 'vcdb') {$niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']);}
    if ($appattribute['type'] == 'qdb') {$niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $qdb->qualifierText($appattribute['name'], explode('~', str_replace('|','',$appattribute['value']))), 'cosmetic' => $appattribute['cosmetic']);}
    if ($appattribute['type'] == 'note') {$niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']);}
   }

   $nicefitmentarray = array();    
   foreach ($niceattributes as $niceattribute){$nicefitmentarray[] = $niceattribute['text'];}
   $filecontent.=$mmy['makename']."\t".$mmy['modelname']."\t".$mmy['year']."\t".$app['partnumber']."\t".$pcdb->parttypeName($app['parttypeid'])."\t".$pcdb->positionName($app['positionid'])."\t".$app['quantityperapp']."\t".implode('; ', $nicefitmentarray)."\r\n";
  }
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
