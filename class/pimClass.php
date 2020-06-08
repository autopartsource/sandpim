<?php
/*
 core functions - mostly related to applications
*/
include_once("mysqlClass.php");


class pim
{


 function getAppsByBasevehicleid($basevehicleid,$appcategories)
 {
  $categoryarray=array(); foreach($appcategories as $appcategory){$categoryarray[]=intval($appcategory);} $categorylist=implode(',',$categoryarray); // sanitize input
  $db = new mysql; 
//  $db->dbname='pim';
  $db->connect();
  $apps=array();
  if($stmt=$db->conn->prepare('select * from application where basevehicleid=? and appcategory in('.$categorylist.') order by partnumber'))
  {
   $stmt->bind_param('i', $basevehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributes=$this->getAppAttributes($row['id']);
    $attributeshash=$this->appAttributesHash($attributes);
    $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'appcategory'=>$row['appcategory'],'attributes'=>$attributes,'attributeshash'=>$attributeshash);
   }
  }
  $db->close();
  return $apps;
 }

 function getAppsByAppcategories($appcategories)
 {
  $categoryarray=array(); foreach($appcategories as $appcategory){$categoryarray[]=intval($appcategory);} $categorylist=implode(',',$categoryarray); // sanitize input
  $db = new mysql; 
//  $db->dbname='pim';
  $db->connect();
  $apps=array();
  if($stmt=$db->conn->prepare('select * from application where appcategory in('.$categorylist.') order by partnumber'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributes=$this->getAppAttributes($row['id']);
    $attributeshash=$this->appAttributesHash($attributes);
    $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'appcategory'=>$row['appcategory'],'attributes'=>$attributes,'attributeshash'=>$attributeshash);
   }
  }
  $db->close();
  return $apps;
 }

 function getAppsByPartnumber($partnumber)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  $apps=array();
  if($stmt=$db->conn->prepare('select * from application where partnumber=?'))
  {
   $stmt->bind_param('s', $partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributes=$this->getAppAttributes($row['id']);
    $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'appcategory'=>$row['appcategory'],'attributes'=>$attributes);
   }
  }
  $db->close();
  return $apps;
 }


 function getFavoriteMakes()
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $makes=array();
  if($stmt=$db->conn->prepare('select * from Make order by MakeName'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $makes[]=array('id'=>$row['MakeID'],'name'=>$row['MakeName']);
   }
  }
  $db->close();
  return $makes;
 }

 function getFavoriteParttypes()
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  $parttypes=array();
  if($stmt=$db->conn->prepare('select * from parttype order by `name`'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $parttypes[]=array('id'=>$row['id'],'name'=>$row['name']);
   }
  }
  $db->close();
  return $parttypes;
 }
 
 function getFavoritePositions()
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  $positions=array();
  if($stmt=$db->conn->prepare('select * from position order by `name`'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $positions[]=array('id'=>$row['id'],'name'=>$row['name']);
   }
  }
  $db->close();
  return $positions;
 }

 function getApp($appid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $app=false;
  if($stmt=$db->conn->prepare('select * from application where id=?'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $attributes=$this->getAppAttributes($appid);
    $attributeshash=$this->appAttributesHash($attributes);
    $app=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'internalnotes'=>base64_decode($row['internalnotes']),'cosmetic'=>$row['cosmetic'],'appcategory'=>$row['appcategory'],'attributes'=>$attributes,'attributeshash'=>$attributeshash);
   }
  }
  $db->close();
  return $app;
 }

 function getOIDofApp($appid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $oid='';
  if($stmt=$db->conn->prepare('select oid from application where id=?'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $oid=$row['oid'];
   }
  }
  $db->close();
  return $oid;
 }


 function getAppAttributes($appid)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  $attributes=array();
  if($stmt=$db->conn->prepare('select * from application_attribute where applicationid=? order by sequence'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $pairtemp=array('name'=>$row['name'],'value'=>$row['value']);
    $attributes[]=array('id'=>$row['id'],'name'=>$row['name'],'value'=>$row['value'],'type'=>$row['type'],'sequence'=>$row['sequence'],'cosmetic'=>$row['cosmetic']);
   }
  }
  $db->close();
  return $attributes;
 }

 function appAttributesHash($attributes)
 {
  $hashinput='';
  foreach($attributes as $attribute)
  {
   $hashinput.=$attribute['name'].$attribute['value'].$attribute['type'].$attribute['sequence'].$attribute['cosmetic'];
  }
  return md5($hashinput);
 }

 function cleansequenceAppAttributes($appid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $attributes=array();
  if($stmt=$db->conn->prepare('select id from application_attribute where applicationid=? order by sequence'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc()){$attributes[]=$row['id'];}
  }

  $sequence=1;
  if($stmt=$db->conn->prepare('update application_attribute set sequence=? where id=?'))
  {
   $stmt->bind_param('ii',$sequence,$id);
   foreach($attributes as $id)
   {
    $stmt->execute();
    $sequence++;
   }
  }
  $db->close();
 }





 function toggleAppAttributeCosmetic($appid,$attributeid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application_attribute set cosmetic=cosmetic XOR 1 where applicationid=? and id=?'))
  {
   $this->updateAppOID($appid);
   $stmt->bind_param('ii', $appid,$attributeid);
   $stmt->execute();
  } //else{print_r($db->conn->error);}
  $db->close();
 }

 function incAppAttributeSequence($appid,$attributeid)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('update application_attribute set sequence=sequence+1 where applicationid=? and id=?'))
  {
//   $this->updateAppOID($appid);
   $stmt->bind_param('ii', $appid,$attributeid);
   $stmt->execute();
  } //else{print_r($db->conn->error);}
  $db->close();
 }

 function deleteAppAttribute($appid,$attributeid)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('delete from application_attribute where applicationid=? and id=?'))
  {
   $this->updateAppOID($appid);
   $stmt->bind_param('ii', $appid,$attributeid);
   $stmt->execute();
  } // else{print_r($db->conn->error);}
  $db->close();
 }

 function highestAppAttributeSequence($appid)
 {
  $topsequence=0;
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('select max(sequence) as topsequence from application_attribute where applicationid=?'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $topsequence=intval($row['topsequence']);
   }
  }  //else{print_r($db->conn->error);}
  $db->close();
  return $topsequence;
 }

 function getPart($partnumber)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $part=false;
  if($stmt=$db->conn->prepare('select part.*,partcategory.name,partcategory.brandID from part left join partcategory on part.partcategory=partcategory.id where partnumber=?'))
  {
   $stmt->bind_param('s', $partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $part=array('partnumber'=>$row['partnumber'],
        'oid'=>$row['oid'],
        'parttypeid'=>$row['parttypeid'],
        'lifecyclestatus'=>$row['lifecyclestatus'],
        'partcategory'=>$row['partcategory'],
        'replacedby'=>$row['replacedby'],
        'internalnotes'=> base64_decode($row['internalnotes']),
        'description'=>$row['description'],'GTIN'=>$row['GTIN'],'UNSPC'=>$row['UNSPC'],
        'brandid'=>$row['brandID']
            );
    
   }
  }
  $db->close();
  return $part;
 }

 function getParts($partnumber,$matchtype,$limit)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $parts=array();
  $sql='select part.*,partcategory.name as partcategoryname from part left join partcategory on part.partcategory=partcategory.id where partnumber like ? order by partnumber limit ?';

  if($stmt=$db->conn->prepare($sql))
  {
   $searchstring=$partnumber;
   if($matchtype=='contains'){$searchstring='%'.$partnumber.'%';}
   if($matchtype=='startswith'){$searchstring=$partnumber.'%';}

   $stmt->bind_param('si', $searchstring, $limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $parts[]=array('partnumber'=>$row['partnumber'],'oid'=>$row['oid'],'parttypeid'=>$row['parttypeid'],'lifecyclestatus'=>$row['lifecyclestatus'],'partcategory'=>$row['partcategory'],'partcategoryname'=>$row['partcategoryname'],'replacedby'=>$row['replacedby'],'description'=>$row['description']);
   }
  }//else{echo 'prepare';}
  $db->close();
  return $parts;
 }


 function getOIDdata($oid)
 {
  $data=false;
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();

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

 function getOIDsInSlice($sliceid,$limit)
 {
  $oids=array();
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();

  // consult slice table to get a list of appcategories to query the application table for 

  $appcategories=array(0=>17);
  $categoryarray=array(); foreach($appcategories as $appcategory){$categoryarray[]=intval($appcategory);} $categorylist=implode(',',$categoryarray); // sanitize input


  if($stmt=$db->conn->prepare('select oid from application where status=0 and appcategory in('.$categorylist.') limit ?'))
  {
   $stmt->bind_param('i', $limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $oids[]=$row['oid'];
   }
  }

  // consult slice table to get a list of assetcategories to query the asset table for 




  $db->close();
  return $oids;
 }


 function updateAppOID($appid)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('update application set oid=? where id=?'))
  {
   $oid=$this->newoid();
   $stmt->bind_param('si', $oid, $appid);
   $stmt->execute();
  }
  $db->close();
  return $oid;
 }

  function getOIDofPart($partnumber)
 {
  $db = new mysql; $db->connect();
  $oid='';
  if($stmt=$db->conn->prepare('select oid from part where partnumber=?'))
  {
   $stmt->bind_param('s', $partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $oid=$row['oid'];
   }
  }
  $db->close();
  return $oid;
 }


 function updatePartOID($partnumber)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('update part set oid=? where partnumber=?'))
  {
   $oid=$this->newoid();
   $stmt->bind_param('ss', $oid, $partnumber);
   $stmt->execute();
  }
  $db->close();
  return $oid;
 }
 
 function setPartParttype($partnumber,$parttypeid,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set parttypeid=? where partnumber=?'))
  {
   $stmt->bind_param('is',$parttypeid,$partnumber);
   $stmt->execute();
   if($updateoid){$this->updatePartOID($partnumber);}
  }
  $db->close();
 }
 
 function setPartLifecyclestatus($partnumber,$lifecyclestatus,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set lifecyclestatus=? where partnumber=?'))
  {
   $stmt->bind_param('ss',$lifecyclestatus,$partnumber);
   $stmt->execute();
   if($updateoid){$this->updatePartOID($partnumber);}
  }
  $db->close();
 }
 
 function setPartCategory($partnumber,$partcategory,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set partcategory=? where partnumber=?'))
  {
   $stmt->bind_param('is',$partcategory,$partnumber);
   $stmt->execute();
   if($updateoid){$this->updatePartOID($partnumber);}
  }
  $db->close();
 }
 
 function setPartInternalnotes($partnumber,$internalnotes)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set internalnotes=? where partnumber=?'))
  {
   $encodednotes=base64_encode($internalnotes);
   $stmt->bind_param('ss', $encodednotes,$partnumber);
   $stmt->execute();
  }else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }
 
 function setPartDescription($partnumber,$description,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set description=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $description,$partnumber);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }

 function setPartGTIN($partnumber,$gtin,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set GTIN=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $gtin,$partnumber);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }

 function setPartUNSPC($partnumber,$unspc,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set UNSPC=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $unspc,$partnumber);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }

 
 function getAppCategories()
 {
  $categories=array();
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('select id,name,logouri from appcategory order by name'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $categories[]=array('id'=>$row['id'],'name'=>$row['name'],'logouri'=>$row['logouri']);
   }
  }
  $db->close();
  return $categories;
 }

 
 function getPartAttribute($partnumber,$PAID,$attributename)
 {
  $attributes=false;
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('select id,PAID,userDefinedAttributeName,`value`,uom from part_attribute where partnumber=? and PAID=? and userDefinedAttributeName=?'))
  {
   $stmt->bind_param('sis',$partnumber,$PAID,$attributename);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributes[]=array('id'=>$row['id'],'PAID'=>$row['PAID'],'value'=>$row['userDefinedAttributeName'],'value'=>$row['value'],'uom'=>$row['uom']);
   }
  }
  $db->close();
  return $attributes;
 }

 function getPartAttributeById($attributeid)
 {
  $db = new mysql; $db->connect();
  $attribute=false;
  if($stmt=$db->conn->prepare('select id,partnumber,PAID,userDefinedAttributeName,`value`,uom from part_attribute where id=?'))
  {
   $stmt->bind_param('i',$attributeid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $attribute=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'name'=>$row['userDefinedAttributeName'],'PAID'=>$row['PAID'],'value'=>$row['userDefinedAttributeName'],'value'=>$row['value'],'uom'=>$row['uom']);
   }
  }
  $db->close();
  return $attribute;
 }

 
 
 
 
 function getPartAttributes($partnumber)
 {
  $attributes=false;
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('select id,PAID,userDefinedAttributeName,`value`,uom from part_attribute where partnumber=?'))
  {
   $stmt->bind_param('s',$partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributes[]=array('id'=>$row['id'],'PAID'=>$row['PAID'],'name'=>$row['userDefinedAttributeName'],'value'=>$row['value'],'uom'=>$row['uom']);
   }
  }
  $db->close();
  return $attributes;
 }


 function writePartAttribute($partnumber,$PAID,$attributename,$attributevalue,$uom)
 { // PAID of 0 implies a user-defned attribute 
  $id=false;
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('insert into part_attribute (id,partnumber,PAID,userDefinedAttributeName,`value`,uom) values(null,?,?,?,?,?)'))
  {
   $stmt->bind_param('sisss',$partnumber,$PAID,$attributename,$attributevalue,$uom);
   $stmt->execute();
   $id=$db->conn->insert_id;
  } // else{print_r($db->conn->error);}

  $db->close();
  return $id;
 }

 function updatePartAttribute($partnumber,$PAID,$attributename,$attributevalue,$uom)
 { // PAID of 0 implies a user-defned attribute 
  $id=false;
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update part_attribute set `value`=?,uom=? where partnumber=? and PAID=? and userDefinedAttributeName=?'))
  {
   $stmt->bind_param('sssis',$attributevalue,$uom,$partnumber,$PAID,$attributename);
   $stmt->execute();
   $id=$db->conn->insert_id;
  } // else{print_r($db->conn->error);}

  $db->close();
  return $id;
 }





 function deletePartAttribute($attributeid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from part_attribute where id=?'))
  {
   $stmt->bind_param('i',$attributeid);
   $stmt->execute();
  } // else{print_r($db->conn->error);}
  $db->close();
 }

  
 function createPartcategory($name,$partcategory)
 {
  $db=new mysql; $db->connect();
  $success=false; $brandid='';
  if(!$this->validPartcategoryid($partcategory) && !$this->existingPartcategoryName($name))
  {
   if($partcategory=='')
   {
    if($stmt=$db->conn->prepare('insert into partcategory (id,name,brandID) values(null,?,?)'))
    {
     if($stmt->bind_param('ss', $name, $brandid))
     {
      $success=$stmt->execute();
     }//  else{echo 'problem with bind';}
    }//  else{echo 'problem with prepare';}
   }
   else
   {
    if($stmt=$db->conn->prepare('insert into partcategory (id,name,brandID) values(?,?,?)'))
    {
     if($stmt->bind_param('iss', $partcategory, $name, $brandid))
     {
      $success=$stmt->execute();
     }//  else{echo 'problem with bind';}
    }//  else{echo 'problem with prepare';}
   }
  }//  else{echo 'already exists';}
  $db->close();
  return $success;
 }

 function deletePartcategory($partcategory)
 {
  $db=new mysql; $db->connect();
  $success=false;
  if(!$this->countPartsByPartcategory($partcategory))
  {
    if($stmt=$db->conn->prepare('delete from partcategory where id=?'))
    {
     if($stmt->bind_param('i', $partcategory))
     {
      $success=$stmt->execute();
     }  //else{echo 'problem with bind';}
    }  //else{echo 'problem with prepare';}
  }  //else{echo 'already exists';}
  $db->close();
  return $success;
 }

 function updatePartcategoryName($partcategory,$name)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update partcategory set `name`=? where id=?'))
  {
   $stmt->bind_param('si', $name,$partcategory);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }
 
 function updatePartcategoryBrandID($partcategory,$brandID)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update partcategory set `brandID`=? where id=?'))
  {
   $stmt->bind_param('si', $brandID,$partcategory);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }


 
 
 function createAppCategory($name,$appcategory)
 {
  $db=new mysql; $db->connect();
  $success=false;
  if(!$this->validAppcategoryid($appcategory) && !$this->existingAppcategoryName($name))
  {
   if($appcategory=='')
   {
    if($stmt=$db->conn->prepare('insert into appcategory (id,name) values(null,?)'))
    {
     if($stmt->bind_param('s', $name))
     {
      $success=$stmt->execute();
     } // else{echo 'problem with bind';}
    } // else{echo 'problem with prepare';}
   }
   else
   {
    if($stmt=$db->conn->prepare('insert into appcategory (id,name) values(?,?)'))
    {
     if($stmt->bind_param('is', $appcategory, $name))
     {
      $success=$stmt->execute();
     } // else{echo 'problem with bind';}
    } // else{echo 'problem with prepare';}
   }
  } // else{echo 'already exists';}
  $db->close();
  return $success;
 }

  function deleteAppcategory($appcategory)
 {
  $db=new mysql; $db->connect();
  $success=false;
  if(!$this->countAppsByAppcategory($appcategory))
  {
    if($stmt=$db->conn->prepare('delete from appcategory where id=?'))
    {
     if($stmt->bind_param('i', $appcategory))
     {
      $success=$stmt->execute();
     } // else{echo 'problem with bind';}
    }  //else{echo 'problem with prepare';}
  }  //else{echo 'already exists';}
  $db->close();
  return $success;
 }

 
 
 
 function getPartCategories()
 {
  $categories=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select id,`name` from partcategory order by name'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $categories[]=array('id'=>$row['id'],'name'=>$row['name']);
   }
  }
  $db->close();
  return $categories;
 }

 function getPartCategory($id)
 {
  $category=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from partcategory where id=?'))
  {
   if($stmt->bind_param('i', $id))
   {
    $stmt->execute();
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $category=array('id'=>$row['id'],'name'=>$row['name'],'brandID'=>$row['brandID']);
    }
   }
  }
  $db->close();
  return $category;
 }

 

 function appCategoryName($appcategoryid)
 {
  $name='not found';
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('select name from appcategory where id=?'))
  {
   $stmt->bind_param('i', $appcategoryid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $name=$row['name'];
   }
  }
  $db->close();
  return $name;
 }

 function countAppsByAppcategory($appcategoryid)
 {
  $db=new mysql; $db->connect();
  $count=0;
  if($stmt=$db->conn->prepare('select count(*) as appcount from application where appcategory=?'))
  {
   if($stmt->bind_param('i', $appcategoryid))
   {
    if($result=$stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $count=intval($row['appcount']);
     }
    } // else{echo 'problem with execute';}
   } // else{echo 'problem with bind';}
  } // else{echo 'problem with prepare';}
  $db->close();
  return $count;
 }

 function countAppsByAppcategories($appcategories)
 {
  $categoryarray=array(); foreach($appcategories as $appcategory){$categoryarray[]=intval($appcategory);} $categorylist=implode(',',$categoryarray); // sanitize input
  $db=new mysql; $db->connect();
  $count=0;
  if($stmt=$db->conn->prepare('select count(*) as appcount from application where in('.$categorylist.')'))
  {
   if($result=$stmt->execute())
   {
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $count=intval($row['appcount']);
    }
   } // else{echo 'problem with execute';}
  } // else{echo 'problem with prepare';}
  $db->close();
  return $count;
 }
 

 function countPartsByPartcategory($partcategory)
 {
  $db=new mysql; $db->connect();
  $count=0;
  if($stmt=$db->conn->prepare('select count(*) as partcount from part where partcategory=?'))
  {
   if($stmt->bind_param('i', $partcategory))
   {
    if($result=$stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $count=intval($row['partcount']);
     }
    } // else{echo 'problem with execute';}
   } // else{echo 'problem with bind';}
  } // else{echo 'problem with prepare';}
  $db->close();
  return $count;
 }



 
 function partCategoryName($partcategoryid)
 {
  $name='('.$partcategoryid.') Not Found';
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('select name from partcategory where id=?'))
  {
   $stmt->bind_param('i', $partcategoryid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $name=$row['name'];
   }
  }
  $db->close();
  return $name;
 }

 function validPartcategoryid($partcategoryid)
 {
  $returnval=false;
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select name from partcategory where id=?'))
  {
   $stmt->bind_param('i', $partcategoryid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $returnval=true;
   }
  }
  $db->close();
  return $returnval;
 }

 function existingPartcategoryName($name)
 {
  $returnval=false;
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select id from partcategory where `name`=?'))
  {
   $stmt->bind_param('s', $name);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $returnval=true;
   }
  }
  $db->close();
  return $returnval;
 }

 function validAppcategoryid($appcategoryid)
 {
  $returnval=false;
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select name from appcategory where id=?'))
  {
   $stmt->bind_param('i', $appcategoryid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $returnval=true;
   }
  }
  $db->close();
  return $returnval;
 }

 function existingAppcategoryName($name)
 {
  $returnval=false;
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select id from appcategory where `name`=?'))
  {
   $stmt->bind_param('s', $name);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $returnval=true;
   }
  }
  $db->close();
  return $returnval;
 }


 
 
 
 
 function getBackgroundjobs($jobtype,$status)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $jobs=false;
  if($stmt=$db->conn->prepare('select * from backgroundjob where jobtype=? and status like ? order by datetimecreated'))
  {
   $stmt->bind_param('ss', $jobtype,$status);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    if($row['status']=='hidden'){continue;}
    $jobs[]=array('id'=>$row['id'],'jobtype'=>$row['jobtype'],'status'=>$row['status'],'userid'=>$row['userid'],'inputfile'=>$row['inputfile'],'outputfile'=>$row['outputfile'],'parameters'=>$row['parameters'],'datetimecreated'=>$row['datetimecreated'],'datetimetostart'=>$row['datetimetostart'],'datetimestarted'=>$row['datetimestarted'],'datetimeended'=>$row['datetimeended'],'percentage'=>$row['percentage']);
   }
  }
  $db->close();
  return $jobs;
 }

 function getBackgroundjob_log($jobid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $events=false;
  if($stmt=$db->conn->prepare('select * from backgroundjob_log where jobid=? order by timestamp'))
  {
   $stmt->bind_param('i', $jobid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $events[]=array('id'=>$row['id'],'jobid'=>$row['jobid'],'eventtext'=>$row['eventtext'],'timestamp'=>$row['timestamp']);
   }
  }
  $db->close();
  return $events;
 }


 function updateBackgroundjob($jobid,$status,$currenttask,$percentage,$datetimeended)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update backgroundjob set status=?,percentage=?,datetimeended=? where id=?'))
  {
   if($stmt->bind_param('sisi', $status,$percentage,$datetimeended,$jobid))
   {
    $stmt->execute();

    if($stmt=$db->conn->prepare('insert into backgroundjob_log (id,jobid,eventtext,timestamp) values(null,?,?,now())'))
    {
     if($stmt->bind_param('is',$jobid,$currenttask))
     {
      $stmt->execute();
     }
    }
   }
  }
  $db->close();
 }

 function hideBackgroundjob($jobid)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  $status='hidden'; $currenttask='hidden by user';
  if($stmt=$db->conn->prepare('update backgroundjob set status=? where id=?'))
  {
   if($stmt->bind_param('si', $status,$jobid))
   {
    $stmt->execute();

    if($stmt=$db->conn->prepare('insert into backgroundjob_log (id,jobid,eventtext,timestamp) values(null,?,?,now())'))
    {
     if($stmt->bind_param('is',$jobid,$currenttask))
     {
      $stmt->execute();
     }
    }
   }
  }
  $db->close();
 }







 function createBackgroundjob($jobtype,$status,$userid,$inputfile,$outputfile,$parameters,$datetimetostart)
 {
  $jobid=false;
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();

  if($stmt=$db->conn->prepare('insert into backgroundjob (id,jobtype,status,userid,inputfile,outputfile,parameters,datetimecreated,datetimetostart,datetimestarted,datetimeended,percentage) values(null,?,?,?,?,?,?,now(),?,0,0,0)'))
  {
   $stmt->bind_param('ssissss',$jobtype,$status,$userid,$inputfile,$outputfile,$parameters,$datetimetostart,);
   $stmt->execute();
   $jobid=$db->conn->insert_id;
  }else{print_r($db->conn->error);}

  $currenttask='job created';
  if($stmt=$db->conn->prepare('insert into backgroundjob_log (id,jobid,eventtext,timestamp) values(null,?,?,now())'))
  {
   $stmt->bind_param('is',$jobid,$currenttask);
   $stmt->execute();
  }else{print_r($db->conn->error);}
  $db->close();
  return $jobid;
 }

 function newoid()
 {
  $charset=array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
  $oid='';
  for($i=0;$i<10;$i++)
  {
   $oid.=$charset[random_int(0,61)];
  }
  return $oid;
 }


 function validPart($partnumber)
 {
  $db=new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $exists=false;
  if($stmt=$db->conn->prepare('select oid from part where partnumber=?'))
  {
   if($stmt->bind_param('s', $partnumber))
   {
    if($result=$stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $exists=true;
     }
    } // else{echo 'problem with execute';}
   } // else{echo 'problem with bind';}
  } // else{echo 'problem with prepare';}
  $db->close();
  return $exists;
 }

 function createPart($partnumber,$partcategory,$parttypeid)
 {
  $db=new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $success=false;
  if(!$this->validPart($partnumber))
  {
   $replacedby=''; $lifecyclestatus='2'; $oid=$this->newoid();
   if($stmt=$db->conn->prepare("insert into part (partnumber,partcategory,parttypeid,replacedby,lifecyclestatus,internalnotes,description,GTIN,UNSPC,createdDate,firststockedDate,discontinuedDate,oid) values(?,?,?,?,?,'','','','',now(),'0000-00-00','0000-00-00',?)"))
   {
    if($stmt->bind_param('siisss', $partnumber,$partcategory,$parttypeid,$replacedby,$lifecyclestatus,$oid))
    {
     $success=$stmt->execute();
    }// else{echo 'problem with bind';}
   }// else{echo 'problem with prepare';}
  }// else{echo 'already exists';}
  $db->close();
  return $success;
 }

 function addVCdbAttributeToApp($applicationid,$attributename,$attributevalue,$sequence,$cosmetic)
 {
  $success=true;
  $db=new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('insert into application_attribute (id,applicationid,`name`,`value`,`type`,sequence,cosmetic) values(null,?,?,?,?,?,?)'))
  {
   $attributetype='vcdb';
   $stmt->bind_param('isssii', $applicationid,$attributename,$attributevalue,$attributetype,$sequence,$cosmetic);
   $stmt->execute();
   $this->updateAppOID($applicationid);
  }
  $db->close();
  return $success;
 }

 function addNoteAttributeToApp($applicationid,$note,$sequence,$cosmetic)
 {
  $success=true;
  $db=new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('insert into application_attribute (id,applicationid,`name`,`value`,`type`,sequence,cosmetic) values(null,?,?,?,?,?,?)'))
  {
   $attributename='note'; $attributetype='note';
   $stmt->bind_param('isssii', $applicationid,$attributename,$note,$attributetype,$sequence,$cosmetic);
   $stmt->execute();
   $this->updateAppOID($applicationid);
  }
  $db->close();
  return $success;
 }

 function addQdbAttributeToApp($applicationid,$qdbid,$attributevalues,$sequence,$cosmetic)
 {
  $attributetype='qdb';
 }


 function removeAllAppAttributes($applicationid,$updateoid)
 {
  $db=new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('delete from application_attribute where applicationid=?'))
  {
   $stmt->bind_param('i', $applicationid);
   $stmt->execute();
   if($updateoid){$this->updateAppOID($applicationid);}
  }
  $db->close();
 }

 function setAppStatus($applicationid,$status)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set status=? where id=?'))
  {
   $stmt->bind_param('ii',$status,$applicationid);
   $stmt->execute();
  }
  $db->close();
 }

 function setAppCategory($applicationid,$appcategory)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set appcategory=? where id=?'))
  {
   $stmt->bind_param('ii',$appcategory,$applicationid);
   $stmt->execute();
  }
  $db->close();
 }

 function setAppPosition($applicationid,$positionid,$updateoid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set positionid=? where id=?'))
  {
   $stmt->bind_param('ii',$positionid,$applicationid);
   $stmt->execute();
   if($updateoid){$this->updateAppOID($applicationid);}
  }
  $db->close();
 }

 function setAppParttype($applicationid,$parttypeid,$updateoid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set parttypeid=? where id=?'))
  {
   $stmt->bind_param('ii',$parttypeid,$applicationid);
   $stmt->execute();
   if($updateoid){$this->updateAppOID($applicationid);}
  }
  $db->close();
 }

 function setAppQuantity($applicationid,$quantityperapp,$updateoid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set quantityperapp=? where id=?'))
  {
   $stmt->bind_param('ii',$quantityperapp,$applicationid);
   $stmt->execute();
   if($updateoid){$this->updateAppOID($applicationid);}
  }
  $db->close();
 }

 function toggleAppCosmetic($appid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set cosmetic=cosmetic XOR 1 where id=?'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
  } //else{print_r($db->conn->error);}
  $db->close();
 }

 function setAppInternalnotes($applicationid,$internalnotes)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set internalnotes=? where id=?'))
  {
   $encodednotes=base64_encode($internalnotes);
   $stmt->bind_param('si', $encodednotes,$applicationid);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }




 function conformApp($appid,$refappid,$copyfitment,$copyposition,$copyparttype,$copycategory)
 {
  // over-write app's fitment, position and parttype with that of the refapp
  // used for drag/drop in the app grid interface
  $refapp=$this->getApp($refappid);
  $app=$this->getApp($appid);
  $OID=$app['oid'];
  $neednewOID=false; $historytext='conformApp using reference app:'.$refappid;
//$app=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'internalnotes'=>'','cosmetic'=>$row['cosmetic'],'appcategory'=>$row['appcategory'],'attributes'=>array());
//$attributes[]=array('id'=>$row['id'],'name'=>$row['name'],'value'=>$row['value'],'type'=>$row['type'],'sequence'=>$row['sequence'],'cosmetic'=>$row['cosmetic']);
  if($copyfitment && $refapp['attributeshash']!=$refapp['attributeshash'])
  {
   $neednewOID=true;
   $this->removeAllAppAttributes($appid,false);
   $historytext.='; All fitment attributes removed';
   foreach($refapp['attributes'] as $attribute)
   { // set attributes for "to" app

    switch($attribute['type'])
    {
     case 'vcdb':
      $this->addVCdbAttributeToApp($appid,$attribute['name'],$attribute['value'],$attribute['sequence'],$attribute['cosmetic']);
      $historytext.='; Added VCdb '.$attribute['name'].':'.$attribute['value'].';sequence:'.$attribute['sequence'].';cosmetic:'.$attribute['cosmetic'];
      break;
     case 'note':
      $this->addNoteAttributeToApp($appid,$attribute['value'],$attribute['sequence'],$attribute['cosmetic']);
      $historytext.='; Added Note:'.$attribute['value'].';sequence:'.$attribute['sequence'].';cosmetic:'.$attribute['cosmetic'];
      break;
     case 'qdb':
//      $this->addQdbAttributeToApp($appid,...
      break;
     default: break;
    }
   }
  }

  if($copyposition && $refapp['positionid']!=$app['positionid']){$this->setAppPosition($appid,$refapp['positionid'],false); $neednewOID=true; $historytext.='; changed position from:'.$app['positionid'].' to '.$refapp['positionid'];}
  if($copyparttype && $refapp['parttypeid']!=$app['parttypeid']){$this->setAppParttype($appid,$refapp['parttypeid'],false); $neednewOID=true; $historytext.='; changed parttype from:'.$app['parttypeid'].' to '.$refapp['parttypeid'];}
  if($copycategory && $refapp['appcategory']!=$app['appcategory']){$this->setAppCategory($appid,$refapp['appcategory']); $historytext.='; changed appcategory from:'.$app['appcategory'].' to '.$refapp['appcategory'];}
  if($neednewOID){$OID=$this->updateAppOID($appid);}
  $userid=0;
  $this->logAppEvent($appid,$userid,$historytext,$OID);
 }


 function applyAppAttributes($appid,$attributes,$updateoid)
 {
  $this->removeAllAppAttributes($appid,false);
  foreach($attributes as $attribute)
  {
   switch($attribute['type'])
   {
    case 'vcdb':
     $this->addVCdbAttributeToApp($appid,$attribute['name'],$attribute['value'],$attribute['sequence'],$attribute['cosmetic']);
     break;
    case 'note':
     $this->addNoteAttributeToApp($appid,$attribute['value'],$attribute['sequence'],$attribute['cosmetic']);
     break;
    case 'qdb':
//      $this->addQdbAttributeToApp($appid,...
     break;
    default: break;
   }
  }
  if($updateoid){$this->updateAppOID($appid);}
 }

 function newApp($basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber,$appcategory,$cosmetic,$attributes)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $applicationid=false;
  if($stmt=$db->conn->prepare('insert into application (id,oid,basevehicleid,makeid,equipmentid,parttypeid,positionid,quantityperapp,partnumber,status,cosmetic,appcategory) values(null,?,?,0,0,?,?,?,?,0,?,?)'))
  {
   $oid=$this->newoid();
   $stmt->bind_param('siiiisii', $oid,$basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber,$cosmetic,$appcategory);
   $stmt->execute();
   $applicationid=$db->conn->insert_id;

   if(count($attributes))
   {
    $this->applyAppAttributes($applicationid,$attributes,false);
   }
  }
  $db->close();
  return $applicationid;
 }

 function logAppEvent($applicationid,$userid,$description,$newoid)
 {
  $db=new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('insert into application_history (id,applicationid,eventdatetime,userid,description,new_oid) values(null,?,now(),?,?,?)'))
  {
   $stmt->bind_param('iiss', $applicationid,$userid,$description,$newoid);
   $stmt->execute();
  } // else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }

 function getAppEvents($applicationid,$limit)
 {
  $db=new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  $events=array();
  if($stmt=$db->conn->prepare('select * from application_history where applicationid=? order by eventdatetime desc limit ?'))
  {
   $stmt->bind_param('ii', $applicationid,$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $events[]=array('id'=>$row['id'],'applicationid'=>$row['applicationid'],'eventdatetime'=>$row['eventdatetime'],'userid'=>$row['userid'],'description'=>$row['description'],'new_oid'=>$row['new_oid']);
   }
  }
  $db->close();
  return $events;
 }




 
 function logPartEvent($partnumber,$userid,$description,$newoid)
 {
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into part_history (id,partnumber,eventdatetime,userid,description,new_oid) values(null,?,now(),?,?,?)'))
  {
   $stmt->bind_param('siss', $partnumber,$userid,$description,$newoid);
   $stmt->execute();
  } // else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }

 
 

 function createAppFromACESsnippet($xml,$appcategory)
 {
  $db=new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $app_count=0;

  foreach($xml->App as $app)
  {
   if($stmt=$db->conn->prepare('insert into application (id,oid,basevehicleid,makeid,equipmentid,parttypeid,positionid,quantityperapp,partnumber,status,cosmetic,appcategory) values(null,?,?,0,0,?,?,?,?,0,0,?)'))
   {
    $oid=$this->newoid();
    $stmt->bind_param('siiiisi', $oid,$basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber,$appcategory);
    $cosmetic=0; $sequence=0; $basevehicleid=intval($app->BaseVehicle['id']); $quantityperapp=intval($app->Qty); $parttypeid=intval($app->PartType['id']); $positionid=intval($app->Position['id']); $partnumber=(string)$app->Part;
    $stmt->execute(); // insert the application record
    $applicationid=$db->conn->insert_id;

    // insert attribute records
    $attributes=array();
    if($id=$app->SubModel['id']){$attributes[]=array('type'=>'vcdb','name'=>'SubModel', 'value'=>intval($id));}
    if($id=$app->MfrBodyCode['id']){$attributes[]=array('type'=>'vcdb','name'=>'MfrBodyCode', 'value'=>intval($id));}
    if($id=$app->BodyNumDoors['id']){$attributes[]=array('type'=>'vcdb','name'=>'BodyNumDoors','value'=>intval($id));}
    if($id=$app->BodyType['id']){$attributes[]=array('type'=>'vcdb','name'=>'BodyType','value'=>intval($id));}
    if($id=$app->DriveType['id']){$attributes[]=array('type'=>'vcdb','name'=>'DriveType','value'=>intval($id));}
    if($id=$app->EngineBase['id']){$attributes[]=array('type'=>'vcdb','name'=>'EngineBase','value'=>intval($id));}
    if($id=$app->EngineDesignation['id']){$attributes[]=array('type'=>'vcdb','name'=>'EngineDesignation','value'=>intval($id));}
    if($id=$app->EngineVIN['id']){$attributes[]=array('type'=>'vcdb','name'=>'EngineVIN','value'=>intval($id));}
    if($id=$app->EngineVersion['id']){$attributes[]=array('type'=>'vcdb','name'=>'EngineVersion','value'=>intval($id));}
    if($id=$app->EngineMfr['id']){$attributes[]=array('type'=>'vcdb','name'=>'EngineMfr','value'=>intval($id));}
    if($id=$app->PowerOutput['id']){$attributes[]=array('type'=>'vcdb','name'=>'PowerOutput','value'=>intval($id));}
    if($id=$app->ValvesPerEngine['id']){$attributes[]=array('type'=>'vcdb','name'=>'ValvesPerEngine','value'=>intval($id));}
    if($id=$app->FuelDeliveryType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FuelDeliveryType','value'=>intval($id));}
    if($id=$app->FuelDeliverySubType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FuelDeliverySubType','value'=>intval($id));}
    if($id=$app->FuelSystemControlType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FuelSystemControlType','value'=>intval($id));}
    if($id=$app->FuelSystemDesign['id']){$attributes[]=array('type'=>'vcdb','name'=>'FuelSystemDesign','value'=>intval($id));}
    if($id=$app->Aspiration['id']){$attributes[]=array('type'=>'vcdb','name'=>'Aspiration','value'=>intval($id));}
    if($id=$app->CylinderHeadType['id']){$attributes[]=array('type'=>'vcdb','name'=>'CylinderHeadType','value'=>intval($id));}
    if($id=$app->FuelType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FuelType','value'=>intval($id));}
    if($id=$app->IgnitionSystemType['id']){$attributes[]=array('type'=>'vcdb','name'=>'IgnitionSystemType','value'=>intval($id));}
    if($id=$app->TransmissionMfrCode['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionMfrCode','value'=>intval($id));}
    if($id=$app->TransmissionBase['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionBase','value'=>intval($id));}
    if($id=$app->TransmissionType['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionType','value'=>intval($id));}
    if($id=$app->TransmissionControlType['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionControlType','value'=>intval($id));}
    if($id=$app->TransmissionNumSpeeds['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionNumSpeeds','value'=>intval($id));}
    if($id=$app->TransElecControlled['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransElecControlled','value'=>intval($id));}
    if($id=$app->TransmissionMfr['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionMfr','value'=>intval($id));}
    if($id=$app->BedLength['id']){$attributes[]=array('type'=>'vcdb','name'=>'BedLength','value'=>intval($id));}
    if($id=$app->BedType['id']){$attributes[]=array('type'=>'vcdb','name'=>'BedType','value'=>intval($id));}
    if($id=$app->WheelBase['id']){$attributes[]=array('type'=>'vcdb','name'=>'WheelBase','value'=>intval($id));}
    if($id=$app->BrakeSystem['id']){$attributes[]=array('type'=>'vcdb','name'=>'BrakeSystem','value'=>intval($id));}
    if($id=$app->FrontBrakeType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FrontBrakeType','value'=>intval($id));}
    if($id=$app->RearBrakeType['id']){$attributes[]=array('type'=>'vcdb','name'=>'RearBrakeType','value'=>intval($id));}
    if($id=$app->BrakeABS['id']){$attributes[]=array('type'=>'vcdb','name'=>'BrakeABS','value'=>intval($id));}
    if($id=$app->FrontSpringType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FrontSpringType','value'=>intval($id));}
    if($id=$app->RearSpringType['id']){$attributes[]=array('type'=>'vcdb','name'=>'RearSpringType','value'=>intval($id));}
    if($id=$app->SteeringSystem['id']){$attributes[]=array('type'=>'vcdb','name'=>'SteeringSystem','value'=>intval($id));}
    if($id=$app->SteeringType['id']){$attributes[]=array('type'=>'vcdb','name'=>'SteeringType','value'=>intval($id));}
    if($id=$app->Region['id']){$attributes[]=array('type'=>'vcdb','name'=>'Region','value'=>intval($id));}
    if($id=$app->VehicleType['id']){$attributes[]=array('type'=>'vcdb','name'=>'VehicleType','value'=>intval($id));}

    foreach($app->Note as $note){$attributes[]=array('type'=>'note','name'=>'note','value'=>(string)$note);}

    foreach($app->Qual as $qual)
    {
     $params=array();
     foreach($qual->param as $param){$params[]=(string)$param['value'].':'.(string)$param['uom'];}
     $attributes[]=array('type'=>'qdb','name'=>(string)$qual['id'],'value'=>implode(';',$params));
    }

    if($stmt=$db->conn->prepare('insert into application_attribute (id,applicationid,`name`,`value`,`type`,sequence,cosmetic) values(null,?,?,?,?,?,?)'))
    {
     $stmt->bind_param('isssii', $applicationid,$attribute_name,$attribute_value,$attribute_type,$sequence,$cosmetic);
     foreach($attributes as $attribute)
     {
      $sequence++; $attribute_name=$attribute['name']; $attribute_value=$attribute['value']; $attribute_type=$attribute['type'];
      $stmt->execute(); // insert the application record
     }
    }
   }
   $app_count++;
   $this->createPart($partnumber,0,$parttypeid);
  }
  $db->close();
  return $app_count;
 }

 
 //----
 
 function createAppsFromText($data)
 {
  $db=new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $app_count=0;

  // validate that the txt has the proper number of tab-delimited columns
  $rows= explode("\r\n", $data);
  foreach($rows as $row)
  {
   $fields=explode("\t",$row);
   if(count($fields)==10)
   {// row has the correct number of fields
    if($stmt=$db->conn->prepare('insert into application (id,oid,basevehicleid,makeid,equipmentid,parttypeid,positionid,quantityperapp,partnumber,status,cosmetic,appcategory) values(null,?,?,0,0,?,?,?,?,0,0,?)'))
    {
     $oid=$this->newoid();
     $stmt->bind_param('siiiisi', $oid,$basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber,$appcategory);
     $appcategory=intval($fields[0]);
     $cosmetic=intval($fields[1]); 
     $basevehicleid=intval($fields[2]); 
     $partnumber=trim(strtoupper($fields[3]));
     $parttypeid=intval($fields[4]); 
     $positionid=intval($fields[5]); 
     $quantityperapp=intval($fields[6]); 

     $stmt->execute(); // insert the application record
     $applicationid=$db->conn->insert_id;
     
     //insert attribute records
     //vcdbattributes (name|value|sequence|cosmetic)
     // example: "FrontBrakeType|5|1|0;SubModel|20|3|1;"

     $attributes=array();
     
     if(strlen($fields[7]))
     {// VCdb attributes are present. parse them.
      $attributestrings=explode(';',$fields[7]);
      foreach($attributestrings as $attributestring)
      {
       $attributechunks=explode('|',$attributestring);
       if(count($attributechunks)==4)
       {// FrontBrakeType|5|1|0   (Disc, sequence 1, non-cosmetic)
        $attributes[]=array('type'=>'vcdb','name'=>$attributechunks[0], 'value'=>intval($attributechunks[1]),'cosmetic'=>intval($attributechunks[3]),'sequence'=>intval($attributechunks[2]));
       }
      }
     }

     if(strlen($fields[9]))
     {// notes are present.
      $notechunks=explode('|',$fields[9]);
      if(count($notechunks)==3)
      {// Some more Notes|2|1  (notetext, sequence 2, cosmetic)
        $attributes[]=array('type'=>'note','name'=>'note','value'=>$notechunks[0],'cosmetic'=>intval($notechunks[2]),'sequence'=>intval($notechunks[1]));
      }
     }     

     if(strlen($fields[8]))
     {// Qdb is present.
      $params=array();
      //foreach($qual->param as $param){$params[]=(string)$param['value'].':'.(string)$param['uom'];}
      //$attributes[]=array('type'=>'qdb','name'=>(string)$qual['id'],'value'=>implode(';',$params));
     }
     

     if($stmt=$db->conn->prepare('insert into application_attribute (id,applicationid,`name`,`value`,`type`,sequence,cosmetic) values(null,?,?,?,?,?,?)'))
     {
      $sequence=0;
      $stmt->bind_param('isssii', $applicationid,$attribute_name,$attribute_value,$attribute_type,$sequence,$cosmetic);
      foreach($attributes as $attribute)
      {
       $sequence=$attribute['sequence']; $attribute_name=$attribute['name']; $attribute_value=$attribute['value']; $attribute_type=$attribute['type']; $cosmetic=$attribute['cosmetic'];
       $stmt->execute(); // insert the application record
      }
     }
     
     $app_count++;
     $this->createPart($partnumber,0,$parttypeid);
    }
   }
  }
  
  $db->close();
  return $app_count;
 }

 //----
 
 
 function addFavoriteParttype($parttypeid,$myname)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $parttypes=array();
  if($stmt=$db->conn->prepare('insert into parttype values(?,?)'))
  {
   $stmt->bind_param('is', $parttypeid,$myname); 
   $stmt->execute();
  }
  $db->close();
 }

 function removeFavoriteParttype($parttypeid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $parttypes=array();
  if($stmt=$db->conn->prepare('delete from parttype where id=?'))
  {
   $stmt->bind_param('i', $parttypeid); 
   $stmt->execute();
  }
  $db->close();
 }
 


 function getReceiverprofiles()
 {
  $profiles=false; $status=0;
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('select * from receiverprofile where status=?'))
  {
   $stmt->bind_param('i',$status);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $profiles[]=array('id'=>$row['id'],'name'=>$row['name'],'data'=>$row['data'],'status'=>$row['status'],'intervaldays'=>$row['intervaldays'],'lastexport'=>$row['lastexport'],'notes'=>$row['notes']);
   }
  }
  $db->close();
  return $profiles;
 }

 function getReceiverprofileById($id)
 {
  $profile=false; $status=0;
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('select * from receiverprofile where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $profile=array('id'=>$row['id'],'name'=>$row['name'],'data'=>$row['data'],'status'=>$row['status'],'intervaldays'=>$row['intervaldays'],'lastexport'=>$row['lastexport'],'notes'=>$row['notes']);
   }
  }
  $db->close();
  return $profile;
 }

 function createReceiverprofile($name, $data)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare("insert into receiverprofile values(null,0,?,?,30,'0000-00-00','')"))
  {
   $stmt->bind_param('ss',$name,$data);
   $stmt->execute();
  }
  $db->close();
 }
 
 function updateReceiverprofile($id, $name, $data)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update receiverprofile set `name`=?, `data`=? where id=?'))
  {
   $stmt->bind_param('ssi',$name,$data,$id);
   $stmt->execute();
  }
  $db->close();
 }

 function deleteReceiverprofile($id)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update receiverprofile set status=1 where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
  }
  $db->close();
 }


 
 
 function getMarketingcopyByReceiverprofileId($receiverprofileid)
 {
  $marketingcopy=false;
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('select * from receiverprofile_marketingcopy where receiverprofileid=?'))
  {
   $stmt->bind_param('i',$receiverprofileid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $marketingcopy[]=array('id'=>$row['id'],'receiverprofileid'=>$row['receiverprofileid'],'marketcopycontent'=>$row['marketcopycontent'],'marketcopycode'=>$row['marketcopycode'],'marketcopyreference'=>$row['marketcopyreference'],'marketcopytype'=>$row['marketcopytype'],'recordsequence'=>$row['recordsequence'],'languagecode'=>$row['languagecode']);
   }
  }
  $db->close();
  return $marketingcopy;
 }


 function isValidBarcode($barcode) 
 {
  //checks validity of: GTIN-8, GTIN-12, GTIN-13, GTIN-14, GSIN, SSCC
  //see: http://www.gs1.org/how-calculate-check-digit-manually
  $barcode = (string) $barcode;
  //we accept only digits
  if (!preg_match("/^[0-9]+$/", $barcode)) {return false;}
  //check valid lengths:
  $l = strlen($barcode);
  if(!in_array($l, [8,12,13,14,17,18])){ return false;}
  //get check digit
  $check = substr($barcode, -1);
  $barcode = substr($barcode, 0, -1);
  $sum_even = $sum_odd = 0;
  $even = true;
  while(strlen($barcode)>0) 
  {
   $digit = substr($barcode, -1);
   if($even)
   {
    $sum_even += 3 * $digit;
   }
   else 
   {
    $sum_odd += $digit;
   }
   $even = !$even;
   $barcode = substr($barcode, 0, -1);
  }
  $sum = $sum_even + $sum_odd;
  $sum_rounded_up = ceil($sum/10) * 10;
  return ($check == ($sum_rounded_up - $sum));
 }

 function getAutocareDatabaseList($type)
 {
  $db=new mysql; $db->connect();
  $events=array();
  if($stmt=$db->conn->prepare('select * from autocare_databases where databasetype like ? order by versiondate desc'))
  {
   $stmt->bind_param('s',$type);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $databases[]=array('name'=>$row['databasename'],'type'=>$row['databasetype'],'versiondate'=>$row['versiondate']);
   }
  }
  $db->close();
  return $databases;
 }
 
 

 

}?>
