<?php
include_once('/var/www/html/class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pim= new pim;
$history=$pim->getHistoryEvents(20);

?>
<!DOCTYPE html>
<html>
 <head>
  <style>
   .apppart {padding: 1px; border: 1px solid #808080; margin: 0px; background-color:#d0f0c0;}
   .apppart-cosmetic {padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#33FFD7;}
   .apppart-hidden {padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#FFD433;}
   .apppart-deleted { padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#FF5533;}

   a:link {color: blue; text-decoration: none;}
   a:visited {color: blue; text-decoration: none;}
   a:hover {color: gray; text-decoration: none;}
   a:active {color: blue; text-decoration: none;}

   table {border-collapse: collapse;}
   table, th, td {border: 1px solid black;}
  </style>
 </head>
 <body>
 <?php include('topnav.php');?>
  <h1>Dashboard</h1>
  <?php if(count($history))
  {
   echo '<table><tr><th>Date/Time</th><th>User</th><th>Change Description</th><th>OID After Change</th></tr>';
   foreach($history as $record)
   {
    echo '<tr><td>'.$record['eventdatetime'].'</td><td>'.$record['userid'].'</td><td>'.$record['description'].'</td><td>'.$record['new_oid'].'</td></tr>';
   }
   echo '</table>';
  }?>

 </body>
</html>
