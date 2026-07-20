<?php
include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/vcdbAPIClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/vcdbClass.php');
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/configGetClass.php');
include_once(__DIR__.'/class/configSetClass.php');

$starttime=time();
$pim = new pim();
$vcdb=new vcdb();
$logs = new logs();
$configGet = new configGet();
$configSet = new configSet();

$existinglocks=$pim->getLocksByType('UPDATEFROMVCDBAPI');
if(count($existinglocks))
{
 $logs->logSystemEvent('AutoCare API Client', 0, 'updateVCdbFromACAAPI found lock record (id:'.$existinglocks[0]['id'].') and declined to run');
 exit; 
}
$mylockid=$pim->addLock('UPDATEFROMVCDBAPI', 'pid:'. getmypid());

$daysback=7;
$sincedate=false; //'2024-12-01'; // set this data to false to query the API for all records in named tables
$asofdate=date('Y-m-d',time()-(25*3600));
$tableattemptcount=0;
$totalfails=0;

$vcdbapi=new vcdbapi;
$vcdbapi->debug=false;// debug is useful for manual command calls. A bunch of stuff is echoed to the console
if($configGet->getConfigValue('VCdbAPIdebugMode','0')=='1')
{
 $vcdbapi->debug=false;
}

$vcdbapi->loggingverbosity=intval($configGet->getConfigValue('VCdbAPIloggingVerbosity','1')); // (1-10) Ten is the most verbose 

$vcdbapi->failedsync=false;

$pickupattablename='';
// process any command-line args (in a manual call situation)
foreach($argv as $i=>$arg)
{
 if($arg=='-pickup' && $i<$argc && in_array($argv[$i+1], $vcdbapi->tableslist))
 {
  $pickupattablename=$argv[$i+1];     
 }
 if($arg=='-debug'){ $vcdbapi->debug=true;}
}
if($vcdbapi->debug && $pickupattablename!=''){echo 'pickingup at table: '.$pickupattablename."\n";}
 
$lastsync=intval($configGet->getConfigValue('lastSuccessfulVCdbAPIsync',0));

if($lastsync)
{
 $sincedate=date('Y-m-d', intval($lastsync)-(24*3600*$daysback));  // set sincedate to [daysback] days before last sync
}
else 
{// no history of last successful sync - setup for full download
 $sincedate=false;
}

$clearfirst=false;  // deletes all rec in every named table before engaging with the server - used for testing/debugging work
if($configGet->getConfigValue('VCdbAPIclearFirst','0')=='1')
{
 $clearfirst=true;
 $configSet->setConfigValue('VCdbAPIclearFirst', '0');
}

$deletelocalorphans=false; // cause records in each local table (identified by primary keys) to be deleted if they are not present in API results 

$vcdbapi->clientid=$configGet->getConfigValue('AutoCareAPIclientid');
$vcdbapi->clientsecret=$configGet->getConfigValue('AutoCareAPIclientsecret');
$vcdbapi->username=$configGet->getConfigValue('AutoCareAPIusername');
$vcdbapi->password=$configGet->getConfigValue('AutoCareAPIpassword');

$configSet->setConfigValue('AutoCareAPIsuccessThroughVCdbTable', '');

$vcdbapi->getAccessToken();
$vcdbapi->pagelimit=0;

if($vcdbapi->loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'VCdb API sync started. SinceDate='.$sincedate.', AsOfDate='.$asofdate); }

if($vcdbapi->activetoken)
{
 if($vcdbapi->loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'Got Token ('.substr($vcdbapi->token,0,20).'...). Expires in: '.$vcdbapi->tokenLife().' seconds'); }
//            $vcdbapi->getDatabaseses();
//            print_r($vcdbapi->databases);
//            $tables=$vcdbapi->getTables('VCDB');
//            print_r($tables);

 $configSet->setConfigValue('lastSuccessfulVCdbAPIsync', '0');
 
 if($clearfirst)
 {
  if($vcdbapi->loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0,'Clearing '.count($vcdbapi->tableslist).' local tables');}
  foreach($vcdbapi->tableslist as $tablename)
  {
   $timetemp=time();
   $vcdbapi->clearTable($tablename);
   if($vcdbapi->loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'Cleared Local Table '.$tablename.' in '.(time()-$timetemp).' seconds');}
  }
 }

 $totalinserts=0; $totalupdates=0; $totaldeletes=0;
 
 $reachedpickuptable=false;
  
 foreach($vcdbapi->tableslist as $tablename)
 {
  // if we are doing a pickup at a specific table, skip forward till we find it in the list
  if(!$reachedpickuptable && $pickupattablename!='')
  {
   if($tablename==$pickupattablename)
   {
    $reachedpickuptable=true;
   }
   else
   {
    continue;
   }
  }
     
     
     
  $timetemp=time();

  $totalinserts+=$vcdbapi->insertcount;
  $totalupdates+=$vcdbapi->updatecount;
  $totaldeletes+=$vcdbapi->deletecount;
  
  $vcdbapi->insertcount=0;
  $vcdbapi->updatecount=0;
  $vcdbapi->deletecount=0;
  $vcdbapi->deleteorphancount=0;
  
  if($vcdbapi->tokenLife()<$vcdbapi->tokenrefreshthreshold)
  {
   if($vcdbapi->loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Active token expires in: '.$vcdbapi->tokenLife().' seconds. Requesting new token');}
   
   $vcdbapi->getAccessToken();
   if(!$vcdbapi->activetoken)
   {
    $logs->logSystemEvent('AutoCare API Client', 0,'Token refresh rquest failed after '.$vcdbapi->tokenrefreshcount.' refreshes. Exiting Process. Curl response:'.$vcdbapi->errormessage);
    break;
   }

   if($vcdbapi->loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Successful request of new token. Expires in '.$vcdbapi->tokenLife().' seconds');}
  }

  $tableattemptcount=0; $localwritetime=0;
  while(true)
  {
   $tableattemptcount++;
   if($vcdbapi->loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Starting request of '.$tablename.' (attempt '.$tableattemptcount.')');}
   
   if($vcdbapi->getRecords('VCDB',$tablename,'en-US',$sincedate,$asofdate))
   {     
    if($vcdbapi->loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Finished getting ('.count($vcdbapi->records).') records of '.$tablename.'. Starting local processing of results.');}
              
    // we're about to go dark for potentially 20 minutes writing the collected data to the local cache - refresh the token just in case.
    $vcdbapi->getAccessToken();
    if(!$vcdbapi->activetoken)
    {
     $logs->logSystemEvent('AutoCare API Client', 0,'updateVCdbFromACAAPI.php - Token refresh rquest failed on table '.$tablename.' after '.$vcdbapi->tokenrefreshcount.' refreshes. Exiting Process. Curl response:'.$vcdbapi->errormessage);
     $vcdbapi->failedsync=true;
     break;
    }
       
    $localwritetime=time();
    $vcdbapi->populateTable($tablename, $vcdbapi->records, $deletelocalorphans);
    $localwritetime=time()-$localwritetime;
    if($vcdbapi->loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Finished processing ('.count($vcdbapi->records).') records of '.$tablename.' into local cache - took '.$localwritetime.' seconds.');}
    $configSet->setConfigValue('AutoCareAPIsuccessThroughVCdbTable', $tablename);
    break; // this breaks the endless "while"        
   }
   else
   {// non-success getting records for the current table
    $totalfails++;
    if($vcdbapi->loggingverbosity>0){$logs->logSystemEvent('AutoCare API Client',0,'Failure getting records for table: '.$tablename.'. http status: '.$vcdbapi->httpstatus);}    
    if($tableattemptcount>=5)
    {
     $vcdbapi->failedsync=true;
     break; // this breaks the endless "while"        
    }    
   }
  }
  
  if($vcdbapi->failedsync)
  {
   if($vcdbapi->loggingverbosity>0){$logs->logSystemEvent('AutoCare API Client',0,'Gave up on: '.$tablename.". after ".$tableattemptcount." attempts. Terminating process.");}
   break; // this breaks the foreach tables list 
  }
  
  if($vcdbapi->loggingverbosity>0){$logs->logSystemEvent('AutoCare API Client', 0, $tablename.' - inserts: '.$vcdbapi->insertcount.', updates: '.$vcdbapi->updatecount.', deletes: '.$vcdbapi->deletecount.', orphan deletes: '.$vcdbapi->deleteorphancount.' in '.(time()-$timetemp).' seconds');}
 }
 
 $runtime=time()-$starttime;
 if($vcdbapi->failedsync)
 {
  $logs->logSystemEvent('AutoCare API Client', 0, 'VCdb API sync failed in '.$runtime.' seconds. '.$vcdbapi->totalcalls.' API calls, '.$vcdbapi->tokenrefreshcount.' token requests, '.$totalinserts.' inserts, '.$totalupdates.' updates, '.$totaldeletes.' deletes. Total failed api calls: '.$totalfails.'. SinceDate used:'.$sincedate);
 }
 else
 {
  if($vcdbapi->loggingverbosity>0){$logs->logSystemEvent('AutoCare API Client',0,'Successful sync - total run time: '.$runtime.' seconds. Total API calls: '.$vcdbapi->totalcalls.'. Token refreshes:'.$vcdbapi->tokenrefreshcount.'. Total failed api calls: '.$totalfails);}
  $configSet->setConfigValue('lastSuccessfulVCdbAPIsync', time());
  $vcdbapi->setVersionDate(date('Y-m-d'));     
 }
 
}
else
{
 $logs->logSystemEvent('AutoCare API Client', 0, 'VCdb API sync failed - no access token granted (http status: '.$vcdbapi->httpstatus.', curl errormessage:'.$vcdbapi->errormessage.')'); 
}

// VCdb integrity check
$integrityissues=$vcdb->integrityCheck();
if(count($integrityissues)==0)
{
 $logs->logSystemEvent('AutoCare API Client', 0, 'VCdb integrity check clean');
}
else
{
 $logs->logSystemEvent('AutoCare API Client', 0, 'VCdb integrity check failed: '.implode(',',$integrityissues));
}

$pim->removeLockById($mylockid);