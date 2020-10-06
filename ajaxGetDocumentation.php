<?php
include_once('./class/pimClass.php');
session_start();
$pim= new pim;

$result='';

if(isset($_SESSION['userid']) && isset($_GET['path']))
{
 $path= base64_decode($_GET['path']);
 $records=$pim->getDocumentationText($path,'EN');
 foreach($records as $record)
 {
     $result.='<div>'.$record['doctext'].'</div>';
 }
 
 echo $result;
}?>
