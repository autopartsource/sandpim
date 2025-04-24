<?php
include_once("mysqlClass.php");

class qdbapi
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
  $this->tableslist=array('GroupNumber','Qualifier','QualifierGroup','QualifierType');
  $this->tablekeyslist=array('GroupNumber'=>'GroupNumberID','Qualifier'=>'QualifierID','QualifierGroup'=>'QualifierGroupID','QualifierType'=>'QualifierTypeID');   
 
  $this->tableslist=array('Qualifier');
      
  $this->pagelimit=0;
  $this->totalcalls=0;
  $this->activetoken=false;
  $this->tokenrefreshcount=0;
  $this->insertcount=0;
  $this->updatecount=0;
  $this->deletecount=0;
  $this->deleteorphancount=0;
  
  $this->localdbname=$_localdbname;  // default to the hard-coded dbname from the class file (prob "qdb")
  if(!$_localdbname)
  { // no secific vsersion was passed in. Consult pim database for the name
    // of the active qdb database. It will be something like qdb20210827
      
   $db = new mysql; $db->connect();
   if($stmt=$db->conn->prepare("select configvalue from config where configname='qdbAPIcacheDatabase'"))
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
 
 function getDatabaseses()
 {
  $success=false;
  $ch = curl_init();
  $authorization = "Authorization: Bearer ".$this->token;
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_URL,'https://common.autocarevip.com/api/v1.0/databases');
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $this->response = curl_exec($ch);
  $this->totalcalls++;
  $this->httpstatus=curl_getinfo($ch,CURLINFO_HTTP_CODE);
  if($this->httpstatus=='200')
  {
   $this->databases = json_decode($this->response,true);
  }
  else
  {
   $this->errormessage=curl_error($ch);  
  }  
  curl_close ($ch);
  return $success;
 }
 
 function getTables($database)
 {
  $ch = curl_init();
  $authorization = "Authorization: Bearer ".$this->token;
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_URL,'https://common.autocarevip.com/api/v1.0/databases/'.$database.'/tables');
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $this->response = curl_exec($ch);
  $this->totalcalls++;
  $this->httpstatus=curl_getinfo($ch,CURLINFO_HTTP_CODE);
  if($this->httpstatus=='200')
  {
   $tables = json_decode($this->response,true);
   curl_close ($ch);
   return $tables;
  }
  else
  {
   $this->errormessage=curl_error($ch);
   curl_close ($ch);
   return false;
  }
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
  
 function clearTable($tablename)
 {
  $db = new mysql; $db->dbname=$this->localdbname; $db->connect();
  if($stmt=$db->conn->prepare('delete from '.$tablename))
  {
   $stmt->execute();
  } 
  $db->close();     
 }
 
 function getTableIDs($tablename)
 {
  // returns an array keyed by the primary key of the given table
  $existingids=array();
  if(!array_key_exists($tablename,$this->tablekeyslist))
  {
   echo 'missing table name in keyfields list: '.$tablename."\r\n";
   return $existingids;
  }
  
  
  $keyfieldname=$this->tablekeyslist[$tablename];
  
  if($keyfieldname==''){return $existingids;}// this is a keyless table 
  
  $db = new mysql; $db->dbname=$this->localdbname; $db->connect();
  
  if($stmt=$db->conn->prepare('select `'.$keyfieldname.'` from `'.$tablename.'`'))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $existingids[$row[$keyfieldname]]=1;
    }
   }
  }
  $db->close();
  return $existingids;
 }
 
 
 function populateTable($tablename,$records,$deletelocalorphans)
 {
  $db = new mysql; $db->dbname=$this->localdbname; $db->connect(); 
  $inserts=0; $idkeyedapirecords=array();
  
  if(!array_key_exists($tablename,$this->tablekeyslist)){return 0;}

  $existingids=$this->getTableIDs($tablename);
  $keyfieldname=$this->tablekeyslist[$tablename];
  $idkeyedapirecords=array(); 
  $localorphanids=array();
  
  
  if($keyfieldname!='')
  {// this is a keyed table
   foreach($records as $record)
   {
    $idkeyedapirecords[$record[$keyfieldname]]=$record;
   }
  }
  
  if($deletelocalorphans)
  { // find orphaned ID's in local table that are not in API results so they can be deleted
   foreach($existingids as $id=>$trash)
   {
    if(!array_key_exists($id,$idkeyedapirecords)){$localorphanids[]=$id;}
   }
  }
  
  
  switch($tablename)
  {
//-------------- GroupNumber    
   case 'GroupNumber':
       
    if($stmt=$db->conn->prepare('insert into GroupNumber values(?,?)'))
    {
     if($stmt->bind_param('is', $GroupNumberID, $GroupDescription))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $GroupNumberID=$record['GroupNumberID'];
        $GroupDescription=$record['GroupDescription'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from GroupNumber where GroupNumberID=?'))
    {
     if($stmt->bind_param('i', $GroupNumberID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $GroupNumberID=$record['GroupNumberID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $GroupNumberID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update GroupNumber set GroupDescription=? where GroupNumberID=?'))
    {
     if($stmt->bind_param('si', $GroupDescription,$GroupNumberID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $GroupNumberID=$record['GroupNumberID'];
        $GroupDescription=$record['GroupDescription']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;
          
//--------------------- Qualifier    
          
   case 'Qualifier':
       
    if($stmt=$db->conn->prepare('insert into Qualifier values(?,?,?,?,?,?)'))
    {
     if($stmt->bind_param('issiis', $QualifierID, $QualifierText,$ExampleText,$QualifierTypeID,$NewQualifierID,$WhenModified))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $QualifierID=$record['QualifierID'];
        $QualifierText=$record['QualifierText'];
        $ExampleText=$record['ExampleText'];
        $QualifierTypeID=$record['QualifierTypeID'];
        $NewQualifierID=$record['NewQualifierID'];
        $WhenModified=$record['WhenModified'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Qualifier where QualifierID=?'))
    {
     if($stmt->bind_param('i', $QualifierID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $QualifierID=$record['QualifierID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $QualifierID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Qualifier set QualifierText=?,ExampleText=?,QualifierTypeID=?,NewQualifierID=?,WhenModified=? where QualifierID=?'))
    {
     if($stmt->bind_param('ssiisi', $QualifierText,$ExampleText,$QualifierTypeID,$NewQualifierID,$WhenModified,$QualifierID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $QualifierID=$record['QualifierID'];
        $QualifierText=$record['QualifierText']; 
        $ExampleText=$record['ExampleText']; 
        $QualifierTypeID=$record['QualifierTypeID']; 
        $NewQualifierID=$record['NewQualifierID']; 
        $WhenModified=$record['WhenModified']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;
      
      
      
//QualifierGroup    
//QualifierType    
    
    
   default: break;      
  }
  


  
  
  
  
  
  $db->close();
  return $inserts;
 }

 
 
 
}

?>
