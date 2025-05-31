<?php
include_once("mysqlClass.php");

class pcdbapi
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
  $this->tableslist=array('ACESCodedValues','Alias','Categories','PartPosition','Parts','PIESField','PIESReferenceFieldCode','Positions','Subcategories','Use','PartsDescription','PartsRelationship','PartsSupersession','PartsToAlias','PartsToUse','PIESCode','PIESEXPICode');

  $this->tablekeyslist=array('ACESCodedValues'=>'','Alias'=>'AliasID','Categories'=>'CategoryID','PartPosition'=>'PartPositionID','Parts'=>'PartTerminologyID','PIESField'=>'FieldID','PIESReferenceFieldCode'=>'ReferenceFieldCodeID','PIESSegment'=>'SegmentID','Positions'=>'PositionID','Subcategories'=>'SubCategoryID','Use'=>'UseID','PartsDescription'=>'PartsDescriptionID','PartsRelationship'=>'','PartsSupersession'=>'','PartsToAlias'=>'','PartsToUse'=>'','PIESCode'=>'CodeValueID','PIESEXPICode'=>'EXPICodeID');   
 
  
  $this->pagelimit=0;
  $this->totalcalls=0;
  $this->activetoken=false;
  $this->tokenrefreshcount=0;
  $this->insertcount=0;
  $this->updatecount=0;
  $this->deletecount=0;
  $this->deleteorphancount=0;
  
  $this->localdbname=$_localdbname;  // default to the hard-coded dbname from the class file (prob "pcdb")
  if(!$_localdbname)
  { // no secific vsersion was passed in. Consult pim database for the name
    // of the active pcdb database. It will be something like pcdbcache
      
   $db = new mysql; $db->connect();
   if($stmt=$db->conn->prepare("select configvalue from config where configname='pcdbAPIcacheDatabase'"))
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
 
 function setVersionDate($date)
 {
  $returnval=false;
  $db = new mysql; $db->dbname=$this->localdbname; $db->connect();
  if($stmt=$db->conn->prepare('update Version set VersionDate=?'))
  {
   if($stmt->bind_param('s', $date))
   {   
    if($stmt->execute())
    {
     $returnval=true;
    }
   }
  }
  $db->close();
  return $returnval;
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
  $hashlist=array();
  
  
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
  
//----------------------------------------------------------
  
  switch($tablename)
  {
      
//-------------- ACESCodedValues --------- has no key
       
   case 'ACESCodedValues':
       
    $localhashlist=array(); // compile a hashlist of the existing local table's recs
    if($stmt=$db->conn->prepare('select `Element`,`Attribute`,CodedValue,CodeDescription,hash from ACESCodedValues'))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      while($row = $db->result->fetch_assoc())
      {
       $localhashlist[$row['hash']]=1;
      }
     }
    }
        
    if($stmt=$db->conn->prepare('insert into ACESCodedValues values(?,?,?,?,?)'))
    {
     if($stmt->bind_param('sssss', $Element,$Attribute,$CodedValue,$CodeDescription,$hash))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       $hash=md5($record['Element'].$record['Attribute'].$record['CodedValue'].$record['CodeDescription']);
       $Element=$record['Element'];
       $Attribute=$record['Attribute'];
       $CodedValue=$record['CodedValue'];
       $CodeDescription=$record['CodeDescription'];
       if(!array_key_exists($hash,$localhashlist))
       {
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }

    
    if($deletelocalorphans)
    {   // find hash diffs that imply local orphans to delete
     
     $remotehashlist=array();  // compile a hashlist of remote recs
     foreach($records as $record)
     {
      if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
      $hash=md5($record['Element'].$record['Attribute'].$record['CodedValue'].$record['CodeDescription']);
      $remotehashlist[$hash]= 1;
     }
    
     $localhashestodelete=array();
     foreach($localhashlist as $localhash=>$trash)
     {
      if(!array_key_exists($localhash,$remotehashlist))
      {
       $localhashestodelete[]=$localhash;
      }  
     }
        
     if($stmt=$db->conn->prepare('delete from ACESCodedValues where `hash`=?'))
     {
      if($stmt->bind_param('s', $hash))
      {
       foreach($localhashestodelete as $hashtodlete)
       {
        $hash=$hashtodlete;
        if($stmt->execute()){$this->deleteorphancount++; $this->deletecount++;}
       }
      }
     }
    }
        
    break;    
      
      
      

   case 'Alias':
       
    if($stmt=$db->conn->prepare('insert into Alias values(?,?)'))
    {
     if($stmt->bind_param('is', $AliasID, $AliasName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $AliasID=$record['AliasID'];
        $AliasName=$record['AliasName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Alias where AliasID=?'))
    {
     if($stmt->bind_param('i', $AliasID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $AliasID=$record['AliasID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $AliasID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Alias set AliasName=? where AliasID=?'))
    {
     if($stmt->bind_param('si', $AliasName,$AliasID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $AliasID=$record['AliasID'];
        $AliasName=$record['AliasName']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;

   case 'Categories':
       
    if($stmt=$db->conn->prepare('insert into Categories values(?,?)'))
    {
     if($stmt->bind_param('is', $CategoryID, $CategoryName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $CategoryID=$record['CategoryID'];
        $CategoryName=$record['CategoryName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Categories where CategoryID=?'))
    {
     if($stmt->bind_param('i', $CategoryID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $CategoryID=$record['CategoryID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $CategoryID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Categories set CategoryName=? where CategoryID=?'))
    {
     if($stmt->bind_param('si', $CategoryName,$CategoryID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $CategoryID=$record['CategoryID'];
        $CategoryName=$record['CategoryName']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;

       
//------------------- PartPosition ----------------
  
    case 'PartPosition':
       
    if($stmt=$db->conn->prepare('insert into PartPosition values(?,?,?,?)'))
    {
     if($stmt->bind_param('iiis', $PartPositionID, $PartTerminologyID, $PositionID, $RevDate))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $PartPositionID=$record['PartPositionID'];
        $PartTerminologyID=$record['PartTerminologyID'];
        $PositionID=$record['PositionID'];
        $RevDate=$record['RevDate'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from PartPosition where PartPositionID=?'))
    {
     if($stmt->bind_param('i', $PartPositionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $PartPositionID=$record['PartPositionID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $PartPositionID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update PartPosition set PartTerminologyID=?,PositionID=?,RevDate=? where PartPositionID=?'))
    {
     if($stmt->bind_param('sssi', $PartTerminologyID,$PositionID,$RevDate,$PartPositionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $PartPositionID=$record['PartPositionID'];
        $PartTerminologyID=$record['PartTerminologyID']; 
        $PositionID=$record['PositionID']; 
        $RevDate=$record['RevDate']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;

    
//-------------- Parts ---------------------

   case 'Parts':
       
    if($stmt=$db->conn->prepare('insert into Parts values(?,?,?,?)'))
    {
     if($stmt->bind_param('isis', $PartTerminologyID, $PartTerminologyName,$PartsDescriptionID,$RevDate))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $PartTerminologyID=$record['PartTerminologyID'];
        $PartTerminologyName=$record['PartTerminologyName'];
        $PartsDescriptionID=$record['PartsDescriptionID'];
        $RevDate=$record['RevDate'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Parts where PartTerminologyID=?'))
    {
     if($stmt->bind_param('i', $PartTerminologyID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $PartTerminologyID=$record['PartTerminologyID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $PartTerminologyID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Parts set PartTerminologyName=?,PartsDescriptionID=?,RevDate=? where PartTerminologyID=?'))
    {
     if($stmt->bind_param('sisi', $PartTerminologyName,$PartsDescriptionID,$RevDate,$PartTerminologyID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $PartTerminologyID=$record['PartTerminologyID'];
        $PartTerminologyName=$record['PartTerminologyName']; 
        $PartsDescriptionID=$record['PartsDescriptionID']; 
        $RevDate=$record['RevDate']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;

//------------ PartsDescription ---------------------
   case 'PartsDescription':
       
    if($stmt=$db->conn->prepare('insert into PartsDescription values(?,?)'))
    {
     if($stmt->bind_param('is', $PartsDescriptionID, $PartsDescription))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $PartsDescriptionID=$record['PartsDescriptionID'];
        $PartsDescription=$record['PartsDescription'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from PartsDescription where PartsDescriptionID=?'))
    {
     if($stmt->bind_param('i', $PartsDescriptionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $PartsDescriptionID=$record['PartsDescriptionID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $PartsDescriptionID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update PartsDescription set PartsDescription=? where PartsDescriptionID=?'))
    {
     if($stmt->bind_param('si', $PartsDescription,$PartsDescriptionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $PartsDescriptionID=$record['PartsDescriptionID'];
        $PartsDescription=$record['PartsDescription']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;
    
    
    
    
//-------------- PartsRelationship -------- has no primary key

   case 'PartsRelationship':
       
    $localhashlist=array(); // compile a hashlist of the existing local table's recs
    if($stmt=$db->conn->prepare('select PartTerminologyID,RelatedPartTerminologyID,`hash` from PartsRelationship'))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      while($row = $db->result->fetch_assoc())
      {
       $localhashlist[$row['hash']]=1;
      }
     }
    }
        
    if($stmt=$db->conn->prepare('insert into PartsRelationship values(?,?,?)'))
    {
     if($stmt->bind_param('iis', $PartTerminologyID,$RelatedPartTerminologyID,$hash))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       $hash=md5($record['PartTerminologyID'].$record['RelatedPartTerminologyID']);
       $PartTerminologyID=$record['PartTerminologyID']; $RelatedPartTerminologyID=$record['RelatedPartTerminologyID'];
       if(!array_key_exists($hash,$localhashlist))
       {
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }

    
    if($deletelocalorphans)
    {   // find hash diffs that imply local orphans to delete
     
     $remotehashlist=array();  // compile a hashlist of remote recs
     foreach($records as $record)
     {
      if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
      $hash=md5($record['PartTerminologyID'].$record['RelatedPartTerminologyID']);
      $remotehashlist[$hash]= 1;
     }
    
     $localhashestodelete=array();
     foreach($localhashlist as $localhash=>$trash)
     {
      if(!array_key_exists($localhash,$remotehashlist))
      {
       $localhashestodelete[]=$localhash;
      }  
     }
        
     if($stmt=$db->conn->prepare('delete from PartsRelationship where `hash`=?'))
     {
      if($stmt->bind_param('s', $hash))
      {
       foreach($localhashestodelete as $hashtodlete)
       {
        $hash=$hashtodlete;
        if($stmt->execute()){$this->deleteorphancount++; $this->deletecount++;}
       }
      }
     }
    }
        
    break;    
    
    
//------------------ PartsSupersession ------------ has no primary key
   case 'PartsSupersession':
       
    $localhashlist=array(); // compile a hashlist of the existing local table's recs
    if($stmt=$db->conn->prepare('select OldPartTerminologyID,OldPartTerminologyName,NewPartTerminologyID,NewPartTerminologyName,hash from PartsSupersession'))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      while($row = $db->result->fetch_assoc())
      {
       $localhashlist[$row['hash']]=1;
      }
     }
    }
        
    if($stmt=$db->conn->prepare('insert into PartsSupersession values(?,?,?,?,?,?)'))
    {
     if($stmt->bind_param('isisss', $OldPartTerminologyID,$OldPartTerminologyName,$NewPartTerminologyID,$NewPartTerminologyName,$RevDate,$hash))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       $OldPartTerminologyID=$record['OldPartTerminologyID'];        
       $OldPartTerminologyName=$record['OldPartTerminologyName'];
       $NewPartTerminologyID=$record['NewPartTerminologyID'];        
       $NewPartTerminologyName=$record['NewPartTerminologyName'];
       $RevDate=$record['RevDate'];
       $hash=md5($record['OldPartTerminologyID'].$record['OldPartTerminologyName'].$record['NewPartTerminologyID'].$record['NewPartTerminologyName']);
       if(!array_key_exists($hash,$localhashlist))
       {
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }

    
    if($deletelocalorphans)
    {   // find hash diffs that imply local orphans to delete
     
     $remotehashlist=array();  // compile a hashlist of remote recs
     foreach($records as $record)
     {
      if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
      $hash=md5($record['OldPartTerminologyID'].$record['OldPartTerminologyName'].$record['NewPartTerminologyID'].$record['NewPartTerminologyName']);
      $remotehashlist[$hash]= 1;
     }
    
     $localhashestodelete=array();
     foreach($localhashlist as $localhash=>$trash)
     {
      if(!array_key_exists($localhash,$remotehashlist))
      {
       $localhashestodelete[]=$localhash;
      }  
     }
        
     if($stmt=$db->conn->prepare('delete from PartsSupersession where `hash`=?'))
     {
      if($stmt->bind_param('s', $hash))
      {
       foreach($localhashestodelete as $hashtodlete)
       {
        $hash=$hashtodlete;
        if($stmt->execute()){$this->deleteorphancount++; $this->deletecount++;}
       }
      }
     }
    }
    break;    
    
    
//--------------------   PartsToAlias -------------- has no primary key
   case 'PartsToAlias':
       
    $localhashlist=array(); // compile a hashlist of the existing local table's recs
    if($stmt=$db->conn->prepare('select PartTerminologyID,AliasID,hash from PartsToAlias'))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      while($row = $db->result->fetch_assoc())
      {
       $localhashlist[$row['hash']]=1;
      }
     }
    }
        
    if($stmt=$db->conn->prepare('insert into PartsToAlias values(?,?,?)'))
    {
     if($stmt->bind_param('iis', $PartTerminologyID,$AliasID,$hash))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       $hash=md5($record['PartTerminologyID'].$record['AliasID']);
       $PartTerminologyID=$record['PartTerminologyID'];
       $AliasID=$record['AliasID'];
       if(!array_key_exists($hash,$localhashlist))
       {
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }

    
    if($deletelocalorphans)
    {   // find hash diffs that imply local orphans to delete
     
     $remotehashlist=array();  // compile a hashlist of remote recs
     foreach($records as $record)
     {
      if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
      $hash=md5($record['PartTerminologyID'].$record['AliasID']);
      $remotehashlist[$hash]= 1;
     }
    
     $localhashestodelete=array();
     foreach($localhashlist as $localhash=>$trash)
     {
      if(!array_key_exists($localhash,$remotehashlist))
      {
       $localhashestodelete[]=$localhash;
      }  
     }
        
     if($stmt=$db->conn->prepare('delete from PartsToAlias where `hash`=?'))
     {
      if($stmt->bind_param('s', $hash))
      {
       foreach($localhashestodelete as $hashtodlete)
       {
        $hash=$hashtodlete;
        if($stmt->execute()){$this->deleteorphancount++; $this->deletecount++;}
       }
      }
     }
    }
    break;    
    
    
    
//--------------- PartsToUse ------------ has no primary key
   case 'PartsToUse':
       
    $localhashlist=array(); // compile a hashlist of the existing local table's recs
    if($stmt=$db->conn->prepare('select PartTerminologyID,UseID,hash from PartsToUse'))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      while($row = $db->result->fetch_assoc())
      {
       $localhashlist[$row['hash']]=1;
      }
     }
    }
        
    if($stmt=$db->conn->prepare('insert into PartsToUse values(?,?,?)'))
    {
     if($stmt->bind_param('iis', $PartTerminologyID, $UseID ,$hash))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       $hash=md5($record['PartTerminologyID'].$record['UseID']);
       $PartTerminologyID=$record['PartTerminologyID'];
       $UseID=$record['UseID'];
       if(!array_key_exists($hash,$localhashlist))
       {
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }

    
    if($deletelocalorphans)
    {   // find hash diffs that imply local orphans to delete
     $remotehashlist=array();  // compile a hashlist of remote recs
     foreach($records as $record)
     {
      if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
      $hash=md5($record['PartTerminologyID'].$record['UseID']);
      $remotehashlist[$hash]= 1;
     }
    
     $localhashestodelete=array();
     foreach($localhashlist as $localhash=>$trash)
     {
      if(!array_key_exists($localhash,$remotehashlist))
      {
       $localhashestodelete[]=$localhash;
      }  
     }
        
     if($stmt=$db->conn->prepare('delete from PartsToUse where `hash`=?'))
     {
      if($stmt->bind_param('s', $hash))
      {
       foreach($localhashestodelete as $hashtodlete)
       {
        $hash=$hashtodlete;
        if($stmt->execute()){$this->deleteorphancount++; $this->deletecount++;}
       }
      }
     }
    }
    
    break;    

    
//----------------- PIESCode ----------------
   case 'PIESCode':
       
    if($stmt=$db->conn->prepare('insert into PIESCode values(?,?,?,?,?,?)'))
    {
     if($stmt->bind_param('isssss', $CodeValueID, $CodeValue,$CodeFormat,$FieldFormat,$CodeDescription,$Source))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $CodeValueID=$record['CodeValueID'];
        $CodeValue=$record['CodeValue'];
        $CodeFormat=$record['CodeFormat'];
        $FieldFormat=$record['FieldFormat'];
        $CodeDescription=$record['CodeDescription'];
        $Source=$record['Source'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from PIESCode where CodeValueID=?'))
    {
     if($stmt->bind_param('i', $CodeValueID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $CodeValueID=$record['CodeValueID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $CodeValueID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update PIESCode set CodeValue=?,CodeFormat=?,FieldFormat=?,CodeDescription=?,Source=? where CodeValueID=?'))
    {
     if($stmt->bind_param('sssssi', $CodeValue,$CodeFormat,$FieldFormat,$CodeDescription,$Source,$CodeValueID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $CodeValueID=$record['CodeValueID'];
        $CodeValue=$record['CodeValue']; 
        $CodeFormat=$record['CodeFormat']; 
        $FieldFormat=$record['FieldFormat']; 
        $CodeDescription=$record['CodeDescription']; 
        $Source=$record['Source']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }              
    break;


//---------------------  PIESExpiCode  -------------

   case 'PIESEXPICode':
       
    if($stmt=$db->conn->prepare('insert into PIESEXPICode values(?,?,?,?)'))
    {
     if($stmt->bind_param('issi', $EXPICodeID, $EXPICode,$EXPICodeDescription,$EXPIGroupID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $EXPICodeID=$record['EXPICodeID'];
        $EXPICode=$record['EXPICode'];
        $EXPICodeDescription=$record['EXPICodeDescription'];
        $EXPIGroupID=$record['EXPIGroupID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from PIESEXPICode where EXPICodeID=?'))
    {
     if($stmt->bind_param('i', $EXPICodeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $EXPICodeID=$record['EXPICodeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $EXPICodeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update PIESEXPICode set EXPICode=?,EXPICodeDescription=?,EXPIGroupID=? where EXPICodeID=?'))
    {
     if($stmt->bind_param('ssii', $EXPICode,$EXPICodeDescription,$EXPIGroupID,$EXPICodeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $EXPICodeID=$record['EXPICodeID'];
        $EXPICode=$record['EXPICode']; 
        $EXPICodeDescription=$record['EXPICodeDescription']; 
        $EXPIGroupID=$record['EXPIGroupID']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;
    
    
    
    
    
    
//------    PIESEXPIGroup -- Do we care to implement this table?




    
//--------   PIESField --------

   case 'PIESField':
       
    if($stmt=$db->conn->prepare('insert into PIESField values(?,?,?,?)'))
    {
     if($stmt->bind_param('issi', $FieldID,$FieldName,$ReferenceFieldNumber,$SegmentID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $FieldID=$record['FieldID'];
        $FieldName=$record['FieldName'];
        $ReferenceFieldNumber=$record['ReferenceFieldNumber'];
        $SegmentID=$record['SegmentID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from PIESField where FieldID=?'))
    {
     if($stmt->bind_param('i', $FieldID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $FieldID=$record['FieldID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $FieldID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update PIESField set FieldName=?,ReferenceFieldNumber=?,SegmentID=? where FieldID=?'))
    {
     if($stmt->bind_param('ssii', $FieldName,$ReferenceFieldNumber,$SegmentID,$FieldID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $FieldID=$record['FieldID'];
        $FieldName=$record['FieldName']; 
        $ReferenceFieldNumber=$record['ReferenceFieldNumber']; 
        $SegmentID=$record['SegmentID']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;
    
 //----------------- PIESReferenceFieldCode
    
    
   case 'PIESReferenceFieldCode':
       
    if($stmt=$db->conn->prepare('insert into PIESReferenceFieldCode values(?,?,?,?,?)'))
    {
     if($stmt->bind_param('iiiis', $ReferenceFieldCodeID, $FieldID, $CodeValueID, $EXPICodeID, $ReferenceNotes))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $ReferenceFieldCodeID=$record['ReferenceFieldCodeID'];
        $FieldID=$record['FieldID'];
        $CodeValueID=$record['CodeValueID'];
        $EXPICodeID=$record['EXPICodeID'];
        $ReferenceNotes=$record['ReferenceNotes'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from PIESReferenceFieldCode where ReferenceFieldCodeID=?'))
    {
     if($stmt->bind_param('i', $ReferenceFieldCodeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $ReferenceFieldCodeID=$record['ReferenceFieldCodeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $ReferenceFieldCodeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }

    if($stmt=$db->conn->prepare('update PIESReferenceFieldCode set FieldID=?,CodeValueID=?,EXPICodeID=?,ReferenceNotes=? where ReferenceFieldCodeID=?'))
    {
     if($stmt->bind_param('iiisi', $FieldID,$CodeValueID,$EXPICodeID,$ReferenceNotes,$ReferenceFieldCodeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $ReferenceFieldCodeID=$record['ReferenceFieldCodeID'];
        $FieldID=$record['FieldID']; 
        $CodeValueID=$record['CodeValueID']; 
        $EXPICodeID=$record['EXPICodeID']; 
        $ReferenceNotes=$record['ReferenceNotes']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;
    
//-----------  PIESSegment ------------

   case 'PIESSegment':
       
    if($stmt=$db->conn->prepare('insert into PIESSegment values(?,?,?,?)'))
    {
     if($stmt->bind_param('isss', $SegmentID, $SegmentAbb,$SegmentName,$SegmentDescription))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $SegmentID=$record['SegmentID'];
        $SegmentAbb=$record['SegmentAbb'];
        $SegmentName=$record['SegmentName'];
        $SegmentDescription=$record['SegmentDescription'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from PIESSegment where SegmentId=?'))
    {
     if($stmt->bind_param('i', $SegmentId))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $SegmentID=$record['SegmentID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $SegmentID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update PIESSegment set SegmentAbb=?,SegmentName=?,SegmentDescription=? where SegmentId=?'))
    {
     if($stmt->bind_param('sssi', $SegmentAbb,$SegmentName,$SegmentDescription,$SegmentID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $SegmentID=$record['SegmentID'];
        $SegmentAbb=$record['SegmentAbb']; 
        $SegmentName=$record['SegmentName']; 
        $SegmentDescription=$record['SegmentDescription']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;

//---------------- Positions ---------------
    
   case 'Positions':
       
    if($stmt=$db->conn->prepare('insert into Positions values(?,?)'))
    {
     if($stmt->bind_param('is', $PositionID, $Position))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $PositionID=$record['PositionID'];
        $Position=$record['Position'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Positions where PositionID=?'))
    {
     if($stmt->bind_param('i', $PositionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $PositionID=$record['PositionID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $PositionID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Positions set Position=? where PositionID=?'))
    {
     if($stmt->bind_param('si', $Position,$PositionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $PositionID=$record['PositionID'];
        $Position=$record['Position']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;
    
//----------- Subcategories -----------------
   case 'Subcategories':
       
    if($stmt=$db->conn->prepare('insert into Subcategories values(?,?)'))
    {
     if($stmt->bind_param('is', $SubCategoryID, $SubCategoryName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $SubCategoryID=$record['SubCategoryID'];
        $SubCategoryName=$record['SubCategoryName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Subcategories where SubCategoryID=?'))
    {
     if($stmt->bind_param('i', $SubCategoryID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $SubCategoryID=$record['SubCategoryID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $SubCategoryID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Subcategories set SubCategoryName=? where SubCategoryID=?'))
    {
     if($stmt->bind_param('si', $SubCategoryName,$SubCategoryID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $SubCategoryID=$record['SubCategoryID'];
        $SubCategoryName=$record['SubCategoryName']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;

//------------ Use ----------------
    
   case 'Use':
       
    if($stmt=$db->conn->prepare('insert into `Use` values(?,?)'))
    {
     if($stmt->bind_param('is', $UseID, $UseDescription))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $UseID=$record['UseID'];
        $UseDescription=$record['UseDescription'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from `Use` where UseID=?'))
    {
     if($stmt->bind_param('i', $UseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $UseID=$record['UseID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $UseID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update `Use` set UseDescription=? where UseID=?'))
    {
     if($stmt->bind_param('si', $UseDescription,$UseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $UseID=$record['UseID'];
        $UseDescription=$record['UseDescription']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;
    
    
    
    
    
    
    
   default: break;      
  }
  


  
  
  
  
  
  $db->close();
  return $inserts;
 }

 
 
 
}

?>
