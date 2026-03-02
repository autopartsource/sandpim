<?php
/*
 *              Changes to accomodate VCdb API version 2 upgrade
 * FuelSystemControlType table: Add CultureID and fix insert/update SQL statements to match
 *  alter table FuelSystemControlType add CultureID varchar(255) not null;
 * 
 * FuelSystemDesign table: Add CultureID and fix insert/update SQL statements to match
 *  alter table FuelSystemDesign add CultureID varchar(255) not null;
 * 
 * FuelType table: Add CultureID and fix insert/update SQL statements to match
 *  alter table FuelType add CultureID varchar(255) not null;
 * 
 * IgnitionSystemType table: Add CultureID and fix insert/update SQL statements to match
 *  alter table IgnitionSystemType add CultureID varchar(255) not null;
 * 
 * Make table: Add CultureID and fix insert/update SQL statements to match
 *  alter table Make add CultureID varchar(255) not null;
 * 
 * Mfr table: Add CultureID and fix insert/update SQL statements to match
 *  alter table Mfr add CultureID varchar(255) not null;
 * 
 * MfrBodyCode table: Add CultureID and fix insert/update SQL statements to match
 *  alter table MfrBodyCode add CultureID varchar(255) not null;
 * 
 * Model table: Add CultureID and fix insert/update SQL statements to match
 *  alter table Model add CultureID varchar(255) not null;
 * 
 * Model table: Add CultureID and fix insert/update SQL statements to match
 *  alter table Model add CultureID varchar(255) not null;
 * 
 * PowerOutput table: Add CultureID and fix insert/update SQL statements to match
 *  alter table PowerOutput add CultureID varchar(255) not null;
 * 
 * PublicationStage table: Add CultureID and fix insert/update SQL statements to match
 *  alter table PublicationStage add CultureID varchar(255) not null;
 * 
 * Region table: Drop ParentID, add CultureID and fix insert/update SQL statements to match
 *  alter table Region drop ParentID;
 *  alter table Region add CultureID varchar(255) not null;
 *   
 * TransmissionType table: add CultureID and fix insert/update SQL statemnts to match
 *  alter table TransmissionType add CultureID varchar(255) not null; * 
 * 
 * Valves table: add CultureID and fix insert/update SQL statemnts to match
 *  alter table Valves add CultureID varchar(255) not null;
 * 
 * Vehilce table: Drop fields Source,PublicationStageSource,PublicationStageDate and fix insert/update SQL statemnts to match 
 *  alter table Vehicle drop Source;
 *  alter table Vehicle drop PublicationStageSource;
 *  alter table Vehicle drop PublicationStageDate;
 * 
 * VehicleToBedConfig table: Drop field Source and fix insert/update SQL statemnts to match
 *  alter table VehicleToBedConfig drop source;
 * 
 * VehicleToBodyConfig table: Drop field Source and fix insert/update SQL statemnts to match
 *  alter table VehicleToBodyConfig drop Source;
 * 
 * VehicleToBodyStyleConfig table: Drop field Source and fix insert/update SQL statemnts to match
 *  alter table VehicleToBodyStyleConfig drop Source; 
 * 
 * VehicleToBrakeConfig table: Drop field Source and fix insert/update SQL statemnts to match
 *  alter table VehicleToBrakeConfig drop Source;
 * 
 * VehicleToClass table: Drop field Source and fix insert/update SQL statemnts to match
 *  alter table VehicleToClass drop Source;
 * 
 * VehicleToDriveType table: Drop field Source and fix insert/update SQL statemnts to match
 *  alter table VehicleToDriveType drop Source;
 * 
 * VehicleToEngineConfig table: Drop field Source and fix insert/update SQL statemnts to match
 *  alter table VehicleToEngineConfig drop Source;
 * 
 * VehicleToMfrBodyCode table: Drop field Source and fix insert/update SQL statemnts to match
 *  alter table VehicleToMfrBodyCode drop Source;
 * 
 * VehicleToSpringTypeConfig table: Drop field Source and fix insert/update SQL statemnts to match
 * alter table VehicleToSpringTypeConfig drop Source;
 * 
 * VehicleToSteeringConfig table: Drop field Source and fix insert/update SQL statemnts to match
 *  alter table VehicleToSteeringConfig drop Source;
 * 
 * VehicleToTransmission table: Drop field Source and fix insert/update SQL statemnts to match
 *  alter table VehicleToTransmission drop Source;
 * 
 * VehicleToWheelbase table: Drop field Source and fix insert/update SQL statemnts to match
 *  alter table VehicleToWheelbase drop Source;
 * 
 * VehicleType table: Add CultureID and fix insert/update SQL statemnts to match
 *  alter table VehicleType add CultureID varchar(255) not null;
 * 
 * VehicleTypeGroup table: Add VehicleTypeGroupDescription, ClutureID and fix insert/update SQL
 *  alter table VehicleTypeGroup add VehicleTypeGroupDescription varchar(255) not null;
 *  alter table VehicleTypeGroup add CultureID varchar(255) not null;
 * 
 * WheelBase table: Add CultureID and fix insert/update SQL
 *  alter table WheelBase add CultureID varchar(255) not null;
 * 
 * 
 */


include_once("mysqlClass.php");

class vcdbapi
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
  $this->tableslist=array('Abbreviation','Aspiration','BaseVehicle','BedConfig','BedLength','BedType','BodyNumDoors','BodyStyleConfig','BodyType','BrakeABS','BrakeConfig','BrakeSystem','BrakeType','Class','CylinderHeadType','DriveType','ElecControlled','EngineBase','EngineBase2','EngineBlock','EngineBoreStroke','EngineConfig','EngineConfig2','EngineDesignation','EngineVIN','EngineVersion','FuelDeliveryConfig','FuelDeliverySubType','FuelDeliveryType','FuelSystemControlType','FuelSystemDesign','FuelType','IgnitionSystemType','Make','Mfr','MfrBodyCode','Model','PowerOutput','PublicationStage','Region','SpringType','SpringTypeConfig','SteeringConfig','SteeringSystem','SteeringType','SubModel','Transmission','TransmissionBase','TransmissionControlType','TransmissionMfrCode','TransmissionNumSpeeds','TransmissionType','VCdbChanges','Valves','Vehicle','VehicleToBedConfig','VehicleToBodyConfig','VehicleToBodyStyleConfig','VehicleToBrakeConfig','VehicleToClass','VehicleToDriveType','VehicleToEngineConfig','VehicleToMfrBodyCode','VehicleToSpringTypeConfig','VehicleToSteeringConfig','VehicleToTransmission','VehicleToWheelbase','VehicleType','VehicleTypeGroup','WheelBase','Year');
  //$this->tableslist=array('VehicleToBodyStyleConfig');
  $this->tablekeyslist=array('Abbreviation'=>'Abbreviation','Aspiration'=>'AspirationID','BaseVehicle'=>'BaseVehicleID','BedConfig'=>'BedConfigID','BedLength'=>'BedLengthID','BedType'=>'BedTypeID','BodyNumDoors'=>'BodyNumDoorsID','BodyStyleConfig'=>'BodyStyleConfigID','BodyType'=>'BodyTypeID','BrakeABS'=>'BrakeABSID','BrakeConfig'=>'BrakeConfigID','BrakeSystem'=>'BrakeSystemID','BrakeType'=>'BrakeTypeID','Class'=>'ClassID','CylinderHeadType'=>'CylinderHeadTypeID','DriveType'=>'DriveTypeID','ElecControlled'=>'ElecControlledID','EngineBase'=>'EngineBaseID','EngineBase2'=>'EngineBaseID','EngineBlock'=>'EngineBlockID','EngineBoreStroke'=>'EngineBoreStrokeID','EngineConfig'=>'EngineConfigID','EngineConfig2'=>'EngineConfigID','EngineDesignation'=>'EngineDesignationID','EngineVIN'=>'EngineVINID','EngineVersion'=>'EngineVersionID','FuelDeliveryConfig'=>'FuelDeliveryConfigID','FuelDeliverySubType'=>'FuelDeliverySubTypeID','FuelDeliveryType'=>'FuelDeliveryTypeID','FuelSystemControlType'=>'FuelSystemControlTypeID','FuelSystemDesign'=>'FuelSystemDesignID','FuelType'=>'FuelTypeID','IgnitionSystemType'=>'IgnitionSystemTypeID','Make'=>'MakeID','Mfr'=>'MfrID','MfrBodyCode'=>'MfrBodyCodeID','Model'=>'ModelID','PowerOutput'=>'PowerOutputID','PublicationStage'=>'PublicationStageID','Region'=>'RegionID','SpringType'=>'SpringTypeID','SpringTypeConfig'=>'SpringTypeConfigID','SteeringConfig'=>'SteeringConfigID','SteeringSystem'=>'SteeringSystemID','SteeringType'=>'SteeringTypeID','SubModel'=>'SubModelID','Transmission'=>'TransmissionID','TransmissionBase'=>'TransmissionBaseID','TransmissionControlType'=>'TransmissionControlTypeID','TransmissionMfrCode'=>'TransmissionMfrCodeID','TransmissionNumSpeeds'=>'TransmissionNumSpeedsID','TransmissionType'=>'TransmissionTypeID','VCdbChanges'=>'ID','Valves'=>'ValvesID','Vehicle'=>'VehicleID','VehicleToBedConfig'=>'VehicleToBedConfigID','VehicleToBodyConfig'=>'VehicleToBodyConfigID','VehicleToBodyStyleConfig'=>'VehicleToBodyStyleConfigID','VehicleToBrakeConfig'=>'VehicleToBrakeConfigID','VehicleToClass'=>'VehicleToClassID','VehicleToDriveType'=>'VehicleToDriveTypeID','VehicleToEngineConfig'=>'VehicleToEngineConfigID','VehicleToMfrBodyCode'=>'VehicleToMfrBodyCodeID','VehicleToSpringTypeConfig'=>'VehicleToSpringTypeConfigID','VehicleToSteeringConfig'=>'VehicleToSteeringConfigID','VehicleToTransmission'=>'VehicleToTransmissionID','VehicleToWheelbase'=>'VehicleToWheelbaseID','VehicleType'=>'VehicleTypeID','VehicleTypeGroup'=>'VehicleTypeGroupID','WheelBase'=>'WheelBaseID','Year'=>'YearID');   

  $this->pagelimit=0;
  $this->totalcalls=0;
  $this->activetoken=false;
  $this->tokenrefreshcount=0;
  $this->insertcount=0;
  $this->updatecount=0;
  $this->deletecount=0;
  $this->deleteorphancount=0;
  
  $this->localdbname=$_localdbname;  // default to the hard-coded dbname from the class file (prob "vcdb")
  if(!$_localdbname)
  { // no secific vsersion was passed in. Consult pim database for the name
    // of the active vcdb database. It will be something like vcdb20210827
      
   $db = new mysql; $db->connect();
   if($stmt=$db->conn->prepare("select configvalue from config where configname='vcdbAPIcacheDatabase'"))
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
  curl_setopt($ch, CURLOPT_URL,'https://common.autocarevip.com/api/v2.0/databases');
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
  curl_setopt($ch, CURLOPT_URL,'https://common.autocarevip.com/api/v2.0/databases/'.$database.'/tables');
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
   
   if($this->tokenLife()<300)
   {// need to refresh token - it less than 5 minutes life left
    if($this->debug){echo " Token-refresh needed before table complete.\r\n";}

    $this->activetoken=false;  $this->getAccessToken();
    if(!$this->activetoken)
    {
     if($this->debug){echo " Token refresh request failed.\r\n";}
     return false;
    }
   }
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
   $url='https://'.$database.'.autocarevip.com/api/v2.0/'.$database.'/'.$table.'?CultureId='.$cultureid.$sincedateclause;
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
  $db = new mysql; $db->dbname=$this->localdbname; $db->connect();
  
  if($stmt=$db->conn->prepare('select '.$keyfieldname.' from '.$tablename))
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
  
  foreach($records as $record)
  {
   $idkeyedapirecords[$record[$keyfieldname]]=$record;
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
   case 'Abbreviation':
       
    if($stmt=$db->conn->prepare('insert into Abbreviation values(?,?,?)'))
    {
     if($stmt->bind_param('sss', $Abbreviation, $Description, $LongDescription))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $Abbreviation=$record['Abbreviation']; $Description=$record['Description']; $LongDescription=$record['LongDescription'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Abbreviation where Abbreviation=?'))
    {
     if($stmt->bind_param('s', $Abbreviation))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $Abbreviation=$record['Abbreviation']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $Abbreviation=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Abbreviation set Description=?, LongDescription=? where Abbreviation=?'))
    {
     if($stmt->bind_param('sss', $Description, $LongDescription, $Abbreviation))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $Abbreviation=$record['Abbreviation']; $Description=$record['Description']; $LongDescription=$record['LongDescription'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       

       
    break;

   case 'Aspiration':
    if($stmt=$db->conn->prepare('insert into Aspiration values(?,?)'))
    {
     if($stmt->bind_param('is', $AspirationID, $AspirationName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $AspirationID=$record['AspirationID']; $AspirationName=$record['AspirationName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Aspiration where AspirationID=?'))
    {
     if($stmt->bind_param('i', $AspirationID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $AspirationID=$record['AspirationID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $AspirationID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Aspiration set AspirationName=? where AspirationID=?'))
    {
     if($stmt->bind_param('si', $AspirationName, $AspirationID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $AspirationID=$record['AspirationID']; $AspirationName=$record['AspirationName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;
    
   case 'BaseVehicle':
              
    if($stmt=$db->conn->prepare('insert into BaseVehicle values(?,?,?,?)'))
    {
     if($stmt->bind_param('iiii', $BaseVehicleID, $YearID, $MakeID, $ModelID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $BaseVehicleID=$record['BaseVehicleID']; $MakeID=$record['MakeID']; $ModelID=$record['ModelID']; $YearID=$record['YearID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from BaseVehicle where BaseVehicleID=?'))
    {
     if($stmt->bind_param('i', $BaseVehicleID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $BaseVehicleID=$record['BaseVehicleID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $BaseVehicleID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update BaseVehicle set YearID=?,MakeID=?,ModelID=? where BaseVehicleID=?'))
    {
     if($stmt->bind_param('iiii', $YearID, $MakeID, $ModelID, $BaseVehicleID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $BaseVehicleID=$record['BaseVehicleID']; $MakeID=$record['MakeID']; $ModelID=$record['ModelID']; $YearID=$record['YearID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'BedConfig':
    if($stmt=$db->conn->prepare('insert into BedConfig values(?,?,?)'))
    {
     if($stmt->bind_param('iii', $BedConfigID,$BedLengthID,$BedTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $BedConfigID=$record['BedConfigID']; $BedLengthID=$record['BedLengthID']; $BedTypeID=$record['BedTypeID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from BedConfig where BedConfigID=?'))
    {
     if($stmt->bind_param('i', $BedConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $BedConfigID=$record['BedConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $BedConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update BedConfig set BedLengthID=?, BedTypeID=? where BedConfigID=?')) 
    {
     if($stmt->bind_param('iii', $BedLengthID, $BedTypeID, $BedConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $BedConfigID=$record['BedConfigID']; $BedLengthID=$record['BedLengthID']; $BedTypeID=$record['BedTypeID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
       
    break;

   case 'BedLength':
              
    if($stmt=$db->conn->prepare('insert into BedLength values(?,?,?)'))
    {
     if($stmt->bind_param('iss', $BedLengthID,$BedLength,$BedLengthMetric))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $BedLengthID=$record['BedLengthID']; $BedLength=$record['BedLength']; $BedLengthMetric=$record['BedLengthMetric'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from BedLength where BedLengthID=?'))
    {
     if($stmt->bind_param('i', $BedLengthID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $BedLengthID=$record['BedLengthID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $BedLengthID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update BedLength set BedLength=?, BedLengthMetric=? where BedLengthID=?'))
    {
     if($stmt->bind_param('ssi',$BedLength,$BedLengthMetric, $BedLengthID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $BedLengthID=$record['BedLengthID']; $BedLength=$record['BedLength']; $BedLengthMetric=$record['BedLengthMetric'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    
    break;

   case 'BedType':

    if($stmt=$db->conn->prepare('insert into BedType values(?,?)'))
    {
     if($stmt->bind_param('is', $BedTypeID,$BedTypeName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $BedTypeID=$record['BedTypeID']; $BedTypeName=$record['BedTypeName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from BedType where BedTypeID=?'))
    {
     if($stmt->bind_param('i', $BedTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $BedTypeID=$record['BedTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $BedTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update BedType set BedTypeName=? where BedTypeID=?'))
    {
     if($stmt->bind_param('si',$BedTypeName,$BedTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $BedTypeID=$record['BedTypeID']; $BedTypeName=$record['BedTypeName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'BodyNumDoors':

    if($stmt=$db->conn->prepare('insert into BodyNumDoors values(?,?)'))
    {
     if($stmt->bind_param('is', $BodyNumDoorsID,$BodyNumDoors))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $BodyNumDoorsID=$record['BodyNumDoorsID']; $BodyNumDoors=$record['BodyNumDoors'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from BodyNumDoors where BodyNumDoorsID=?'))
    {
     if($stmt->bind_param('i', $BodyNumDoorsID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $BodyNumDoorsID=$record['BodyNumDoorsID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $BodyNumDoorsID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update BodyNumDoors set BodyNumDoors=? where BodyNumDoorsID=?'))
    {
     if($stmt->bind_param('si', $BodyNumDoors, $BodyNumDoorsID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $BodyNumDoorsID=$record['BodyNumDoorsID']; $BodyNumDoors=$record['BodyNumDoors'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;
    
    case 'BodyStyleConfig':

    if($stmt=$db->conn->prepare('insert into BodyStyleConfig values(?,?,?)'))
    {
     if($stmt->bind_param('iii', $BodyStyleConfigID,$BodyNumDoorsID,$BodyTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $BodyStyleConfigID=$record['BodyStyleConfigID']; $BodyNumDoorsID=$record['BodyNumDoorsID']; $BodyTypeID=$record['BodyTypeID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from BodyStyleConfig where BodyStyleConfigID=?'))
    {
     if($stmt->bind_param('i', $BodyStyleConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $BodyStyleConfigID=$record['BodyStyleConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $BodyStyleConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update BodyStyleConfig set BodyNumDoorsID=?, BodyTypeID=? where BodyStyleConfigID=?'))
    {
     if($stmt->bind_param('iii',$BodyNumDoorsID,$BodyTypeID,$BodyStyleConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $BodyStyleConfigID=$record['BodyStyleConfigID']; $BodyNumDoorsID=$record['BodyNumDoorsID']; $BodyTypeID=$record['BodyTypeID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }   
    break;

   case 'BodyType':

    if($stmt=$db->conn->prepare('insert into BodyType values(?,?)'))
    {
     if($stmt->bind_param('is', $BodyTypeID,$BodyTypeName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $BodyTypeID=$record['BodyTypeID']; $BodyTypeName=$record['BodyTypeName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from BodyType where BodyTypeID=?'))
    {
     if($stmt->bind_param('i', $BodyTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $BodyTypeID=$record['BodyTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $BodyTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update BodyType set BodyTypeName=? where BodyTypeID=?'))
    {
     if($stmt->bind_param('si',$BodyTypeName,$BodyTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $BodyTypeID=$record['BodyTypeID']; $BodyTypeName=$record['BodyTypeName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'BrakeABS':
       
    if($stmt=$db->conn->prepare('insert into BrakeABS values(?,?)'))
    {
     if($stmt->bind_param('is', $BrakeABSID,$BrakeABSName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $BrakeABSID=$record['BrakeABSID']; $BrakeABSName=$record['BrakeABSName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from BrakeABS where BrakeABSID=?'))
    {
     if($stmt->bind_param('i', $BrakeABSID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $BrakeABSID=$record['BrakeABSID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $BrakeABSID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update BrakeABS set BrakeABSName=? where BrakeABSID=?'))
    {
     if($stmt->bind_param('si',$BrakeABSName,$BrakeABSID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $BrakeABSID=$record['BrakeABSID']; $BrakeABSName=$record['BrakeABSName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
      
    break;

   case 'BrakeConfig':
          
    if($stmt=$db->conn->prepare('insert into BrakeConfig values(?,?,?,?,?)'))
    {
     if($stmt->bind_param('iiiii', $BrakeConfigID,$FrontBrakeTypeID,$RearBrakeTypeID,$BrakeSystemID,$BrakeABSID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $BrakeConfigID=$record['BrakeConfigID']; $FrontBrakeTypeID=$record['FrontBrakeTypeID']; $RearBrakeTypeID=$record['RearBrakeTypeID']; $BrakeSystemID=$record['BrakeSystemID']; $BrakeABSID=$record['BrakeABSID']; 
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from BrakeConfig where BrakeConfigID=?'))
    {
     if($stmt->bind_param('i', $BrakeConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $BrakeConfigID=$record['BrakeConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $BrakeConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update BrakeConfig set FrontBrakeTypeID=?,RearBrakeTypeID=?,BrakeSystemID=?,BrakeABSID=? where BrakeConfigID=?'))
    {
     if($stmt->bind_param('iiiii',$FrontBrakeTypeID,$RearBrakeTypeID,$BrakeSystemID,$BrakeABSID,$BrakeConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $FrontBrakeTypeID=$record['FrontBrakeTypeID'];
        $RearBrakeTypeID=$record['RearBrakeTypeID'];
        $BrakeSystemID=$record['BrakeSystemID'];
        $BrakeABSID=$record['BrakeABSID'];
        $BrakeConfigID=$record['BrakeConfigID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'BrakeSystem':

    if($stmt=$db->conn->prepare('insert into BrakeSystem values(?,?)'))
    {
     if($stmt->bind_param('is', $BrakeSystemID,$BrakeSystemName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $BrakeSystemID=$record['BrakeSystemID']; $BrakeSystemName=$record['BrakeSystemName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from BrakeSystem where BrakeSystemID=?'))
    {
     if($stmt->bind_param('i', $BrakeSystemID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $BrakeSystemID=$record['BrakeSystemID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $BrakeSystemID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update BrakeSystem set BrakeSystemName=? where BrakeSystemID=?'))
    {
     if($stmt->bind_param('si',$BrakeSystemName,$BrakeSystemID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $BrakeSystemID=$record['BrakeSystemID']; $BrakeSystemName=$record['BrakeSystemName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'BrakeType':

    if($stmt=$db->conn->prepare('insert into BrakeType values(?,?)'))
    {
     if($stmt->bind_param('is', $BrakeTypeID,$BrakeTypeName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $BrakeTypeID=$record['BrakeTypeID']; $BrakeTypeName=$record['BrakeTypeName']; 
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from BrakeType where BrakeTypeID=?'))
    {
     if($stmt->bind_param('i', $BrakeTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $BrakeTypeID=$record['BrakeTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $BrakeTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update BrakeType set BrakeTypeName=? where BrakeTypeID=?'))
    {
     if($stmt->bind_param('si', $BrakeTypeName, $BrakeTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $BrakeTypeID=$record['BrakeTypeID']; $BrakeTypeName=$record['BrakeTypeName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
       
    break;

   case 'Class':

    if($stmt=$db->conn->prepare('insert into Class values(?,?)'))
    {
     if($stmt->bind_param('is', $ClassID,$ClassName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $ClassID=$record['ClassID']; $ClassName=$record['ClassName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Class where ClassID=?'))
    {
     if($stmt->bind_param('i', $ClassID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $ClassID=$record['ClassID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $ClassID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Class set ClassName=? where ClassID=?'))
    {
     if($stmt->bind_param('si', $ClassName, $ClassID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $ClassID=$record['ClassID']; $ClassName=$record['ClassName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'CylinderHeadType':
       
    if($stmt=$db->conn->prepare('insert into CylinderHeadType values(?,?)'))
    {
     if($stmt->bind_param('is', $CylinderHeadTypeID,$CylinderHeadTypeName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $CylinderHeadTypeID=$record['CylinderHeadTypeID']; $CylinderHeadTypeName=$record['CylinderHeadTypeName']; 
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from CylinderHeadType where CylinderHeadTypeID=?'))
    {
     if($stmt->bind_param('i', $CylinderHeadTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $CylinderHeadTypeID=$record['CylinderHeadTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $CylinderHeadTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update CylinderHeadType set CylinderHeadTypeName=? where CylinderHeadTypeID=?'))
    {
     if($stmt->bind_param('si', $CylinderHeadTypeName, $CylinderHeadTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $CylinderHeadTypeID=$record['CylinderHeadTypeID']; $CylinderHeadTypeName=$record['CylinderHeadTypeName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'DriveType':
    if($stmt=$db->conn->prepare('insert into DriveType values(?,?)'))
    {
     if($stmt->bind_param('is', $DriveTypeID,$DriveTypeName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $DriveTypeID=$record['DriveTypeID']; $DriveTypeName=$record['DriveTypeName']; 
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from DriveType where DriveTypeID=?'))
    {
     if($stmt->bind_param('i', $DriveTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $DriveTypeID=$record['DriveTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $DriveTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update DriveType set DriveTypeName=? where DriveTypeID=?'))
    {
     if($stmt->bind_param('si', $DriveTypeName, $DriveTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $DriveTypeID=$record['DriveTypeID']; $DriveTypeName=$record['DriveTypeName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
       
    break;

   case 'ElecControlled':
    if($stmt=$db->conn->prepare('insert into ElecControlled values(?,?)'))
    {
     if($stmt->bind_param('is', $ElecControlledID,$ElecControlled))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $ElecControlledID=$record['ElecControlledID']; $ElecControlled=$record['ElecControlled'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from ElecControlled where ElecControlledID=?'))
    {
     if($stmt->bind_param('i', $ElecControlledID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $ElecControlledID=$record['ElecControlledID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $ElecControlledID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update ElecControlled set ElecControlled=? where ElecControlledID=?'))
    {
     if($stmt->bind_param('si', $ElecControlled, $ElecControlledID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $ElecControlledID=$record['ElecControlledID']; $ElecControlled=$record['ElecControlled'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }     
    break;

    // seems to be some missing data from the API on this one - may need to add in null-handling on Liter, CC, CID,Cylinders, BlockType,EngBoreIn, EngBoreMetric, EngStrokeIn, EngStrokeMetric
   case 'EngineBase':
       
    if($stmt=$db->conn->prepare('insert into EngineBase values(?,?,?,?,?,?,?,?,?,?)'))
    {
     if($stmt->bind_param('isssssssss', $EngineBaseID, $Liter, $CC, $CID, $Cylinders, $BlockType, $EngBoreIn, $EngBoreMetric, $EngStrokeIn, $EngStrokeMetric))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $EngineBaseID=$record['EngineBaseID'];
        $Liter=''; if(isset($record['Liter'])){$Liter=$record['Liter'];}
        $CC=''; if(isset($record['CC'])){$CC=$record['CC']; }
        $CID=''; if(isset($record['CID'])){$CID=$record['CID']; }
        $Cylinders=''; if(isset($record['Cylinders'])){$Cylinders=$record['Cylinders']; }
        $BlockType=''; if(isset($record['BlockType'])){$BlockType=$record['BlockType']; }
        $EngBoreIn=''; if(isset($record['EngBoreIn'])){$EngBoreIn=$record['EngBoreIn']; }
        $EngBoreMetric=''; if(isset($record['EngBoreMetric'])){$EngBoreMetric=$record['EngBoreMetric'];} 
        $EngStrokeIn=''; if(isset($record['EngStrokeIn'])){$EngStrokeIn=$record['EngStrokeIn']; }
        $EngStrokeMetric=''; if(isset($record['EngStrokeMetric'])){$EngStrokeMetric=$record['EngStrokeMetric']; }   
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from EngineBase where EngineBaseID=?'))
    {
     if($stmt->bind_param('i', $EngineBaseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $EngineBaseID=$record['EngineBaseID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $EngineBaseID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update EngineBase set Liter=?,CC=?,CID=?,Cylinders=?,BlockType=?,EngBoreIn=?,EngBoreMetric=?,EngStrokeIn=?,EngStrokeMetric=? where EngineBaseID=?'))
    {
     if($stmt->bind_param('sssssssssi', $Liter, $CC, $CID, $Cylinders, $BlockType, $EngBoreIn, $EngBoreMetric, $EngStrokeIn, $EngStrokeMetric, $EngineBaseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $EngineBaseID=$record['EngineBaseID'];
        $Liter=''; if(isset($record['Liter'])){$Liter=$record['Liter'];}
        $CC=''; if(isset($record['CC'])){$CC=$record['CC']; }
        $CID=''; if(isset($record['CID'])){$CID=$record['CID']; }
        $Cylinders=''; if(isset($record['Cylinders'])){$Cylinders=$record['Cylinders']; }
        $BlockType=''; if(isset($record['BlockType'])){$BlockType=$record['BlockType']; }
        $EngBoreIn=''; if(isset($record['EngBoreIn'])){$EngBoreIn=$record['EngBoreIn']; }
        $EngBoreMetric=''; if(isset($record['EngBoreMetric'])){$EngBoreMetric=$record['EngBoreMetric'];} 
        $EngStrokeIn=''; if(isset($record['EngStrokeIn'])){$EngStrokeIn=$record['EngStrokeIn']; }
        $EngStrokeMetric=''; if(isset($record['EngStrokeMetric'])){$EngStrokeMetric=$record['EngStrokeMetric']; }   
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }     
    break;

   
   case 'EngineBase2':
       
    if($stmt=$db->conn->prepare('insert into EngineBase2 values(?,?,?)'))
    {
     if($stmt->bind_param('iii', $EngineBaseID,$EngineBlockID,$EngineBoreStrokeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $EngineBaseID=$record['EngineBaseID']; $EngineBlockID=$record['EngineBlockID'];  $EngineBoreStrokeID=$record['EngineBoreStrokeID']; 
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from EngineBase2 where EngineBaseID=?'))
    {
     if($stmt->bind_param('i', $EngineBaseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $EngineBaseID=$record['EngineBaseID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $EngineBaseID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update EngineBase2 set EngineBlockID=?,EngineBoreStrokeID=? where EngineBaseID=?'))
    {
     if($stmt->bind_param('iii', $EngineBlockID, $EngineBoreStrokeID, $EngineBaseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $EngineBaseID=$record['EngineBaseID']; $EngineBlockID=$record['EngineBlockID'];  $EngineBoreStrokeID=$record['EngineBoreStrokeID']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }  
    
       
       
       
    break;

   case 'EngineBlock':
       
    if($stmt=$db->conn->prepare('insert into EngineBlock values(?,?,?,?,?,?)'))
    {
     if($stmt->bind_param('isssss', $EngineBlockID,$Liter,$CC,$CID,$Cylinders,$BlockType))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $EngineBlockID=$record['EngineBlockID']; $Liter=$record['Liter']; $CC=$record['CC']; $CID=$record['CID']; $Cylinders=$record['Cylinders']; $BlockType=$record['BlockType'];  
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from EngineBlock where EngineBlockID=?'))
    {
     if($stmt->bind_param('i', $EngineBlockID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $EngineBlockID=$record['EngineBlockID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $EngineBlockID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update EngineBlock set Liter=?,CC=?,CID=?,Cylinders=?,BlockType=? where EngineBlockID=?'))
    {
     if($stmt->bind_param('sssssi',$Liter,$CC,$CID,$Cylinders,$BlockType,$EngineBlockID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $EngineBlockID=$record['EngineBlockID']; $Liter=$record['Liter']; $CC=$record['CC']; $CID=$record['CID']; $Cylinders=$record['Cylinders']; $BlockType=$record['BlockType'];  
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
       
    break;

   case 'EngineBoreStroke':
       
    if($stmt=$db->conn->prepare('insert into EngineBoreStroke values(?,?,?,?,?)'))
    {
     if($stmt->bind_param('issss', $EngineBoreStrokeID,$EngBoreIn,$EngBoreMetric,$EngStrokeIn,$EngStrokeMetric))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $EngineBoreStrokeID=$record['EngineBoreStrokeID']; $EngBoreIn=$record['EngBoreIn']; $EngBoreMetric=$record['EngBoreMetric']; $EngStrokeIn=$record['EngStrokeIn']; $EngStrokeMetric=$record['EngStrokeMetric'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from EngineBoreStroke where EngineBoreStrokeID=?'))
    {
     if($stmt->bind_param('i', $EngineBoreStrokeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $EngineBoreStrokeID=$record['$EngineBoreStrokeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $EngineBoreStrokeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update EngineBoreStroke set EngBoreIn=?,EngBoreMetric=?,EngStrokeIn=?,EngStrokeMetric=? where EngineBoreStrokeID=?'))
    {
     if($stmt->bind_param('ssssi', $EngBoreIn, $EngBoreMetric, $EngStrokeIn, $EngStrokeMetric, $EngineBoreStrokeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $EngineBoreStrokeID=$record['EngineBoreStrokeID']; $EngBoreIn=$record['EngBoreIn']; $EngBoreMetric=$record['EngBoreMetric']; $EngStrokeIn=$record['EngStrokeIn']; $EngStrokeMetric=$record['EngStrokeMetric'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
       
    break;

   case 'EngineConfig':
       
    if($stmt=$db->conn->prepare('insert into EngineConfig values(?,?,?,?,?,?,?,?,?,?,?,?,?)'))
    {
     if($stmt->bind_param('iiiiiiiiiiiii', $EngineConfigID,$EngineDesignationID,$EngineVINID,$ValvesID,$EngineBaseID,$FuelDeliveryConfigID,$AspirationID,$CylinderHeadTypeID,$FuelTypeID,$IgnitionSystemTypeID,$EngineMfrID,$EngineVersionID,$PowerOutputID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $EngineConfigID=$record['EngineConfigID']; $EngineDesignationID=$record['EngineDesignationID']; $EngineVINID=$record['EngineVINID']; $ValvesID=$record['ValvesID']; $EngineBaseID=$record['EngineBaseID']; $FuelDeliveryConfigID=$record['FuelDeliveryConfigID']; $AspirationID=$record['AspirationID']; $CylinderHeadTypeID=$record['CylinderHeadTypeID']; $FuelTypeID=$record['FuelTypeID']; $IgnitionSystemTypeID=$record['IgnitionSystemTypeID']; $EngineMfrID=$record['EngineMfrID']; $EngineVersionID=$record['EngineVersionID']; $PowerOutputID=$record['PowerOutputID'];              
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from EngineConfig where EngineConfigID=?'))
    {
     if($stmt->bind_param('i', $EngineConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $EngineConfigID=$record['EngineConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $EngineConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update EngineConfig set EngineDesignationID=?,EngineVINID=?,ValvesID=?,EngineBaseID=?,FuelDeliveryConfigID=?,AspirationID=?,CylinderHeadTypeID=?,FuelTypeID=?,IgnitionSystemTypeID=?,EngineMfrID=?,EngineVersionID=?,PowerOutputID=? where EngineConfigID=?'))
    {
     if($stmt->bind_param('iiiiiiiiiiiii', $EngineDesignationID,$EngineVINID,$ValvesID,$EngineBaseID,$FuelDeliveryConfigID,$AspirationID,$CylinderHeadTypeID,$FuelTypeID,$IgnitionSystemTypeID,$EngineMfrID,$EngineVersionID,$PowerOutputID,$EngineConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $EngineConfigID=$record['EngineConfigID']; $EngineDesignationID=$record['EngineDesignationID']; $EngineVINID=$record['EngineVINID']; $ValvesID=$record['ValvesID']; $EngineBaseID=$record['EngineBaseID']; $FuelDeliveryConfigID=$record['FuelDeliveryConfigID']; $AspirationID=$record['AspirationID']; $CylinderHeadTypeID=$record['CylinderHeadTypeID']; $FuelTypeID=$record['FuelTypeID']; $IgnitionSystemTypeID=$record['IgnitionSystemTypeID']; $EngineMfrID=$record['EngineMfrID']; $EngineVersionID=$record['EngineVersionID']; $PowerOutputID=$record['PowerOutputID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

    
    
   case 'EngineConfig2':
    // vvvv
    if($stmt=$db->conn->prepare('insert into EngineConfig2 values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'))
    {
     if($stmt->bind_param('iiiiiiiiiiiiiii', $EngineConfigID,$EngineDesignationID,$EngineVINID,$ValvesID,$EngineBaseID,$EngineBlockID,$EngineBoreStrokeID,$FuelDeliveryConfigID,$AspirationID,$CylinderHeadTypeID,$FuelTypeID,$IgnitionSystemTypeID,$EngineMfrID,$EngineVersionID,$PowerOutputID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $EngineConfigID=$record['EngineConfigID']; $EngineDesignationID=$record['EngineDesignationID']; $EngineVINID=$record['EngineVINID']; $ValvesID=$record['ValvesID']; $EngineBaseID=$record['EngineBaseID']; $EngineBlockID=$record['EngineBlockID']; $EngineBoreStrokeID=$record['EngineBoreStrokeID']; $FuelDeliveryConfigID=$record['FuelDeliveryConfigID']; $AspirationID=$record['AspirationID']; $CylinderHeadTypeID=$record['CylinderHeadTypeID']; $FuelTypeID=$record['FuelTypeID']; $IgnitionSystemTypeID=$record['IgnitionSystemTypeID']; $EngineMfrID=$record['EngineMfrID']; $EngineVersionID=$record['EngineVersionID']; $PowerOutputID=$record['PowerOutputID'];              
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from EngineConfig2 where EngineConfigID=?'))
    {
     if($stmt->bind_param('i', $EngineConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $EngineConfigID=$record['EngineConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $EngineConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update EngineConfig2 set EngineDesignationID=?,EngineVINID=?,ValvesID=?,EngineBaseID=?,EngineBlockID=?,EngineBoreStrokeID=?,FuelDeliveryConfigID=?,AspirationID=?,CylinderHeadTypeID=?,FuelTypeID=?,IgnitionSystemTypeID=?,EngineMfrID=?,EngineVersionID=?,PowerOutputID=? where EngineConfigID=?'))
    {
     if($stmt->bind_param('iiiiiiiiiiiiiii', $EngineDesignationID,$EngineVINID,$ValvesID,$EngineBaseID,$EngineBlockID,$EngineBoreStrokeID,$FuelDeliveryConfigID,$AspirationID,$CylinderHeadTypeID,$FuelTypeID,$IgnitionSystemTypeID,$EngineMfrID,$EngineVersionID,$PowerOutputID,$EngineConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $EngineConfigID=$record['EngineConfigID']; $EngineDesignationID=$record['EngineDesignationID']; $EngineVINID=$record['EngineVINID']; $ValvesID=$record['ValvesID']; $EngineBaseID=$record['EngineBaseID']; $EngineBlockID=$record['EngineBlockID']; $EngineBoreStrokeID=$record['EngineBoreStrokeID']; $FuelDeliveryConfigID=$record['FuelDeliveryConfigID']; $AspirationID=$record['AspirationID']; $CylinderHeadTypeID=$record['CylinderHeadTypeID']; $FuelTypeID=$record['FuelTypeID']; $IgnitionSystemTypeID=$record['IgnitionSystemTypeID']; $EngineMfrID=$record['EngineMfrID']; $EngineVersionID=$record['EngineVersionID']; $PowerOutputID=$record['PowerOutputID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
       
    break;







   case 'EngineDesignation':
       
    if($stmt=$db->conn->prepare('insert into EngineDesignation values(?,?)'))
    {
     if($stmt->bind_param('is', $EngineDesignationID,$EngineDesignationName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $EngineDesignationID=$record['EngineDesignationID']; $EngineDesignationName=$record['EngineDesignationName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from EngineDesignation where EngineDesignationID=?'))
    {
     if($stmt->bind_param('i', $EngineDesignationID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $EngineDesignationID=$record['EngineDesignationID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $EngineDesignationID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update EngineDesignation set EngineDesignationName=? where EngineDesignationID=?'))
    {
     if($stmt->bind_param('si', $EngineDesignationName, $EngineDesignationID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $EngineDesignationID=$record['EngineDesignationID']; $EngineDesignationName=$record['EngineDesignationName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'EngineVIN':
       
    if($stmt=$db->conn->prepare('insert into EngineVIN values(?,?)'))
    {
     if($stmt->bind_param('is', $EngineVINID,$EngineVINName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $EngineVINID=$record['EngineVINID']; $EngineVINName=$record['EngineVINName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from EngineVIN where EngineVINID=?'))
    {
     if($stmt->bind_param('i', $EngineVINID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $EngineVINID=$record['EngineVINID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $EngineVINID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update EngineVIN set EngineVINName=? where EngineVINID=?'))
    {
     if($stmt->bind_param('si', $EngineVINName, $EngineVINID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $EngineVINID=$record['EngineVINID']; $EngineVINName=$record['EngineVINName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'EngineVersion':
       
    if($stmt=$db->conn->prepare('insert into EngineVersion values(?,?)'))
    {
     if($stmt->bind_param('is', $EngineVersionID, $EngineVersion))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $EngineVersionID=$record['EngineVersionID']; $EngineVersion=$record['EngineVersion'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from EngineVersion where EngineVersionID=?'))
    {
     if($stmt->bind_param('i', $EngineVersionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $EngineVersionID=$record['EngineVersionID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $EngineVersionID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update EngineVersion set EngineVersion=? where EngineVersionID=?'))
    {
     if($stmt->bind_param('si', $EngineVersion, $EngineVersionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $EngineVersionID=$record['EngineVersionID']; $EngineVersion=$record['EngineVersion'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
       
    break;

   case 'FuelDeliveryConfig':
       
    if($stmt=$db->conn->prepare('insert into FuelDeliveryConfig values(?,?,?,?,?)'))
    {
     if($stmt->bind_param('iiiii', $FuelDeliveryConfigID,$FuelDeliveryTypeID,$FuelDeliverySubTypeID,$FuelSystemControlTypeID,$FuelSystemDesignID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $FuelDeliveryConfigID=$record['FuelDeliveryConfigID']; $FuelDeliveryTypeID=$record['FuelDeliveryTypeID']; $FuelDeliverySubTypeID=$record['FuelDeliverySubTypeID']; $FuelSystemControlTypeID=$record['FuelSystemControlTypeID']; $FuelSystemDesignID=$record['FuelSystemDesignID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from FuelDeliveryConfig where FuelDeliveryConfigID=?'))
    {
     if($stmt->bind_param('i', $FuelDeliveryConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $FuelDeliveryConfigID=$record['FuelDeliveryConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $FuelDeliveryConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update FuelDeliveryConfig set FuelDeliveryTypeID=?,FuelDeliverySubTypeID=?,FuelSystemControlTypeID=?,FuelSystemDesignID=? where FuelDeliveryConfigID=?'))
    {
     if($stmt->bind_param('iiiii', $FuelDeliveryTypeID, $FuelDeliverySubTypeID, $FuelSystemControlTypeID, $FuelSystemDesignID, $FuelDeliveryConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $FuelDeliveryConfigID=$record['FuelDeliveryConfigID']; $FuelDeliveryTypeID=$record['FuelDeliveryTypeID']; $FuelDeliverySubTypeID=$record['FuelDeliverySubTypeID']; $FuelSystemControlTypeID=$record['FuelSystemControlTypeID']; $FuelSystemDesignID=$record['FuelSystemDesignID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;
    
   case 'FuelDeliverySubType':

    if($stmt=$db->conn->prepare('insert into FuelDeliverySubType values(?,?)'))
    {
     if($stmt->bind_param('is', $FuelDeliverySubTypeID, $FuelDeliverySubTypeName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $FuelDeliverySubTypeID=$record['FuelDeliverySubTypeID']; $FuelDeliverySubTypeName=$record['FuelDeliverySubTypeName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from FuelDeliverySubType where FuelDeliverySubTypeID=?'))
    {
     if($stmt->bind_param('i', $FuelDeliverySubTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $FuelDeliverySubTypeID=$record['FuelDeliverySubTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $FuelDeliverySubTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update FuelDeliverySubType set FuelDeliverySubTypeName=? where FuelDeliverySubTypeID=?'))
    {
     if($stmt->bind_param('si', $FuelDeliverySubTypeName, $FuelDeliverySubTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $FuelDeliverySubTypeID=$record['FuelDeliverySubTypeID']; $FuelDeliverySubTypeName=$record['FuelDeliverySubTypeName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'FuelDeliveryType':
       
    if($stmt=$db->conn->prepare('insert into FuelDeliveryType values(?,?)'))
    {
     if($stmt->bind_param('is', $FuelDeliveryTypeID, $FuelDeliveryTypeName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $FuelDeliveryTypeID=$record['FuelDeliveryTypeID']; $FuelDeliveryTypeName=$record['FuelDeliveryTypeName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from FuelDeliveryType where FuelDeliveryTypeID=?'))
    {
     if($stmt->bind_param('i', $FuelDeliveryTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $FuelDeliveryTypeID=$record['FuelDeliveryTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $FuelDeliveryTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update FuelDeliveryType set FuelDeliveryTypeName=? where FuelDeliveryTypeID=?'))
    {
     if($stmt->bind_param('si', $FuelDeliveryTypeName, $FuelDeliveryTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $FuelDeliveryTypeID=$record['FuelDeliveryTypeID']; $FuelDeliveryTypeName=$record['FuelDeliveryTypeName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

    
   case 'FuelSystemControlType':

    if($stmt=$db->conn->prepare('insert into FuelSystemControlType values(?,?,?)'))
    {
     if($stmt->bind_param('iss', $FuelSystemControlTypeID, $CultureID, $FuelSystemControlTypeName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $FuelSystemControlTypeID=$record['FuelSystemControlTypeID']; $FuelSystemControlTypeName=$record['FuelSystemControlTypeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from FuelSystemControlType where FuelSystemControlTypeID=?'))
    {
     if($stmt->bind_param('i', $FuelSystemControlTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $FuelSystemControlTypeID=$record['FuelSystemControlTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $FuelSystemControlTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update FuelSystemControlType set FuelSystemControlTypeName=?, CultureID=? where FuelSystemControlTypeID=?'))
    {
     if($stmt->bind_param('ssi', $FuelSystemControlTypeName, $CultureID, $FuelSystemControlTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $FuelSystemControlTypeID=$record['FuelSystemControlTypeID']; $FuelSystemControlTypeName=$record['FuelSystemControlTypeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

    
   case 'FuelSystemDesign':
       
    if($stmt=$db->conn->prepare('insert into FuelSystemDesign values(?,?,?)'))
    {
     if($stmt->bind_param('iss', $FuelSystemDesignID, $FuelSystemDesignName, $CultureID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $FuelSystemDesignID=$record['FuelSystemDesignID']; $FuelSystemDesignName=$record['FuelSystemDesignName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from FuelSystemDesign where FuelSystemDesignID=?'))
    {
     if($stmt->bind_param('i', $FuelSystemDesignID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $FuelSystemDesignID=$record['FuelSystemDesignID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $FuelSystemDesignID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update FuelSystemDesign set FuelSystemDesignName=?, CultureID=? where FuelSystemDesignID=?'))
    {
     if($stmt->bind_param('ssi', $FuelSystemDesignName, $CultureID, $FuelSystemDesignID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $FuelSystemDesignID=$record['FuelSystemDesignID']; $FuelSystemDesignName=$record['FuelSystemDesignName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

    
   case 'FuelType':
       
    if($stmt=$db->conn->prepare('insert into FuelType values(?,?,?)'))
    {
     if($stmt->bind_param('iss', $FuelTypeID, $FuelTypeName, $CultureID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $FuelTypeID=$record['FuelTypeID']; $FuelTypeName=$record['FuelTypeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from FuelType where FuelTypeID=?'))
    {
     if($stmt->bind_param('i', $FuelTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $FuelTypeID=$record['FuelTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $FuelTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update FuelType set FuelTypeName=?, CultureID=? where FuelTypeID=?'))
    {
     if($stmt->bind_param('ssi', $FuelTypeName, $CultureID, $FuelTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $FuelTypeID=$record['FuelTypeID']; $FuelTypeName=$record['FuelTypeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'IgnitionSystemType':
       
    if($stmt=$db->conn->prepare('insert into IgnitionSystemType values(?,?,?)'))
    {
     if($stmt->bind_param('iss', $IgnitionSystemTypeID, $CultureID, $IgnitionSystemTypeName))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $IgnitionSystemTypeID=$record['IgnitionSystemTypeID']; $IgnitionSystemTypeName=$record['IgnitionSystemTypeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from IgnitionSystemType where IgnitionSystemTypeID=?'))
    {
     if($stmt->bind_param('i', $IgnitionSystemTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $IgnitionSystemTypeID=$record['IgnitionSystemTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $IgnitionSystemTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update IgnitionSystemType set IgnitionSystemTypeName=?, CultureID=? where IgnitionSystemTypeID=?'))
    {
     if($stmt->bind_param('ssi', $IgnitionSystemTypeName, $CultureID, $IgnitionSystemTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $IgnitionSystemTypeID=$record['IgnitionSystemTypeID']; $IgnitionSystemTypeName=$record['IgnitionSystemTypeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'Make':
       
    if($stmt=$db->conn->prepare('insert into Make values(?,?,?)'))
    {
     $stmt->bind_param('iss', $MakeID, $MakeName, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $MakeID=$record['MakeID']; $MakeName=$record['MakeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Make where MakeID=?'))
    {
     if($stmt->bind_param('i', $MakeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $MakeID=$record['MakeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $MakeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Make set MakeName=?, ClultureID=? where MakeID=?'))
    {
     if($stmt->bind_param('ssi', $MakeName, $CultureID, $MakeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $MakeID=$record['MakeID']; $MakeName=$record['MakeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;
    
   case 'Mfr':

    if($stmt=$db->conn->prepare('insert into Mfr values(?,?,?)'))
    {
     $stmt->bind_param('iss', $MfrID, $MfrName, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $MfrID=$record['MfrID']; $MfrName=$record['MfrName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Mfr where MfrID=?'))
    {
     if($stmt->bind_param('i', $MfrID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $MfrID=$record['MfrID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $MfrID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Mfr set MfrName=?, CultureID=? where MfrID=?'))
    {
     if($stmt->bind_param('ssi', $MfrName, $CultureID, $MfrID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $MfrID=$record['MfrID']; $MfrName=$record['MfrName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'MfrBodyCode':

    if($stmt=$db->conn->prepare('insert into MfrBodyCode values(?,?,?)'))
    {
     $stmt->bind_param('iss', $MfrBodyCodeID, $MfrBodyCodeName, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $MfrBodyCodeID=$record['MfrBodyCodeID']; $MfrBodyCodeName=$record['MfrBodyCodeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from MfrBodyCode where MfrBodyCodeID=?'))
    {
     if($stmt->bind_param('i', $MfrBodyCodeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $MfrBodyCodeID=$record['MfrBodyCodeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $MfrBodyCodeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update MfrBodyCode set MfrBodyCodeName=?, CultureID=? where MfrBodyCodeID=?'))
    {
     if($stmt->bind_param('ssi', $MfrBodyCodeName, $CultureID, $MfrBodyCodeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $MfrBodyCodeID=$record['MfrBodyCodeID']; $MfrBodyCodeName=$record['MfrBodyCodeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

    
   case 'Model':       
    if($stmt=$db->conn->prepare('insert into Model values(?,?,?,?)'))
    {
     $stmt->bind_param('isis', $ModelID, $ModelName, $VehicleTypeID, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $ModelID=$record['ModelID']; $ModelName=$record['ModelName']; $VehicleTypeID=$record['VehicleTypeID']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Model where ModelID=?'))
    {
     if($stmt->bind_param('i', $ModelID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $ModelID=$record['ModelID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $ModelID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Model set ModelName=?,VehicleTypeID=?, ClutureID=? where ModelID=?'))
    {
     if($stmt->bind_param('sisi', $ModelName, $VehicleTypeID, $CultureID, $ModelID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $ModelID=$record['ModelID']; $ModelName=$record['ModelName']; $VehicleTypeID=$record['VehicleTypeID']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
              
    break;
    
   case 'PowerOutput':
       
    if($stmt=$db->conn->prepare('insert into PowerOutput values(?,?,?,?)'))
    {
     $stmt->bind_param('isss', $PowerOutputID, $HorsePower, $KilowattPower, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $PowerOutputID=$record['PowerOutputID']; $HorsePower=$record['HorsePower']; $KilowattPower=$record['KilowattPower']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from PowerOutput where PowerOutputID=?'))
    {
     if($stmt->bind_param('i', $PowerOutputID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $PowerOutputID=$record['PowerOutputID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $PowerOutputID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update PowerOutput set HorsePower=?,KilowattPower=?,CultureID=? where PowerOutputID=?'))
    {
     if($stmt->bind_param('sssi', $HorsePower, $KilowattPower, $CultureID, $PowerOutputID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $PowerOutputID=$record['PowerOutputID']; $HorsePower=$record['HorsePower']; $KilowattPower=$record['KilowattPower']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'PublicationStage':

    if($stmt=$db->conn->prepare('insert into PublicationStage values(?,?,?)'))
    {
     $stmt->bind_param('iss', $PublicationStageID, $PublicationStageName, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $PublicationStageID=$record['PublicationStageID']; $PublicationStageName=$record['PublicationStageName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from PublicationStage where PublicationStageID=?'))
    {
     if($stmt->bind_param('i', $PublicationStageID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $PublicationStageID=$record['PublicationStageID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $PublicationStageID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update PublicationStage set PublicationStageName=?, CultureID=? where PublicationStageID=?'))
    {
     if($stmt->bind_param('ssi', $PublicationStageName, $CultureID, $PublicationStageID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $PublicationStageID=$record['PublicationStageID']; $PublicationStageName=$record['PublicationStageName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }      
    break;

        
   case 'Region':
    if($stmt=$db->conn->prepare('insert into Region values(?,?,?,?)'))
    {
     $stmt->bind_param('isss', $RegionID, $RegionAbbr, $RegionName, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $RegionID=$record['RegionID']; $RegionAbbr=$record['RegionAbbr']; $RegionName=$record['RegionName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Region where RegionID=?'))
    {
     if($stmt->bind_param('i', $RegionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $RegionID=$record['RegionID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $RegionID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Region set RegionAbbr=?,RegionName=?, CultureID=? where RegionID=?'))
    {
     if($stmt->bind_param('sssi', $RegionAbbr, $RegionName, $CultureID, $RegionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $RegionID=$record['RegionID']; $RegionAbbr=$record['RegionAbbr']; $RegionName=$record['RegionName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

    
   case 'SpringType':
       
    if($stmt=$db->conn->prepare('insert into SpringType values(?,?)'))
    {
     $stmt->bind_param('is', $SpringTypeID, $SpringTypeName);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $SpringTypeID=$record['SpringTypeID']; $SpringTypeName=$record['SpringTypeName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from SpringType where SpringTypeID=?'))
    {
     if($stmt->bind_param('i', $SpringTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $SpringTypeID=$record['SpringTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $SpringTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update SpringType set SpringTypeName=? where SpringTypeID=?'))
    {
     if($stmt->bind_param('si', $SpringTypeName, $SpringTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $SpringTypeID=$record['SpringTypeID']; $SpringTypeName=$record['SpringTypeName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
       
    break;

   case 'SpringTypeConfig':
       
    if($stmt=$db->conn->prepare('insert into SpringTypeConfig values(?,?,?)'))
    {
     $stmt->bind_param('iii', $SpringTypeConfigID, $FrontSpringTypeID, $RearSpringTypeID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $SpringTypeConfigID=$record['SpringTypeConfigID']; $FrontSpringTypeID=$record['FrontSpringTypeID']; $RearSpringTypeID=$record['RearSpringTypeID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from SpringTypeConfig where SpringTypeConfigID=?'))
    {
     if($stmt->bind_param('i', $SpringTypeConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $SpringTypeConfigID=$record['SpringTypeConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $SpringTypeConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update SpringTypeConfig set FrontSpringTypeID=?,RearSpringTypeID=? where SpringTypeConfigID=?'))
    {
     if($stmt->bind_param('iii', $FrontSpringTypeID, $RearSpringTypeID, $SpringTypeConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $SpringTypeConfigID=$record['SpringTypeConfigID']; $FrontSpringTypeID=$record['FrontSpringTypeID']; $RearSpringTypeID=$record['RearSpringTypeID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;

   case 'SteeringConfig':
       
    if($stmt=$db->conn->prepare('insert into SteeringConfig values(?,?,?)'))
    {
     $stmt->bind_param('iii', $SteeringConfigID, $SteeringTypeID, $SteeringSystemID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $SteeringConfigID=$record['SteeringConfigID']; $SteeringTypeID=$record['SteeringTypeID']; $SteeringSystemID=$record['SteeringSystemID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from SteeringConfig where SteeringConfigID=?'))
    {
     if($stmt->bind_param('i', $SteeringConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $SteeringConfigID=$record['SteeringConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $SteeringConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update SteeringConfig set SteeringTypeID=?,SteeringSystemID=? where SteeringConfigID=?'))
    {
     if($stmt->bind_param('iii', $SteeringTypeID, $SteeringSystemID, $SteeringConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $SteeringConfigID=$record['SteeringConfigID']; $SteeringTypeID=$record['SteeringTypeID']; $SteeringSystemID=$record['SteeringSystemID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
       
    break;

   case 'SteeringSystem':
       
    if($stmt=$db->conn->prepare('insert into SteeringSystem values(?,?)'))
    {
     $stmt->bind_param('is', $SteeringSystemID, $SteeringSystemName);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $SteeringSystemID=$record['SteeringSystemID']; $SteeringSystemName=$record['SteeringSystemName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from SteeringSystem where SteeringSystemID=?'))
    {
     if($stmt->bind_param('i', $SteeringSystemID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $SteeringSystemID=$record['SteeringSystemID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $SteeringSystemID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update SteeringSystem set SteeringSystemName=? where SteeringSystemID=?'))
    {
     if($stmt->bind_param('si', $SteeringSystemName, $SteeringSystemID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $SteeringSystemID=$record['SteeringSystemID']; $SteeringSystemName=$record['SteeringSystemName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'SteeringType':

    if($stmt=$db->conn->prepare('insert into SteeringType values(?,?)'))
    {
     $stmt->bind_param('is', $SteeringTypeID, $SteeringTypeName);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $SteeringTypeID=$record['SteeringTypeID']; $SteeringTypeName=$record['SteeringTypeName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from SteeringType where SteeringTypeID=?'))
    {
     if($stmt->bind_param('i', $SteeringTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $SteeringTypeID=$record['SteeringTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $SteeringTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update SteeringType set SteeringTypeName=? where SteeringTypeID=?'))
    {
     if($stmt->bind_param('si', $SteeringTypeName, $SteeringTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $SteeringTypeID=$record['SteeringTypeID']; $SteeringTypeName=$record['SteeringTypeName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

    
   case 'SubModel':
    if($stmt=$db->conn->prepare('insert into SubModel values(?,?)'))
    {
     $stmt->bind_param('is', $SubModelID, $SubModelName);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $SubModelID=$record['SubModelID']; $SubModelName=$record['SubModelName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from SubModel where SubModelID=?'))
    {
     if($stmt->bind_param('i', $SubModelID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $SubModelID=$record['SubModelID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $SubModelID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update SubModel set SubModelName=? where SubModelID=?'))
    {
     if($stmt->bind_param('si', $SubModelName, $SubModelID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $SubModelID=$record['SubModelID']; $SubModelName=$record['SubModelName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
       
    break;

   case 'Transmission':
       
    if($stmt=$db->conn->prepare('insert into Transmission values(?,?,?,?,?)'))
    {
     $stmt->bind_param('iiiii', $TransmissionID, $TransmissionBaseID, $TransmissionMfrCodeID, $TransmissionElecControlledID, $TransmissionMfrID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $TransmissionID=$record['TransmissionID']; $TransmissionBaseID=$record['TransmissionBaseID']; $TransmissionMfrCodeID=$record['TransmissionMfrCodeID']; $TransmissionElecControlledID=$record['TransmissionElecControlledID']; $TransmissionMfrID=$record['TransmissionMfrID']; 
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Transmission where TransmissionID=?'))
    {
     if($stmt->bind_param('i', $TransmissionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $TransmissionID=$record['TransmissionID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $TransmissionID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Transmission set TransmissionBaseID=?,TransmissionMfrCodeID=?,TransmissionElecControlledID=?,TransmissionMfrID=? where TransmissionID=?'))
    {
     if($stmt->bind_param('iiiii', $TransmissionBaseID, $TransmissionMfrCodeID, $TransmissionElecControlledID, $TransmissionMfrID, $TransmissionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $TransmissionID=$record['TransmissionID']; $TransmissionBaseID=$record['TransmissionBaseID']; $TransmissionMfrCodeID=$record['TransmissionMfrCodeID']; $TransmissionElecControlledID=$record['TransmissionElecControlledID']; $TransmissionMfrID=$record['TransmissionMfrID']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'TransmissionBase':
       
    if($stmt=$db->conn->prepare('insert into TransmissionBase values(?,?,?,?)'))
    {
     $stmt->bind_param('iiii', $TransmissionBaseID, $TransmissionTypeID, $TransmissionNumSpeedsID, $TransmissionControlTypeID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $TransmissionBaseID=$record['TransmissionBaseID']; $TransmissionTypeID=$record['TransmissionTypeID']; $TransmissionNumSpeedsID=$record['TransmissionNumSpeedsID']; $TransmissionControlTypeID=$record['TransmissionControlTypeID'];  
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from TransmissionBase where TransmissionBaseID=?'))
    {
     if($stmt->bind_param('i', $TransmissionBaseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $TransmissionBaseID=$record['TransmissionBaseID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $TransmissionBaseID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update TransmissionBase set TransmissionTypeID=?, TransmissionNumSpeedsID=?, TransmissionControlTypeID=? where TransmissionBaseID=?'))
    {
     if($stmt->bind_param('iiii', $TransmissionTypeID, $TransmissionNumSpeedsID, $TransmissionControlTypeID, $TransmissionBaseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $TransmissionBaseID=$record['TransmissionBaseID']; $TransmissionTypeID=$record['TransmissionTypeID']; $TransmissionNumSpeedsID=$record['TransmissionNumSpeedsID']; $TransmissionControlTypeID=$record['TransmissionControlTypeID'];  
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
       
    break;
        
   case 'TransmissionControlType':
    if($stmt=$db->conn->prepare('insert into TransmissionControlType values(?,?)'))
    {
     $stmt->bind_param('is', $TransmissionControlTypeID, $TransmissionControlTypeName);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $TransmissionControlTypeID=$record['TransmissionControlTypeID']; $TransmissionControlTypeName=$record['TransmissionControlTypeName'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from TransmissionControlType where TransmissionControlTypeID=?'))
    {
     if($stmt->bind_param('i', $TransmissionControlTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $TransmissionControlTypeID=$record['TransmissionControlTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $TransmissionControlTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update TransmissionControlType set TransmissionControlTypeName=? where TransmissionControlTypeID=?'))
    {
     if($stmt->bind_param('si', $SteeringSystemName, $SteeringSystemID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $TransmissionControlTypeID=$record['TransmissionControlTypeID']; $TransmissionControlTypeName=$record['TransmissionControlTypeName'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
       
    break;

   case 'TransmissionMfrCode':
    if($stmt=$db->conn->prepare('insert into TransmissionMfrCode values(?,?)'))
    {
     $stmt->bind_param('is', $TransmissionMfrCodeID, $TransmissionMfrCode);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $TransmissionMfrCodeID=$record['TransmissionMfrCodeID']; $TransmissionMfrCode=$record['TransmissionMfrCode'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from TransmissionMfrCode where TransmissionMfrCodeID=?'))
    {
     if($stmt->bind_param('i', $TransmissionMfrCodeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $TransmissionMfrCodeID=$record['TransmissionMfrCodeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $TransmissionMfrCodeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update TransmissionMfrCode set TransmissionMfrCode=? where TransmissionMfrCodeID=?'))
    {
     if($stmt->bind_param('si', $TransmissionMfrCode, $TransmissionMfrCodeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $TransmissionMfrCodeID=$record['TransmissionMfrCodeID']; $TransmissionMfrCode=$record['TransmissionMfrCode'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'TransmissionNumSpeeds':
    if($stmt=$db->conn->prepare('insert into TransmissionNumSpeeds values(?,?)'))
    {
     $stmt->bind_param('is', $TransmissionNumSpeedsID, $TransmissionNumSpeeds);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $TransmissionNumSpeedsID=$record['TransmissionNumSpeedsID']; $TransmissionNumSpeeds=$record['TransmissionNumSpeeds'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from TransmissionNumSpeeds where TransmissionNumSpeedsID=?'))
    {
     if($stmt->bind_param('i', $TransmissionNumSpeedsID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $TransmissionNumSpeedsID=$record['TransmissionNumSpeedsID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $TransmissionNumSpeedsID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update TransmissionNumSpeeds set TransmissionNumSpeeds=? where TransmissionNumSpeedsID=?'))
    {
     if($stmt->bind_param('si', $TransmissionNumSpeeds, $TransmissionNumSpeedsID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $TransmissionNumSpeedsID=$record['TransmissionNumSpeedsID']; $TransmissionNumSpeeds=$record['TransmissionNumSpeeds'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
       
    break;

   case 'TransmissionType':
    if($stmt=$db->conn->prepare('insert into TransmissionType values(?,?,?)'))
    {
     $stmt->bind_param('iss', $TransmissionTypeID, $TransmissionTypeName, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $TransmissionTypeID=$record['TransmissionTypeID']; $TransmissionTypeName=$record['TransmissionTypeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from TransmissionType where TransmissionTypeID=?'))
    {
     if($stmt->bind_param('i', $TransmissionTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $TransmissionTypeID=$record['TransmissionTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $TransmissionTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update TransmissionType set TransmissionTypeName=?, CultureID=? where TransmissionTypeID=?'))
    {
     if($stmt->bind_param('ssi', $TransmissionTypeName, $CultureID, $TransmissionTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $TransmissionTypeID=$record['TransmissionTypeID']; $TransmissionTypeName=$record['TransmissionTypeName']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'VCdbChanges':
    break;

   case 'Valves':
    if($stmt=$db->conn->prepare('insert into Valves values(?,?,?)'))
    {
     $stmt->bind_param('iss', $ValvesID, $ValvesPerEngine, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $ValvesID=$record['ValvesID']; $ValvesPerEngine=$record['ValvesPerEngine']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Valves where ValvesID=?'))
    {
     if($stmt->bind_param('i', $ValvesID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $ValvesID=$record['ValvesID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $ValvesID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Valves set ValvesPerEngine=?, CultureID=? where ValvesID=?'))
    {
     if($stmt->bind_param('ssi', $ValvesPerEngine, $CultureID, $ValvesID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $ValvesID=$record['ValvesID']; $ValvesPerEngine=$record['ValvesPerEngine']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;
    
   case 'Vehicle':
    if($stmt=$db->conn->prepare('insert into Vehicle values(?,?,?,?,?)'))
    {
     $stmt->bind_param('iiiii', $VehicleID, $BaseVehicleID, $SubmodelID, $RegionID, $PublicationStageID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert    
        $VehicleID=$record['VehicleID']; 
        $BaseVehicleID=$record['BaseVehicleID']; 
        $SubmodelID=$record['SubModelID']; 
        $RegionID=$record['RegionID']; 
        $PublicationStageID=$record['PublicationStageID']; 
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Vehicle where VehicleID=?'))
    {
     if($stmt->bind_param('i', $VehicleID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleID=$record['VehicleID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update Vehicle set BaseVehicleID=?, SubmodelID=?, RegionID=?, PublicationStageID=? where VehicleID=?'))
    {
     if($stmt->bind_param('iiiii', $BaseVehicleID, $SubmodelID, $RegionID, $PublicationStageID, $VehicleID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update    
        $VehicleID=$record['VehicleID']; 
        $BaseVehicleID=$record['BaseVehicleID']; 
        $SubmodelID=$record['SubModelID']; 
        $RegionID=$record['RegionID']; 
        $PublicationStageID=$record['PublicationStageID']; 
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

    
    
   case 'VehicleToBedConfig':
    if($stmt=$db->conn->prepare('insert into VehicleToBedConfig values(?,?,?)'))
    {
     $stmt->bind_param('iii', $VehicleToBedConfigID, $VehicleID, $BedConfigID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToBedConfigID=$record['VehicleToBedConfigID']; $VehicleID=$record['VehicleID']; $BedConfigID=$record['BedConfigID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToBedConfig where VehicleToBedConfigID=?'))
    {
     if($stmt->bind_param('i', $VehicleToBedConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToBedConfigID=$record['VehicleToBedConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToBedConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToBedConfig set VehicleID=?, BedConfigID=? where VehicleToBedConfigID=?'))
    {
     if($stmt->bind_param('iii', $VehicleID, $BedConfigID, $VehicleToBedConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToBedConfigID=$record['VehicleToBedConfigID']; $VehicleID=$record['VehicleID']; $BedConfigID=$record['BedConfigID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'VehicleToBodyConfig':
    if($stmt=$db->conn->prepare('insert into VehicleToBodyConfig values(?,?,?,?,?,?)'))
    {
     $stmt->bind_param('iiiiii', $VehicleToBodyConfigID,$VehicleID,$WheelBaseID,$BedConfigID,$BodyStyleConfigID,$MfrBodyCodeID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToBodyConfigID=$record['VehicleToBodyConfigID']; $VehicleID=$record['VehicleID']; $WheelBaseID=$record['WheelBaseID']; $BedConfigID=$record['BedConfigID']; $BodyStyleConfigID=$record['BodyStyleConfigID']; $MfrBodyCodeID=$record['MfrBodyCodeID'];       
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToBodyConfig where VehicleToBodyConfigID=?'))
    {
     if($stmt->bind_param('i', $VehicleToBodyConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToBodyConfigID=$record['VehicleToBodyConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToBodyConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToBodyConfig set  VehicleID=?,WheelBaseID=?,BedConfigID=?,BodyStyleConfigID=?,MfrBodyCodeID=? where VehicleToBodyConfigID=?'))
    {
     if($stmt->bind_param('iiiiii',$VehicleID,$WheelBaseID,$BedConfigID,$BodyStyleConfigID,$MfrBodyCodeID,$VehicleToBodyConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToBodyConfigID=$record['VehicleToBodyConfigID']; $VehicleID=$record['VehicleID']; $WheelBaseID=$record['WheelBaseID']; $BedConfigID=$record['BedConfigID']; $BodyStyleConfigID=$record['BodyStyleConfigID']; $MfrBodyCodeID=$record['MfrBodyCodeID'];       
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

    
    
   case 'VehicleToBodyStyleConfig':
    if($stmt=$db->conn->prepare('insert into VehicleToBodyStyleConfig values(?,?,?)'))
    {
     $stmt->bind_param('iii', $VehicleToBodyStyleConfigID, $VehicleID, $BodyStyleConfigID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToBodyStyleConfigID=$record['VehicleToBodyStyleConfigID']; $VehicleID=$record['VehicleID']; $BodyStyleConfigID=$record['BodyStyleConfigID']; 
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToBodyStyleConfig where VehicleToBodyStyleConfigID=?'))
    {
     if($stmt->bind_param('i', $VehicleToBodyStyleConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToBodyStyleConfigID=$record['VehicleToBodyStyleConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToBodyStyleConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToBodyStyleConfig set VehicleID=?, BodyStyleConfigID=? where VehicleToBodyStyleConfigID=?'))
    {
     if($stmt->bind_param('iii', $VehicleID, $BodyStyleConfigID, $VehicleToBodyStyleConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToBodyStyleConfigID=$record['VehicleToBodyStyleConfigID']; $VehicleID=$record['VehicleID']; $BodyStyleConfigID=$record['BodyStyleConfigID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }              
    break;
    
    
   case 'VehicleToBrakeConfig':
    if($stmt=$db->conn->prepare('insert into VehicleToBrakeConfig values(?,?,?)'))
    {
     $stmt->bind_param('iii', $VehicleToBrakeConfigID, $VehicleID, $BrakeConfigID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToBrakeConfigID=$record['VehicleToBrakeConfigID']; $VehicleID=$record['VehicleID']; $BrakeConfigID=$record['BrakeConfigID']; $Source=$record['Source'];      
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToBrakeConfig where VehicleToBrakeConfigID=?'))
    {
     if($stmt->bind_param('i', $VehicleToBrakeConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToBrakeConfigID=$record['VehicleToBrakeConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToBrakeConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToBrakeConfig set VehicleID=?, BrakeConfigID=? where VehicleToBrakeConfigID=?'))
    {
     if($stmt->bind_param('iii', $VehicleID, $BrakeConfigID, $VehicleToBrakeConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToBrakeConfigID=$record['VehicleToBrakeConfigID']; $VehicleID=$record['VehicleID']; $BrakeConfigID=$record['BrakeConfigID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }              
    break;

   case 'VehicleToClass':
    if($stmt=$db->conn->prepare('insert into VehicleToClass values(?,?,?)'))
    {
     $stmt->bind_param('iii', $VehicleToClassID, $VehicleID, $ClassID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToClassID=$record['VehicleToClassID']; $VehicleID=$record['VehicleID']; $ClassID=$record['ClassID']; $Source=$record['Source'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToClass where VehicleToClassID=?'))
    {
     if($stmt->bind_param('i', $VehicleToClassID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToClassID=$record['VehicleToClassID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToClassID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToClass set VehicleID=?, ClassID=? where VehicleToClassID=?'))
    {
     if($stmt->bind_param('iii', $VehicleID, $ClassID, $VehicleToClassID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToClassID=$record['VehicleToClassID']; $VehicleID=$record['VehicleID']; $ClassID=$record['ClassID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'VehicleToDriveType':
    if($stmt=$db->conn->prepare('insert into VehicleToDriveType values(?,?,?)'))
    {
     $stmt->bind_param('iii', $VehicleToDriveTypeID, $VehicleID, $DriveTypeID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToDriveTypeID=$record['VehicleToDriveTypeID']; $VehicleID=$record['VehicleID']; $DriveTypeID=$record['DriveTypeID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToDriveType where VehicleToDriveTypeID=?'))
    {
     if($stmt->bind_param('i', $VehicleToDriveTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToDriveTypeID=$record['VehicleToDriveTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToDriveTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToDriveType set VehicleID=?, DriveTypeID=? where VehicleToDriveTypeID=?'))
    {
     if($stmt->bind_param('iii', $VehicleID, $DriveTypeID, $VehicleToDriveTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToDriveTypeID=$record['VehicleToDriveTypeID']; $VehicleID=$record['VehicleID']; $DriveTypeID=$record['DriveTypeID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }   
    break;
    
   case 'VehicleToEngineConfig':
    if($stmt=$db->conn->prepare('insert into VehicleToEngineConfig values(?,?,?)'))
    {
     $stmt->bind_param('iii', $VehicleToEngineConfigID, $VehicleID, $EngineConfigID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToEngineConfigID=$record['VehicleToEngineConfigID']; $VehicleID=$record['VehicleID']; $EngineConfigID=$record['EngineConfigID']; $Source=$record['Source'];      
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToEngineConfig where VehicleToEngineConfigID=?'))
    {
     if($stmt->bind_param('i', $VehicleToEngineConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToEngineConfigID=$record['VehicleToEngineConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToEngineConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToEngineConfig set VehicleID=?, EngineConfigID=? where VehicleToEngineConfigID=?'))
    {
     if($stmt->bind_param('iii', $VehicleID, $EngineConfigID, $VehicleToEngineConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToEngineConfigID=$record['VehicleToEngineConfigID']; $VehicleID=$record['VehicleID']; $EngineConfigID=$record['EngineConfigID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }
    break;

   case 'VehicleToMfrBodyCode':
    if($stmt=$db->conn->prepare('insert into VehicleToMfrBodyCode values(?,?,?)'))
    {
     $stmt->bind_param('iii', $VehicleToMfrBodyCodeID, $VehicleID, $MfrBodyCodeID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToMfrBodyCodeID=$record['VehicleToMfrBodyCodeID']; $VehicleID=$record['VehicleID']; $MfrBodyCodeID=$record['MfrBodyCodeID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToMfrBodyCode where VehicleToMfrBodyCodeID=?'))
    {
     if($stmt->bind_param('i', $VehicleToMfrBodyCodeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToMfrBodyCodeID=$record['VehicleToMfrBodyCodeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToMfrBodyCodeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToMfrBodyCode set VehicleID=?, MfrBodyCodeID=? where VehicleToMfrBodyCodeID=?'))
    {
     if($stmt->bind_param('iii', $VehicleID, $MfrBodyCodeID, $VehicleToMfrBodyCodeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToMfrBodyCodeID=$record['VehicleToMfrBodyCodeID']; $VehicleID=$record['VehicleID']; $MfrBodyCodeID=$record['MfrBodyCodeID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }              
    break;

   case 'VehicleToSpringTypeConfig':
    if($stmt=$db->conn->prepare('insert into VehicleToSpringTypeConfig values(?,?,?)'))
    {
     $stmt->bind_param('iii', $VehicleToSpringTypeConfigID, $VehicleID, $SpringTypeConfigID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToSpringTypeConfigID=$record['VehicleToSpringTypeConfigID']; $VehicleID=$record['VehicleID']; $SpringTypeConfigID=$record['SpringTypeConfigID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToSpringTypeConfig where VehicleToSpringTypeConfigID=?'))
    {
     if($stmt->bind_param('i', $VehicleToSpringTypeConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToSpringTypeConfigID=$record['VehicleToSpringTypeConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToSpringTypeConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToSpringTypeConfig set VehicleID=?, SpringTypeConfigID=? where VehicleToSpringTypeConfigID=?'))
    {
     if($stmt->bind_param('iii', $VehicleID, $SpringTypeConfigID, $VehicleToSpringTypeConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToSpringTypeConfigID=$record['VehicleToSpringTypeConfigID']; $VehicleID=$record['VehicleID']; $SpringTypeConfigID=$record['SpringTypeConfigID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'VehicleToSteeringConfig':
    if($stmt=$db->conn->prepare('insert into VehicleToSteeringConfig values(?,?,?)'))
    {
     $stmt->bind_param('iii', $VehicleToSteeringConfigID, $VehicleID, $SteeringConfigID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToSteeringConfigID=$record['VehicleToSteeringConfigID']; $VehicleID=$record['VehicleID']; $SteeringConfigID=$record['SteeringConfigID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToSteeringConfig where VehicleToSteeringConfigID=?'))
    {
     if($stmt->bind_param('i', $VehicleToSteeringConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToSteeringConfigID=$record['VehicleToSteeringConfigID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToSteeringConfigID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToSteeringConfig set VehicleID=?, SteeringConfigID=? where VehicleToSteeringConfigID=?'))
    {
     if($stmt->bind_param('iii', $VehicleID, $SteeringConfigID, $VehicleToSteeringConfigID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToSteeringConfigID=$record['VehicleToSteeringConfigID']; $VehicleID=$record['VehicleID']; $SteeringConfigID=$record['SteeringConfigID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }  
    break;

   case 'VehicleToTransmission':
    if($stmt=$db->conn->prepare('insert into VehicleToTransmission values(?,?,?)'))
    {
     $stmt->bind_param('iii', $VehicleToTransmissionID, $VehicleID, $TransmissionID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToTransmissionID=$record['VehicleToTransmissionID']; $VehicleID=$record['VehicleID']; $TransmissionID=$record['TransmissionID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToTransmission where VehicleToTransmissionID=?'))
    {
     if($stmt->bind_param('i', $VehicleToTransmissionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToTransmissionID=$record['VehicleToTransmissionID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToTransmissionID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToTransmission set VehicleID=?, TransmissionID=? where VehicleToTransmissionID=?'))
    {
     if($stmt->bind_param('iii', $VehicleID, $TransmissionID, $VehicleToTransmissionID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToTransmissionID=$record['VehicleToTransmissionID']; $VehicleID=$record['VehicleID']; $TransmissionID=$record['TransmissionID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
       
    break;

   case 'VehicleToWheelbase':
    if($stmt=$db->conn->prepare('insert into VehicleToWheelbase values(?,?,?)'))
    {
     $stmt->bind_param('iii', $VehicleToWheelbaseID, $VehicleID, $WheelbaseID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleToWheelbaseID=$record['VehicleToWheelbaseID']; $VehicleID=$record['VehicleID']; $WheelbaseID=$record['WheelbaseID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleToWheelbase where VehicleToWheelbaseID=?'))
    {
     if($stmt->bind_param('i', $VehicleToWheelbaseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleToWheelbaseID=$record['VehicleToWheelbaseID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleToWheelbaseID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleToWheelbase set VehicleID=?, WheelbaseID=? where VehicleToWheelbaseID=?'))
    {
     if($stmt->bind_param('iii', $VehicleID, $WheelbaseID, $VehicleToWheelbaseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleToWheelbaseID=$record['VehicleToWheelbaseID']; $VehicleID=$record['VehicleID']; $WheelbaseID=$record['WheelbaseID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'VehicleType':
    if($stmt=$db->conn->prepare('insert into VehicleType values(?,?,?,?)'))
    {
     $stmt->bind_param('isis', $VehicleTypeID, $VehicleTypeName, $VehicleTypeGroupID, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleTypeID=$record['VehicleTypeID']; $VehicleTypeName=$record['VehicleTypeName']; $VehicleTypeGroupID=$record['VehicleTypeGroupID']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleType where VehicleTypeID=?'))
    {
     if($stmt->bind_param('i', $VehicleTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleTypeID=$record['VehicleTypeID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleTypeID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleType set VehicleTypeName=?, VehicleTypeGroupID=?, CultureID=? where VehicleTypeID=?'))
    {
     if($stmt->bind_param('sisi', $VehicleTypeName, $VehicleTypeGroupID, $CultureID, $VehicleTypeID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleTypeID=$record['VehicleTypeID']; $VehicleTypeName=$record['VehicleTypeName']; $VehicleTypeGroupID=$record['VehicleTypeGroupID']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }  
    break;

   case 'VehicleTypeGroup':
    if($stmt=$db->conn->prepare('insert into VehicleTypeGroup values(?,?,?,?)'))
    {
     $stmt->bind_param('isss', $VehicleTypeGroupID, $VehicleTypeGroupName, $VehicleTypeGroupDescription, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $VehicleTypeGroupID=$record['VehicleTypeGroupID']; $VehicleTypeGroupName=$record['VehicleTypeGroupName']; $VehicleTypeGroupDescription=$record['VehicleTypeGroupDescription']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from VehicleTypeGroup where VehicleTypeGroupID=?'))
    {
     if($stmt->bind_param('i', $VehicleTypeGroupID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $VehicleTypeGroupID=$record['VehicleTypeGroupID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $VehicleTypeGroupID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update VehicleTypeGroup set VehicleTypeGroupName=?, VehicleTypeGroupDescription=?, CultureID=? where VehicleTypeGroupID=?'))
    {
     if($stmt->bind_param('sssi', $VehicleTypeGroupName, $VehicleTypeGroupDescription, $CultureID, $VehicleTypeGroupID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $VehicleTypeGroupID=$record['VehicleTypeGroupID']; $VehicleTypeGroupName=$record['VehicleTypeGroupName']; $VehicleTypeGroupDescription=$record['VehicleTypeGroupDescription']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'WheelBase':
    if($stmt=$db->conn->prepare('insert into WheelBase values(?,?,?,?)'))
    {
     $stmt->bind_param('isss', $WheelBaseID, $WheelBase, $WheelBaseMetric, $CultureID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $WheelBaseID=$record['WheelBaseID']; $WheelBase=$record['WheelBase']; $WheelBaseMetric=$record['WheelBaseMetric']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from WheelBase where WheelBaseID=?'))
    {
     if($stmt->bind_param('i', $WheelBaseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $WheelBaseID=$record['WheelBaseID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $WheelBaseID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
      }
     }
    }
 
    if($stmt=$db->conn->prepare('update WheelBase set WheelBase=?, WheelBaseMetric=?, CultureID=? where WheelBaseID=?'))
    {
     if($stmt->bind_param('sssi', $WheelBase, $WheelBaseMetric, $CultureID, $WheelBaseID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(array_key_exists($record[$keyfieldname],$existingids))
       {// key found in local tables - do the update
        $WheelBaseID=$record['WheelBaseID']; $WheelBase=$record['WheelBase']; $WheelBaseMetric=$record['WheelBaseMetric']; $CultureID=$record['CultureID'];
        if($stmt->execute()){$this->updatecount++;}
       }
      }
     }
    }       
    break;

   case 'Year':
    if($stmt=$db->conn->prepare('insert into Year values(?)'))
    {
     $stmt->bind_param('i', $YearID);
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10){continue;} // skip records that are deleted
       if(!array_key_exists($record[$keyfieldname],$existingids))
       {// key not found in local tables - do the insert
        $YearID=$record['YearID'];
        if($stmt->execute()){$this->insertcount++;}
       }
      }
     }
    }
    
    if($stmt=$db->conn->prepare('delete from Year where YearID=?'))
    {
     if($stmt->bind_param('i', $YearID))
     {
      foreach($records as $record)
      {
       if(isset($record['EndDateTime']) && strlen($record['EndDateTime'])>=10)
       {// a date present implies the record was deleted ex: 11/15/2024 00:50:19          
        if(array_key_exists($record[$keyfieldname],$existingids))
        {// key found in local tables - do the delete
         $YearID=$record['YearID']; if($stmt->execute()){$this->deletecount++;}
        }
       }
      }   
      foreach($localorphanids as $localorphanid)
      {
       $YearID=$localorphanid; if($stmt->execute()){$this->deletecount++; $this->deleteorphancount++;}
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
