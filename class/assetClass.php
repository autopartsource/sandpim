<?php
include_once("mysqlClass.php");

class asset
{

 function addAsset($assetid,$filename,$uri,$orientationViewCode,$colorModeCode,$assetHeight,$assetWidth,$dimensionUOM,$background,$fileType,$public,$approved,$description,$oid,$fileHashMD5,$filesize)
 {
  $id=false;
  $db=new mysql; 
  $db->connect();
  if($stmt=$db->conn->prepare('insert into asset(id,assetid,filename,uri,orientationViewCode,colorModeCode,assetHeight,assetWidth,dimensionUOM,background,fileType,createdDate,public,approved,description,oid,fileHashMD5,filesize) values(null,?,?,?,?,?,?,?,?,?,?,date(now()),?,?,?,?,?,?)'))
  {
   $stmt->bind_param('sssssiisssiisssi',$assetid,$filename,$uri,$orientationViewCode,$colorModeCode,$assetHeight,$assetWidth,$dimensionUOM,$background,$fileType,$public,$approved,$description,$oid,$fileHashMD5,$filesize);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }
  $db->close();
  return $id;
 }

 function getAssetRecordsByAssetid($assetid)
 {
  $records=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from asset where assetid=?'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize']);
   }
  }
  $db->close();
  return $records;   
 }
 

 function getAssetById($id)
 {
  $asset=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from asset where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
       $asset=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize']);
   }
  }
  $db->close();
  return $asset;   
 }
 
 function connectPartToAsset($part,$assetid,$assettypecode,$sequence)
 {
  $id=false;
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into part_asset (id,partnumber,assetid,assettypecode,sequence) values(null,?,?,?,?)'))
  {   
   $stmt->bind_param('sssi',);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }
  $db->close();
  return $id;   
 }
 
 function disconnectPartFromAsset($part,$assetid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from part_asset where part=? and assetid=?'))
  {
   $stmt->bind_param('ss',$part,$assetid);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }
 
 function deleteAssetRecord($id)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from asset where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
  
  // need to delete (unlink) local file if it exists
  
 }
 
 function setAssetDescription($assetid,$description)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set description=? where assetid=?'))
  {
   $encodednotes=base64_encode($internalnotes);
   $stmt->bind_param('ss', $encodednotes,$assetid);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }
 
 function toggleAssetPublic($assetid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set public=public XOR 1 where assetid=?'))
  {
   $stmt->bind_param('s', $assetid);
   $stmt->execute();
  } //else{print_r($db->conn->error);}
  $db->close();
 }
 
 function toggleAssetUriPublic($assetid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set uripublic=uripublic XOR 1 where assetid=?'))
  {
   $stmt->bind_param('s', $assetid);
   $stmt->execute();
  } //else{print_r($db->conn->error);}
  $db->close();
 }


 function getRecentAssets($limit)
 {
  $assets=array();
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from asset order by id desc limit ?'))
  {
   $stmt->bind_param('i',$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $assets[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize']);
   }
  }
  $db->close();
  return $assets;   
 }

}?>
