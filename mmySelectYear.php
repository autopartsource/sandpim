<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');

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
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <div style="padding:10px;font-size:25px;">Apps by make/model/year - Select year of <?php echo $vcdb->makeName($makeid); ?>, <?php echo $vcdb->modelName($modelid); ?></div>
  <?php
   echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[0] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';
   if(isset($groupedyears[1])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[1] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
   if(isset($groupedyears[2])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[2] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
   if(isset($groupedyears[3])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[3] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
   if(isset($groupedyears[4])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[4] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
   if(isset($groupedyears[5])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[5] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
   if(isset($groupedyears[6])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[6] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
   if(isset($groupedyears[7])){echo '<div style="float:left;padding:10px;">'; foreach($groupedyears[7] as $year){echo '<div style="padding:3px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'">'.$year['id'].'</a></div>';} echo '</div>';}
   echo '<div style="clear:both;"></div>';
   ?>
 </div>
 </body>
</html>

