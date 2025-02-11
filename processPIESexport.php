<?php
/*
 * intended to be executed from the command-line be a cron call ("php processPIESexport.php")
 * on a cycle (likely every 5 or 10 minutes). It will query the db for the oldest job that 
 * is status "started" and execute it.
 * 
 * On my fedora 31 box, I had to apply a read/write SELinux policy to the 
 * directory where apache can write the exported files (/var/www/html/ACESexports
 * semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/ACESexports(/.*)?"
 * restorecon -Rv /var/www/html/ACESexports/
 * 
 * 
 */


include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
$pim = new pim();
$jobs=$pim->getBackgroundjobs('PIESxmlExport','started');


if(count($jobs))
{
 include_once(__DIR__.'/class/pcdbClass.php');
 include_once(__DIR__.'/class/pricingClass.php');
 include_once(__DIR__.'/class/packagingClass.php');
 include_once(__DIR__.'/class/assetClass.php');
 include_once(__DIR__.'/class/logsClass.php');
 include_once(__DIR__.'/class/PIES7_1GeneratorClass.php');

 $pcdb=new pcdb();
 $pricing=new pricing();
 $packaging=new packaging();
 $assets=new asset();
 $logs=new logs();
 $generator=new PIESgenerator();

 $file_name=$jobs[0]['outputfile'];
 $jobid=$jobs[0]['id'];
 $pim->updateBackgroundjobRunning($jobid, date('Y-m-d H:i:s'));
 
 $parameters=array();
 $parameterbits=explode(';',$jobs[0]['parameters']);
 foreach($parameterbits as $parameterbit)
 {
  $temp=explode(':',$parameterbit); if(count($temp)==2){$parameters[$temp[0]]=$temp[1];}
 }

 $receiverprofileid=intval($parameters['receiverprofile']);
 $profile=$pim->getReceiverprofileById($receiverprofileid);
 $profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';
 $profilename=$profile['name'];
 $partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
 $partnumbers=$pim->getPartnumbersByPartcategories($partcategories);
 
 $filename=$jobs[0]['outputfile'];
 $profileelements=explode(';',$profiledata);
 $keyedprofile=array();
 foreach($profileelements as $profileelement)
 {
  $bits=explode(':',$profileelement);
  if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}
 } 
// -------------------------------------
 
 $header=array();
 $items=array();
 $marketingcopys=array();
 $logicerrors=array();
 
 $generatoroptions=array('ProfileName'=>$profilename);

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


 foreach($partnumbers as $partnumber)
 {
  $item=array();
  $part=$pim->getPart($partnumber);
    
  $item['ItemLevelGTIN']=$part['GTIN'];
  if(strlen($part['GTIN'])==12)
  {
   $item['ItemLevelGTIN']='00'.$part['GTIN'];
  }
  
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

 
 $doc=$generator->createPIESdoc($header,$marketingcopys,$items,$generatoroptions);
 $doc->formatOutput=true;
 $piesxmlstring=$doc->saveXML();    

 $newdoc=new DOMDocument();
 $newdoc->loadXML($piesxmlstring); 
 // I do realize that this extra step seems redundant. Running the schema validation 
 // directly on the original object failed because of namespace problems that 
 // I could not resolve (or understand). Exporting the original object's xml 
 // to a text string and then re-importing it to a new DOM object was the
 // work-around that I found.

 
 $schemaresults=array();
 libxml_use_internal_errors(true);
 if(!$newdoc->schemaValidate(__DIR__.'/PIES_7_1_r4_XSD.xsd'))
 {
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

  //echo 'schema validations failed';
  foreach($schemaresults as $schemaresult)
  {
      $pim->logBackgroundjobEvent($jobid, $schemaresult);
  }   
  $pim->updateBackgroundjobDone($jobid,'failed',date('Y-m-d H:i:s'));
  $logs->logSystemEvent('Export', 0, 'PIES file ['.$filename.'] (jobid:'.$jobid.') export failed (schema violation) during houskeeper processing; parts:'.count($partnumbers));
 }
 else
 {
  //echo 'schema validations success';
  $newdoc->formatOutput=true;
  $writeresult=$newdoc->save($filename);
  if($writeresult)
  {
   //echo 'output file created ('.$writeresult.' bytes)';
   $pim->updateBackgroundjobDone($jobid,'complete',date('Y-m-d H:i:s'));
   $pim->logBackgroundjobEvent($jobid, 'PIESS file ['.$filename.'] created containing '.count($partnumbers).' parts');
   $logs->logSystemEvent('Export', 0, 'PIES file ['.$filename.'] (jobid:'.$jobid.') exported by houskeeper; parts:'.count($partnumbers));
  }
  else
  {  // writing the output xml file failed
   //echo 'output file write failed';
   $pim->updateBackgroundjobDone($jobid,'failed',date('Y-m-d H:i:s'));
   $pim->logBackgroundjobEvent($jobid, 'file write failed ['.$filename.']' );
   $logs->logSystemEvent('Export', 0, 'PIES file ['.$filename.'] (jobid:'.$jobid.') export failed (write permission denied) during houskeeper processing; parts:'.count($partnumbers));
  }
 }
}
else
{
 echo"no jobs pending\r\n";    
}
?>
