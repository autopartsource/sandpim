<?php
include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/brandAPIClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/configGetClass.php');
include_once(__DIR__.'/class/configSetClass.php');


$starttime=time();
$pim = new pim();
$logs = new logs();
$configGet = new configGet();
$configSet = new configSet();

$tokenlowlifeseconds=3000; //every time a new records page is requested, the remaining life of the active token is checked. If lif is less than this number, a nre token is requested 
$tokenrefreshlimit=30; // how many new-token requests are allowed in this session (this php script execution)
$loggingverbosity=1; // (1-10) Ten is the most verbose 

$lastsync=$configGet->getConfigValue('lastSuccessfulBrandAPIsync');
if($lastsync)
{
 $sincedate=date('Y-m-d', intval($lastsync)-(24*3600*2));  // set sincedat to 2 days before last sync
}
else 
{// no history of last successful sync - setup for full download
 $sincedate=false;
}

$sincedate=false;

$brandapi=new brandapi();

$brandapi->clientid=$configGet->getConfigValue('AutoCareAPIclientid');
$brandapi->clientsecret=$configGet->getConfigValue('AutoCareAPIclientsecret');
$brandapi->username=$configGet->getConfigValue('AutoCareAPIusername');
$brandapi->password=$configGet->getConfigValue('AutoCareAPIpassword');
        
$brandapi->getAccessToken();
$brandapi->pagelimit=0;
$brandapi->debug=false;// debug is useful for manual command calls. A bunch of stuff is echoed to the console

if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'Brand API sync started'); }

if($brandapi->activetoken)
{
 if($brandapi->debug){echo "Got API auth token\r\n";}
 if($loggingverbosity>1){$logs->logSystemEvent('AutoCare API Client', 0, 'Got Token ('.substr($brandapi->token,0,20).'...). Expires in: '.$brandapi->tokenLife().' seconds'); }
  
 $brandapi->insertcount=0;

 $success=$brandapi->getRecords('brand','Brand','en-US',$sincedate);

 if($success)
 {
  echo 'got '.count($brandapi->records). " records\r\n";
  $brandapi->populateBrandTable($brandapi->records);
 }
 else
 {
  echo "no records\r\n";
 }
 
 $runtime=time()-$starttime;
 if($brandapi->debug){echo 'Total run time: '.$runtime.' seconds. Total API calls: '.$brandapi->totalcalls."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'Brand API sync completed in '.$runtime.' seconds. '.$brandapi->totalcalls.' API calls, '.$brandapi->tokenrefreshcount.' token requests, '.$brandapi->insertcount.' inserts');
 $configSet->setConfigValue('lastSuccessfulBrandAPIsync', time());
}
else
{
 if($brandapi->debug){echo 'API auth failed - http status:'.$brandapi->httpstatus."\r\n";}
 $logs->logSystemEvent('AutoCare API Client', 0, 'Brand API sync failed (http response: '.$brandapi->httpstatus.')'); 
}