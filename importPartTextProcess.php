<?php
include_once('./class/pimClass.php');
include_once('./class/PIES7_1GeneratorClass.php');
include_once('./class/logsClass.php');

$navCategory = 'import';


$pim = new pim;

 //ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'sandpiper index.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$logs = new logs;
$PIESgenerator=new PIESgenerator();

if(isset($_POST['submit']) && $_POST['submit']=='Next') 
{
 $parseerrors=array(); $warnings=array(); $schemaresults=array();
 
 // --------------------- items ---------------------------------   
 $itemsrecords = explode("\r\n", $_POST['items']);
 $headerfields=explode("\t",$itemsrecords[0]);
    
 if($headerfields[0]=='PartNumber' && $headerfields[1]=='PartTerminologyID' && $headerfields[2]=='BrandAAIAID')
 { // three required elements are present in the header row
  $items=array(); 
 
 // examine the header to map the optional elements that we care about
  $fieldnumber=0;
  $ItemLevelGTINfieldIndex=0; $GTINQualifierFieldIndex=0; $MinimumOrderQuantityFieldIndex=0; $MinimumOrderQuantityUOMfieldIndex=0;
  for($i=0; $i<=count($headerfields)-1; $i++)
  {
   if($headerfields[$i]=='ItemLevelGTIN'){$ItemLevelGTINfieldIndex=$i;}
   if($headerfields[$i]=='GTINQualifier'){$GTINQualifierFieldIndex=$i;}
   if($headerfields[$i]=='MinimumOrderQuantity'){$MinimumOrderQuantityFieldIndex=$i;}
   if($headerfields[$i]=='MinimumOrderQuantityUOM'){$MinimumOrderQuantityUOMfieldIndex=$i;}
   if($headerfields[$i]=='HazardousMaterialCode'){$HazardousMaterialCodeFieldIndex=$i;}
   if($headerfields[$i]=='BaseItemID'){$BaseItemIDfieldIndex=$i;}
   if($headerfields[$i]=='ACESApplications'){$ACESApplicationsFieldIndex=$i;}
   if($headerfields[$i]=='ItemQuantitySize'){$ItemQuantitySizeFieldIndex=$i;}
   if($headerfields[$i]=='ItemQuantitySizeUOM'){$ItemQuantitySizeUOMfieldIndex=$i;}
   if($headerfields[$i]=='ContainerType'){$ContainerTypeFieldIndex=$i;}
   if($headerfields[$i]=='ItemEffectiveDate'){$ItemEffectiveDateFieldIndex=$i;}
   if($headerfields[$i]=='AvailableDate'){$AvailableDateFieldIndex=$i;}
   if($headerfields[$i]=='UNSPSC'){$UNSPSCfieldIndex=$i;}
   $fieldnumber++;
  }
   
     
     
  // main items list. parse the text-area input lines
  $recordnumber=0;
  foreach($itemsrecords as $record)
  {
   $fields = explode("\t",$record);
   if(count($fields)==1){continue;} // empty row
   if($recordnumber==0){$recordnumber++;continue;}
     
   $item=array('descriptions'=>array(), 'attributes'=>array(),'interchanges'=>array(),'assets'=>array(),'expis'=>array(),'packages'=>array());
   
   $PartNumber=trim($fields[0]);
   $item['PartTerminologyID']=trim($fields[1]);
   $item['BrandAAIAID']=trim($fields[2]);
   
   if($ItemLevelGTINfieldIndex && trim($fields[$ItemLevelGTINfieldIndex])!=''){$item['ItemLevelGTIN']=trim($fields[$ItemLevelGTINfieldIndex]);}
   if($GTINQualifierFieldIndex && trim($fields[$GTINQualifierFieldIndex])!=''){$item['GTINQualifier']=trim($fields[$GTINQualifierFieldIndex]);}
   if($MinimumOrderQuantityFieldIndex && trim($fields[$MinimumOrderQuantityFieldIndex])!=''){$item['MinimumOrderQuantity']=trim($fields[$MinimumOrderQuantityFieldIndex]);}
   if($MinimumOrderQuantityUOMfieldIndex && trim($fields[$MinimumOrderQuantityUOMfieldIndex])!=''){$item['MinimumOrderQuantityUOM']=trim($fields[$MinimumOrderQuantityUOMfieldIndex]);}
   if($HazardousMaterialCodeFieldIndex && trim($fields[$HazardousMaterialCodeFieldIndex])!=''){$item['HazardousMaterialCode']=trim($fields[$HazardousMaterialCodeFieldIndex]);}
   if($BaseItemIDfieldIndex && trim($fields[$BaseItemIDfieldIndex])!=''){$item['BaseItemID']=trim($fields[$BaseItemIDfieldIndex]);}
   if($ACESApplicationsFieldIndex && trim($fields[$ACESApplicationsFieldIndex])!=''){$item['ACESApplications']=trim($fields[$ACESApplicationsFieldIndex]);}
   if($ItemQuantitySizeFieldIndex && trim($fields[$ItemQuantitySizeFieldIndex])!=''){$item['ItemQuantitySize']=trim($fields[$ItemQuantitySizeFieldIndex]);}
   if($ItemQuantitySizeUOMfieldIndex && trim($fields[$ItemQuantitySizeUOMfieldIndex])!=''){$item['ItemQuantitySizeUOM']=trim($fields[$ItemQuantitySizeUOMfieldIndex]);}
   if($ContainerTypeFieldIndex && trim($fields[$ContainerTypeFieldIndex])!=''){$item['ContainerType']=trim($fields[$ContainerTypeFieldIndex]);}
   if($ItemEffectiveDateFieldIndex && trim($fields[$ItemEffectiveDateFieldIndex])!=''){$item['ItemEffectiveDate']=trim($fields[$ItemEffectiveDateFieldIndex]);}
   if($AvailableDateFieldIndex && trim($fields[$AvailableDateFieldIndex])!=''){$item['AvailableDate']=trim($fields[$AvailableDateFieldIndex]);}
   if($UNSPSCfieldIndex && trim($fields[$UNSPSCfieldIndex])!=''){$item['UNSPSC']=trim($fields[$UNSPSCfieldIndex]);}  
   
   $items[$PartNumber]=$item;
   $recordnumber++;
  }
 }
 else
 { // The header row does not contain: "PartNumber \t PartTerminologyID \t BrandAAIAID"

   $parseerrors[]='First row does not contain the expected column names (PartNumber,PartTerminologyID,BrandAAIAID)';  
 }
 
 // --------------------- Descriptions -----------------------

 $descriptionsrecords = explode("\r\n", $_POST['descriptions']);
 $headerfields=explode("\t",$descriptionsrecords[0]);

 $descriptions=array();

 $PartNumberFieldIndex=-1; $DescriptionFieldIndex=-1; $DescriptionCodeFieldIndex=-1; $LanguageCodeFieldIndex=-1; $SequenceFieldIndex=-1; 
 for($i=0; $i<=count($headerfields)-1; $i++)
 {
  if($headerfields[$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
  if($headerfields[$i]=='Description'){$DescriptionFieldIndex=$i;}
  if($headerfields[$i]=='DescriptionCode'){$DescriptionCodeFieldIndex=$i;}
  if($headerfields[$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
  if($headerfields[$i]=='Sequence'){$SequenceFieldIndex=$i;}
 } 
  
 $recordnumber=0;
 if($PartNumberFieldIndex==0)
 {
  foreach($descriptionsrecords as $record)
  {
   $fields = explode("\t",$record);
   if(count($fields)==1){continue;} // empty row
   if($recordnumber==0){$recordnumber++;continue;}
   $description=array();
   $PartNumber=trim($fields[0]);
   if($DescriptionFieldIndex>=0){$description['Description']=trim($fields[$DescriptionFieldIndex]);}
   if($DescriptionCodeFieldIndex>=0){$description['DescriptionCode']=trim($fields[$DescriptionCodeFieldIndex]);}
   if($LanguageCodeFieldIndex>=0){$description['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);}
   if($SequenceFieldIndex>=0){$description['Sequence']=trim($fields[$SequenceFieldIndex]);}
   // see if this partnumber was established in the Items list
   if(array_key_exists($PartNumber,$items))
   {
    $items[$PartNumber]['descriptions'][]=$description;
   }
   else
   {
    $parseerrors[]='Descriptions contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
   }
   $recordnumber++;
  } 
 }
}
 
 

 // --------------------- Prices -----------------------
$pricesrecords = explode("\r\n", $_POST['prices']);
$headerfields=explode("\t",$pricesrecords[0]);

$PartNumberFieldIndex=-1; 
$PriceSheetNumberFieldIndex=-1;
$CurrencyCodeFieldIndex=-1;
$EffectiveDateFieldIndex=-1;
$ExpirationDateFieldIndex=-1;
$PriceFieldIndex=-1;
$PriceUOMFieldIndex=-1;
$PriceTypeDescriptionFieldIndex=-1;
$PriceBreakFieldIndex=-1;
$PriceBreakUOMFieldIndex=-1;
$PriceMultiplierFieldIndex=-1;
$PriceTypeFieldIndex=-1;



for($i=0; $i<=count($headerfields)-1; $i++)
{ // identify the named columns' IDs
 if($headerfields[$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
 if($headerfields[$i]=='PriceSheetNumber'){$PriceSheetNumberFieldIndex=$i;}
 if($headerfields[$i]=='CurrencyCode'){$CurrencyCodeFieldIndex=$i;}
 if($headerfields[$i]=='EffectiveDate'){$EffectiveDateFieldIndex=$i;}
 if($headerfields[$i]=='ExpirationDate'){$ExpirationDateFieldIndex=$i;}
 if($headerfields[$i]=='Price'){$PriceFieldIndex=$i;}
 if($headerfields[$i]=='PriceUOM'){$PriceUOMFieldIndex=$i;}
 if($headerfields[$i]=='PriceTypeDescription'){$PriceTypeDescriptionFieldIndex=$i;}
 if($headerfields[$i]=='PriceBreak'){$PriceBreakFieldIndex=$i;}
 if($headerfields[$i]=='PriceBreakUOM'){$PriceBreakUOMFieldIndex=$i;}
 if($headerfields[$i]=='PriceMultiplier'){$PriceMultiplierFieldIndex=$i;}
 if($headerfields[$i]=='PriceType'){$PriceTypeFieldIndex=$i;}
} 

$recordnumber=0;
if($PartNumberFieldIndex==0)
{
 foreach($pricesrecords as $record)
 {
  $fields = explode("\t",$record);
  if(count($fields)==1){continue;} // empty row
  if($recordnumber==0){$recordnumber++;continue;}
  $price=array();
  $PartNumber=trim($fields[0]);

  if($PriceSheetNumberFieldIndex>=0 && trim($fields[$PriceSheetNumberFieldIndex])!=''){$price['PriceSheetNumber']=trim($fields[$PriceSheetNumberFieldIndex]);}
  $price['CurrencyCode']=''; if($CurrencyCodeFieldIndex>=0 && trim($fields[$CurrencyCodeFieldIndex])!=''){$price['CurrencyCode']=trim($fields[$CurrencyCodeFieldIndex]);}
  $price['EffectiveDate']='0000-00-00'; if($EffectiveDateFieldIndex>=0 && trim($fields[$EffectiveDateFieldIndex])!=''){$price['EffectiveDate']=trim($fields[$EffectiveDateFieldIndex]);}
  $price['ExpirationDate']='0000-00-00'; if($ExpirationDateFieldIndex>=0 && trim($fields[$ExpirationDateFieldIndex])!=''){$price['ExpirationDate']=trim($fields[$ExpirationDateFieldIndex]);}
  if($PriceFieldIndex>=0 && trim($fields[$PriceFieldIndex])!=''){$price['Price']=trim($fields[$PriceFieldIndex]);}
  if($PriceUOMFieldIndex>=0 && trim($fields[$PriceUOMFieldIndex])!=''){$price['PriceUOM']=trim($fields[$PriceUOMFieldIndex]);}
  $price['PriceTypeDescription']=''; if($PriceTypeDescriptionFieldIndex>=0 && trim($fields[$PriceTypeDescriptionFieldIndex])!=''){$price['PriceTypeDescription']=trim($fields[$PriceTypeDescriptionFieldIndex]);}
  $price['PriceBreak']=''; if($PriceBreakFieldIndex>=0 && trim($fields[$PriceBreakFieldIndex])!=''){$price['PriceBreak']=trim($fields[$PriceBreakFieldIndex]);}
  $price['PriceBreakUOM']=''; if($PriceBreakUOMFieldIndex>=0 && trim($fields[$PriceBreakUOMFieldIndex])!=''){$price['PriceBreakUOM']=trim($fields[$PriceBreakUOMFieldIndex]);}
  $price['PriceMultiplier']=''; if($PriceMultiplierFieldIndex>=0 && trim($fields[$PriceMultiplierFieldIndex])!=''){$price['PriceMultiplier']=trim($fields[$PriceMultiplierFieldIndex]);}
  if($PriceTypeFieldIndex>=0 && trim($fields[$PriceTypeFieldIndex])!=''){$price['PriceType']=trim($fields[$PriceTypeFieldIndex]);}
    
  // see if this partnumber was established in the Items list
  if(array_key_exists($PartNumber,$items))
  {
   $items[$PartNumber]['prices'][]=$price;
  }
  else
  {
   $parseerrors[]='Prices contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
  }
  $recordnumber++;
 }
}



 
 // --------------------- EXPI -----------------------
 
 // --------------------- Attributes -----------------------

$attributesrecords = explode("\r\n", $_POST['attributes']);
$headerfields=explode("\t",$attributesrecords[0]);

$PartNumberFieldIndex=-1; $AttributeIDFieldIndex=-1; $PADBAttributeFieldIndex=-1; $AttributeValueFieldIndex=-1; $StyleIDFieldIndex=-1; $AttributeUOMFieldIndex=-1; $MultiValueQuantityFieldIndex=-1;	$MultiValueSequenceFieldIndex=-1; $LanguageCodeFieldIndex=-1; $RecordNumberFieldIndex=-1; 
for($i=0; $i<=count($headerfields)-1; $i++)
{ // identify the named columns' IDs
 if($headerfields[$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
 if($headerfields[$i]=='AttributeID'){$AttributeIDFieldIndex=$i;}
 if($headerfields[$i]=='PADBAttribute'){$PADBAttributeFieldIndex=$i;}
 if($headerfields[$i]=='AttributeValue'){$AttributeValueFieldIndex=$i;}
 if($headerfields[$i]=='StyleID'){$StyleIDFieldIndex=$i;}
 if($headerfields[$i]=='AttributeUOM'){$AttributeUOMFieldIndex=$i;}
 if($headerfields[$i]=='MultiValueQuantity'){$MultiValueQuantityFieldIndex=$i;}
 if($headerfields[$i]=='MultiValueSequence'){$MultiValueSequenceFieldIndex=$i;}
 if($headerfields[$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
 if($headerfields[$i]=='RecordNumber'){$RecordNumberFieldIndex=$i;} 
} 
  
$recordnumber=0;
if($PartNumberFieldIndex==0)
{
 foreach($attributesrecords as $record)
 {
  $fields = explode("\t",$record);
  if(count($fields)==1){continue;} // empty row
  if($recordnumber==0){$recordnumber++;continue;}
  $attribute=array();
  $PartNumber=trim($fields[0]);

  if($AttributeIDFieldIndex>=0){$attribute['AttributeID']=trim($fields[$AttributeIDFieldIndex]);}
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
   $parseerrors[]='Attributes contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
  }
  $recordnumber++;
 }
}

 // --------------------- Packages -----------------------
 
$packagesrecords = explode("\r\n", $_POST['packages']);
$headerfields=explode("\t",$packagesrecords[0]);

$PartNumberFieldIndex=-1; $PackageLevelGTINFieldIndex=-1; $ElectronicProductCodeFieldIndex=-1; $PackageBarCodeCharactersFieldIndex=-1; $PackageUOMFieldIndex=-1; $QuantityofEachesFieldIndex=-1; $InnerQuantityFieldIndex=-1; $InnerQuantityUOMFieldIndex=-1; $MerchandisingHeightFieldIndex=-1; $MerchandisingWidthFieldIndex=-1; $MerchandisingLengthFieldIndex=-1; $ShippingHeightFieldIndex=-1; $ShippingWidthFieldIndex=-1; $ShippingLengthFieldIndex=-1; $DimensionsUOMFieldIndex=-1; $WeightFieldIndex=-1; $DimensionalWeightFieldIndex=-1; $WeightsUOMFieldIndex=-1; $WeightVarianceFieldIndex=-1; $StackingFactorFieldIndex=-1; $HazardousMaterialFieldIndex=-1; $ShippingScopeFieldIndex=-1; $BulkFieldIndex=-1; $RegulatingCountryFieldIndex=-1; $TransportMethodFieldIndex=-1; $RegulatedFieldIndex=-1; $DescriptionFieldIndex=-1; $HazardousMaterialCodeQualifierFieldIndex=-1; $HazardousMaterialDescriptionFieldIndex=-1; $HazardousMaterialLabelCodeFieldIndex=-1; $ShippingNameFieldIndex=-1; $UNNAIDCodeFieldIndex=-1; $HazardousPlacardNotationFieldIndex=-1; $WHMISCodeFieldIndex=-1; $WHMISFreeTextFieldIndex=-1; $PackingGroupCodeFieldIndex=-1; $RegulationsExemptionCodeFieldIndex=-1; $TextMessageFieldIndex=-1; $OuterPackageLabelFieldIndex=-1; $LanguageCodeFieldIndex=-1;

$packages=array();

for($i=0; $i<=count($headerfields)-1; $i++)
{ // identify the named columns' IDs
 if($headerfields[$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
 if($headerfields[$i]=='PackageLevelGTIN'){$PackageLevelGTINFieldIndex=$i;}
 if($headerfields[$i]=='ElectronicProductCode'){$ElectronicProductCodeFieldIndex=$i;}
 if($headerfields[$i]=='PackageBarCodeCharacters'){$PackageBarCodeCharactersFieldIndex=$i;}
 if($headerfields[$i]=='PackageUOM'){$PackageUOMFieldIndex=$i;}
 if($headerfields[$i]=='QuantityofEaches'){$QuantityofEachesFieldIndex=$i;}
 if($headerfields[$i]=='InnerQuantity'){$InnerQuantityFieldIndex=$i;}
 if($headerfields[$i]=='InnerQuantityUOM'){$InnerQuantityUOMFieldIndex=$i;}
 if($headerfields[$i]=='MerchandisingHeight'){$MerchandisingHeightFieldIndex=$i;}
 if($headerfields[$i]=='MerchandisingWidth'){$MerchandisingWidthFieldIndex=$i;}
 if($headerfields[$i]=='MerchandisingLength'){$MerchandisingLengthFieldIndex=$i;}
 if($headerfields[$i]=='ShippingHeight'){$ShippingHeightFieldIndex=$i;}
 if($headerfields[$i]=='ShippingWidth'){$ShippingWidthFieldIndex=$i;}
 if($headerfields[$i]=='ShippingLength'){$ShippingLengthFieldIndex=$i;}
 if($headerfields[$i]=='DimensionsUOM'){$DimensionsUOMFieldIndex=$i;}
 if($headerfields[$i]=='Weight'){$WeightFieldIndex=$i;}
 if($headerfields[$i]=='DimensionalWeight'){$DimensionalWeightFieldIndex=$i;}
 if($headerfields[$i]=='WeightsUOM'){$WeightsUOMFieldIndex=$i;}
 if($headerfields[$i]=='WeightVariance'){$WeightVarianceFieldIndex=$i;}
 if($headerfields[$i]=='StackingFactor'){$StackingFactorFieldIndex=$i;}
 //if($headerfields[$i]=='HazardousMaterial'){$HazardousMaterialFieldIndex=$i;}
 if($headerfields[$i]=='ShippingScope'){$ShippingScopeFieldIndex=$i;}
 if($headerfields[$i]=='Bulk'){$BulkFieldIndex=$i;}
 if($headerfields[$i]=='RegulatingCountry'){$RegulatingCountryFieldIndex=$i;}
 if($headerfields[$i]=='TransportMethod'){$TransportMethodFieldIndex=$i;}
 if($headerfields[$i]=='Regulated'){$RegulatedFieldIndex=$i;}
 if($headerfields[$i]=='Description'){$DescriptionFieldIndex=$i;}
 if($headerfields[$i]=='HazardousMaterialCodeQualifier'){$HazardousMaterialCodeQualifierFieldIndex=$i;}
 if($headerfields[$i]=='HazardousMaterialDescription'){$HazardousMaterialDescriptionFieldIndex=$i;}
 if($headerfields[$i]=='HazardousMaterialLabelCode'){$HazardousMaterialLabelCodeFieldIndex=$i;}
 if($headerfields[$i]=='ShippingName'){$ShippingNameFieldIndex=$i;}
 if($headerfields[$i]=='UNNAIDCode'){$UNNAIDCodeFieldIndex=$i;}
 if($headerfields[$i]=='HazardousPlacardNotation'){$HazardousPlacardNotationFieldIndex=$i;}
 if($headerfields[$i]=='WHMISCode'){$WHMISCodeFieldIndex=$i;}
 if($headerfields[$i]=='WHMISFreeText'){$WHMISFreeTextFieldIndex=$i;}
 if($headerfields[$i]=='PackingGroupCode'){$PackingGroupCodeFieldIndex=$i;}
 if($headerfields[$i]=='RegulationsExemptionCode'){$RegulationsExemptionCodeFieldIndex=$i;}
 if($headerfields[$i]=='TextMessage'){$TextMessageFieldIndex=$i;}
 if($headerfields[$i]=='OuterPackageLabel'){$OuterPackageLabelFieldIndex=$i;}
 if($headerfields[$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;} 
} 
  
$recordnumber=0;
if($PartNumberFieldIndex==0)
{
 foreach($packagesrecords as $record)
 {
  $fields = explode("\t",$record);
  if(count($fields)==1){continue;} // empty row
  if($recordnumber==0){$recordnumber++;continue;}
  $package=array();
  $PartNumber=trim($fields[0]);

  if($PartNumberFieldIndex>=0){$package['PartNumber']=trim($fields[$PartNumberFieldIndex]);}
  if($PackageLevelGTINFieldIndex>=0&&trim($fields[$PackageLevelGTINFieldIndex])!=''){$package['PackageLevelGTIN']=trim($fields[$PackageLevelGTINFieldIndex]);}
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
   $parseerrors[]='packages contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
  }
  $recordnumber++;
 }
}


 // --------------------- Kits -----------------------
 


 // --------------------- Interchanges -----------------------
$interchanges=array();

$interchangesrecords = explode("\r\n", $_POST['interchanges']);
$headerfields=explode("\t",$interchangesrecords[0]);

$PartNumberFieldIndex=-1; $CompetitorPartNumberFieldIndex=-1; $ReferenceItemFieldIndex=-1; $InterchangeQuantityFieldIndex=-1; $UOMFieldIndex=-1; $InterchangeNotesFieldIndex=-1; $BrandAAIAIDFieldIndex=-1; $BrandLabelFieldIndex=-1; $SubBrandAAIAIDFieldIndex=-1; $SubBrandLabelFieldIndex=-1; $VMRSBrandIDFieldIndex=-1; $ItemEquivalentUOMFieldIndex=-1; $QualityGradeLevelFieldIndex=-1; $InternalNotesFieldIndex=-1; $LanguageCodeFieldIndex=-1;

for($i=0; $i<=count($headerfields)-1; $i++)
{ // identify the named columns' IDs
 if($headerfields[$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
 if($headerfields[$i]=='CompetitorPartNumber'){$CompetitorPartNumberFieldIndex=$i;}
 if($headerfields[$i]=='ReferenceItem'){$ReferenceItemFieldIndex=$i;}
 if($headerfields[$i]=='InterchangeQuantity'){$InterchangeQuantityFieldIndex=$i;}
 if($headerfields[$i]=='UOM'){$UOMFieldIndex=$i;}
 if($headerfields[$i]=='InterchangeNotes'){$InterchangeNotesFieldIndex=$i;}
 if($headerfields[$i]=='BrandAAIAID'){$BrandAAIAIDFieldIndex=$i;}
 if($headerfields[$i]=='BrandLabel'){$BrandLabelFieldIndex=$i;}
 if($headerfields[$i]=='SubBrandAAIAID'){$SubBrandAAIAIDFieldIndex=$i;}
 if($headerfields[$i]=='SubBrandLabel'){$SubBrandLabelFieldIndex=$i;}
 if($headerfields[$i]=='VMRSBrandID'){$VMRSBrandIDFieldIndex=$i;}
 if($headerfields[$i]=='ItemEquivalentUOM'){$ItemEquivalentUOMFieldIndex=$i;}
 if($headerfields[$i]=='QualityGradeLevel'){$QualityGradeLevelFieldIndex=$i;}
 if($headerfields[$i]=='InternalNotes'){$InternalNotesFieldIndex=$i;}
 if($headerfields[$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
} 
  
$recordnumber=0;
if($PartNumberFieldIndex==0)
{
 foreach($interchangesrecords as $record)
 {
  $fields = explode("\t",$record);
  if(count($fields)==1){continue;} // empty row
  if($recordnumber==0){$recordnumber++;continue;}
  $interchange=array();
  $PartNumber=trim($fields[0]);

  if($CompetitorPartNumberFieldIndex>=0 && trim($fields[$CompetitorPartNumberFieldIndex])!=''){$interchange['CompetitorPartNumber']=trim($fields[$CompetitorPartNumberFieldIndex]);}
  if($ReferenceItemFieldIndex>=0 && trim($fields[$ReferenceItemFieldIndex])!=''){$interchange['ReferenceItem']=trim($fields[$ReferenceItemFieldIndex]);}
  if($InterchangeQuantityFieldIndex>=0 && trim($fields[$InterchangeQuantityFieldIndex])!=''){$interchange['InterchangeQuantity']=trim($fields[$InterchangeQuantityFieldIndex]);}
  if($UOMFieldIndex>=0 && trim($fields[$UOMFieldIndex])!=''){$interchange['UOM']=trim($fields[$UOMFieldIndex]);}
  if($InterchangeNotesFieldIndex>=0 && trim($fields[$InterchangeNotesFieldIndex])!=''){$interchange['InterchangeNotes']=trim($fields[$InterchangeNotesFieldIndex]);}
  if($BrandAAIAIDFieldIndex>=0 && trim($fields[$BrandAAIAIDFieldIndex])!=''){$interchange['BrandAAIAID']=trim($fields[$BrandAAIAIDFieldIndex]);}
  if($BrandLabelFieldIndex>=0 && trim($fields[$BrandLabelFieldIndex])!=''){$interchange['BrandLabel']=trim($fields[$BrandLabelFieldIndex]);}
  if($SubBrandAAIAIDFieldIndex>=0 && trim($fields[$SubBrandAAIAIDFieldIndex])!=''){$interchange['SubBrandAAIAID']=trim($fields[$SubBrandAAIAIDFieldIndex]);}
  if($SubBrandLabelFieldIndex>=0 && trim($fields[$SubBrandLabelFieldIndex])!=''){$interchange['SubBrandLabel']=trim($fields[$SubBrandLabelFieldIndex]);}
  if($VMRSBrandIDFieldIndex>=0 && trim($fields[$VMRSBrandIDFieldIndex])!=''){$interchange['VMRSBrandID']=trim($fields[$VMRSBrandIDFieldIndex]);}
  if($ItemEquivalentUOMFieldIndex>=0 && trim($fields[$ItemEquivalentUOMFieldIndex])!=''){$interchange['ItemEquivalentUOM']=trim($fields[$ItemEquivalentUOMFieldIndex]);}
  if($QualityGradeLevelFieldIndex>=0 && trim($fields[$QualityGradeLevelFieldIndex])!=''){$interchange['QualityGradeLevel']=trim($fields[$QualityGradeLevelFieldIndex]);}
  if($InternalNotesFieldIndex>=0 && trim($fields[$InternalNotesFieldIndex])!=''){$interchange['InternalNotes']=trim($fields[$InternalNotesFieldIndex]);}
  if($LanguageCodeFieldIndex>=0 && trim($fields[$LanguageCodeFieldIndex])!=''){$interchange['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);}
  
  // see if this partnumber was established in the Items list
  if(array_key_exists($PartNumber,$items))
  {
   $items[$PartNumber]['interchanges'][]=$interchange;
  }
  else
  {
   $parseerrors[]='Interchanges contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
  }
  $recordnumber++;
 }
}

 
 // --------------------- Assets -----------------------
 
$assetsrecords = explode("\r\n", $_POST['assets']);
$headerfields=explode("\t",$assetsrecords[0]);

$PartNumberFieldIndex=-1; $FileNameFieldIndex=-1; $AssetIDFieldIndex=-1; $AssetTypeFieldIndex=-1; $FileTypeFieldIndex=-1; $RepresentationFieldIndex=-1; $FileSizeFieldIndex=-1; $ResolutionFieldIndex=-1; $ColorModeFieldIndex=-1; $BackgroundFieldIndex=-1; $OrientationViewFieldIndex=-1; $AssetHeightFieldIndex=-1; $AssetWidthFieldIndex=-1; $UOMFieldIndex=-1; $FilePathFieldIndex=-1; $URIFieldIndex=-1; $DurationFieldIndex=-1; $DurationUOMFieldIndex=-1; $FrameFieldIndex=-1; $TotalFramesFieldIndex=-1; $PlaneFieldIndex=-1; $HemisphereFieldIndex=-1; $PlungeFieldIndex=-1; $TotalPlanesFieldIndex=-1; $DescriptionFieldIndex=-1; $DescriptionCodeFieldIndex=-1; $DescriptionLanguageCodeFieldIndex=-1; $AssetDateFieldIndex=-1; $AssetDateTypeFieldIndex=-1; $CountryFieldIndex=-1; $LanguageCodeFieldIndex=-1;

for($i=0; $i<=count($headerfields)-1; $i++)
{ // identify the named columns' IDs
 if($headerfields[$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
 if($headerfields[$i]=='FileName'){$FileNameFieldIndex=$i;}
 if($headerfields[$i]=='AssetID'){$AssetIDFieldIndex=$i;}
 if($headerfields[$i]=='AssetType'){$AssetTypeFieldIndex=$i;}
 if($headerfields[$i]=='FileType'){$FileTypeFieldIndex=$i;}
 if($headerfields[$i]=='Representation'){$RepresentationFieldIndex=$i;}
 if($headerfields[$i]=='FileSize'){$FileSizeFieldIndex=$i;}
 if($headerfields[$i]=='Resolution'){$ResolutionFieldIndex=$i;}
 if($headerfields[$i]=='ColorMode'){$ColorModeFieldIndex=$i;}
 if($headerfields[$i]=='Background'){$BackgroundFieldIndex=$i;}
 if($headerfields[$i]=='OrientationView'){$OrientationViewFieldIndex=$i;}
 if($headerfields[$i]=='AssetHeight'){$AssetHeightFieldIndex=$i;}
 if($headerfields[$i]=='AssetWidth'){$AssetWidthFieldIndex=$i;}
 if($headerfields[$i]=='UOM'){$UOMFieldIndex=$i;}
 if($headerfields[$i]=='FilePath'){$FilePathFieldIndex=$i;}
 if($headerfields[$i]=='URI'){$URIFieldIndex=$i;}
 if($headerfields[$i]=='Duration'){$DurationFieldIndex=$i;}
 if($headerfields[$i]=='DurationUOM'){$DurationUOMFieldIndex=$i;}
 if($headerfields[$i]=='Frame'){$FrameFieldIndex=$i;}
 if($headerfields[$i]=='TotalFrames'){$TotalFramesFieldIndex=$i;}
 if($headerfields[$i]=='Plane'){$PlaneFieldIndex=$i;}
 if($headerfields[$i]=='Hemisphere'){$HemisphereFieldIndex=$i;}
 if($headerfields[$i]=='Plunge'){$PlungeFieldIndex=$i;}
 if($headerfields[$i]=='TotalPlanes'){$TotalPlanesFieldIndex=$i;}
 if($headerfields[$i]=='Description'){$DescriptionFieldIndex=$i;}
 if($headerfields[$i]=='DescriptionCode'){$DescriptionCodeFieldIndex=$i;}
 if($headerfields[$i]=='DescriptionLanguageCode'){$DescriptionLanguageCodeFieldIndex=$i;}
 if($headerfields[$i]=='AssetDate'){$AssetDateFieldIndex=$i;}
 if($headerfields[$i]=='AssetDateType'){$AssetDateTypeFieldIndex=$i;}
 if($headerfields[$i]=='Country'){$CountryFieldIndex=$i;}
 if($headerfields[$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
} 
  
$recordnumber=0;
if($PartNumberFieldIndex==0)
{
 foreach($assetsrecords as $record)
 {
  $fields = explode("\t",$record);
  if(count($fields)==1){continue;} // empty row
  if($recordnumber==0){$recordnumber++;continue;}
  $asset=array();
  $PartNumber=trim($fields[0]);

  if($FileNameFieldIndex>=0 && trim($fields[$FileNameFieldIndex])!=''){$asset['FileName']=trim($fields[$FileNameFieldIndex]);}
  if($AssetIDFieldIndex>=0 && trim($fields[$AssetIDFieldIndex])!=''){$asset['AssetID']=trim($fields[$AssetIDFieldIndex]);}
  if($AssetTypeFieldIndex>=0 && trim($fields[$AssetTypeFieldIndex])!=''){$asset['AssetType']=trim($fields[$AssetTypeFieldIndex]);}
  if($FileTypeFieldIndex>=0 && trim($fields[$FileTypeFieldIndex])!=''){$asset['FileType']=trim($fields[$FileTypeFieldIndex]);}
  if($RepresentationFieldIndex>=0 && trim($fields[$RepresentationFieldIndex])!=''){$asset['Representation']=trim($fields[$RepresentationFieldIndex]);}
  if($FileSizeFieldIndex>=0 && trim($fields[$FileSizeFieldIndex])!=''){$asset['FileSize']=trim($fields[$FileSizeFieldIndex]);}
  if($ResolutionFieldIndex>=0 && trim($fields[$ResolutionFieldIndex])!=''){$asset['Resolution']=trim($fields[$ResolutionFieldIndex]);}
  if($ColorModeFieldIndex>=0 && trim($fields[$ColorModeFieldIndex])!=''){$asset['ColorMode']=trim($fields[$ColorModeFieldIndex]);}
  if($BackgroundFieldIndex>=0 && trim($fields[$BackgroundFieldIndex])!=''){$asset['Background']=trim($fields[$BackgroundFieldIndex]);}
  if($OrientationViewFieldIndex>=0 && trim($fields[$OrientationViewFieldIndex])!=''){$asset['OrientationView']=trim($fields[$OrientationViewFieldIndex]);}
  if($AssetHeightFieldIndex>=0 && trim($fields[$AssetHeightFieldIndex])!=''){$asset['AssetHeight']=trim($fields[$AssetHeightFieldIndex]);}
  if($AssetWidthFieldIndex>=0 && trim($fields[$AssetWidthFieldIndex])!=''){$asset['AssetWidth']=trim($fields[$AssetWidthFieldIndex]);}
  if($UOMFieldIndex>=0 && trim($fields[$UOMFieldIndex])!=''){$asset['UOM']=trim($fields[$UOMFieldIndex]);}
  if($FilePathFieldIndex>=0 && trim($fields[$FilePathFieldIndex])!=''){$asset['FilePath']=trim($fields[$FilePathFieldIndex]);}
  if($URIFieldIndex>=0 && trim($fields[$URIFieldIndex])!=''){$asset['URI']=trim($fields[$URIFieldIndex]);}
  if($DurationFieldIndex>=0 && trim($fields[$DurationFieldIndex])!=''){$asset['Duration']=trim($fields[$DurationFieldIndex]);}
  if($DurationUOMFieldIndex>=0 && trim($fields[$DurationUOMFieldIndex])!=''){$asset['DurationUOM']=trim($fields[$DurationUOMFieldIndex]);}
  if($FrameFieldIndex>=0 && trim($fields[$FrameFieldIndex])!=''){$asset['Frame']=trim($fields[$FrameFieldIndex]);}
  if($TotalFramesFieldIndex>=0 && trim($fields[$TotalFramesFieldIndex])!=''){$asset['TotalFrames']=trim($fields[$TotalFramesFieldIndex]);}
  if($PlaneFieldIndex>=0 && trim($fields[$PlaneFieldIndex])!=''){$asset['Plane']=trim($fields[$PlaneFieldIndex]);}
  if($HemisphereFieldIndex>=0 && trim($fields[$HemisphereFieldIndex])!=''){$asset['Hemisphere']=trim($fields[$HemisphereFieldIndex]);}
  if($PlungeFieldIndex>=0 && trim($fields[$PlungeFieldIndex])!=''){$asset['Plunge']=trim($fields[$PlungeFieldIndex]);}
  if($TotalPlanesFieldIndex>=0 && trim($fields[$TotalPlanesFieldIndex])!=''){$asset['TotalPlanes']=trim($fields[$TotalPlanesFieldIndex]);}
  if($DescriptionFieldIndex>=0 && trim($fields[$DescriptionFieldIndex])!=''){$asset['Description']=trim($fields[$DescriptionFieldIndex]);}
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
   $parseerrors[]='Assets contains a partnumber ('.$PartNumber.') that is not found in the main Items list';  
  }
  $recordnumber++;
 }
}
 
 //-----------------------------------------------------
$doimport=false; if(isset($_POST['doimport'])){$doimport=true;}
$createparts=true; if($_POST['partcategory']==''){$createparts=false;}
$importresults=$PIESgenerator->importPIESdata($_SESSION['userid'],$items,$createparts,intval($_POST['partcategory']),$doimport);
?>
<!DOCTYPE html>
<html>
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
                        <h3 class="card-header text-start">Import part data from spreadsheet template</h3>

                        <div class="card-body">
                            <h5 class="alert alert-secondary">Step 2: Results</h5>
                            <?php if(count($parseerrors)>0){?>
                            <div class="alert alert-danger" role="alert">Logic Problems</div>
                            <table class="table"><?php
                            foreach($parseerrors as $error)
                            {
                                echo '<tr><td style="text-align:left;">'.$error.'</td></tr>';
                            }
                            ?>
                            </table>
                            <?php }?>

                            <?php if(count($importresults)>0){?>
                            <div class="alert alert-success">Actions</div>
                            <table class="table"><?php
                            foreach($importresults as $importresult)
                            {
                                echo '<tr><td style="text-align:left;">'.$importresult.'</td></tr>';
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
<?php include('./includes/footer.php'); ?>
    </body>
</html>