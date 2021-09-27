<?php
include_once('./class/pimClass.php');  
include_once('./class/pcdbClass.php');  
include_once('./class/mysqlClass.php');  
include_once('./class/logsClass.php');  
include_once('./class/configGetClass.php');


$navCategory = 'import';

session_start();
if (!isset($_SESSION['userid']))
{
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$pim=new pim;
$mysql=new mysql;
$logs=new logs;
$config=new configGet;

$newlinechars="<br/>\n";
$errormessage=array();
$havewriteaccess=false;

//do a test write to verify apache has permissions to write to the downloads folder
$downloadsdirectory=$config->getConfigValue('AutoCareDownloadsDirectory');//'/var/www/html/autocaredownloads';
if(!$downloadsdirectory)
{
 $errormessage[]='AutoCareDownloadsDirectory configuration value is not set';
}

$testfile= $downloadsdirectory.'/'.random_int(1000000, 9000000).'.txt';
$testwriteresults= file_put_contents($testfile, 'testing 123');
if($testwriteresults)
{// test write to downloads directory was succdssful
 unlink($testfile);   // delete the test file
 $havewriteaccess=true;
}
else
{// write failed
 $errormessage[]='file create failed - apache needs to have write access to:'.$downloadsdirectory;
 $logs->logSystemEvent('autocareupdate', $_SESSION['userid'], 'file create failed - apache needs to have write access to:'.$downloadsdirectory); 
}




$dbversion=''; // no dashes (20201030)
$uri=false;
$found=false;
if(isset($_GET['versiondate']) && $pim->validAutoCareVersionFormat($_GET['versiondate']))
{//2020-10-30
 $pcdbsavailable=$pim->getAutoCareReleaseList('PCdb');
 if(count($pcdbsavailable)>0)
 {
  foreach($pcdbsavailable as $pcdbavailable)
  {
   if($_GET['versiondate']==$pcdbavailable['versiondate'])
   {
    $found=true;
    $uri=$pcdbavailable['uri'];
    $hash=$pcdbavailable['sha256'];
    $versiondate=$pcdbavailable['versiondate']; // with slashes (2020-10-30)
    $dbversion=substr($versiondate,0,4).substr($versiondate,5,2).substr($versiondate,8,2);
   }
  }
 }
 else
 {// something went wrong getting the list 
  $errormessage[]='error getting AutoCare resource list'; 
 }
}
else 
{// bogus/hostile input 
    exit;
}

echo 'Download URI:'.$uri.'<br/>';

$username=$config->getConfigValue('AutoCareFTPusername');
$password=$config->getConfigValue('AutoCareFTPpassword');

if($username ===false || $password===false)
{
 $errormessage[]='config values for AutoCareFTPusername and AutoCareFTPpassword must be set in Settings > Config';
 echo 'config values for AutoCareFTPusername and AutoCareFTPpassword must be set in Settings > Config';
}

if($uri && $havewriteaccess && $username && $password)
{
 $randomint= random_int(1000000, 9000000);
 $randomfilename= $randomint.'.zip';
 echo 'Downloading MySQL package ('.$dbversion.") from AutoCare FTP server to local server (".$downloadsdirectory.').........';

 ob_flush();
 exec('wget --quiet --ftp-user='.$username.' --ftp-password='.$password.' --no-check-certificate '.$uri.' --output-document='.$downloadsdirectory.'/'.$randomfilename);
 echo 'Done'.$newlinechars;

 // test file size for 0 to see if the download failed
 $archivesize=filesize($downloadsdirectory.'/'.$randomfilename);

 if($archivesize>0)
 {
   // calc the file's sha256 hash
  $localhash=''; $localhashreturn=array();
  exec('sha256sum '.$downloadsdirectory.'/'.$randomfilename,$localhashreturn);
  if(isset($localhashreturn[0]))
  {
   $localhashchunks= explode(' ', $localhashreturn[0]);
   if(strlen($localhashchunks[0])==64){$localhash=$localhashchunks[0];}   
  }
  echo 'SHA256:'.$localhash.$newlinechars;
  if($hash==$localhash){echo ' (valid)'.$newlinechars;}else{echo ' (NOT VALID)'.$newlinechars;}       
     
  echo "Extracting MySQL package.........";
  exec('unzip -o '.$downloadsdirectory.'/'.$randomfilename.' -d '.$downloadsdirectory);
  echo 'Done'.$newlinechars;

  // verify they filename extracted is to the expected pattern
  if(file_exists($downloadsdirectory.'/'.'AAIA PCdb MySQL '.$dbversion.'.sql'))
  {
   // create the new database
   $dbcreationresult=$pim->createAutoCareDatabase('pcdb'.$dbversion, $mysql->user);
   if($dbcreationresult=='success')
   {
    // import the sql file into the mysql client
    echo "Importing database to MySQL server.........";
    exec('mysql --host='.$mysql->host.' --user='.$mysql->user.' --password='.$mysql->passwd.' pcdb'.$dbversion." < '".$downloadsdirectory.'/AAIA PCdb MySQL '.$dbversion.'.sql'."'");
    echo 'Done'.$newlinechars;

    $pcdb=new pcdb('pcdb'.$dbversion); // test the new version as ask it for its versiondate
    $versiondatefromdb=$pcdb->version();
    $pim->recordAutocareDatabaseList('pcdb'.$dbversion, 'pcdb', $versiondatefromdb);   // catalog the new version
    $logs->logSystemEvent('autocareupdate', $_SESSION['userid'], 'PCdb '.$dbversion.' imported');
   }
   else
   {// database create failed
    echo 'database create failed: '.$dbcreationresult.$newlinechars;
    $logs->logSystemEvent('autocareupdate', $_SESSION['userid'], 'PCdb import failed ('.$dbcreationresult.')');
   }
   exec('rm -f '.$downloadsdirectory.'/'.'AAIA\ PCdb\ MySQL\ '.$dbversion.'.sql');
  }
  else
  {
   echo 'did not find the expected SQL file inside the downloaded zip file'.$newlinechars;    
   $logs->logSystemEvent('autocareupdate', $_SESSION['userid'], 'PCdb import failed - archive did not contain expected .sql filename');
  }
 }
 else
 {// downloaded zipfile filesiize is 0
  echo 'Dowload is empty'.$newlinechars;   
  $logs->logSystemEvent('autocareupdate', $_SESSION['userid'], 'PCdb import failed - filesize 0');
 }

 exec('rm -f '.$downloadsdirectory.'/'.$randomfilename);
}
?>
