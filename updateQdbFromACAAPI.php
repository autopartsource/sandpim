<?php
include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/qdbAPIClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
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



$lastsync=$configGet->getConfigValue('lastSuccessfulQdbAPIsync');
if($lastsync)
{
 $sincedate=date('Y-m-d', intval($lastsync)-(24*3600*$daysback));  // set sincedate to [daysback] days before last sync
}
else 
{// no history of last successful sync - setup for full download
 $sincedate=false;
}

// ------------ false = all Recs!!! -----------------------
//$sincedate=false; //'2024-12-01'; // set this data to false to query the API for all records in named tables


$clearfirst=false;  // deletes all rec in every named table before engaging with the server - used for testing/debugging work
$deletelocalorphans=false; // cause records in each local table (identified by primary keys or hashes) to be deleted if they are not present in API results 

$qdbapi=new qdbapi;

$qdbapi->clientid=$configGet->getConfigValue('AutoCareAPIclientid');
$qdbapi->clientsecret=$configGet->getConfigValue('AutoCareAPIclientsecret');
$qdbapi->username=$configGet->getConfigValue('AutoCareAPIusername');
$qdbapi->password=$configGet->getConfigValue('AutoCareAPIpassword');
        
$qdbapi->getAccessToken();
$qdbapi->pagelimit=0;
$qdbapi->debug=false;// debug is useful for manual command calls. A bunch of stuff is echoed to the console

if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'Qdb API sync started'); }

if($qdbapi->activetoken)
{
 if($qdbapi->debug){echo "Got API auth token\r\n";}
 if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'Got Token ('.substr($qdbapi->token,0,20).'...). Expires in: '.$qdbapi->tokenLife().' seconds'); }
//            $qdbapi->getDatabaseses();
//            print_r($qdbapi->databases);
//            $tables=$qdbapi->getTables('QDB');
//            print_r($tables);

 if($clearfirst)
 {
  if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0,'Clearing '.count($qdbapi->tableslist).' local tables');}
  foreach($qdbapi->tableslist as $tablename)
  {
   $timetemp=time();
   if($qdbapi->debug){echo 'Clearing Local Table '.$tablename."...";}
   $qdbapi->clearTable($tablename);
   if($qdbapi->debug){echo ' Done - '.(time()-$timetemp)." seconds\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0, 'Cleared Local Table '.$tablename.' in '.(time()-$timetemp).' seconds');}
  }
 }

 $totalinserts=0; $totalupdates=0; $totaldeletes=0;
 
 foreach($qdbapi->tableslist as $tablename)
 {
  $timetemp=time();

  $totalinserts+=$qdbapi->insertcount;
  $totalupdates+=$qdbapi->updatecount;
  $totaldeletes+=$qdbapi->deletecount;
  
  $qdbapi->insertcount=0;
  $qdbapi->updatecount=0;
  $qdbapi->deletecount=0;
  $qdbapi->deleteorphancount=0;
  
  if($qdbapi->tokenLife()<$tokenlowlifeseconds)
  {
   if($qdbapi->tokenrefreshcount>=$tokenrefreshlimit)
   {
    if($qdbapi->debug){echo " Local token-refresh limit reached. Terminating Process.\r\n";}
    $logs->logSystemEvent('AutoCare API Client', 0,'Local token-refresh limit reached. Exiting Process.');
    break;       
   }
   
   if($qdbapi->debug){echo "  Active token expires in: ".$qdbapi->tokenLife()." seconds. Requesting new token...\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Active token expires in: '.$qdbapi->tokenLife().' seconds. Requesting new token');}
   
   $qdbapi->activetoken=false;
   $qdbapi->getAccessToken();
   if(!$qdbapi->activetoken)
   {
    if($qdbapi->debug){echo " Request failed. Terminating process.\r\n";}
    $logs->logSystemEvent('AutoCare API Client', 0,'Token refresh rquest failed. Exiting Process.');    
    break;
   }
   
   if($qdbapi->debug){echo " Success. New token expires in ".$qdbapi->tokenLife()." seconds\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Successful request of new token. Expires in '.$qdbapi->tokenLife().' seconds');}  
  }
  
  if($qdbapi->debug){echo ' '.$tablename.'...';}
  $success=$qdbapi->getRecords('QDB',$tablename,'en-US',$sincedate);
  //print_r($qdbapi->records);
  $qdbapi->populateTable($tablename, $qdbapi->records, $deletelocalorphans);
  if($qdbapi->debug){echo ' inserts: '.$qdbapi->insertcount.', updates:'.$qdbapi->updatecount.', deletes: '.$qdbapi->deletecount.', orphan deletes: '.$qdbapi->deleteorphancount.' on local database records in '.(time()-$timetemp)." seconds\r\n";}
  if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, $tablename.' - inserts: '.$qdbapi->insertcount.', updates: '.$qdbapi->updatecount.', deletes: '.$qdbapi->deletecount.', orphan deletes: '.$qdbapi->deleteorphancount.' in '.(time()-$timetemp).' seconds');}
 }
 
 $runtime=time()-$starttime;
 if($qdbapi->debug){echo 'Total run time: '.$runtime.' seconds. Total API calls: '.$qdbapi->totalcalls."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'Qdb API sync completed in '.$runtime.' seconds. '.$qdbapi->totalcalls.' API calls, '.$qdbapi->tokenrefreshcount.' token requests, '.$totalinserts.' inserts, '.$totalupdates.' updates, '.$totaldeletes.' deletes. SinceDate set to:'.$sincedate);
 $configSet->setConfigValue('lastSuccessfulQdbAPIsync', time());
}
else
{
 if($qdbapi->debug){echo 'API auth failed - http status:'.$qdbapi->httpstatus."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'Qdb API sync failed (http response: '.$qdbapi->httpstatus.')'); 
}