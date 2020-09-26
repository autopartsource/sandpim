<?php
include_once('./class/pimClass.php');
session_start();
$pim= new pim;

if(isset($_SESSION['userid']) && isset($_GET['id']))
{
 $id=intval($_GET['id']);
 $pim->deleteIssue($id);
}
?>
