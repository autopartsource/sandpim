<?php
include_once("mysqlClass.php");

class activity
{
 private $requesturi;
 private $events=array();
 public $response;
         
 function __construct($_requesturi) 
 {
    $this->requesturi=$_requesturi;
    $limit=999;
    $sort='timestamp';
    $sortdirection='asc';
    $keyedparms=array();
    $temp=explode('?',$this->requesturi[3]);
    
    
    if(isset($temp[1]) && trim($temp[1])!='')
    {// there is stuff to the right of the ?mark like:  /sandpiper/v1/activity?limit=10&sort=xyz    chop it up by & character
        $parms=explode('&',$temp[1]);
        foreach($parms as $parm)
        {
            $parmparts=explode('=',$parm);
            $keyedparms[$parmparts[0]]=@$parmparts[1];
        }

        if(array_key_exists('limit', $keyedparms)){$limit=intval($keyedparms['limit']);}
        if(array_key_exists('sort', $keyedparms)){$sort=$keyedparms['sort'];}
        if(array_key_exists('sortdirection', $keyedparms)){$sortdirection=$keyedparms['sortdirection'];}
    }
    
    $this->getEvents($limit,$sort,$sortdirection);
      
    if(array_key_exists('nice', $keyedparms))
    {
        $this->response='<pre>'.print_r($this->events,true).'</pre>';
    }
    else
    {
        $this->response=json_encode($this->events);
    }
 }
    

 
 function getEvents($limit,$sorton,$sortdirection)
 {
  $db = new mysql; $db->connect(); $success=false;
    
  $orderby='id';
  $direction='desc';
  
  if($sorton=='planuuid' || $sorton=='subscriptionuuid' || $sorton=='timestamp')
  {
   $orderby=$sorton;
  }
  if($sortdirection=='asc')
  {
   $direction=$sortdirection;
  }      
  
  
  if($stmt=$db->conn->prepare('select * from sandpiperactivity order by '.$orderby.' '.$direction.' limit ?'))
  { 
   if($stmt->bind_param('i', $limit))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      $success=true;
      while($row = $db->result->fetch_assoc())
      {
       $this->events[]=array('id'=>$row['id'],'planuuid'=>$row['planuuid'],'subscriptionuuid'=>$row['subscriptionuuid'],'action'=>$row['action'],'timestamp'=>$row['timestamp']);
      }
     }
    }
   }
  }
  $db->close();
  return $success;
 }

 
 
 
    
    
}





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
