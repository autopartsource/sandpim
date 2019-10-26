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



}
?>
