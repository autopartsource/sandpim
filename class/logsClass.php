<?php
include_once("mysqlClass.php");

class logs
{

 function logSystemEvent($eventtype,$userid,$text)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
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
  $db = new mysql; $db->dbname='pim'; $db->connect();
  $events=array();

  if($userid)
  {
   $sql='select * from system_history where eventtype=? and userid=? order eventdatetime desc limit ?';
  }
  else
  {
   $sql='select * from system_history where eventtype=? order eventdatetime desc limit ?';
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


}?>
