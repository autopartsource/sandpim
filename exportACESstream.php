<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/logsClass.php');
include_once('./class/ACES4_1GeneratorClass.php');

$navCategory = 'import/export';

session_start();
if(!isset($_SESSION['userid']))
{
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
 exit;
}


$pim = new pim();
$vcdb=new vcdb();
$pcdb=new pcdb();
$qdb=new qdb();

$logs=new logs();
$generator=new ACESgenerator();

$streamXML=true;
$logicerrors=array();
$errors=array();

$receiverprofileid=intval($_POST['receiverprofile']);
$profile=$pim->getReceiverprofileById($receiverprofileid);
$profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';
$appcategories=$pim->getReceiverprofileAppcategories($receiverprofileid);
$appscount=$pim->countAppsByAppcategories($appcategories);

if($appscount>5000)
{ // dataset is too big - this export will be handled by the housekeeper (cron "wget") and written to a temp directory for download
 $streamXML=false;
 $filename=__DIR__.'/ACESexports/ACES_4_1_'.$pim->newoid().'_FULL_'.date('Y-m-d').'.xml';
 echo 'This export will contain '.$appscount.' apps. It will be processed by the houskeeper and be available at: '.$filename;
 $pim->createBackgroundjob('ACESxmlExport','started',$_SESSION['userid'],'',$filename,'receiverprofile:'.$receiverprofileid.';',date('Y-m-d H:i:s'));
 $logs->logSystemEvent('Export', 0, 'ACES file ['.$filename.'] export setup for houskeeper; apps:'.count($apps).' by:'.$_SERVER['REMOTE_ADDR']);
}
else
{// dataset is small enough to stream it on-the-fly without kicking-off background processing
 $apps=$pim->getAppsByAppcategories($appcategories);
 $filename='ACES_4_1_FULL_'.date('Y-m-d').'.xml';
 $profileelements=explode(';',$profiledata);
 $keyedprofile=array();
 foreach($profileelements as $profileelement)
 {
  $bits=explode(':',$profileelement);
  if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}
 }

 $header=array('Company'=>'not set in profile ['.$profile['name'].']','SenderName'=>'not set in profile ['.$profile['name'].']', 'SenderPhone'=>'not set in profile ['.$profile['name'].']','BrandAAIAID'=>'XXXX','DocumentTitle'=>'not set in profile ['.$profile['name'].']','TransferDate'=>date('Y-m-d'),'EffectiveDate'=>date('Y-m-d'),'SubmissionType'=>'FULL','VcdbVersionDate'=>$vcdb->version(),'QdbVersionDate'=>$qdb->version(),'PcdbVersionDate'=>$pcdb->version());
 if(array_key_exists('Company', $keyedprofile)){$header['Company']=$keyedprofile['Company'];}
 if(array_key_exists('SenderName', $keyedprofile)){$header['SenderName']=$keyedprofile['SenderName'];}
 if(array_key_exists('SenderPhone', $keyedprofile)){$header['SenderPhone']=$keyedprofile['SenderPhone'];}
 if(array_key_exists('BrandAAIAID', $keyedprofile)){$header['BrandAAIAID']=$keyedprofile['BrandAAIAID'];}
 if(array_key_exists('DocumentTitle', $keyedprofile)){$header['DocumentTitle']=$keyedprofile['DocumentTitle'];}
 if(array_key_exists('ApprovedFor', $keyedprofile)){$header['ApprovedFor']=$keyedprofile['ApprovedFor'];}
 if(array_key_exists('MapperCompany', $keyedprofile)){$header['MapperCompany']=$keyedprofile['MapperCompany'];}
 if(array_key_exists('MapperContact', $keyedprofile)){$header['MapperContact']=$keyedprofile['MapperContact'];}

 $assetapps=array();
 $generatoroptions=array('IncludeCosmeticApps'=>true,'IncludeCosmeticAttributes'=>true,);

 $doc=$generator->createACESdoc($header,$apps,$assetapps,$generatoroptions);//,$descriptions,$prices,$expi,$attributes,$packages,$kits,$interchanges,$assets);
 $doc->formatOutput=true;
 $acesxml=$doc->saveXML();

 $schemavalidated=true;   
 $schemaresults=array();
 libxml_use_internal_errors(true);
 if(!$doc->schemaValidate('ACES_4_1_XSDSchema_Rev1.xsd'))
 {
  $schemavalidated=false;
  $schemaerrors = libxml_get_errors();
  foreach ($schemaerrors as $schemaerror)
  {
   switch ($schemaerror->level) 
   {
    case LIBXML_ERR_WARNING:
 //    $schemaresults[]='Warning code '. $schemaerror->code;
     break;
    case LIBXML_ERR_ERROR:
  //   $schemaresults[]='Error code '.$schemaerror->code;
     break;
    case LIBXML_ERR_FATAL:
     $schemaresults[]='Fatal Error code '.$schemaerror->code;
     break;
   }
   $schemaresults[]=trim($schemaerror->message);   
  }
  libxml_clear_errors();
 }
 
 if(count($schemaresults)>0)
 {
  $streamXML=false;
  echo '<div style="margin:10px; background-color:#ffc0c0;"><div style="font-size:1.5em;font-weight:bold;">Scheama (XSD) problems</div>';
  foreach($schemaresults as $result)
  { // render each element of schema problems into a table
   echo '<div style="padding:8px">'.$result.'</div>';
  }
  echo '</div>';
  $logs->logSystemEvent('Export', 0, 'failed ACES export (schema problems):'.$filename.';apps:'.count($apps).' by:'.$_SERVER['REMOTE_ADDR']);
 }

}
if($streamXML)
{   
 $logs->logSystemEvent('Export', 0, 'ACES file ['.$filename.'] exported; apps:'.count($apps).' by:'.$_SERVER['REMOTE_ADDR']);

 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($acesxml));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $acesxml;
}
?>
