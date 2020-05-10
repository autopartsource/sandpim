<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'settings';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$logs = new logs;

if (isset($_POST['submit']) && $_POST['submit']=='Save') 
{
 if($_POST['name']!=$_POST['oldname'])   
 {
  $pim->updatePartcategoryName(intval($_POST['id']), $_POST['name']); 
  $logs->logSystemEvent('partcategorychange', $_SESSION['userid'], 'Part Category '.$_POST['id'].' name was changed from '.$_POST['oldname'].' to '.$_POST['name']);
 }
 if($_POST['brandID']!=$_POST['oldbrandID'])   
 {
  $pim->updatePartcategoryBrandID(intval($_POST['id']), $_POST['brandID']); 
  $logs->logSystemEvent('partcategorychange', $_SESSION['userid'], 'Part Category '.$_POST['id'].' brandAAIAID was changed from '.$_POST['oldbrandID'].' to '.$_POST['brandID']);
 }
 
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./partCategories.php'\" /></head><body></body></html>";
 exit;
}


$profile = $pim->getReceiverprofileById(intval($_GET['id']));

?>

<!DOCTYPE html>
<html>
    <head>
        <?php include('/var/www/html/includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>

        <!-- Header -->
        <h3>Receiver Profile</h3>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
             <form action="" method="post">
              <table>
               <tr>
                <th>ID</th><td><?php echo $profile['id'];?><input type="hidden" name="id" value="<?php echo $partcategory['id'];?>"/><input type="hidden" name="oldname" value="<?php echo $profile['name'];?>"/></td></tr>
               <tr><th>Name</th><td><input type="text" name="name" value="<?php echo $profile['name'];?>"/></td></tr>
               <tr><th>Data</th><td><textarea name="profiledata"><?php echo $profile['data'];?></textarea></td></tr>
               <tr><th></th><td><input name="submit" type="submit" value="Save"/></td></tr>
              </table>
             </form>
            </div>
            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>