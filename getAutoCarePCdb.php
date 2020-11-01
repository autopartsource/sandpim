<?php
/*
 * To be run on a cron schedule (php CLI) every day
 * 
 * 
 * 
 * 
 */

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/pcdbClass.php');  
include_once(__DIR__.'/class/mysqlClass.php');  
include_once(__DIR__.'/class/logsClass.php');  
include_once(__DIR__.'/class/configGetClass.php');

$pim=new pim;
$mysql=new mysql;
$logs=new logs;
$config=new configGet;

$dbversion='20201030';


$resourcelistURI='https://aps.dev/sandpim/AutoCareTechnology.php';//getConfigValue('AutoCareResourceListURI');
$listJSON= @file_get_contents($resourcelistURI);

if($listJSON!==false)
{
 if(substr($listJSON,0,1)=='{')
 {// looks like a JSON-encoded string (starts with a "{")
     
  $listdata= json_decode($listJSON,true);  
  if(array_key_exists('PCdb', $listdata))
  {
   $currentversiondate=$listdata['PCdb']['MySQL']['current']['versiondate'];
   $currentversionuri=$listdata['PCdb']['MySQL']['current']['uri'];      
  }
  else
  {
   echo "resource list does not does not contain a top-level key of VCdb\n";      
  }
 }
 else
 {
  echo "resource list does not look like JSON\n";    
 }
}
else
{
 echo "error getting resource list\n";    
}

exit;


$host=$config->getConfigValue('AutoCareFTPserver');
$username=$config->getConfigValue('AutoCareFTPusername');
$password=$config->getConfigValue('AutoCareFTPpassword');
$downloadsdirectory=$config->getConfigValue('AutoCareDownloadsDirectory');//'/var/www/html/autocaredownloads';

if($host===false || $username ===false || $password===false || $downloadsdirectory===false)
{
 $logs->logSystemEvent('autocareupdate', 0, 'PCdb import skipped (host,user,password or downloads directory are not configured)');
 exit;
}





// -----  PCdb -----

$randomint= random_int(1000000, 9000000);
$randomfilename= $randomint.'.zip';
echo "Downloading MySQL package (".$dbversion.") from AutoCare FTP server to local server (".$downloadsdirectory.").........";

$uri='wget --quiet --ftp-user='.$username.' --ftp-password='.$password.' --no-check-certificate ftps://'.$host.'/download_pcdb/MySQL/AAIA%20PCdb%20MySQL%20'.$dbversion.'.zip --output-document='.$downloadsdirectory.'/'.$randomfilename;

exec('wget --quiet --ftp-user='.$username.' --ftp-password='.$password.' --no-check-certificate '.$uri.' --output-document='.$downloadsdirectory.'/'.$randomfilename);
echo "Done\n";

// test file size for 0 to see if the download failed
$archivesize=filesize($downloadsdirectory.'/'.$randomfilename);

if($archivesize>0)
{
 echo "Extracting MySQL package.........";
 exec('unzip -o '.$downloadsdirectory.'/'.$randomfilename.' -d '.$downloadsdirectory);
 echo "Done\n";

 // verify they filename extracted is to the expected pattern
 if(file_exists($downloadsdirectory.'/'.'AAIA PCdb MySQL '.$dbversion.'.sql'))
 {
  // create the new database
  $dbcreationresult=$pim->createAutoCareDatabase('pcdb'.$dbversion, $mysql->user);
  if($dbcreationresult=='success')
  {
   // import the sql file into the mysql client
   echo "Importing database to MySQL server.........";
   exec('mysql --user='.$mysql->user.' --password='.$mysql->passwd.' pcdb'.$dbversion." < '".$downloadsdirectory.'/AAIA PCdb MySQL '.$dbversion.'.sql'."'");
   echo "Done\n";

   $pcdb=new pcdb('pcdb'.$dbversion); // test the new version as ask it for its versiondate
   $versiondate=$pcdb->version();
   $pim->recordAutocareDatabaseList('pcdb'.$dbversion, 'pcdb', $versiondate);   // catalog the new version
   $logs->logSystemEvent('autocareupdate', 0, 'PCdb '.$dbversion.' imported');
  }
  else
  {// database create failed
   echo 'database create failed: '.$dbcreationresult."\n";
   $logs->logSystemEvent('autocareupdate', 0, 'PCdb import failed ('.$dbcreationresult.')');
  }
 
  exec('rm -f '.$downloadsdirectory.'/'.'AAIA\ PCdb\ MySQL\ '.$dbversion.'.sql');

 }
 else
 {
  echo "did not find the epected SQL in the downloaded zip file\n";    
  $logs->logSystemEvent('autocareupdate', 0, 'PCdb import failed - archive did not contain expected .sql filename');
 }

}
else
{// downloaded zipfile filesiize is 0
 $logs->logSystemEvent('autocareupdate', 0, 'PCdb import failed - filesize 0');
}

exec('rm -f '.$downloadsdirectory.'/'.$randomfilename);

?>
