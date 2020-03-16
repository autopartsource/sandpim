<?php
include_once("mysqlClass.php");

class PIESgenerator
{
    
 function createPIESdoc($header,$marketingcopys,$items)//,$descriptions,$prices,$expi,$attributes,$packages,$kits,$interchanges,$assets)
 {
  $doc = new DOMDocument('1.0', 'UTF-8');
  $root = $doc->createElementNS('http://www.autocare.org', 'PIES');
  $root = $doc->appendChild($root);
  
  $TestFileElement=new DOMElement('TestFile','false');
  $root->appendChild($TestFileElement);
  
  // ---------------------- header ------------------------------
  $headerElement =  new DOMElement('Header');
  $root->appendChild($headerElement);
  $PIESVersionElement=new DOMElement('PIESVersion','7.1');
  $headerElement->appendChild($PIESVersionElement);

  $SubmissionTypeElement=new DOMElement('SubmissionType','FULL');
  $headerElement->appendChild($SubmissionTypeElement);

    
  if(array_key_exists('BlanketEffectiveDate',$header)){$BlanketEffectiveDateElement=$doc->createElement('BlanketEffectiveDate',$header['BlanketEffectiveDate']); $headerElement->appendChild($BlanketEffectiveDateElement);  }
  if(array_key_exists('ChangesSinceDate',$header)){$ChangesSinceDateElement=$doc->createElement('ChangesSinceDate',$header['ChangesSinceDate']); $headerElement->appendChild($ChangesSinceDateElement);}
  if(array_key_exists('ParentDUNSNumber',$header)){$ParentDUNSNumberElement=new DOMElement('ParentDUNSNumber',$header['ParentDUNSNumber']);  $headerElement->appendChild($ParentDUNSNumberElement);}
  if(array_key_exists('ParentGLN',$header)){$ParentGLNelement=new DOMElement('ParentGLN',$header['ParentGLN']);  $headerElement->appendChild($ParentGLNelement);}
  if(array_key_exists('ParentVMRSID',$header)){$ParentVMRSIDelement=new DOMElement('ParentVMRSID',$header['ParentVMRSID']);  $headerElement->appendChild($ParentVMRSIDelement);}
  if(array_key_exists('ParentAAIAID',$header)){$ParentAAIAIDelement=new DOMElement('ParentAAIAID',$header['ParentAAIAID']);  $headerElement->appendChild($ParentAAIAIDelement);}
  if(array_key_exists('BrandOwnerDUNS',$header)){$BrandOwnerDUNSelement=new DOMElement('BrandOwnerDUNS',$header['BrandOwnerDUNS']);  $headerElement->appendChild($BrandOwnerDUNSelement);}
  if(array_key_exists('BrandOwnerGLN',$header)){$BrandOwnerGLNelement=new DOMElement('BrandOwnerGLN',$header['BrandOwnerGLN']);  $headerElement->appendChild($BrandOwnerGLNelement);}
  if(array_key_exists('BrandOwnerVMRSID', $header)){$BrandOwnerVMRSIDelement=new DOMElement('BrandOwnerVMRSID',$header['BrandOwnerVMRSID']);  $headerElement->appendChild($BrandOwnerVMRSIDelement);}
  if(array_key_exists('BrandOwnerAAIAID', $header)){$BrandOwnerAAIAIDelement=new DOMElement('BrandOwnerAAIAID',$header['BrandOwnerAAIAID']); $headerElement->appendChild($BrandOwnerAAIAIDelement);}   
  if(array_key_exists('BuyerDuns',$header)){$BuyerDunsElement=new DOMElement('BuyerDuns',$header['BuyerDuns']); $headerElement->appendChild($BuyerDunsElement);}   
  if(array_key_exists('CurrencyCode',$header)){$CurrencyCodeElement=new DOMElement('CurrencyCode',$header['CurrencyCode']); $headerElement->appendChild($CurrencyCodeElement);}
  if(array_key_exists('LanguageCode',$header)){$LanguageCodeElement=new DOMElement('LanguageCode',$header['LanguageCode']); $headerElement->appendChild($LanguageCodeElement);}
  if(array_key_exists('TechnicalContact',$header)){$TechnicalContactElement=new DOMElement('TechnicalContact',$header['TechnicalContact']); $headerElement->appendChild($TechnicalContactElement);}
  if(array_key_exists('ContactEmail', $header)){$ContactEmailElement = new DOMElement('ContactEmail',$header['ContactEmail']); $headerElement->appendChild($ContactEmailElement);}
  if(array_key_exists('PCdbVersionDate',$header)){$PCdbVersionDateElement=new DOMElement('PCdbVersionDate',$header['PCdbVersionDate']);  $headerElement->appendChild($PCdbVersionDateElement);}
  if(array_key_exists('PAdbVersionDate',$header)){$PAdbVersionDateElement=new DOMElement('PAdbVersionDate',$header['PAdbVersionDate']);  $headerElement->appendChild($PAdbVersionDateElement);}

  //----------------------- price sheets ---------------
  
   if(count($item['prices']))
   {
    $PricesElement = $doc->createElement('Prices');
    
    $pricesheetnumber=''; $currencycode=''; $effectivedate=''; $expirationdate='';
    
    
    foreach($item['prices'] as $price)
    {
       // if($price[''])
        
    }
    
    $PriceSheetsElement=$doc->createElement('PriceSheets');
    $PriceSheetElement=$doc->createElement('PriceSheet');
    $PriceSheetsElement->appendChild($PriceSheetElement);
    $PriceSheetElement->setAttribute('MaintenanceType', 'A');
    $PriceSheetNumberElement=$doc->createElement('PriceSheetNumber',$pricesheetnumber); $PriceSheetElement->appendChild($PriceSheetNumberElement);
    $CurrencyCodeElement=$doc->createElement('CurrencyCode',$currencycode); $PriceSheetElement->appendChild($CurrencyCodeElement);
    $EffectiveDateElement=$doc->createElement('EffectiveDate',$effectivedate); $PriceSheetElement->appendChild($EffectiveDateElement);
    $ExpirationDateElement=$doc->createElement('ExpirationDate',$expirationdate); $PriceSheetElement->appendChild($ExpirationDateElement);
    $root->appendChild($PriceSheetsElement);
   }
  
  //----------------------- marketing copy -----------------------
  if(count($marketingcopys))
  {      
   $MarketingCopyElement=$doc->createElement('MarketingCopy');
   foreach($marketingcopys as $marketcopy)
   {
    $MarketCopyElement=$doc->createElement('MarketCopy');
    $MarketCopyContentElement=$doc->createElement('MarketCopyContent',$marketcopy['MarketCopyContent']);
    $MarketCopyContentElement->setAttribute('MaintenanceType', 'A');
    if(array_key_exists('MarketCopyCode',$marketcopy)){$MarketCopyContentElement->setAttribute('MarketCopyCode', $marketcopy['MarketCopyCode']);}
    if(array_key_exists('MarketCopyReference',$marketcopy)){$MarketCopyContentElement->setAttribute('MarketCopyReference', $marketcopy['MarketCopyReference']);}
    if(array_key_exists('MarketCopySubCode',$marketcopy)){$MarketCopyContentElement->setAttribute('MarketCopySubCode', $marketcopy['MarketCopySubCode']);}
    if(array_key_exists('MarketCopySubCodeReference',$marketcopy)){$MarketCopyContentElement->setAttribute('MarketCopySubCodeReference', $marketcopy['MarketCopySubCodeReference']);}
    if(array_key_exists('MarketCopyType',$marketcopy)){$MarketCopyContentElement->setAttribute('MarketCopyType', $marketcopy['MarketCopyType']);}
    if(array_key_exists('RecordSequence',$marketcopy)){$MarketCopyContentElement->setAttribute('RecordSequence', $marketcopy['RecordSequence']);}
    if(array_key_exists('LanguageCode',$marketcopy)){$MarketCopyContentElement->setAttribute('LanguageCode', $marketcopy['LanguageCode']);}
    $MarketCopyElement->appendChild($MarketCopyContentElement);
    $MarketingCopyElement->appendChild($MarketCopyElement);
   }
   $root->appendChild($MarketingCopyElement);
  }
 
  // --------------------- items ---------------------------------   
  $ItemsElement=new DOMElement('Items');
  $root->appendChild($ItemsElement);
  foreach($items as $PartNumber=>$item)
  {
   $ItemElement=new DOMElement('Item');
   $ItemsElement->appendChild($ItemElement);

   $ItemElement->setAttribute('MaintenanceType', 'A');
 
   if(array_key_exists('HazardousMaterialCode', $item)){$HazardousMaterialCodeElement= $doc->createElement('HazardousMaterialCode',$item['HazardousMaterialCode']); $ItemElement->appendChild($HazardousMaterialCodeElement);}
   if(array_key_exists('BaseItemID', $item)){$BaseItemIDelement= $doc->createElement('BaseItemID',$item['BaseItemID']); $ItemElement->appendChild($BaseItemIDelement);}
   if(array_key_exists('ItemLevelGTIN',$item)){$ItemLevelGTINelement= $doc->createElement('ItemLevelGTIN',$item['ItemLevelGTIN']); $ItemElement->appendChild($ItemLevelGTINelement); $ItemLevelGTINelement->setAttribute('GTINQualifier', $item['GTINQualifier']);}
 
   $PartNumberElement= $doc->createElement('PartNumber',$PartNumber);
   $ItemElement->appendChild($PartNumberElement);

   $BrandAAIAIDelement= $doc->createElement('BrandAAIAID',$item['BrandAAIAID']);
   $ItemElement->appendChild($BrandAAIAIDelement);

   if(array_key_exists('ACESApplications', $item)){$ACESApplicationsElement= $doc->createElement('ACESApplications',$item['ACESApplications']); $ItemElement->appendChild($ACESApplicationsElement);}
   if(array_key_exists('ItemQuantitySize', $item) && array_key_exists('ItemQuantitySizeUOM', $item)){$ItemQuantitySizeElement= $doc->createElement('ItemQuantitySize',$item['ItemQuantitySize']); $ItemQuantitySizeElement->setAttribute('UOM', $item['ItemQuantitySizeUOM']); $ItemElement->appendChild($ItemQuantitySizeElement);}
   if(array_key_exists('ContainerType', $item)){$ContainerTypeElement= $doc->createElement('ContainerType',$item['ContainerType']); $ItemElement->appendChild($ContainerTypeElement);}
   if(array_key_exists('ItemEffectiveDate', $item)){$ItemEffectiveDateElement= $doc->createElement('ItemEffectiveDate',$item['ItemEffectiveDate']); $ItemElement->appendChild($ItemEffectiveDateElement);}
   if(array_key_exists('AvailableDate', $item)){$AvailableDateElement= $doc->createElement('AvailableDate',$item['AvailableDate']); $ItemElement->appendChild($AvailableDateElement);}
   if(array_key_exists('MinimumOrderQuantity', $item) && array_key_exists('MinimumOrderQuantityUOM', $item)){$MinimumOrderQuantityElement= $doc->createElement('MinimumOrderQuantity',$item['MinimumOrderQuantity']); $ItemElement->appendChild($MinimumOrderQuantityElement);  $MinimumOrderQuantityElement->setAttribute('UOM', $item['MinimumOrderQuantityUOM']);  }
   if(array_key_exists('UNSPSC', $item)){$UNSPSCElement= $doc->createElement('UNSPSC',$item['UNSPSC']); $ItemElement->appendChild($UNSPSCElement); }

   $PartTerminologyIDelement= $doc->createElement('PartTerminologyID',$item['PartTerminologyID']);
   $ItemElement->appendChild($PartTerminologyIDelement);

   if(count($item['descriptions']))
   {
    $DescriptionsElement = $doc->createElement('Descriptions');
    foreach($item['descriptions'] as $description)
    {
     $DescriptionElement=$doc->createElement('Description', $description['Description']);
     $DescriptionElement->setAttribute('MaintenanceType','A');
     if(array_key_exists('DescriptionCode',$description)){$DescriptionElement->setAttribute('DescriptionCode',$description['DescriptionCode']);}
     if(array_key_exists('LanguageCode',$description)){$DescriptionElement->setAttribute('LanguageCode',$description['LanguageCode']);}
     if(array_key_exists('Sequence',$description)){$DescriptionElement->setAttribute('Sequence',$description['Sequence']);}
     $DescriptionsElement->appendChild($DescriptionElement);
    }
    $ItemElement->appendChild($DescriptionsElement);
   }
   //---------------------- prices ---------------------------------
   
   if(count($item['prices']))
   {
    $PricesElement = $doc->createElement('Prices');
    foreach($item['prices'] as $price)
    {
     $PricingElement=$doc->createElement('Pricing');
     $PricingElement->setAttribute('MaintenanceType','A');
     $PricingElement->setAttribute('PriceType',$price['PriceType']);
     if(array_key_exists('PriceSheetNumber',$price)){$PriceSheetNumberElement=$doc->createElement('PriceSheetNumber',$price['PriceSheetNumber']); $PricingElement->appendChild($PriceSheetNumberElement);}
     if(array_key_exists('CurrencyCode',$price)){$CurrencyCodeElement=$doc->createElement('CurrencyCode',$price['CurrencyCode']); $PricingElement->appendChild($CurrencyCodeElement);}
     if(array_key_exists('EffectiveDate',$price)){$EffectiveDateElement=$doc->createElement('EffectiveDate',$price['EffectiveDate']); $PricingElement->appendChild($EffectiveDateElement);}
     if(array_key_exists('ExpirationDate',$price)){$ExpirationDateElement=$doc->createElement('ExpirationDate',$price['ExpirationDate']); $PricingElement->appendChild($ExpirationDateElement);}
     if(array_key_exists('Price',$price) && array_key_exists('PriceUOM',$price)){$PriceElement=$doc->createElement('Price',$price['Price']); $PriceElement->setAttribute('UOM', $price['PriceUOM']); $PricingElement->appendChild($PriceElement);}
     if(array_key_exists('PriceTypeDescription',$price)){$PriceTypeDescriptionElement=$doc->createElement('PriceTypeDescription',$price['PriceTypeDescription']); $PricingElement->appendChild($PriceTypeDescriptionElement);}
     if(array_key_exists('PriceBreak',$price) && array_key_exists('PriceBreakUOM',$price)){$PriceBreakElement=$doc->createElement('PriceBreak',$price['PriceBreak']); $PriceBreakElement->setAttribute('UOM', $price['PriceBreakUOM']); $PricingElement->appendChild($PriceBreakElement);}
     if(array_key_exists('PriceMultiplier',$price)){$PriceMultiplierElement=$doc->createElement('PriceMultiplier',$price['PriceMultiplier']); $PricingElement->appendChild($PriceMultiplierElement);}
     $PricesElement->appendChild($PricingElement);
    }
    $ItemElement->appendChild($PricesElement);
   }
   
   
   //----------------------- attributes ----------------------------
   if(count($item['attributes']))
   {
    $ProductAttributesElement = $doc->createElement('ProductAttributes');

    foreach($item['attributes'] as $attribute)
    {
     $ProductAttributeElement=$doc->createElement('ProductAttribute', $attribute['AttributeValue']);
     $ProductAttributeElement->setAttribute('MaintenanceType','A');
     if(intval($attribute['AttributeID'])>0){$ProductAttributeElement->setAttribute('PADBAttribute','Y');}else{$ProductAttributeElement->setAttribute('PADBAttribute','N');}
     $ProductAttributeElement->setAttribute('AttributeID',$attribute['AttributeID']);
     if(array_key_exists('AttributeUOM',$attribute)){$ProductAttributeElement->setAttribute('AttributeUOM',$attribute['AttributeUOM']);}
     if(array_key_exists('StyleID',$attribute)){$ProductAttributeElement->setAttribute('StyleID',$attribute['StyleID']);}
     if(array_key_exists('RecordNumber',$attribute)){$ProductAttributeElement->setAttribute('RecordNumber',$attribute['RecordNumber']);}
     if(array_key_exists('LanguageCode',$attribute)){$ProductAttributeElement->setAttribute('LanguageCode',$attribute['LanguageCode']);}
     $ProductAttributesElement->appendChild($ProductAttributeElement);
    }
    $ItemElement->appendChild($ProductAttributesElement);
   }
 
   if(count($item['packages']))
   {       
    $PackagesElement=$doc->createElement('Packages');
    foreach($item['packages'] as $package)
    {
     $PackageElement=$doc->createElement('Package');
     $PackageElement->setAttribute('MaintenanceType','A');

     if(array_key_exists('PackageLevelGTIN',$package)){$PackageLevelGTINelement=$doc->createElement('PackageLevelGTIN',$package['PackageLevelGTIN']); $PackageElement->appendChild($PackageLevelGTINelement);}
     if(array_key_exists('ElectronicProductCode',$package)){$ElectronicProductCodeElement=$doc->createElement('ElectronicProductCode',$package['ElectronicProductCode']); $PackageElement->appendChild($ElectronicProductCodeElement);}
     if(array_key_exists('PackageBarCodeCharacters',$package)){$PackageBarCodeCharactersElement=$doc->createElement('PackageBarCodeCharacters',$package['PackageBarCodeCharacters']); $PackageElement->appendChild($PackageBarCodeCharactersElement);}
     if(array_key_exists('PackageUOM',$package)){$PackageUOMelement=$doc->createElement('PackageUOM',$package['PackageUOM']); $PackageElement->appendChild($PackageUOMelement);}
     if(array_key_exists('QuantityofEaches',$package)){$QuantityofEachesElement=$doc->createElement('QuantityofEaches',$package['QuantityofEaches']); $PackageElement->appendChild($QuantityofEachesElement);}

     if(array_key_exists('InnerQuantity',$package)){$InnerQuantityElement=$doc->createElement('InnerQuantity',$package['InnerQuantity']); $PackageElement->appendChild($InnerQuantityElement);}
     if(array_key_exists('InnerQuantityUOM',$package)){$InnerQuantityElement->setAttribute('InnerQuantityUOM',$package['InnerQuantityUOM']);}

     $DimensionsElement=$doc->createElement('Dimensions');
     if(array_key_exists('DimensionsUOM',$package)){$DimensionsElement->setAttribute('UOM', $package['DimensionsUOM']);}

     
     if(array_key_exists('MerchandisingHeight',$package)){$MerchandisingHeightElement=$doc->createElement('MerchandisingHeight',$package['MerchandisingHeight']); $DimensionsElement->appendChild($MerchandisingHeightElement);}
     if(array_key_exists('MerchandisingWidth',$package)){$MerchandisingWidthElement=$doc->createElement('MerchandisingWidth',$package['MerchandisingWidth']); $DimensionsElement->appendChild($MerchandisingWidthElement);}
     if(array_key_exists('MerchandisingLength',$package)){$MerchandisingLengthElement=$doc->createElement('MerchandisingLength',$package['MerchandisingLength']); $DimensionsElement->appendChild($MerchandisingLengthElement);}
     
     if(array_key_exists('ShippingHeight',$package)){$ShippingHeightElement=$doc->createElement('ShippingHeight',$package['ShippingHeight']); $DimensionsElement->appendChild($ShippingHeightElement);}
     if(array_key_exists('ShippingWidth',$package)){$ShippingWidthElement=$doc->createElement('ShippingWidth',$package['ShippingWidth']); $DimensionsElement->appendChild($ShippingWidthElement);}
     if(array_key_exists('ShippingLength',$package)){$ShippingLengthElement=$doc->createElement('ShippingLength',$package['ShippingLength']); $DimensionsElement->appendChild($ShippingLengthElement); $PackageElement->appendChild($DimensionsElement);}

     if(array_key_exists('Weight',$package) && array_key_exists('WeightsUOM',$package))
     {
      $WeightsElement=$doc->createElement('Weights'); 
      if(array_key_exists('WeightsUOM',$package)){$WeightsElement->setAttribute('UOM',$package['WeightsUOM']);}
      if(array_key_exists('Weight',$package)){$WeightElement=$doc->createElement('Weight',$package['Weight']); $WeightsElement->appendChild($WeightElement);}
      if(array_key_exists('DimensionalWeight',$package)){$DimensionalWeightElement=$doc->createElement('DimensionalWeight',$package['DimensionalWeight']); $WeightsElement->appendChild($DimensionalWeightElement);}
      $PackageElement->appendChild($WeightsElement);
     }

     if(array_key_exists('WeightVariance',$package)){$WeightVarianceElement=$doc->createElement('WeightVariance',$package['WeightVariance']); $PackageElement->appendChild($WeightVarianceElement);}
     if(array_key_exists('StackingFactor',$package)){$StackingFactorElement=$doc->createElement('StackingFactor',$package['StackingFactor']); $PackageElement->appendChild($StackingFactorElement);}

     if(array_key_exists('ShippingScope',$package) || array_key_exists('Bulk',$package) || array_key_exists('RegulatingCountry',$package) || array_key_exists('TransportMethod',$package) || array_key_exists('Regulated',$package) || array_key_exists('Description',$package) || array_key_exists('HazardousMaterialCodeQualifier',$package) || array_key_exists('HazardousMaterialDescription',$package) || array_key_exists('HazardousMaterialLabelCode',$package) || array_key_exists('ShippingName',$package) || array_key_exists('UNNAIDCode',$package) || array_key_exists('HazardousPlacardNotation',$package) || array_key_exists('WHMISCode',$package) || array_key_exists('WHMISFreeText',$package) || array_key_exists('PackingGroupCode',$package) || array_key_exists('RegulationsExemptionCode',$package) || array_key_exists('TextMessage',$package))
     {
      $HazardousMaterialElement=$doc->createElement('HazardousMaterial');
      $HazardousMaterialElement->setAttribute('MaintenanceType', 'A');
      if(array_key_exists('LanguageCode',$package)){$HazardousMaterialElement->setAttribute('LanguageCode',$package['LanguageCode']);}
      if(array_key_exists('ShippingScope',$package)){$ShippingScopeElement=$doc->createElement('ShippingScope',$package['ShippingScope']); $HazardousMaterialElement->appendChild($ShippingScopeElement);}
      if(array_key_exists('Bulk',$package)){$BulkElement=$doc->createElement('Bulk',$package['Bulk']); $HazardousMaterialElement->appendChild($BulkElement);}
      if(array_key_exists('RegulatingCountry',$package)){$RegulatingCountryElement=$doc->createElement('RegulatingCountry',$package['RegulatingCountry']); $HazardousMaterialElement->appendChild($RegulatingCountryElement);}
      if(array_key_exists('TransportMethod',$package)){$TransportMethodElement=$doc->createElement('TransportMethod',$package['TransportMethod']); $HazardousMaterialElement->appendChild($TransportMethodElement);}
      if(array_key_exists('Regulated',$package)){$RegulatedElement=$doc->createElement('Regulated',$package['Regulated']); $HazardousMaterialElement->appendChild($RegulatedElement);}
      if(array_key_exists('Description',$package)){$DescriptionElement=$doc->createElement('Description',$package['Description']); $HazardousMaterialElement->appendChild($DescriptionElement);}
      if(array_key_exists('HazardousMaterialCodeQualifier',$package)){$HazardousMaterialCodeQualifierElement=$doc->createElement('HazardousMaterialCodeQualifier',$package['HazardousMaterialCodeQualifier']); $HazardousMaterialElement->appendChild($HazardousMaterialCodeQualifierElement);}
      if(array_key_exists('HazardousMaterialDescription',$package)){$HazardousMaterialDescriptionElement=$doc->createElement('HazardousMaterialDescription',$package['HazardousMaterialDescription']); $HazardousMaterialElement->appendChild($HazardousMaterialDescriptionElement);}
      if(array_key_exists('HazardousMaterialLabelCode',$package)){$HazardousMaterialLabelCodeElement=$doc->createElement('HazardousMaterialLabelCode',$package['HazardousMaterialLabelCode']); $HazardousMaterialElement->appendChild($HazardousMaterialLabelCodeElement);}
      if(array_key_exists('ShippingName',$package)){$ShippingNameElement=$doc->createElement('ShippingName',$package['ShippingName']); $HazardousMaterialElement->appendChild($ShippingNameElement);}
      if(array_key_exists('UNNAIDCode',$package)){$UNNAIDCodeElement=$doc->createElement('UNNAIDCode',$package['UNNAIDCode']); $HazardousMaterialElement->appendChild($UNNAIDCodeElement);}
      if(array_key_exists('HazardousPlacardNotation',$package)){$HazardousPlacardNotationElement=$doc->createElement('HazardousPlacardNotation',$package['HazardousPlacardNotation']); $HazardousMaterialElement->appendChild($HazardousPlacardNotationElement);}
      if(array_key_exists('WHMISCode',$package)){$WHMISCodeElement=$doc->createElement('WHMISCode',$package['WHMISCode']); $HazardousMaterialElement->appendChild($WHMISCodeElement);}
      if(array_key_exists('WHMISFreeText',$package)){$WHMISFreeTextElement=$doc->createElement('WHMISFreeText',$package['WHMISFreeText']); $HazardousMaterialElement->appendChild($WHMISFreeTextElement);}
      if(array_key_exists('PackingGroupCode',$package)){$PackingGroupCodeElement=$doc->createElement('PackingGroupCode',$package['PackingGroupCode']); $HazardousMaterialElement->appendChild($PackingGroupCodeElement);}
      if(array_key_exists('RegulationsExemptionCode',$package)){$RegulationsExemptionCodeElement=$doc->createElement('RegulationsExemptionCode',$package['RegulationsExemptionCode']); $HazardousMaterialElement->appendChild($RegulationsExemptionCodeElement);}
      if(array_key_exists('TextMessage',$package)){$TextMessageElement=$doc->createElement('TextMessage',$package['TextMessage']); $HazardousMaterialElement->appendChild($TextMessageElement);}
      if(array_key_exists('OuterPackageLabel',$package)){$OuterPackageLabelElement=$doc->createElement('OuterPackageLabel',$package['OuterPackageLabel']); $HazardousMaterialElement->appendChild($OuterPackageLabelElement);}
      $PackageElement->appendChild($HazardousMaterialElement); 
     }     
   
     $PackagesElement->appendChild($PackageElement); 
    }  
    $ItemElement->appendChild($PackagesElement);
   }
  
   
   
   
   
   
   if(count($item['interchanges']))
   {
    $PartInterchangeInfoElement=$doc->createElement('PartInterchangeInfo');
    foreach($item['interchanges'] as $interchange)
    {
     $PartInterchangeElement=$doc->createElement('PartInterchange');
     $PartInterchangeElement->setAttribute('MaintenanceType','A');
     $PartInterchangeElement->setAttribute('BrandAAIAID',$interchange['BrandAAIAID']);

     if(array_key_exists('BrandLabel',$interchange)){$PartInterchangeElement->setAttribute('BrandLabel',$interchange['BrandLabel']);}
     if(array_key_exists('SubBrandAAIAID',$interchange)){$PartInterchangeElement->setAttribute('SubBrandAAIAID',$interchange['SubBrandAAIAID']);}
     if(array_key_exists('SubBrandLabel',$interchange)){$PartInterchangeElement->setAttribute('SubBrandLabel',$interchange['SubBrandLabel']);}
     if(array_key_exists('VMRSBrandID',$interchange)){$PartInterchangeElement->setAttribute('VMRSBrandID',$interchange['VMRSBrandID']);}
     if(array_key_exists('ItemEquivalentUOM',$interchange)){$PartInterchangeElement->setAttribute('ItemEquivalentUOM',$interchange['ItemEquivalentUOM']);}
     if(array_key_exists('QualityGradeLevel',$interchange)){$PartInterchangeElement->setAttribute('QualityGradeLevel',$interchange['QualityGradeLevel']);}
     if(array_key_exists('InternalNotes',$interchange)){$PartInterchangeElement->setAttribute('InternalNotes',$interchange['InternalNotes']);}
     if(array_key_exists('LanguageCode',$interchange)){$PartInterchangeElement->setAttribute('LanguageCode',$interchange['LanguageCode']);}
     
     
     $InterchangePartNumberElement=$doc->createElement('PartNumber',$interchange['CompetitorPartNumber']);
     if(array_key_exists('ReferenceItem',$interchange)){$InterchangePartNumberElement->setAttribute('ReferenceItem', $interchange['ReferenceItem']);}
     if(array_key_exists('InterchangeQuantity',$interchange)){$InterchangePartNumberElement->setAttribute('InterchangeQuantity', $interchange['InterchangeQuantity']);}
     if(array_key_exists('UOM',$interchange)){$InterchangePartNumberElement->setAttribute('UOM', $interchange['UOM']);}
     if(array_key_exists('InterchangeNotes',$interchange)){$InterchangePartNumberElement->setAttribute('InterchangeNotes', $interchange['InterchangeNotes']);}
     
     $PartInterchangeElement->appendChild($InterchangePartNumberElement);
     $PartInterchangeInfoElement->appendChild($PartInterchangeElement);
     
    }  
    $ItemElement->appendChild($PartInterchangeInfoElement);
   }
   
   
   
   
   
   
   
   
 
   if(count($item['assets']))
   {
    $DigitalAssetsElement=$doc->createElement('DigitalAssets');
    foreach($item['assets'] as $asset)
    {
     $DigitalFileInformationElement=$doc->createElement('DigitalFileInformation');
     $DigitalFileInformationElement->setAttribute('MaintenanceType','A');
     if(array_key_exists('AssetID',$asset)){$DigitalFileInformationElement->setAttribute('AssetID',$asset['AssetID']);}
     if(array_key_exists('LanguageCode',$asset)){$DigitalFileInformationElement->setAttribute('LanguageCode',$asset['LanguageCode']);}
     if(array_key_exists('FileName',$asset)){$FileNameElement=$doc->createElement('FileName',$asset['FileName']); $DigitalFileInformationElement->appendChild($FileNameElement);}
     if(array_key_exists('AssetType',$asset)){$AssetTypeElement=$doc->createElement('AssetType',$asset['AssetType']); $DigitalFileInformationElement->appendChild($AssetTypeElement);}
     if(array_key_exists('FileType',$asset)){$FileTypeElement=$doc->createElement('FileType',$asset['FileType']); $DigitalFileInformationElement->appendChild($FileTypeElement);}
     if(array_key_exists('Representation',$asset)){$RepresentationElement=$doc->createElement('Representation',$asset['Representation']);$DigitalFileInformationElement->appendChild($RepresentationElement);}
     if(array_key_exists('FileSize',$asset)){$FileSizeElement=$doc->createElement('FileSize',$asset['FileSize']); $DigitalFileInformationElement->appendChild($FileSizeElement);}
     if(array_key_exists('Resolution',$asset)){$ResolutionElement=$doc->createElement('Resolution',$asset['Resolution']); $DigitalFileInformationElement->appendChild($ResolutionElement);}
     if(array_key_exists('ColorMode',$asset)){$ColorModeElement=$doc->createElement('ColorMode',$asset['ColorMode']); $DigitalFileInformationElement->appendChild($ColorModeElement);}
     if(array_key_exists('Background',$asset)){$BackgroundElement=$doc->createElement('Background',$asset['Background']); $DigitalFileInformationElement->appendChild($BackgroundElement);}
     if(array_key_exists('OrientationView',$asset)){$OrientationViewElement=$doc->createElement('OrientationView',$asset['OrientationView']); $DigitalFileInformationElement->appendChild($OrientationViewElement);}
     
     if(array_key_exists('AssetDimensionsUOM',$asset) && array_key_exists('AssetHeight',$asset) && array_key_exists('AssetWidth',$asset))
     {
      $AssetDimensionsElement=$doc->createElement('AssetDimensions'); 
      $AssetDimensionsElement->setAttribute('UOM', $asset['AssetDimensionsUOM']);
      $AssetHeightElement=$doc->createElement('AssetHeight',$asset['AssetHeight']);
      $AssetWidthElement=$doc->createElement('AssetWidth',$asset['AssetWidth']);
      $AssetDimensionsElement->appendChild($AssetHeightElement);
      $AssetDimensionsElement->appendChild($AssetWidthElement);
      $DigitalFileInformationElement->appendChild($AssetDimensionsElement);
     }

     if(array_key_exists('FilePath',$asset)){$FilePathElement=$doc->createElement('FilePath',$asset['FilePath']); $DigitalFileInformationElement->appendChild($FilePathElement);}
     if(array_key_exists('URI',$asset)){$URIelement=$doc->createElement('URI',$asset['URI']); $DigitalFileInformationElement->appendChild($URIelement);}
     if(array_key_exists('Duration',$asset) && array_key_exists('DurationUOM',$asset)){$DurationElement=$doc->createElement('Duration',$asset['Duration']); $DurationElement->setAttribute('UOM', $asset['DurationUOM']); $DigitalFileInformationElement->appendChild($DurationElement);}
     if(array_key_exists('Frame',$asset)){$FrameElement=$doc->createElement('Frame',$asset['Frame']); $DigitalFileInformationElement->appendChild($FrameElement);}
     if(array_key_exists('TotalFrames',$asset)){$TotalFramesElement=$doc->createElement('TotalFrames',$asset['TotalFrames']); $DigitalFileInformationElement->appendChild($TotalFramesElement);}
     if(array_key_exists('Plane',$asset)){$PlaneElement=$doc->createElement('Plane',$asset['Plane']); $DigitalFileInformationElement->appendChild($PlaneElement);}
     if(array_key_exists('Hemisphere',$asset)){$HemisphereElement=$doc->createElement('Hemisphere',$asset['Hemisphere']); $DigitalFileInformationElement->appendChild($HemisphereElement);}
     if(array_key_exists('Plunge',$asset)){$PlungeElement=$doc->createElement('Plunge',$asset['Plunge']); $DigitalFileInformationElement->appendChild($PlungeElement);}
     if(array_key_exists('TotalPlanes',$asset)){$TotalPlanesElement=$doc->createElement('TotalPlanes',$asset['TotalPlanes']); $DigitalFileInformationElement->appendChild($TotalPlanesElement);}

     if(array_key_exists('Description',$asset) && array_key_exists('DescriptionCode',$asset))
     {
      $AssetDescriptionsElement=$doc->createElement('AssetDescriptions');
      $DescriptionElement=$doc->createElement('Description',$asset['Description']);
      $DescriptionElement->setAttribute('MaintenanceType', 'A');
      $DescriptionElement->setAttribute('DescriptionCode', $asset['DescriptionCode']);
      if(array_key_exists('DescriptionLanguageCode',$asset)){$DescriptionElement->setAttribute('LanguageCode', $asset['DescriptionLanguageCode']);}
      $AssetDescriptionsElement->appendChild($DescriptionElement);   
      $DigitalFileInformationElement->appendChild($AssetDescriptionsElement);
     }
     
     if(array_key_exists('AssetDate',$asset) && array_key_exists('AssetDateType',$asset))
     {
      $AssetDatesElement=$doc->createElement('AssetDates');
      $AssetDateElement=$doc->createElement('AssetDate',$asset['AssetDate']);
      $AssetDateElement->setAttribute('assetDateType', $asset['AssetDateType']);
      $AssetDatesElement->appendChild($AssetDateElement); 
      $DigitalFileInformationElement->appendChild($AssetDatesElement);
     }
     
     if(array_key_exists('Country',$asset)){$CountryElement=$doc->createElement('Country',$asset['Country']); $DigitalFileInformationElement->appendChild($CountryElement);}
     
     $DigitalAssetsElement->appendChild($DigitalFileInformationElement);
    }  
    $ItemElement->appendChild($DigitalAssetsElement);
   }
  }
 
  $TrailerElement=new DOMElement('Trailer');
  $root->appendChild($TrailerElement); 

  $TransactionDateElement=new DOMElement('TransactionDate','2020-01-27');
  $ItemCountElement=new DOMElement('ItemCount',count($items));
  $TrailerElement->appendChild($ItemCountElement);
  $TrailerElement->appendChild($TransactionDateElement);
 
  return $doc;
 }

    
    
    
    
 function parttypeName($parttypeid)
 {
  $name='not found';
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select PartTerminologyName from Parts where PartTerminologyID=?'))
  {
   $stmt->bind_param('i', $parttypeid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $name=$row['PartTerminologyName'];
   }
  }
  $db->close();
  return $name;
 }

 
 function getLifeCycleCodes()
 {
  $codes=array();
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select CodeValue,CodeDescription from PIESReferenceFieldCode, PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and  PIESReferenceFieldCode.PIESFieldId=93 order by CodeDescription'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $codes[]=array('code'=>$row['CodeValue'],'description'=>$row['CodeDescription']);
   }
  }
  $db->close();
  return $codes;    
 }

 function lifeCycleCodeDescription($code)
 {
  if(trim($code)==''){return 'not set (blank)';}
  $description='not found';
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select CodeDescription from PIESReferenceFieldCode, PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESReferenceFieldCode.PIESFieldId=93 and CodeValue=?'))
  {
   $stmt->bind_param('s', $code);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $description=$row['CodeDescription'];
   }
  }
  $db->close();
  return $description;    
 }
 
}
?>
