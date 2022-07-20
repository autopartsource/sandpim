<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/PIES6_7GeneratorClass.php');
include_once('./class/XLSXReaderClass.php');
$navCategory = 'utilities';

session_start();

$pim = new pim;
$PIESgenerator=new PIESgenerator();
$pcdb = new pcdb($_POST['pcdbversion']);
$logs=new logs();
$pcdbVersion=$pcdb->version();

$validAssetTypes=array(); $assetTypeCodes=$pcdb->getAssetTypeCodes(); foreach($assetTypeCodes as $assetTypeCode){$validAssetTypes[$assetTypeCode['code']]=$assetTypeCode['description'];}
$validDescriptionCodes=array(); $descriptionCodes=$pcdb->getItemDescriptionCodes(); foreach($descriptionCodes as $descriptionCode){$validDescriptionCodes[$descriptionCode['code']]=$descriptionCode['description'];}
$validPartTypes=array(); $partTypes=$pcdb->getPartTypes('%',999999); foreach($partTypes as $partType){$validPartTypes[$partType['id']]=$partType['name'];}

$piesxmlstring='';
$streamXML=true;

$originalFilename='';
$validUpload=false;
$inputFileLog=array();

if(isset($_POST['submit']) && $_POST['submit']=='Generate PIES xml')
{
 if($_FILES['fileToUpload']['type']=='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
 {
  if($_FILES['fileToUpload']['size']<5000000 || isset($_SESSION['userid']))   
  {     
   
   $xlsx = new XLSXReader($_FILES['fileToUpload']['tmp_name']);
   $sheetNames = $xlsx->getSheetNames();
   $originalFilename= basename($_FILES['fileToUpload']['name']);

   if(in_array('Items',$sheetNames) && in_array('Header',$sheetNames))
   {
    $headerSheet=$xlsx->getSheetData('Header');
    $itemsSheet=$xlsx->getSheetData('Items');
   
    if(true) //isset($headerSheet[0][0]) && isset($headerSheet[0][0]) && isset($headerSheet[0][1]) && $headerSheet[0][1]=='7.1')
    {
     $validUpload=true;
     $headerElementsList=array(); foreach($headerSheet as $row){$headerElementsList[$row[0]]=$row[1];}
    
     if(array_key_exists('TechnicalContact' ,$headerElementsList) && $headerElementsList['TechnicalContact']!='' && array_key_exists('ContactEmail' ,$headerElementsList) && $headerElementsList['ContactEmail']!='' && array_key_exists('PCdbVersionDate' ,$headerElementsList) && $headerElementsList['PCdbVersionDate']!='')
     {
     }
     else
     {
      $validUpload=false; 
      $inputFileLog[]='Header is missing TechnicalContact, ContactEmail or PCdbVersionDate';
      $logs->logSystemEvent('rhubarb', 0, 'Header is missing TechnicalContact, ContactEmail or PCdbVersionDate');
     }
    
     if(isset($itemsSheet[0][0]) && $itemsSheet[0][0]=='PartNumber' && isset($itemsSheet[0][1]) && $itemsSheet[0][1]=='PartTerminologyID' && isset($itemsSheet[0][2]) && $itemsSheet[0][2]=='BrandAAIAID')
     {
     }
     else
     {
      $validUpload=false;
      $inputFileLog[]='First row of Items sheet must start with these three columns: PartNumber, PartTerminologyID, BrandAAIAID';
      $logs->logSystemEvent('rhubarb', 0, 'First row of Items sheet must start with these three columns: PartNumber, PartTerminologyID, BrandAAIAID');
     }
    }
    else
    {
     $inputFileLog[]='Header did not countain proper rhubarb tag';
     $logs->logSystemEvent('rhubarb', 0, 'Header did not countain proper rhubarb tag');
    }
   }
   else
   { // Header or Items sheets are not present
    $inputFileLog[]='Uploaded workbook does not contain required worksheets: Header and Items'; 
    $logs->logSystemEvent('rhubarb', 0, 'Uploaded workbook does not contain required worksheets: Header and Items');
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

$errors=array(); $warnings=array(); $schemaresults=array(); $header=array();

if($validUpload)
{
 foreach($headerSheet as $fields)
 {
  if(count($fields)>=2)
  {
   if($fields[0]=='BlanketEffectiveDate' && trim($fields[1]!='')){$header['BlanketEffectiveDate']=trim($fields[1]);}
   if($fields[0]=='ChangesSinceDate' && trim($fields[1]!='')){$header['ChangesSinceDate']=trim($fields[1]);}
   if($fields[0]=='ParentDUNSNumber' && trim($fields[1]!='')){$header['ParentDUNSNumber']=trim($fields[1]);}
   if($fields[0]=='ParentGLN' && trim($fields[1]!='')){$header['ParentGLN']=trim($fields[1]);}
   if($fields[0]=='ParentVMRSID' && trim($fields[1]!='')){$header['ParentVMRSID']=trim($fields[1]);}
   if($fields[0]=='ParentAAIAID' && trim($fields[1]!='')){$header['ParentAAIAID']=trim($fields[1]);}
   if($fields[0]=='BrandOwnerDUNS' && trim($fields[1]!='')){$header['BrandOwnerDUNS'] = trim($fields[1]);}
   if($fields[0]=='BrandOwnerGLN' && trim($fields[1]!='')){$header['BrandOwnerGLN']=trim($fields[1]);}
   if($fields[0]=='BrandOwnerVMRSID' && trim($fields[1]!='')){$header['BrandOwnerVMRSID']=trim($fields[1]);}
   if($fields[0]=='BrandOwnerAAIAID' && trim($fields[1]!='')){$header['BrandOwnerAAIAID']=trim($fields[1]);}   
   if($fields[0]=='BuyerDuns' && trim($fields[1]!='')){$header['BuyerDuns']=trim($fields[1]);}   
   if($fields[0]=='CurrencyCode' && trim($fields[1]!='')){$header['CurrencyCode']=trim($fields[1]);}
   if($fields[0]=='LanguageCode' && trim($fields[1]!='')){$header['LanguageCode']=trim($fields[1]);}
   if($fields[0]=='TechnicalContact' && trim($fields[1]!='')){$header['TechnicalContact']=trim($fields[1]);}
   if($fields[0]=='ContactEmail' && trim($fields[1]!='')){$header['ContactEmail']=trim($fields[1]);}
   if($fields[0]=='PAdbVersionDate' && trim($fields[1]!='')){$header['PAdbVersionDate']=trim($fields[1]);}
   if($fields[0]=='PCdbVersionDate' && trim($fields[1]!='')){$header['PCdbVersionDate']=trim($fields[1]);}
  }
 }
 
//----------------------- marketing copy -----------------------
 $marketingcopys=array();
 if(in_array('MarketingCopy',$sheetNames))
 {
  $marketingCopySheet=$xlsx->getSheetData('MarketingCopy');
  $MarketCopyTypeFieldIndex=-1; $LanguageCodeFieldIndex=-1; $MarketCopyCodeFieldIndex=-1; $MarketCopyReferenceFieldIndex=-1; $MarketCopySubCodeFieldIndex=-1; $MarketCopySubCodeReferenceFieldIndex=-1; $RecordSequenceFieldIndex=-1; $MarketCopyFieldIndex=-1;

  for($i=0; $i<=count($marketingCopySheet[0])-1; $i++)
  {
   if($marketingCopySheet[0][$i]=='MarketCopyContent'){$MarketCopyFieldIndex=$i;}
   if($marketingCopySheet[0][$i]=='MarketCopyCode'){$MarketCopyCodeFieldIndex=$i;}
   if($marketingCopySheet[0][$i]=='MarketCopyReference'){$MarketCopyReferenceFieldIndex=$i;}
   if($marketingCopySheet[0][$i]=='MarketCopySubCode'){$MarketCopySubCodeFieldIndex=$i;}
   if($marketingCopySheet[0][$i]=='MarketCopySubCodeReference'){$MarketCopySubCodeReferenceFieldIndex=$i;}
   if($marketingCopySheet[0][$i]=='MarketCopyType'){$MarketCopyTypeFieldIndex=$i;}
   if($marketingCopySheet[0][$i]=='RecordSequence'){$RecordSequenceFieldIndex=$i;}
   if($marketingCopySheet[0][$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
  } 
  
  $recordnumber=0;
  foreach($marketingCopySheet as $fields)
  {
   if($recordnumber==0){$recordnumber++;continue;}
   $marketcopy=array();
   if($MarketCopyFieldIndex>=0 && trim($fields[$MarketCopyFieldIndex])!=''){$marketcopy['MarketCopyContent']=htmlspecialchars(trim($fields[$MarketCopyFieldIndex]));}
   if($MarketCopyCodeFieldIndex>=0 && trim($fields[$MarketCopyCodeFieldIndex])!=''){ $marketcopy['MarketCopyCode']=trim($fields[$MarketCopyCodeFieldIndex]);}
   if($MarketCopyReferenceFieldIndex>=0 && trim($fields[$MarketCopyReferenceFieldIndex])!=''){$marketcopy['MarketCopyReference']=trim($fields[$MarketCopyReferenceFieldIndex]);}
   if($MarketCopySubCodeFieldIndex>=0 && trim($fields[$MarketCopySubCodeFieldIndex])!=''){$marketcopy['MarketCopySubCode']=trim($fields[$MarketCopySubCodeFieldIndex]);}
   if($MarketCopySubCodeReferenceFieldIndex>=0 && trim($fields[$MarketCopySubCodeReferenceFieldIndex])!=''){$marketcopy['MarketCopySubCodeReference']=trim($fields[$MarketCopySubCodeReferenceFieldIndex]);}
   if($MarketCopyTypeFieldIndex>=0 && trim($fields[$MarketCopyTypeFieldIndex])!=''){$marketcopy['MarketCopyType']=trim($fields[$MarketCopyTypeFieldIndex]);}
   if($RecordSequenceFieldIndex>=0 && trim($fields[$RecordSequenceFieldIndex])!=''){$marketcopy['RecordSequence']=trim($fields[$RecordSequenceFieldIndex]);}
   if($LanguageCodeFieldIndex>=0 && trim($fields[$LanguageCodeFieldIndex])!=''){$marketcopy['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);}
   $marketingcopys[]=$marketcopy;
   $recordnumber++;
  }
 }
 
 // --------------------- items ---------------------------------   
 $items=array(); 
 $PartTerminologyIDfieldIndex=-1; $BrandAAIAIDfieldIndex=-1; $ItemLevelGTINfieldIndex=-1; $GTINQualifierFieldIndex=-1; $MinimumOrderQuantityFieldIndex=-1; $MinimumOrderQuantityUOMfieldIndex=-1;  $HazardousMaterialCodeFieldIndex=-1; $BaseItemIDfieldIndex=-1; $ACESApplicationsFieldIndex=-1; $ItemQuantitySizeFieldIndex=-1;  $ItemQuantitySizeUOMfieldIndex=-1; $ContainerTypeFieldIndex=-1; $ItemEffectiveDateFieldIndex=-1; $AvailableDateFieldIndex=-1;  $UNSPSCfieldIndex=-1; $BrandLabelFieldIndex=-1; $VMRSBrandIDfieldIndex=-1; $VMRSCodeFieldIndex=-1; $QuantityPerApplicationFieldIndex=-1;  $QuantityPerApplicationQualifierFieldIndex=-1; $QuantityPerApplicationUOMfieldIndex=-1; $ManufacturerProductCodeGroupFieldIndex=-1; $ManufacturerProductCodeSubGroupFieldIndex=-1; $AAIAProductCategoryCodeFieldIndex=-1;
 for($i=0; $i<=count($itemsSheet[0])-1; $i++)
 {
  if($itemsSheet[0][$i]=='PartTerminologyID'){$PartTerminologyIDfieldIndex=$i;}
  if($itemsSheet[0][$i]=='BrandAAIAID'){$BrandAAIAIDfieldIndex=$i;}
  if($itemsSheet[0][$i]=='ItemLevelGTIN'){$ItemLevelGTINfieldIndex=$i;}
  if($itemsSheet[0][$i]=='GTINQualifier'){$GTINQualifierFieldIndex=$i;}
  if($itemsSheet[0][$i]=='MinimumOrderQuantity'){$MinimumOrderQuantityFieldIndex=$i;}
  if($itemsSheet[0][$i]=='MinimumOrderQuantityUOM'){$MinimumOrderQuantityUOMfieldIndex=$i;}
  if($itemsSheet[0][$i]=='HazardousMaterialCode'){$HazardousMaterialCodeFieldIndex=$i;}
  if($itemsSheet[0][$i]=='BaseItemID'){$BaseItemIDfieldIndex=$i;}
  if($itemsSheet[0][$i]=='ACESApplications'){$ACESApplicationsFieldIndex=$i;}
  if($itemsSheet[0][$i]=='ItemQuantitySize'){$ItemQuantitySizeFieldIndex=$i;}
  if($itemsSheet[0][$i]=='ItemQuantitySizeUOM'){$ItemQuantitySizeUOMfieldIndex=$i;}
  if($itemsSheet[0][$i]=='ContainerType'){$ContainerTypeFieldIndex=$i;}
  if($itemsSheet[0][$i]=='ItemEffectiveDate'){$ItemEffectiveDateFieldIndex=$i;}
  if($itemsSheet[0][$i]=='AvailableDate'){$AvailableDateFieldIndex=$i;}
  if($itemsSheet[0][$i]=='UNSPSC'){$UNSPSCfieldIndex=$i;}
  if($itemsSheet[0][$i]=='BrandLabel'){$BrandLabelFieldIndex=$i;}
  if($itemsSheet[0][$i]=='VMRSBrandID'){$VMRSBrandIDfieldIndex=$i;}
  if($itemsSheet[0][$i]=='VMRSCode'){$VMRSCodeFieldIndex=$i;}
  if($itemsSheet[0][$i]=='QuantityPerApplication'){$QuantityPerApplicationFieldIndex=$i;}
  if($itemsSheet[0][$i]=='QuantityPerApplicationQualifier'){$QuantityPerApplicationQualifierFieldIndex=$i;}
  if($itemsSheet[0][$i]=='QuantityPerApplicationUOM'){$QuantityPerApplicationUOMfieldIndex=$i;}
  if($itemsSheet[0][$i]=='ManufacturerProductCodeGroup'){$ManufacturerProductCodeGroupFieldIndex=$i;}
  if($itemsSheet[0][$i]=='ManufacturerProductCodeSubGroup'){$ManufacturerProductCodeSubGroupFieldIndex=$i;}
  if($itemsSheet[0][$i]=='AAIAProductCategoryCode'){$AAIAProductCategoryCodeFieldIndex=$i;}
 }

  // main items list. parse the text-area input lines
 $recordnumber=0;
 foreach($itemsSheet as $fields)
 {
  if($recordnumber==0){$recordnumber++;continue;}
  $item=array('descriptions'=>array(), 'attributes'=>array(),'interchanges'=>array(),'assets'=>array(),'expis'=>array(),'kits'=>array(), 'packages'=>array());
   
  $PartNumber=trim($fields[0]);
  if($PartTerminologyIDfieldIndex >=0 && trim($fields[$PartTerminologyIDfieldIndex])!=''){$item['PartTerminologyID']=trim($fields[$PartTerminologyIDfieldIndex]);}
  if($BrandAAIAIDfieldIndex >=0 && trim($fields[$BrandAAIAIDfieldIndex])!=''){$item['BrandAAIAID']=trim($fields[$BrandAAIAIDfieldIndex]);}
  
  if($ItemLevelGTINfieldIndex >=0 && trim($fields[$ItemLevelGTINfieldIndex])!='')
  {
      $item['ItemLevelGTIN']=trim($fields[$ItemLevelGTINfieldIndex]);
      if(strlen($item['ItemLevelGTIN'])==12){$item['ItemLevelGTIN']='00'.$item['ItemLevelGTIN'];}
  }
  
  if($GTINQualifierFieldIndex >=0 && trim($fields[$GTINQualifierFieldIndex])!=''){$item['GTINQualifier']=trim($fields[$GTINQualifierFieldIndex]);}
  if($MinimumOrderQuantityFieldIndex >=0 && trim($fields[$MinimumOrderQuantityFieldIndex])!=''){$item['MinimumOrderQuantity']=trim($fields[$MinimumOrderQuantityFieldIndex]);}
  if($MinimumOrderQuantityUOMfieldIndex >=0 && trim($fields[$MinimumOrderQuantityUOMfieldIndex])!=''){$item['MinimumOrderQuantityUOM']=trim($fields[$MinimumOrderQuantityUOMfieldIndex]);}
  if($HazardousMaterialCodeFieldIndex >=0 && trim($fields[$HazardousMaterialCodeFieldIndex])!=''){$item['HazardousMaterialCode']=trim($fields[$HazardousMaterialCodeFieldIndex]);}
  if($BaseItemIDfieldIndex >=0 && trim($fields[$BaseItemIDfieldIndex])!=''){$item['BaseItemID']=trim($fields[$BaseItemIDfieldIndex]);}
  if($ACESApplicationsFieldIndex >=0 && trim($fields[$ACESApplicationsFieldIndex])!=''){$item['ACESApplications']=trim($fields[$ACESApplicationsFieldIndex]);}
  if($ItemQuantitySizeFieldIndex >=0 && trim($fields[$ItemQuantitySizeFieldIndex])!=''){$item['ItemQuantitySize']=trim($fields[$ItemQuantitySizeFieldIndex]);}
  if($ItemQuantitySizeUOMfieldIndex >=0 && trim($fields[$ItemQuantitySizeUOMfieldIndex])!=''){$item['ItemQuantitySizeUOM']=trim($fields[$ItemQuantitySizeUOMfieldIndex]);}
  if($ContainerTypeFieldIndex >=0 && trim($fields[$ContainerTypeFieldIndex])!=''){$item['ContainerType']=trim($fields[$ContainerTypeFieldIndex]);}
  if($ItemEffectiveDateFieldIndex >=0 && trim($fields[$ItemEffectiveDateFieldIndex])!=''){$item['ItemEffectiveDate']=trim($fields[$ItemEffectiveDateFieldIndex]);}
  if($AvailableDateFieldIndex >=0 && trim($fields[$AvailableDateFieldIndex])!=''){$item['AvailableDate']=trim($fields[$AvailableDateFieldIndex]);}
  if($UNSPSCfieldIndex >=0 && trim($fields[$UNSPSCfieldIndex])!=''){$item['UNSPSC']=trim($fields[$UNSPSCfieldIndex]);}  
  if($BrandLabelFieldIndex >=0 && trim($fields[$BrandLabelFieldIndex])!=''){$item['BrandLabel']=trim($fields[$BrandLabelFieldIndex]);}  
  if($VMRSBrandIDfieldIndex >=0 && trim($fields[$VMRSBrandIDfieldIndex])!=''){$item['VMRSBrandID']=trim($fields[$VMRSBrandIDfieldIndex]);}  
  if($VMRSCodeFieldIndex >=0 && trim($fields[$VMRSCodeFieldIndex])!=''){$item['VMRSCode']=trim($fields[$VMRSCodeFieldIndex]);}  
  if($QuantityPerApplicationFieldIndex >=0 && trim($fields[$QuantityPerApplicationFieldIndex])!=''){$item['QuantityPerApplication']=trim($fields[$QuantityPerApplicationFieldIndex]);}  
  if($QuantityPerApplicationQualifierFieldIndex >=0 && trim($fields[$QuantityPerApplicationQualifierFieldIndex])!=''){$item['QuantityPerApplicationQualifier']=trim($fields[$QuantityPerApplicationQualifierFieldIndex]);}  
  if($QuantityPerApplicationUOMfieldIndex >=0 && trim($fields[$QuantityPerApplicationUOMfieldIndex])!=''){$item['QuantityPerApplicationUOM']=trim($fields[$QuantityPerApplicationUOMfieldIndex]);}  
  if($ManufacturerProductCodeGroupFieldIndex >=0 && trim($fields[$ManufacturerProductCodeGroupFieldIndex])!=''){$item['ManufacturerProductCodeGroup']=trim($fields[$ManufacturerProductCodeGroupFieldIndex]);}  
  if($ManufacturerProductCodeSubGroupFieldIndex >=0 && trim($fields[$ManufacturerProductCodeSubGroupFieldIndex])!=''){$item['ManufacturerProductCodeSubGroup']=trim($fields[$ManufacturerProductCodeSubGroupFieldIndex]);}  
  if($AAIAProductCategoryCodeFieldIndex >=0 && trim($fields[$AAIAProductCategoryCodeFieldIndex])!=''){$item['AAIAProductCategoryCode']=trim($fields[$AAIAProductCategoryCodeFieldIndex]);}  
  if(!array_key_exists($item['PartTerminologyID'],$validPartTypes))
  {
   $errors[]='Partnumber ('.$PartNumber.') has an invalid PartTerminologyID ('.$item['PartTerminologyID'].')';  
  }
  
  $items[$PartNumber]=$item;
  $recordnumber++;
 }

 // --------------------- Descriptions -----------------------
 
 if(in_array('Descriptions',$sheetNames))
 {
  $descriptionsSheet=$xlsx->getSheetData('Descriptions');
  $descriptions=array();

  $PartNumberFieldIndex=-1; $DescriptionFieldIndex=-1; $DescriptionCodeFieldIndex=-1; $LanguageCodeFieldIndex=-1; $SequenceFieldIndex=-1; 
  for($i=0; $i<=count($descriptionsSheet[0])-1; $i++)
  {
   if($descriptionsSheet[0][$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
   if($descriptionsSheet[0][$i]=='Description'){$DescriptionFieldIndex=$i;}
   if($descriptionsSheet[0][$i]=='DescriptionCode'){$DescriptionCodeFieldIndex=$i;}
   if($descriptionsSheet[0][$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
   if($descriptionsSheet[0][$i]=='Sequence'){$SequenceFieldIndex=$i;}
  } 
  
  $recordnumber=0;
  if($PartNumberFieldIndex==0)
  {
   foreach($descriptionsSheet as $fields)
   {
    if($recordnumber==0){$recordnumber++;continue;}
    $PartNumber=trim($fields[0]);
    
    if($DescriptionFieldIndex>=0 && trim($fields[$DescriptionFieldIndex])!='')
    {
     $description=array();
     $description['Description']= htmlspecialchars(trim($fields[$DescriptionFieldIndex]));
     if($DescriptionCodeFieldIndex>=0 && trim($fields[$DescriptionCodeFieldIndex])!=''){$description['DescriptionCode']=trim($fields[$DescriptionCodeFieldIndex]);}
     if($LanguageCodeFieldIndex>=0 && trim($fields[$LanguageCodeFieldIndex])!=''){$description['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);}
     if($SequenceFieldIndex>=0 && trim($fields[$SequenceFieldIndex])!=''){$description['Sequence']=trim($fields[$SequenceFieldIndex]);}
    
     if(array_key_exists($PartNumber,$items))
     {
      $items[$PartNumber]['descriptions'][]=$description;
     }
     else
     {
      $errors[]='Descriptions contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
     }
    }
    
    
    if(!array_key_exists($description['DescriptionCode'], $validDescriptionCodes))
    {
     $errors[]='Descriptions contains an invalid code ('.$description['DescriptionCode'].') for partnumber ('.$PartNumber.')';  
    }
    $recordnumber++;
   }
  }
 }
 
 // --------------------- Prices -----------------------

 if(in_array('Prices',$sheetNames))
 {
  $pricesSheet=$xlsx->getSheetData('Prices');
  $PartNumberFieldIndex=-1; $PriceSheetNumberFieldIndex=-1; $CurrencyCodeFieldIndex=-1; $EffectiveDateFieldIndex=-1; $ExpirationDateFieldIndex=-1; $PriceFieldIndex=-1; $PriceUOMFieldIndex=-1; $PriceTypeDescriptionFieldIndex=-1; $PriceBreakFieldIndex=-1; $PriceBreakUOMFieldIndex=-1; $PriceMultiplierFieldIndex=-1; $PriceTypeFieldIndex=-1;

  for($i=0; $i<=count($pricesSheet[0])-1; $i++)
  { // identify the named columns' IDs
   if($pricesSheet[0][$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
   if($pricesSheet[0][$i]=='PriceSheetNumber'){$PriceSheetNumberFieldIndex=$i;}
   if($pricesSheet[0][$i]=='CurrencyCode'){$CurrencyCodeFieldIndex=$i;}
   if($pricesSheet[0][$i]=='EffectiveDate'){$EffectiveDateFieldIndex=$i;}
   if($pricesSheet[0][$i]=='ExpirationDate'){$ExpirationDateFieldIndex=$i;}
   if($pricesSheet[0][$i]=='Price'){$PriceFieldIndex=$i;}
   if($pricesSheet[0][$i]=='PriceUOM'){$PriceUOMFieldIndex=$i;}
   if($pricesSheet[0][$i]=='PriceTypeDescription'){$PriceTypeDescriptionFieldIndex=$i;}
   if($pricesSheet[0][$i]=='PriceBreak'){$PriceBreakFieldIndex=$i;}
   if($pricesSheet[0][$i]=='PriceBreakUOM'){$PriceBreakUOMFieldIndex=$i;}
   if($pricesSheet[0][$i]=='PriceMultiplier'){$PriceMultiplierFieldIndex=$i;}
   if($pricesSheet[0][$i]=='PriceType'){$PriceTypeFieldIndex=$i;}
  } 

  $recordnumber=0;
  if($PartNumberFieldIndex==0)
  {
   foreach($pricesSheet as $fields)
   {
    if($recordnumber==0){$recordnumber++;continue;}
    $price=array();
    $PartNumber=trim($fields[0]);

    if($PriceSheetNumberFieldIndex>=0 && trim($fields[$PriceSheetNumberFieldIndex])!=''){$price['PriceSheetNumber']= htmlspecialchars(trim($fields[$PriceSheetNumberFieldIndex]));}
    if($CurrencyCodeFieldIndex>=0 && trim($fields[$CurrencyCodeFieldIndex])!=''){$price['CurrencyCode']=trim($fields[$CurrencyCodeFieldIndex]);}
    if($EffectiveDateFieldIndex>=0 && trim($fields[$EffectiveDateFieldIndex])!=''){$price['EffectiveDate']=trim($fields[$EffectiveDateFieldIndex]);}
    if($ExpirationDateFieldIndex>=0 && trim($fields[$ExpirationDateFieldIndex])!=''){$price['ExpirationDate']=trim($fields[$ExpirationDateFieldIndex]);}
    if($PriceFieldIndex>=0 && trim($fields[$PriceFieldIndex])!=''){$price['Price']=trim($fields[$PriceFieldIndex]);}
    if($PriceUOMFieldIndex>=0 && trim($fields[$PriceUOMFieldIndex])!=''){$price['PriceUOM']=trim($fields[$PriceUOMFieldIndex]);}
    if($PriceTypeDescriptionFieldIndex>=0 && trim($fields[$PriceTypeDescriptionFieldIndex])!=''){$price['PriceTypeDescription']=trim($fields[$PriceTypeDescriptionFieldIndex]);}
    if($PriceBreakFieldIndex>=0 && trim($fields[$PriceBreakFieldIndex])!=''){$price['PriceBreak']=trim($fields[$PriceBreakFieldIndex]);}
    if($PriceBreakUOMFieldIndex>=0 && trim($fields[$PriceBreakUOMFieldIndex])!=''){$price['PriceBreakUOM']=trim($fields[$PriceBreakUOMFieldIndex]);}
    if($PriceMultiplierFieldIndex>=0 && trim($fields[$PriceMultiplierFieldIndex])!=''){$price['PriceMultiplier']=trim($fields[$PriceMultiplierFieldIndex]);}
    if($PriceTypeFieldIndex>=0 && trim($fields[$PriceTypeFieldIndex])!=''){$price['PriceType']=trim($fields[$PriceTypeFieldIndex]);}
    
    // see if this partnumber was established in the Items list
    if(array_key_exists($PartNumber,$items))
    {
     $items[$PartNumber]['prices'][]=$price;
    }
    else
    {
     $errors[]='Prices contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
    }
    $recordnumber++;
   }
  }
 }
 
 // --------------------- EXPI -----------------------
 if(in_array('EXPI',$sheetNames))
 {
  $EXPIsheet=$xlsx->getSheetData('EXPI');

  $PartNumberFieldIndex=-1; $EXPICodeFieldIndex=-1; $EXPIValueFieldIndex=-1;  $LanguageCodeFieldIndex=-1; 
  for($i=0; $i<=count($EXPIsheet[0])-1; $i++)
  { // identify the named columns' IDs
   if($EXPIsheet[0][$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
   if($EXPIsheet[0][$i]=='EXPICode'){$EXPICodeFieldIndex=$i;}
   if($EXPIsheet[0][$i]=='EXPIValue'){$EXPIValueFieldIndex=$i;}
   if($EXPIsheet[0][$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
  } 
  
  $recordnumber=0;
  if($PartNumberFieldIndex==0)
  {
   foreach($EXPIsheet as $fields)
   {
    if($recordnumber==0){$recordnumber++;continue;}
    $expi=array();
    $PartNumber=trim($fields[0]);

    if($EXPICodeFieldIndex>=0){$expi['EXPICode']=trim($fields[$EXPICodeFieldIndex]);}
    if($EXPIValueFieldIndex>=0){$expi['EXPIValue']= htmlspecialchars(trim($fields[$EXPIValueFieldIndex]));}
    if($LanguageCodeFieldIndex>=0 && trim($fields[$LanguageCodeFieldIndex])!=''){$expi['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);}

    // see if this partnumber was established in the Items list
    if(array_key_exists($PartNumber,$items))
    {
     $items[$PartNumber]['expis'][]=$expi;
    }
    else
    {
     $errors[]='EXPI contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
    }
    
    // validate code/value combinations
    
    $validCodes=$pcdb->getValidEXPIvalues($expi['EXPICode']);
    $EXPIvalidaded=false;
    foreach($validCodes as $validCode)
    {
     if($validCode['code']==$expi['EXPIValue'] || $validCode['code']=='*')
     {
      $EXPIvalidaded=true; break;
     }
    }
    
    // not a valid code/value combo. warranty codes have pcdb records that imply wildcard-status
    if($expi['EXPICode']=='WS1' || $expi['EXPICode']=='WS2')
    {
     $EXPIvalidaded=true;
    }
    
       
    if(!$EXPIvalidaded)
    {
     $errors[]='EXPI contains invalid code/value combination ('.$expi['EXPICode'].'/'.$expi['EXPIValue'].') for a partnumber ('.$PartNumber.')';
    }
    
//    if(!isset($validEXPIcodes[$expi['EXPICode']][$expi['EXPIValue']]))
   // {
  //   $errors[]='EXPI contains invalid code/value combination ('.$expi['EXPICode'].'/'.$expi['EXPIValue'].') for a partnumber ('.$PartNumber.')';  
   // }
        //courtney
        
   
    
    $recordnumber++;
   }
  }
 }
 
 // --------------------- Attributes -----------------------
 if(in_array('Attributes',$sheetNames))
 {
  $attributesSheet=$xlsx->getSheetData('Attributes');

  $PartNumberFieldIndex=-1; $AttributeIDFieldIndex=-1; $PADBAttributeFieldIndex=-1; $AttributeValueFieldIndex=-1; $StyleIDFieldIndex=-1; $AttributeUOMFieldIndex=-1; $MultiValueQuantityFieldIndex=-1;	$MultiValueSequenceFieldIndex=-1; $LanguageCodeFieldIndex=-1; $RecordNumberFieldIndex=-1; 
  for($i=0; $i<=count($attributesSheet[0])-1; $i++)
  { // identify the named columns' IDs
   if($attributesSheet[0][$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
   if($attributesSheet[0][$i]=='AttributeID'){$AttributeIDFieldIndex=$i;}
   if($attributesSheet[0][$i]=='PADBAttribute'){$PADBAttributeFieldIndex=$i;}
   if($attributesSheet[0][$i]=='AttributeValue'){$AttributeValueFieldIndex=$i;}
   if($attributesSheet[0][$i]=='StyleID'){$StyleIDFieldIndex=$i;}
   if($attributesSheet[0][$i]=='AttributeUOM'){$AttributeUOMFieldIndex=$i;}
   if($attributesSheet[0][$i]=='MultiValueQuantity'){$MultiValueQuantityFieldIndex=$i;}
   if($attributesSheet[0][$i]=='MultiValueSequence'){$MultiValueSequenceFieldIndex=$i;}
   if($attributesSheet[0][$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
   if($attributesSheet[0][$i]=='RecordNumber'){$RecordNumberFieldIndex=$i;} 
  } 
  
  $recordnumber=0;
  if($PartNumberFieldIndex==0)
  {
   foreach($attributesSheet as $fields)
   {
    if($recordnumber==0){$recordnumber++;continue;}
    $attribute=array();
    $PartNumber=trim($fields[0]);

    if($AttributeIDFieldIndex>=0){$attribute['AttributeID']= trim($fields[$AttributeIDFieldIndex]);}
    if($PADBAttributeFieldIndex>=0){$attribute['PADBAttribute']=trim($fields[$PADBAttributeFieldIndex]);}
    if($AttributeValueFieldIndex>=0){$attribute['AttributeValue']=trim($fields[$AttributeValueFieldIndex]);}
    if($StyleIDFieldIndex>=0){$attribute['StyleID']=trim($fields[$StyleIDFieldIndex]);}
    if($AttributeUOMFieldIndex>=0){$attribute['AttributeUOM']=trim($fields[$AttributeUOMFieldIndex]);}
    if($MultiValueQuantityFieldIndex>=0){$attribute['MultiValueQuantity']=trim($fields[$MultiValueQuantityFieldIndex]);}
    if($MultiValueSequenceFieldIndex>=0){$attribute['MultiValueSequence']=trim($fields[$MultiValueSequenceFieldIndex]);}
    if($LanguageCodeFieldIndex>=0){$attribute['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);}
    if($RecordNumberFieldIndex>=0){$attribute['RecordNumber']=trim($fields[$RecordNumberFieldIndex]);}

    // see if this partnumber was established in the Items list
    if(array_key_exists($PartNumber,$items))
    {
     $items[$PartNumber]['attributes'][]=$attribute;
    }
    else
    {
     $errors[]='Attributes contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
    }
    $recordnumber++;
   }
  }
 }
 
 // --------------------- Packages -----------------------
 if(in_array('Packages',$sheetNames))
 {
  $packagesSheet=$xlsx->getSheetData('Packages');

  $PartNumberFieldIndex=-1; $PackageLevelGTINFieldIndex=-1; $ElectronicProductCodeFieldIndex=-1; $PackageBarCodeCharactersFieldIndex=-1; $PackageUOMFieldIndex=-1; $QuantityofEachesFieldIndex=-1; $InnerQuantityFieldIndex=-1; $InnerQuantityUOMFieldIndex=-1; $MerchandisingHeightFieldIndex=-1; $MerchandisingWidthFieldIndex=-1; $MerchandisingLengthFieldIndex=-1; $ShippingHeightFieldIndex=-1; $ShippingWidthFieldIndex=-1; $ShippingLengthFieldIndex=-1; $DimensionsUOMFieldIndex=-1; $WeightFieldIndex=-1; $DimensionalWeightFieldIndex=-1; $WeightsUOMFieldIndex=-1; $WeightVarianceFieldIndex=-1; $StackingFactorFieldIndex=-1; $HazardousMaterialFieldIndex=-1; $ShippingScopeFieldIndex=-1; $BulkFieldIndex=-1; $RegulatingCountryFieldIndex=-1; $TransportMethodFieldIndex=-1; $RegulatedFieldIndex=-1; $DescriptionFieldIndex=-1; $HazardousMaterialCodeQualifierFieldIndex=-1; $HazardousMaterialDescriptionFieldIndex=-1; $HazardousMaterialLabelCodeFieldIndex=-1; $ShippingNameFieldIndex=-1; $UNNAIDCodeFieldIndex=-1; $HazardousPlacardNotationFieldIndex=-1; $WHMISCodeFieldIndex=-1; $WHMISFreeTextFieldIndex=-1; $PackingGroupCodeFieldIndex=-1; $RegulationsExemptionCodeFieldIndex=-1; $TextMessageFieldIndex=-1; $OuterPackageLabelFieldIndex=-1; $LanguageCodeFieldIndex=-1;

  $packages=array();

  for($i=0; $i<=count($packagesSheet[0])-1; $i++)
  { // identify the named columns' IDs
   if($packagesSheet[0][$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
   if($packagesSheet[0][$i]=='PackageLevelGTIN'){$PackageLevelGTINFieldIndex=$i;}
   if($packagesSheet[0][$i]=='ElectronicProductCode'){$ElectronicProductCodeFieldIndex=$i;}
   if($packagesSheet[0][$i]=='PackageBarCodeCharacters'){$PackageBarCodeCharactersFieldIndex=$i;}
   if($packagesSheet[0][$i]=='PackageUOM'){$PackageUOMFieldIndex=$i;}
   if($packagesSheet[0][$i]=='QuantityofEaches'){$QuantityofEachesFieldIndex=$i;}
   if($packagesSheet[0][$i]=='InnerQuantity'){$InnerQuantityFieldIndex=$i;}
   if($packagesSheet[0][$i]=='InnerQuantityUOM'){$InnerQuantityUOMFieldIndex=$i;}
   if($packagesSheet[0][$i]=='MerchandisingHeight'){$MerchandisingHeightFieldIndex=$i;}
   if($packagesSheet[0][$i]=='MerchandisingWidth'){$MerchandisingWidthFieldIndex=$i;}
   if($packagesSheet[0][$i]=='MerchandisingLength'){$MerchandisingLengthFieldIndex=$i;}
   if($packagesSheet[0][$i]=='ShippingHeight'){$ShippingHeightFieldIndex=$i;}
   if($packagesSheet[0][$i]=='ShippingWidth'){$ShippingWidthFieldIndex=$i;}
   if($packagesSheet[0][$i]=='ShippingLength'){$ShippingLengthFieldIndex=$i;}
   if($packagesSheet[0][$i]=='DimensionsUOM'){$DimensionsUOMFieldIndex=$i;}
   if($packagesSheet[0][$i]=='Weight'){$WeightFieldIndex=$i;}
   if($packagesSheet[0][$i]=='DimensionalWeight'){$DimensionalWeightFieldIndex=$i;}
   if($packagesSheet[0][$i]=='WeightsUOM'){$WeightsUOMFieldIndex=$i;}
   if($packagesSheet[0][$i]=='WeightVariance'){$WeightVarianceFieldIndex=$i;}
   if($packagesSheet[0][$i]=='StackingFactor'){$StackingFactorFieldIndex=$i;}
   //if($packagesSheet[0][$i]=='HazardousMaterial'){$HazardousMaterialFieldIndex=$i;}
   if($packagesSheet[0][$i]=='ShippingScope'){$ShippingScopeFieldIndex=$i;}
   if($packagesSheet[0][$i]=='Bulk'){$BulkFieldIndex=$i;}
   if($packagesSheet[0][$i]=='RegulatingCountry'){$RegulatingCountryFieldIndex=$i;}
   if($packagesSheet[0][$i]=='TransportMethod'){$TransportMethodFieldIndex=$i;}
   if($packagesSheet[0][$i]=='Regulated'){$RegulatedFieldIndex=$i;}
   if($packagesSheet[0][$i]=='Description'){$DescriptionFieldIndex=$i;}
   if($packagesSheet[0][$i]=='HazardousMaterialCodeQualifier'){$HazardousMaterialCodeQualifierFieldIndex=$i;}
   if($packagesSheet[0][$i]=='HazardousMaterialDescription'){$HazardousMaterialDescriptionFieldIndex=$i;}
   if($packagesSheet[0][$i]=='HazardousMaterialLabelCode'){$HazardousMaterialLabelCodeFieldIndex=$i;}
   if($packagesSheet[0][$i]=='ShippingName'){$ShippingNameFieldIndex=$i;}
   if($packagesSheet[0][$i]=='UNNAIDCode'){$UNNAIDCodeFieldIndex=$i;}
   if($packagesSheet[0][$i]=='HazardousPlacardNotation'){$HazardousPlacardNotationFieldIndex=$i;}
   if($packagesSheet[0][$i]=='WHMISCode'){$WHMISCodeFieldIndex=$i;}
   if($packagesSheet[0][$i]=='WHMISFreeText'){$WHMISFreeTextFieldIndex=$i;}
   if($packagesSheet[0][$i]=='PackingGroupCode'){$PackingGroupCodeFieldIndex=$i;}
   if($packagesSheet[0][$i]=='RegulationsExemptionCode'){$RegulationsExemptionCodeFieldIndex=$i;}
   if($packagesSheet[0][$i]=='TextMessage'){$TextMessageFieldIndex=$i;}
   if($packagesSheet[0][$i]=='OuterPackageLabel'){$OuterPackageLabelFieldIndex=$i;}
   if($packagesSheet[0][$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;} 
  } 
  
  $recordnumber=0;
  if($PartNumberFieldIndex==0)
  {
   foreach($packagesSheet as $fields)
   {
    if($recordnumber==0){$recordnumber++;continue;}
    $package=array();
    $PartNumber=trim($fields[0]);

    if($PartNumberFieldIndex>=0){$package['PartNumber']=trim($fields[$PartNumberFieldIndex]);}
    if($PackageLevelGTINFieldIndex>=0&&trim($fields[$PackageLevelGTINFieldIndex])!='')
    {
        $package['PackageLevelGTIN']=trim($fields[$PackageLevelGTINFieldIndex]);
        if(strlen($package['PackageLevelGTIN'])==12){$package['PackageLevelGTIN']='00'.$package['PackageLevelGTIN'];}
    }
    
    if($ElectronicProductCodeFieldIndex>=0&&trim($fields[$ElectronicProductCodeFieldIndex])!=''){$package['ElectronicProductCode']=trim($fields[$ElectronicProductCodeFieldIndex]);}
    if($PackageBarCodeCharactersFieldIndex>=0&&trim($fields[$PackageBarCodeCharactersFieldIndex])!=''){$package['PackageBarCodeCharacters']=trim($fields[$PackageBarCodeCharactersFieldIndex]);}
    if($PackageUOMFieldIndex>=0){$package['PackageUOM']=trim($fields[$PackageUOMFieldIndex]);}
    if($QuantityofEachesFieldIndex>=0&&trim($fields[$QuantityofEachesFieldIndex])!=''){$package['QuantityofEaches']=trim($fields[$QuantityofEachesFieldIndex]);}
    if($InnerQuantityFieldIndex>=0 && trim($fields[$InnerQuantityFieldIndex])!=''){$package['InnerQuantity']=trim($fields[$InnerQuantityFieldIndex]);}
    if($InnerQuantityUOMFieldIndex>=0 && trim($fields[$InnerQuantityUOMFieldIndex])!=''){$package['InnerQuantityUOM']=trim($fields[$InnerQuantityUOMFieldIndex]);}
    if($MerchandisingHeightFieldIndex>=0 && trim($fields[$MerchandisingHeightFieldIndex])!=''){$package['MerchandisingHeight']=trim($fields[$MerchandisingHeightFieldIndex]);}
    if($MerchandisingWidthFieldIndex>=0 && trim($fields[$MerchandisingWidthFieldIndex])!=''){$package['MerchandisingWidth']=trim($fields[$MerchandisingWidthFieldIndex]);}
    if($MerchandisingLengthFieldIndex>=0 && trim($fields[$MerchandisingLengthFieldIndex])!=''){$package['MerchandisingLength']=trim($fields[$MerchandisingLengthFieldIndex]);}
    if($ShippingHeightFieldIndex>=0 && trim($fields[$ShippingHeightFieldIndex])!=''){$package['ShippingHeight']=trim($fields[$ShippingHeightFieldIndex]);}
    if($ShippingWidthFieldIndex>=0 && trim($fields[$ShippingWidthFieldIndex])!=''){$package['ShippingWidth']=trim($fields[$ShippingWidthFieldIndex]);}
    if($ShippingLengthFieldIndex>=0 && trim($fields[$ShippingLengthFieldIndex])!=''){$package['ShippingLength']=trim($fields[$ShippingLengthFieldIndex]);}
    if($DimensionsUOMFieldIndex>=0 && trim($fields[$DimensionsUOMFieldIndex])!=''){$package['DimensionsUOM']=trim($fields[$DimensionsUOMFieldIndex]);}
    if($WeightFieldIndex>=0 && trim($fields[$WeightFieldIndex])!=''){$package['Weight']=trim($fields[$WeightFieldIndex]);}
    if($DimensionalWeightFieldIndex>=0 && trim($fields[$DimensionalWeightFieldIndex])!=''){$package['DimensionalWeight']=trim($fields[$DimensionalWeightFieldIndex]);}
    if($WeightsUOMFieldIndex>=0){$package['WeightsUOM']=trim($fields[$WeightsUOMFieldIndex]);}
    if($WeightVarianceFieldIndex>=0 && trim($fields[$WeightVarianceFieldIndex])!=''){$package['WeightVariance']=trim($fields[$WeightVarianceFieldIndex]);}
    if($StackingFactorFieldIndex>=0 && trim($fields[$StackingFactorFieldIndex])!=''){$package['StackingFactor']=trim($fields[$StackingFactorFieldIndex]);}
    //if($HazardousMaterialFieldIndex>=0 && trim($fields[$HazardousMaterialFieldIndex])!=''){$package['HazardousMaterial']=trim($fields[$HazardousMaterialFieldIndex]);}
    if($ShippingScopeFieldIndex>=0 && trim($fields[$ShippingScopeFieldIndex])!=''){$package['ShippingScope']=trim($fields[$ShippingScopeFieldIndex]);}
    if($BulkFieldIndex>=0 && trim($fields[$BulkFieldIndex])!=''){$package['Bulk']=trim($fields[$BulkFieldIndex]);}
    if($RegulatingCountryFieldIndex>=0 && trim($fields[$RegulatingCountryFieldIndex])!=''){$package['RegulatingCountry']=trim($fields[$RegulatingCountryFieldIndex]);}
    if($TransportMethodFieldIndex>=0 && trim($fields[$TransportMethodFieldIndex])!=''){$package['TransportMethod']=trim($fields[$TransportMethodFieldIndex]);}
    if($RegulatedFieldIndex>=0 && trim($fields[$RegulatedFieldIndex])!=''){$package['Regulated']=trim($fields[$RegulatedFieldIndex]);}
    if($DescriptionFieldIndex>=0 && trim($fields[$DescriptionFieldIndex])!=''){$package['Description']=trim($fields[$DescriptionFieldIndex]);}
    if($HazardousMaterialCodeQualifierFieldIndex>=0 && trim($fields[$HazardousMaterialCodeQualifierFieldIndex])!=''){$package['HazardousMaterialCodeQualifier']=trim($fields[$HazardousMaterialCodeQualifierFieldIndex]);}
    if($HazardousMaterialDescriptionFieldIndex>=0 && trim($fields[$HazardousMaterialDescriptionFieldIndex])!=''){$package['HazardousMaterialDescription']=trim($fields[$HazardousMaterialDescriptionFieldIndex]);}
    if($HazardousMaterialLabelCodeFieldIndex>=0 && trim($fields[$HazardousMaterialLabelCodeFieldIndex])!=''){$package['HazardousMaterialLabelCode']=trim($fields[$HazardousMaterialLabelCodeFieldIndex]);}
    if($ShippingNameFieldIndex>=0 && trim($fields[$ShippingNameFieldIndex])!=''){$package['ShippingName']=trim($fields[$ShippingNameFieldIndex]);}
    if($UNNAIDCodeFieldIndex>=0 && trim($fields[$UNNAIDCodeFieldIndex])!=''){$package['UNNAIDCode']=trim($fields[$UNNAIDCodeFieldIndex]);}
    if($HazardousPlacardNotationFieldIndex>=0 && trim($fields[$HazardousPlacardNotationFieldIndex])!=''){$package['HazardousPlacardNotation']=trim($fields[$HazardousPlacardNotationFieldIndex]);}
    if($WHMISCodeFieldIndex>=0 && trim($fields[$WHMISCodeFieldIndex])!=''){$package['WHMISCode']=trim($fields[$WHMISCodeFieldIndex]);}
    if($WHMISFreeTextFieldIndex>=0 && trim($fields[$WHMISFreeTextFieldIndex])!=''){$package['WHMISFreeText']=trim($fields[$WHMISFreeTextFieldIndex]);}
    if($PackingGroupCodeFieldIndex>=0 && trim($fields[$PackingGroupCodeFieldIndex])!=''){$package['PackingGroupCode']=trim($fields[$PackingGroupCodeFieldIndex]);}
    if($RegulationsExemptionCodeFieldIndex>=0 && trim($fields[$RegulationsExemptionCodeFieldIndex])!=''){$package['RegulationsExemptionCode']=trim($fields[$RegulationsExemptionCodeFieldIndex]);}
    if($TextMessageFieldIndex>=0 && trim($fields[$TextMessageFieldIndex])!=''){$package['TextMessage']=trim($fields[$TextMessageFieldIndex]);}
    if($OuterPackageLabelFieldIndex>=0 && trim($fields[$OuterPackageLabelFieldIndex])!=''){$package['OuterPackageLabel']=trim($fields[$OuterPackageLabelFieldIndex]);}
    if($LanguageCodeFieldIndex>=0 && trim($fields[$LanguageCodeFieldIndex])!=''){$package['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);} 

    // see if this partnumber was established in the Items list
    if(array_key_exists($PartNumber,$items))
    {
     $items[$PartNumber]['packages'][]=$package;
    }
    else
    {
     $errors[]='packages contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
    }
    $recordnumber++;
   }
  }
 }

 // --------------------- Kits -----------------------
 if(in_array('Kits',$sheetNames))
 {
  $kitsSheet=$xlsx->getSheetData('Kits');

  $PartNumberFieldIndex=-1; $DescriptionFieldIndex=-1; $DescriptionCodeFieldIndex=-1; $QuantityInKitFieldIndex=-1; $QuantityInKitUOMfieldIndex=-1; $LanguageCodeFieldIndex=-1; $ComponentPartTerminologyIDfieldIndex=-1; $SoldSeparatelyFieldIndex=-1; $IDQualifierFieldIndex=-1; $SequenceCodeFieldIndex=-1; $ComponentPartNumberFieldIndex=-1; $ComponentBrandFieldIndex=-1; $ComponentBrandLabelFieldIndex=-1; $ComponentSubBrandFieldIndex=-1; $ComponentSubBrandLabelFieldIndex=-1;
  for($i=0; $i<=count($kitsSheet[0])-1; $i++)
  { // identify the named columns' IDs
   if($kitsSheet[0][$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
   if($kitsSheet[0][$i]=='Description'){$DescriptionFieldIndex=$i;}
   if($kitsSheet[0][$i]=='DescriptionCode'){$DescriptionCodeFieldIndex=$i;}
   if($kitsSheet[0][$i]=='QuantityInKit'){$QuantityInKitFieldIndex=$i;}
   if($kitsSheet[0][$i]=='QuantityInKitUOM'){$QuantityInKitUOMfieldIndex=$i;}
   if($kitsSheet[0][$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
   if($kitsSheet[0][$i]=='ComponentPartTerminologyID'){$ComponentPartTerminologyIDfieldIndex=$i;}
   if($kitsSheet[0][$i]=='SoldSeparately'){$SoldSeparatelyFieldIndex=$i;}
   if($kitsSheet[0][$i]=='IDQualifier'){$IDQualifierFieldIndex=$i;}
   if($kitsSheet[0][$i]=='SequenceCode'){$SequenceCodeFieldIndex=$i;}
   if($kitsSheet[0][$i]=='ComponentPartNumber'){$ComponentPartNumberFieldIndex=$i;}
   if($kitsSheet[0][$i]=='ComponentBrand'){$ComponentBrandFieldIndex=$i;}
   if($kitsSheet[0][$i]=='ComponentBrandLabel'){$ComponentBrandLabelFieldIndex=$i;}
   if($kitsSheet[0][$i]=='ComponentSubBrand'){$ComponentSubBrandFieldIndex=$i;}
   if($kitsSheet[0][$i]=='ComponentSubBrandLabel'){$ComponentSubBrandLabelFieldIndex=$i;}
  } 
  
  $recordnumber=0;
  if($PartNumberFieldIndex==0)
  {
   foreach($kitsSheet as $fields)
   {
    if($recordnumber==0){$recordnumber++;continue;}
    $kit=array();
    $PartNumber=trim($fields[0]);

    if($DescriptionFieldIndex>=0){$kit['Description']= htmlspecialchars(trim($fields[$DescriptionFieldIndex]));}
    if($DescriptionCodeFieldIndex>=0){$kit['DescriptionCode']= trim($fields[$DescriptionCodeFieldIndex]);}
    if($QuantityInKitFieldIndex>=0){$kit['QuantityInKit']=trim($fields[$QuantityInKitFieldIndex]);}
    if($QuantityInKitUOMfieldIndex>=0){$kit['QuantityInKitUOM']=trim($fields[$QuantityInKitUOMfieldIndex]);}
    if($LanguageCodeFieldIndex>=0){$kit['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);}
    if($ComponentPartTerminologyIDfieldIndex>=0){$kit['ComponentPartTerminologyID']=trim($fields[$ComponentPartTerminologyIDfieldIndex]);}
    if($SoldSeparatelyFieldIndex>=0){$kit['SoldSeparately']=trim($fields[$SoldSeparatelyFieldIndex]);}
    if($IDQualifierFieldIndex>=0){$kit['IDQualifier']=trim($fields[$IDQualifierFieldIndex]);}
    if($SequenceCodeFieldIndex>=0){$kit['SequenceCode']=trim($fields[$SequenceCodeFieldIndex]);}
    if($ComponentPartNumberFieldIndex>=0){$kit['ComponentPartNumber']=trim($fields[$ComponentPartNumberFieldIndex]);}
    if($ComponentBrandFieldIndex>=0){$kit['ComponentBrand']=trim($fields[$ComponentBrandFieldIndex]);}
    if($ComponentBrandLabelFieldIndex>=0){$kit['ComponentBrandLabel']=trim($fields[$ComponentBrandLabelFieldIndex]);}
    if($ComponentSubBrandFieldIndex>=0){$kit['ComponentSubBrand']=trim($fields[$ComponentSubBrandFieldIndex]);}
    if($ComponentSubBrandLabelFieldIndex>=0){$kit['ComponentSubBrandLabel']=trim($fields[$ComponentSubBrandLabelFieldIndex]);}
    
    // see if this partnumber was established in the Items list
    if(array_key_exists($PartNumber,$items))
    {
     $items[$PartNumber]['kits'][]=$kit;
    }
    else
    {
     $errors[]='Kits contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
    }
    $recordnumber++;
   }
  }
 }
 
 
 // --------------------- Interchanges -----------------------
 if(in_array('Interchanges',$sheetNames))
 {
  $interchangesSheet=$xlsx->getSheetData('Interchanges');
  $interchanges=array();

  $PartNumberFieldIndex=-1; $CompetitorPartNumberFieldIndex=-1; $ReferenceItemFieldIndex=-1; $InterchangeQuantityFieldIndex=-1; $UOMFieldIndex=-1; $InterchangeNotesFieldIndex=-1; $BrandAAIAIDFieldIndex=-1; $BrandLabelFieldIndex=-1; $SubBrandAAIAIDFieldIndex=-1; $SubBrandLabelFieldIndex=-1; $VMRSBrandIDFieldIndex=-1; $ItemEquivalentUOMFieldIndex=-1; $QualityGradeLevelFieldIndex=-1; $InternalNotesFieldIndex=-1; $LanguageCodeFieldIndex=-1;

  for($i=0; $i<=count($interchangesSheet[0])-1; $i++)
  { // identify the named columns' IDs
   if($interchangesSheet[0][$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='CompetitorPartNumber'){$CompetitorPartNumberFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='ReferenceItem'){$ReferenceItemFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='InterchangeQuantity'){$InterchangeQuantityFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='UOM'){$UOMFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='InterchangeNotes'){$InterchangeNotesFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='BrandAAIAID'){$BrandAAIAIDFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='BrandLabel'){$BrandLabelFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='SubBrandAAIAID'){$SubBrandAAIAIDFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='SubBrandLabel'){$SubBrandLabelFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='VMRSBrandID'){$VMRSBrandIDFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='ItemEquivalentUOM'){$ItemEquivalentUOMFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='QualityGradeLevel'){$QualityGradeLevelFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='InternalNotes'){$InternalNotesFieldIndex=$i;}
   if($interchangesSheet[0][$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
  } 
  
  $recordnumber=0;
  if($PartNumberFieldIndex==0)
  {
   foreach($interchangesSheet as $fields)
   {
    if($recordnumber==0){$recordnumber++;continue;}
    $interchange=array();
    $PartNumber=trim($fields[0]);

    if($CompetitorPartNumberFieldIndex>=0 && trim($fields[$CompetitorPartNumberFieldIndex])!=''){$interchange['CompetitorPartNumber']=trim($fields[$CompetitorPartNumberFieldIndex]);}
    if($ReferenceItemFieldIndex>=0 && trim($fields[$ReferenceItemFieldIndex])!=''){$interchange['ReferenceItem']=trim($fields[$ReferenceItemFieldIndex]);}
    if($InterchangeQuantityFieldIndex>=0 && trim($fields[$InterchangeQuantityFieldIndex])!=''){$interchange['InterchangeQuantity']=trim($fields[$InterchangeQuantityFieldIndex]);}
    if($UOMFieldIndex>=0 && trim($fields[$UOMFieldIndex])!=''){$interchange['UOM']=trim($fields[$UOMFieldIndex]);}
    if($InterchangeNotesFieldIndex>=0 && trim($fields[$InterchangeNotesFieldIndex])!=''){$interchange['InterchangeNotes']=htmlspecialchars(trim($fields[$InterchangeNotesFieldIndex]));}
    if($BrandAAIAIDFieldIndex>=0 && trim($fields[$BrandAAIAIDFieldIndex])!=''){$interchange['BrandAAIAID']=trim($fields[$BrandAAIAIDFieldIndex]);}
    if($BrandLabelFieldIndex>=0 && trim($fields[$BrandLabelFieldIndex])!=''){$interchange['BrandLabel']=trim($fields[$BrandLabelFieldIndex]);}
    if($SubBrandAAIAIDFieldIndex>=0 && trim($fields[$SubBrandAAIAIDFieldIndex])!=''){$interchange['SubBrandAAIAID']=trim($fields[$SubBrandAAIAIDFieldIndex]);}
    if($SubBrandLabelFieldIndex>=0 && trim($fields[$SubBrandLabelFieldIndex])!=''){$interchange['SubBrandLabel']=trim($fields[$SubBrandLabelFieldIndex]);}
    if($VMRSBrandIDFieldIndex>=0 && trim($fields[$VMRSBrandIDFieldIndex])!=''){$interchange['VMRSBrandID']=trim($fields[$VMRSBrandIDFieldIndex]);}
    if($ItemEquivalentUOMFieldIndex>=0 && trim($fields[$ItemEquivalentUOMFieldIndex])!=''){$interchange['ItemEquivalentUOM']=trim($fields[$ItemEquivalentUOMFieldIndex]);}
    if($QualityGradeLevelFieldIndex>=0 && trim($fields[$QualityGradeLevelFieldIndex])!=''){$interchange['QualityGradeLevel']=trim($fields[$QualityGradeLevelFieldIndex]);}
    if($InternalNotesFieldIndex>=0 && trim($fields[$InternalNotesFieldIndex])!=''){$interchange['InternalNotes']=htmlspecialchars(trim($fields[$InternalNotesFieldIndex]));}
    if($LanguageCodeFieldIndex>=0 && trim($fields[$LanguageCodeFieldIndex])!=''){$interchange['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);}
  
    // see if this partnumber was established in the Items list
    if(array_key_exists($PartNumber,$items))
    {
     $items[$PartNumber]['interchanges'][]=$interchange;
    }
    else
    {
     $errors[]='Interchanges contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
    }
    $recordnumber++;
   }
  }
 }
 
 // --------------------- Assets -----------------------
 if(in_array('DigitalAssets',$sheetNames))
 {
  $digitalAssetsSheet=$xlsx->getSheetData('DigitalAssets');
  $PartNumberFieldIndex=-1; $FileNameFieldIndex=-1; $AssetIDFieldIndex=-1; $AssetTypeFieldIndex=-1; $FileTypeFieldIndex=-1; $RepresentationFieldIndex=-1; $FileSizeFieldIndex=-1; $ResolutionFieldIndex=-1; $ColorModeFieldIndex=-1; $BackgroundFieldIndex=-1; $OrientationViewFieldIndex=-1; $AssetHeightFieldIndex=-1; $AssetWidthFieldIndex=-1; $UOMFieldIndex=-1; $FilePathFieldIndex=-1; $URIFieldIndex=-1; $DurationFieldIndex=-1; $DurationUOMFieldIndex=-1; $FrameFieldIndex=-1; $TotalFramesFieldIndex=-1; $PlaneFieldIndex=-1; $HemisphereFieldIndex=-1; $PlungeFieldIndex=-1; $TotalPlanesFieldIndex=-1; $DescriptionFieldIndex=-1; $DescriptionCodeFieldIndex=-1; $DescriptionLanguageCodeFieldIndex=-1; $AssetDateFieldIndex=-1; $AssetDateTypeFieldIndex=-1; $CountryFieldIndex=-1; $LanguageCodeFieldIndex=-1;

  for($i=0; $i<=count($digitalAssetsSheet[0])-1; $i++)
  { // identify the named columns' IDs
   if($digitalAssetsSheet[0][$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='FileName'){$FileNameFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='AssetID'){$AssetIDFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='AssetType'){$AssetTypeFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='FileType'){$FileTypeFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='Representation'){$RepresentationFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='FileSize'){$FileSizeFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='Resolution'){$ResolutionFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='ColorMode'){$ColorModeFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='Background'){$BackgroundFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='OrientationView'){$OrientationViewFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='AssetHeight'){$AssetHeightFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='AssetWidth'){$AssetWidthFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='UOM'){$UOMFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='FilePath'){$FilePathFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='URI'){$URIFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='Duration'){$DurationFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='DurationUOM'){$DurationUOMFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='Frame'){$FrameFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='TotalFrames'){$TotalFramesFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='TotalFrames'){$TotalFramesFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='TotalFrames'){$TotalFramesFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='Plane'){$PlaneFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='Hemisphere'){$HemisphereFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='Plunge'){$PlungeFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='TotalPlanes'){$TotalPlanesFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='Description'){$DescriptionFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='DescriptionCode'){$DescriptionCodeFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='DescriptionLanguageCode'){$DescriptionLanguageCodeFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='AssetDate'){$AssetDateFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='AssetDateType'){$AssetDateTypeFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='Country'){$CountryFieldIndex=$i;}
   if($digitalAssetsSheet[0][$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
  } 
  
  $recordnumber=0;
  if($PartNumberFieldIndex==0)
  {
   foreach($digitalAssetsSheet as $fields)
   {
    if($recordnumber==0){$recordnumber++;continue;}
    $asset=array();
    $PartNumber=trim($fields[0]); 

    if($FileNameFieldIndex>=0 && trim($fields[$FileNameFieldIndex])!=''){$asset['FileName']=trim($fields[$FileNameFieldIndex]);}
    if($AssetIDFieldIndex>=0 && trim($fields[$AssetIDFieldIndex])!=''){$asset['AssetID']=trim($fields[$AssetIDFieldIndex]);}
    if($AssetTypeFieldIndex>=0 && trim($fields[$AssetTypeFieldIndex])!='')
    {
     $asset['AssetType']=trim($fields[$AssetTypeFieldIndex]);
     if(!array_key_exists($asset['AssetType'], $validAssetTypes))
     {
      $errors[]='Asset type ('.$asset['AssetType'].') for partnumber ('.$PartNumber.') is not found in the PCdb';  
     }
    }
    if($FileTypeFieldIndex>=0 && trim($fields[$FileTypeFieldIndex])!=''){$asset['FileType']=trim($fields[$FileTypeFieldIndex]);}
    if($RepresentationFieldIndex>=0 && trim($fields[$RepresentationFieldIndex])!=''){$asset['Representation']=trim($fields[$RepresentationFieldIndex]);}
    if($FileSizeFieldIndex>=0 && trim($fields[$FileSizeFieldIndex])!=''){$asset['FileSize']=trim($fields[$FileSizeFieldIndex]);}
    if($ResolutionFieldIndex>=0 && trim($fields[$ResolutionFieldIndex])!=''){$asset['Resolution']=trim($fields[$ResolutionFieldIndex]);}
    if($ColorModeFieldIndex>=0 && trim($fields[$ColorModeFieldIndex])!=''){$asset['ColorMode']=trim($fields[$ColorModeFieldIndex]);}
    if($BackgroundFieldIndex>=0 && trim($fields[$BackgroundFieldIndex])!=''){$asset['Background']=trim($fields[$BackgroundFieldIndex]);}
    if($OrientationViewFieldIndex>=0 && trim($fields[$OrientationViewFieldIndex])!=''){$asset['OrientationView']=trim($fields[$OrientationViewFieldIndex]);}
    if($AssetHeightFieldIndex>=0 && trim($fields[$AssetHeightFieldIndex])!=''){$asset['AssetHeight']=trim($fields[$AssetHeightFieldIndex]);}
    if($AssetWidthFieldIndex>=0 && trim($fields[$AssetWidthFieldIndex])!=''){$asset['AssetWidth']=trim($fields[$AssetWidthFieldIndex]);}
    if($UOMFieldIndex>=0 && trim($fields[$UOMFieldIndex])!=''){$asset['AssetDimensionsUOM']=trim($fields[$UOMFieldIndex]);}
    if($FilePathFieldIndex>=0 && trim($fields[$FilePathFieldIndex])!=''){$asset['FilePath']=trim($fields[$FilePathFieldIndex]);}
    if($URIFieldIndex>=0 && trim($fields[$URIFieldIndex])!=''){$asset['URI']=htmlspecialchars(trim($fields[$URIFieldIndex]));}
    if($DurationFieldIndex>=0 && trim($fields[$DurationFieldIndex])!=''){$asset['Duration']=trim($fields[$DurationFieldIndex]);}
    if($DurationUOMFieldIndex>=0 && trim($fields[$DurationUOMFieldIndex])!=''){$asset['DurationUOM']=trim($fields[$DurationUOMFieldIndex]);}
    if($FrameFieldIndex>=0 && trim($fields[$FrameFieldIndex])!=''){$asset['Frame']=trim($fields[$FrameFieldIndex]);}
    if($TotalFramesFieldIndex>=0 && trim($fields[$TotalFramesFieldIndex])!=''){$asset['TotalFrames']=trim($fields[$TotalFramesFieldIndex]);}
    if($PlaneFieldIndex>=0 && trim($fields[$PlaneFieldIndex])!=''){$asset['Plane']=trim($fields[$PlaneFieldIndex]);}
    if($HemisphereFieldIndex>=0 && trim($fields[$HemisphereFieldIndex])!=''){$asset['Hemisphere']=trim($fields[$HemisphereFieldIndex]);}
    if($PlungeFieldIndex>=0 && trim($fields[$PlungeFieldIndex])!=''){$asset['Plunge']=trim($fields[$PlungeFieldIndex]);}
    if($TotalPlanesFieldIndex>=0 && trim($fields[$TotalPlanesFieldIndex])!=''){$asset['TotalPlanes']=trim($fields[$TotalPlanesFieldIndex]);}
    if($DescriptionFieldIndex>=0 && trim($fields[$DescriptionFieldIndex])!=''){$asset['Description']=htmlspecialchars(trim($fields[$DescriptionFieldIndex]));}
    if($DescriptionCodeFieldIndex>=0 && trim($fields[$DescriptionCodeFieldIndex])!=''){$asset['DescriptionCode']=trim($fields[$DescriptionCodeFieldIndex]);}
    if($DescriptionLanguageCodeFieldIndex>=0 && trim($fields[$DescriptionLanguageCodeFieldIndex])!=''){$asset['DescriptionLanguageCode']=trim($fields[$DescriptionLanguageCodeFieldIndex]);}
    if($AssetDateFieldIndex>=0 && trim($fields[$AssetDateFieldIndex])!=''){$asset['AssetDate']=trim($fields[$AssetDateFieldIndex]);}
    if($AssetDateTypeFieldIndex>=0 && trim($fields[$AssetDateTypeFieldIndex])!=''){$asset['AssetDateType']=trim($fields[$AssetDateTypeFieldIndex]);}
    if($CountryFieldIndex>=0 && trim($fields[$CountryFieldIndex])!=''){$asset['Country']=trim($fields[$CountryFieldIndex]);}
    if($LanguageCodeFieldIndex>=0 && trim($fields[$LanguageCodeFieldIndex])!=''){$asset['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);}
    
    // see if this partnumber was established in the Items list
    if(array_key_exists($PartNumber,$items))
    {
     $items[$PartNumber]['assets'][]=$asset;
    }
    else
    {
     $errors[]='Assets contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
    }
    $recordnumber++;
   }
  }
 }
 
 //-----------------------------------------------------
 $doc=$PIESgenerator->createPIESdoc($header,$marketingcopys,$items);//,$descriptions,$prices,$expi,$attributes,$packages,$kits,$interchanges,$assets);
 $doc->formatOutput=true;
 $piesxmlstring=$doc->saveXML();    

 $newdoc=new DOMDocument();
 $newdoc->loadXML($piesxmlstring); 
 // I do realize that this extra step seems redundant. Running the schema validation 
 // directly on the original object failed because of namespace problems that 
 // I could not resolve (or understand). Exporting the original object's xml 
 // to a text string and then re-importing it to a new DOM object was the
 // work-around that I found.
 
 $schemavalidated=true;   
 libxml_use_internal_errors(true);
 if(!$newdoc->schemaValidate('PIES_6_7_r5_XSD.xsd'))
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

if(isset($_POST['showtext']) || (count($errors)>0 && !isset($_POST['ignorelogic']) ) || count($schemaresults)>0 || !$validUpload)
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
                        <h3 class="card-header text-start">Build PIES xml from structured text</h3>

                        <div class="card-body">
                            <h5 class="alert alert-secondary">Step 2: Analyze results and download XML</h5>
                            <div class="alert alert-info"><em>Validation done against PCdb version: <?php echo $pcdbVersion;?></em></div>
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
                            <?php }else{if(strlen($piesxmlstring)>0){?>
                            <div style="padding:10px;"><textarea rows="20" cols="150"><?php echo $piesxmlstring;?></textarea></div>
                            <?php }}?>

                            <?php if(count($errors)>0 && !isset($_POST['ignorelogic'])){?>
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
       <?php if (isset($_SESSION['userid'])){include('./includes/footer.php');} ?>
    </body>
</html>
<?php }
$logs->logSystemEvent('rhubarb', 0, 'version:6.7;file:'.$originalFilename.';items:'.count($items).';xsd:'.count($schemaresults).';logic:'.count($errors).';by:'.$_SERVER['REMOTE_ADDR']);

if($streamXML && $validUpload)
{
$filename='PIES_6_7_FULL_'.date('Y-m-d').'.xml';
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Type: application/octet-stream');
header('Content-Length: ' . strlen($piesxmlstring));
header('Connection: close');    
echo $piesxmlstring;
}?>