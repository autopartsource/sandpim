<?php
include_once("mysqlClass.php");

class padb
{
 public $padbversion;
 
 function __construct($_padbversion=false) 
 {
  $this->padbversion=$_padbversion;
 }

 
 function getAttributesForParttype($parttypeid)
 {
  $attributes=array();
  $db = new mysql; $db->dbname=$db->padbname;
  if($this->padbversion!==false){$db->dbname=$this->padbversion;}
  $db->connect();
  if($stmt=$db->conn->prepare('select PartAttributeAssignment.PAID,PAName,UoMList,ValidValues from PartAttributeAssignment,PartAttributes where PartAttributeAssignment.PAID=PartAttributes.PAID and PartTerminologyID=?'))
  {
   $stmt->bind_param('i', $parttypeid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributes[]=array('PAID'=>$row['PAID'],'name'=>$row['PAName'],'uomlist'=>$row['UoMList'],'validvalues'=>$row['ValidValues']);
   }
  }
  $db->close();
  return $attributes;
 }

function PAIDname($PAID)
{
  $name=false;
  $db = new mysql; $db->dbname=$db->padbname; 
  if($this->padbversion!==false){$db->dbname=$this->padbversion;}
  $db->connect();
  if($stmt=$db->conn->prepare('select PAName from PartAttributes where PAID=?'))
  {
   $stmt->bind_param('i', $PAID);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $name=$row['PAName'];
   }
  }
  $db->close();
  return $name;
}
function version()
{
  $versiondate='not found';
  $db = new mysql; 
  $db->dbname=$db->padbname; if($this->padbversion!==false){$db->dbname=$this->padbversion;} $db->connect();
  if($stmt=$db->conn->prepare('select PAdbPublication from Version'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $versiondate=$row['PAdbPublication'];
   }
  }
  $db->close();
  return $versiondate;
 }

 
}
?>
