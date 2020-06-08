<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/logsClass.php');
include_once('./class/ACES4_1GeneratorClass.php');

$pim = new pim();
$vcdb=new vcdb();
$pcdb=new pcdb();
$qdb=new qdb();

$logs=new logs();
$generator=new ACESgenerator();

$streamXML=true;
$showXML=false;
$logicerrors=array();
$errors=array();

// an "export profile" includes (css-style name-value pairs)
// 
//  

$profile=$pim->getReceiverprofileById(intval($_POST['receiverprofile']));
$profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';



$appscount=$pim->countAppsByAppcategories(array(17));

if($appscount>10000)
{ // dataset is too big - this export will be handled by the housekeeper (cron "wget") and written to a temp directory for download
 $streamXML=false;
    
    
}
else
{// dataset is small enough to stream it on-the-fly without kicking-off background processing
 $apps=$pim->getAppsByAppcategories(array(17));
}

$filename='ACES_4_1_FULL_'.date('Y-m-d').'.xml';

$profileelements=explode(';',$profiledata);
$keyedprofile=array();
foreach($profileelements as $profileelement)
{
    $bits=explode(':',$profileelement);
    if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}
}

$header=array(
  'Company'=>'not set in profile ['.$profile['name'].']',
  'SenderName'=>'not set in profile ['.$profile['name'].']',
  'SenderPhone'=>'not set in profile ['.$profile['name'].']',
  'BrandAAIAID'=>'XXXX',
  'DocumentTitle'=>'not set in profile ['.$profile['name'].']',
  'TransferDate'=>date('Y-m-d'),
  'EffectiveDate'=>date('Y-m-d'),
  'SubmissionType'=>'FULL',
  'VcdbVersionDate'=>$vcdb->version(),
  'QdbVersionDate'=>$qdb->version(),
  'PcdbVersionDate'=>$pcdb->version());

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
}

if(count($schemaresults)==0 && $showXML)
{
 echo '<textarea rows="20" cols="150">'.$acesxml.'</textarea>';
}


$logs->logSystemEvent('Export', 0, 'ACES file:'.$filename.';apps:'.count($apps).';xsd:'.count($schemaresults).';logic:'.count($errors).';by:'.$_SERVER['REMOTE_ADDR']);

if($streamXML)
{   
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($acesxml));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $acesxml;
}
?>
