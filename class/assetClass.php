<?php
include_once("mysqlClass.php");

class asset
{

 function addAsset($assetid,$filename,$localpath,$uri,$orientationViewCode,$colorModeCode,$assetHeight,$assetWidth,$dimensionUOM,$background,$fileType,$public,$approved,$description,$oid,$fileHashMD5,$filesize,$uripublic)
 {
  $id=false;
  $db=new mysql; 
  $db->connect();
  if($stmt=$db->conn->prepare('insert into asset(id,assetid,filename,localpath,uri,orientationViewCode,colorModeCode,assetHeight,assetWidth,dimensionUOM,background,fileType,createdDate,public,approved,description,oid,fileHashMD5,filesize,uripublic) values(null,?,?,?,?,?,?,?,?,?,?,?,date(now()),?,?,?,?,?,?,?)'))
  {
   if($stmt->bind_param('ssssssiisssiisssii',$assetid,$filename,$localpath,$uri,$orientationViewCode,$colorModeCode,$assetHeight,$assetWidth,$dimensionUOM,$background,$fileType,$public,$approved,$description,$oid,$fileHashMD5,$filesize,$uripublic))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    }else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
   }else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  }else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
  return $id;
 }

 function getAssetRecordsByAssetid($assetid)
 {
  $records=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from asset where assetid=?'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize']);
   }
  }
  $db->close();
  return $records;   
 }
 

 function getAssetById($id)
 {
  $asset=false;
  $db=new mysql; $db->connect();
  
  if($stmt=$db->conn->prepare('select * from asset where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
       $asset=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize']);
   }
  }
  $db->close();
  return $asset;   
 }
 
 function connectPartToAsset($part,$assetid,$assettypecode,$sequence)
 {
  $id=false;
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into part_asset (id,partnumber,assetid,assettypecode,sequence) values(null,?,?,?,?)'))
  {   
   $stmt->bind_param('sssi',$part,$assetid,$assettypecode,$sequence);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
  
  return $id;   
 }
 
 function disconnectPartFromAsset($part,$assetid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from part_asset where part=? and assetid=?'))
  {
   $stmt->bind_param('ss',$part,$assetid);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }
 
 function getPartsConnectedToAsset($assetid)
 {
  $connections=false;
  $db=new mysql; $db->connect();
  
  if($stmt=$db->conn->prepare('select * from part_asset where assetid=? order by partnumber'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $connections[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'partnumber'=>$row['partnumber'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence']);
   }
  }
  $db->close();
  return $connections;   
 }
 
 function getAssetsConnectedToPart($partnumber)
 {
  $connections=false;
  $db=new mysql; $db->connect();
  
  if($stmt=$db->conn->prepare('select * from part_asset where partnumber=? order by sequence'))
  {
   $stmt->bind_param('s',$partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $connections[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'partnumber'=>$row['partnumber'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence']);
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
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
  
  // need to delete (unlink) local file if it exists
  
 }
 
 function setAssetDescription($assetid,$description)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set description=? where assetid=?'))
  {
   $encodednotes=base64_encode($internalnotes);
   $stmt->bind_param('ss', $encodednotes,$assetid);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }
 
 function toggleAssetPublic($id)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set public=public XOR 1 where id=?'))
  {
   $stmt->bind_param('i', $id);
   $stmt->execute();
  } //else{print_r($db->conn->error);}
  $db->close();
 }
 
 function toggleAssetUriPublic($assetid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set uripublic=uripublic XOR 1 where assetid=?'))
  {
   $stmt->bind_param('s', $assetid);
   $stmt->execute();
  } //else{print_r($db->conn->error);}
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
       $assets[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize']);
   }
  }
  $db->close();
  return $assets;   
 }

 function logAppEvent($assetid,$userid,$description,$newoid)
 {
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into asset_history (id,assetid,eventdatetime,userid,description,new_oid) values(null,?,now(),?,?,?)'))
  {
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
            $name='JPEG';
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
            $name='unknown EXIF type';
            break;
    } 
    return $name;
 }
 
 function niceFileSize($size)
 {
     $nicevalue= number_format($size/1000,0).'KB';
     if($size>1000000)
     {
         $nicevalue= number_format($size/100000,1,',','.').'MB';
     }
     if($size>10000000)
     {
         $nicevalue= number_format($size/1000000,0,',','.').'MB';
     }
     
     
          
     return $nicevalue;
 }
 
}?>
