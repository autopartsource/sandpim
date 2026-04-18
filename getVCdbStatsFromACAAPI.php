<?php
include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/vcdbAPIClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/configGetClass.php');

$starttime=time();
$pim = new pim();
$logs = new logs();
$configGet = new configGet();

$existinglocks=$pim->getLocksByType('VCDBSTATSFROMAPI');
if(count($existinglocks))
{
 $logs->logSystemEvent('AutoCare API Client', 0, 'getVCdbStatsFromACAAPI found lock record (id:'.$existinglocks[0]['id'].') and declined to run');
 exit; 
}
$mylockid=$pim->addLock('VCDBSTATSFROMAPI', 'pid:'. getmypid());

$tokenlowlifeseconds=3000; //every time a new records page is requested, the remaining life of the active token is checked. If lif is less than this number, a nre token is requested 
$tokenrefreshlimit=100; // how many new-token requests are allowed in this session (this php script execution)
$loggingverbosity=1; // (1-10) Ten is the most verbose 
$sincedate=false; //'2024-12-01'; // set this data to false to query the API for all records in named tables
$failurecount=0;
$localrecordcounttotal=0;
$diffs=[];

$vcdbapi=new vcdbapi;

$vcdbapi->clientid=$configGet->getConfigValue('AutoCareAPIclientid');
$vcdbapi->clientsecret=$configGet->getConfigValue('AutoCareAPIclientsecret');
$vcdbapi->username=$configGet->getConfigValue('AutoCareAPIusername');
$vcdbapi->password=$configGet->getConfigValue('AutoCareAPIpassword');
        
$vcdbapi->getAccessToken();
$vcdbapi->pagelimit=0;
$vcdbapi->pagesize=1; // number of records in each response
$vcdbapi->debug=false;// debug is useful for manual command calls. A bunch of stuff is echoed to the console

if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'VCdb API - get stats started'); }

if($vcdbapi->activetoken)
{
 if($vcdbapi->debug){echo "Got API auth token\r\n";}
 if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'Got Token ('.substr($vcdbapi->token,0,20).'...). Expires in: '.$vcdbapi->tokenLife().' seconds'); }

 foreach($vcdbapi->tableslist as $tablename)
 {
  $timetemp=time();
  
  if($vcdbapi->tokenLife()<$tokenlowlifeseconds)
  {
   if($vcdbapi->tokenrefreshcount>=$tokenrefreshlimit)
   {
    if($vcdbapi->debug){echo " Local token-refresh limit reached. Terminating Process.\r\n";}
    $logs->logSystemEvent('AutoCare API Client', 0,'Local token-refresh limit reached. Existing Process.');
    break;       
   }
   
   if($vcdbapi->debug){echo "  Active token expires in: ".$vcdbapi->tokenLife()." seconds. Requesting new token...\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Active token expires in: '.$vcdbapi->tokenLife().' seconds. Requesting new token');}
   
   $vcdbapi->activetoken=false;
   $vcdbapi->getAccessToken();
   if(!$vcdbapi->activetoken)
   {
    if($vcdbapi->debug){echo " Request failed. Terminating process.\r\n";}
    $logs->logSystemEvent('AutoCare API Client', 0,'Token refresh rquest failed after '.$vcdbapi->tokenrefreshcount.' refreshes. Exiting Process. Raw server response:'.$vcdbapi->errormessage);    
    break;
   }
   
   if($vcdbapi->debug){echo " Success. New token expires in ".$vcdbapi->tokenLife()." seconds\r\n";}
   if($loggingverbosity>2){$logs->logSystemEvent('AutoCare API Client', 0,'Successful request of new token. Expires in '.$vcdbapi->tokenLife().' seconds');}  
  }
  
  if($vcdbapi->debug){echo ' '.$tablename.'...';}
  
  $vcdbapi->records=array();
  $vcdbapi->morepages=false;
  $success=$vcdbapi->getRecordsPage('VCDB', $tablename, 'en-US', $sincedate);
  if($success)
  {
   $localrecordcount=$vcdbapi->getTableRecordCount($tablename);
   $localrecordcounttotal+=$localrecordcount;
   if($localrecordcount!=$vcdbapi->tablerecordcounts[$tablename])
   {
    $diffs[]=$tablename.' --- API: '.$vcdbapi->tablerecordcounts[$tablename].', local: '.$localrecordcount;
   }   
   if($vcdbapi->debug){print_r($vcdbapi->records);}   
  }
  else
  {
   $failurecount++;
   if($vcdbapi->debug){echo " Failed to get records (http status:".$vcdbapi->httpstatus.").\r\n";}
   $logs->logSystemEvent('AutoCare API Client', 0," Failed to get records (http status:".$vcdbapi->httpstatus.") getting stats for ".$tablename);   
  }
  
 }
 
 if($failurecount==0)
 {
  if($vcdbapi->debug){print_r($vcdbapi->tablerecordcounts);}
  if(count($diffs))
  { // there are record-count diffs between local vcdbcach and the api's just-reported counts
   $logs->logSystemEvent('AutoCare API Client', 0, 'VCdb API vs. local ('.$vcdbapi->version().') record count comparisons<br/>'.implode('<br/>',$diffs));
  }
  else
  {
   $logs->logSystemEvent('AutoCare API Client', 0, 'VCdb API vs. local ('.$vcdbapi->version().') - no table count differences found across '.count($vcdbapi->tableslist).' tables, '.$localrecordcounttotal.' total records');
  }
 }
 else
 {
  if($vcdbapi->debug){echo " Failed to get a full and valid dataset.\r\n";}
 }
 
 
 $runtime=time()-$starttime;
 if($vcdbapi->debug){echo 'Total run time: '.$runtime.' seconds. Total API calls: '.$vcdbapi->totalcalls.'. Token refreshes:'.$vcdbapi->tokenrefreshcount."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'VCdb API stats query completed in '.$runtime.' seconds. ');
}
else
{
 if($vcdbapi->debug){echo 'API auth failed - http status:'.$vcdbapi->httpstatus."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'VCdb API sync failed (http response: '.$vcdbapi->httpstatus.')'); 
}
$pim->removeLockById($mylockid);
