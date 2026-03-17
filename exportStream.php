<?php
/*
 * and "export" is a saved snapshot of apps,parts or assets from a spcecific 
 * receiver profile for a stated purpose (audit, release-candidate, publishing, etc)
 * 
 * exports can be captured to current-state for that receiver - consider the 
 * case where an aces file was rendered for Epicor or WHI. We would not know for
 * a week or two that the file was accepted and processed. During those weeks,
 * life moves on and many apps,items,assets come and go in our local source-of-truth
 * the snapshot that was taken at render-time can me "captured" to the ongoing ledger
 * (receiver_appstate) we can keep for them at the time we get word that the file was 
 * accepted.
 * 
 */
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');

$navCategory = 'export';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exportStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
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

$exportid=intval($_GET['exportid']);
$exportformat='raw-apps'; if(in_array($_GET['format'],['raw-apps','decoded-apps','meta'])){$exportformat=$_GET['format'];}
$clientfilename='export_'.$exportid.'_'.$exportformat.'_'.date('Y-m-d').'.txt';
$appcount=0;
$filecontent='';

$export=$pim->getExport($exportid);        // $export=array('id'=>$row['id'],'datetimeexported'=>$row['datetimeexported'],'receiverprofileid'=>$row['receiverprofileid'],'type'=>$row['type'],'identifier'=>$row['identifier'],'notes'=>$row['notes']);
if(!$export){exit;}

$records=$pim->getExportDetail($exportid);  //$records[]=array('id'=>$row['id'],'exportid'=>$row['exportid'],'objecttype'=>$row['objecttype'],'objectdata'=>$row['objectdata'],'keynumeric'=>$row['keynumeric'],'keyalhpa'=>$row['keyalhpa'],'oid'=>$row['oid']);
$explanation='Exported on: '.$export['datetimeexported'].' for receiver Profile: '.$pim->receiverprofileName($export['receiverprofileid']).' with notes: '.$export['notes'];

if($exportformat=='raw-apps')
{
 $filecontent="App ID\tState\tcosmetic\tbasevehicleid\tpartnumber\tparttypeid\tpositionid\tquantityperapp\tpartnumber\tVCdbAttributes\tQdbAttributes\tNotes\t".$explanation."\r\n";
    
 foreach($records as $record)
 {
  if($record['objecttype']!='APP'){continue;}
  $appcount++;
  $app=unserialize($record['objectdata']);
  
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
  $filecontent.=$app['id']."\t".$app['oid']."\t".$app['cosmetic']."\t".$app['basevehicleid']."\t".$app['partnumber']."\t".$app['parttypeid']."\t".$app['positionid']."\t".$app['quantityperapp']."\t".$app['partnumber']."\t".$vcdbattributesstring."\t".$qdbattributesstring."\t".$notesstring."\r\n";
 }
}
    
if($exportformat=='decoded-apps')
{
 $filecontent='App ID'."\t".'State'."\t".'cosmetic'."\t".'Make'."\t".'Model'."\t".'Year'."\t".'Partnumber'."\t".'Part-Type'."\t".'Position'."\t".'App-Quantity'."\t".'Fitment Qualifiers'."\t".$explanation."\r\n";
 foreach($records as $record)    
 {
  if($record['objecttype']!='APP'){continue;}
  $appcount++;
  $app=unserialize($record['objectdata']);
     
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
  $filecontent.=$app['id']."\t".$app['oid']."\t".$app['cosmetic']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$mmy['year']."\t".$app['partnumber']."\t".$pcdb->parttypeName($app['parttypeid'])."\t".$pcdb->positionName($app['positionid'])."\t".$app['quantityperapp']."\t".implode('; ', $nicefitmentarray)."\r\n";
 }
}

if($exportformat=='meta')
{
 $filecontent="App ID\tState\t".$explanation."\r\n";
 foreach($records as $record)
 {
  if($record['objecttype']!='APP'){continue;}
  $appcount++;
  $filecontent.=$record['keynumeric']."\t".$record['oid']."\r\n";
 }
}
 
header('Content-Disposition: attachment; filename="'.$clientfilename.'"');
header('Content-Type: text');
header('Content-Length: ' . strlen($filecontent));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');
echo $filecontent;

$logs->logSystemEvent('Export', $_SESSION['userid'], 'Export ['.$exportid.'] downloaded as format ['.$exportformat.']; apps:'.$appcount.' by:'.$_SERVER['REMOTE_ADDR']);