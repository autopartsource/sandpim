<?php
include_once("mysqlClass.php");

class sandpiperPrimary
{
    
    // back-end methods for sandpiper interaction as primary 

    // by the sandpiper official (GO pgsql) schema, a subscription only contains 1 slice.
    
    // slice = partcategory
    // the same slice (partcategory identified by sandpiper with a UUID) can be in multiple subscriptions
    // slices (parttypes) connect (multiple) to a plan
    // 
    // grain = aces-item (collections of "Apps" that would be in an ACES file)    or    pies-item (an individual "Item" segment that would be on a PIES file)
    // querying the data for a grain:?


    
function uuidv4()
{
  $randodata = file_get_contents('/dev/urandom', NULL, NULL, 0, 16);
  $uuid=vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($randodata), 4));

  // all 128 bits are now randomly generated in the hex output. Set the "M" (version) nibble to "4" by over-writing it 
  $uuid= substr_replace($uuid,'4', 14, 1);
  
  // set the "N" (variant) nibble to a,b,8 or 9 to specify the MSB as set and the second most significant bit to clear
  $valid_n_hex_nibbles=array('a','b','8','9');
  $n_hex_nibble=$valid_n_hex_nibbles[random_int(0, 3)];
  $uuid= substr_replace($uuid, $n_hex_nibble, 19, 1);
          
  return $uuid;
}
   
    
function createSubscription($planid, $sliceid, $slicetype, $partcategory, $metadata)    
{
 $this->getSlice($sliceid);
 
 $db = new mysql; $db->connect(); $returnval=false;
 if($stmt=$db->conn->prepare('insert into '))
 {
  if($stmt->bind_param('si', $hash,$sliceid))
  {      
   if($stmt->execute())
   {
        
    $subscriptionuuid= $this->uuidv4();
    $returnval=$subscriptionuuid;
   }
  }
 }
 $db->close();
 return $returnval;
    
}
    
    
 // compute and update slice hashes
 // This is generally done by a housekeeping background process. It could potentially
 // be invoked during a sync 
 function updateSliceHash($sliceid)
 {
  $db = new mysql; $db->connect(); $returnval=false;
  $grainlist=$this->getSliceGrainList($sliceid);
  $hash=md5(implode('',$grainlist));
  if($stmt=$db->conn->prepare('update slice set slicehash=? where id=?'))
  {
   if($stmt->bind_param('si', $hash,$sliceid))
   {      
    if($stmt->execute())
    {
     $returnval=$hash;
    }
   }
  }
  $db->close();
  return $returnval;
 }


    
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



 function getPlans()
 {
  $db = new mysql; $db->connect(); $plans=array();
  if($stmt=$db->conn->prepare('select * from plan'))
  {
   if($stmt->execute())
   {
    if($db->result = $stmt->get_result())
    {
     while($row = $db->result->fetch_assoc())
     {
      $plans[]=array('id'=>$row['id'],'description'=>$row['description'],'planuuid'=>$row['planuuid'],'receiverprofileid'=>$row['receiverprofileid'],'plannmetadata'=>$row['plannmetadata']);
     }
    }
   }
  }
  $db->close();
  return $plans;
 }

 
 function getPlanById($id)
 {
  $db = new mysql; $db->connect(); $plan=false;
  if($stmt=$db->conn->prepare('select * from plan where id=?'))
  {
   if($stmt->bind_param('i', $id))
   {      
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      while($row = $db->result->fetch_assoc())
      {
       $plan=array('id'=>$row['id'],'description'=>$row['description'],'planuuid'=>$row['planuuid'],'receiverprofileid'=>$row['receiverprofileid'],'plannmetadata'=>$row['plannmetadata']);
      }
     }
    }
   }
  }
  $db->close();
  return $plan;
 }


 function getPlanSlices($planid)
 {
  $db = new mysql; $db->connect(); $slices=array();
  if($stmt=$db->conn->prepare('select plan_slice.id as id, description, sliceid, partcategory, subscriptionuuid, sliceuuid, subscriptionmetadata,slicetype,slicehash from slice, plan_slice where plan_slice.sliceid=slice.id and plan_slice.planid=?'))
  {
   if($stmt->bind_param('i', $planid))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      while($row = $db->result->fetch_assoc())
      {
       $slices[]=array('id'=>$row['id'],'description'=>$row['description'],'sliceid'=>$row['sliceid'],'partcategory'=>$row['partcategory'],'subscriptionuuid'=>$row['subscriptionuuid'],'sliceuuid'=>$row['sliceuuid'],'subscriptionmetadata'=>$row['subscriptionmetadata'],'slicetype'=>$row['slicetype'],'slicehash'=>$row['slicehash']);
      }
     }
    }
   }
  }
  $db->close();
  return $slices;
 }
 
 function getAllSlices()
 {
  $db = new mysql; $db->connect(); $slices=array();
  if($stmt=$db->conn->prepare('select * from slice'))
  {
   if($stmt->execute())
   {
    if($db->result = $stmt->get_result())
    {
     while($row = $db->result->fetch_assoc())
     {
      $slices[]=array('id'=>$row['id'],'description'=>$row['description'],'partcategory'=>$row['partcategory'],'sliceuuid'=>$row['sliceuuid'],'slicemetadata'=>$row['slicemetadata'],'slicetype'=>$row['slicetype'],'slicehash'=>$row['slicehash']);
     }
    }
   }
  }
  $db->close();
  return $slices;
 }
 
 
 
 
 function sliceIdofSubscription($subscriptionuuid)
 {
     
     
     
 }
 
 
 
 
 function getSlice($sliceid)
 {
  $db = new mysql; $db->connect(); $slice=false;
  if($stmt=$db->conn->prepare('select * from slice where id=?'))
  {
   if($stmt->bind_param('i', $sliceid))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      if($row = $db->result->fetch_assoc())
      {// got slice record
       $slice=array('description'=>$row['description'], 'sliceuuid'=>$row['sliceuuid'], 'slicetype'=>$row['slicetype'], 'slicemetadata'=>$row['slicemetadata'], 'partcategory'=>$row['partcategory'],'slicehash'=>$row['slicehash']);
      }
     }
    }
   }
  }
  $db->close();
  return $slice;
 }
 
 
 
 
 
 

 function getSliceGrainList($sliceid)
 {
   // slice carries type (aces-item,pies-item,asset)
  $db = new mysql; $db->connect(); $list=array();
  if($stmt=$db->conn->prepare('select sliceuuid,slicetype,partcategory from slice where id=?'))
  {
   if($stmt->bind_param('i', $sliceid))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      if($row = $db->result->fetch_assoc())
      {// got slice record
       $sliceuuid=$row['sliceuuid'];
       $slicetype=$row['slicetype'];
       $partcategory=$row['partcategory'];
          
       switch ($slicetype)
       {
        case 'pies-item':
            //get part oids for the parts in slice's partcategory 
            $list= $this->getPartOIDsByPartcategory($partcategory);       
         break;
     
        case 'aces-item':
            // get app oids for the apps connected to the parts in slice's partcategory
            $list=$this->getAppOIDsByPartcategory($partcategory);
         break;
     
        case 'asset':
            // get asset oids for the assets connected to the parts  in slice's partcategory
            $list=$this->getAssetOIDsByPartcategory($partcategory);
         break;
     
        default: break;
       }   
      }
     }
    }
   }     
  }
  $db->close();
  return $list;
 }

 
 
 
function getPartOIDsByPartcategory($partcategory)
{
  $db = new mysql; $db->connect(); $oids=array();
  if($stmt=$db->conn->prepare('select oid from part where partcategory=?'))
  {
   if($stmt->bind_param('i', $partcategory))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      while($row = $db->result->fetch_assoc())
      {
       $oids[]=$row['oid'];
      }
     }
    }
   }
  }
  $db->close();
  return $oids;
 }


function getAppOIDsByPartcategory($partcategory)
{
  $db = new mysql; $db->connect(); $oids=array();
  if($stmt=$db->conn->prepare('select application.oid from application, part where application.partnumber =part.partnumber and part.partcategory=?'))
  {
   if($stmt->bind_param('i', $partcategory))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      while($row = $db->result->fetch_assoc())
      {
       $oids[]=$row['oid'];
      }
     }
    }
   }
  }
  $db->close();
  return $oids;
 }

function getAssetOIDsByPartcategory($partcategory)
{
  $db = new mysql; $db->connect(); $oids=array();
  if($stmt=$db->conn->prepare('select distinct asset.oid from part,part_asset,asset where part.partnumber=part_asset.partnumber and part_asset.assetid=asset.assetid and part.partcategory=?'))
  {
   if($stmt->bind_param('i', $partcategory))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      while($row = $db->result->fetch_assoc())
      {
       $oids[]=$row['oid'];
      }
     }
    }
   }
  }
  $db->close();
  return $oids;
 }
 
 
 
    
 // get data hooked to an oid   
 function getOIDdata($oid)
 {
  $data=false;
  $db = new mysql; $db->connect();

  // see if this oid is attched to an app
  if($stmt=$db->conn->prepare('select * from application where oid=?'))
  {
   $stmt->bind_param('s', $oid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   { // found a hit in applications - call the getApp function to get the actual data
    $data=$row;
    $db->close();
    return $data;
   }
  }

  // see if this oid is attached to a part
  if($stmt=$db->conn->prepare('select * from part where oid=?'))
  {
   $stmt->bind_param('s', $oid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   { // found a hit in part - call the getPart function to get the actual data
    $data=$row;
    $db->close();
    return $data;
   }
  }

  // see if this oid is attached to an asset

  if($stmt=$db->conn->prepare('select * from asset where oid=?'))
  {
   $stmt->bind_param('s', $oid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   { // found a hit in part - call the getPart function to get the actual data
    $data=$row;
    $db->close();
    return $data;
   }
  }
 
  
  
  
  
  $db->close();
  return $data;
 }


    
 
 
}
?>
