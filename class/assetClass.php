<?php
include_once("mysqlClass.php");

class asset
{
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

    
 function validAsset($assetid)
 {
  $db=new mysql; $db->connect(); $returnval=array();
  if($stmt=$db->conn->prepare('select * from asset where assetid=?'))
  {
   $stmt->bind_param('s',$assetid);
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
    
 function addAsset($assetid,$filename,$localpath,$uri,$orientationViewCode,$colorModeCode,$assetHeight,$assetWidth,$dimensionUOM,$resolution,$background,$fileType,$public,$approved,$description,$oid,$fileHashMD5,$filesize,$uripublic,$languagecode,$assetlabel,$createddate,$frame,$totalframes,$plane,$totalplanes)
 {
  $db=new mysql; $db->connect(); $id=false;

  $created=date('Y-m-d');
  if($createddate){$created=$createddate;}
  
  if($stmt=$db->conn->prepare('insert into asset(id,assetid,filename,localpath,uri,orientationViewCode,colorModeCode,assetHeight,assetWidth,dimensionUOM,resolution,background,fileType,createdDate,public,approved,description,oid,fileHashMD5,filesize,uripublic,languagecode,assetLabel,changedDate,frame,totalframes,plane,totalplanes) values(null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,now(),?,?,?,?)'))
  {
   if($stmt->bind_param('ssssssiisisssiisssiissiiii',$assetid,$filename,$localpath,$uri,$orientationViewCode,$colorModeCode,$assetHeight,$assetWidth,$dimensionUOM,$resolution,$background,$fileType,$created,$public,$approved,$description,$oid,$fileHashMD5,$filesize,$uripublic,$languagecode,$assetlabel,$frame,$totalframes,$plane,$totalplanes))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    }else{echo 'problem with execute: '.$db->conn->error;}
   }else{echo 'problem with bind';}
  }else{echo 'problem with prepare';}
  $db->close();
  return $id;
 }

 function getAssetRecordsByAssetid($assetid)
 {
  $db=new mysql; $db->connect(); $records=array();
  if($stmt=$db->conn->prepare('select * from asset where assetid=?'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode'],'assetlabel'=>$row['assetlabel'],'changedDate'=>$row['changedDate'],'frame'=>$row['frame'],'totalFrames'=>$row['totalFrames'],'plane'=>$row['plane'],'totalPlanes'=>$row['totalPlanes']);
   }
  }
  $db->close();
  return $records;   
 }
 
 function getAssetById($id)
 {
  $db=new mysql; $db->connect(); $asset=false;
  if($stmt=$db->conn->prepare('select * from asset where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $asset=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode'],'assetlabel'=>$row['assetlabel'],'changedDate'=>'2000-01-01');
   }
  }
  $db->close();
  return $asset;   
 }
/*
 function getAssetRecordsByAssettagid($assettagid)
 {
  $db=new mysql; $db->connect(); $records=array();
  if($stmt=$db->conn->prepare('select * from asset,asset_assettag where asset.assetid=asset_assettag.assetid and asset.assetid=?'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode'],'assetlabel'=>$row['assetlabel'],'changedDate'=>$row['changedDate'],'frame'=>$row['frame'],'totalFrames'=>$row['totalFrames'],'plane'=>$row['plane'],'totalPlanes'=>$row['totalPlanes']);
   }
  }
  $db->close();
  return $records;   
 }
*/
 
 function connectPartToAsset($part,$assetid,$assettypecode,$sequence,$representation)
 {
  $db=new mysql; $db->connect(); $id=false;
  if($stmt=$db->conn->prepare('insert into part_asset (id,partnumber,assetid,assettypecode,sequence,representation) values(null,?,?,?,?,?)'))
  {   
   $stmt->bind_param('sssis',$part,$assetid,$assettypecode,$sequence,$representation);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }
  $db->close();
  return $id;   
 }
 
 function disconnectPartFromAsset($partnumber,$connectionid=false)
 {
  $db = new mysql; $db->connect();
  $sql='delete from part_asset where partnumber=?'; // start by assuming no connection id was passed, and only select for partnumber the partnbmer (not connectionid)
  if($connectionid)
  {
   $sql='delete from part_asset where partnumber=? and id=?';
  }   
  
  if($stmt=$db->conn->prepare($sql))
  {
   if($connectionid)
   {
    $stmt->bind_param('si',$partnumber,$connectionid);
   }
   else
   {// no connection id was passed - we are binding only the partnbmer 
    $stmt->bind_param('s',$partnumber);       
   }
   
   if($connectionid)
   {
    $stmt->bind_param('si',$partnumber,$connectionid);
   }
   $stmt->execute();
  }
  $db->close();
 }
 
 function getPartsConnectedToAsset($assetid)
 {
  $db=new mysql; $db->connect(); $connections=array();
  if($stmt=$db->conn->prepare('select * from part_asset where assetid=? order by partnumber'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $connections[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'partnumber'=>$row['partnumber'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence'],'representation'=>$row['representation']);
   }
  }
  $db->close();
  return $connections;   
 }
 
 function getAssetsConnectedToPart($partnumber,$excludenonpublic=false)
 {
     // deal with inheritance
     // if given part has a basepart, get those assets too. The resulting list of assets will be the combination from both parts
    
  $db=new mysql; $db->connect(); $connections=array();
  $publicclause=''; if($excludenonpublic){$publicclause=' and public=1';}
  if($stmt=$db->conn->prepare('select part_asset.id as connectionid, partnumber,assettypecode,sequence,representation, asset.* from part_asset,asset where part_asset.assetid=asset.assetid and partnumber=? '.$publicclause.' order by representation, id'))
  {
   if($stmt->bind_param('s',$partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
       $connections[]=array('id'=>$row['id'],'connectionid'=>$row['connectionid'],'assetid'=>$row['assetid'],'partnumber'=>$row['partnumber'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence'],'representation'=>$row['representation'],'uri'=>$row['uri'],'filename'=>$row['filename'],'filetype'=>$row['fileType'],'assetlabel'=>$row['assetlabel'],'inheritedfrom'=>'','frame'=>$row['frame'],'totalFrames'=>$row['totalFrames'],'plane'=>$row['plane'],'totalPlanes'=>$row['totalPlanes']);
     }
    }
   }
  }
  
  //ccc
  
  $basepart='';
  $stmt=$db->conn->prepare('select basepart from part where partnumber=?');
  $stmt->bind_param('s', $partnumber);
  $stmt->execute();
  $db->result = $stmt->get_result();
  if($row = $db->result->fetch_assoc()){$basepart=$row['basepart'];}
  
  if($basepart!='')
  {
   if($stmt=$db->conn->prepare('select part_asset.id as connectionid, partnumber,assettypecode,sequence,representation, asset.* from part_asset,asset where part_asset.assetid=asset.assetid and partnumber=? and public=1 order by sequence'))
   {
    if($stmt->bind_param('s',$basepart))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      while($row = $db->result->fetch_assoc())
      {
       $connections[]=array('id'=>$row['id'],'connectionid'=>$row['connectionid'],'assetid'=>$row['assetid'],'partnumber'=>$row['partnumber'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence'],'representation'=>$row['representation'],'uri'=>$row['uri'],'filename'=>$row['filename'],'filetype'=>$row['fileType'],'inheritedfrom'=>$basepart);
      }
     }
    }
   }
  }
    
  
  $db->close();
  return $connections;   
 }

 function getAssetByPartConnectionid($connectionid)
 {
  $db=new mysql; $db->connect(); $asset=false;
  if($stmt=$db->conn->prepare('select * from part_asset,asset where part_asset.assetid=asset.assetid and part_asset.id=? order by sequence'))
  {
   if($stmt->bind_param('i',$connectionid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
       $asset=array('id'=>$row['id'],'assetid'=>$row['assetid'],'partnumber'=>$row['partnumber'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence'],'representation'=>$row['representation'],'uri'=>$row['uri']);
     }
    }
   }
  }
  $db->close();
  return $asset;   
 }
//ccc
 function getAssetsConnectedToBrand($BrandID,$excludenonpublic=false)
 {
  $db=new mysql; $db->connect(); $connections=array();
  $publicclause=''; if($excludenonpublic){$publicclause=' and public=1';}
  //if($stmt=$db->conn->prepare('select brand_asset.id as connectionid, BrandID,assettypecode,sequence, asset.* from brand_asset,asset where brand_asset.assetid=asset.assetid and BrandID=? '.$publicclause.' order by sequence'))
  if($stmt=$db->conn->prepare('select brand_asset.id as connectionid, BrandID,assettypecode,sequence, asset.* from brand_asset,asset where brand_asset.assetid=asset.assetid and BrandID=? '.$publicclause.' order by sequence'))
  {
   if($stmt->bind_param('s',$BrandID))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
       $connections[]=array('id'=>$row['id'],'connectionid'=>$row['connectionid'],'assetid'=>$row['assetid'],'BrandID'=>$row['BrandID'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence'],'uri'=>$row['uri'],'filename'=>$row['filename'],'filetype'=>$row['fileType'],'public'=>$row['public'],'filesize'=>$row['filesize'],'description'=>$row['description']);
     }
    }
   }
  }
  $db->close();
  return $connections;   
 }

 function getBrandsConnectedToAsset($assetid,$excludenonpublic=false)
 {
  $db=new mysql; $db->connect(); $connections=array();
  $publicclause=''; if($excludenonpublic){$publicclause=' and public=1';}
  if($stmt=$db->conn->prepare('select brand_asset.id as connectionid, BrandID,assettypecode,sequence, asset.* from brand_asset,asset where brand_asset.assetid=asset.assetid and asset.assetid=? '.$publicclause.' order by sequence'))
  {
   if($stmt->bind_param('s',$assetid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
       $connections[]=array('id'=>$row['id'],'connectionid'=>$row['connectionid'],'assetid'=>$row['assetid'],'BrandID'=>$row['BrandID'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence'],'uri'=>$row['uri'],'filename'=>$row['filename'],'filetype'=>$row['fileType']);
     }
    }
   }
  }
  $db->close();
  return $connections;   
 }

 
 function connectBrandToAsset($brandid,$assetid,$assettypecode,$sequence)
 {
  $db=new mysql; $db->connect(); $id=false;
  if($stmt=$db->conn->prepare('insert into brand_asset (id,BrandID,assetid,assettypecode,sequence) values(null,?,?,?,?)'))
  {   
   $stmt->bind_param('sssi',$brandid,$assetid,$assettypecode,$sequence);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }
  $db->close();
  return $id;   
 }

 function disconnectBrandFromAsset($brandid,$connectionid=false)
 {
  $db = new mysql; $db->connect();
  $sql='delete from brand_asset where BrandID=?'; // start by assuming no connection id was passed, and only select for partnumber the partnbmer (not connectionid)
  if($connectionid)
  {
   $sql='delete from brand_asset where BrandID=? and id=?';
  }   
  
  if($stmt=$db->conn->prepare($sql))
  {
   if($connectionid)
   {
    $stmt->bind_param('si',$brandid,$connectionid);
   }
   else
   {// no connection id was passed - we are binding only the partnbmer 
    $stmt->bind_param('s',$brandid);       
   }
   
   if($connectionid)
   {
    $stmt->bind_param('si',$brandid,$connectionid);
   }
   $stmt->execute();
  }
  $db->close();
 }



 
 function getUnconnecteddAssets()
 {
  $db=new mysql; $db->connect(); $assets=array();
  if($stmt=$db->conn->prepare('select asset.id,asset.assetid,uri,filetype from asset left join part_asset on asset.assetid = part_asset.assetid where part_asset.assetid is null'))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
      $assets[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'uri'=>$row['uri'],'filetype'=>$row['filetype']);
    }
   }
  }
  $db->close();
  return $assets;
 }
 
 function getOrphanPartAssetRecords()
 {
  $db=new mysql; $db->connect(); $connections=array();
  if($stmt=$db->conn->prepare('select part_asset.* from part_asset left join asset on part_asset.assetid = asset.assetid where asset.id is null;'))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
      $connections[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'assetid'=>$row['assetid'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence'],'representation'=>$row['representation']);
    }
   }
  }
  $db->close();
  return $connections;
 }
 
 
 
 function deleteAssetRecord($id)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from asset where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
  }
  $db->close();
  
  // need to delete (unlink) local file if it exists
  
 }
 

 function deleteAssetRecordByOID($oid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from asset where oid=?'))
  {
   $stmt->bind_param('s',$oid);
   $stmt->execute();
  }
  $db->close();
 }
 
 function deleteAssetsByAssetid($assetid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from asset where assetid=?'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
  }

  // delete any part-asset connection that exist for this asset
  if($stmt=$db->conn->prepare('delete from part_asset where assetid=?'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
  }

  $db->close();
// need to delete (unlink) local file if it exists  
 }
  
 function primaryPhotoURIofPart($partnumber)
 {
  $returnval=false;
  $assets=$this->getAssetsConnectedToPart($partnumber, false);
  foreach($assets as $asset)
  {
   if($asset['assettypecode']=='P04' && $asset['uri']!='')
   {
    $returnval=$asset['uri'];
    break;
   }
  }
  return $returnval;
 }
 
 function setAssetDescription($assetid,$description)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set description=?, changedDate=now() where assetid=?'))
  {
   $encodednotes=base64_encode($description);
   $stmt->bind_param('ss', $encodednotes,$assetid);
   $stmt->execute();
  }
  $db->close();
 }

 function setAssetLabel($id,$label)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set assetlabel=?, changedDate=now() where id=?'))
  {
   $stmt->bind_param('si', $label, $id);
   $stmt->execute();
  }
  $db->close();
 }
 
 function setAssetOrientationviewcode($id,$orientationviewcode)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set orientationviewcode=?, changedDate=now() where id=?'))
  {
   $stmt->bind_param('si', $orientationviewcode, $id);
   $stmt->execute();
  }
  $db->close();
 }



 function setAssetHash($id,$hash)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set fileHashMD5=?, changedDate=now() where id=?'))
  {
   $stmt->bind_param('si', $hash, $id);
   $stmt->execute();
  }
  $db->close();
 }
 
 function setAssetFilesize($id,$size)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set filesize=?, changedDate=now() where id=?'))
  {
   $stmt->bind_param('ii', $size, $id);
   $stmt->execute();
  }
  $db->close();
 }
 
 
 function updateAssetOIDbyRecordID($id)
 {
  $db = new mysql; $db->connect(); $oid=false;
  if($stmt=$db->conn->prepare('update asset set oid=?, changedDate=now() where id=?'))
  {
   $oid=$this->newoid();
   $stmt->bind_param('si', $oid,$id);
   $stmt->execute();
  }
  $db->close();
  return $oid;
 }

 
 function updateAssetOID($assetid)
 {
  $db = new mysql; $db->connect(); $oid=false;
  if($stmt=$db->conn->prepare('update asset set oid=?, changedDate=now() where id=?'))
  {
   $oid=$this->newoid();
   $stmt->bind_param('ss', $oid,$assetid);
   $stmt->execute();
  }
  $db->close();
  return $oid;
 }

 function newoid()
 {
  $oid= $this->uuidv4();
/*
  $charset=array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
  $oid='';
  for($i=0;$i<10;$i++)
  {
   $oid.=$charset[random_int(0,61)];
  }
 */
  return $oid;
 }

 
 function toggleAssetPublic($id)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set public=public XOR 1, changedDate=now() where id=?'))
  {
   $stmt->bind_param('i', $id);
   $stmt->execute();
  }
  $db->close();
 }

 function setAssetPublic($id,$piblic)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set public=?, changedDate=now() where id=?'))
  {
   $stmt->bind_param('ii', $piblic, $id);
   $stmt->execute();
  }
  $db->close();
 }

 function setAssetUri($id,$uri)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set uri=?, changedDate=now() where id=?'))
  {
   $stmt->bind_param('si', $uri, $id);
   $stmt->execute();
  }
  $db->close();
 }
 
 function toggleAssetUriPublic($assetid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set uripublic=uripublic XOR 1, changedDate=now() where assetid=?'))
  {
   $stmt->bind_param('s', $assetid);
   $stmt->execute();
  }
  $db->close();
 }

 function setAssetUriPublic($id,$public)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set uripublic=?, changedDate=now() where id=?'))
  {
   $stmt->bind_param('ii', $public, $id);
   $stmt->execute();
  }
  $db->close();
 }

 function getRecentAssets($limit)
 {
  $assets=array(); $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from asset order by id desc limit ?'))
  {
   $stmt->bind_param('i',$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $assets[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode'],'assetlabel'=>$row['assetlabel'],'frame'=>$row['frame'],'totalFrames'=>$row['totalFrames'],'plane'=>$row['plane'],'totalPlanes'=>$row['totalPlanes']);
   }
  }
  $db->close();
  return $assets;   
 }

 function getAssettags()
 {
  $assettags=array(); $db=new mysql; $db->connect();  
  if($stmt=$db->conn->prepare('select * from assettag order by tagtext'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $assettags[]=array('id'=>$row['id'],'tagtext'=>$row['tagtext']);
   }
  }
  $db->close();
  return $assettags;
 }

 function assettagText($id)
 {
  $tagtext='unknown tag (id '.$id.')'; $db=new mysql; $db->connect();  
  if($stmt=$db->conn->prepare('select tagtext from assettag where id=?'))
  {
   $stmt->bind_param('i',$id);      
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $tagtext=$row['tagtext'];
   }
  }
  $db->close();
  return $tagtext;
 }
 
 function assetTagid($tagtext)
 {
  $returnval=false;
  $db=new mysql; $db->connect();  
  if($stmt=$db->conn->prepare('select id from assettag where tagtext=?'))
  {
   $stmt->bind_param('s',$tagtext);      
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $returnval=$row['id'];
   }
  }
  $db->close();
  return $returnval;
 }

 
 function getAssettagsForAsset($assetid)
 { // return an array of tags hooked this the given asset
  $tags=array(); $db=new mysql; $db->connect();  
  if($stmt=$db->conn->prepare('select asset_assettag.id as id, assettag.id as assettagid, assettag.tagtext as tagtext from asset_assettag,assettag where asset_assettag.assettagid=assettag.id and asset_assettag.assetid=?'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $tags[]=array('id'=>$row['id'],'assettagid'=>$row['assettagid'],'tagtext'=>$row['tagtext']);
   }
  }
  $db->close();
  return $tags;   
 }
 
 function addAssetTagidToAsset($assetid,$tagid)
 {
  $returnval=false;
  $db=new mysql; $db->connect();

  if($stmt=$db->conn->prepare('select id from asset_assettag where assetid=? and assettagid=?'))
  {
   if($stmt->bind_param('si',$assetid,$tagid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $returnval=$row['id'];
     }   
    }
   }      
  }
  
  if(!$returnval)
  {
   if($stmt=$db->conn->prepare('insert into asset_assettag values(null,?,?)'))
   {
    if($stmt->bind_param('si',$assetid,$tagid))
    {
     if($stmt->execute())
     {
      $returnval=$id=$db->conn->insert_id;
     }
    }
   }
  }
  $db->close();
  return $returnval;     
 }
 
 function removeTagsFromAsset($assetid,$tagid)
 {
  $db=new mysql; $db->connect();
  if($tagid)
  {
   $stmt=$db->conn->prepare('delete from asset_assettag where assetid=? and assettagid=?');
   $stmt->bind_param('si',$assetid,$tagid);   
   $stmt->execute();
  }
  else
  {// no tag specified         
   $stmt=$db->conn->prepare('delete from asset_assettag where assetid=?');
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
  }
  
  $db->close();
 }
 
 
 function assetHasTag($assetid,$tagtext)
 {
  $db=new mysql; $db->connect();  
  $returnval=false;
  if($stmt=$db->conn->prepare('select asset_assettag.id as id from asset_assettag, assettag where asset_assettag.assettagid=assettag.id and asset_assettag.assetid=? and assettag.tagtext=?'))
  {
   $stmt->bind_param('ss',$assetid,$tagtext);
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
 
 function getAssetsByRandom($limit)
 {
  $assets=array(); $db=new mysql; 
  $db->connect();
  if($stmt=$db->conn->prepare('select * from asset order by rand() limit ?'))
  {
   $stmt->bind_param('i',$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $assets[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode'],'assetlabel'=>$row['assetlabel'],'frame'=>$row['frame'],'totalFrames'=>$row['totalFrames'],'plane'=>$row['plane'],'totalPlanes'=>$row['totalPlanes']);
   }
  }
  $db->close();
  return $assets;   
 }

 
 
 function sqlclean($string)
 {
   //$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
   $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
   return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
}
 
 function getAssets($assetid,$assetidsearchtype,$filetype,$orientation,$createddate,$createdsearchtype,$publicprivate,$filehash,$assetlabel,$assetlabelsearchtype,$filename,$filenamesearchtype,$limit)
 {
  $assets=array(); $db=new mysql; 
  $db->connect();
  
  //because we cant bind parameters with a wildcard, we have to manually build the query string 
  // 
  
  $assetid=$this->sqlclean($assetid);
  if($assetidsearchtype=='equals'){$assetidsearch=$assetid;}
  if($assetidsearchtype=='startswith'){$assetidsearch=$assetid.'%';}
  if($assetidsearchtype=='contains'){$assetidsearch='%'.$assetid.'%';}
  if($assetidsearchtype=='endswith'){$assetidsearch='%'.$assetid;}
  
  $assetlabel=$this->sqlclean($assetlabel);
  if($assetlabelsearchtype=='equals'){$assetlabelsearch=$assetlabel;}
  if($assetlabelsearchtype=='startswith'){$assetlabelsearch=$assetlabel.'%';}
  if($assetlabelsearchtype=='contains'){$assetlabelsearch='%'.$assetlabel.'%';}
  if($assetlabelsearchtype=='endswith'){$assetlabelsearch='%'.$assetlabel;}
  
  $filename=$this->sqlclean($filename);
  if($filenamesearchtype=='equals'){$filenamesearch=$filename;}
  if($filenamesearchtype=='startswith'){$filenamesearch=$filename.'%';}
  if($filenamesearchtype=='contains'){$filenamesearch='%'.$filename.'%';}
  if($filenamesearchtype=='endswith'){$filenamesearch='%'.$filename;}
   
  if($filetype=='any'){$filetype='%';}
  if($orientation=='any'){$orientation='%';}
  
  if($createdsearchtype=='any'){$createdsearchtype='like'; $createddate='%';}
  if($createdsearchtype=='from'){$createdsearchtype='>=';}
  if($createdsearchtype=='to'){$createdsearchtype='<=';}
  if($createdsearchtype=='on'){$createdsearchtype='=';}
  
  $publicprivateclause=''; // "any"
  if($publicprivate=='public'){$publicprivateclause='and public=1';}
  if($publicprivate=='private'){$publicprivateclause='and public=0';}
  
  if($filehash==''){$filehash='%';}
  $sql="select * from asset where assetid like '".$assetidsearch."' and fileType like ? and orientationViewCode like ? and createdDate ".$createdsearchtype." ? ".$publicprivateclause." and fileHashMD5 like ? and assetlabel like '".$assetlabelsearch."' and filename like '".$filenamesearch."'";
   
  if($stmt=$db->conn->prepare($sql))
  {
   $stmt->bind_param('ssss',$filetype,$orientation,$createddate,$filehash);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $assets[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode'],'assetlabel'=>$row['assetlabel'],'frame'=>$row['frame'],'totalFrames'=>$row['totalFrames'],'plane'=>$row['plane'],'totalPlanes'=>$row['totalPlanes']);
   }
  }
  $db->close();
  return $assets;   
 }

 
 function getAssetByOID($oid)
 {
  $db=new mysql; $db->connect(); $asset=false;
  
  if($stmt=$db->conn->prepare('select * from asset where oid=?'))
  {
   $stmt->bind_param('s',$oid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $asset=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode'],'assetlabel'=>$row['assetlabel'],'frame'=>$row['frame'],'totalFrames'=>$row['totalFrames'],'plane'=>$row['plane'],'totalPlanes'=>$row['totalPlanes']);
   }
  }
  $db->close();
  return $asset;
 }

  
 
 
 
 
 function logAssetEvent($assetid,$userid,$description,$newoid)
 {
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into asset_history (id,assetid,eventdatetime,userid,description,new_oid) values(null,?,now(),?,?,?)'))
  {
   if($newoid==''){$newoid= $this->newoid();}
   $stmt->bind_param('siss', $assetid,$userid,$description,$newoid);
   $stmt->execute();
  } // else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }

 function getHistoryEventsForAsset($assetid,$limit)
 {
  $db=new mysql; $db->connect();
  $events=array();
  if($stmt=$db->conn->prepare('select * from asset_history where assetid=? order by eventdatetime desc limit ?'))
  {
   $stmt->bind_param('si', $assetid,$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $events[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'eventdatetime'=>$row['eventdatetime'],'userid'=>$row['userid'],'description'=>$row['description'],'new_oid'=>$row['new_oid']);
   }
  }
  $db->close();
  return $events;
 }

 function niceBoolText($value,$textiftrue,$textiffalse)
 {
    $nicevalue=$textiffalse;
    if($value==true)
    {
        $nicevalue=$textiftrue;        
    }
    return $nicevalue;
 }

 function niceExifTypeName($type)
 {
    switch($type)
    {
        case 1:
            $name='GIF';
            break;
        case 2:
            $name='JPG';
            break;
        case 3:
            $name='PNG';
            break;
        case 4:
            $name='SWF';
            break;
        case 5:
            $name='PSD';
            break;
        case 6:
            $name='BMP';
            break;
        case 7:
            $name='TIFF_II (intel byte order)';
            break;
        case 8:
            $name='TIFF_MM (motorola byte order)';
            break;
        case 9:
            $name='JPC';
            break;
        case 10:
            $name='JP2';
            break;
        case 11:
            $name='JPX';
            break;
        case 12:
            $name='JB2';
            break;
        case 13:
            $name='SWC';
            break;
        case 14:
            $name='IFF';
            break;
        case 15:
            $name='WBMP';
            break;
        case 16:
            $name='XBM';
            break;
        case 17:
            $name='ICO';
            break;
        case 18:
            $name='WEBP';
            break;
        default:
            $name='unknown EXIF type ('.$type.')';
            break;
    } 
    return $name;
 }
 
 function niceFileSize($size)
 {
     $nicevalue= number_format($size/1000,0).'KB';
     if($size>1000000)
     {
         $nicevalue= number_format($size/1000000,1,'.',',').'MB';
     }
     if($size>10000000)
     {
         $nicevalue= number_format($size/1000000,0,'.',',').'MB';
     }
     return $nicevalue;
 }
 
 function attributesOfAssetAtURI($uri)
 {
     /* retreive a file from uri and compute hash of it
      * return false if download failed
      */

    if(strstr($uri, 'youtube.com') || strstr($uri, 'y2u.be')){return false;} // don't try do download youtube vids
     
    $attributes=false;
    $fixedescapeduri = str_replace(['%2F', '%3A', '%2B'], ['/', ':','+'], urlencode($uri));  // the %2B is an AWS-specific hack
    $assetfilecontents = file_get_contents($fixedescapeduri);
    if($assetfilecontents)
    {
        $attributes['fileHashMD5']=md5($assetfilecontents); $attributes['filesize']=strlen($assetfilecontents);
    }
    return $attributes;
 }
 
 
 function getAssetlabels()
 {
  $db=new mysql; $db->connect(); $assetlabels=array();
  if($stmt=$db->conn->prepare('select * from assetlabel order by labeltext'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $assetlabels[]=array('id'=>$row['id'],'labeltext'=>$row['labeltext']);
   }
  }
  $db->close();
  return $assetlabels;
 }
 
 
 
 
}?>
