<?php
include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/padbAPIClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
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



$lastsync=$configGet->getConfigValue('lastSuccessfulPAdbAPIsync');
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
$deletelocalorphans=false; // cause records in each local table (identified by primary keys or hashes) to be deleted if they are not present in API results 

$padbapi=new padbapi;

$padbapi->clientid=$configGet->getConfigValue('AutoCareAPIclientid');
$padbapi->clientsecret=$configGet->getConfigValue('AutoCareAPIclientsecret');
$padbapi->username=$configGet->getConfigValue('AutoCareAPIusername');
$padbapi->password=$configGet->getConfigValue('AutoCareAPIpassword');
        
$padbapi->getAccessToken();
$padbapi->pagelimit=0;
$padbapi->debug=false;// debug is useful for manual command calls. A bunch of stuff is echoed to the console

if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'PAdb API sync started'); }

if($padbapi->activetoken)
{
 if($padbapi->debug){echo "Got API auth token\r\n";}
 if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'Got Token ('.substr($padbapi->token,0,20).'...). Expires in: '.$padbapi->tokenLife().' seconds'); }
//            $padbapi->getDatabaseses();
//            print_r($padbapi->databases);
//            $tables=$padbapi->getTables('PADB');
//            print_r($tables);

 if($clearfirst)
 {
  if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0,'Clearing '.count($padbapi->tableslist).' local tables');}
  foreach($padbapi->tableslist as $tablename)
  {
   $timetemp=time();
   if($padbapi->debug){echo 'Clearing Local Table '.$tablename."...";}
   $padbapi->clearTable($tablename);
   if($padbapi->debug){echo ' Done - '.(time()-$timetemp)." seconds\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0, 'Cleared Local Table '.$tablename.' in '.(time()-$timetemp).' seconds');}
  }
 }

 $totalinserts=0; $totalupdates=0; $totaldeletes=0;
 
 foreach($padbapi->tableslist as $tablename)
 {
  $timetemp=time();

  $totalinserts+=$padbapi->insertcount;
  $totalupdates+=$padbapi->updatecount;
  $totaldeletes+=$padbapi->deletecount;
  
  $padbapi->insertcount=0;
  $padbapi->updatecount=0;
  $padbapi->deletecount=0;
  $padbapi->deleteorphancount=0;
  
  if($padbapi->tokenLife()<$tokenlowlifeseconds)
  {
   if($padbapi->tokenrefreshcount>=$tokenrefreshlimit)
   {
    if($padbapi->debug){echo " Local token-refresh limit reached. Terminating Process.\r\n";}
    $logs->logSystemEvent('AutoCare API Client', 0,'Local token-refresh limit reached. Exiting Process.');
    break;       
   }
   
   if($padbapi->debug){echo "  Active token expires in: ".$padbapi->tokenLife()." seconds. Requesting new token...\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Active token expires in: '.$padbapi->tokenLife().' seconds. Requesting new token');}
   
   $padbapi->activetoken=false;
   $padbapi->getAccessToken();
   if(!$padbapi->activetoken)
   {
    if($padbapi->debug){echo " Request failed. Terminating process.\r\n";}
    $logs->logSystemEvent('AutoCare API Client', 0,'Token refresh rquest failed. Exiting Process.');    
    break;
   }
   
   if($padbapi->debug){echo " Success. New token expires in ".$padbapi->tokenLife()." seconds\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Successful request of new token. Expires in '.$padbapi->tokenLife().' seconds');}  
  }
  
  if($padbapi->debug){echo ' '.$tablename.'...';}
  $success=$padbapi->getRecords('PCDB',$tablename,'en-US',$sincedate);
  //print_r($padbapi->records);
  $padbapi->populateTable($tablename, $padbapi->records, $deletelocalorphans);
  if($padbapi->debug){echo ' inserts: '.$padbapi->insertcount.', updates:'.$padbapi->updatecount.', deletes: '.$padbapi->deletecount.', orphan deletes: '.$padbapi->deleteorphancount.' on local database records in '.(time()-$timetemp)." seconds\r\n";}
  if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, $tablename.' - inserts: '.$padbapi->insertcount.', updates: '.$padbapi->updatecount.', deletes: '.$padbapi->deletecount.', orphan deletes: '.$padbapi->deleteorphancount.' in '.(time()-$timetemp).' seconds');}
 }
 
 $runtime=time()-$starttime;
 if($padbapi->debug){echo 'Total run time: '.$runtime.' seconds. Total API calls: '.$padbapi->totalcalls."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'PAdb API sync completed in '.$runtime.' seconds. '.$padbapi->totalcalls.' API calls, '.$padbapi->tokenrefreshcount.' token requests, '.$totalinserts.' inserts, '.$totalupdates.' updates, '.$totaldeletes.' deletes. SinceDate set to:'.$sincedate);
 $configSet->setConfigValue('lastSuccessfulPAdbAPIsync', time());
}
else
{
 if($padbapi->debug){echo 'API auth failed - http status:'.$padbapi->httpstatus."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'PAdb API sync failed (http response: '.$padbapi->httpstatus.')'); 
}