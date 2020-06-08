<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'settings';

session_start();
if (!isset($_SESSION['userid']))
{
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
 exit;
}

$pim = new pim;
$logs = new logs;

if (isset($_POST['submit']) && $_POST['submit']=='Save') 
{
 $profiledata=str_replace("\r\n",';',$_POST['profiledata']);

 $pim->updateReceiverprofile(intval($_POST['id']), $_POST['profilename'],$profiledata); 
 $logs->logSystemEvent('receiverprofilechange', $_SESSION['userid'], 'Receiver Profile '.$_POST['id'].' was changed.');
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./receiverProfiles.php'\" /></head><body></body></html>";
 exit;
}

if (isset($_POST['submit']) && $_POST['submit']=='Delete') 
{
 $pim->deleteReceiverprofile(intval($_POST['id'])); 
 $logs->logSystemEvent('receiverprofiledelete', $_SESSION['userid'], 'Receiver Profile '.$_POST['id'].' was deleted.');
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./receiverProfiles.php'\" /></head><body></body></html>";
 exit;
}



$profile = $pim->getReceiverprofileById(intval($_GET['id']));

$profile['data']=str_replace(';',"\r\n",$profile['data']);


?>

<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
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
                <th>ID</th><td><?php echo $profile['id'];?><input type="hidden" name="id" value="<?php echo $profile['id'];?>"/><input type="hidden" name="oldname" value="<?php echo $profile['name'];?>"/></td></tr>
               <tr><th>Name</th><td><input type="text" name="profilename" value="<?php echo $profile['name'];?>"/></td></tr>
               <tr><th>Data</th><td><textarea name="profiledata" rows="20" cols="50"><?php echo $profile['data'];?></textarea></td></tr>
               <tr><th></th><td><input name="submit" type="submit" value="Save"/> <input name="submit" type="submit" value="Delete"/></td></tr>
              </table>
             </form>
            </div>
            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('./includes/footer.php'); ?>
    </body>
</html>