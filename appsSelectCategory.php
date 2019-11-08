<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$userid=$_SESSION['userid'];

$vcdb=new vcdb;
$pim = new pim;
$user=new user;

$makeid=intval($_GET['makeid']);
if(isset($_GET['modelid'])){$modelid=intval($_GET['modelid']);}
if(isset($_GET['yearid'])){$yearid=intval($_GET['yearid']);}
if(isset($_GET['equipmentid'])){$equipmentid=intval($_GET['equipmentid']);}

//$allappcategories=$pim->getAppCategories();

$appcategories=$user->getUserVisibleAppcategories($userid);


?>
<!DOCTYPE html>
<html>
 <head>
  <link rel="stylesheet" type="text/css" href="styles.css">
 </head>
 <body>
  <?php include('topnav.php');?>
  <form action="showAppsByBasevehicle.php">
   <h1>Applications (<?php echo $vcdb->makeName($makeid).', '.$vcdb->modelName($modelid).', '.$yearid;?>)</h1>
   <div style="padding:20px;">
    <?php foreach($appcategories as $appcategory){echo '<div><input type="checkbox" id="appcategory_'.$appcategory['id'].'" name="appcategory_'.$appcategory['id'].'" checked><label for="appcategory_'.$appcategory['id'].'">'.$appcategory['name'].'</label></div>';}?>
    <input type="hidden" name="makeid" value="<?php echo $makeid;?>"/>
    <?php if(isset($modelid)){echo '<input type="hidden" name="modelid" value="'.$modelid.'"/>';}
    if(isset($yearid)){echo '<input type="hidden" name="yearid" value="'.$yearid.'"/>';}
    if(isset($equipmentid)){echo '<input type="hidden" name="equipmentid" value="'.$equipmentid.'"/>';} ?>
    <div style="padding-top:10px;"><input type="submit" name="submit" value="Show Applications"/></div>
   </div>
  </form>
 </body>
</html>

