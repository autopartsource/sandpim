<?php
include_once("mysqlClass.php");

class pcdb
{
 public $pcdbversion;
    
 public function __construct($_pcdbversion=false) 
 {
  $this->pcdbversion=$_pcdbversion;
  if(!$_pcdbversion)
  { // no secific vsersion was passed in. Consult pim database for the name
    // of the active vcdb database. It will be something like pcdb20210827      
   $db = new mysql; $db->connect();
   if($stmt=$db->conn->prepare("select configvalue from config where configname='pcdbProductionDatabase'"))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      if($row = $db->result->fetch_assoc())
      {
       $this->pcdbversion=$row['configvalue'];
      }
     }
    }
    $db->close();
   }
  }  
 }
    
 function addIndexes()
 {
     //create index idx_PartTerminologyID_PositionID on CodeMaster (PartTerminologyID,PositionID);
     
     
     
 }
 
 
 
 function positionName($positionid)
 {
  $name='not found';
  $db = new mysql; $db->dbname=$db->pcdbname; 
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
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

 function positionIDofName($positionname)
 {
  $id=false; $db = new mysql; $db->dbname=$db->pcdbname; 
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;} $db->connect();
  if($stmt=$db->conn->prepare('select PositionID from Positions where Position=?'))
  {
   $stmt->bind_param('s', $positionname);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id=$row['PositionID'];
   }
  }
  $db->close();
  return $id;
 }

 
 
 
 function validParttypePosition($parttypeid,$positionid)
 {     
     // the native MySQL published database from ACA does not have an index on CodeMaster that makes this query fast
     // add this index after installation
     //create index idx_PartTerminologyID_PositionID on CodeMaster (PartTerminologyID,PositionID);
  $db = new mysql; $db->dbname=$db->pcdbname; $valid=false;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  $sql='select * from CodeMaster where PartTerminologyID=? and PositionID=?';
  if($db->dbname=='pcdbcache')
  { // API-based schema is different
   $sql='select * from PartPosition where PartTerminologyID=? and PositionID=?';   
  }
  if($stmt=$db->conn->prepare($sql))
  {
   $stmt->bind_param('ii', $parttypeid, $positionid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $valid=true;
   }
  }
  $db->close();
  return $valid;
 }
 
 
 
 
 function parttypeName($parttypeid)
 {
  $name='not found';
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
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

 function validPartType($parttypeid)
 {
  $db = new mysql; $db->dbname=$db->pcdbname; $returnval=false;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  if($stmt=$db->conn->prepare('select PartTerminologyName from Parts where PartTerminologyID=?'))
  {
   if($stmt->bind_param('i', $parttypeid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $returnval=true;
     }
    } 
   } 
  }
  $db->close();
  return $returnval;
 }

 function parttypeIDofName($parttypename)
 {
  $id=false; $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;} $db->connect();
  if($stmt=$db->conn->prepare('select PartTerminologyID from Parts where PartTerminologyName=?'))
  {
   $stmt->bind_param('s', $parttypename);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id=$row['PartTerminologyID'];
   }
  }
  $db->close();
  return $id;
 }
 
 
 function version()
 {
  $versiondate='not found';
  $db = new mysql; 
  $db->dbname=$db->pcdbname; 
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
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


 function getPartTypes($searchstring, $_limit=false)
 {
  $types=array();
  $db = new mysql; $db->dbname=$db->pcdbname; 
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  
  if($_limit===false){$limit=250;}else{$limit=intval($_limit);}
  
  if($stmt=$db->conn->prepare('select PartTerminologyID, PartTerminologyName from Parts where PartTerminologyName like ? order by PartTerminologyName limit ?'))
  {
   $stmt->bind_param('si', $searchstring,$limit);
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
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
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

 function getAllPositions()
 {
  $positions=array();
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  if($stmt=$db->conn->prepare('select PositionID, Position from Positions'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $positions[$row['PositionID']]=$row['Position'];
   }
  }
  $db->close();
  return $positions;
 }

 function getAllParttypes()
 {
  $parttypes=array();
  $db = new mysql; $db->dbname=$db->pcdbname; if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;} $db->connect();
  if($stmt=$db->conn->prepare('select PartTerminologyID,PartTerminologyName from Parts'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $parttypes[$row['PartTerminologyID']]=$row['PartTerminologyName'];
   }
  }
  $db->close();
  return $parttypes;
 }

 
 function getLifeCycleCodes()
 {
  $codes=array();
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  $sql='select CodeValue,CodeDescription from PIESReferenceFieldCode, PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and  PIESReferenceFieldCode.PIESFieldId=93 order by CodeDescription';
  if($db->dbname=='pcdbcache')
  {// the API-based schema has diffenent naming of some fields
   $sql='select CodeValue,CodeDescription from PIESReferenceFieldCode, PIESCode where PIESReferenceFieldCode.CodeValueID=PIESCode.CodeValueID and  PIESReferenceFieldCode.FieldId=93 order by CodeDescription';
  }
  
  if($stmt=$db->conn->prepare($sql))
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
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  $sql='select CodeDescription from PIESReferenceFieldCode, PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESReferenceFieldCode.PIESFieldId=93 and CodeValue=?';
  if($db->dbname=='pcdbcache')
  {
   $sql='select CodeDescription from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.FieldId=PIESField.FieldId and PIESReferenceFieldCode.CodeValueID=PIESCode.CodeValueID and PIESField.FieldId=93 and CodeValue=?';
  }
  if($stmt=$db->conn->prepare($sql))
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

 function getItemDescriptionCodes()
 {
  $codes=array();
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
//  if($stmt=$db->conn->prepare('select CodeValue,CodeDescription,FieldFormat from PIESReferenceFieldCode,PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and  PIESFieldId=39 order by CodeValue'))
  if($stmt=$db->conn->prepare('select CodeValue,CodeDescription,FieldFormat from PIESReferenceFieldCode,PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and  PIESFieldId=60 order by CodeValue'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $codes[]=array('code'=>$row['CodeValue'],'description'=>$row['CodeDescription'],'format'=>$row['FieldFormat']);
   }
  }
  $db->close();
  return $codes;    
 }

 
 function validPartDecriptionCode($code)
 {
  $returnval=false;
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  if($stmt=$db->conn->prepare('select CodeValue,CodeDescription,FieldFormat from PIESReferenceFieldCode,PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESFieldId=60 and CodeValue=?'))
  {
   if($stmt->bind_param('s', $code))
   {
    $stmt->execute();
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $returnval=true;
    }
   }
  }
  $db->close();
  return $returnval; 
 }
 
 
 function getAssetTypeCodes()
 {
  $codes=array();
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  $sql='select CodeValue,CodeDescription from  PIESReferenceFieldCode,PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and  PIESFieldId=32 order by CodeValue';
  if($db->dbname=='pcdbcache')
  {
   $sql='select CodeValue, CodeDescription from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.FieldId=PIESField.FieldId and PIESReferenceFieldCode.CodeValueID=PIESCode.CodeValueID and PIESField.FieldId=32 order by CodeValue';
  }

  if($stmt=$db->conn->prepare($sql))
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
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  $sql='select CodeDescription from PIESReferenceFieldCode, PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESReferenceFieldCode.PIESFieldId=32 and CodeValue=?';
  if($db->dbname=='pcdbcache')
  {
   $sql='select CodeDescription from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.FieldId=PIESField.FieldId and PIESReferenceFieldCode.CodeValueID=PIESCode.CodeValueID and PIESField.FieldId=32 and CodeValue=?';
  }

  if($stmt=$db->conn->prepare($sql))
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
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
//ccc
  $sql='select CodeValue,CodeDescription,FieldFormat from  PIESReferenceFieldCode,PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESFieldId=60 order by CodeDescription';
  if($db->dbname=='pcdbcache')
  {
   $sql='select CodeValue,CodeDescription,FieldFormat from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.FieldId=PIESField.FieldId and PIESReferenceFieldCode.CodeValueID=PIESCode.CodeValueID and PIESField.FieldId=60 order by CodeDescription';
  }
  if($stmt=$db->conn->prepare($sql))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $codes[]=array('code'=>$row['CodeValue'],'description'=>$row['CodeDescription'],'format'=>$row['FieldFormat']);
   }
  }
  $db->close();
  return $codes;    
 }

 function getPartDescriptionLanguageCodes()
 {
  $codes=array();
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  $sql='select CodeValue,CodeDescription from  PIESReferenceFieldCode,PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and  PIESFieldId=31 order by CodeDescription';
  if($db->dbname=='pcdbcache')
  {
   $sql='select CodeValue,CodeDescription,FieldFormat from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.FieldId=PIESField.FieldId and PIESReferenceFieldCode.CodeValueID=PIESCode.CodeValueID and PIESField.FieldId=31 order by CodeDescription';
  }

  if($stmt=$db->conn->prepare($sql))
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
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
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
 
 function EXPIcodeDescription($code)
 {
  $db = new mysql; $db->dbname=$db->pcdbname; 
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;} $db->connect();
  $value='unknown ('.$code.')';
  
  if($stmt=$db->conn->prepare('select ExpiCodeDescription from PIESExpiCode where ExpiCode=?'))
  {
   $stmt->bind_param('s', $code);      
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $value=$row['ExpiCodeDescription'];
   }
  }
  $db->close();
  return $value;
 }
 
 function getAllEXPIcodes()
 {
  $db = new mysql; $db->dbname=$db->pcdbname; $codes=array();
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  $sql='select * from PIESExpiCode order by Expicode';
  if($db->dbname=='pcdbcache'){$sql='select EXPICode as ExpiCode,EXPICodeDescription as ExpiCodeDescription from PIESEXPICode order by Expicode';}
  if($stmt=$db->conn->prepare($sql))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $codes[]=array('code'=>$row['ExpiCode'],'description'=>$row['ExpiCodeDescription']);
   }
  }
  $db->close();
  return $codes;
 }
 
 function validEXPIcode($EXPI)
 {
  $returnval=true;
  return $returnval;
 }
 
 
function getValidEXPIvalues($code)
 {
  $options=array();
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  if($stmt=$db->conn->prepare('select ExpiCode,CodeValue,CodeDescription from PIESReferenceFieldCode, PIESCode,PIESExpiCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESReferenceFieldCode.PIESExpiCodeId=PIESExpiCode.PIESExpiCodeId and ExpiCode=? order by ExpiCode,CodeValue'))
  {
   $stmt->bind_param('s', $code);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $options[]=array('code'=>$row['CodeValue'],'description'=>$row['CodeDescription']);
   }
  }
  
  if(count($options)==0)
  {// no valid value optioions exist for this EXPI code - see if the code itself is valid
   if($stmt=$db->conn->prepare('select ExpiCode,ExpiCodeDescription from PIESExpiCode where ExpiCode=?'))
   {
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
      $options[]=array('code'=>'*','description'=>'*');
    }
   }
  }
  $db->close();
  return $options;
 }

 function getUoMsForPackaging($typename)
 {
/*
|26 | Inner Quantity UOM                                        | H22                  |            10 |
|25 | Orderable Package                                         | H24                  |            10 |
|24 | UOM for Dimensions                                        | H40                  |            10 |
|23 | UOM for Weight                                            | H46                  |            10 |
|27 | Package UOM                                               | H15                  |            10 |
*/
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect(); 
  $records=array();
  $type=0;
  
  switch ($typename)
  {
   case 'Inner Quantity': $type=26; break;
   case 'Orderable Package': $type=25; break;
   case 'UOM for Dimensions': $type=24; break;
   case 'UOM for Weight': $type=23; break;
   case 'Package UOM': $type=27; break;
   default : break;
  }

 $sql='select CodeValue,CodeDescription from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.PIESFieldId=PIESField.PIESFieldId and PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESField.PIESFieldId=? order by CodeDescription';
 if($db->dbname=='pcdbcache')
 {
  $sql='select CodeValue,CodeDescription from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.FieldId=PIESField.FieldId and PIESReferenceFieldCode.CodeValueID=PIESCode.CodeValueID and PIESField.FieldId=? order by CodeDescription';
 }

  if($stmt=$db->conn->prepare($sql))
  {
   if($stmt->bind_param('i',$type))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $records[]=array('code'=>$row['CodeValue'],'description'=>$row['CodeDescription']);
     }
    }
   }
  }
  $db->close();
  return $records;     
 }

 
 function getUoMsForPrice()
 {
  $db = new mysql; $db->dbname=$db->pcdbname; 
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect(); 
  $records=array();

  $sql='select CodeValue,CodeDescription from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.PIESFieldId=PIESField.PIESFieldId and PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESField.PIESFieldId=34 order by CodeDescription';
  if($db->dbname=='pcdbcache')
  {
   $sql='select CodeValue,CodeDescription from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.FieldId=PIESField.FieldId and PIESReferenceFieldCode.CodeValueID=PIESCode.CodeValueID and PIESField.FieldId=34 order by CodeDescription';
  }

  if($stmt=$db->conn->prepare($sql))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $records[]=array('code'=>$row['CodeValue'],'description'=>$row['CodeDescription']);
    }
   }
  }
  $db->close();
  return $records;     
 }
 
 function getPriceTypeCodes()
 {
  $codes=array();
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();

  $sql='select CodeValue,CodeDescription from  PIESReferenceFieldCode,PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and  PIESFieldId=47 order by CodeValue';
  if($db->dbname=='pcdbcache')
  {
   $sql='select CodeValue,CodeDescription from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.FieldId=PIESField.FieldId and PIESReferenceFieldCode.CodeValueID=PIESCode.CodeValueID and PIESField.FieldId=47 order by CodeDescription';
  }

  if($stmt=$db->conn->prepare($sql))
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

 function priceTypeDescription($pricetype)
 {
  $description='not found';
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  if($stmt=$db->conn->prepare('select CodeValue,CodeDescription from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.PIESFieldId=PIESField.PIESFieldId and PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESField.PIESFieldId=47 and CodeValue=? order by CodeDescription'))
  {
   if($stmt->bind_param('s',$pricetype))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $description=$row['CodeDescription'];
     }
    }
   }
  }
  $db->close();
  return $description;
 }

 function getAssetOrientationViewCodes()
 {
  $codes=array();
  $db = new mysql; $db->dbname=$db->pcdbname;
  if($this->pcdbversion!==false){$db->dbname=$this->pcdbversion;}
  $db->connect();
  $sql="select CodeValue,CodeDescription from PIESReferenceFieldCode, PIESCode where PIESReferenceFieldCode.PIESCodeId=PIESCode.PIESCodeId and PIESReferenceFieldCode.PIESFieldId=103 and CodeDescription <>'User Defined' order by CodeDescription";
  if($db->dbname=='pcdbcache')
  {
   $sql="select CodeValue, CodeDescription from PIESReferenceFieldCode,PIESField,PIESCode where PIESReferenceFieldCode.FieldId=PIESField.FieldId and PIESReferenceFieldCode.CodeValueID=PIESCode.CodeValueID and PIESField.FieldId=103 and CodeDescription <>'User Defined' order by CodeValue";
  }

  if($stmt=$db->conn->prepare($sql))
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



}
?>
