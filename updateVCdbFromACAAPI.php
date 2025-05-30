<?php
include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/vcdbAPIClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/configGetClass.php');
include_once(__DIR__.'/class/configSetClass.php');


$starttime=time();
$pim = new pim();
$logs = new logs();
$configGet = new configGet();
$configSet = new configSet();


$daysback=7;
$tokenlowlifeseconds=3000; //every time a new records page is requested, the remaining life of the active token is checked. If lif is less than this number, a nre token is requested 
$tokenrefreshlimit=30; // how many new-token requests are allowed in this session (this php script execution)
$loggingverbosity=1; // (1-10) Ten is the most verbose 
//$sincedate=false; //'2024-12-01'; // set this data to false to query the API for all records in named tables

$lastsync=$configGet->getConfigValue('lastSuccessfulVCdbAPIsync');
if($lastsync)
{
 $sincedate=date('Y-m-d', intval($lastsync)-(24*3600*$daysback));  // set sincedate to [daysback] days before last sync
}
else 
{// no history of last successful sync - setup for full download
 $sincedate=false;
}


$clearfirst=false;  // deletes all rec in every named table before engaging with the server - used for testing/debugging work
$deletelocalorphans=false; // cause records in each local table (identified by primary keys) to be deleted if they are not present in API results 

$vcdbapi=new vcdbapi;

$vcdbapi->clientid=$configGet->getConfigValue('AutoCareAPIclientid');
$vcdbapi->clientsecret=$configGet->getConfigValue('AutoCareAPIclientsecret');
$vcdbapi->username=$configGet->getConfigValue('AutoCareAPIusername');
$vcdbapi->password=$configGet->getConfigValue('AutoCareAPIpassword');
        
$vcdbapi->getAccessToken();
$vcdbapi->pagelimit=0;
$vcdbapi->debug=false;// debug is useful for manual command calls. A bunch of stuff is echoed to the console

if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'VCdb API sync started'); }

if($vcdbapi->activetoken)
{
 if($vcdbapi->debug){echo "Got API auth token\r\n";}
 if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'Got Token ('.substr($vcdbapi->token,0,20).'...). Expires in: '.$vcdbapi->tokenLife().' seconds'); }
//            $vcdbapi->getDatabaseses();
//            print_r($vcdbapi->databases);
//            $tables=$vcdbapi->getTables('VCDB');
//            print_r($tables);

 if($clearfirst)
 {
  if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0,'Clearing '.count($vcdbapi->tableslist).' local tables');}
  foreach($vcdbapi->tableslist as $tablename)
  {
   $timetemp=time();
   if($vcdbapi->debug){echo 'Clearing Local Table '.$tablename."...";}
   $vcdbapi->clearTable($tablename);
   if($vcdbapi->debug){echo ' Done - '.(time()-$timetemp)." seconds\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0, 'Cleared Local Table '.$tablename.' in '.(time()-$timetemp).' seconds');}
  }
 }

 $totalinserts=0; $totalupdates=0; $totaldeletes=0;
 
 foreach($vcdbapi->tableslist as $tablename)
 {
  $timetemp=time();

  $totalinserts+=$vcdbapi->insertcount;
  $totalupdates+=$vcdbapi->updatecount;
  $totaldeletes+=$vcdbapi->deletecount;
  
  $vcdbapi->insertcount=0;
  $vcdbapi->updatecount=0;
  $vcdbapi->deletecount=0;
  $vcdbapi->deleteorphancount=0;
  
  if($vcdbapi->tokenLife()<$tokenlowlifeseconds)
  {
   if($vcdbapi->tokenrefreshcount>=$tokenrefreshlimit)
   {
    if($vcdbapi->debug){echo " Local token-refresh limit reached. Terminating Process.\r\n";}
    $logs->logSystemEvent('AutoCare API Client', 0,'Local token-refresh limit reached. Exiting Process.');
    break;       
   }
   
   if($vcdbapi->debug){echo "  Active token expires in: ".$vcdbapi->tokenLife()." seconds. Requesting new token...\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Active token expires in: '.$vcdbapi->tokenLife().' seconds. Requesting new token');}
   
   $vcdbapi->activetoken=false;
   $vcdbapi->getAccessToken();
   if(!$vcdbapi->activetoken)
   {
    if($vcdbapi->debug){echo " Request failed. Terminating process.\r\n";}
    $logs->logSystemEvent('AutoCare API Client', 0,'Token refresh rquest failed. Exiting Process.');    
    break;
   }
   
   if($vcdbapi->debug){echo " Success. New token expires in ".$vcdbapi->tokenLife()." seconds\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Successful request of new token. Expires in '.$vcdbapi->tokenLife().' seconds');}  
  }
  
  if($vcdbapi->debug){echo ' '.$tablename.'...';}
  $success=$vcdbapi->getRecords('VCDB',$tablename,'en-US',$sincedate);
  //print_r($vcdbapi->records);
  $vcdbapi->populateTable($tablename, $vcdbapi->records, $deletelocalorphans);
  if($vcdbapi->debug){echo ' inserts: '.$vcdbapi->insertcount.', updates:'.$vcdbapi->updatecount.', deletes: '.$vcdbapi->deletecount.', orphan deletes: '.$vcdbapi->deleteorphancount.' on local database records in '.(time()-$timetemp)." seconds\r\n";}
  if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, $tablename.' - inserts: '.$vcdbapi->insertcount.', updates: '.$vcdbapi->updatecount.', deletes: '.$vcdbapi->deletecount.', orphan deletes: '.$vcdbapi->deleteorphancount.' in '.(time()-$timetemp).' seconds');}
 }
 
 $runtime=time()-$starttime;
 if($vcdbapi->debug){echo 'Total run time: '.$runtime.' seconds. Total API calls: '.$vcdbapi->totalcalls."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'VCdb API sync completed in '.$runtime.' seconds. '.$vcdbapi->totalcalls.' API calls, '.$vcdbapi->tokenrefreshcount.' token requests, '.$totalinserts.' inserts, '.$totalupdates.' updates, '.$totaldeletes.' deletes. SinceDate set to:'.$sincedate);
 $configSet->setConfigValue('lastSuccessfulVCdbAPIsync', time());
}
else
{
 if($vcdbapi->debug){echo 'API auth failed - http status:'.$vcdbapi->httpstatus."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'VCdb API sync failed (http response: '.$vcdbapi->httpstatus.')'); 
}