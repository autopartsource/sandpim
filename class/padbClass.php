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
  $attributes=array(); $attributenames=array();
  $db = new mysql; $db->dbname=$db->padbname;
  if($this->padbversion!==false){$db->dbname=$this->padbversion;}
  $db->connect();
  if($stmt=$db->conn->prepare('select PartAttributeAssignment.PAID,PAName from PartAttributeAssignment,PartAttributes where PartAttributeAssignment.PAID=PartAttributes.PAID and PartTerminologyID=?'))
  {
   $stmt->bind_param('i', $parttypeid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributenames[]=array('PAID'=>$row['PAID'],'name'=>$row['PAName']);
   }
  }
  
  if($stmt=$db->conn->prepare('select UOMCode from  PartAttributeAssignment, MetaUOMCodeAssignment, MetaUOMCodes  where  PartAttributeAssignment.PAPTID=MetaUOMCodeAssignment.PAPTID and MetaUOMCodeAssignment.MetaUOMID=MetaUOMCodes.MetaUOMID and PartAttributeAssignment.PAID=? and PartAttributeAssignment.PartTerminologyID=?'))
  {
   $PAID=0;
   $stmt->bind_param('ii', $PAID, $parttypeid);

   foreach($attributenames as $attributename)
   {
    $uoms=array();
    $PAID=$attributename['PAID'];
    $stmt->execute();
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc()){$uoms[]=$row['UOMCode'];}
    $attributes[]=array('PAID'=>$attributename['PAID'],'name'=>$attributename['name'],'uomlist'=>$uoms,'validvalues'=>'');   
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
