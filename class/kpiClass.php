<?php
include_once("mysqlClass.php");

class kpi
{

 function recordMetric($datakey,$value)
 {
  $db=new mysql; $db->connect();    
  if($stmt=$db->conn->prepare('insert into metrics (id,datakey,capturedate,metric) values(null,?,date(now()),?)'))
  {
   if($stmt->bind_param('sd',$datakey,$value))
   {
    $stmt->execute();
   }
  }
  $db->close();
 }

 
 function getMetric($datakey,$fromdate,$todate)
 {
  $db=new mysql; $db->connect();
  $data=array();
  
  if($stmt=$db->conn->prepare('select * from metrics where datakey like ?  and capturedate>=? and capturedate<=? order by capturedate'))
  {
   if($stmt->bind_param('sss',$datakey,$fromdate,$todate))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
       $data[]=array('value'=>$row['metric'],'capturedate'=>$row['capturedate']);
     }
    }
   }
  }
  $db->close();
  return $data;
 }


 
}?>
