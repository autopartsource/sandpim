<?php
include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/vcdbAPIClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/configGetClass.php');
include_once(__DIR__.'/class/configSetClass.php');

$starttime=time();
$pim = new pim();
$vcdbapi=new vcdbapi();
$logs = new logs();
$configGet = new configGet();
$configSet = new configSet();

$loggingverbosity=10;

$existinglocks=$pim->getLocksByType('UPDATEFROMVCDBJSON');
if(count($existinglocks))
{
 $logs->logSystemEvent('AutoCare JSON importer', 0, 'updateVCdbFromLocalJSON found lock record (id:'.$existinglocks[0]['id'].') and declined to run');
 exit; 
}
$mylockid=$pim->addLock('UPDATEFROMVCDBJSON', 'pid:'. getmypid());

$vcdbapi->debug=true;

if($loggingverbosity>1){$logs->logSystemEvent('AutoCare JSON importer', 0, 'import started'); }

$totalinserts=0; $totalupdates=0; $totaldeletes=0;

foreach($vcdbapi->tableslist as $tablename)
{
 $timetemp=time();

 $filepath='/var/www/html/ACESuploads/'.$tablename.'.json';
 
 $totalinserts+=$vcdbapi->insertcount;
 $totalupdates+=$vcdbapi->updatecount;
 $totaldeletes+=$vcdbapi->deletecount;

 $vcdbapi->insertcount=0;
 $vcdbapi->updatecount=0;
 $vcdbapi->deletecount=0;
 $vcdbapi->deleteorphancount=0;
 
 
 if($vcdbapi->debug){echo 'Clearing Local Table '.$tablename."\n";}
 $vcdbapi->clearTable($tablename);
 if($vcdbapi->debug){echo 'Getting data from json file for '.$filepath.'... ';}
 $jsondata=$vcdbapi->readLocalJSONrecords($filepath);
 if($vcdbapi->debug){echo "done\n";}
 
  
 if(count($jsondata))
 {
  if($vcdbapi->debug){echo 'populating local database table '.$tablename." with ".count($jsondata)." records ... ";}
  $vcdbapi->populateTable($tablename, $jsondata, false);
  if($vcdbapi->debug){echo "done (".(time()-$timetemp)." seconds) \n";}  
 }
 else
 {// non-success getting records for the current table
  if($vcdbapi->debug){echo "failed\n";}  
  $logs->logSystemEvent('AutoCare JSON importer', 0, 'Failure getting records for table: '.$tablename.'. http status: '.$vcdbapi->httpstatus.'. No action taken with the local table. Terminating process.');
  break;
 }

}

$runtime=time()-$starttime;
$logs->logSystemEvent('AutoCare JSON importer', 0, 'VCdb import completed in '.$runtime.' seconds. '.$vcdbapi->totalcalls.' API calls, '.$vcdbapi->tokenrefreshcount.' token requests, '.$totalinserts.' inserts, '.$totalupdates.' updates, '.$totaldeletes.' deletes');

$pim->removeLockById($mylockid);