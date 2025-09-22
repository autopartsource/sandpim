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
 $logs->logSystemEvent('accesscontrol',0, 'appSearchStream.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
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

$randomstring=$pim->newoid();
$clientfilename='FlatApps_'.$randomstring.'_'.date('Y-m-d').'.txt';

$apps=$pim->getAppsByPartcategories($partcategories);



$filecontent='';

// get an array of apps



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

$logs->logSystemEvent('Export', 0, 'Flat apps file ['.$clientfilename.'] exported; apps:'.count($apps).' by:'.$_SERVER['REMOTE_ADDR']);

header('Content-Disposition: attachment; filename="'.$clientfilename.'"');
header('Content-Type: text');
header('Content-Length: ' . strlen($filecontent));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');
echo $filecontent;