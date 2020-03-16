<?php
include_once("mysqlClass.php");

class pcdb
{

 function positionName($positionid)
 {
  $name='not found';
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select Position from Positions where PositionID=?'))
  {
   $stmt->bind_param('i', $positionid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $name=$row['Position'];
   }
  }
  $db->close();
  return $name;
 }

 function parttypeName($parttypeid)
 {
  $name='not found';
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select PartTerminologyName from Parts where PartTerminologyID=?'))
  {
   $stmt->bind_param('i', $parttypeid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $name=$row['PartTerminologyName'];
   }
  }
  $db->close();
  return $name;
 }

 function version()
 {
  $versiondate='not found';
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select VersionDate from Version'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $versiondate=$row['VersionDate'];
   }
  }
  $db->close();
  return $versiondate;
 }


 function getPartTypes($searchstring)
 {
  $types=array();
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select PartTerminologyID, PartTerminologyName from Parts where PartTerminologyName like ? order by PartTerminologyName'))
  {
   $stmt->bind_param('s', $searchstring);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $types[]=array('id'=>$row['PartTerminologyID'],'name'=>$row['PartTerminologyName']);
   }
  }
  $db->close();
  return $types;
 }

 function getPositions($searchstring)
 {
  $positions=array();
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select PositionID, Position from Positions where Position like ? order by Position'))
  {
   $stmt->bind_param('s', $searchstring);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $positions[]=array('id'=>$row['PositionID'],'name'=>$row['Position']);
   }
  }
  $db->close();
  return $positions;
 }
 
 function getLifeCycleCodes()
 {
  $codes=array();
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select CodeValue,CodeDescription from PIESReferenceFieldCode, PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and  PIESReferenceFieldCode.PIESFieldId=93 order by CodeDescription'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $codes[]=array('code'=>$row['CodeValue'],'description'=>$row['CodeDescription']);
   }
  }
  $db->close();
  return $codes;    
 }

 function lifeCycleCodeDescription($code)
 {
  if(trim($code)==''){return 'not set (blank)';}
  $description='not found';
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select CodeDescription from PIESReferenceFieldCode, PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESReferenceFieldCode.PIESFieldId=93 and CodeValue=?'))
  {
   $stmt->bind_param('s', $code);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $description=$row['CodeDescription'];
   }
  }
  $db->close();
  return $description;    
 }
 
 function getAssetTypeCodes()
 {
  $codes=array();
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select CodeValue,CodeDescription from  PIESReferenceFieldCode,PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and  PIESFieldId=32 order by CodeValue'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $codes[]=array('code'=>$row['CodeValue'],'description'=>$row['CodeDescription']);
   }
  }
  $db->close();
  return $codes;    
 }
 
 function assetTypeCodeDescription($code)
 {
  if(trim($code)==''){return 'not set (blank)';}
  $description='not found';
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select CodeDescription from PIESReferenceFieldCode, PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESReferenceFieldCode.PIESFieldId=32 and CodeValue=?'))
  {
   $stmt->bind_param('s', $code);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $description=$row['CodeDescription'];
   }
  }
  $db->close();
  return $description;    
 }
 
 function getPartDescriptionTypeCodes()
 {
  $codes=array();
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select CodeValue,CodeDescription from  PIESReferenceFieldCode,PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and  PIESFieldId=60 order by CodeValue'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $codes[]=array('code'=>$row['CodeValue'],'description'=>$row['CodeDescription']);
   }
  }
  $db->close();
  return $codes;    
 }
 
 function partDescriptionTypeCodeDescription($code)
 {
  if(trim($code)==''){return 'not set (blank)';}
  $description='invalid code ('.$code.')';
  $db = new mysql; $db->dbname='pcadb'; $db->connect();
  if($stmt=$db->conn->prepare('select CodeDescription from PIESReferenceFieldCode, PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESReferenceFieldCode.PIESFieldId=60 and CodeValue=?'))
  {
   $stmt->bind_param('s', $code);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $description=$row['CodeDescription'];
   }
  }
  $db->close();
  return $description;    
 }
 
 
 
 
 
}
?>
