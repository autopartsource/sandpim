<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/ACES4_1GeneratorClass.php');
include_once('./class/XLSXReaderClass.php');
$navCategory = 'import';

session_start();

$pim = new pim;
$ACESgenerator=new ACESgenerator();
$pcdb=new pcdb($_POST['pcdbversion']);
$vcdb=new vcdb($_POST['vcdbversion']);
$qdb=new qdb($_POST['qdbversion']);
$pcdbVersion=$pcdb->version();
$vcdbVersion=$vcdb->version();
$qdbVersion=$qdb->version();
$logs=new logs();

$acesxmlstring='';
$streamXML=true;

$originalFilename='';
$validUpload=false;
$inputFileLog=array();
$apps=array();
$header=array('Company'=>'ACME Anvils','SenderName'=>'Luke Smith','SenderPhone'=>'804-329-3000','TransferDate'=>'2020-10-17','DocumentTitle'=>'stuff','EffectiveDate'=>'2020-10-17','SubmissionType'=>'FULL','VcdbVersionDate'=>'2020-08-28','QdbVersionDate'=>'2020-08-28','PcdbVersionDate'=>'2020-08-28');
$assets=array();
$options=array();
        
$validcolumns=array('PartNumber','Brand','AppID','GroupID','Qty','MfrLabel','DisplayOrder','PartType','Make','Model','SubModel','YearFrom','YearTo','Region','BaseModelType','BedLength','BedType','BodyDoor','BodyType','BrakeAbs','BrakeSystem','Drive','EngineAspiration','EngineBaseBlockType','EngineBaseBoreInch','EngineBaseBoreMetric','EngineBaseCubicCentimeter','EngineBaseCubicInch','EngineBaseCylinder','EngineBaseLiter','EngineBaseStrokeInch','EngineBaseStrokeMetric','EngineCylinderHeadType','EngineDesignation','EngineFuelDeliverySubType','EngineFuelDeliverySystemControlType','EngineFuelDeliverySystemDesign','EngineFuelDeliveryType','EngineFuelType','EngineIgnitionSystemType','EngineMfr','EnginePowerOutput','EngineValve','EngineVersion','EngineVin','FrontBrakeType','FrontSpringType','MfrBodyCode','Position','RearBrakeType','RearSpringType','SteeringSystem','SteeringType','TransmissionBaseControlType','TransmissionBaseGear','TransmissionBaseType','TransmissionElectronociallyControlledInformation','TransmissionMfrCode','TransmissionMfr','WheelBase','Notes','NotesRecordNumber','Qualifier','QualifierValues','QualifierText','AssetName','AssetType','AssetRepresentation','LanguageCode','LanguageName','ParentPartNumber','ParentBrand');
$partnumbercolumnid=-1; $brandcolumnid=-1; $appidcolumnid=-1; $groupidcolumnid=-1; $qtycolumnid=-1; $mfrlabelcolumnid=-1; $displayordercolumnid=-1; $parttypecolumnid=-1; $makenamecolumnid=-1; $modelnamecolumnid=-1; $submodelcolumnid=-1; $yearfromcolumnid=-1; $yeartocolumnid=-1; $regioncolumnid=-1; $basemodeltypecolumnid=-1; $bedlengthcolumnid=-1; $bedtypecolumnid=-1; $bodydoorcolumnid=-1; $bodytypecolumnid=-1; $brakeabscolumnid=-1; $brakesystemcolumnid=-1; $drivecolumnid=-1;  $engineaspirationcolumnid=-1;  $enginebaseblocktypecolumnid=-1;  $enginebaseboreinchcolumnid=-1;  $enginebaseboremetriccolumnid=-1;  $enginebaseborecentimetercolumnid=-1;  $enginebasecubicinchcolumnid=-1;  $enginebasecylindercolumnid=-1;  $enginebaselitercolumnid=-1;  $enginebasestrokeinchcolumnid=-1;  $enginebasestrokemetriccolumnid=-1;  $enginecylinderheadtypecolumnid=-1;  $enginedesignationcolumnid=-1;  $enginefueldeliverysubtypecolumnid=-1;  $enginefueldeliverysystemcontroltypecolumnid=-1;  $enginefueldeliverysystemdesigncolumnid=-1;  $enginefueldeliverytypecolumnid=-1;  $enginefueltypecolumnid=-1;  $engineignitionsystemtypecolumnid=-1;  $enginemfrcolumnid=-1;  $enginepoweroutputcolumnid=-1;  $enginevalvecolumnid=-1;  $engineversioncolumnid=-1;  $enginevincolumnid=-1;  $frontbraketypecolumnid=-1;  $frontspringtypecolumnid=-1;  $mfrbodycodecolumnid=-1;  $positioncolumnid=-1;  $rearbraketypecolumnid=-1;  $rearspringtypecolumnid=-1;  $steeringsystemcolumnid=-1;  $steeringtypecolumnid=-1; $transmissionbasecontroltypecolumnid=-1;  $transmissionbasegearcolumnid=-1; $transmissionbasetypecolumnid=-1; $transmissionelectronociallycontrolledinformationcolumnid=-1;  $transmissionmfrcodecolumnid=-1; $transmissionmfrcolumnid=-1; $wheelbasecolumnid=-1; $notescolumnid=-1;  $qualifiertextcolumnid=-1; $assetnamecolumnid=-1; $assettypecolumnid=-1; $assetrepresentationcolumnid=-1;  $languagecodecolumnid=-1;  $languagenamecolumnid=-1; $parentpartnumbercolumnid=-1; $parentbrandcolumnid=-1;
$columnids=array();

$basevidscache=array();
$positionidscache=array();
$parttypenamescache=array();
$regionidscache=array();
$bedlengthcache=array();
$bedtypeidscache=array();
$bodynumdoorscache=array();
$bodytypeidscache=array();
$brakeabsidscache=array();
$brakesystemidscache=array();
        
if(isset($_POST['submit']) && $_POST['submit']=='Generate ACES xml')
{
 if($_FILES['fileToUpload']['type']=='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
 {
  if($_FILES['fileToUpload']['size']<5000000 || isset($_SESSION['userid']))   
  {     
   
   $xlsx = new XLSXReader($_FILES['fileToUpload']['tmp_name']);
   $sheetNames = $xlsx->getSheetNames();
   $originalFilename= basename($_FILES['fileToUpload']['name']);

   if(in_array('Applications',$sheetNames))
   {
    $appsSheet=$xlsx->getSheetData('Applications');
   
    $validUpload=true;
    
     if(isset($appsSheet[0][0]) && $appsSheet[0][0]=='PartNumber' )
     {
         
         
     }
     else
     {
      $validUpload=false;
      $inputFileLog[]='First row must contain these three columns: PartNumber, PartType, Make, Model, YearFrom, YearTo';
      $logs->logSystemEvent('rhubarb', 0, 'First row must contain these three columns: PartNumber, PartType, Make, Model, YearFrom, YearTo');
     }
   }
   else
   { // Header or Items sheets are not present
    $inputFileLog[]='Uploaded workbook does not contain required worksheet named Applications'; 
    $logs->logSystemEvent('rhubarb', 0, 'Uploaded workbook does not contain required worksheet named Applications');
   }
  }
  else
  {
   $inputFileLog[]='Input file was too big (5M limit for anonymous users)';
   $logs->logSystemEvent('rhubarb', 0, 'Input file was too big (1M limit for anonymous users');
  }
 }
 else
 {
  $inputFileLog[]='Error uploading file - un-supported file format (must be a valid xlsx file)';
  $logs->logSystemEvent('rhubarb', 0, 'Error uploading file - un-supported file format');
 }
}

$errors=array(); $warnings=array(); $schemaresults=array();

if($validUpload)
{
     
 // Establish a map of column names (identified by the first row - which is element 0) and their relative column number.
 // this way, there does not have to be a specific order of columns in the input data - we just need to 
 // verify that all of the required elements are present.
 foreach ($appsSheet[0] as $columnnumber=>$columnname)
 {
  if(in_array($columnname, $validcolumns)){$columnids[$columnname]=$columnnumber;}
 }     

 $partnumbercolumnid=$columnids['PartNumber'];
 $brandcolumnid=$columnids['Brand'];
 $appidcolumnid=$columnids['AppID'];
 $groupidcolumnid=$columnids['GroupID'];
 $qtycolumnid=$columnids['Qty'];
 $mfrlabelcolumnid=$columnids['MfrLabel'];
 $displayordercolumnid=$columnids['DisplayOrder'];
 $parttypecolumnid=$columnids['PartType'];
 $makenamecolumnid=$columnids['Make'];
 $modelnamecolumnid=$columnids['Model'];
 $submodelcolumnid=$columnids['SubModel'];
 $yearfromcolumnid=$columnids['YearFrom'];
 $yeartocolumnid=$columnids['YearTo'];
 $regioncolumnid=$columnids['Region'];
 $basemodeltypecolumnid=$columnids['BaseModelType'];
 $bedlengthcolumnid=$columnids['BedLength'];
 $bedtypecolumnid=$columnids['BedType'];
 $bodydoorcolumnid=$columnids['BodyDoor'];
 $bodytypecolumnid=$columnids['BodyType'];
 $brakeabscolumnid=$columnids['BrakeAbs'];
 $brakesystemcolumnid=$columnids['BrakeSystem'];
 $drivecolumnid=$columnids['Drive'];
 $engineaspirationcolumnid=$columnids['EngineAspiration'];
 $enginebaseblocktypecolumnid=$columnids['EngineBaseBlockType'];
 $enginebaseboreinchcolumnid=$columnids['EngineBaseBoreInch'];
 $enginebaseboremetriccolumnid=$columnids['EngineBaseBoreMetric'];
 $enginebaseborecentimetercolumnid=$columnids['EngineBaseCubicCentimeter'];
 $enginebasecubicinchcolumnid=$columnids['EngineBaseCubicInch'];
 $enginebasecylindercolumnid=$columnids['EngineBaseCylinder'];
 $enginebaselitercolumnid=$columnids['EngineBaseLiter'];
 $enginebasestrokeinchcolumnid=$columnids['EngineBaseStrokeInch'];
 $enginebasestrokemetriccolumnid=$columnids['EngineBaseStrokeMetric'];
 $enginecylinderheadtypecolumnid=$columnids['EngineCylinderHeadType'];
 $enginedesignationcolumnid=$columnids['EngineDesignation'];
 $enginefueldeliverysubtypecolumnid=$columnids['EngineFuelDeliverySubType'];
 $enginefueldeliverysystemcontroltypecolumnid=$columnids['EngineFuelDeliverySystemControlType'];
 $enginefueldeliverysystemdesigncolumnid=$columnids['EngineFuelDeliverySystemDesign'];
 $enginefueldeliverytypecolumnid=$columnids['EngineFuelDeliveryType'];
 $enginefueltypecolumnid=$columnids['EngineFuelType'];
 $engineignitionsystemtypecolumnid=$columnids['EngineIgnitionSystemType'];
 $enginemfrcolumnid=$columnids['EngineMfr'];
 $enginepoweroutputcolumnid=$columnids['EnginePowerOutput'];
 $enginevalvecolumnid=$columnids['EngineValve'];
 $engineversioncolumnid=$columnids['EngineVersion'];
 $enginevincolumnid=$columnids['EngineVin'];
 $frontbraketypecolumnid=$columnids['FrontBrakeType'];
 $frontspringtypecolumnid=$columnids['FrontSpringType'];
 $mfrbodycodecolumnid=$columnids['MfrBodyCode'];
 $positioncolumnid=$columnids['Position'];
 $rearbraketypecolumnid=$columnids['RearBrakeType'];
 $rearspringtypecolumnid=$columnids['RearSpringType'];
 $steeringsystemcolumnid=$columnids['SteeringSystem'];
 $steeringtypecolumnid=$columnids['SteeringType'];
 $transmissionbasecontroltypecolumnid=$columnids['TransmissionBaseControlType'];
 $transmissionbasegearcolumnid=$columnids['TransmissionBaseGear'];
 $transmissionbasetypecolumnid=$columnids['TransmissionBaseType'];
 $transmissionelectronociallycontrolledinformationcolumnid=$columnids['TransmissionElectronociallyControlledInformation'];
 $transmissionmfrcodecolumnid=$columnids['TransmissionMfrCode'];
 $transmissionmfrcolumnid=$columnids['TransmissionMfr'];
 $wheelbasecolumnid=$columnids['WheelBase'];
 $notescolumnid=$columnids['Notes'];
 $qualifiertextcolumnid=$columnids['QualifierText'];
 $assetnamecolumnid=$columnids['AssetName'];
 $assettypecolumnid=$columnids['AssetType'];
 $assetrepresentationcolumnid=$columnids['AssetRepresentation'];
 $languagecodecolumnid=$columnids['LanguageCode'];
 $languagenamecolumnid=$columnids['LanguageName'];
 $parentpartnumbercolumnid=$columnids['ParentPartNumber'];
 $parentbrandcolumnid=$columnids['ParentBrand'];
 


 foreach ($appsSheet as $rownumber=>$row)
 {
  if($rownumber==0){continue;}  // skip the header row
  // get basevid
  
  $makename=$row[$makenamecolumnid];
  $modelname=$row[$modelnamecolumnid];
  $yearfrom=intval($row[$yearfromcolumnid]);
  $yearto=intval($row[$yeartocolumnid]);
          
  $basevids=array();
  if($yearfrom==$yearto)
  {// single year
   if(array_key_exists($makename.'_'.$modelname.'_'.$yearfrom, $basevidscache))
   {// seen this basevid before - get the id from the cache
    $basevids[]=$basevidscache[$makename.'_'.$modelname.'_'.$yearfrom];
   }
   else
   {// have not seen this basevid yet - look it up in the vcdb and cache it
    $basevid=$vcdb->getBasevehicleidForMMY($makename, $modelname, $yearfrom);
    $basevids[]=$basevid;
    $basevidscache[$makename.'_'.$modelname.'_'.$yearfrom]=$basevid;
   }
  }
  else
  {// year range
   for($i=$yearfrom; $i<=$yearto; $i++)
   {
    if(array_key_exists($makename.'_'.$modelname.'_'.$i, $basevidscache))
    {// seen this basevid before - get the id from the cache
     $basevids[]=$basevidscache[$makename.'_'.$modelname.'_'.$i];
    }
    else
    {// have not seen this basevid yet - look it up in the vcdb and cache it
     $basevid=$vcdb->getBasevehicleidForMMY($makename, $modelname, $i);
     $basevids[]=$basevid;
     $basevidscache[$makename.'_'.$modelname.'_'.$i]=$basevid;   
    }    
   }
  }
  
  foreach($basevids as $basevid)
  {// 1 or more basevids come from each spreadsheet row
    
   $goodrecord=true;
   $partnumber=$row[$partnumbercolumnid];
   $ref=$row[$appidcolumnid];
   $notes=$row[$notescolumnid];
   $mfrlabel=$row[$mfrlabelcolumnid];
   $qty=$row[$qtycolumnid];
   $attributes=array();
   
   $positionname=$row[$positioncolumnid];
   if(array_key_exists($positionname, $positionidscache))
   {// position name is already in cache
    $positionid=$positionidscache[$positionname];
   }
   else
   {// no hit on position name cache - look it up and add it to the cache
    $positionid=$pcdb->positionIDofName($positionname);
    $positionidscache[$positionname]=$positionid;
   }
     
   $parttypename=$row[$parttypecolumnid];
   if(array_key_exists($parttypename, $parttypenamescache))
   {// parttype name is already in cache
    $parttypeid=$parttypenamescache[$parttypename];
   }
   else
   {// no hit on parttype name cache - look it up and add it to the cache
    $parttypeid=$pcdb->parttypeIDofName($parttypename);
    $parttypenamescache[$parttypename]=$parttypeid;
   }   

   $regionname=trim($row[$regioncolumnid]);
   if($regionname!='')
   {
    if(array_key_exists($regionname, $regionidscache))
    {// region name is already in cache
     $regionid=$regionidscache[$regionname];
    }
    else
    {// no hit on region name cache - look it up and add it to the cache
     $regionid=$vcdb->regionIDofRegionName($regionname);
     $regionidscache[$regionname]=$regionid;
    }   
    if($regionid===false)
    {// name supplied was not found in the VCdb
     $errors[]='row '.$rownumber.' contains a region name ['.$regionname.'] that was not found in the VCdb';
     $goodrecord=false;
    }
    else
    {// successful lookup of value
     $attributes[]=array('id'=>0,'name'=>'Region','value'=>$regionid,'type'=>'vcdb','sequence'=>1,'cosmetic'=>0);
    }
   }


   $bedlength=trim($row[$bedlengthcolumnid]);
   if($bedlength!='')
   {
    if(array_key_exists($bedlength, $bedlengthcache))
    {// bedlength name is already in cache
     $bedlengthid=$bedlengthcache[$bedlength];
    }
    else
    {// no hit on bedlength cache - look it up and add it to the cache
     $bedlengthid=$vcdb->bedlengthIDofBedlength($bedlength);
     $bedlengthcache[$bedlength]=$bedlengthid;
    }   
    if($bedlengthid===false)
    {// bedlength supplied was not found in the VCdb
     $errors[]='row '.$rownumber.' contains a BedLength ['.$bedlength.'] that was not found in the VCdb';
     $goodrecord=false;
    }
    else
    {// successful lookup of bedlength
     $attributes[]=array('id'=>0,'name'=>'BedLength','value'=>$bedlengthid,'type'=>'vcdb','sequence'=>1,'cosmetic'=>0);
    }
   }

   $bedtypename=trim($row[$bedtypecolumnid]);
   if($bedtypename!='')
   {
    if(array_key_exists($bedtypename, $bedtypeidscache))
    {// bedtype name is already in cache
     $bedtypeid=$bedtypeidscache[$bedtypename];
    }
    else
    {// no hit on bedlength cache - look it up and add it to the cache
     $bedtypeid=$vcdb->bedtypeIDofBedtypeName($bedtypename);
     $bedtypeidscache[$bedtypename]=$bedtypeid;
    }   
    if($bedtypeid===false)
    {// bedtype supplied was not found in the VCdb
     $errors[]='row '.$rownumber.' contains a BedType ['.$bedtypename.'] that was not found in the VCdb';
     $goodrecord=false;
    }
    else
    {// successful lookup of bedtype
     $attributes[]=array('id'=>0,'name'=>'BedType','value'=>$bedtypeid,'type'=>'vcdb','sequence'=>1,'cosmetic'=>0);
    }
   }

   $bodynumdoors=trim($row[$bodydoorcolumnid]);
   if($bodynumdoors!='')
   {
    if(array_key_exists($bodynumdoors, $bodynumdoorscache))
    {// bedtype name is already in cache
     $bodynumdoorsid=$bodynumdoorscache[$bodynumdoors];
    }
    else
    {// no hit on bedlength cache - look it up and add it to the cache
     $bodynumdoorsid=$vcdb->bodynumdoorsIDofBodyNumDoors($bodynumdoors);
     $bodynumdoorscache[$bodynumdoors]=$bodynumdoorsid;
    }   
    if($bodynumdoorsid===false)
    {// bedtype supplied was not found in the VCdb
     $errors[]='row '.$rownumber.' contains a BodyNumDoors ['.$bodynumdoors.'] that was not found in the VCdb';
     $goodrecord=false;
    }
    else
    {// successful lookup of bedtype
     $attributes[]=array('id'=>0,'name'=>'BodyNumDoors','value'=>$bodynumdoorsid,'type'=>'vcdb','sequence'=>1,'cosmetic'=>0);
    }
   }
   
   
   $bodytypename=trim($row[$bodytypecolumnid]);
   if($bodytypename!='')
   {
    if(array_key_exists($bodytypename, $bodytypeidscache))
    {// bedtype name is already in cache
     $bodytypeidid=$bodytypeidscache[$bodytypename];
    }
    else
    {// no hit on bedlength cache - look it up and add it to the cache
     $bodytypeidid=$vcdb->bodytypeIDofBodyTypeName($bodytypename);
     $bodytypeidscache[$bodytypename]=$bodytypeidid;
    }   
    if($bodytypeidid===false)
    {// bedtype supplied was not found in the VCdb
     $errors[]='row '.$rownumber.' contains a BodyType name ['.$bodytypename.'] that was not found in the VCdb';
     $goodrecord=false;
    }
    else
    {// successful lookup of bedtype
     $attributes[]=array('id'=>0,'name'=>'BodyType','value'=>$bodytypeidid,'type'=>'vcdb','sequence'=>1,'cosmetic'=>0);
    }
   }
   
   
   
   
   $brakeabsname=trim($row[$brakeabscolumnid]);
   if($brakeabsname!='')
   {
    if(array_key_exists($brakeabsname, $brakeabsidscache))
    {// bedtype name is already in cache
     $brakeabsid=$brakeabsidscache[$brakeabsname];
    }
    else
    {// no hit on bedlength cache - look it up and add it to the cache
     $brakeabsid=$vcdb->brakeabsIDofBrakeAbsName($brakeabsname);
     $brakeabsidscache[$brakeabsname]=$brakeabsid;
    }   
    if($brakeabsid===false)
    {// bedtype supplied was not found in the VCdb
     $errors[]='row '.$rownumber.' contains a BrakeABS name ['.$brakeabsname.'] that was not found in the VCdb';
     $goodrecord=false;
    }
    else
    {// successful lookup of bedtype
     $attributes[]=array('id'=>0,'name'=>'BrakeABS','value'=>$brakeabsid,'type'=>'vcdb','sequence'=>1,'cosmetic'=>0);
    }
   }
      
   $brakesystemname=trim($row[$brakesystemcolumnid]);
   if($brakesystemname!='')
   {
    if(array_key_exists($brakesystemname, $brakesystemidscache))
    {// bedtype name is already in cache
     $brakesystemid=$brakesystemidscache[$brakesystemname];
    }
    else
    {// no hit on bedlength cache - look it up and add it to the cache
     $brakesystemid=$vcdb->brakesystemIDofBrakeAbsName($brakesystemname);
     $brakesystemidscache[$brakesystemname]=$brakesystemid;
    }   
    if($brakesystemid===false)
    {// bedtype supplied was not found in the VCdb
     $errors[]='row '.$rownumber.' contains a BrakeSystem name ['.$brakesystemname.'] that was not found in the VCdb';
     $goodrecord=false;
    }
    else
    {// successful lookup of bedtype
     $attributes[]=array('id'=>0,'name'=>'BrakeSystem','value'=>$brakesystemid,'type'=>'vcdb','sequence'=>1,'cosmetic'=>0);
    }
   }
           
           
   
   
   
   if($goodrecord)
   {
    $apps[]=array('partnumber'=>$partnumber,'id'=>$ref,'basevehicleid'=>$basevid,'parttypeid'=>$parttypeid,'positionid'=>$positionid,'quantityperapp'=>$qty,'attributes'=>$attributes);
   }
  }
  
            
            
 }
   

 
 //-----------------------------------------------------
 $partdescriptions=array();
 $doc=$ACESgenerator->createACESdoc($header, $apps, $assets, $partdescriptions, $options);
 $doc->formatOutput=true;
 $acesxmlstring=$doc->saveXML();    

 $newdoc=new DOMDocument();
 $newdoc->loadXML($acesxmlstring); 
 // I do realize that this extra step seems redundant. Running the schema validation 
 // directly on the original object failed because of namespace problems that 
 // I could not resolve (or understand). Exporting the original object's xml 
 // to a text string and then re-importing it to a new DOM object was the
 // work-around that I found.
 
 $schemavalidated=true;   
 libxml_use_internal_errors(true);
 if(!$newdoc->schemaValidate('ACES_4_1_XSDSchema_Rev1.xsd'))
 {
  $schemavalidated=false;
  $schemaerrors = libxml_get_errors();
  foreach ($schemaerrors as $schemaerror)
  {
   $errormessage='';
   switch ($schemaerror->level) 
   {
    case LIBXML_ERR_WARNING:
     //$errormessage .= 'Warning code '. $schemaerror->code;
     break;
    case LIBXML_ERR_ERROR:
     //$errormessage .= 'Error code '.$schemaerror->code;
     break;
    case LIBXML_ERR_FATAL:
     //$errormessage .= 'Fatal Error code '.$schemaerror->code;
     break;
   }
   $errormessage.= trim($schemaerror->message);
   $schemaresults[]=$errormessage;   
  }
  libxml_clear_errors();
 }
}
else
{ // not a valid upload (for many possible reasons)
    
    
}

if(isset($_POST['showtext']) || (count($errors)>0 && !isset($_POST['ignorelookupfails']) ) || count($schemaresults)>0 || !$validUpload)
{
 $streamXML=false; ?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php if (isset($_SESSION['userid'])){include('topnav.php');} ?>

        <!-- Header -->
        <h1>Build ACES xml from spreadsheet</h1>
        <h2>Step 2: Analyze results and download XML</h2>
        <div style="font-style: italic;">Validation done against VCdb: <?php echo $pcdbVersion;?> and PCdb version: <?php echo $vcdbVersion;?></div>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <?php
                    if(!$validUpload){?>
                    <div style="padding:10px;background-color:#FF0000;font-size:1.5em;">Your input file has problems:</div>
                    <table class="table"><?php
                    foreach($inputFileLog as $result)
                    { // render each element of schema problems into a table
                        echo '<tr><td style="text-align:left;background-color:#FF0000;">'.$result.'</td></tr>';
                    }
                    ?>
                    </table>
                    <?php }?>

                    <?php if(count($schemaresults)>0){?>
                    <div style="padding:10px;background-color:#FF8800;font-size:1.5em;">Your input data causes schema (XSD) problems. Here they are:</div>
                    <table class="table"><?php
                    foreach($schemaresults as $result)
                    { // render each element of schema problems into a table
                     echo '<tr><td style="text-align:left;background-color:#FF8800;">'.$result.'</td></tr>';
                    } ?>
                    </table>
                    <?php }else{if(strlen($acesxmlstring)>0){?>
                    <div style="padding:10px;"><textarea rows="20" cols="150"><?php echo $acesxmlstring;?></textarea></div>
                    <?php }}?>

                    <?php if(count($errors)>0 && !isset($_POST['ignorelookupfails'])){?>
                    <div style="padding:10px;background-color:yellow;font-size:1.5em;"><?php if(count($schemaresults)==0){echo 'XSD-validated output was (or could be) produced. However, ';} ?>your input data contains logic problems. Here are the ones we detected:</div>
                    <table class="table"><?php
                    foreach($errors as $error)
                    {
                        echo '<tr><td style="text-align:left;background-color:yellow;">'.$error.'</td></tr>';
                    }
                    ?>
                    </table>
                    <?php }?>
                 
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->

        <!-- Footer -->
       <?php if (isset($_SESSION['userid'])){include('./includes/footer.php');} ?>
    </body>
</html>
<?php }
$logs->logSystemEvent('rhubarb', 0, 'file:'.$originalFilename.';apps:'.count($apps).';xsd:'.count($schemaresults).';logic:'.count($errors).';by:'.$_SERVER['REMOTE_ADDR']);
if($streamXML && $validUpload)
{
$filename='ACES_4_1_FULL_'.date('Y-m-d').'.xml';
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Type: application/octet-stream');
header('Content-Length: ' . strlen($acesxmlstring));
header('Connection: close');    
echo $acesxmlstring;
}?>