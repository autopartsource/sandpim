<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
$navCategory = 'applications';

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
        <?php include('/var/www/html/includes/header.php'); ?>
        <script>
            function selectUnselectAppcategory(userid,appcategory)
            {
             if(document.getElementById('appcategory_'+appcategory).checked) 
             { // appcategory has been clocked on 
              console.log(appcategory);
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxSelectUnselectUserAppcateory.php?userid='+userid+'&appcategory='+appcategory+'&action=select');
              xhr.send();
             }
             else
             { // appcategory has been clocked off
              var xhr = new XMLHttpRequest();
              console.log(appcategory);

              xhr.open('GET', 'ajaxSelectUnselectUserAppcateory.php?userid='+userid+'&appcategory='+appcategory+'&action=unselect');
              xhr.send();
             }
            }
        </script>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>Applications (<?php echo $vcdb->makeName($makeid).', '.$vcdb->modelName($modelid).', '.$yearid;?>)</h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <form action="showAppsByBasevehicle.php">
                    <div style="padding:20px;">
                     <?php foreach($appcategories as $appcategory)
                     {
                         $checked=''; if($appcategory['selected']){$checked=' checked';}
                         echo '<div style="padding:5px"><input type="checkbox" id="appcategory_'.$appcategory['id'].'" onclick="selectUnselectAppcategory(\''.$userid.'\',\''.$appcategory['id'].'\')" name="appcategory_'.$appcategory['id'].'"'.$checked.'><label style="padding:5px;border: 1px solid;margin:3px; border-radius:5px"for="appcategory_'.$appcategory['id'].'">'.$appcategory['name'].'<img style="padding:0px 5px 0px" height="17px" src="'.$appcategory['logouri'].'"></label></div>';
                     }?>
                     <input type="hidden" name="makeid" value="<?php echo $makeid;?>"/>
                     <?php if(isset($modelid)){echo '<input type="hidden" name="modelid" value="'.$modelid.'"/>';}
                     if(isset($yearid)){echo '<input type="hidden" name="yearid" value="'.$yearid.'"/>';}
                     if(isset($equipmentid)){echo '<input type="hidden" name="equipmentid" value="'.$equipmentid.'"/>';} ?>
                     <div style="padding-top:10px;"><input type="submit" name="submit" value="Show Applications"/></div>
                    </div>
                </form>
            </div>

            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>
