<?php
include_once(__DIR__."/qdbClass.php");

class ACESgenerator
{
    
 function createACESdoc($header,$apps,$assets,$parttranslations,$partdescriptions,$options)
 {
     /* options keys
      * 'IncludeCosmeticApps' (boolenan)
      * 'IncludeCosmeticAttributes'  (boolenan)
      * 'SuppressDuplicates' (boolean) - apps identical in every way to something already in the output will not be exported
      */
  $qdb=new qdb;
  
  $includecosmeticattributes=false; if(array_key_exists('IncludeCosmeticAttributes',$options)){$includecosmeticattributes=$options['IncludeCosmeticAttributes'];}
  $includecosmeticapps=false; if(array_key_exists('IncludeCosmeticApps',$options)){$includecosmeticapps=$options['IncludeCosmeticApps'];}
  $suppressduplicateapps=false; if(array_key_exists('SuppressDuplicateApps',$options)){$suppressduplicateapps=$options['SuppressDuplicateApps'];}
  $profilename=''; if(array_key_exists('ProfileName',$options)){$profilename=$options['ProfileName'];}
  $descriptiontonmfrlabel=''; if(array_key_exists('DescriptionToMfrlabel',$options)){$descriptiontonmfrlabel=$options['DescriptionToMfrlabel'];}
  
  
  $existingapphashes=array();
  
  
  $doc = new DOMDocument('1.0', 'UTF-8');
  $comments=$doc->createComment("\r\n       File generated by SandPIM - an open-source PIM system\r\n       designed around the ACES and PIES standards.\r\n       Source repo for SandPIM can be found at\r\n       https://github.com/autopartsource/sandpim\r\n\r\n       receiver profile:".$profilename."\r\n\r\n");
  $doc->appendChild($comments);
  $root = $doc->createElement('ACES');
  $root = $doc->appendChild($root);
  $root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi' ,'http://www.w3.org/2001/XMLSchema-instance');
  $root->setAttribute('version','4.1');

 // ---------------------- header ------------------------------
  $headerElement =  new DOMElement('Header');
  $root->appendChild($headerElement);
  if(array_key_exists('Company', $header)){$companyElement=new DOMElement('Company',$header['Company']); $headerElement->appendChild($companyElement);}
  if(array_key_exists('SenderName', $header)){$sendernameElement=new DOMElement('SenderName',$header['SenderName']); $headerElement->appendChild($sendernameElement);}
  if(array_key_exists('SenderPhone', $header)){$senderphoneElement=new DOMElement('SenderPhone',$header['SenderPhone']); $headerElement->appendChild($senderphoneElement);}
  if(array_key_exists('TransferDate', $header)){$transferdateElement=new DOMElement('TransferDate',$header['TransferDate']); $headerElement->appendChild($transferdateElement);}
  if(array_key_exists('BrandAAIAID', $header)){$brandAAIAIDElement=new DOMElement('BrandAAIAID',$header['BrandAAIAID']); $headerElement->appendChild($brandAAIAIDElement);}
  if(array_key_exists('DocumentTitle', $header)){$documenttitleElement=new DOMElement('DocumentTitle',$header['DocumentTitle']); $headerElement->appendChild($documenttitleElement);}
  if(array_key_exists('EffectiveDate', $header)){$effectivedateElement=new DOMElement('EffectiveDate',$header['EffectiveDate']); $headerElement->appendChild($effectivedateElement);}
  if(array_key_exists('ApprovedFor', $header)){$approvedforElement=new DOMElement('ApprovedFor'); $headerElement->appendChild($approvedforElement); $countryElement=new DOMElement('Country',$header['ApprovedFor']);  $approvedforElement->appendChild($countryElement);}
  if(array_key_exists('SubmissionType', $header)){$submissiontypeElement=new DOMElement('SubmissionType',$header['SubmissionType']); $headerElement->appendChild($submissiontypeElement);}
  if(array_key_exists('MapperCompany', $header)){$mappercompanyElement=new DOMElement('MapperCompany',$header['MapperCompany']); $headerElement->appendChild($mappercompanyElement);}
  if(array_key_exists('MapperContact', $header)){$mappercontactElement=new DOMElement('MapperContact',$header['MapperContact']); $headerElement->appendChild($mappercontactElement);}
  if(array_key_exists('VcdbVersionDate', $header)){$vcdbversiondateElement=new DOMElement('VcdbVersionDate',$header['VcdbVersionDate']); $headerElement->appendChild($vcdbversiondateElement);}
  if(array_key_exists('QdbVersionDate', $header)){$qdbversiondateElement=new DOMElement('QdbVersionDate',$header['QdbVersionDate']); $headerElement->appendChild($qdbversiondateElement);}
  if(array_key_exists('PcdbVersionDate', $header)){$pcdbversiondateElement=new DOMElement('PcdbVersionDate',$header['PcdbVersionDate']); $headerElement->appendChild($pcdbversiondateElement);}
          
  // --------------------- apps ---------------------------------   
  $appnumber=1;
  foreach($apps as $app)
  {
   if($includecosmeticapps==false && $app['cosmetic']==1){continue;}
   
   if($app['parttypeid']==0 || $app['quantityperapp']==0)
   { // major app problems that would cause an XSD violation //$app['positionid']==0 ||
    continue;
   }

   $mfrlabel=''; if(array_key_exists('mfrlabel', $app) && trim($app['mfrlabel'])!=''){$mfrlabel=$app['mfrlabel'];}
   if($descriptiontonmfrlabel!='' && array_key_exists($app['partnumber'],$partdescriptions)){$mfrlabel=$partdescriptions[$app['partnumber']];}   
   
   if($suppressduplicateapps)
   {// hash what goes to the output
    $apphash=md5($app['basevehicleid'].$app['makeid'].$app['equipmentid'].$app['parttypeid'].$app['positionid'].$app['quantityperapp'].$app['partnumber'].$mfrlabel.$this->appAttributesHash($app['attributes'], $includecosmeticattributes));     
    if(array_key_exists($apphash, $existingapphashes))
    {
     continue;
    }
    else  
    {// first time seeing this hash
     $existingapphashes[$apphash]='';
    }
   }  
   
   $appElement=new DOMElement('App');
   $root->appendChild($appElement);

   $appElement->setAttribute('action', 'A');
   $appElement->setAttribute('id', $appnumber);
   $appElement->setAttribute('ref', $app['id']);
   
   $basevehicleElement=new DOMElement('BaseVehicle');
   $appElement->appendChild($basevehicleElement);
   $basevehicleElement->setAttribute('id', $app['basevehicleid']);
   
   
   // get sorted copy of the attributes
   $vcdbattributes=array();
   foreach($app['attributes'] as $attribute)
   {
    if($includecosmeticattributes==false && $attribute['cosmetic']==1){continue;}
    if($attribute['type']=='vcdb' && $attribute['value']!=0)
    {
     $vcdbattributes[]=$attribute;
    }
   }
   
   usort($vcdbattributes, function($a,$b) 
   {
    $reflist=array('SubModel'=>1,'MfrBodyCode'=>2,'BodyNumDoors'=>3,'BodyType'=>4,'DriveType'=>5,'EngineBase'=>6,'EngineBlock'=>7,'EngineBoreStroke'=>8,'EngineDesignation'=>9,'EngineVIN'=>10,'EngineVersion'=>11,'EngineMfr'=>12,'PowerOutput'=>13,'ValvesPerEngine'=>14,'FuelDeliveryType'=>15,'FuelDeliverySubType'=>16,'FuelSystemControlType'=>17,'FuelSystemDesign'=>18,'Aspiration'=>19,'CylinderHeadType'=>20,'FuelType'=>21,'IgnitionSystemType'=>22,'TransmissionMfrCode'=>23,'TransmissionBase'=>24,'TransmissionType'=>25,'TransmissionControlType'=>26,'TransmissionNumSpeeds'=>27,'TransElecControlled'=>28,'TransmissionMfr'=>29,'BedLength'=>30,'BedType'=>31,'WheelBase'=>32,'BrakeSystem'=>33,'FrontBrakeType'=>34,'RearBrakeType'=>35,'BrakeABS'=>36,'FrontSpringType'=>37,'RearSpringType'=>38,'SteeringSystem'=>39,'SteeringType'=>40,'Region'=>41);
    if(array_key_exists($a['name'], $reflist) && array_key_exists($b['name'], $reflist))
    {
    if($reflist[$a['name']]==$reflist[$b['name']]){return 0;}
    if(intval($reflist[$a['name']]) > intval($reflist[$b['name']])){return 1;}else{return -1;}
   }
   else
   { // one (or both) of the attribute names is not valid. return "=" (0)
    return 0;
   }});
   
   foreach($vcdbattributes as $attribute)
   {
     $vcdbElement=new DOMElement($attribute['name']);
     $appElement->appendChild($vcdbElement);
     $vcdbElement->setAttribute('id', $attribute['value']);
   }

   foreach($app['attributes'] as $attribute)
   {
    if($includecosmeticattributes==false && $attribute['cosmetic']==1){continue;}
    if($attribute['type']=='qdb')
    {
     $qdbtext=$qdb->qualifierText($attribute['name'],explode('~', str_replace('|','',$attribute['value'])));
     $qualElement=new DOMElement('Qual');
     $appElement->appendChild($qualElement);
     $qualElement->setAttribute('id', $attribute['name']);
     
     if($attribute['value']!='')
     {
      $parms=explode('~',$attribute['value']);
      foreach($parms as $parm)
      {
       if(trim($parm)!='')
       {
        $parmbits=explode('|',$parm);        
        if(count($parmbits)==2 && trim($parmbits[0])!='')
        {
         $paramElement=new DOMElement('param');
         $qualElement->appendChild($paramElement);
         $paramElement->setAttribute('value', trim($parmbits[0]));
      
         if(trim($parmbits[1])!='')
         {// uom is present "288|mm"
          $paramElement->setAttribute('uom', trim($parmbits[1]));
         }        
        }
       }
      }
     }
     
     $qdbtextElement=new DOMElement('text', htmlspecialchars($qdbtext, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
     $qualElement->appendChild($qdbtextElement);
    }       
   }   

   foreach($app['attributes'] as $attribute)
   {
    if($includecosmeticattributes==false && $attribute['cosmetic']==1){continue;}
    if($attribute['type']=='note')
    {
     $noteElement=new DOMElement('Note', htmlspecialchars($attribute['value'], ENT_XML1 | ENT_COMPAT, 'UTF-8'));
     $appElement->appendChild($noteElement);
    }
   }
   
   $qtyElement=new DOMElement('Qty',$app['quantityperapp']);
   $appElement->appendChild($qtyElement);
   
   $parttypeElement=new DOMElement('PartType');
   $appElement->appendChild($parttypeElement);
   $parttypeElement->setAttribute('id', $app['parttypeid']);

   if($mfrlabel != ''){$mfrlabelElement=new DOMElement('MfrLabel',htmlspecialchars($mfrlabel, ENT_XML1 | ENT_COMPAT, 'UTF-8')); $appElement->appendChild($mfrlabelElement);} 
   
   if($app['positionid']!=0)
   {
    $positionElement=new DOMElement('Position');
    $appElement->appendChild($positionElement);
    $positionElement->setAttribute('id', $app['positionid']);
   }   

   if(array_key_exists($app['partnumber'], $parttranslations))
   {// traanslation exist for this app's partnumber - flip it
    $partElement=new DOMElement('Part',$parttranslations[$app['partnumber']]);    
   }
   else
   {// no traanslation exist for this app's partnumber 
    $partElement=new DOMElement('Part',$app['partnumber']);
   }

   $appElement->appendChild($partElement);
   if(array_key_exists('brand',$app) && $app['brand']!=''){$partElement->setAttribute('BrandAAIAID', $app['brand']);}
      
   if(array_key_exists('assetname', $app) && trim($app['assetname'])!=''){
       $assetnameElement=new DOMElement('AssetName',$app['assetname']); 
       $appElement->appendChild($assetnameElement);}
  
   $appnumber++;
  }
  
  $footerElement=new DOMElement('Footer');
  $root->appendChild($footerElement);
  $recordcountElement=new DOMElement('RecordCount',($appnumber-1));
  $footerElement->appendChild($recordcountElement);
  return $doc;
 }


 function appAttributesHash($attributes,$includecosmeticattributes)
 {
  $hashinput='';
  foreach($attributes as $attribute)
  {
   if(!$includecosmeticattributes && $attribute['cosmetic']==1){continue;}
   $hashinput.=$attribute['name'].$attribute['value'].$attribute['type'];
  }
  return md5($hashinput);
 }

 
 
 
}
?>
