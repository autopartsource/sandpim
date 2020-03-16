<?php
include_once("mysqlClass.php");

class interchange
{

 function addInterchange($partnumber,$competitorpartnumber,$brandAAIAID,$interchangequantity,$uom,$interchangenotes,$internalnotes)
 {
  $id=false;
  $db=new mysql; 
  $db->connect();
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
  $records=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from interchange where id=?'))
  {
   $stmt->bind_param('i',$interchangeid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'competitorpartnumber'=>$row['competitorpartnumber'],'brandAAIAID'=>$row['brandAAIAID'],'interchangequantity'=>$row['interchangequantity'],'uom'=>$row['uom'],'referenceitem'=>$row['referenceitem'],'interchangenotes'=>$row['interchangenotes'],'internalnotes'=>$row['internalnotes']);
   }
  }
  $db->close();
  return $records;   
 }

 function deleteInterchangeById($interchangeid)
 {
  $records=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('delete from interchange where id=?'))
  {
   $stmt->bind_param('i',$interchangeid);
   $stmt->execute();
  }
  $db->close();
  return $records;   
 }
 
 function getInterchangeByPartnumber($partnumber)
 {
  $records=false;
  $db=new mysql; 
  $db->connect();
  
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

 
}?>
