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
