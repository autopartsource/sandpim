<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/XLSXWriterClass.php');
$navCategory = 'import/export';

$anonSizeLimit=15000000;
session_start();

$pim = new pim();
$logs=new logs();
$pcdb = new pcdb();
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
  
  
  
  
  
  
  $items[$partnumber]=array('PartTerminologyID'=>$partterminologyid,'BrandAAIAID'=>$brandaaiaid,'ItemLevelGTIN'=>$itemlevelgtin,'GTINQualifier'=>$gtinqualifier,'MinimumOrderQuantity'=>$minimumorderquantity,'MinimumOrderQuantityUOM'=>$minimumorderquantityuom,'HazardousMaterialCode'=>$hazardousmaterialcode,'BaseItemID'=>$baseitemid,'ItemEffectiveDate'=>$itemeffectivedate,'AvailableDate'=>$availabledate,'ACESApplications'=>$ACESapplications,'ItemQuantitySize'=>$itemquantitysize,'ItemQuantitySizeUOM'=>$itemquantitysizeuom,'ContainerType'=>$containertype,'QuantityPerApplication'=>$quantityperapplication,'QuantityPerApplicationUOM'=>$quantityperapplicationuom,'BrandLabel'=>$brandlabel,'VMRSBrandID'=>$VMRSbrandid,'UNSPSC'=>$UNSPSC,'NicePartTerminologyName'=>$niceparttypename,'descriptions'=>$descriptions,'prices'=>$prices,'expis'=>$expis,'attributes'=>$attributes,'packages'=>$packages);
 } // item element foreach
  
 $writer = new XLSXWriter();
 $writer->setAuthor('SandPIM'); 
 
 //$writer->writeSheetHeader('Header', array('RhubarbTemplate'=>'string','7.1'=>'string'),array(['fill'=>'#ff0000']));
// $row=array('RhubarbTemplate','7.1'); $writer->writeSheetRow('Header', $row);
 $row=array('TechnicalContact',$header['TechnicalContact']); $writer->writeSheetRow('Header', $row);
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
 
 
 $writer->writeSheetHeader('Items', array('PartNumber'=>'string','PartTerminologyID'=>'integer','BrandAAIAID'=>'string','PartTerminologyName'=>'string','ItemLevelGTIN'=>'string','GTINQualifier'=>'string','MinimumOrderQuantity'=>'integer','MinimumOrderQuantityUOM'=>'string','HazardousMaterialCode'=>'string','BaseItemID'=>'string','ItemEffectiveDate'=>'string','AvailableDate'=>'string','ACESApplications'=>'string','ItemQuantitySize'=>'integer','ItemQuantitySizeUOM'=>'string','ContainerType'=>'string','QuantityPerApplication'=>'integer','QuantityPerApplicationUOM'=>'string','BrandLabel'=>'string','VMRSBrandID'=>'string','UNSPC'=>'string'),        array('freeze_rows'=>1, 'freeze_columns'=>1,['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#c0c0c0'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00']));
 foreach($items as $partnumber=>$item)
 {
  $row=array($partnumber,$item['PartTerminologyID'],$item['BrandAAIAID'],$item['NicePartTerminologyName'],$item['ItemLevelGTIN'],$item['GTINQualifier'],$item['MinimumOrderQuantity'],$item['MinimumOrderQuantityUOM'],$item['HazardousMaterialCode'],$item['BaseItemID'],$item['ItemEffectiveDate'],$item['AvailableDate'],$item['ACESApplications'],$item['ItemQuantitySize'],$item['ItemQuantitySizeUOM'],$item['ContainerType'],$item['QuantityPerApplication'],$item['QuantityPerApplicationUOM'],$item['BrandLabel'],$item['VMRSBrandID'],$item['UNSPSC']);
  $writer->writeSheetRow('Items', $row);
 }

 $writer->writeSheetHeader('Descriptions', array('PartNumber'=>'string','Description'=>'string','DescriptionCode'=>'string','Sequence'=>'integer','LanguageCode'=>'string'), array('freeze_rows'=>1, 'freeze_columns'=>1,['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],['fill'=>'#ffff00']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['descriptions'] as $description)
  {
   $row=array($partnumber,$description['Description'],$description['DescriptionCode'],$description['Sequence'],$description['LanguageCode']);
   $writer->writeSheetRow('Descriptions', $row);
  }
 }
  
//-------- prices ---------
 $writer->writeSheetHeader('Prices', array('PartNumber'=>'string','PriceSheetNumber'=>'string','Price'=>'0.00','PriceUOM'=>'string','PriceType'=>'string','CurrencyCode'=>'string','EffectiveDate'=>'string','ExpirationDate'=>'string','PriceTypeDescription'=>'string','PriceBreak'=>'integer','PriceBreakUOM'=>'string','PriceMultiplier'=>'0.000'), array('freeze_rows'=>1, 'freeze_columns'=>1,['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#0000ff']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['prices'] as $price)
  {
   $row=array($partnumber,$price['PriceSheetNumber'],$price['Price'],$price['PriceUOM'],$price['PriceType'],$price['CurrencyCode'],$price['EffectiveDate'],$price['ExpirationDate'],$price['PriceTypeDescription'],$price['PriceBreak'],$price['PriceBreakUOM'],$price['PriceMultiplier']);
   $writer->writeSheetRow('Prices', $row);
  }
 }
 
 //-------------- expi ---------
 $writer->writeSheetHeader('EXPI', array('PartNumber'=>'string','EXPICode'=>'string','EXPIValue'=>'string','LanguageCode'=>'string'), array('freeze_rows'=>1, 'freeze_columns'=>1,    ['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['expis'] as $expi)
  {
   $row=array($partnumber,$expi['EXPICode'],$expi['EXPIValue'],$expi['LanguageCode']);
   $writer->writeSheetRow('EXPI', $row);
  }
 }
 
 //-------------- attributes ---------
 $writer->writeSheetHeader('Attributes', array('PartNumber'=>'string', 'AttributeID'=>'string', 'AttributeValue'=>'string',             'AttributeUOM'=>'string',             'PADBAttribute'=>'string', 'RecordNumber'=>'integer', 'StyleID'=>'integer', 'MultiValueQuantity'=>'integer', 'MultiValueSequence'=>'integer','LanguageCode'=>'string'), array('freeze_rows'=>1, 'freeze_columns'=>1, ['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'], ['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['attributes'] as $attribute)
  {
   $row=array($partnumber,$attribute['AttributeID'],$attribute['AttributeValue'],$attribute['AttributeUOM'],$attribute['PADBAttribute'],$attribute['RecordNumber'],$attribute['StyleID'],$attribute['MultiValueQuantity'],$attribute['MultiValueSequence'],$attribute['LanguageCode']);
   $writer->writeSheetRow('Attributes', $row);
  }
 }
 
//-------------- packages ---------
 $writer->writeSheetHeader('Packages', array(
     'PartNumber'=>'string','PackageUOM'=>'string','QuantityofEaches'=>'integer','InnerQuantity'=>'integer',
     'InnerQuantityUOM'=>'string','Weight'=>'0.00','WeightsUOM'=>'string','PackageLevelGTIN'=>'string','PackageBarCodeCharacters'=>'string',
     'ShippingHeight'=>'0.00','ShippingWidth'=>'0.00','ShippingLength'=>'0.00','DimensionsUOM'=>'string',
       'DimensionalWeight'=>'0.00','WeightVariance'=>'0.00','StackingFactor'=>'0.00','ElectronicProductCode'=>'string', 
       'MerchandisingHeight'=>'0.00','MerchandisingWidth'=>'0.00','MerchandisingLength'=>'0.00','ShippingScope'=>'string',
       'Bulk'=>'string','RegulatingCountry'=>'string','TransportMethod'=>'string','Regulated'=>'string','Description'=>'string',
       'HazardousMaterialCodeQualifier'=>'string','HazardousMaterialDescription'=>'string','HazardousMaterialLabelCode'=>'string',
       'ShippingName'=>'string','UNNAIDCode'=>'string','HazardousPlacardNotation'=>'string','OuterPackageLabel'=>'string',
       'TextMessage'=>'string','RegulationsExemptionCode'=>'string','PackingGroupCode'=>'string','WHMISFreeText'=>'string',
       'WHMISCode'=>'string','LanguageCode'=>'string'), array('freeze_rows'=>1, 'freeze_columns'=>1,    
             ['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],
             ['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],
             ['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],
             ['fill'=>'#ffff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],
             ['fill'=>'#00ff00'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],
             ['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],
             ['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],
             ['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],
           ['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff'],
           ['fill'=>'#0000ff'],['fill'=>'#0000ff'],['fill'=>'#0000ff']));
 foreach($items as $partnumber=>$item)
 {
  foreach($item['packages'] as $package)
  {
   $row=array($partnumber,
       $package['PackageUOM'],
       $package['QuantityofEaches'],
       $package['InnerQuantity'],
       $package['InnerQuantityUOM'],
       $package['Weight'],
       $package['WeightsUOM'],
       $package['PackageLevelGTIN'],
       $package['PackageBarCodeCharacters'],
       $package['ShippingHeight'],
       $package['ShippingWidth'],
       $package['ShippingLength'],
       $package['DimensionsUOM'],
       $package['DimensionalWeight'],
       $package['WeightVariance'],
       $package['StackingFactor'], 
       $package['ElectronicProductCode'], 
       $package['MerchandisingHeight'],
       $package['MerchandisingWidth'],
       $package['MerchandisingLength'],
       $package['ShippingScope'],
       $package['Bulk'],
       $package['RegulatingCountry'],
       $package['TransportMethod'],
       $package['Regulated'],
       $package['Description'],
       $package['HazardousMaterialCodeQualifier'],
       $package['HazardousMaterialDescription'],
       $package['HazardousMaterialLabelCode'],
       $package['ShippingName'],
       $package['UNNAIDCode'],
       $package['HazardousPlacardNotation'],
       $package['OuterPackageLabel'],
       $package['TextMessage'],
       $package['RegulationsExemptionCode'],
       $package['PackingGroupCode'],
       $package['WHMISFreeText'],
       $package['WHMISCode'],
       $package['LanguageCode']);


   $writer->writeSheetRow('Packages', $row);
  }
 }
 
 
 
 
 //-------- errors ------------
 
 if(count($errors))
 {
  $writer->writeSheetHeader('Errors', array('Error Type'=>'string','Description'=>'string'), array('freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
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

        <!-- Header -->
        <h1>Convert PIES (7.1) xml to Excel spreadsheet</h1>
        <h2>Step 2: Analyze results and download spreadsheet</h2>
        <div style="font-style: italic;">Validation done against PCdb version: <?php echo $pcdbVersion;?></div>
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
    

                <?php
                if(!$validUpload){?>
                <div style="padding:10px;background-color:#FF0000;font-size:1.5em;">Your input file has problems:</div>
                <table>
                <?php
                foreach($inputFileLog as $result)
                { // render each element of schema problems into a table
                    echo '<tr><td style="text-align:left;background-color:#FF0000;">'.$result.'</td></tr>';
                }?>
                </table>
                
                <?php }?>
                
                <?php if(count($schemaresults)>0){?>
                <div style="padding:10px;background-color:#FF8800;font-size:1.5em;">Your input data causes schema (XSD) problems. Here they are:</div>
                <table><?php
                foreach($schemaresults as $result)
                { // render each element of schema problems into a table
                 echo '<tr><td style="text-align:left;background-color:#FF8800;">'.$result.'</td></tr>';
                }
                ?>
                </table>
                <?php }
                
                if(count($errors)>0 && !isset($_POST['ignorelogic'])){?>
                <div style="padding:10px;background-color:yellow;font-size:1.5em;"><?php if(count($schemaresults)==0){echo 'XSD-validated output was produced. However, ';} ?>your input data contains logic problems. Here are the ones we detected:</div>
                <table><?php
                foreach($errors as $error)
                {
                    echo '<tr><td style="text-align:left;background-color:yellow;">'.$error.'</td></tr>';
                }
                ?>
                </table>
                <?php }?>
                 
            </div>

            <div class="contentRight"></div>
        </div>

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