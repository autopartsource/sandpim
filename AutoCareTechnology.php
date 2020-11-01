<?php
session_start();
$data=array();
$data['VCdb']['MySQL']['complete']['current']=array('versiondate'=>'2020-10-30','uri'=>'ftps://52.168.10.67/download_vcdb/Complete/MySQL/AAIA%20VCdb2009%20MySQL%20Complete%20VCDB%2020201030.zip','sha256'=>'');
$data['VCdb']['MySQL']['complete']['releases'][]=array('versiondate'=>'2020-10-30','uri'=>'ftps://52.168.10.67/download_vcdb/Complete/MySQL/AAIA%20VCdb2009%20MySQL%20Complete%20VCDB%2020201030.zip','sha256'=>'');
$data['VCdb']['MySQL']['complete']['releases'][]=array('versiondate'=>'2020-09-25','uri'=>'ftps://52.168.10.67/download_vcdb/Complete/MySQL/AAIA%20VCdb2009%20MySQL%20Complete%20VCDB%2020200925.zip','sha256'=>'');
$data['VCdb']['MySQL']['complete']['releases'][]=array('versiondate'=>'2020-08-28','uri'=>'ftps://52.168.10.67/download_vcdb/Complete/MySQL/AAIA%20VCdb2009%20MySQL%20Complete%20VCDB%2020200828.zip','sha256'=>'');
$data['VCdb']['MySQL']['complete']['releases'][]=array('versiondate'=>'2020-07-31','uri'=>'ftps://52.168.10.67/download_vcdb/Complete/MySQL/AAIA%20VCdb2009%20MySQL%20Complete%20VCDB%2020200731.zip','sha256'=>'');

$data['PCdb']['MySQL']['current']=array('versiondate'=>'2020-10-30','uri'=>'ftps://52.168.10.67/download_pcdb/MySql/AAIA%20PCdb%20MySQL%2020201030.zip','sha256'=>'50E4684F9178BA6C4B309DE1DE248FFD50534FA28C453D346880EEE66DD8ABA8');
$data['PCdb']['MySQL']['releases'][]=array('versiondate'=>'2020-10-30','uri'=>'ftps://52.168.10.67/download_pcdb/MySql/AAIA%20PCdb%20MySQL%2020201030.zip','sha256'=>'50E4684F9178BA6C4B309DE1DE248FFD50534FA28C453D346880EEE66DD8ABA8');
$data['PCdb']['MySQL']['releases'][]=array('versiondate'=>'2020-10-09','uri'=>'ftps://52.168.10.67/download_pcdb/MySql/AAIA%20PCdb%20MySQL%2020201009.zip','sha256'=>'');
$data['PCdb']['MySQL']['releases'][]=array('versiondate'=>'2020-09-25','uri'=>'ftps://52.168.10.67/download_pcdb/MySql/AAIA%20PCdb%20MySQL%2020200925.zip','sha256'=>'');
$data['PCdb']['MySQL']['releases'][]=array('versiondate'=>'2020-09-11','uri'=>'ftps://52.168.10.67/download_pcdb/MySql/AAIA%20PCdb%20MySQL%2020200911.zip','sha256'=>'');

$data['Qdb']['MySQL']['current']=array('versiondate'=>'2020-10-30','uri'=>'ftps://52.168.10.67/download_qdb/MySql/AAIA%20Qdb%20MySQL%2020201030.zip','sha256'=>'');
$data['Qdb']['MySQL']['releases'][]=array('versiondate'=>'2020-10-30','uri'=>'ftps://52.168.10.67/download_qdb/MySql/AAIA%20Qdb%20MySQL%2020201030.zip','sha256'=>'');
$data['Qdb']['MySQL']['releases'][]=array('versiondate'=>'2020-09-25','uri'=>'ftps://52.168.10.67/download_qdb/MySql/AAIA%20Qdb%20MySQL%2020200925.zip','sha256'=>'');
$data['Qdb']['MySQL']['releases'][]=array('versiondate'=>'2020-08-28','uri'=>'ftps://52.168.10.67/download_qdb/MySql/AAIA%20Qdb%20MySQL%2020200828.zip','sha256'=>'');
$data['Qdb']['MySQL']['releases'][]=array('versiondate'=>'2020-07-31','uri'=>'ftps://52.168.10.67/download_qdb/MySql/AAIA%20Qdb%20MySQL%2020200731.zip','sha256'=>'');

$data['PAdb']['MySQL']['current']=array('versiondate'=>'2020-10-30','uri'=>'ftps://52.168.10.67/download_padb/MySQL/AAIA%20PCAdb%20MySQL%2020201030.zip','sha256'=>'');
$data['PAdb']['MySQL']['releases'][]=array('versiondate'=>'2020-10-30','uri'=>'ftps://52.168.10.67/download_padb/MySQL/AAIA%20PCAdb%20MySQL%2020201030.zip','sha256'=>'');
$data['PAdb']['MySQL']['releases'][]=array('versiondate'=>'2020-09-25','uri'=>'ftps://52.168.10.67/download_padb/MySQL/AAIA%20PCAdb%20MySQL%2020200925.zip','sha256'=>'');
$data['PAdb']['MySQL']['releases'][]=array('versiondate'=>'2020-08-28','uri'=>'ftps://52.168.10.67/download_padb/MySQL/AAIA%20PCAdb%20MySQL%2020200828.zip','sha256'=>'');
$data['PAdb']['MySQL']['releases'][]=array('versiondate'=>'2020-07-31','uri'=>'ftps://52.168.10.67/download_padb/MySQL/AAIA%20PCAdb%20MySQL%2020200731.zip','sha256'=>'');





if(isset($_GET['nice']))
{
 echo '<pre>'.print_r($data,true).'</pre>';
}
else
{
 echo json_encode($data);    
}
?>