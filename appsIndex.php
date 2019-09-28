<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');

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
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <h1>Applications - Select Make</h1>
   <?php
   echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[0] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
   echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[1] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
   echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[2] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
   echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[3] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
   echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[4] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
   echo '<div style="float:left;padding:15px;">'; foreach($groupedmakes[5] as $make){echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';} echo '</div>';
   echo '<div style="clear:both;"></div>';?>
 </div>
 </body>
</html>

