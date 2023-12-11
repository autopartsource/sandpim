<?php
include_once("mysqlClass.php");

class qdb
{
 public $qdbversion;
    
 public function __construct($_qdbversion=false) 
 {
  $this->qdbversion=$_qdbversion;
  if(!$_qdbversion)
  { // no secific vsersion was passed in. Consult pim database for the name
    // of the active vcdb database. It will be something like vcdb20210827      
   $db = new mysql; $db->connect();
   if($stmt=$db->conn->prepare("select configvalue from config where configname='qdbProductionDatabase'"))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      if($row = $db->result->fetch_assoc())
      {
       $this->qdbversion=$row['configvalue'];
      }
     }
    }
    $db->close();
   }
  }  
 }

//get the human-readable rendering of a specific QdbID. if an array of parm strings
// is supplied, the placeholders (<p2 type="idlist"/>) will be replaced by the
// respective elements in the array. If no parms array is given, the placeholders
// will be left in the string for a human to see
   
 function qualifierText($qualifierid,$parms=false)
 {
  $text='not found';
  $db = new mysql; $db->dbname=$db->qdbname; if($this->qdbversion!==false){$db->dbname=$this->qdbversion;} $db->connect();
  if($stmt=$db->conn->prepare('select QualifierText from Qualifier where QualifierID=?'))
  {
   $stmt->bind_param('i', $qualifierid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $text=$row['QualifierText'];
    
    if($parms)
    {
     $parmtypes= $this->parmTypes($text);
     $translate=array();
     foreach($parmtypes as $i=>$parmtype)
     {
      if(array_key_exists($i, $parms))
      {
       $translate['<p'.($i+1).' type="'.$parmtype.'"/>']=$parms[$i];
      }
     }
     $text=strtr($text,$translate);
    }
   }
  }
  
  $db->close();
  return $text;
 }

 
 
 function parmTypes($text)
 {
  // extract a list of parm types from qdb text
  //<p1 type="name"/> Brake Code <p2 type="idlist"/>
  //  will result in array('name','idlist');   
  $parmtypes=array();
  for($i=1;$i<=9;$i++)
  {
   $start=strpos($text,'<p'.$i.' type="');
   if($start!==false)
   {
    $end=strpos($text,'"/>',$start);
    if($end!==false)
    {
     $start+=10;
     $parmtypes[]= substr($text, $start,($end-$start));
    }  
   }
  }
  return $parmtypes;
 }
    
  
 function getQualifiersBySearch($search,$type=false)
 {
  $qualifiers=array();
  $findtags=array('&','<','>','"');
  $replacetags=array('&amp','&lt;','&gt;','&quot;');
  $typeclause=''; if($type){$typeclause=' and QualifierTypeID='.intval($type);}
  
  $db = new mysql; $db->dbname=$db->qdbname; if($this->qdbversion!==false){$db->dbname=$this->qdbversion;} $db->connect();
  if($stmt=$db->conn->prepare('select QualifierID,QualifierText,QualifierTypeID,ExampleText from Qualifier WHERE match(QualifierText) against(? IN BOOLEAN MODE) '.$typeclause))
  {
   $stmt->bind_param('s', $search);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {    
    $cleaned=preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $row['QualifierText']); // this is a greasy hack - unicode needs to handled correctly here, but I had to make it work fast.
    $qualifiers[]=array('qualifierid'=>$row['QualifierID'],'qualifiertext'=>$cleaned,'htmlsafequalifiertext'=> str_replace($findtags,$replacetags,$cleaned),'parmtypes'=>$this->parmTypes($cleaned));
   }
  }
  $db->close();
  return $qualifiers;
 }


 function getAllQdbs()
 {
  $qualifiers=array();
  $db = new mysql; $db->dbname=$db->qdbname; if($this->qdbversion!==false){$db->dbname=$this->qdbversion;} $db->connect();
  if($stmt=$db->conn->prepare('select QualifierID,QualifierText,QualifierTypeID from Qualifier'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $qualifiers[$row['QualifierID']]=array('qualifiertext'=>$row['QualifierText']);
   }
  }
  $db->close();
  return $qualifiers;
 }

  function addDatabaseFulltextIndex($table,$field)
 {
     // AutoCare's mysql version of the Qdb (as of 4/2022) does not have all the needed indexes for effecient lookups
     // specifically, it's missing a fulltext index on qualifiertext
     // ALTER TABLE Qualifier  ADD FULLTEXT(QualifierText)  
  $result='';
  $db = new mysql; $db->dbname=$db->qdbname; if($this->qdbversion!==false){$db->dbname=$this->qdbversion;} $db->connect();
  if($stmt=$db->conn->prepare('alter table '.$table.' add FULLTEXT('.$field.')'))
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

 function version()
 {
  $versiondate='not found';
  $db = new mysql; $db->dbname=$db->qdbname; if($this->qdbversion!==false){$db->dbname=$this->qdbversion;} 
  $db->connect();
  if($stmt=$db->conn->prepare('select date(VersionDate) as qdbversion from Version'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $versiondate=$row['qdbversion'];
   }
  }
  $db->close();
  return $versiondate;
 }
 
}
?>
