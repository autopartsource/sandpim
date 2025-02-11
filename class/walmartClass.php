<?php
include_once("mysqlClass.php");

class walmart
{
 var $clientid='';
 var $secret='';
 var $consumerid='';
 var $consumerchanneltype='';
 var $accesstoken='';
 var $errormessage='';
 var $correlationid='';
 var $feedid='';
 var $feedtype='';
 var $reportcontent='';
 var $httpstatus;
 
 public function __construct($_clientid, $_secret, $_consumerid, $_consumerchanneltype) 
 {
  $this->clientid=$_clientid;
  $this->secret=$_secret; 
  $this->consumerid=$_consumerid;
  $this->consumerchanneltype=$_consumerchanneltype;
 } 
    
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
    
 function timeAgo($epoch1,$epoch2)
 {
     if(($epoch2-$epoch1)<0){return 'in the future';}
     if(($epoch2-$epoch1)>145152000){return 'a long time ago';}
     if(($epoch2-$epoch1)>145152000){return '8 months ago';}
     if(($epoch2-$epoch1)>127008000){return '7 months ago';}
     if(($epoch2-$epoch1)>108864000){return '6 months ago';}
     if(($epoch2-$epoch1)>90720000){return '5 months ago';}
     if(($epoch2-$epoch1)>72576000){return '4 months ago';}
     if(($epoch2-$epoch1)>54432000){return '3 months ago';}
     if(($epoch2-$epoch1)>36288000){return '2 months ago';}
     if(($epoch2-$epoch1)>18144000){return 'a month ago';}
     if(($epoch2-$epoch1)>12700800){return '3 weeks ago';}
     if(($epoch2-$epoch1)>8467200){return '2 weeks ago';}
     if(($epoch2-$epoch1)>604800){return 'a week ago';}
     if(($epoch2-$epoch1)>518400){return '6 days ago';}
     if(($epoch2-$epoch1)>432000){return '5 days ago';}
     if(($epoch2-$epoch1)>345600){return '4 days ago';}
     if(($epoch2-$epoch1)>259200){return '3 days ago';}
     if(($epoch2-$epoch1)>172800){return '2 days ago';}
     if(($epoch2-$epoch1)>86400){return 'yesterday';}     
     if(($epoch2-$epoch1)>43200){return '12 hours ago';}
     if(($epoch2-$epoch1)>36000){return '10 hours ago';}
     if(($epoch2-$epoch1)>32400){return '9 hours ago';}
     if(($epoch2-$epoch1)>28800){return '8 hours ago';}
     if(($epoch2-$epoch1)>25200){return '7 hours ago';}
     if(($epoch2-$epoch1)>21600){return '6 hours ago';}
     if(($epoch2-$epoch1)>18000){return '5 hours ago';}
     if(($epoch2-$epoch1)>14400){return '4 hours ago';}
     if(($epoch2-$epoch1)>10800){return '3 hours ago';}
     if(($epoch2-$epoch1)>7200){return '2 hours ago';}
     if(($epoch2-$epoch1)>3600){return 'an hour ago';}
     // diff is less than an hour
     return round(($epoch2-$epoch1)/60,0).' minutes ago';      
 }
 
 
 
 function getRecentSessions($limit)
 {
  $db = new mysql; $db->connect(); $sessions=array();
  if($stmt=$db->conn->prepare('select * from wmapisession order by id desc limit ?'))
  {
   $stmt->bind_param('i', $limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $sessions[]=array('id'=>$row['id'],'state'=>$row['state'],'accesstoken'=>$row['accesstoken'],'correlationid'=>$row['correlationid'],'startepoch'=>$row['startepoch'],'messages'=>$row['messages']);
   }
  }
  $db->close();
  return $sessions;
 }
 
 function getSession($sessionid)
 {
  $db = new mysql; $db->connect(); $session=false;
  if($stmt=$db->conn->prepare('select * from wmapisession where id=?'))
  {
   $stmt->bind_param('i', $sessionid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $session=array('id'=>$row['id'],'state'=>$row['state'],'accesstoken'=>$row['accesstoken'],'correlationid'=>$row['correlationid'],'startepoch'=>$row['startepoch'],'messages'=>$row['messages']);
   }
  }
  $db->close();
  return $session;
 }

 
 function saveSession($state,$accesstoken,$correlationid,$startepoch,$messages)
 {
  $db = new mysql; $db->connect(); $sessions=array();
  if($stmt=$db->conn->prepare('insert into wmapisession (id,state,accesstoken,correlationid,startepoch,messages) values(null,?,?,?,?,?)'))
  {
   $stmt->bind_param('sssis', $state,$accesstoken,$correlationid,$startepoch,$messages);
   $stmt->execute();
  }
  $db->close();
 }

 function getFeed($id)
 {
  $db = new mysql; $db->connect(); $feed=false;
  if($stmt=$db->conn->prepare('select * from wmapifeed where id=?'))
  {
   $stmt->bind_param('i', $id);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $feed=array('id'=>$row['id'],'feedid'=>$row['feedid'],'type'=>$row['type'],'state'=>$row['state'],'localfile'=>$row['localfile'],'postfilename'=>$row['postfilename'],'receiverprofileid'=>$row['receiverprofileid'],'messages'=>$row['messages'],'epochstart'=>$row['epochstart'],'progress'=>$row['progress'],'errors'=>$row['errors']);
   }
  }
  $db->close();
  return $feed;
 }


 function getFeeds($limit)
 {
  $db = new mysql; $db->connect(); $feeds=array();
  if($stmt=$db->conn->prepare('select * from wmapifeed order by id desc limit ?'))
  {
   $stmt->bind_param('i', $limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $feeds[]=array('id'=>$row['id'],'feedid'=>$row['feedid'],'type'=>$row['type'],'state'=>$row['state'],'localfile'=>$row['localfile'],'postfilename'=>$row['postfilename'],'receiverprofileid'=>$row['receiverprofileid'],'messages'=>$row['messages'],'epochstart'=>$row['epochstart'],'progress'=>$row['progress'],'errors'=>$row['errors']);
   }
  }
  $db->close();
  return $feeds;
 }


 
 function saveFeed($feedid,$type,$localfile,$postfilename,$receiverprofileid,$messages,$progress,$errors)
 {
  $db = new mysql; $db->connect(); $state='NEW'; $epochstart=time();
  if($stmt=$db->conn->prepare('insert into wmapifeed (id,feedid,`type`,state,localfile,postfilename,receiverprofileid,messages,epochstart,progress,errors) values(null,?,?,?,?,?,?,?,?,?,?)'))
  {
   $stmt->bind_param('sssssisidi', $feedid,$type,$state,$localfile,$postfilename,$receiverprofileid,$messages,$epochstart,$progress,$errors);
   $stmt->execute();
  }
  $db->close();
 }
 
 function updateFeedState($id,$state)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update wmapifeed set state=? where id=?'))
  {
   $stmt->bind_param('si',$state,$id);
   $stmt->execute();
  }
  $db->close();
 }
 
 function updateFeedProgress($id,$progress)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update wmapifeed set progress=? where id=?'))
  {
   $stmt->bind_param('di',$progress,$id);
   $stmt->execute();
  }
  $db->close();
 }
 
 function updateFeedErrors($id,$errors)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update wmapifeed set errors=? where id=?'))
  {
   $stmt->bind_param('ii',$errors,$id);
   $stmt->execute();
  }
  $db->close();
 }
 
 function apiGetAccessToken()
 {
  $success=false;
  $this->correlationid= $this->uuidv4();
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,'https://marketplace.walmartapis.com/v3/token');
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS,'grant_type=client_credentials');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $headers = [
    'WM_SVC.NAME: Walmart Marketplace',
    'WM_QOS.CORRELATION_ID: '.$this->correlationid,
    'Authorization: Basic '.base64_encode($this->clientid.':'.$this->secret),
    'Content-Type: application/x-www-form-urlencoded',
    'wm_consumer.id: '.$this->consumerid,
    'WM_CONSUMER.CHANNEL.TYPE: '.$this->consumerchanneltype
  ];

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $response = curl_exec ($ch);
  $this->httpstatus=curl_getinfo($ch,CURLINFO_HTTP_CODE);
  curl_close ($ch);
  $xmlobject=simplexml_load_string($response);

  if(property_exists($xmlobject,'accessToken'))
  {   
   $temp=(array)$xmlobject->accessToken;
   $this->accesstoken=$temp[0];
   $success=true;
  }
  else
  {
   $ns = $xmlobject->getNamespaces(true);
   foreach ($xmlobject->children($ns['ns2'])->error as $error)
   {
    $this->errormessage.=$error->children($ns['ns2'])->description.'; ';
   }
  }
  return $success;
 }
 
 
 
 function apiPostFile($filepath,$uploadfilename,$filetype)
 {
  $success=false;
  $cf=new CURLFile($filepath, 'application/zip', $uploadfilename);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,'https://marketplace.walmartapis.com/v3/feeds?feedType='.$filetype);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $headers = [
    'WM_SVC.NAME: Walmart Marketplace',
    'WM_QOS.CORRELATION_ID: '.$this->correlationid,
    'Accept: application/json',
    'WM_SEC.ACCESS_TOKEN: '.$this->accesstoken,
    'Authorization: Basic '.base64_encode($this->clientid.':'.$this->secret),
    'Content-Type: multipart/form-data'
  ];
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cf]);
  $response = curl_exec ($ch);
  $this->httpstatus=curl_getinfo($ch,CURLINFO_HTTP_CODE);
  curl_close ($ch);  
  $decoded=json_decode($response,true);
  
  if(is_array($decoded) && array_key_exists('feedId',$decoded))
  {
   $this->feedid=$decoded['feedId'];
   $success=true;
  }
  else
  {
   $this->errormessage=$response;
  }
  
  return $success;     
 }
 
 
 function apiStreamReport()
 {
  $success=false;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,'https://marketplace.walmartapis.com/v3/feeds/'.$this->feedid.'/errorReport?feedType='.$this->feedtype);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $headers = [
    'feedId: '.$this->feedid,
    'feedType: '.$this->feedtype,
    'Authorization: Basic '.base64_encode($this->clientid.':'.$this->secret),
    'WM_SEC.ACCESS_TOKEN: '.$this->accesstoken,
    'WM_QOS.CORRELATION_ID: '.$this->correlationid,
    'WM_SVC.NAME: Walmart Marketplace',
    'ACCEPT: application/octet-stream'
  ];
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $this->reportcontent = curl_exec ($ch);
  $this->httpstatus=curl_getinfo($ch,CURLINFO_HTTP_CODE);
  curl_close ($ch);
  return $success;     
 }

 function apiGetFeedEvents()
 {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,'https://marketplace.walmartapis.com/v3/feeds/'.$this->feedid);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $headers = [
    'feedId: '.$this->feedid,
    'Authorization: Basic '.base64_encode($this->clientid.':'.$this->secret),
    'WM_SEC.ACCESS_TOKEN: '.$this->accesstoken,
    'WM_QOS.CORRELATION_ID: '.$this->correlationid,
    'WM_SVC.NAME: Walmart Marketplace',
    'Accept: application/json'
  ];
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $response = curl_exec ($ch);
  $decoded=json_decode($response,true);  
  $this->httpstatus=curl_getinfo($ch,CURLINFO_HTTP_CODE);
  curl_close ($ch);
  return $decoded;
 }


 
 
 

 
}?>
