<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'import/export';

session_start();
if (!isset($_SESSION['userid']))
{
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
 exit;
}

$pim = new pim;
$logs = new logs;

if (isset($_POST['submit']) && $_POST['submit']=='Delete') 
{
 $pim->deleteBackgroundjob(intval($_POST['id'])); 
 $logs->logSystemEvent('backgroundjob', $_SESSION['userid'], 'Background job '.intval($_POST['id']).' was deleted.');
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./backgroundJobs.php'\" /></head><body></body></html>";
 exit;
}

$job = $pim->getBackgroundjob(intval($_GET['id']));

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
        <h3>Background import/export job</h3>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
             <form action="" method="post">
              <table>
               <tr><th>ID</th><td><?php echo $job['id'];?></td></tr>
               <tr><th>Type</th><td><?php echo $job['jobtype'];?></td></tr>
               <tr><th>Status</th><td><?php echo $job['status'];?></td></tr>
               <tr><th>Token</th><td><?php echo $job['token'];?></td></tr>
               <tr><th>Input File</th><td><?php echo $job['inputfile'];?></td></tr>
               <tr><th>Output File</th><td><?php echo $job['outputfile'];?></td></tr>
               <tr><th>Parameters</th><td><?php echo $job['parameters'];?></td></tr>
               <tr><th>Created on</th><td><?php echo $job['datetimecreated'];?></td></tr>
               <tr><th>Started on</th><td><?php echo $job['datetimestarted'];?></td></tr>
               <tr><th>Ended on</th><td><?php echo $job['datetimeended'];?></td></tr>
               <tr><th></th><td><input type="hidden" name="id" value="<?php echo $job['id'];?>"><input name="submit" type="submit" value="Delete"/></td></tr>
              </table>
             </form>
            </div>
            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('./includes/footer.php'); ?>
    </body>
</html>