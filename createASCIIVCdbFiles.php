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

$versionyyyymmdd=date('20000101');
$basedirectorypath='/var/www/html/ACESuploads/';
$tempdirname= random_int(1000000, 9999999);
$loggingverbosity=10;

$existinglocks=$pim->getLocksByType('CREATEASCIIVCDB');
if(count($existinglocks))
{
 $logs->logSystemEvent('AutoCare VCDB Converter', 0, 'createASCIIVCdbFiles found lock record (id:'.$existinglocks[0]['id'].') and declined to run');
 exit; 
}
$mylockid=$pim->addLock('CREATEASCIIVCDB', 'pid:'. getmypid());

$vcdbapi->debug=false;

$shellresult= shell_exec('mkdir '.$basedirectorypath.$tempdirname);
if($vcdbapi->debug){echo "create directory ".$tempdirname." (shell result:".$shellresult.")\n";}



if($loggingverbosity>1){$logs->logSystemEvent('AutoCare VCDB Converter', 0, 'conversion started. Temp dir:'.$tempdirname); }

$vcdbapi->tableslist[]='Attachment';
$vcdbapi->tableslist[]='AttachmentType';
$vcdbapi->tableslist[]='ChangeAttributeStates';
$vcdbapi->tableslist[]='ChangeDetails';
$vcdbapi->tableslist[]='ChangeReasons';
$vcdbapi->tableslist[]='Changes';
$vcdbapi->tableslist[]='ChangeTableNames';
$vcdbapi->tableslist[]='Language';
$vcdbapi->tableslist[]='LanguageTranslation';
$vcdbapi->tableslist[]='LanguageTranslationAttachment';
$vcdbapi->tableslist[]='VCdbChanges';

$inputfilepath=$basedirectorypath.'Version.json';
if(file_exists($inputfilepath))
{
 $jsondata = json_decode(file_get_contents($inputfilepath), true);
 if(count($jsondata))
 { // {"DatabaseName":"VCdb","Version":"2.0","PublicationDate":"2026-04-30T00:00:00"}
  $versionyyyymmdd=substr($jsondata[0]['PublicationDate'],0,4).substr($jsondata[0]['PublicationDate'],5,2).substr($jsondata[0]['PublicationDate'],8,2); 
  if($fh=fopen($basedirectorypath.$tempdirname.'/'.$versionyyyymmdd.'_Version.txt', 'a'))
  {
   $writeresult=fwrite($fh, "VersionDate\r\n");
   $writeresult=fwrite($fh, substr($jsondata[0]['PublicationDate'],5,2).'/'.substr($jsondata[0]['PublicationDate'],8,2).'/'.substr($jsondata[0]['PublicationDate'],0,4)."\r\n");
   fclose($fh);
  }
 } 
}
if($vcdbapi->debug){echo "version from Version.json:".$versionyyyymmdd."\n";}

foreach($vcdbapi->tableslist as $tablename)
{
 $timetemp=time();

 $inputfilepath=$basedirectorypath.$tablename.'.json';
   
 if($vcdbapi->debug){echo 'Getting data from json file for '.$inputfilepath.'... ';}
 
 $jsondata=array('dummydata');
 
 if(file_exists($inputfilepath))
 {
  $jsondata=$vcdbapi->readLocalJSONrecords($inputfilepath);
  if($vcdbapi->debug){echo "done\n";}
 }
 else
 {// filename does not exist
  if($vcdbapi->debug){echo "file not found for input\n";}    
 }
 
   
 if(count($jsondata))
 {
  $asciirecords=$vcdbapi->makeASCIIrecords($tablename, $jsondata, '|', $versionyyyymmdd);
  
  $outptufilename=$versionyyyymmdd.'_'.$tablename.'.txt';
    
  if($vcdbapi->debug){echo 'writing out local file '.$outptufilename.' with '.count($asciirecords).' records ... ';}
    
  if($fh=fopen($basedirectorypath.$tempdirname.'/'.$outptufilename, 'a'))
  {
   foreach($asciirecords as $asciirecord)
   {
    $writeresult=fwrite($fh, $asciirecord."\r\n");
   }
   fclose($fh);
  } 
  
  if($vcdbapi->debug){echo "done (".(time()-$timetemp)." seconds) \n";}  
 }
 else
 {// non-success getting records for the current table
  if($vcdbapi->debug){echo "failed\n";}  
  $logs->logSystemEvent('AutoCare JSON importer', 0, 'Failure getting records for table: '.$tablename);
  break;
 }

}

$shellresult= shell_exec('zip '.$basedirectorypath.'AutoCare_VCdb_ASCII_'.$versionyyyymmdd.'.zip '.$basedirectorypath.$tempdirname.'/*');
$shellresult= shell_exec('rm -rf '.$basedirectorypath.$tempdirname);


$runtime=time()-$starttime;
$logs->logSystemEvent('AutoCare VCDB Converter', 0, 'VCdb conversion completed in '.$runtime.' seconds.');

$pim->removeLockById($mylockid);