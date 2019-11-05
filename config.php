<?php
include_once('/var/www/html/class/userClass.php');
include_once('/var/www/html/class/configClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$user=new user;
$config=new config;

$configs=$config->getAllConfigValues();


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
  <h1>System Configuration</h1>


  <div style="padding:10px;">
   <h3>Configuration Parameters</h3>
   <table>
    <tr><th>Parameter</th><th>Value</th><th></th></tr>
    <?php foreach($configs as $config)
    {
     echo '<tr><td>'.$config['configname'].'</td><td>'.$config['configvalue'].'</td><td><button>Delete</button></td></tr>';
    }?>
    <tr><td><input type="text" name="configname" size="30"/></td><td><input type="text" name="configvalue" size="50"/></td><td><button>Add</button></td></tr>
   </table>
  </div>

 </body>
</html>
