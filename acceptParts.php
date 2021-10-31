<?php
include_once('./class/pimClass.php');
include_once('./class/pricingClass.php');
include_once('./class/packagingClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$starttime=time();

$pim = new pim();
$pricing = new pricing();
$packaging = new packaging();
$interchange = new interchange();
$asset = new asset();
$logs=new logs();

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'acceptParts - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}


$newpartcount=0;  $droppedpartcount=0;

if(isset($_GET['detail']))
{ // get local list of all part oid's
 $localparts=$pim->getParts('', 'startswith', 'any', 'any', 'any', 999999);
 $localoids=array(); foreach($localparts as $localpart){$localoids[]=$localpart['oid'];}
 sort($localoids);
 $oidliststring=''; foreach($localoids as $oid){$oidliststring.=$oid;}

 if($_GET['detail']=='hash')
 {
  $hash=md5($oidliststring);
  echo json_encode(array('hash'=> $hash));
  $logs->logSystemEvent('partacceptor', 0, 'client requested hash ('.$hash.') of oids');   
 }
 else
 {
  echo json_encode(array('oids'=>$localoids));
  $logs->logSystemEvent('partacceptor', 0, 'client requested list of ('.count($localoids).') local oids');
 }
}

$bodyraw=file_get_contents('php://input');

if(strlen($bodyraw)>0)
{
 $body=json_decode($bodyraw,true);

 if(isset($body['drops']))
 { // drop list is oid's (not partnumber's)
  foreach ($body['drops'] as $oid)
  {
   $partnumbers=$pim->deletePartsByOID($oid);
   foreach($partnumbers as $partnumber)
   {// possible (but unlikely) that multiple parts could have had the same oid - delete them all
    $pim->logPartEvent($partnumber, 0, 'part deleted by partAcceptor.php', '');
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
     $packaging->addPackage($partnumber, $pk['packageuom'], $pk['quantityofeaches'], $pk['innerquantity'], $pk['innerquantityuom'], $pk['weight'], $pk['weightsuom'], $pk['packagelevelGTIN'], $pk['packagebarcodecharacters'], $pk['shippingheight'], $pk['shippingwidth'], $pk['shippinglength'], $pk['dimensionsuom']);
    }
    
    foreach($p['interchanges'] as $ic)
    {
     $interchange->addInterchange($partnumber, $ic['competitorpartnumber'], $ic['brandAAIAID'], $ic['interchangequantity'], $ic['uom'], $ic['interchangenotes'], $ic['internalnotes']);       
    }
    
    foreach($p['assetconnections'] as $ac)
    {// write all the part-asset recs
     $asset->connectPartToAsset($partnumber, $ac['assetid'], $ac['assettypecode'], $ac['sequence'], $ac['representation']);
    }
    
    $pim->logPartEvent($partnumber, 0, 'part created by partAcceptor.php', $p['oid']);
    
    $newpartcount++;
   }
   else
   {// remote client tried to add a part that already existed

    $logs->logSystemEvent('partacceptor', 0, 'declined to add part ('.$partnumber.' that already exists');
     
   }
  }
 }
}
    
$runtime=time()-$starttime;
if($newpartcount || $runtime>10)
{
 $logs->logSystemEvent('partacceptor', 0, 'Part acceptor added '.$newpartcount.', dropped '.$droppedpartcount.' parts in '.$runtime.' seconds');   
}
?>