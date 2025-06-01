<?php
include_once("mysqlClass.php");

class padbapi
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
  $this->tableslist=array('MeasurementGroup','MetaData','MetaUOMCodeAssignment','MetaUOMCodes','PartAttributeAssignment','PartAttributeStyle','PartAttributes','PartTypeStyle','Style','ValidValueAssignment','ValidValues'); 
  $this->tablekeyslist=array('MeasurementGroup'=>'MeasurementGroupID','MetaData'=>'MetaID','MetaUOMCodeAssignment'=>'MetaUOMCodeAssignmentID','MetaUOMCodes'=>'','PartAttributeAssignment'=>'','PartAttributeStyle'=>'','PartAttributes'=>'PAID','PartTypeStyle'=>'','Style'=>'','ValidValueAssignment'=>'ValidValueAssignmentID','ValidValues'=>'ValidValueID');
  
  $this->pagelimit=0;
  $this->totalcalls=0;
  $this->activetoken=false;
  $this->tokenrefreshcount=0;
  $this->insertcount=0;
  $this->updatecount=0;
  $this->deletecount=0;
  $this->deleteorphancount=0;
  
  $this->localdbname=$_localdbname;  // default to the hard-coded dbname from the class file (prob "padb")
  if(!$_localdbname)
  { // no secific vsersion was passed in. Consult pim database for the name
    // of the active padb database. It will be something like padb20210827
      
   $db = new mysql; $db->connect();
   if($stmt=$db->conn->prepare("select configvalue from config where configname='padbAPIcacheDatabase'"))
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
  if($stmt=$db->conn->prepare('update Version set PAdbPublication=?'))
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
   $url='https://'.$database.'.autocarevip.com/api/v4.0/padb/'.$table.'?CultureId='.$cultureid.$sincedateclause;
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
  if($keyfieldname==''){return $existingids;}
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

//----------------------------------------------------------
  
  switch($tablename)
  {
 
    
   case 'MeasurementGroup':
       
    if($stmt=$db->conn->prepare('insert into MeasurementGroup values(?,?)'))
    {
     if($stmt->bind_param('is', $MeasurementGroupID, $MeasurementGroupName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $MeasurementGroupID=$record['MeasurementGroupID'];
        $MeasurementGroupName=$record['MeasurementGroupName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from MeasurementGroup where MeasurementGroupID=?'))
    {
     if($stmt->bind_param('i', $MeasurementGroupID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $MeasurementGroupID=$record['MeasurementGroupID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $MeasurementGroupID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update MeasurementGroup set MeasurementGroupName=? where MeasurementGroupID=?'))
    {
     if($stmt->bind_param('si', $MeasurementGroupName,$MeasurementGroupID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $MeasurementGroupID=$record['MeasurementGroupID'];
        $MeasurementGroupName=$record['MeasurementGroupName']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;

    
//----------------- MetaData
   case 'MetaData':
       
    if($stmt=$db->conn->prepare('insert into MetaData values(?,?,?,?,?,?,?)'))
    {
     if($stmt->bind_param('issssii', $MetaID, $MetaName,$MetaDescr,$MetaFormat,$DataType,$MinLength,$MaxLength))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $MetaID=$record['MetaID'];
        $MetaName=$record['MetaName'];
        $MetaDescr=$record['MetaDescr'];
        $MetaFormat=$record['MetaFormat'];
        $DataType=$record['DataType'];
        $MinLength=$record['MinLength'];
        $MaxLength=$record['MaxLength'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from MetaData where MetaID=?'))
    {
     if($stmt->bind_param('i', $MetaID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $MetaID=$record['MetaID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $MetaID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update MetaData set MetaName=?,MetaDescr=?,MetaFormat=?,DataType=?,MinLength=?,MaxLength=? where MetaID=?'))
    {
     if($stmt->bind_param('ssssiii', $MetaName,$MetaDescr,$MetaFormat,$DataType,$MinLength,$MaxLength,$MetaID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $MetaID=$record['MetaID'];
        $MetaName=$record['MetaName']; 
        $MetaDescr=$record['MetaDescr']; 
        $MetaFormat=$record['MetaFormat']; 
        $DataType=$record['DataType']; 
        $MinLength=$record['MinLength']; 
        $MaxLength=$record['MaxLength']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;

    
//----------------- MetaUOMCodeAssignment

   case 'MetaUOMCodeAssignment':
       
    if($stmt=$db->conn->prepare('insert into MetaUOMCodeAssignment values(?,?,?)'))
    {
     if($stmt->bind_param('iii', $MetaUOMCodeAssignmentID, $PAPTID, $MetaUOMID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $MetaUOMCodeAssignmentID=$record['MetaUOMCodeAssignmentID'];
        $PAPTID=$record['PAPTID'];
        $MetaUOMID=$record['MetaUOMID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from MetaUOMCodeAssignment where MetaUOMCodeAssignmentID=?'))
    {
     if($stmt->bind_param('i', $MetaUOMCodeAssignmentID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $MetaUOMCodeAssignmentID=$record['MetaUOMCodeAssignmentID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $MetaUOMCodeAssignmentID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update MetaUOMCodeAssignment set PAPTID=?,MetaUOMID=? where MetaUOMCodeAssignmentID=?'))
    {
     if($stmt->bind_param('iii', $PAPTID, $MetaUOMID,$MetaUOMCodeAssignmentID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $MetaUOMCodeAssignmentID=$record['MetaUOMCodeAssignmentID'];
        $PAPTID=$record['PAPTID']; 
        $MetaUOMID=$record['MetaUOMID']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;

    
//----------------- MetaUOMCodes -- no key

   case 'MetaUOMCodes':
       
    $localhashlist=array(); // compile a hashlist of the existing local table's recs
    if($stmt=$db->conn->prepare('select MetaUOMID,UOMCode,UOMDescription,UOMLabel,MeasurementGroupID,hash from MetaUOMCodes'))
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
        
    if($stmt=$db->conn->prepare('insert into MetaUOMCodes values(?,?,?,?,?,?)'))
    {
     if($stmt->bind_param('isssis', $MetaUOMID,$UOMCode,$UOMDescription,$UOMLabel,$MeasurementGroupID,$hash))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       $hash=md5($record['MetaUOMID'].$record['UOMCode'].$record['UOMDescription'].$record['UOMLabel'].$record['MeasurementGroupID']);
       $MetaUOMID=$record['MetaUOMID'];
       $UOMCode=$record['UOMCode'];
       $UOMDescription=$record['UOMDescription'];
       $UOMLabel=$record['UOMLabel'];
       $MeasurementGroupID=$record['MeasurementGroupID'];
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
      $hash=md5($record['MetaUOMID'].$record['UOMCode'].$record['UOMDescription'].$record['UOMLabel'].$record['MeasurementGroupID']);
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
        
     if($stmt=$db->conn->prepare('delete from MetaUOMCodes where `hash`=?'))
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
    
//----------------- PartAttributeAssignment -- no key

   case 'PartAttributeAssignment':
       
    $localhashlist=array(); // compile a hashlist of the existing local table's recs
    if($stmt=$db->conn->prepare('select PAPTID,PartTerminologyID,PAID,MetaID,hash from PartAttributeAssignment'))
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
        
    if($stmt=$db->conn->prepare('insert into PartAttributeAssignment values(?,?,?,?,?)'))
    {
     if($stmt->bind_param('iiiis', $PAPTID,$PartTerminologyID,$PAID,$MetaID,$hash))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       $hash=md5($record['PAPTID'].$record['PartTerminologyID'].$record['PAID'].$record['MetaID']);
       $PAPTID=$record['PAPTID'];
       $PartTerminologyID=$record['PartTerminologyID'];
       $PAID=$record['PAID'];
       $MetaID=$record['MetaID'];
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
      $hash=md5($record['PAPTID'].$record['PartTerminologyID'].$record['PAID'].$record['MetaID']);
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
        
     if($stmt=$db->conn->prepare('delete from PartAttributeAssignment where `hash`=?'))
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
    
//----------------- PartAttributeStyle -- no key    

   case 'PartAttributeStyle':
       
    $localhashlist=array(); // compile a hashlist of the existing local table's recs
    if($stmt=$db->conn->prepare('select StyleID,PAPTID,hash from PartAttributeStyle'))
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
        
    if($stmt=$db->conn->prepare('insert into PartAttributeStyle values(?,?,?)'))
    {
     if($stmt->bind_param('iis', $StyleID,$PAPTID,$hash))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       $hash=md5($record['StyleID'].$record['PAPTID']);
       $StyleID=$record['StyleID'];
       $PAPTID=$record['PAPTID'];
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
      $hash=md5($record['StyleID'].$record['PAPTID']);
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
        
     if($stmt=$db->conn->prepare('delete from PartAttributeStyle where `hash`=?'))
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
    
    
//----------------- PartAttributes
   case 'PartAttributes':
       
    if($stmt=$db->conn->prepare('insert into PartAttributes values(?,?,?)'))
    {
     if($stmt->bind_param('iss', $PAID,$PAName,$PADescr))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $PAID=$record['PAID'];
        $PAName=$record['PAName'];
        $PADescr=$record['PADescr'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from PartAttributes where PAID=?'))
    {
     if($stmt->bind_param('i', $PAID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $PAID=$record['PAID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $PAID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update PartAttributes set PAName=?,PADescr=? where PAID=?'))
    {
     if($stmt->bind_param('ssi', $PAName,$PADescr,$PAID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $PAID=$record['PAID'];
        $PAName=$record['PAName']; 
        $PADescr=$record['PADescr']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;
    
//----------------- PartTypeStyle -- no key

    case 'PartTypeStyle':
       
    $localhashlist=array(); // compile a hashlist of the existing local table's recs
    if($stmt=$db->conn->prepare('select StyleID,PartTerminologyID,hash from PartTypeStyle'))
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
        
    if($stmt=$db->conn->prepare('insert into PartTypeStyle values(?,?,?)'))
    {
     if($stmt->bind_param('iis', $StyleID,$PartTerminologyID,$hash))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       $hash=md5($record['StyleID'].$record['PartTerminologyID']);
       $StyleID=$record['StyleID'];
       $PartTerminologyID=$record['PartTerminologyID'];
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
      $hash=md5($record['StyleID'].$record['PartTerminologyID']);
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
        
     if($stmt=$db->conn->prepare('delete from PartTypeStyle where `hash`=?'))
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

//----------------- Style -- no key

    case 'Style':
       
    $localhashlist=array(); // compile a hashlist of the existing local table's recs
    if($stmt=$db->conn->prepare('select StyleID,StyleName,hash from Style'))
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
        
    if($stmt=$db->conn->prepare('insert into Style values(?,?,?)'))
    {
     if($stmt->bind_param('iss', $StyleID,$StyleName,$hash))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       $hash=md5($record['StyleID'].$record['StyleName']);
       $StyleID=$record['StyleID'];
       $StyleName=$record['StyleName'];
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
      $hash=md5($record['StyleID'].$record['StyleName']);
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
        
     if($stmt=$db->conn->prepare('delete from `Style` where `hash`=?'))
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
    
    
    
//----------------- ValidValueAssignment

    case 'ValidValueAssignment':
       
    if($stmt=$db->conn->prepare('insert into ValidValueAssignment values(?,?,?)'))
    {
     if($stmt->bind_param('iii', $ValidValueAssignmentID, $PAPTID,$ValidValueID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $ValidValueAssignmentID=$record['ValidValueAssignmentID'];
        $PAPTID=$record['PAPTID'];
        $ValidValueID=$record['ValidValueID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from ValidValueAssignment where ValidValueAssignmentID=?'))
    {
     if($stmt->bind_param('i', $ValidValueAssignmentID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $ValidValueAssignmentID=$record['ValidValueAssignmentID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $ValidValueAssignmentID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update ValidValueAssignment set PAPTID=?,ValidValueID=? where ValidValueAssignmentID=?'))
    {
     if($stmt->bind_param('iii', $PAPTID,$ValidValueID,$ValidValueAssignmentID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $ValidValueAssignmentID=$record['ValidValueAssignmentID'];
        $PAPTID=$record['PAPTID']; 
        $ValidValueID=$record['ValidValueID']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;
        
//----------------- ValidValues
    
    case 'ValidValues':
       
    if($stmt=$db->conn->prepare('insert into ValidValues values(?,?)'))
    {
     if($stmt->bind_param('is', $ValidValueID, $ValidValue))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $ValidValueID=$record['ValidValueID'];
        $ValidValue=$record['ValidValue'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from ValidValues where ValidValueID=?'))
    {
     if($stmt->bind_param('i', $ValidValueID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// record found in local tables - do the delete
         $ValidValueID=$record['ValidValueID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $ValidValueID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update ValidValues set ValidValue=? where ValidValueID=?'))
    {
     if($stmt->bind_param('si', $ValidValue,$ValidValueID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $ValidValueID=$record['ValidValueID'];
        $ValidValue=$record['ValidValue']; 
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
