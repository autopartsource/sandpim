<?php
include_once('./class/userClass.php');
session_start();
$user= new user;

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['userid']) && isset($_GET['appcategory']) && isset($_GET['action']))
{
 $userid=intval($_GET['userid']);
 $appcategory=intval($_GET['appcategory']);

 switch($_GET['action'])
 {
  case 'select':
   $user->userSelectAppcategory($userid,$appcategory);
  break;

  case 'unselect':
   $user->userUnselectAppcategory($userid,$appcategory);
  break;

  default:
  break;
 }
}

?>
