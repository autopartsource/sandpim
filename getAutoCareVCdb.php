<?php
/*
 * To be run on a cron schedule (php CLI) every day
 * 
 * 
 * 
 * 
 */

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/vcdbClass.php');  
include_once(__DIR__.'/class/mysqlClass.php');  
include_once(__DIR__.'/class/logsClass.php');  
include_once(__DIR__.'/class/configGetClass.php');

$pim=new pim;
$mysql=new mysql;
$logs=new logs;
$config=new configGet;

$dbversion='20201030';
$host=$config->getConfigValue('AutoCareFTPserver');
$username=$config->getConfigValue('AutoCareFTPusername');
$password=$config->getConfigValue('AutoCareFTPpassword');
$downloadsdirectory=$config->getConfigValue('AutoCareDownloadsDirectory');//'/var/www/html/autocaredownloads';

if($host===false || $username ===false || $password===false || $downloadsdirectory===false)
{
 $logs->logSystemEvent('autocareupdate', 0, 'VCdb import skipped (host,user,password or downloads directory are not configured)');
 exit;
}





// -----  VCdb -----

$randomint= random_int(1000000, 9000000);
$randomfilename= $randomint.'.zip';
echo "Downloading MySQL package (".$dbversion.") from AutoCare FTP server to local server (".$downloadsdirectory.").........";
exec('wget --quiet --ftp-user='.$username.' --ftp-password='.$password.' --no-check-certificate ftps://'.$host.'/download_vcdb/Complete/MySQL/AAIA%20VCdb2009%20MySQL%20Complete%20VCDB%20'.$dbversion.'.zip --output-document='.$downloadsdirectory.'/'.$randomfilename);
echo "Done\n";

// test file size for 0 to see if the download failed
$archivesize=filesize($downloadsdirectory.'/'.$randomfilename);

if($archivesize>0)
{
 echo "Extracting MySQL package.........";
 exec('unzip -o '.$downloadsdirectory.'/'.$randomfilename.' -d '.$downloadsdirectory);
 echo "Done\n";

 // verify they filename extracted is to the expected pattern
 if(file_exists($downloadsdirectory.'/'.'AAIA VCdb2009 MySQL Complete VCDB '.$dbversion.'.sql'))
 {
  // create the new database
  $dbcreationresult=$pim->createAutoCareDatabase('vcdb'.$dbversion, $mysql->user);
  if($dbcreationresult=='success')
  {
   // import the sql file into the mysql client
   echo "Importing database to MySQL server.........";
   exec('mysql --user='.$mysql->user.' --password='.$mysql->passwd.' vcdb'.$dbversion." < '".$downloadsdirectory.'/AAIA VCdb2009 MySQL Complete VCDB '.$dbversion.'.sql'."'");
   echo "Done\n";

   $vcdb=new vcdb('vcdb'.$dbversion); // test the new version as ask it for its versiondate
   $versiondate=$vcdb->version();
   $pim->recordAutocareDatabaseList('vcdb'.$dbversion, 'vcdb', $versiondate);   // catalog the new version
   $logs->logSystemEvent('autocareupdate', 0, 'VCdb '.$dbversion.' imported');
  }
  else
  {// database create failed
   echo 'database create failed: '.$dbcreationresult."\n";
   $logs->logSystemEvent('autocareupdate', 0, 'VCdb import failed ('.$dbcreationresult.')');
  }
 
  exec('rm -f '.$downloadsdirectory.'/AAIA\ VCdb2009\ MySQL\ Complete\ VCDB\ '.$dbversion.'.sql');

 }
 else
 {
  echo "did not find the epected SQL in the downloaded zip file\n";    
  $logs->logSystemEvent('autocareupdate', 0, 'VCdb import failed - archive did not contain expected .sql filename');
 }

}
else
{// downloaded zipfile filesiize is 0
 echo "download failed - filesize is 0\n";    
 $logs->logSystemEvent('autocareupdate', 0, 'VCdb import failed - filesize 0');
}

exec('rm -f '.$downloadsdirectory.'/'.$randomfilename);

?>
