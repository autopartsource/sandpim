<?php
include_once("mysqlClass.php");

class qdb
{

 function qualifierText($qualifierid)
 {
  $text='not found';
  $db = new mysql; $db->dbname=$db->qdbname; $db->connect();
  if($stmt=$db->conn->prepare('select QualifierText from Qualifier where QualifierID=?'))
  {
   $stmt->bind_param('i', $qualifierid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $text=$row['QualifierText'];
   }
  }
  $db->close();
  return $text;
 }

 function getQualifiersBySearch($search)
 {
  $qualifiers=array();
  $findtags=array('&','<','>','"');
  $replacetags=array('&amp','&lt;','&gt;','&quot;');
  
  $db = new mysql; $db->dbname=$db->qdbname; $db->connect();
 // if($stmt=$db->conn->prepare('select QualifierID,QualifierText,QualifierTypeID,ExampleText from Qualifier where QualifierText like ?'))
  
  if($stmt=$db->conn->prepare('select QualifierID,QualifierText,QualifierTypeID,ExampleText from Qualifier WHERE match(QualifierText) against(? IN BOOLEAN MODE)'))
  {
   $stmt->bind_param('s', $search);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $qualifiers[]=array('qualifierid'=>$row['QualifierID'],'qualifiertext'=>$row['QualifierText'],'htmlsafequalifiertext'=> str_replace($findtags,$replacetags,$row['QualifierText']));
   }
  }
  $db->close();
  return $qualifiers;
 }

 
 function version()
 {
  $versiondate='not found';
  $db = new mysql; $db->dbname=$db->qdbname; $db->connect();
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
