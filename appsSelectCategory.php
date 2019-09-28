<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');

$vcdb=new vcdb;
$pim = new pim;

$makeid=intval($_GET['makeid']);
if(isset($_GET['modelid'])){$modelid=intval($_GET['modelid']);}
if(isset($_GET['yearid'])){$yearid=intval($_GET['yearid']);}
if(isset($_GET['equipmentid'])){$equipmentid=intval($_GET['equipmentid']);}

$appcategories=$pim->getAppCategories();


?>
<!DOCTYPE html>
<html>
 <head>
 </head>
 <body>
  <?php include('topnav.inc');?>
   <form action="showAppsByBasevehicle.php">
    <div style="border-style: groove;">
     <div style="padding:10px;font-size:25px;">Apps - Select Categories</div>
      <div style="padding:20px;">
      <?php foreach($appcategories as $appcategory){
       echo '<div><input type="checkbox" id="appcategory_'.$appcategory['id'].'" name="appcategory_'.$appcategory['id'].'"><label for="appcategory_'.$appcategory['id'].'">'.$appcategory['name'].'</label></div>';
     }?>
     <input type="hidden" name="makeid" value="<?php echo $makeid;?>"/>
     <?php if(isset($modelid)){echo '<input type="hidden" name="modelid" value="'.$modelid.'"/>';}
     if(isset($yearid)){echo '<input type="hidden" name="yearid" value="'.$yearid.'"/>';}
     if(isset($equipmentid)){echo '<input type="hidden" name="equipmentid" value="'.$equipmentid.'"/>';} ?>
     <input type="submit" name="submit" value="Show Applications"/>
    </div>
   </form>
  </div>
 </body>
</html>

