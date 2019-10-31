<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;
//$pim= new pim;


$makeid=intval($_GET['makeid']);
$modelid=intval($_GET['modelid']);
$years=$vcdb->getYears($makeid,$modelid);



$groupcount=5;
$yearcount=count($years);
if($yearcount<=70){$groupcount=6;}
if($yearcount<=60){$groupcount=5;}
if($yearcount<=40){$groupcount=4;}
if($yearcount<=30){$groupcount=3;}
if($yearcount<=20){$groupcount=2;}
if($yearcount<=10){$groupcount=1;}
// comment -  

$yearcount=count($years);
$groupsize=intval(count($years)/$groupcount);
$i=0; $groupnumber=0; $groupedyears=array();
foreach($years as $year)
{
 $groupedyears[$groupnumber][]=$year;
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
  <h1>Applications (<?php echo $vcdb->makeName($makeid).' '.$vcdb->modelName($modelid);?>)</h1>
  <?php
  echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[0] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';
  if(isset($groupedyears[1])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[1] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
  if(isset($groupedyears[2])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[2] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
  if(isset($groupedyears[3])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[3] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
  if(isset($groupedyears[4])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[4] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
  if(isset($groupedyears[5])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[5] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
  if(isset($groupedyears[6])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[6] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
  if(isset($groupedyears[7])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[7] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
  echo '<div style="clear:both;"></div>';?>
 </body>
</html>

