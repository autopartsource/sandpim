<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/pricingClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/assetClass.php');
include_once('./class/packagingClass.php');
include_once('./class/PIES7_1GeneratorClass.php');

$vcdb = new vcdb;
$pim = new pim;
$pricing = new pricing;
$interchange= new interchange;
$assets = new asset;
$packaging=new packaging;
$PIESgenerator=new PIESgenerator();


$streamXML=true;

if(isset($_GET['showxml']))
{
 $streamXML=false;   
}


//receiver profile will hold CSS-style elements to convey into the PIES xml
/*
 * 
 * 
 * 
 * 
 * 
 * 
 */

$header=array();
$marketingcopys=array();

$profile=$pim->getReceiverprofileById(intval($_GET['receiverprofile']));
$profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';

$partcategories=$pim->getReceiverprofilePartcategories($profile['id']);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories);


$marketingcopyrecords=$pim->getMarketingcopyByReceiverprofileId($profile['id']);

foreach($marketingcopyrecords as $marketingcopyrecord)
{
 $marketingcopy=array();
 $marketingcopy['MarketCopyContent']=$marketingcopyrecord['marketcopycontent'];
 $marketingcopy['MarketCopyCode']=$marketingcopyrecord['marketcopycode'];
 $marketingcopy['MarketCopyReference']=$marketingcopyrecord['marketcopyreference'];
 //$marketingcopy['MarketCopySubCode']=$marketingcopyrecord[''];
 //$marketingcopy['MarketCopySubCodeReference']=$marketingcopyrecord[''];
 $marketingcopy['MarketCopyType']=$marketingcopyrecord['marketcopytype'];
 $marketingcopy['RecordSequence']=$marketingcopyrecord['recordsequence'];
 $marketingcopy['LanguageCode']=$marketingcopyrecord['languagecode'];
 $marketingcopys[]=$marketingcopy;
}

//print_r($marketingcopyrecords);

$elements=explode(';',$profiledata);
foreach($elements as $element)
{
    $bits=explode(':',$element);
    if(count($bits)==2)
    {
        $header[$bits[0]]=$bits[1];
    }
}
$header['BlanketEffectiveDate']= date('Y-m-d');
$header['PAdbVersionDate']=date('Y-m-d');
$header['PCdbVersionDate']=date('Y-m-d');

$logicerrors=array();

$items=array();

//--------------------- marketing copy -------------------------------    

foreach($partnumbers as $partnumber)
{
    $item=array();
    $part=$pim->getPart($partnumber);
    $item['externalpart']=$pim->receiverPart($profile['id'], $partnumber);
    
    if($part['GTIN']!='')
    {
        $item['ItemLevelGTIN']=$part['GTIN'];
        $item['GTINQualifier']='UP';
        if(strlen($item['ItemLevelGTIN'])==12){$item['ItemLevelGTIN']='00'.$item['ItemLevelGTIN'];}
    }
    
    
    $item['PartTerminologyID']=$part['parttypeid'];
    $item['BrandAAIAID']=$part['brandid'];

//'MinimumOrderQuantity'
//'MinimumOrderQuantityUOM'
//'ACESApplications'
//'ItemQuantitySize'
//'ItemQuantitySizeUOM'
//'ContainerType'
//'ItemEffectiveDate'
//'AvailableDate'
//'UNSPSC'

//--------------------- descriptions -------------------------------    
    
    $descriptions=$pim->getPartDescriptions($partnumber);
    foreach($descriptions as $description)
    {
     $partdescription=array();
     $partdescription['Description']=$description['description'];
     $partdescription['DescriptionCode']=$description['descriptioncode'];
     $partdescription['Sequence']=$description['sequence'];
     $partdescription['LanguageCode']=$description['languagecode'];
     $item['descriptions'][]=$partdescription;   
    }
    
//--------------------- prices -------------------------------    
    $prices=$pricing->getPricesByPartnumber($partnumber);
    if($prices && count($prices))
    {
     foreach($prices as $pricerecord)
     {
      $price=array();
      $price['PriceSheetNumber']=$pricerecord['pricesheetnumber'];
      $price['Price']=$pricerecord['amount'];
      $price['PriceUOM']=$pricerecord['priceuom'];
      $price['PriceType']=$pricerecord['pricetype'];
      $price['CurrencyCode']=$pricerecord['currency'];
      $price['EffectiveDate']=$pricerecord['effectivedate'];
      $price['ExpirationDate']=$pricerecord['expirationdate'];
      $item['prices'][]=$price;
     }
    }
 //--------------------- EXPI -------------------------------    

    if(trim($part['lifecyclestatus'])!='')
    {
        $item['expis'][]=array('EXPICode'=>'LIF','EXPIValue'=>trim($part['lifecyclestatus']));   
    }
    
 //--------------------- attributes -------------------------------    
    $partattributes=$pim->getPartAttributes($partnumber);
    if($partattributes && count($partattributes)>0)
    {
     foreach($partattributes as $partattribute)
     {
      $attribute['PADBAttribute']='N';
      $attribute['AttributeID']=$partattribute['name'];
      $attribute['AttributeValue']=$partattribute['value'];
 
      if($partattribute['PAID']>0)
      {
       $attribute['PADBAttribute']='Y';
       $attribute['AttributeID']=$partattribute['PAID'];
      }
      //$attribute['StyleID']=$partattributes[''];
      $attribute['AttributeUOM']=$partattribute['uom'];
      //$attribute['MultiValueQuantity']=$partattributes[''];
      //$attribute['MultiValueSequence']=$partattributes[''];
      //$attribute['LanguageCode']=$partattributes[''];
      //$attribute['RecordNumber']=$partattributes[''];
      if(trim($partattribute['value'])!=''){$item['attributes'][]=$attribute;}
     }
    }
    
 //--------------------- packages -------------------------------    
  $packages=$packaging->getPackagesByPartnumber($partnumber);

  foreach($packages as $package)  
  {
   $itempackage=array();   
   $itempackage['PackageUOM']=$package['packageuom'];
   $itempackage['QuantityofEaches']=round($package['quantityofeaches'],0);
   $itempackage['InnerQuantity']=$package['innerquantity'];
   $itempackage['InnerQuantityUOM']=$package['innerquantityuom'];
   $itempackage['Weight']=$package['weight'];
   $itempackage['WeightsUOM']=$package['weightsuom'];
   $itempackage['PackageLevelGTIN']=$package['packagelevelGTIN'];
   $itempackage['PackageBarCodeCharacters']=$package['packagebarcodecharacters'];
   $itempackage['ShippingHeight']=$package['shippingheight'];
   $itempackage['ShippingWidth']=$package['shippingwidth'];
   $itempackage['ShippingLength']=$package['shippinglength'];
   $itempackage['DimensionsUOM']=$package['dimensionsuom'];
   
   $item['packages'][]=$itempackage;   
  }
   
    
 //--------------------- kits -------------------------------    
 
 //--------------------- interchanges -------------------------------    

  $interchanges=$interchange->getInterchangeByPartnumber($partnumber);
  //    $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],
  //    'competitorpartnumber'=>$row['competitorpartnumber'],
  //    'brandAAIAID'=>$row['brandAAIAID'],
  //    'interchangequantity'=>$row['interchangequantity'],
  //    'uom'=>$row['uom'],
  //    'interchangenotes'=>base64_decode($row['interchangenotes']),
  //    'internalnotes'=>base64_decode($row['internalnotes']));

  
  foreach($interchanges as $interchangerecord)
  {
   $iteminterchange=array();
   $iteminterchange['CompetitorPartNumber']=$interchangerecord['competitorpartnumber'];
   $iteminterchange['BrandAAIAID']=$interchangerecord['brandAAIAID'];
   $iteminterchange['InterchangeQuantity']=$interchangerecord['interchangequantity'];
   $iteminterchange['UOM']=$interchangerecord['uom'];
   $iteminterchange['InterchangeNotes']=$interchangerecord['interchangenotes'];
   $item['interchanges'][]=$iteminterchange;         
  }
  
  
 //--------------------- assets -------------------------------    
    
  $digialassetconnections=$assets->getAssetsConnectedToPart($partnumber);
  if($digialassetconnections && count($digialassetconnections))
  {
   foreach($digialassetconnections as $digitalassetconnection)
   {
    $digitalassetrecords=$assets->getAssetRecordsByAssetid($digitalassetconnection['assetid']);
    foreach($digitalassetrecords as $digitalassetrecord)
    {  
     $digitalasset=array();
     $digitalasset['FileName']=$digitalassetrecord['filename'];
     $digitalasset['AssetID']=$digitalassetrecord['assetid'];
     $digitalasset['AssetType']=$digitalassetconnection['assettypecode'];
     $digitalasset['FileType']=$digitalassetrecord['fileType'];
     $digitalasset['Representation']=$digitalassetconnection['representation'];
     $digitalasset['FileSize']=$digitalassetrecord['filesize'];
     $digitalasset['Resolution']=$digitalassetrecord['resolution'];
     $digitalasset['ColorMode']=$digitalassetrecord['colorModeCode'];
     $digitalasset['Background']=$digitalassetrecord['background'];
     $digitalasset['OrientationView']=$digitalassetrecord['orientationViewCode'];
     $digitalasset['AssetHeight']=$digitalassetrecord['assetHeight'];
     $digitalasset['AssetWidth']=$digitalassetrecord['assetWidth'];
     $digitalasset['AssetDimensionsUOM']=$digitalassetrecord['dimensionUOM'];
     //$digitalasset['FilePath']=$digitalassetrecord[''];
     $digitalasset['URI']=$digitalassetrecord['uri'];
     //$digitalasset['Duration']=$digitalassetrecord[''];
     //$digitalasset['DurationUOM']=$digitalassetrecord[''];
     //$digitalasset['Frame']=$digitalassetrecord[''];
     //$digitalasset['TotalFrames']=$digitalassetrecord[''];
     //$digitalasset['Plane']=$digitalassetrecord[''];
     //$digitalasset['Hemisphere']=$digitalassetrecord[''];
     //$digitalasset['Plunge']=$digitalassetrecord[''];
     //$digitalasset['TotalPlanes']=$digitalassetrecord[''];
     //$digitalasset['Description']=$digitalassetrecord[''];
     //$digitalasset['DescriptionCode']=$digitalassetrecord[''];
     //$digitalasset['DescriptionLanguageCode']=$digitalassetrecord[''];
     $digitalasset['AssetDate']=$digitalassetrecord['createdDate'];
     $digitalasset['AssetDateType']='CRE';// according to PCdb20201030, valid options are: MOD="Modified", EXP="Expired", EFF="Effective", CRE="Created"
     //$digitalasset['Country']=$digitalassetrecord[''];
     $digitalasset['LanguageCode']=$digitalassetrecord['languagecode'];
            
     $item['assets'][]=$digitalasset;
    }
   }
  }
   
 $items[$partnumber]=$item;    
}

$doc=$PIESgenerator->createPIESdoc($header,$marketingcopys,$items);//,$descriptions,$prices,$expi,$attributes,$packages,$kits,$interchanges,$assets);
$doc->formatOutput=true;
$piesxml=$doc->saveXML();    

$newdoc=new DOMDocument();
$newdoc->loadXML($piesxml); 
// I do realize that this extra step seems redundant. Running the schema validation 
// directly on the original object failed because of namespace problems that 
// I could not resolve (or understand). Exporting the original object's xml 
// to a text string and then re-importing it to a new DOM object was the
// work-around that I found.

$schemavalidated=true;   
libxml_use_internal_errors(true);
if(!$newdoc->schemaValidate('PIES_7_1_r4_XSD.xsd'))
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

 
if((isset($schemaresults) && count($schemaresults)>0) || count($logicerrors)>0)
{
 echo '<div style="margin:10px; background-color:#ffc0c0;"><div style="font-size:1.5em;font-weight:bold;">Scheama (XSD) problems</div>';
 foreach($schemaresults as $result)
 { // render each element of schema problems into a table
  echo '<div style="padding:8px">'.$result.'</div>';
 }
 echo '</div>';
 
 
 
}
else
{// validated xml is ready to give to the user
 if($streamXML)
 {// download to file
  $filename='PIES_7_1_FULL_'.date('Y-m-d').'.xml';
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  header('Content-Type: application/octet-stream');
  header('Content-Length: ' . strlen($piesxml));
  header('Connection: close');    
  echo $piesxml;
 }
 else
 {// display in text area
  echo '<textarea rows="20" cols="150">'.$piesxml.'</textarea>';
 }
}

if(count($logicerrors))
{
 echo '<div style="margin:10px; background-color:#ffffc0;"><div style="font-size:1.5em;font-weight:bold;">General issues and logic problems</div>';
 foreach($logicerrors as $logicerror)
 {
  echo '<div style="padding:8px">'.$logicerror.'</div>';
 }
 echo '</div>';
}



?>
