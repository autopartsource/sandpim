<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;

$makeid=intval($_GET['makeid']);
$models=$vcdb->getModels($makeid);


$groupcount=7;
$modelcount=count($models);
if($modelcount<=70){$groupcount=6;}
if($modelcount<=60){$groupcount=5;}
if($modelcount<=40){$groupcount=4;}
if($modelcount<=30){$groupcount=3;}
if($modelcount<=20){$groupcount=2;}
if($modelcount<=10){$groupcount=1;}
//comment

$groupsize=intval(count($models)/$groupcount);
$i=0; $groupnumber=0; $groupemodeles=array();
foreach($models as $model)
{
 $groupedmodels[$groupnumber][]=$model;
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
  <h1>Applications (<?php echo $vcdb->makeName($makeid); ?>)</h1>
   <?php
  echo '<div style="float:left;padding:10px;">'; foreach($groupedmodels[0] as $model){echo '<div style="padding:3px;"><a href="mmySelectYear.php?makeid='.$makeid.'&modelid='.$model['id'].'">'.$model['name'].'</a></div>';} echo '</div>';
  if(isset($groupedmodels[1])){echo '<div style="float:left;padding:10px;">'; foreach($groupedmodels[1] as $model){echo '<div style="padding:3px;"><a href="mmySelectYear.php?makeid='.$makeid.'&modelid='.$model['id'].'">'.$model['name'].'</a></div>';} echo '</div>';}
  if(isset($groupedmodels[2])){echo '<div style="float:left;padding:10px;">'; foreach($groupedmodels[2] as $model){echo '<div style="padding:3px;"><a href="mmySelectYear.php?makeid='.$makeid.'&modelid='.$model['id'].'">'.$model['name'].'</a></div>';} echo '</div>';}
  if(isset($groupedmodels[3])){echo '<div style="float:left;padding:10px;">'; foreach($groupedmodels[3] as $model){echo '<div style="padding:3px;"><a href="mmySelectYear.php?makeid='.$makeid.'&modelid='.$model['id'].'">'.$model['name'].'</a></div>';} echo '</div>';}
  if(isset($groupedmodels[4])){echo '<div style="float:left;padding:10px;">'; foreach($groupedmodels[4] as $model){echo '<div style="padding:3px;"><a href="mmySelectYear.php?makeid='.$makeid.'&modelid='.$model['id'].'">'.$model['name'].'</a></div>';} echo '</div>';}
  if(isset($groupedmodels[5])){echo '<div style="float:left;padding:10px;">'; foreach($groupedmodels[5] as $model){echo '<div style="padding:3px;"><a href="mmySelectYear.php?makeid='.$makeid.'&modelid='.$model['id'].'">'.$model['name'].'</a></div>';} echo '</div>';}
  if(isset($groupedmodels[6])){echo '<div style="float:left;padding:10px;">'; foreach($groupedmodels[6] as $model){echo '<div style="padding:3px;"><a href="mmySelectYear.php?makeid='.$makeid.'&modelid='.$model['id'].'">'.$model['name'].'</a></div>';} echo '</div>';}
  if(isset($groupedmodels[7])){echo '<div style="float:left;padding:10px;">'; foreach($groupedmodels[7] as $model){echo '<div style="padding:3px;"><a href="mmySelectYear.php?makeid='.$makeid.'&modelid='.$model['id'].'">'.$model['name'].'</a></div>';} echo '</div>';}
  echo '<div style="clear:both;"></div>';?>
 </body>
</html>

