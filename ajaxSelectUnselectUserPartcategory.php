<?php
include_once('./class/userClass.php');
session_start();
$user= new user;

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['userid']) && isset($_GET['partcategory']) && isset($_GET['action']))
{
 $userid=intval($_GET['userid']);
 $partcategory=intval($_GET['partcategory']);

 switch($_GET['action'])
 {
  case 'select':
   $user->userSelectPartcategory($userid,$partcategory);
  break;

  case 'unselect':
   $user->userUnselectPartcategory($userid,$partcategory);
  break;

  default:
  break;
 }
}

?>
