<?php
include_once("mysqlClass.php");

class asset
{

 function addAsset($assetid,$filename,$localpath,$uri,$orientationViewCode,$colorModeCode,$assetHeight,$assetWidth,$dimensionUOM,$resolution,$background,$fileType,$public,$approved,$description,$oid,$fileHashMD5,$filesize,$uripublic,$languagecode,$createddate=false)
 {
  $id=false;
  $db=new mysql; 
  $db->connect();

  $created=date('Y-m-d');
  if($createddate){$created=$createddate;}
  
  if($stmt=$db->conn->prepare('insert into asset(id,assetid,filename,localpath,uri,orientationViewCode,colorModeCode,assetHeight,assetWidth,dimensionUOM,resolution,background,fileType,createdDate,public,approved,description,oid,fileHashMD5,filesize,uripublic,languagecode) values(null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'))
  {
   if($stmt->bind_param('ssssssiisisssiisssiis',$assetid,$filename,$localpath,$uri,$orientationViewCode,$colorModeCode,$assetHeight,$assetWidth,$dimensionUOM,$resolution,$background,$fileType,$created,$public,$approved,$description,$oid,$fileHashMD5,$filesize,$uripublic,$languagecode))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    } else{echo 'problem with execute: '.$db->conn->error;}
   } else{echo 'problem with bind';}
  } else{echo 'problem with prepare';}
  $db->close();
  return $id;
 }

 function getAssetRecordsByAssetid($assetid)
 {
  $records=array();
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from asset where assetid=?'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode']);
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
       $asset=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode']);
   }
  }
  $db->close();
  return $asset;   
 }
 
 function connectPartToAsset($part,$assetid,$assettypecode,$sequence,$representation)
 {
  $id=false;
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into part_asset (id,partnumber,assetid,assettypecode,sequence,representation) values(null,?,?,?,?,?)'))
  {   
   $stmt->bind_param('sssis',$part,$assetid,$assettypecode,$sequence,$representation);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }//else{echo $db->conn->error;}//$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
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
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }
 
 function getPartsConnectedToAsset($assetid)
 {
  $connections=array();
  $db=new mysql; $db->connect();
  
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
  $db=new mysql; $db->connect();
  $connections=array();
  
  $publicclause=''; if($excludenonpublic){$publicclause=' and public=1';}
  
  if($stmt=$db->conn->prepare('select part_asset.id as connectionid, partnumber,assettypecode,sequence,representation, asset.* from part_asset,asset where part_asset.assetid=asset.assetid and partnumber=? '.$publicclause.' order by sequence'))
  {
   if($stmt->bind_param('s',$partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
       $connections[]=array('id'=>$row['id'],'connectionid'=>$row['connectionid'],'assetid'=>$row['assetid'],'partnumber'=>$row['partnumber'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence'],'representation'=>$row['representation'],'uri'=>$row['uri'],'filename'=>$row['filename'],'filetype'=>$row['fileType']);
     }
    }
   }
  }
  $db->close();
  return $connections;   
 }

 function getAssetByPartConnectionid($connectionid)
 {
  $db=new mysql; $db->connect();
  $asset=false;
  
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

 function getUnconnecteddAssets()
 {
  $db=new mysql; $db->connect();
  $assets=array();
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
 
 
 
 function deleteAssetsByAssetid($assetid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from asset where assetid=?'))
  {
   $stmt->bind_param('s',$assetid);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
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
  if($stmt=$db->conn->prepare('update asset set description=? where assetid=?'))
  {
   $encodednotes=base64_encode($internalnotes);
   $stmt->bind_param('ss', $encodednotes,$assetid);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }

 function updateAssetOID($assetid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update asset set oid=? where id=?'))
  {
   $oid=$this->newoid();
   $stmt->bind_param('ss', $oid,$assetid);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
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
       $assets[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode']);
   }
  }
  $db->close();
  return $assets;   
 }

 function sqlclean($string)
 {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
   $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
   return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
}
 
 function getAssets($assetid,$assetidsearchtype,$filetype,$orientation,$createddate,$createdsearchtype,$publicprivate,$filehash,$limit)
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
  $sql="select * from asset where assetid like '".$assetidsearch."' and fileType like ? and orientationViewCode like ? and createdDate ".$createdsearchtype." ? ".$publicprivateclause." and fileHashMD5 like ?";
   
  if($stmt=$db->conn->prepare($sql))
  {
   $stmt->bind_param('ssss',$filetype,$orientation,$createddate,$filehash);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $assets[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode']);
   }
  }
  $db->close();
  return $assets;   
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
 
}?>
