<?php
include_once('./class/pimClass.php');
include_once('./class/pricingClass.php');
include_once('./class/packagingClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/assetClass.php');
include_once('./class/replicationClass.php');
include_once('./class/logsClass.php');

$starttime=time();

$pim = new pim();
$logs=new logs();

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'acceptParts - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$pricing = new pricing();
$packaging = new packaging();
$interchange = new interchange();
$asset = new asset();
$replication = new replication();

$newpartcount=0;  $droppedpartcount=0;

if(isset($_GET['detail']))
{ // get local list of all part oid's
 $localparts=$pim->getParts('', 'startswith', 'any', 'any', 'any', 'any', 999999);
 $localoids=array(); foreach($localparts as $localpart){$localoids[]=$localpart['oid'];}
 sort($localoids);
 $oidliststring=''; foreach($localoids as $oid){$oidliststring.=$oid;}

 if($_GET['detail']=='hash')
 {
  $hash=md5($oidliststring);
  echo json_encode(array('hash'=> $hash));
  $logs->logSystemEvent('replication', 0, 'gave hash ('.$hash.') of '.count($localoids).' local part oids to client '.$_SERVER['REMOTE_ADDR']);
 }
 else
 {
  echo json_encode(array('oids'=>$localoids));
  $logs->logSystemEvent('partacceptor', 0, 'gave list of '.count($localoids).' local part oids to client '.$_SERVER['REMOTE_ADDR']);
 }
}

$bodyraw=file_get_contents('php://input');

if(strlen($bodyraw)>0)
{
 $body=json_decode($bodyraw,true);

  if(!array_key_exists('identifier',$body) || !array_key_exists('signature',$body))
 {
  $logs->logSystemEvent('replication', 0, 'invalid data (missing identifier or signature) posted to acceptParts API from client '.$_SERVER['REMOTE_ADDR']);
  exit;
 }

  // lookup peer by its claimed identifier
 $peers=$replication->getPeers($body['identifier'],'part', 'primary');
 if(count($peers)==0)
 {
  $logs->logSystemEvent('replication', 0, 'unknown identifier ['.$body['identifier'].'] posted to acceptPatrs API from client '.$_SERVER['REMOTE_ADDR']);  
  exit;
 }
 
 //test signature of payload
 $computedsignature = hash_hmac('SHA256', json_encode(array('identifier'=>$body['identifier'],'adds'=>$body['adds'],'drops'=>$body['drops'])), $peers[0]['sharedsecret'],false);
 if($body['signature']!=$computedsignature)
 {
  $logs->logSystemEvent('replication', 0, 'invalid signature on payload - no adds/drops accepted by acceptParts API from peer identified by: '.$body['identifier']);
  exit;
 }

 
 if(isset($body['drops']))
 { // drop list is oid's (not partnumber's)
  foreach ($body['drops'] as $oid)
  {
   $partnumbers=$pim->deletePartsByOID($oid);
   foreach($partnumbers as $partnumber)
   {// possible (but unlikely) that multiple parts could have had the same oid - delete them all
    $pim->logPartEvent($partnumber, 0, 'part deleted by acceptPart API', '');
   }
   $droppedpartcount++;
  }
 } 
 
 if(isset($body['adds']))
 {
  foreach ($body['adds'] as $p)
  {
   $partnumber=$p['partnumber'];
   if(!$pim->validPart($partnumber))
   {// part being added by remote master does not already exist
       
    $pim->createPart($partnumber, $p['partcategory'], $p['parttypeid']);
    $pim->setPartOID($partnumber, $p['oid']);
    $pim->setPartGTIN($partnumber, $p['GTIN'], false);
    $pim->setPartUNSPC($partnumber, $p['UNSPC'], false);
    $pim->setPartLifecyclestatus($partnumber, $p['lifecyclestatus'], false);
    $pim->setPartInternalnotes($partnumber, base64_decode($p['internalnotes']),false);
    $pim->setPartReplacedby($partnumber, $p['replacedby'], false);
    $pim->setPartCreatedDate($partnumber, $p['createdDate'], false);
    $pim->setPartFirststockedDate($partnumber, $p['firststockedDate'], false);
    $pim->setPartDiscontinuedDate($partnumber, $p['discontinuedDate'], false);
    
    // part_x records 
    foreach($p['descriptions'] as $d)
    {
     $pim->addPartDescription($partnumber, $d['description'], $d['descriptioncode'], $d['sequence'], $d['languagecode']);      
    }

    foreach($p['attributes'] as $at)
    {
     $pim->writePartAttribute($partnumber, $at['PAID'], $at['name'], $at['value'], $at['uom']);
    }
    
    foreach($p['prices'] as $pr)
    {
     $pricing->addPrice($partnumber, $pr['pricesheetnumber'], $pr['amount'], $pr['currency'], $pr['priceuom'], $pr['pricetype'], $pr['effectivedate'], $pr['expirationdate']);
    }

    foreach($p['packages'] as $pk)
    {
     $packaging->addPackage($partnumber, $pk['packageuom'], $pk['quantityofeaches'], $pk['innerquantity'], $pk['innerquantityuom'], $pk['weight'], $pk['weightsuom'], $pk['packagelevelGTIN'], $pk['packagebarcodecharacters'], $pk['shippingheight'], $pk['shippingwidth'], $pk['shippinglength'], $pk['merchandisingheight'], $pk['merchandisingwidth'], $pk['merchandisinglength'], $pk['dimensionsuom'],$pk['orderable']);
    }
    
    foreach($p['interchanges'] as $ic)
    {
     $interchange->addInterchange($partnumber, $ic['competitorpartnumber'], $ic['brandAAIAID'], $ic['interchangequantity'], $ic['uom'], $ic['interchangenotes'], $ic['internalnotes']);       
    }
    
    foreach($p['assetconnections'] as $ac)
    {// write all the part-asset recs
     $asset->connectPartToAsset($partnumber, $ac['assetid'], $ac['assettypecode'], $ac['sequence'], $ac['representation']);
    }
    
    $pim->logPartEvent($partnumber, 0, 'part created by acceptPart API', $p['oid']);
    
    $newpartcount++;
   }
   else
   {// remote client tried to add a part that already existed
    $logs->logSystemEvent('replication', 0, 'declined to add part ('.$partnumber.' that already exists');
   }
  }
 }
}
    
$runtime=time()-$starttime;
if($newpartcount || $runtime>10)
{
 $logs->logSystemEvent('replication', 0, 'Added '.$newpartcount.', dropped '.$droppedpartcount.' parts in '.$runtime.' seconds from '.$_SERVER['REMOTE_ADDR']);
}

?>