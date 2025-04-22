<?php
include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/pcdbAPIClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/configGetClass.php');
include_once(__DIR__.'/class/configSetClass.php');


$starttime=time();
$pim = new pim();
$logs = new logs();
$configGet = new configGet();
$configSet = new configSet();


$daysback=14;
$tokenlowlifeseconds=3000; //every time a new records page is requested, the remaining life of the active token is checked. If lif is less than this number, a nre token is requested 
$tokenrefreshlimit=30; // how many new-token requests are allowed in this session (this php script execution)
$loggingverbosity=1; // (1-10) Ten is the most verbose 



$lastsync=$configGet->getConfigValue('lastSuccessfulPCdbAPIsync');
if($lastsync)
{
 $sincedate=date('Y-m-d', intval($lastsync)-(24*3600*2));  // set sincedat to 2 days before last sync
}
else 
{// no history of last successful sync - setup for full download
 $sincedate=false;
}

// ------------ false = all Recs!!! -----------------------
//$sincedate=false; //'2024-12-01'; // set this data to false to query the API for all records in named tables


$clearfirst=false;  // deletes all rec in every named table before engaging with the server - used for testing/debugging work
$deletelocalorphans=false; // cause records in each local table (identified by primary keys) to be deleted if they are not present in API results 

$pcdbapi=new pcdbapi;

$pcdbapi->clientid=$configGet->getConfigValue('AutoCareAPIclientid');
$pcdbapi->clientsecret=$configGet->getConfigValue('AutoCareAPIclientsecret');
$pcdbapi->username=$configGet->getConfigValue('AutoCareAPIusername');
$pcdbapi->password=$configGet->getConfigValue('AutoCareAPIpassword');
        
$pcdbapi->getAccessToken();
$pcdbapi->pagelimit=0;
$pcdbapi->debug=false;// debug is useful for manual command calls. A bunch of stuff is echoed to the console

if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'PCdb API sync started'); }

if($pcdbapi->activetoken)
{
 if($pcdbapi->debug){echo "Got API auth token\r\n";}
 if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'Got Token ('.substr($pcdbapi->token,0,20).'...). Expires in: '.$pcdbapi->tokenLife().' seconds'); }
//            $pcdbapi->getDatabaseses();
//            print_r($pcdbapi->databases);
//            $tables=$pcdbapi->getTables('PCDB');
//            print_r($tables);

 if($clearfirst)
 {
  if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0,'Clearing '.count($pcdbapi->tableslist).' local tables');}
  foreach($pcdbapi->tableslist as $tablename)
  {
   $timetemp=time();
   if($pcdbapi->debug){echo 'Clearing Local Table '.$tablename."...";}
   $pcdbapi->clearTable($tablename);
   if($pcdbapi->debug){echo ' Done - '.(time()-$timetemp)." seconds\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0, 'Cleared Local Table '.$tablename.' in '.(time()-$timetemp).' seconds');}
  }
 }

 $totalinserts=0; $totalupdates=0; $totaldeletes=0;
 
 foreach($pcdbapi->tableslist as $tablename)
 {
  $timetemp=time();

  $totalinserts+=$pcdbapi->insertcount;
  $totalupdates+=$pcdbapi->updatecount;
  $totaldeletes+=$pcdbapi->deletecount;
  
  $pcdbapi->insertcount=0;
  $pcdbapi->updatecount=0;
  $pcdbapi->deletecount=0;
  $pcdbapi->deleteorphancount=0;
  
  if($pcdbapi->tokenLife()<$tokenlowlifeseconds)
  {
   if($pcdbapi->tokenrefreshcount>=$tokenrefreshlimit)
   {
    if($pcdbapi->debug){echo " Local token-refresh limit reached. Terminating Process.\r\n";}
    $logs->logSystemEvent('AutoCare API Client', 0,'Local token-refresh limit reached. Exiting Process.');
    break;       
   }
   
   if($pcdbapi->debug){echo "  Active token expires in: ".$pcdbapi->tokenLife()." seconds. Requesting new token...\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Active token expires in: '.$pcdbapi->tokenLife().' seconds. Requesting new token');}
   
   $pcdbapi->activetoken=false;
   $pcdbapi->getAccessToken();
   if(!$pcdbapi->activetoken)
   {
    if($pcdbapi->debug){echo " Request failed. Terminating process.\r\n";}
    $logs->logSystemEvent('AutoCare API Client', 0,'Token refresh rquest failed. Exiting Process.');    
    break;
   }
   
   if($pcdbapi->debug){echo " Success. New token expires in ".$pcdbapi->tokenLife()." seconds\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Successful request of new token. Expires in '.$pcdbapi->tokenLife().' seconds');}  
  }
  
  if($pcdbapi->debug){echo ' '.$tablename.'...';}
  $success=$pcdbapi->getRecords('PCDB',$tablename,'en-US',$sincedate);
  //print_r($pcdbapi->records);
  $pcdbapi->populateTable($tablename, $pcdbapi->records, $deletelocalorphans);
  if($pcdbapi->debug){echo ' inserts: '.$pcdbapi->insertcount.', updates:'.$pcdbapi->updatecount.', deletes: '.$pcdbapi->deletecount.', orphan deletes: '.$pcdbapi->deleteorphancount.' on local database records in '.(time()-$timetemp)." seconds\r\n";}
  if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, $tablename.' - inserts: '.$pcdbapi->insertcount.', updates: '.$pcdbapi->updatecount.', deletes: '.$pcdbapi->deletecount.', orphan deletes: '.$pcdbapi->deleteorphancount.' in '.(time()-$timetemp).' seconds');}
 }
 
 $runtime=time()-$starttime;
 if($pcdbapi->debug){echo 'Total run time: '.$runtime.' seconds. Total API calls: '.$pcdbapi->totalcalls."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'PCdb API sync completed in '.$runtime.' seconds. '.$pcdbapi->totalcalls.' API calls, '.$pcdbapi->tokenrefreshcount.' token requests, '.$totalinserts.' inserts, '.$totalupdates.' updates, '.$totaldeletes.' deletes. SinceDate set to:'.$sincedate);
 $configSet->setConfigValue('lastSuccessfulPCdbAPIsync', time());
}
else
{
 if($pcdbapi->debug){echo 'API auth failed - http status:'.$pcdbapi->httpstatus."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'PCdb API sync failed (http response: '.$pcdbapi->httpstatus.')'); 
}