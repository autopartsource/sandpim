<?php
include_once('/var/www/html/class/pimClass.php');
include_once('/var/www/html/class/userClass.php');
include_once('/var/www/html/class/configGetClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pim= new pim;
$user= new user;
$configGet=new configGet;
$history=$pim->getHistoryEvents(20);

$logpreviewlength=intval($configGet->getConfigValue('logPreviewDescriptionLength',80));


?>
<!DOCTYPE html>
<html>
 <head>
     <link rel="stylesheet" type="text/css" href="styles.css">
  <script>


  </script>
 </head>
 <body>
 <?php include('topnav.php');?>
  <h1>Dashboard</h1>
  <?php if(count($history))
    {
    echo '<table><tr><th>Date/Time</th><th>User</th><th>Change Description</th></tr>';
    foreach($history as $record)
    {
        $nicedescription=$record['description']; if(strlen($nicedescription)>$logpreviewlength){$nicedescription= substr($nicedescription,0,$logpreviewlength).'...';}
        echo '<tr><td>'.$record['eventdatetime'].'</td><td>'.$user->realNameOfUserid($record['userid']).'</td><td>'.$nicedescription.'</td></tr>';
    }
    echo '</table>';
    }?>

 </body>
</html>
