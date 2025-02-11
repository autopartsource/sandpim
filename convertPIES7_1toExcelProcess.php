<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/XLSXWriterClass.php');
$navCategory = 'utilities';

$anonSizeLimit=15000000;
session_start();

$pim = new pim();
$logs=new logs();
$pcdb = new pcdb($_POST['pcdbversion']);
$pcdbVersion=$pcdb->version();

$validAssetTypes=array(); $assetTypeCodes=$pcdb->getAssetTypeCodes(); foreach($assetTypeCodes as $assetTypeCode){$validAssetTypes[$assetTypeCode['code']]=$assetTypeCode['description'];}
$validDescriptionCodes=array(); $descriptionCodes=$pcdb->getItemDescriptionCodes(); foreach($descriptionCodes as $descriptionCode){$validDescriptionCodes[$descriptionCode['code']]=$descriptionCode['description'];}
//$validEXPIcodes=$pcdb->getAllEXPIcodes();
//$validPartTypes=array(); $partTypes=$pcdb->getPartTypes('%'); foreach($partTypes as $partType){$validPartTypes[$partType['id']]=$partType['name'];}


$streamXLSX=false;
$xlsxdata='';

$originalFilename='';
$validUpload=false;
$schemaresults=array();
$inputFileLog=array();
$errors=array(); 

$gtinmasterlist=array();



if(isset($_POST['submit']) && $_POST['submit']=='Generate Excel file')
{
 if($_FILES['fileToUpload']['type']=='text/xml' || $_FILES['fileToUpload']['type']=='')
 {
  if($_FILES['fileToUpload']['size']<$anonSizeLimit || isset($_SESSION['userid']))   
  {     
   $originalFilename= basename($_FILES['fileToUpload']['name']);
   $doc = new DOMDocument('1.0', 'UTF-8');
   $doc->load($_FILES['fileToUpload']['tmp_name']);
   
   libxml_use_internal_errors(true);
   if(!$doc->schemaValidate('PIES_7_1_r4_XSD.xsd'))
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
   
   if(count($schemaresults)==0)
   {
       $validUpload=true;
   }
   else
   {
    $inputFileLog[]='schema validation failed.'.implode('. ',$schemaresults);
    $logs->logSystemEvent('rhubarb', 0, 'schema validation failed.'.implode('. ',$schemaresults));
   }
  }
  else
  {
   $inputFileLog[]='Input file was too big ('.round($anonSizeLimit/1000000,1).'Mb limit for anonymous users)';
   $logs->logSystemEvent('rhubarb', 0, 'Input file was too big ('.round($anonSizeLimit/1000000,1).'Mb limit for anonymous users)');
  }
 }
 else
 {
  $inputFileLog[]='Error uploading file - un-supported file format ('.$_FILES['fileToUpload']['type'].'). Must be a valid xml file';
  $logs->logSystemEvent('rhubarb', 0, 'Error uploading file - un-supported file format');
 }
}

if($validUpload)
{
 // ---------- header ---------------

 $header=array('BlanketEffectiveDate'=>'','ChangesSinceDate'=>'','ParentDUNSNumber'=>'','ParentGLN'=>'','ParentVMRSID'=>'','ParentAAIAID'=>'','BrandOwnerDUNS'=>'','BrandOwnerGLN'=>'','BrandOwnerVMRSID'=>'','BrandOwnerAAIAID'=>'','BuyerDuns'=>'','CurrencyCode'=>'','LanguageCode'=>'','TechnicalContact'=>'','ContactEmail'=>'','PCdbVersionDate'=>'','PAdbVersionDate'=>'');

 $headerElement=$doc->getElementsByTagName('Header');
 if(count($headerElement))
 {
  $blanketeffectivedateElement=$headerElement[0]->getElementsByTagName('BlanketEffectiveDate');
  if(count($blanketeffectivedateElement)){$header['BlanketEffectiveDate']=$blanketeffectivedateElement[0]->nodeValue;}

  $changessincedateElement=$headerElement[0]->getElementsByTagName('ChangesSinceDate');
  if(count($changessincedateElement)){$header['ChangesSinceDate']=$changessincedateElement[0]->nodeValue;}
  
  $parentDUNSNumberElement=$headerElement[0]->getElementsByTagName('ParentDUNSNumber');
  if(count($parentDUNSNumberElement)){$header['ParentDUNSNumber']=$parentDUNSNumberElement[0]->nodeValue;}
  
  $parentGLNelement=$headerElement[0]->getElementsByTagName('ParentGLN');
  if(count($parentGLNelement)){$header['ParentGLN']=$parentGLNelement[0]->nodeValue;}
  
  $parentVMRSIDelement=$headerElement[0]->getElementsByTagName('ParentVMRSID');
  if(count($parentVMRSIDelement)){$header['ParentVMRSID']=$parentVMRSIDelement[0]->nodeValue;}
  
  $parentAAIAIDelement=$headerElement[0]->getElementsByTagName('ParentAAIAID');
  if(count($parentAAIAIDelement)){$header['ParentAAIAID']=$parentAAIAIDelement[0]->nodeValue;}
  
  $brandOwnerDUNSelement=$headerElement[0]->getElementsByTagName('BrandOwnerDUNS');
  if(count($brandOwnerDUNSelement)){$header['BrandOwnerDUNS']=$brandOwnerDUNSelement[0]->nodeValue;}
  
  $brandOwnerGLNelement=$headerElement[0]->getElementsByTagName('BrandOwnerGLN');
  if(count($brandOwnerGLNelement)){$header['BrandOwnerGLN']=$brandOwnerGLNelement[0]->nodeValue;}
  
  $brandOwnerVMRSIDelement=$headerElement[0]->getElementsByTagName('BrandOwnerVMRSID');
  if(count($brandOwnerVMRSIDelement)){$header['BrandOwnerVMRSID']=$brandOwnerVMRSIDelement[0]->nodeValue;}
  
  $brandownerAAIAIDelement=$headerElement[0]->getElementsByTagName('BrandOwnerAAIAID');
  if(count($brandownerAAIAIDelement)){$header['BrandOwnerAAIAID']=$brandownerAAIAIDelement[0]->nodeValue;}
  
  $buyerDunsElement=$headerElement[0]->getElementsByTagName('BuyerDuns');
  if(count($buyerDunsElement)){$header['BuyerDuns']=$buyerDunsElement[0]->nodeValue;}
  
  $currencycodeElement=$headerElement[0]->getElementsByTagName('CurrencyCode');
  if(count($currencycodeElement)){$header['CurrencyCode']=$currencycodeElement[0]->nodeValue;}
  
  $languagecodeElement=$headerElement[0]->getElementsByTagName('LanguageCode');
  if(count($languagecodeElement)){$header['LanguageCode']=$languagecodeElement[0]->nodeValue;}
  
  $technicalcontactElement=$headerElement[0]->getElementsByTagName('TechnicalContact');
  if(count($technicalcontactElement)){$header['TechnicalContact']=$technicalcontactElement[0]->nodeValue;}
  
  $contactemailElement=$headerElement[0]->getElementsByTagName('ContactEmail');
  if(count($contactemailElement)){$header['ContactEmail']=$contactemailElement[0]->nodeValue;}
  
  $pcdbversiondateElement=$headerElement[0]->getElementsByTagName('PCdbVersionDate');
  if(count($pcdbversiondateElement)){$header['PCdbVersionDate']=$pcdbversiondateElement[0]->nodeValue;}
  
  $padbversiondateElement=$headerElement[0]->getElementsByTagName('PAdbVersionDate');
  if(count($padbversiondateElement)){$header['PAdbVersionDate']=$padbversiondateElement[0]->nodeValue;}
  
 }
 
 $marketingcopys=array();
 $marketingcopyElements=$doc->getElementsByTagName('MarketingCopy');
 if(count($marketingcopyElements))
 {
  $marketcopyElements=$marketingcopyElements[0]->getElementsByTagName('MarketCopy');
  foreach($marketcopyElements as $marketcopyElement)
  {
   $marketcopycontentElements=$marketcopyElement->getElementsByTagName('MarketCopyContent');
   foreach($marketcopycontentElements as $marketcopycontentElement)
   {
    $marketcopycontent=$marketcopycontentElement->nodeValue;
    $marketcopycode= $marketcopycontentElement->getAttribute('MarketCopyCode');
    $marketcopyreference=$marketcopycontentElement->getAttribute('MarketCopyReference');
    $marketcopytype=$marketcopycontentElement->getAttribute('MarketCopyType');
    $recordsequence=$marketcopycontentElement->getAttribute('RecordSequence');
    $languagecode=$marketcopycontentElement->getAttribute('LanguageCode');
    $marketcopysubcode=$marketcopycontentElement->getAttribute('MarketCopySubCode');
    $marketcopysubcodereference=$marketcopycontentElement->getAttribute('MarketCopySubCodeReference');
    $marketingcopys[]=array('MarketCopyContent'=>$marketcopycontent,'MarketCopyCode'=>$marketcopycode,'MarketCopyReference'=>$marketcopyreference,'MarketCopyType'=>$marketcopytype,'RecordSequence'=>$recordsequence,'LanguageCode'=>$languagecode,'MarketCopySubCode'=>$marketcopysubcode,'MarketCopySubCodeReference'=>$marketcopysubcodereference);
   }         
  }
 }     
    
    
 $items=array();
 $itemElements=$doc->getElementsByTagName('Item');
 foreach ($itemElements AS $itemElement) 
 {
  $partnumber=$itemElement->getElementsByTagName('PartNumber')[0]->nodeValue;
  $partterminologyid=$itemElement->getElementsByTagName('PartTerminologyID')[0]->nodeValue;
  
  $niceparttypename=$pcdb->parttypeName($partterminologyid);
  if($niceparttypename=='not found'){$errors[]="Ref:PartTerminologyID\t".$partnumber." has unknown part type (".$partterminologyid.')';}
  
  $brandaaiaid=$itemElement->getElementsByTagName('BrandAAIAID')[0]->nodeValue;
  
  $itemlevelgtin=''; $gtinqualifier='';
  $itemlevelgtinElement=$itemElement->getElementsByTagName('ItemLevelGTIN');
  if(count($itemlevelgtinElement))
  {
   $itemlevelgtin = $itemlevelgtinElement[0]->nodeValue;
   $gtinqualifier=$itemElement->getElementsByTagName('ItemLevelGTIN')[0]->getAttribute('GTINQualifier');//$itemElement->getElementsByTagName('GTINQualifier')->item(0)->nodeValue;
   if(trim($itemlevelgtin)!='')
   {
    if(!$pim->isValidBarcode($itemlevelgtin))
    {// checkdigit validation on GTIN
     $errors[]="GTIN:invalid check digit\t".$partnumber.' has invalid check';
    }

    if($gtinqualifier=='UP' && strlen($itemlevelgtin)==14 && substr($itemlevelgtin,0,2)!='00')
    {// UPC code should start with 00
     $errors[]="GTIN:malformed UPC\t".$partnumber.' has UPC code that does not start with 00';
    }
    
    if(array_key_exists($itemlevelgtin, $gtinmasterlist))
    {
     $errors[]="GTIN:duplicate\t".$partnumber.','.$gtinmasterlist[$itemlevelgtin].' share the same item-level GTIN ('.$itemlevelgtin.')';
    }
    else
    {
        $gtinmasterlist[trim($itemlevelgtin)]=trim($partnumber);
    }
   }
   
   
   
   
   
  }
  
  $minimumorderquantity=''; $minimumorderquantityuom='';
  $minimumorderquantityElement=$itemElement->getElementsByTagName('MinimumOrderQuantity');
  if(count($minimumorderquantityElement))
  {
   $minimumorderquantity = $minimumorderquantityElement[0]->nodeValue;
   $minimumorderquantityuom=$itemElement->getElementsByTagName('MinimumOrderQuantity')[0]->getAttribute('UOM');
  }

  $hazardousmaterialcode=''; $hazardousmaterialcodeElement=$itemElement->getElementsByTagName('HazardousMaterialCode'); if(count($hazardousmaterialcodeElement)){$hazardousmaterialcode = $hazardousmaterialcodeElement[0]->nodeValue;}
  $baseitemid=''; $baseitemidElement=$itemElement->getElementsByTagName('BaseItemID'); if(count($baseitemidElement)){$baseitemid = $baseitemidElement[0]->nodeValue;}
  $itemeffectivedate=''; $itemeffectivedateElement=$itemElement->getElementsByTagName('ItemEffectiveDate'); if(count($itemeffectivedateElement)){$itemeffectivedate = $itemeffectivedateElement[0]->nodeValue;}
  $availabledate=''; $availabledateElement=$itemElement->getElementsByTagName('AvailableDate'); if(count($availabledateElement)){$availabledate = $availabledateElement[0]->nodeValue;}
  $ACESapplications=''; $ACESapplicationsElement=$itemElement->getElementsByTagName('ACESApplications');if(count($ACESapplicationsElement)){$ACESapplications = $ACESapplicationsElement[0]->nodeValue;}
  
  $itemquantitysize=''; $itemquantitysizeuom='';
  $itemquantitysizeElement=$itemElement->getElementsByTagName('ItemQuantitySize');
  if(count($itemquantitysizeElement))
  {
   $itemquantitysize = $itemquantitysizeElement[0]->nodeValue;
   $itemquantitysizeuom=$itemElement->getElementsByTagName('ItemQuantitySize')[0]->getAttribute('UOM');
  }

  $containertype=''; $containertypeElement=$itemElement->getElementsByTagName('ContainerType'); if(count($containertypeElement)){$containertype = $containertypeElement[0]->nodeValue;}

  $quantityperapplication=''; $quantityperapplicationuom='';
  $quantityperapplicationElement=$itemElement->getElementsByTagName('QuantityPerApplication');
  if(count($quantityperapplicationElement))
  {
   $quantityperapplication = $quantityperapplicationElement[0]->nodeValue;
   $quantityperapplicationuom=$itemElement->getElementsByTagName('QuantityPerApplication')[0]->getAttribute('UOM');
  }

  $brandlabel=''; $brandlabelElement=$itemElement->getElementsByTagName('BrandLabel'); if(count($brandlabelElement)){$brandlabel = $brandlabelElement[0]->nodeValue;}  

  /*
  $brandlabel='FFF'; 
  foreach($itemElement->childNodes as $nodeTemp)
  {
      if($nodeTemp->nodeName=='BrandLabel' && !$nodeTemp->hasAttributes()){$brandlabel=$nodeTemp->nodeValue.'sdsdsd'; break;}
  }
  */
  
  
  $VMRSbrandid=''; $VMRSbrandidElement=$itemElement->getElementsByTagName('VMRSBrandID'); if(count($VMRSbrandidElement)){$VMRSbrandid = $VMRSbrandidElement[0]->nodeValue;}
  $UNSPSC=''; $UNSPSCElement=$itemElement->getElementsByTagName('UNSPSC'); if(count($UNSPSCElement)){$UNSPSC = $UNSPSCElement[0]->nodeValue;}
  
  //----------- descriptions -----------
  $descriptions=array();
  $descriptionsElement=$itemElement->getElementsByTagName('Descriptions');
  if(count($descriptionsElement))
  {
   $descriptionElements=$descriptionsElement[0]->getElementsByTagName('Description');
   foreach($descriptionElements as $descriptionElement)
   {
    $descriptionText=''; $descriptionCode=''; $languageCode=''; $sequence=1;
    $descriptionText= $descriptionElement->nodeValue;
    $descriptionCode= $descriptionElement->getAttribute('DescriptionCode');
    $languageCode= $descriptionElement->getAttribute('LanguageCode');
    $sequence=$descriptionElement->getAttribute('Sequence');
    $descriptions[]=array('Description'=>$descriptionText,'DescriptionCode'=>$descriptionCode,'LanguageCode'=>$languageCode,'Sequence'=>$sequence);
   }
  }
 
  //--------------- prices --------------------
  $prices=array();
  $pricesElement=$itemElement->getElementsByTagName('Prices');
  if(count($pricesElement))
  {
   $pricingElements=$pricesElement[0]->getElementsByTagName('Pricing');
   foreach($pricingElements as $pricingElement)
   {
    $pricesheetnumber=''; $price=''; $priceuom=''; $pricetype=''; $currencycode=''; $effectivedate=''; $expirationdate=''; $pricetypedescription=''; $pricebreak=''; $pricebreakuom=''; $pricemultiplier='';   $pricetype=$pricingElement->getAttribute('PriceType');
    $pricesheetnumberElement=$pricingElement->getElementsByTagName('PriceSheetNumber'); if(count($pricesheetnumberElement)){$pricesheetnumber = $pricesheetnumberElement[0]->nodeValue;}
    $priceElement=$pricingElement->getElementsByTagName('Price'); if(count($priceElement)){$price = $priceElement[0]->nodeValue; $priceuom=$priceElement[0]->getAttribute('UOM');}
    $currencycodeElement=$pricingElement->getElementsByTagName('CurrencyCode'); if(count($currencycodeElement)){$currencycode = $currencycodeElement[0]->nodeValue;}
    $effectivedateElement=$pricingElement->getElementsByTagName('EffectiveDate'); if(count($effectivedateElement)){$effectivedate = $effectivedateElement[0]->nodeValue;}
    $expirationdateElement=$pricingElement->getElementsByTagName('ExpirationDate'); if(count($expirationdateElement)){$expirationdate = $expirationdateElement[0]->nodeValue;}
    $pricetypedescriptionElement=$pricingElement->getElementsByTagName('PriceTypeDescription'); if(count($pricetypedescriptionElement)){$pricetypedescription = $pricetypedescriptionElement[0]->nodeValue;}
    $pricebreakElement=$pricingElement->getElementsByTagName('PriceBreak'); if(count($pricebreakElement)){$pricebreak = $pricebreakElement[0]->nodeValue; $pricebreakuom=$pricebreakElement[0]->getAttribute('UOM');}
    $pricemultiplierElement=$pricingElement->getElementsByTagName('PriceMultiplier');
    if(count($pricemultiplierElement)){$pricemultiplier = $pricemultiplierElement[0]->nodeValue;}
    $prices[]=array('PriceSheetNumber'=>$pricesheetnumber,'Price'=>$price,'PriceUOM'=>$priceuom,'PriceType'=>$pricetype,'CurrencyCode'=>$currencycode,'EffectiveDate'=>$effectivedate,'ExpirationDate'=>$expirationdate,'PriceTypeDescription'=>$pricetypedescription,'PriceBreak'=>$pricebreak,'PriceBreakUOM'=>$pricebreakuom,'PriceMultiplier'=>$pricemultiplier);
   }
  }

  //----------- expi -----------
  $expis=array();
  $expisElement=$itemElement->getElementsByTagName('ExtendedInformation');
  if(count($expisElement))
  {

   $expiElements=$expisElement[0]->getElementsByTagName('ExtendedProductInformation');
   foreach($expiElements as $expiElement)
   {
    $expicode=''; $expivalue=''; $languageCode=''; 
    $expivalue=$expiElement->nodeValue;
    $expicode=$expiElement->getAttribute('EXPICode');
    $languageCode=$expiElement->getAttribute('LanguageCode');
    $expis[]=array('EXPICode'=>$expicode, 'EXPIValue'=>$expivalue,'LanguageCode'=>$languageCode);

/*    
    
    if(count($validcodes)==0)
    {// invalid code
        
        $errors[]="EXPI:invalid code\t".$partnumber.','.$expicode.'='.$expivalue;
    }
    else
    {// options count is 1 or more
        if(count($validcodes)==1 && $validcodes[0]['code']=='*')
        {//special case - no valid values exist in the pcdb. this implies free-form text is valid
            
        }
        else
        {// there are prescribed valid values for this expi code
         $found=false; $nicelist=array(); foreach($validcodes as $validcode){$nicelist[]=$validcode['code']; if($validcode['code']==$expivalue){$found=true;break;}}
         if(!$found){$errors[]="EXPI:invalid value\t".$partnumber.','.$expicode.':'.$expivalue.'; valid options:'.implode(',',$nicelist);}
        }
    }
 */
   }
  }

  //----------- attributes -----------
  $attributes=array();
  $attributesElement=$itemElement->getElementsByTagName('ProductAttributes');
  if(count($attributesElement))
  {
   $atttributeElements=$attributesElement[0]->getElementsByTagName('ProductAttribute');
   foreach($atttributeElements as $atttributeElement)
   {
    $attributeid=''; $attributevalue=''; $padbattribute=''; $languageCode=''; $multivaluesequence=''; $multivaluequantity=''; $styleid='';
    $attributevalue=$atttributeElement->nodeValue;
    $attributeid=$atttributeElement->getAttribute('AttributeID');
    $attributeuom=$atttributeElement->getAttribute('AttributeUOM');
    $padbattribute=$atttributeElement->getAttribute('PADBAttribute');
    $recordnumber=$atttributeElement->getAttribute('RecordNumber');
    $styleid=$atttributeElement->getAttribute('StyleID');
    $multivaluequantity=$atttributeElement->getAttribute('MultiValueQuantity');
    $multivaluesequence=$atttributeElement->getAttribute('MultiValueSequence');
    $languageCode=$atttributeElement->getAttribute('LanguageCode');
    $attributes[]=array('AttributeID'=>$attributeid, 'AttributeValue'=>$attributevalue,'AttributeUOM'=>$attributeuom,'RecordNumber'=>$recordnumber,'PADBAttribute'=>$padbattribute,'StyleID'=>$styleid,'MultiValueQuantity'=>$multivaluequantity,'MultiValueSequence'=>$multivaluesequence,'LanguageCode'=>$languageCode);
   }
  }
  
  //------------ packages -----------------------
  
  $packages=array();
  $packagesElement=$itemElement->getElementsByTagName('Packages');
  if(count($packagesElement))
  {
   $packageElements=$packagesElement[0]->getElementsByTagName('Package');
   foreach($packageElements as $packageElement)
   {
    $packagelevelgtin=''; $packagelevelgtinElement=$packageElement->getElementsByTagName('PackageLevelGTIN');   
    if(count($packagelevelgtinElement)){$packagelevelgtin=$packagelevelgtinElement[0]->nodeValue;}

    $electronicproductcode=''; $electronicproductcodeElement=$packageElement->getElementsByTagName('ElectronicProductCode');   
    if(count($electronicproductcodeElement)){$electronicproductcode=$electronicproductcodeElement[0]->nodeValue;}
    
    $packageuom=''; $packageuomElement=$packageElement->getElementsByTagName('PackageUOM');   
    if(count($packageuomElement)){$packageuom=$packageuomElement[0]->nodeValue;}
    
    $quantityofeaches=''; $quantityofeachesElement=$packageElement->getElementsByTagName('QuantityofEaches');   
    if(count($quantityofeachesElement)){$quantityofeaches=$quantityofeachesElement[0]->nodeValue;}
    
    $innerquantity=''; $innerquantityuom='';
    $innerquantityElement=$packageElement->getElementsByTagName('InnerQuantity');
    if(count($innerquantityElement))
    {
     $innerquantity=$innerquantityElement[0]->nodeValue;
     $innerquantityuom=$innerquantityElement[0]->getAttribute('InnerQuantityUOM');
    }
    
    $weight=''; $weightsuom=''; $dimensionalweight='';
    $weightsElement=$packageElement->getElementsByTagName('Weights');
    if(count($weightsElement))
    {
     $weightsuom=$weightsElement[0]->getAttribute('UOM'); 
     $weightElement=$weightsElement[0]->getElementsByTagName('Weight');
     if(count($weightElement)){$weight=$weightElement[0]->nodeValue;}
     $dimensionalweightElement=$weightsElement[0]->getElementsByTagName('DimensionalWeight');
     if(count($dimensionalweightElement)){$dimensionalweight=$weightElement[0]->nodeValue;}
    }

    $packagebarcodecharacters=''; $packagebarcodecharactersElement=$packageElement->getElementsByTagName('PackageBarCodeCharacters');   
    if(count($packagebarcodecharactersElement)){$packagebarcodecharacters=$packagebarcodecharactersElement[0]->nodeValue;}

    $weightvariance=''; $weightvarianceElement=$packageElement->getElementsByTagName('WeightVariance');   
    if(count($weightvarianceElement)){$weightvariance=$weightvarianceElement[0]->nodeValue;}

    $stackingfactor=''; $stackingfactorElement=$packageElement->getElementsByTagName('StackingFactor');   
    if(count($stackingfactorElement)){$stackingfactor=$stackingfactorElement[0]->nodeValue;}
    
        

    $dimensionsuom=''; $merchandisingheight='';$merchandisingwidth=''; $merchandisinglength=''; $shippingheight=''; $shippingwidth=''; $shippinglength='';
    $dimensionsElement=$packageElement->getElementsByTagName('Dimensions');
    if(count($dimensionsElement))
    {
     $dimensionsuom=$dimensionsElement[0]->getAttribute('UOM'); 
     
     $merchandisingheightElement=$dimensionsElement[0]->getElementsByTagName('MerchandisingHeight');
     if(count($merchandisingheightElement)){$merchandisingheight=$merchandisingheightElement[0]->nodeValue;}

     $merchandisingwidthElement=$dimensionsElement[0]->getElementsByTagName('MerchandisingWidth');
     if(count($merchandisingwidthElement)){$merchandisingwidth=$merchandisingwidthElement[0]->nodeValue;}

     $merchandisinglengthElement=$dimensionsElement[0]->getElementsByTagName('MerchandisingLength');
     if(count($merchandisinglengthElement)){$merchandisinglength=$merchandisinglengthElement[0]->nodeValue;}
     
     $shippingheightElement=$dimensionsElement[0]->getElementsByTagName('ShippingHeight');
     if(count($shippingheightElement)){$shippingheight=$shippingheightElement[0]->nodeValue;}

     $shippingwidthElement=$dimensionsElement[0]->getElementsByTagName('ShippingWidth');
     if(count($shippingwidthElement)){$shippingwidth=$shippingwidthElement[0]->nodeValue;}

     $shippinglengthElement=$dimensionsElement[0]->getElementsByTagName('ShippingLength');
     if(count($shippinglengthElement)){$shippinglength=$shippinglengthElement[0]->nodeValue;}
    }

    $languagecode=''; $shippingscope=''; $bulk=''; $regulatingcountry=''; $transportmethod='';
    $regulated=''; $description=''; $hazardousmaterialcodequalifier=''; $hazardousmaterialdescription='';
    $hazardousmateriallabelcode=''; $shippingname=''; $UNNAIDcode='';$hazardousplacardnotation='';
    $outerpackagelabel=''; $textmessage=''; $regulationsexemptioncode=''; $packinggroupcode='';
    $WHMISfreetext=''; $WHMIScode='';
    $hazardousmaterialElement=$packageElement->getElementsByTagName('HazardousMaterial');
    if(count($hazardousmaterialElement))
    {
     $languagecode=$hazardousmaterialElement[0]->getAttribute('LanguageCode'); 
     
     $shippingscopeElement=$hazardousmaterialElement[0]->getElementsByTagName('ShippingScope');
     if(count($shippingscopeElement)){$shippingscope=$shippingscopeElement[0]->nodeValue;}
     
     $bulkElement=$hazardousmaterialElement[0]->getElementsByTagName('Bulk');
     if(count($bulkElement)){$bulk=$bulkElement[0]->nodeValue;}

     $regulatingcountryElement=$hazardousmaterialElement[0]->getElementsByTagName('RegulatingCountry');
     if(count($regulatingcountryElement)){$regulatingcountry=$regulatingcountryElement[0]->nodeValue;}
     
     $transportmethodElement=$hazardousmaterialElement[0]->getElementsByTagName('TransportMethod');
     if(count($transportmethodElement)){$transportmethod=$transportmethodElement[0]->nodeValue;}

     $regulatedElement=$hazardousmaterialElement[0]->getElementsByTagName('Regulated');
     if(count($regulatedElement)){$regulated=$regulatedElement[0]->nodeValue;}

     $descriptionElement=$hazardousmaterialElement[0]->getElementsByTagName('Description');
     if(count($descriptionElement)){$description=$descriptionElement[0]->nodeValue;}

     $hazardousmaterialcodequalifierElement=$hazardousmaterialElement[0]->getElementsByTagName('HazardousMaterialCodeQualifier');
     if(count($hazardousmaterialcodequalifierElement)){$hazardousmaterialcodequalifier=$hazardousmaterialcodequalifierElement[0]->nodeValue;}

     $hazardousmaterialdescriptionElement=$hazardousmaterialElement[0]->getElementsByTagName('HazardousMaterialDescription');
     if(count($hazardousmaterialdescriptionElement)){$hazardousmaterialdescription=$hazardousmaterialdescriptionElement[0]->nodeValue;}
     
     $hazardousmateriallabelcodeElement=$hazardousmaterialElement[0]->getElementsByTagName('HazardousMaterialLabelCode');
     if(count($hazardousmateriallabelcodeElement)){$hazardousmateriallabelcode=$hazardousmateriallabelcodeElement[0]->nodeValue;}

     $shippingnameElement=$hazardousmaterialElement[0]->getElementsByTagName('ShippingName');
     if(count($shippingnameElement)){$shippingname=$shippingnameElement[0]->nodeValue;}

     $UNNAIDcodeElement=$hazardousmaterialElement[0]->getElementsByTagName('UNNAIDCode');
     if(count($UNNAIDcodeElement)){$UNNAIDcode=$UNNAIDcodeElement[0]->nodeValue;}
     
     $hazardousplacardnotationElement=$hazardousmaterialElement[0]->getElementsByTagName('HazardousPlacardNotation');
     if(count($hazardousplacardnotationElement)){$hazardousplacardnotation=$hazardousplacardnotationElement[0]->nodeValue;}
     
     $WHMIScodeElement=$hazardousmaterialElement[0]->getElementsByTagName('WHMISCode');
     if(count($WHMIScodeElement)){$WHMIScode=$WHMIScodeElement[0]->nodeValue;}

     $WHMISfreetextElement=$hazardousmaterialElement[0]->getElementsByTagName('WHMISFreeText');
     if(count($WHMISfreetextElement)){$WHMISfreetext=$WHMISfreetextElement[0]->nodeValue;}

     $packinggroupcodeElement=$hazardousmaterialElement[0]->getElementsByTagName('PackingGroupCode');
     if(count($packinggroupcodeElement)){$packinggroupcode=$packinggroupcodeElement[0]->nodeValue;}

     $regulationsexemptioncodeElement=$hazardousmaterialElement[0]->getElementsByTagName('RegulationsExemptionCode');
     if(count($regulationsexemptioncodeElement)){$regulationsexemptioncode=$regulationsexemptioncodeElement[0]->nodeValue;}

     $textmessageElement=$hazardousmaterialElement[0]->getElementsByTagName('TextMessage');
     if(count($textmessageElement)){$textmessage=$textmessageElement[0]->nodeValue;}

     $outerpackagelabelElement=$hazardousmaterialElement[0]->getElementsByTagName('OuterPackageLabel');
     if(count($outerpackagelabelElement)){$outerpackagelabel=$outerpackagelabelElement[0]->nodeValue;}
     
    }
    
    $packages[]=array('PackageLevelGTIN'=>$packagelevelgtin,'ElectronicProductCode'=>$electronicproductcode,'PackageUOM'=>$packageuom,'QuantityofEaches'=>$quantityofeaches,'InnerQuantity'=>$innerquantity,'InnerQuantityUOM'=>$innerquantityuom,'Weight'=>$weight,'DimensionalWeight'=>$dimensionalweight,'WeightsUOM'=>$weightsuom,'PackageBarCodeCharacters'=>$packagebarcodecharacters,'WeightVariance'=>$weightvariance,'StackingFactor'=>$stackingfactor,'DimensionsUOM'=>$dimensionsuom, 'MerchandisingHeight'=>$merchandisingheight,'MerchandisingWidth'=>$merchandisingwidth, 'MerchandisingLength'=>$merchandisinglength, 'ShippingHeight'=>$shippingheight, 'ShippingWidth'=>$shippingwidth,'ShippingLength'=>$shippinglength, 'LanguageCode'=>$languagecode, 'ShippingScope'=>$shippingscope, 'Bulk'=>$bulk, 'RegulatingCountry'=>$regulatingcountry, 'TransportMethod'=>$transportmethod,'Regulated'=>$regulated, 'Description'=>$description, 'HazardousMaterialCodeQualifier'=>$hazardousmaterialcodequalifier, 'HazardousMaterialDescription'=>$hazardousmaterialdescription,    'HazardousMaterialLabelCode'=>$hazardousmateriallabelcode, 'ShippingName'=>$shippingname, 'UNNAIDCode'=>$UNNAIDcode, 'HazardousPlacardNotation'=>$hazardousplacardnotation,'OuterPackageLabel'=>$outerpackagelabel, 'TextMessage'=>$textmessage,'RegulationsExemptionCode'=>$regulationsexemptioncode, 'PackingGroupCode'=>$packinggroupcode,'WHMISFreeText'=>$WHMISfreetext, 'WHMISCode'=>$WHMIScode);
   }
  }
  
  
  //--------------- kits --------------

  $kits=array();
  $kitsElement=$itemElement->getElementsByTagName('Kits');
  if(count($kitsElement))
  {
   $kitcomponentElements=$kitsElement[0]->getElementsByTagName('KitComponent');
   foreach($kitcomponentElements as $kitcomponentElement)
   {
    $componentpartnumber=''; $description=''; $descriptioncode=''; $quantityinkit=''; $quantityinkituom=''; $soldseparately=''; $languagecode=''; $componentpartterminologyid=''; $sequencecode=''; $componentbrand=''; $componentbrandlabel=''; $componentsubbrand=''; $componentsubbrandlabel='';

    $componentpartnumberElement=$kitcomponentElement->getElementsByTagName('ComponentPartNumber');
    if(count($componentpartnumberElement)){$componentpartnumber=$componentpartnumberElement[0]->nodeValue;}
     
    $componentbrandElement=$kitcomponentElement->getElementsByTagName('ComponentBrand');
    if(count($componentbrandElement)){$componentbrand=$componentbrandElement[0]->nodeValue;}

    $componentbrandlabelElement=$kitcomponentElement->getElementsByTagName('ComponentBrandLabel');
    if(count($componentbrandlabelElement)){$componentbrandlabel=$componentbrandlabelElement[0]->nodeValue;}

    $componentsubbrandElement=$kitcomponentElement->getElementsByTagName('ComponentSubBrand');
    if(count($componentsubbrandElement)){$componentsubbrand=$componentsubbrandElement[0]->nodeValue;}

    $componentsubbrandlabelElement=$kitcomponentElement->getElementsByTagName('ComponentSubBrandLabel');
    if(count($componentsubbrandlabelElement)){$componentsubbrandlabel=$componentsubbrandlabelElement[0]->nodeValue;}
     
    //Description
    $descriptionElement=$kitcomponentElement->getElementsByTagName('Description');
    if(count($descriptionElement))
    {
     $description=$descriptionElement[0]->nodeValue;
     $descriptioncode=$descriptionElement[0]->getAttribute('DescriptionCode'); 
     $languagecode=$descriptionElement[0]->getAttribute('LanguageCode');
    }
     
    $componentpartterminologyidElement=$kitcomponentElement->getElementsByTagName('ComponentPartTerminologyID');
    if(count($componentpartterminologyidElement)){$componentpartterminologyid=$componentpartterminologyidElement[0]->nodeValue;}
     
    $quantityinkitElement=$kitcomponentElement->getElementsByTagName('QuantityInKit');
    if(count($quantityinkitElement))
    {
     $quantityinkit=$quantityinkitElement[0]->nodeValue;
     $quantityinkituom=$quantityinkitElement[0]->getAttribute('UOM'); 
    }

    $sequencecodeElement=$kitcomponentElement->getElementsByTagName('SequenceCode');
    if(count($sequencecodeElement)){$sequencecode=$sequencecodeElement[0]->nodeValue;}
     
    $soldseparatelyElement=$kitcomponentElement->getElementsByTagName('SoldSeparately');
    if(count($soldseparatelyElement)){$soldseparately=$soldseparatelyElement[0]->nodeValue;}
     
     
    $kits[]=array('ComponentPartNumber'=>$componentpartnumber,'Description'=>$description,'DescriptionCode'=>$descriptioncode,'QuantityInKit'=>$quantityinkit,'QuantityInKitUOM'=>$quantityinkituom,'SoldSeparately'=>$soldseparately,'LanguageCode'=>$languagecode,'ComponentPartTerminologyID'=>$componentpartterminologyid, 'SequenceCode'=>$sequencecode, 'ComponentBrand'=>$componentbrand, 'ComponentBrandLabel'=>$componentbrandlabel, 'ComponentSubBrand'=>$componentsubbrand, 'ComponentSubBrandLabel'=>$componentsubbrandlabel); 
   }      
  }
  
  //-------------- interchange -------------------------
  // there's a tricky one-to-many issue here that needs to be considered
  $interchanges=array();
  $partinterchangeinfoElement=$itemElement->getElementsByTagName('PartInterchangeInfo');
  if(count($partinterchangeinfoElement))
  {
   $partinterchangeElements=$partinterchangeinfoElement[0]->getElementsByTagName('PartInterchange');
   foreach($partinterchangeElements as $partinterchangeElement)
   {
    // get the competitor-level attributes
    $interchangebrandAAIAID=$partinterchangeElement->getAttribute('BrandAAIAID');
    $interchangebrandlabel=$partinterchangeElement->getAttribute('BrandLabel');
    $subbrandAAIAID=$partinterchangeElement->getAttribute('SubBrandAAIAID');
    $internalnotes=$partinterchangeElement->getAttribute('InternalNotes');
    $languagecode=$partinterchangeElement->getAttribute('LanguageCode');
    $subbrandlabel=$partinterchangeElement->getAttribute('SubBrandLabel');
    $VMRSbrandid=$partinterchangeElement->getAttribute('VMRSBrandID');
    $qualitygradelevel=$partinterchangeElement->getAttribute('QualityGradeLevel');
    $itemequivalentuom=$partinterchangeElement->getAttribute('ItemEquivalentUOM');
   
    $partnumberElements=$partinterchangeElement->getElementsByTagName('PartNumber');
    foreach($partnumberElements as $partnumberElement)
    {  // possible multiple parts per competitor
     $competitorpartnumber=$partnumberElement->nodeValue;
     $referenceitem=$partnumberElement->getAttribute('ReferenceItem');
     $interchangequantity=$partnumberElement->getAttribute('InterchangeQuantity');
     $interchangequantityuom=$partnumberElement->getAttribute('UOM');
     $interchangenotes=$partnumberElement->getAttribute('InterchangeNotes');
     $interchanges[]=array('CompetitorPartNumber'=>$competitorpartnumber,'ReferenceItem'=>$referenceitem,'InterchangeQuantity'=>$interchangequantity,'UOM'=>$interchangequantityuom,'InterchangeNotes'=>$interchangenotes,'BrandAAIAID'=>$interchangebrandAAIAID,'BrandLabel'=>$interchangebrandlabel,'SubBrandAAIAID'=>$subbrandAAIAID,'InternalNotes'=>$internalnotes,'LanguageCode'=>$languagecode,'SubBrandLabel'=>$subbrandlabel, 'VMRSBrandID'=>$VMRSbrandid, 'QualityGradeLevel'=>$qualitygradelevel, 'ItemEquivalentUOM'=>$itemequivalentuom);
    }
   }
  }
  

  //----------- digitalassets -----------
  $digitalassets=array();
  $digitalassetsElement=$itemElement->getElementsByTagName('DigitalAssets');
  if(count($digitalassetsElement))
  {
   $digitalfileinformationElements=$digitalassetsElement[0]->getElementsByTagName('DigitalFileInformation');
   foreach($digitalfileinformationElements as $digitalfileinformationElement)
   {
    $filename=''; $assettype=''; $filetype=''; $representation=''; $background=''; $orientationview=''; $uri=''; $country=''; $filesize=''; $resolution=''; $colormode=''; $filepath=''; $frame=''; $totalframes=''; $plane=''; $hemisphere=''; $plunge=''; $totalplanes='';
    $assetdescription=''; $assetdescriptioncode=''; $assetdescriptionlanguagecode=''; $assetheight=''; $assetwidth=''; $assetdimensionsuom=''; $assetdatetype=''; $assetdate='';
    
    $assetid=$digitalfileinformationElement->getAttribute('AssetID');
    $languagecode=$digitalfileinformationElement->getAttribute('LanguageCode');

    $filenameElement=$digitalfileinformationElement->getElementsByTagName('FileName');
    if(count($filenameElement)){$filename=$filenameElement[0]->nodeValue;}

    $assettypeElement=$digitalfileinformationElement->getElementsByTagName('AssetType');
    if(count($assettypeElement)){$assettype=$assettypeElement[0]->nodeValue;}
    
    $filetypeElement=$digitalfileinformationElement->getElementsByTagName('FileType');
    if(count($filetypeElement)){$filetype=$filetypeElement[0]->nodeValue;}

    $representationElement=$digitalfileinformationElement->getElementsByTagName('Representation');
    if(count($representationElement)){$representation=$representationElement[0]->nodeValue;}
    
    $backgroundElement=$digitalfileinformationElement->getElementsByTagName('Background');
    if(count($backgroundElement)){$background=$backgroundElement[0]->nodeValue;}
    
    $orientationviewElement=$digitalfileinformationElement->getElementsByTagName('OrientationView');
    if(count($orientationviewElement)){$orientationview=$orientationviewElement[0]->nodeValue;}
    
    $assetDimensionsElement=$digitalfileinformationElement->getElementsByTagName('AssetDimensions');
    if(count($assetDimensionsElement))
    {
        $assetdimensionsuom=$assetDimensionsElement[0]->getAttribute('UOM');            
        $assetDimensionsHeightElement=$assetDimensionsElement[0]->getElementsByTagName('AssetHeight');
        if(count($assetDimensionsHeightElement)){$assetheight=$assetDimensionsHeightElement[0]->nodeValue;}
        $assetDimensionsWidthElement=$assetDimensionsElement[0]->getElementsByTagName('AssetWidth');
        if(count($assetDimensionsWidthElement)){$assetwidth=$assetDimensionsWidthElement[0]->nodeValue;}
    }
    
    $assetDatesElement=$digitalfileinformationElement->getElementsByTagName('AssetDates');
    if(count($assetDatesElement))
    {
        $assetDateElement=$assetDatesElement[0]->getElementsByTagName('AssetDate');
        if(count($assetDateElement))
        {
            $assetdatetype=$assetDateElement[0]->getAttribute('assetDateType');
            $assetdate=$assetDateElement[0]->nodeValue;
        }
    }

    $assetDescriptionsElement=$digitalfileinformationElement->getElementsByTagName('AssetDescriptions');
    if(count($assetDescriptionsElement))
    {
        $assetDescriptionElement=$assetDescriptionsElement[0]->getElementsByTagName('Description');
        if(count($assetDescriptionElement))
        {
            $assetdescription=$assetDescriptionElement[0]->nodeValue;
            $assetdescriptioncode=$assetDescriptionElement[0]->getAttribute('DescriptionCode');
            $assetdescriptionlanguagecode=$assetDescriptionElement[0]->getAttribute('LanguageCode');
        }
    }
    
    $uriElement=$digitalfileinformationElement->getElementsByTagName('URI');
    if(count($uriElement)){$uri=$uriElement[0]->nodeValue;}
    
    $countryElement=$digitalfileinformationElement->getElementsByTagName('Country');
    if(count($countryElement)){$country=$countryElement[0]->nodeValue;}
    
    $filesizeElement=$digitalfileinformationElement->getElementsByTagName('FileSize');
    if(count($filesizeElement)){$filesize=$filesizeElement[0]->nodeValue;}

    $resolutionElement=$digitalfileinformationElement->getElementsByTagName('Resolution');
    if(count($resolutionElement)){$resolution=$resolutionElement[0]->nodeValue;}
		
    $colormodeElement=$digitalfileinformationElement->getElementsByTagName('ColorMode');
    if(count($colormodeElement)){$colormode=$colormodeElement[0]->nodeValue;}

    $filepathElement=$digitalfileinformationElement->getElementsByTagName('FilePath');
    if(count($filepathElement)){$filepath=$filepathElement[0]->nodeValue;}

    $frameElement=$digitalfileinformationElement->getElementsByTagName('Frame');
    if(count($frameElement)){$frame=$frameElement[0]->nodeValue;}

    $totalframesElement=$digitalfileinformationElement->getElementsByTagName('TotalFrames');
    if(count($totalframesElement)){$totalframes=$totalframesElement[0]->nodeValue;}
    
    $planeElement=$digitalfileinformationElement->getElementsByTagName('Plane');
    if(count($planeElement)){$plane=$planeElement[0]->nodeValue;}
    
    $hemisphereElement=$digitalfileinformationElement->getElementsByTagName('Hemisphere');
    if(count($hemisphereElement)){$hemisphere=$hemisphereElement[0]->nodeValue;}
    
    $plungeElement=$digitalfileinformationElement->getElementsByTagName('Plunge');
    if(count($plungeElement)){$plunge=$plungeElement[0]->nodeValue;}
    
    $totalplanesElement=$digitalfileinformationElement->getElementsByTagName('TotalPlanes');
    if(count($totalplanesElement)){$totalplanes=$totalplanesElement[0]->nodeValue;}
    
    $digitalassets[]=array('FileName'=>$filename, 'AssetType'=>$assettype,'AssetID'=>$assetid, 'FileType'=>$filetype, 'Representation'=>$representation, 'Background'=>$background, 'OrientationView'=>$orientationview, 'URI'=>$uri, 'Country'=>$country, 'LanguageCode'=>$languagecode,'FileSize'=>$filesize,'Resolution'=>$resolution,'ColorMode'=>$colormode,'FilePath'=>$filepath,'Frame'=>$frame,'TotalFrames'=>$totalframes,'Plane'=>$plane,'Hemisphere'=>$hemisphere,'Plunge'=>$plunge,'TotalPlanes'=>$totalplanes,'AssetHeight'=>$assetheight,'AssetWidth'=>$assetwidth,'UOM'=>$assetdimensionsuom ,'Description'=>$assetdescription,'DescriptionCode'=>$assetdescriptioncode,'DescriptionLanguageCode'=>$assetdescriptionlanguagecode,'AssetDate'=>$assetdate,'AssetDateType'=>$assetdatetype,'Duration'=>'','DurationUOM'=>'');
   }
  }








  
  //----- jam all the segments into the items array
  $items[$partnumber]=array('PartTerminologyID'=>$partterminologyid,'BrandAAIAID'=>$brandaaiaid,'ItemLevelGTIN'=>$itemlevelgtin,'GTINQualifier'=>$gtinqualifier,'MinimumOrderQuantity'=>$minimumorderquantity,'MinimumOrderQuantityUOM'=>$minimumorderquantityuom,'HazardousMaterialCode'=>$hazardousmaterialcode,'BaseItemID'=>$baseitemid,'ItemEffectiveDate'=>$itemeffectivedate,'AvailableDate'=>$availabledate,'ACESApplications'=>$ACESapplications,'ItemQuantitySize'=>$itemquantitysize,'ItemQuantitySizeUOM'=>$itemquantitysizeuom,'ContainerType'=>$containertype,'QuantityPerApplication'=>$quantityperapplication,'QuantityPerApplicationUOM'=>$quantityperapplicationuom,'BrandLabel'=>$brandlabel,'VMRSBrandID'=>$VMRSbrandid,'UNSPSC'=>$UNSPSC,'NicePartTerminologyName'=>$niceparttypename,'descriptions'=>$descriptions,'prices'=>$prices,'expis'=>$expis,'attributes'=>$attributes,'packages'=>$packages,'kits'=>$kits,'interchanges'=>$interchanges,'digitalassets'=>$digitalassets);
 } // item element foreach
  
 $writer = new XLSXWriter();
 $writer->setAuthor('SandPIM'); 
 
 $writer->writeSheetHeader('Header', array('TechnicalContact'=>'string',$header['TechnicalContact']=>'string'),array('widths'=>array(20,60)));
// $row=array('RhubarbTemplate','7.1'); $writer->writeSheetRow('Header', $row);
// $row=array('TechnicalContact',$header['TechnicalContact']); $writer->writeSheetRow('Header', $row);
 $row=array('ContactEmail',$header['ContactEmail']); $writer->writeSheetRow('Header', $row);
 $row=array('PCdbVersionDate',$header['PCdbVersionDate']); $writer->writeSheetRow('Header', $row);
 $row=array('BlanketEffectiveDate',$header['BlanketEffectiveDate']); $writer->writeSheetRow('Header', $row);
 $row=array('LanguageCode',$header['LanguageCode']); $writer->writeSheetRow('Header', $row);
 $row=array('PAdbVersionDate',$header['PAdbVersionDate']); $writer->writeSheetRow('Header', $row);
 $row=array('ChangesSinceDate',$header['ChangesSinceDate']); $writer->writeSheetRow('Header', $row);
 $row=array('CurrencyCode',$header['CurrencyCode']); $writer->writeSheetRow('Header', $row);
 $row=array('ParentAAIAID',$header['ParentAAIAID']); $writer->writeSheetRow('Header', $row);
 $row=array('BrandOwnerAAIAID',$header['BrandOwnerAAIAID']); $writer->writeSheetRow('Header', $row);
 $row=array('ParentGLN',$header['ParentGLN']); $writer->writeSheetRow('Header', $row);
 $row=array('BrandOwnerGLN',$header['BrandOwnerGLN']); $writer->writeSheetRow('Header', $row);
 $row=array('BuyerDuns',$header['BuyerDuns']); $writer->writeSheetRow('Header', $row);
 $row=array('ParentDUNSNumber',$header['ParentDUNSNumber']); $writer->writeSheetRow('Header', $row);
 $row=array('BrandOwnerDUNS',$header['BrandOwnerDUNS']); $writer->writeSheetRow('Header', $row);
 $row=array('BrandOwnerVMRSID',$header['BrandOwnerVMRSID']); $writer->writeSheetRow('Header', $row);
 $row=array('ParentVMRSID',$header['ParentVMRSID']); $writer->writeSheetRow('Header', $row);
  
 //------- marketingcopy -----
 //MarketCopyContent	MarketCopyCode	MarketCopyReference	MarketCopyType	RecordSequence	LanguageCode	MarketCopySubCode	MarketCopySubCodeReference
 $writer->writeSheetHeader('MarketingCopy', array('MarketCopyContent'=>'string', 'MarketCopyCode'=>'string', 'MarketCopyReference'=>'string', 'MarketCopyType'=>'string', 'RecordSequence'=>'integer', 'LanguageCode'=>'string', 'MarketCopySubCode'=>'string', 'MarketCopySubCodeReference'=>'string'), array('widths'=>array(52,16,21,16,16,14,20,29),'freeze_rows'=>1, 'freeze_columns'=>1,         ['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00']));
 foreach($marketingcopys as $marketingcopy)
 {
  $row=array($marketingcopy['MarketCopyContent'],$marketingcopy['MarketCopyCode'],$marketingcopy['MarketCopyReference'],$marketingcopy['MarketCopyType'],$marketingcopy['RecordSequence'],$marketingcopy['LanguageCode'],$marketingcopy['MarketCopySubCode'],$marketingcopy['MarketCopySubCodeReference']);
  $writer->writeSheetRow('MarketingCopy', $row);
 }
  
 //---------------- items -----------------
 $writer->writeSheetHeader('Items', array('PartNumber'=>'string','PartTerminologyID'=>'integer','BrandAAIAID'=>'string','PartTerminologyName'=>'string','ItemLevelGTIN'=>'string','GTINQualifier'=>'string','MinimumOrderQuantity'=>'integer','MinimumOrderQuantityUOM'=>'string','HazardousMaterialCode'=>'string','BaseItemID'=>'string','ItemEffectiveDate'=>'string','AvailableDate'=>'string','ACESApplications'=>'string','ItemQuantitySize'=>'integer','ItemQuantitySizeUOM'=>'string','ContainerType'=>'string','QuantityPerApplication'=>'integer','QuantityPerApplicationUOM'=>'string','BrandLabel'=>'string','VMRSBrandID'=>'string','UNSPC'=>'string'),        array('widths'=>array(12,16,12,29,15,12,20,25,21,12,15,15,16,15,20,13,20,24,10,13,9),'freeze_rows'=>1, 'freeze_columns'=>1,['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#c0c0c0'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00']));
 foreach($items as $partnumber=>$item)
 {
  $row=array($partnumber,$item['PartTerminologyID'],$item['BrandAAIAID'],$item['NicePartTerminologyName'],$item['ItemLevelGTIN'],$item['GTINQualifier'],$item['MinimumOrderQuantity'],$item['MinimumOrderQuantityUOM'],$item['HazardousMaterialCode'],$item['BaseItemID'],$item['ItemEffectiveDate'],$item['AvailableDate'],$item['ACESApplications'],$item['ItemQuantitySize'],$item['ItemQuantitySizeUOM'],$item['ContainerType'],$item['QuantityPerApplication'],$item['QuantityPerApplicationUOM'],$item['BrandLabel'],$item['VMRSBrandID'],$item['UNSPSC']);
  $writer->writeSheetRow('Items', $row);
 }

//----------- descriptions ---------------
 $writer->writeSheetHeader('Descriptions', array('PartNumber'=>'string','Description'=>'string','DescriptionCode'=>'string','Sequence'=>'integer','LanguageCode'=>'string'), array('widths'=>array(12,56,14,9,13),'freeze_rows'=>1, 'freeze_columns'=>1,['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],['fill'=>'#ffff00']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['descriptions'] as $description)
  {
   $row=array($partnumber,$description['Description'],$description['DescriptionCode'],$description['Sequence'],$description['LanguageCode']);
   $writer->writeSheetRow('Descriptions', $row);
  }
 }

 
//-------- prices ---------
 $writer->writeSheetHeader('Prices', array('PartNumber'=>'string','PriceSheetNumber'=>'string','Price'=>'0.00','PriceUOM'=>'string','PriceType'=>'string','CurrencyCode'=>'string','EffectiveDate'=>'string','ExpirationDate'=>'string','PriceTypeDescription'=>'string','PriceBreak'=>'integer','PriceBreakUOM'=>'string','PriceMultiplier'=>'0.000'), array('widths'=>array(11,16,5.00,9,9,12,11,13,19,10,14,12),'freeze_rows'=>1, 'freeze_columns'=>1,['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#0000ff']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['prices'] as $price)
  {
   $row=array($partnumber,$price['PriceSheetNumber'],$price['Price'],$price['PriceUOM'],$price['PriceType'],$price['CurrencyCode'],$price['EffectiveDate'],$price['ExpirationDate'],$price['PriceTypeDescription'],$price['PriceBreak'],$price['PriceBreakUOM'],$price['PriceMultiplier']);
   $writer->writeSheetRow('Prices', $row);
  }
 }
 
 //-------------- expi ---------
 $writer->writeSheetHeader('EXPI', array('PartNumber'=>'string','EXPICode'=>'string','EXPIValue'=>'string','LanguageCode'=>'string'), array('widths'=>array(13,10,20,14),'freeze_rows'=>1, 'freeze_columns'=>1,    ['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['expis'] as $expi)
  {
   $row=array($partnumber,$expi['EXPICode'],$expi['EXPIValue'],$expi['LanguageCode']);
   $writer->writeSheetRow('EXPI', $row);
  }
 }
 
 //-------------- attributes ---------
 $writer->writeSheetHeader('Attributes', array('PartNumber'=>'string', 'AttributeID'=>'string', 'AttributeValue'=>'string',             'AttributeUOM'=>'string',             'PADBAttribute'=>'string', 'RecordNumber'=>'integer', 'StyleID'=>'integer', 'MultiValueQuantity'=>'integer', 'MultiValueSequence'=>'integer','LanguageCode'=>'string'), array('widths'=>array(11,26,27,13,14,14,7,17,19,14),'freeze_rows'=>1, 'freeze_columns'=>1, ['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'], ['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['attributes'] as $attribute)
  {
   $row=array($partnumber,$attribute['AttributeID'],$attribute['AttributeValue'],$attribute['AttributeUOM'],$attribute['PADBAttribute'],$attribute['RecordNumber'],$attribute['StyleID'],$attribute['MultiValueQuantity'],$attribute['MultiValueSequence'],$attribute['LanguageCode']);
   $writer->writeSheetRow('Attributes', $row);
  }
 }
 
//-------------- packages ---------
 $writer->writeSheetHeader('Packages', array('PartNumber'=>'string',
     'PackageUOM'=>'string',
     'QuantityofEaches'=>'integer',
     'InnerQuantity'=>'integer',
     'InnerQuantityUOM'=>'string',
     'Weight'=>'0.00',
     'WeightsUOM'=>'string',
     'PackageLevelGTIN'=>'string',
     'PackageBarCodeCharacters'=>'string',
     'ShippingHeight'=>'0.00',
     'ShippingWidth'=>'0.00',
     'ShippingLength'=>'0.00',
     'DimensionsUOM'=>'string',
     'DimensionalWeight'=>'0.00',
     'WeightVariance'=>'0.00',
     'StackingFactor'=>'0.00',
     'ElectronicProductCode'=>'string',
     'MerchandisingHeight'=>'0.00',
     'MerchandisingWidth'=>'0.00',
     'MerchandisingLength'=>'0.00',
     'ShippingScope'=>'string',
     'Bulk'=>'string',
     'RegulatingCountry'=>'string',
     'TransportMethod'=>'string',
     'Regulated'=>'string',
     'Description'=>'string',
     'HazardousMaterialCodeQualifier'=>'string',
     'HazardousMaterialDescription'=>'string',
     'HazardousMaterialLabelCode'=>'string',
     'ShippingName'=>'string',
     'UNNAIDCode'=>'string',
     'HazardousPlacardNotation'=>'string',
     'OuterPackageLabel'=>'string',
     'TextMessage'=>'string',
     'RegulationsExemptionCode'=>'string',
     'PackingGroupCode'=>'string',
     'WHMISFreeText'=>'string',
     'WHMISCode'=>'string',
     'LanguageCode'=>'string'), array('widths'=>array(13,13,16,12,17,7,13,17,25,14,14,14,16,18,15,14,29,19,19,19,14,5,17,15,10,15,28,27,26,14,13,24,18,13,25,18,31,12,14),'freeze_rows'=>1, 'freeze_columns'=>1,['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],             ['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['packages'] as $package)
  {
   $row=array($partnumber,$package['PackageUOM'], $package['QuantityofEaches'], $package['InnerQuantity'], $package['InnerQuantityUOM'],$package['Weight'],$package['WeightsUOM'],$package['PackageLevelGTIN'],$package['PackageBarCodeCharacters'],$package['ShippingHeight'],$package['ShippingWidth'],$package['ShippingLength'],$package['DimensionsUOM'],$package['DimensionalWeight'],$package['WeightVariance'],$package['StackingFactor'], $package['ElectronicProductCode'], $package['MerchandisingHeight'],$package['MerchandisingWidth'],$package['MerchandisingLength'],$package['ShippingScope'],$package['Bulk'],$package['RegulatingCountry'],$package['TransportMethod'],$package['Regulated'],$package['Description'],$package['HazardousMaterialCodeQualifier'],$package['HazardousMaterialDescription'],$package['HazardousMaterialLabelCode'],$package['ShippingName'],$package['UNNAIDCode'],$package['HazardousPlacardNotation'],$package['OuterPackageLabel'],$package['TextMessage'],$package['RegulationsExemptionCode'],$package['PackingGroupCode'],$package['WHMISFreeText'],$package['WHMISCode'],$package['LanguageCode']);
   $writer->writeSheetRow('Packages', $row);
  }
 }

//------------- kits ----------------
 $writer->writeSheetHeader('Kits', array('PartNumber'=>'string','Description'=>'string','DescriptionCode'=>'string','QuantityInKit'=>'integer','QuantityInKitUOM'=>'string','SoldSeparately'=>'string','LanguageCode'=>'string','ComponentPartTerminologyID'=>'integer','SequenceCode'=>'integer','ComponentPartNumber'=>'string','ComponentBrand'=>'string','ComponentBrandLabel'=>'string','ComponentSubBrand'=>'string','ComponentSubBrandLabel'=>'string'), array('widths'=>array(11,41,15,12,17,14,14,27,14,21,16,20,19,24),'freeze_rows'=>1, 'freeze_columns'=>1, ['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#00ff00'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['kits'] as $kit)
  {
   $row=array($partnumber,$kit['Description'],$kit['DescriptionCode'],$kit['QuantityInKit'],$kit['QuantityInKitUOM'],$kit['SoldSeparately'],$kit['LanguageCode'],$kit['ComponentPartTerminologyID'],$kit['SequenceCode'],$kit['ComponentPartNumber'],$kit['ComponentBrand'],$kit['ComponentBrandLabel'],$kit['ComponentSubBrand'],$kit['ComponentSubBrandLabel']);
   $writer->writeSheetRow('Kits', $row);
  }
 }
 
 //------------- interchnges ---------
 $writer->writeSheetHeader('Interchanges', array('PartNumber'=>'string','CompetitorPartNumber'=>'string','BrandAAIAID'=>'string','InterchangeQuantity'=>'integer','UOM'=>'string','ReferenceItem'=>'string','InterchangeNotes'=>'string','BrandLabel'=>'string','ItemEquivalentUOM'=>'string','LanguageCode'=>'string','SubBrandAAIAID'=>'string','SubBrandLabel'=>'string','VMRSBrandID'=>'string','QualityGradeLevel'=>'string','InternalNotes'=>'string'), array('widths'=>array(12,21,12,18,6,13,16,11,18,14,16,14,14,17,12),'freeze_rows'=>1, 'freeze_columns'=>1,['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['interchanges'] as $interchange)
  {
   $row=array($partnumber,$interchange['CompetitorPartNumber'],$interchange['BrandAAIAID'],$interchange['InterchangeQuantity'],$interchange['UOM'],$interchange['ReferenceItem'],$interchange['InterchangeNotes'],$interchange['BrandLabel'],$interchange['ItemEquivalentUOM'],$interchange['LanguageCode'],$interchange['SubBrandAAIAID'],$interchange['SubBrandLabel'],$interchange['VMRSBrandID'],$interchange['QualityGradeLevel'],$interchange['InternalNotes']);
   $writer->writeSheetRow('Interchanges', $row);
  }
 }
 
 //-------------- digitalassets ----------
 $writer->writeSheetHeader('DigitalAssets',array('PartNumber'=>'string','FileName'=>'string','AssetType'=>'string','AssetID'=>'string','FileType'=>'string','Representation'=>'string','FileSize'=>'integer','Resolution'=>'string','ColorMode'=>'string','Background'=>'string','OrientationView'=>'string','AssetHeight'=>'integer','AssetWidth'=>'integer','UOM'=>'string','FilePath'=>'string','AssetDate'=>'string','AssetDateType'=>'string','Country'=>'string','LanguageCode'=>'string','URI'=>'string','Duration'=>'string','DurationUOM'=>'string','Frame'=>'integer','TotalFrames'=>'integer','Plane'=>'integer','Hemisphere'=>'string','Plunge'=>'integer','TotalPlanes'=>'integer','Description'=>'string','DescriptionCode'=>'string','DescriptionLanguageCode'=>'string'), array('widths'=>array(12,30,10,37,8,15,9,10,11,11,16,11,11,5,15,10,14,8,14,83,8,13,6,11,6,12,7,11,22,16,24),'freeze_rows'=>1, 'freeze_columns'=>1, ['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['digitalassets'] as $digitalasset)
  {
   $row=array($partnumber,
$digitalasset['FileName'],
$digitalasset['AssetType'],
$digitalasset['AssetID'],
$digitalasset['FileType'],
$digitalasset['Representation'],
$digitalasset['FileSize'],
$digitalasset['Resolution'],
$digitalasset['ColorMode'],
$digitalasset['Background'],
$digitalasset['OrientationView'],
$digitalasset['AssetHeight'],
$digitalasset['AssetWidth'],
$digitalasset['UOM'],
$digitalasset['FilePath'],
$digitalasset['AssetDate'],
$digitalasset['AssetDateType'],
$digitalasset['Country'],
$digitalasset['LanguageCode'],
$digitalasset['URI'],
$digitalasset['Duration'],
$digitalasset['DurationUOM'],
$digitalasset['Frame'],
$digitalasset['TotalFrames'],
$digitalasset['Plane'],
$digitalasset['Hemisphere'],
$digitalasset['Plunge'],
$digitalasset['TotalPlanes'],
$digitalasset['Description'],
$digitalasset['DescriptionCode'],
$digitalasset['DescriptionLanguageCode']);
   $writer->writeSheetRow('DigitalAssets', $row);
  }
 }

 //-------- errors ------------
 
 if(count($errors))
 {
  $writer->writeSheetHeader('Errors', array('Error Type'=>'string','Description'=>'string'), array('widths'=>array(37,100),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
  foreach($errors as $error)
  {
   $row=explode("\t",$error);
   $writer->writeSheetRow('Errors', $row);
  }
 }
 
 
 
 $xlsxdata=$writer->writeToString();
 $streamXLSX=true; 
}

if((count($errors)>0 && !isset($_POST['ignorelogic'])) || count($schemaresults)>0 || !$validUpload)
{
 $streamXLSX=false; ?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php if (isset($_SESSION['userid'])){include('topnav.php');} ?>

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
                        <h3 class="card-header text-start">Convert PIES (7.1) xml to Excel spreadsheet</h3>

                        <div class="card-body">
                            <h5 class="alert alert-secondary">Step 2: Analyze results and download spreadsheet</h5>
                            <div class="alert alert-info"><em>Validation done against PCdb version: <?php echo $pcdbVersion;?></em></div>
                            <?php
                            if(!$validUpload){?>
                            <div style="padding:10px;background-color:#FF0000;font-size:1.5em;">Your input file has problems:</div>
                            <table class="table">
                            <?php
                            foreach($inputFileLog as $result)
                            { // render each element of schema problems into a table
                                echo '<tr><td style="text-align:left;background-color:#FF0000;">'.$result.'</td></tr>';
                            }?>
                            </table>

                            <?php }?>

                            <?php if(count($schemaresults)>0){?>
                            <div style="padding:10px;background-color:#FF8800;font-size:1.5em;">Your input data causes schema (XSD) problems. Here they are:</div>
                            <table class="table"><?php
                            foreach($schemaresults as $result)
                            { // render each element of schema problems into a table
                             echo '<tr><td style="text-align:left;background-color:#FF8800;">'.$result.'</td></tr>';
                            }
                            ?>
                            </table>
                            <?php }

                            if(count($errors)>0 && !isset($_POST['ignorelogic'])){?>
                            <div style="padding:10px;background-color:yellow;font-size:1.5em;"><?php if(count($schemaresults)==0){echo 'XSD-validated output was produced. However, ';} ?>your input data contains logic problems. Here are the ones we detected:</div>
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
<?php 
}
$logs->logSystemEvent('rhubarb', 0, 'file:'.$originalFilename.';items:'.count($items).';xsd:'.count($schemaresults).';logic:'.count($errors).';by:'.$_SERVER['REMOTE_ADDR']);

if($streamXLSX)
{   
 $filename='Rhubarb_7_1_C_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>