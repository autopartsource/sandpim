<?php
include_once("mysqlClass.php");

class sandpiper
{
    
    // back-end methods for sandpiper interaction 

    
 // compute and update slice hashes
 // This is generally done by a housekeeping background process. It could potentially
 // be invoked during a sync 
    
 // get slices in subscription
    
 // get object data
 // Parameters: specific OID
 // Return: associative array of object (app, part, asset) data
 //  for an assst, the actual content of the asset (jpg, pdf, etc) is not returned - just 
 //  the meta-data.
    
 // get objects data in a slice
 // Parameters: sliceID
 // Return: an array of associative arrays of object (app, part, asset) data
 //  for an assst, the actual content of the asset (jpg, pdf, etc) is not returned - just 
 //  the meta-data.
    
 
 // object history
    
    
    
 // get data hooked to an oid   
 function getOIDdata($oid)
 {
  $data=false;
  $db = new mysql; $db->connect();

  // see if this oid is attched to an app
  if($stmt=$db->conn->prepare('select id from application where oid=?'))
  {
   $stmt->bind_param('s', $oid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   { // found a hit in applications - call the getApp function to get the actual data
    $appid=$row['id'];
    $app=$this->getApp($appid);
    $data=array('oid'=>$oid,'type'=>'app',$data=$app);
    $db->close();
    return $data;
   }
  }

  // see if this oid is attached to a part
  if($stmt=$db->conn->prepare('select partnumber from part where oid=?'))
  {
   $stmt->bind_param('s', $oid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   { // found a hit in part - call the getPart function to get the actual data
    $partnumber=$row['partnumber'];
    $part=$this->getPart($partnumber);
    $data=array('oid'=>$oid,'type'=>'part',$data=$part);
    $db->close();
    return $data;
   }
  }

  // see if this oid is attached to an asset

  $db->close();
  return $data;
 }


    
 
 
}
?>
