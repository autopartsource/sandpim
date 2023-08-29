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

 function getAttributeValidValues($parttypeid,$PAID)
 {
  $options=array();
  $db = new mysql; $db->dbname=$db->padbname;
  if($this->padbversion!==false){$db->dbname=$this->padbversion;}
  $db->connect();
  if($stmt=$db->conn->prepare('select ValidValue from PartAttributeAssignment, ValidValueAssignment,ValidValues where PartAttributeAssignment.PAPTID=ValidValueAssignment.PAPTID and ValidValueAssignment.ValidValueID=ValidValues.ValidValueID and PartAttributeAssignment.PartTerminologyID=? and PartAttributeAssignment.PAID=?'))
  {
   $stmt->bind_param('ii', $parttypeid, $PAID);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $options[]=$row['ValidValue'];
   }
  } 
  $db->close();
  return $options;
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

 function addDatabaseIndex($table,$field)
 {
     // AutoCare's mysql version of the PAdb (as of 10/2021) does not have all the needed indexes for effecient lookups
     // we're using random names for the indexes that we create in case the function gets run more than once (unlikely, but possible)
     // 
     //create index idx_MetaUOMID on MetaUOMCodes (MetaUOMID);
     //create index idx_MetaUOMID on MetaUOMCodeAssignment (MetaUOMID);
     //create index idx_PAPTID on MetaUOMCodeAssignment (PAPTID);
     //create index idx_MetaID on PartAttributeAssignment (MetaID);
     //create index idx_PAID on PartAttributeAssignment (PAID);
     //create index idx_PartTerminologyID on PartAttributeAssignment (PartTerminologyID);
     //create index idx_PAPTID on PartAttributeAssignment (PAPTID);
     //create index idx_PAPTID on ValidValueAssignment (PAPTID);
     //create index idx_ValidValueID on ValidValueAssignment (ValidValueID);
    
     // and while you're creating missing indexes, the Qdb needs this:
     // alter table Qualifier add FULLTEXT(QualifierText);

     
     
  $result='';
  $randoname= random_int(100000, 999999);
  $db = new mysql; 
  $db->dbname=$db->padbname; if($this->padbversion!==false){$db->dbname=$this->padbversion;} $db->connect();

  if($stmt=$db->conn->prepare('alter table '.$table.' add index idx_'.$randoname.'('.$field.')'))
  {
   if(!$stmt->execute())
   {
    $result='problem with execute: '.$db->conn->error;;
   }
  }
  else
  {
   $result='problem with prepare: '.$db->conn->error;;   
  }
  $db->close();
  return $result;
 }
 
 
 
}
?>
