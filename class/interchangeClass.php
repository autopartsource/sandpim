<?php
include_once("mysqlClass.php");

class interchange
{

 function addInterchange($partnumber,$competitorpartnumber,$brandAAIAID,$interchangequantity,$uom,$interchangenotes,$internalnotes)
 {
  $id=false;
  $db=new mysql; $db->connect();

  if($stmt=$db->conn->prepare('delete from interchange where partnumber=? and brandAAIAID=? and interchangequantity=? and uom=?'))
  {
   if($stmt->bind_param('ssis',$partnumber,$brandAAIAID,$interchangequantity,$uom))
   {
    $stmt->execute();
   }
  }   
    
  if($stmt=$db->conn->prepare('insert into interchange (id,partnumber,competitorpartnumber,brandAAIAID,interchangequantity,uom,interchangenotes,internalnotes) values(null,?,?,?,?,?,?,?)'))
  {
   if($stmt->bind_param('sssisss',$partnumber,$competitorpartnumber,$brandAAIAID,$interchangequantity,$uom,$interchangenotes,$internalnotes))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    }//else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
   }//else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  }//else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
  return $id;
 }

 function getInterchangeById($interchangeid)
 {
  $db=new mysql; $db->connect();
  $records=array();
  
  if($stmt=$db->conn->prepare('select * from interchange where id=?'))
  {
   if($stmt->bind_param('i',$interchangeid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'competitorpartnumber'=>$row['competitorpartnumber'],'brandAAIAID'=>$row['brandAAIAID'],'interchangequantity'=>$row['interchangequantity'],'uom'=>$row['uom'],'referenceitem'=>$row['referenceitem'],'interchangenotes'=>$row['interchangenotes'],'internalnotes'=>$row['internalnotes']);
     }
    }
   }
  }
  $db->close();
  return $records;   
 }

 function deleteInterchangeById($interchangeid)
 {
  $db=new mysql; $db->connect();
  $result=false;
  
  if($stmt=$db->conn->prepare('delete from interchange where id=?'))
  {
   if($stmt->bind_param('i',$interchangeid))
   {
    if($stmt->execute())
    {
     $result=true;
    }
   }
  }
  $db->close();
  return $result;
 }
 
 function getInterchangeByPartnumber($partnumber)
 {
  $db=new mysql; $db->connect();
  $records=array();
  
  if($stmt=$db->conn->prepare('select * from interchange where partnumber=?'))
  {
   $stmt->bind_param('s',$partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'competitorpartnumber'=>$row['competitorpartnumber'],'brandAAIAID'=>$row['brandAAIAID'],'interchangequantity'=>$row['interchangequantity'],'uom'=>$row['uom'],'interchangenotes'=>$row['interchangenotes'],'internalnotes'=>$row['internalnotes']);
   }
  }
  $db->close();
  return $records;   
 }

 
 function getCompetitors()
 { // distinct list of competitors extracted from interchange table
     
  $db=new mysql; $db->connect();
  $records=array();
  
  if($stmt=$db->conn->prepare('select distinct brandAAIAID from interchange order by brandAAIAID'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('brandAAIAID'=>$row['brandAAIAID'],'name'=>$this->brandName($row['brandAAIAID']));
   }
  }
  $db->close();
  return $records;      
 }
 
 function brandName($brandAAIAID)
 {
  $db=new mysql; $db->connect();
  $name='not found ('.$brandAAIAID.')';
  
  if($stmt=$db->conn->prepare('select BrandName from brand where BrandID=?'))
  {
   if($stmt->bind_param('s',$brandAAIAID))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $name=$row['BrandName'];
     }
    }
   }
  }
  $db->close();
  return $name;   
 }
 
 
 function importBrandTable($data)
 {
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into brand values(?,?,?,?,?,?)'))
  {
   $BrandID=''; $BrandName=''; $BrandOwnerID=''; $BrandOwner=''; $ParentID=''; $ParentCompany='';
   $stmt->bind_param('ssssss',$BrandID, $BrandName, $BrandOwnerID, $BrandOwner, $ParentID, $ParentCompany);
   foreach($data as $record)
   {
    $BrandID=$record['BrandID']; $BrandName=$record['BrandName']; $BrandOwnerID=$record['BrandOwnerID']; $BrandOwner=$record['BrandOwner']; $ParentID=$record['ParentID']; $ParentCompany=$record['ParentCompany'];
    $stmt->execute();
   }
  }
  $db->close(); 
 }
 
  
 
 
 
}?>
