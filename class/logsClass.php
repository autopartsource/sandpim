<?php
include_once("mysqlClass.php");

class logs
{

 function logSystemEvent($eventtype,$userid,$text)
 {
  $db = new mysql;
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('insert into system_history (id,eventdatetime,eventtype,userid,description) values(null,now(),?,?,?)'))
  {
   $stmt->bind_param('sis',$eventtype,$userid,$text);
   $stmt->execute();
  }
  $db->close();
  return $userid;
 }

 function getSystemEvents($eventtype,$userid,$limit)
 {
  $db = new mysql;
  //$db->dbname='pim'; 
  $db->connect();
  $events=array();

  if($userid)
  {
   $sql='select * from system_history where eventtype like ? and userid=? order by eventdatetime desc limit ?';
  }
  else
  {
   $sql='select * from system_history where eventtype like ? order by eventdatetime desc limit ?';
  }

  if($stmt=$db->conn->prepare($sql))
  {
   if($userid)
   {
    $stmt->bind_param('sii',$eventtype,$userid,$limit);
   }
   else
   {
    $stmt->bind_param('si',$eventtype,$limit);
   }

   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $events[]=array('id'=>$row['id'],'eventdatetime'=>$row['eventdatetime'],'eventtype'=>$row['eventtype'],'userid'=>$row['userid'],'description'=>$row['description']);
   }
  }
  $db->close();
  return $events;
 }

 
 function getAppsEvents($limit)
 {
  $db=new mysql; $db->connect();
  $events=array();
  if($stmt=$db->conn->prepare('select * from application_history order by eventdatetime desc limit ?'))
  {
   $stmt->bind_param('i',$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $events[]=array('id'=>$row['id'],'applicationid'=>$row['applicationid'],'eventdatetime'=>$row['eventdatetime'],'userid'=>$row['userid'],'description'=>$row['description'],'new_oid'=>$row['new_oid']);
   }
   
   // sort the results ascending
   $sorted=array();
   for($i=count($events)-1; $i>=0; $i--)
   {
       $sorted[]=$events[$i];
   }
  }
  $db->close();
  return $sorted;
 }

 function getPartsEvents($limit)
 {
  $db=new mysql; $db->connect();
  $events=array();
  if($stmt=$db->conn->prepare('select * from part_history order by eventdatetime desc limit ?'))
  {
   $stmt->bind_param('i',$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $events[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'eventdatetime'=>$row['eventdatetime'],'userid'=>$row['userid'],'description'=>$row['description'],'new_oid'=>$row['new_oid']);
   }
  }
  
  // sort the results ascending
  $sorted=array();
  for($i=count($events)-1; $i>=0; $i--)
  {
   $sorted[]=$events[$i];
  }
  $db->close();
  return $sorted;
 }

 function getPartEvents($partnumber,$limit)
 {
  $db=new mysql; $db->connect();
  $events=array();
  if($stmt=$db->conn->prepare('select * from part_history where partnumber=? order by eventdatetime desc limit ?'))
  {
   $stmt->bind_param('si',$partnumber,$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $events[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'eventdatetime'=>$row['eventdatetime'],'userid'=>$row['userid'],'description'=>$row['description'],'new_oid'=>$row['new_oid']);
   }
  }
  
  // sort the results ascending
  $sorted=array();
  for($i=count($events)-1; $i>=0; $i--)
  {
   $sorted[]=$events[$i];
  }
  $db->close();
  return $sorted;
 }

 
 
 
 
 function getAssetsEvents($limit)
 {
  $db=new mysql; $db->connect();
  $events=array();
  if($stmt=$db->conn->prepare('select * from asset_history order by eventdatetime desc limit ?'))
  {
   $stmt->bind_param('i',$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $events[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'eventdatetime'=>$row['eventdatetime'],'userid'=>$row['userid'],'description'=>$row['description'],'new_oid'=>$row['new_oid']);
   }
  }
 
   // sort the results ascending
  $sorted=array();
  for($i=count($events)-1; $i>=0; $i--)
  {
   $sorted[]=$events[$i];
  }
  
  $db->close();
  return $sorted;
 }



}?>
