<?php
include_once('./class/pimClass.php');  
include_once('./class/padbClass.php');  
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

$newlinechars="<br/>";
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
 $padbsavailable=$pim->getAutoCareReleaseList('PAdb');
 if(count($padbsavailable)>0)
 {
  foreach($padbsavailable as $padbavailable)
  {
   if($_GET['versiondate']==$padbavailable['versiondate'])
   {
    $found=true;
    $uri=$padbavailable['uri'];
    $serverpath=$padbavailable['serverpath'];
    $hash=$padbavailable['sha256'];
    $versiondate=$padbavailable['versiondate']; // with slashes (2020-10-30)
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


$username=$config->getConfigValue('AutoCareFTPusername');
$password=$config->getConfigValue('AutoCareFTPpassword');
$ftpserver=$config->getConfigValue('AutoCareFTPserver');

if($ftpserver===false || $username ===false || $password===false)
{
 $errormessage[]='config values for AutoCareFTPserver, AutoCareFTPusername and AutoCareFTPpassword  must be set in Settings > Config';
 echo 'config values for AutoCareFTPserver, AutoCareFTPusername and AutoCareFTPpassword must be set in Settings > Config';
}


if($ftpserver && $havewriteaccess && $username && $password)
{
 $randomint= random_int(1000000, 9000000);
 $randomfilename= $randomint.'.zip';
 echo "Downloading MySQL package (".$dbversion.") from AutoCare FTP server to local server (".$downloadsdirectory.").........";
 //exec('wget --quiet --ftp-user='.$username.' --ftp-password='.$password.' --no-check-certificate '.$uri.' --output-document='.$downloadsdirectory.'/'.$randomfilename);

 $ftp = ftp_ssl_connect($ftpserver ,21,10);
 $login_result = ftp_login($ftp, $username, $password);
 if($login_result)
 {
  ftp_pasv($ftp, true); // switch to passive mode
  $getresult=ftp_get($ftp,$downloadsdirectory.'/'.$randomfilename,'/download_padb/MySQL/AAIA PAdb MySQL 20220527.zip'); 
  if($getresult)
  {
   echo 'download success<br/>'; 
   ftp_close($ftp);
  }
  else
  {
   echo 'download failure<br/>';          
   ftp_close($ftp);
  }
 }
 else
 {
  echo 'login failed<br/>';
  ftp_close($ftp);
  exit;
 }
 

 echo "Download complete".$newlinechars;

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
  echo 'SHA256:'.$localhash;
  if($hash==$localhash){echo ' (valid)'.$newlinechars;}else{echo ' (NOT VALID)'.$newlinechars;}       
   
  echo "Extracting MySQL package.........";
  flush();
  exec('unzip -o '.$downloadsdirectory.'/'.$randomfilename.' -d '.$downloadsdirectory);
  echo "Done".$newlinechars;
  flush();

  // verify they filename extracted is to the expected pattern
  if(file_exists($downloadsdirectory.'/'.'AAIA PAdb MySQL '.$dbversion.'.sql'))
  {
   // create the new database
   $dbcreationresult=$pim->createAutoCareDatabase('padb'.$dbversion, $mysql->user);
   if($dbcreationresult=='success')
   {
   // import the sql file into the mysql client
    echo "Importing database to MySQL server.........";
    flush();
    exec('mysql --host='.$mysql->host.' --user='.$mysql->user.' --password='.$mysql->passwd.' padb'.$dbversion." < '".$downloadsdirectory.'/AAIA PAdb MySQL '.$dbversion.'.sql'."'");
    echo "Done".$newlinechars;
    flush();

    $padb=new padb('padb'.$dbversion); // test the new version as ask it for its versiondate
    $versiondate=$padb->version();
    $pim->recordAutocareDatabaseList('padb'.$dbversion, 'padb', $versiondate);   // catalog the new version
    $logs->logSystemEvent('autocareupdate', $_SESSION['userid'], 'PAdb '.$dbversion.' imported');

    // add indexes that AutoCare forgot to add!
        
    echo 'Adding index on MetaUOMCodes.MetaUOMID....';
    flush();
    echo $padb->addDatabaseIndex('MetaUOMCodes', 'MetaUOMID').'Done<br/>';  //create index idx_MetaUOMID on MetaUOMCodes (MetaUOMID);

    echo 'Adding index on MetaUOMCodeAssignment.MetaUOMID...';
    flush();
    echo $padb->addDatabaseIndex('MetaUOMCodeAssignment', 'MetaUOMID').'Done<br/>';  //create index idx_MetaUOMID on MetaUOMCodeAssignment (MetaUOMID);

    echo 'Adding index on MetaUOMCodeAssignment.PAPTID...';
    flush();
    echo $padb->addDatabaseIndex('MetaUOMCodeAssignment', 'PAPTID').'Done<br/>';  //create index idx_PAPTID on MetaUOMCodeAssignment (PAPTID);  

    echo 'Adding index on PartAttributeAssignment.MetaID...';
    flush();
    echo $padb->addDatabaseIndex('PartAttributeAssignment', 'MetaID').'Done<br/>';  //create index idx_MetaID on PartAttributeAssignment (MetaID);

    echo 'Adding index on PartAttributeAssignment.PAID...';
    flush();
    echo $padb->addDatabaseIndex('PartAttributeAssignment', 'PAID').'Done<br/>';  //create index idx_PAID on PartAttributeAssignment (PAID);

    echo 'Adding index on PartAttributeAssignment.PartTerminologyID...';
    flush();
    echo $padb->addDatabaseIndex('PartAttributeAssignment', 'PartTerminologyID').'Done<br/>';  //create index idx_PartTerminologyID on PartAttributeAssignment (PartTerminologyID);

    echo 'Adding index on PartAttributeAssignment.PAPTID...';
    flush();
    echo $padb->addDatabaseIndex('PartAttributeAssignment', 'PAPTID').'Done<br/>';  //create index idx_PAPTID on PartAttributeAssignment (PAPTID);
    
    echo 'Adding index on ValidValueAssignment.PAPTID...';
    flush();
    echo $padb->addDatabaseIndex('ValidValueAssignment', 'PAPTID').'Done<br/>';  //create index idx_PAPTID on ValidValueAssignment (PAPTID);
    
    echo 'Adding index on ValidValueAssignment.ValidValueID...';
    flush();
    echo $padb->addDatabaseIndex('ValidValueAssignment', 'ValidValueID').'Done<br/>';  //create index idx_ValidValueID on ValidValueAssignment (ValidValueID);
    
    echo 'import complete';
   }
   else
   {// database create failed
    echo 'database create failed: '.$dbcreationresult."".$newlinechars;
    $logs->logSystemEvent('autocareupdate', $_SESSION['userid'], 'PAdb import failed ('.$dbcreationresult.')');
   }
   exec('rm -f '.$downloadsdirectory.'/'.'AAIA\ PAdb\ MySQL\ '.$dbversion.'.sql');
  }
  else
  {
   echo "did not find the epected SQL in the downloaded zip file".$newlinechars;    
   $logs->logSystemEvent('autocareupdate', $_SESSION['userid'], 'PAdb import failed - archive did not contain expected .sql filename');
  }
 }
 else
 {// downloaded zipfile filesiize is 0
  $logs->logSystemEvent('autocareupdate', $_SESSION['userid'], 'PAdb import failed - filesize 0');
 }
 exec('rm -f '.$downloadsdirectory.'/'.$randomfilename);    
}
?>
