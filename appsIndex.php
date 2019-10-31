<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;
$pim= new pim;

if(isset($_GET['all'])){$makes=$vcdb->getMakes();}else{$makes=$pim->getFavoriteMakes();;}

$makecount=count($makes);
$groupsize=intval(count($makes)/6);
$i=0; $groupnumber=0; $groupedmakes=array();
foreach($makes as $make)
{
 $groupedmakes[$groupnumber][]=$make;
 $i++; if($i>$groupsize){$i=0; $groupnumber++;}
}

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
  <h1>Applications</h1>
  <?php
  echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[0] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
  echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[1] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
  echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[2] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
  echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[3] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
  echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[4] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
  echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[5] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
  echo '<div style="clear:both;"></div>';?>
 </body>
</html>

