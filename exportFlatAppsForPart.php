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

$randomstring=$pim->newoid();
$clientfilename='FlatApps_'.$randomstring.'_'.date('Y-m-d').'.txt';

if($part=$pim->getPart($_GET['partnumber']))
{
 
 $apps=$pim->getAppsByPartnumber($part['partnumber']);

 $filecontent='';
 foreach($apps as $app)
 {
  $niceattributes=array();
  foreach($app['attributes'] as $appattribute)
  {
   switch ($appattribute['type']) 
   {
    case 'vcdb':
     $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']);
     break;

           case 'qdb':
                $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $qdb->qualifierText($appattribute['name'], explode('~', str_replace('|','',$appattribute['value']))), 'cosmetic' => $appattribute['cosmetic']);
               break;

           case 'note':
               $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']);
               break;

           default:
               break;
       } 
   }
   
  $nicefitmentarray = array();
  foreach ($niceattributes as $niceattribute) 
  {
   $nicefitmentarray[] = $niceattribute['text'];
  }
  $nicefitmentstring=implode('; ', $nicefitmentarray);

  $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
  $filecontent.=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$pcdb->positionName($app['positionid'])."\t".$nicefitmentstring."\r\n";
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
