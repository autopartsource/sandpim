<?php
include_once("mysqlClass.php");

class padb
{
 public $padbversion;
 
 public function __construct($_padbversion=false) 
 {
  $this->padbversion=$_padbversion;
  if(!$_padbversion)
  { // no secific vsersion was passed in. Consult pim database for the name
    // of the active vcdb database. It will be something like vcdb20210827
      
   $db = new mysql; $db->connect();
   if($stmt=$db->conn->prepare("select configvalue from config where configname='padbProductionDatabase'"))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      if($row = $db->result->fetch_assoc())
      {
       $this->padbversion=$row['configvalue'];
      }
     }
    }
    $db->close();
   }
  }  
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
