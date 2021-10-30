<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$starttime=time();

$pim = new pim();
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
   //$logs->logSystemEvent('partacceptor', 0, 'dropped part: '.$partnumbers);
   $pim->logPartEvent($partnumber, 0, 'part deleted by partAcceptor.php', '');
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
    $pim->setPartInternalnotes($partnumber, base64_decode($p['internalnotes']));
    $pim->setPartReplacedby($partnumber, $p['replacedby'], false);
    $pim->setPartCreatedDate($partnumber, $p['createdDate'], false);
    $pim->setPartFirststockedDate($partnumber, $p['firststockedDate'], false);
    $pim->setPartDiscontinuedDate($partnumber, $p['discontinuedDate'], false);
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