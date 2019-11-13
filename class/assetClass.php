<?php
include_once("mysqlClass.php");

class asset
{

 function addAsset($assetid,$filename,$uri,$assetTypeCode,$orientationViewCode,$colorModeCode,$assetHeight,$assetWidth,$dimensionUOM,$background,$fileType,$public,$approved,$description,$oid,$fileHashMD5)
 {
  $id=false;
  $db=new mysql; 
  $db->connect();
  if($stmt=$db->conn->prepare('insert into asset(id,assetid,filename,uri,assetTypeCode,orientationViewCode,colorModeCode,assetHeight,assetWidth,dimensionUOM,background,fileType,createdDate,public,approved,description,oid,fileHashMD5) values(null,?,?,?,?,?,?,?,?,?,?,?,date(now()),?,?,?,?,?)'))
  {
   $stmt->bind_param('ssssssiisssiisss',$assetid,$filename,$uri,$assetTypeCode,$orientationViewCode,$colorModeCode,$assetHeight,$assetWidth,$dimensionUOM,$background,$fileType,$public,$approved,$description,$oid,$fileHashMD5);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }
  $db->close();
  return $id;
 }

 function getAssetByAssetid($assetid)
 {
  $asset=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from asset where assetid=?'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
       $asset=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'uri'=>$row['uri'],'assetTypeCode'=>$row['assetTypeCode'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5']);
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
  
  if($stmt=$db->conn->prepare('select * from asset order by createdDate desc limit ?'))
  {
   $stmt->bind_param('i',$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $assets[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'uri'=>$row['uri'],'assetTypeCode'=>$row['assetTypeCode'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5']);
   }
  }
  $db->close();
  return $assets;   
 }


 
 
 
 
}?>
