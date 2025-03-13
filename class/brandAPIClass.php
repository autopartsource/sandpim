<?php
include_once("mysqlClass.php");

class brandapi
{
 public $debug;
 public $activetoken;
 public $localdbname;
 public $clientid;
 public $clientsecret;
 public $username;
 public $password; 
 public $token;
 public $tokenvaliduntil;
 public $errormessage; 
 public $httpstatus;
 public $response;
 public $responseheaders;
 public $databases;
 public $nextpagelink;
 public $records;
 public $morepages;
 public $tableslist;
 public $tablekeyslist; 
 public $pagelimit;
 public $totalcalls;
 public $tokenrefreshcount;
 public $insertcount;
 public $updatecount;
 public $deletecount;
 public $deleteorphancount;

 
 public function __construct($_localdbname=false)
 {
  $this->pagelimit=0;
  $this->totalcalls=0;
  $this->activetoken=false;
  $this->tokenrefreshcount=0;
  $this->insertcount=0;
  $this->updatecount=0;
  $this->deletecount=0;
  $this->deleteorphancount=0;
  
  $this->localdbname=$_localdbname;  // default to the hard-coded dbname from the class file 
  if(!$_localdbname)
  { // no secific local database name was passed in. Consult pim database for the name
      
   $db = new mysql; $db->connect();
   if($stmt=$db->conn->prepare("select configvalue from config where configname='brandAPIcacheDatabase'"))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      if($row = $db->result->fetch_assoc())
      {
       $this->localdbname=$row['configvalue'];
      }
     }
    }
    $db->close();
   }
  }  
 }
 
 function getAccessToken()
 {
  $success=false;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,'https://autocare-identity.autocare.org/connect/token');
  //curl_setopt($ch, CURLOPT_CAINFO,'/etc/pki/ca-trust/extracted/pem/godaddyroot20241129.pem');
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
      'grant_type'=>'password',
      'username'=>$this->username,
      'password'=>$this->password,
      'client_id'=>$this->clientid,
      'client_secret'=>$this->clientsecret,
      'scope'=>'CommonApis QDBApis PcadbApis BrandApis VcdbApis offline_access')));

  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

  $this->response = curl_exec($ch);
  $this->totalcalls++;
  $this->httpstatus=curl_getinfo($ch,CURLINFO_HTTP_CODE);
  if($this->httpstatus=='200')
  {
   $responsearray = json_decode($this->response,true);
   if(array_key_exists('access_token',$responsearray))
   {
    $this->token=$responsearray['access_token'];
    $this->tokenvaliduntil=intval($responsearray['expires_in'])+time() ;
    $this->activetoken=true;
    $this->tokenrefreshcount++;
   }      
  }
  else
  {
   $this->errormessage=curl_error($ch);  
  }  
  curl_close ($ch);
  return $success;
 }
 
 function tokenLife()
 {
     $secondsremaining = ($this->tokenvaliduntil - time());
     if($secondsremaining <= 0){$secondsremaining=0;$this->activetoken=false;}
     return $secondsremaining;
 }
 
 
 
 function getRecords($database,$table,$cultureid,$sincedate)
 {
  $pagecount=0;
  $this->morepages=false;
  $this->records=array();  
  $this->nextpagelink='';
  while(true)
  {    
   $success=$this->getRecordsPage($database, $table, $cultureid, $sincedate);
   if(!$success){return false;}
   $pagecount++;
   if(!$this->morepages){break;}
   if($this->pagelimit > 0 && $pagecount>=$this->pagelimit){break;}
  }
  return true;
 } 
 
 
 function getRecordsPage($database,$table,$cultureid,$sincedate)
 {
  $url='';
  $sincedateclause=''; if($sincedate){$sincedateclause='&SinceDate='.$sincedate;}
  $ch = curl_init();
  $headers = [];
  
  $authorization = "Authorization: Bearer ".$this->token;
  if($this->morepages)
  { // continuation link exists
   //curl_setopt($ch, CURLOPT_URL,$this->nextpagelink);
   $url=$this->nextpagelink;
   curl_setopt($ch, CURLOPT_URL,$url);  
  }
  else
  {// no continuation link exists - this is the inital call
// curl_setopt($ch, CURLOPT_URL,'https://'.$database.'.autocarevip.com/api/v1.0/'.$database.'/'.$table.'?CultureId='.$cultureid.$sincedateclause);
   $url='https://'.$database.'.autocarevip.com/api/v1.0/'.$database.'/'.$table.'?CultureId='.$cultureid.$sincedateclause;
   curl_setopt($ch, CURLOPT_URL,$url);
  }
  
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  
  curl_setopt($ch, CURLOPT_HEADERFUNCTION,
   function($curl, $header) use (&$headers)
   {
    $len = strlen($header);
    $header = explode(':', $header, 2);
    if (count($header) < 2) // ignore invalid headers
      return $len;
    $headers[strtolower(trim($header[0]))][] = trim($header[1]);
    return $len;
   }
  );
  
  $this->response = curl_exec($ch);
  $this->totalcalls++;
  $this->httpstatus=curl_getinfo($ch,CURLINFO_HTTP_CODE);
  $this->responseheaders=$headers;
  $this->nextpagelink='';
  $this->morepages=false;
  
  if(array_key_exists('x-pagination',$headers))
  {
   $paginationarray=json_decode($headers['x-pagination'][0],true);
   if(array_key_exists('nextPageLink',$paginationarray)&&trim($paginationarray['nextPageLink'])!='')
   {
    $this->nextpagelink= str_replace('http:','https:', $paginationarray['nextPageLink']);    
    $this->morepages=true;
   }
  }  
  
  if($this->debug){echo 'CURL url: '.$url."\r\n";}    
  
  if($this->httpstatus=='200')
  {
   if($this->debug){echo 'raw http response: '.$this->response."\r\n";}    
   $recordstemp=json_decode($this->response,true);
   foreach($recordstemp as $recordtemp){$this->records[]=$recordtemp;}      
   curl_close ($ch);
   return true;
  }
  else
  {
   $this->errormessage=curl_error($ch);
   curl_close ($ch);
   if($this->debug){echo 'CURL http status code: '.$this->httpstatus.', CURL errormessage:'.$this->errormessage."\r\n";}
   return false;
  }
 }

 function populateBrandTable($records)
 {
  $db = new mysql; $db->dbname=$this->localdbname; $db->connect(); 
  $inserts=0;
  
  $stmt=$db->conn->prepare('delete from autocarebrand');
  $stmt->execute();
    
  if($stmt=$db->conn->prepare('insert into autocarebrand values(null,?,?,?,?,?,?,?)'))
  {
   if($stmt->bind_param('sssssss', $ParentID,$ParentCompany,$BrandID,$BrandName,$SubBrandID,$SubBrandName,$BrandOEMFlag))
   {
    foreach($records as $record)
    {
     $ParentID=$record['ParentID']; $ParentCompany=$record['ParentCompany']; $BrandID=$record['BrandID']; $BrandName=$record['BrandName']; $SubBrandID=$record['SubBrandID']; $SubBrandName=$record['SubBrand']; $BrandOEMFlag=$record['BrandOEMFlag'];
     if($stmt->execute()){$this->insertcount++;}          
    }         
   }
  }
  
  $db->close();
  return $inserts;
 }

 
 
 
}

?>
