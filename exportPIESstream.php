<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/pricingClass.php');
include_once('./class/assetClass.php');
include_once('./class/packagingClass.php');
include_once('./class/PIESgeneratorClass.php');

$vcdb = new vcdb;
$pim = new pim;
$pricing = new pricing;
$assets = new asset;
$packaging=new packaging;
$PIESgenerator=new PIESgenerator();


// explicit list of parts

// list of part categories (to query part table)

// list of app categories (to extract a list of items from)

// list of parttypeid's (to query part table)

// 


//receiver profile will hold CSS-style elements to convey into the PIES xml
//
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

$profile=$pim->getReceiverprofileById(intval($_POST['receiverprofile']));
$profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';

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

$partnumbers=array();
if($_POST['exporttype']=='itemlist')
{
    $lines=explode("\r\n",$_POST['parts']); $linenumber=0;
    foreach($lines as $line)
    {
        $linenumber++;
        if($pim->getPart(trim($line)))
        {
            $partnumbers[]=trim(strtoupper($line));
        }
        else
        {// invalid part - add it to errors list
            
            $logicerrors[]= 'Input part number: '.$line.' on line '.$linenumber.' is not valid. It was excluded from the export.';
        }
    }
}


$items=array();

//--------------------- marketing copy -------------------------------    



foreach($partnumbers as $partnumber)
{
    $item=array();
    $part=$pim->getPart($partnumber);
    
    $item['ItemLevelGTIN']=$part['GTIN'];
    $item['GTINQualifier']='UP';
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
 print_r($packages);
    
    
    
    
 //--------------------- kits -------------------------------    
 
 //--------------------- interchanges -------------------------------    

 //--------------------- assets -------------------------------    
    
    $digialassetconnections=$assets->getAssetsConnectedToPart($partnumber);
    if($digialassetconnections && count($digialassetconnections))
    {
     foreach($digialassetconnections as $digitalassetconnection)
     {
      $digitalassetrecords=$assets->getAssetRecordsByAssetid($digitalassetconnection['assetid']);
      foreach($digitalassetrecords as $digitalassetrecord)
      {  
       $asset=array();

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
       $digitalasset['UOM']=$digitalassetrecord['dimensionUOM'];
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
       $digitalasset['AssetDateType']='XXX';//$digitalassetrecord[''];
       //$digitalasset['Country']=$digitalassetrecord[''];
       //$digitalasset['LanguageCode']=$digitalassetrecord[''];
            
       $item['assets'][]=$digitalasset;
      }
     }
    }
    
 $items[$partnumber]=$item;    
}

$doc=$PIESgenerator->createPIESdoc($header,$marketingcopys,$items);//,$descriptions,$prices,$expi,$attributes,$packages,$kits,$interchanges,$assets);
$doc->formatOutput=true;
$piesxml=$doc->saveXML();    

$schemavalidated=true;   
libxml_use_internal_errors(true);
if(!$doc->schemaValidate('PIES_7_1_r4_XSD.xsd'))
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

 
if(isset($schemaresults) && count($schemaresults)>0)
{
 echo '<div style="margin:10px; background-color:#ffc0c0;"><div style="font-size:1.5em;font-weight:bold;">Scheama (XSD) problems</div>';
 foreach($schemaresults as $result)
 { // render each element of schema problems into a table
  echo '<div style="padding:8px">'.$result.'</div>';
 }
 echo '</div>';
}
else
{
 echo '<textarea rows="20" cols="150">'.$piesxml.'</textarea>';
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