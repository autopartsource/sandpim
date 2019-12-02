<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pimClass.php');
include_once('/var/www/html/class/logsClass.php');
$navCategory = 'import/export';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$v=new vcdb;
$pim= new pim;
$logs = new logs;
$error_msg=false;


if(isset($_POST['submit']) && intval($_POST['jobid'])>0)
{
 if($_POST['submit']=='Start')
 {
  $pim->updateBackgroundjob(intval($_POST['jobid']),'started','starting process',0,'0000-00-00 00:00:00');
  $logs->logSystemEvent('acesimport', isset($_SESSION['userid']), 'import job '.intval($_POST['jobid']).' started');
 }
 if($_POST['submit']=='Cancel')
 {
  $pim->updateBackgroundjob(intval($_POST['jobid']),'canceled','canceled by user',0,'0000-00-00 00:00:00');
  $logs->logSystemEvent('acesimport', isset($_SESSION['userid']), 'import job '.intval($_POST['jobid']).' canceled');
 }
 if($_POST['submit']=='Hide'){$pim->hideBackgroundjob(intval($_POST['jobid']));}
}


if(isset($_POST['submit']) && $_POST['submit']=='Import')
{
 $target_dir = '/var/www/html/ACESuploads/'; $target_file = $target_dir.basename($_FILES['fileToUpload']['name']);

 // Check if file already exists
 if(!file_exists($target_file))
 {
  if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_file))
  {
   $userid=0; $outputfile=''; $parameters='appcategory:'.$_POST['appcategory'].';'; $datetimetostart=date('Y-m-d H:i:s');
   $jobid=$pim->createBackgroundjob('ACESxmlImport','uploaded',$userid,$target_file,$outputfile,$parameters,$datetimetostart);
   $error_msg='The file ['. basename( $_FILES['fileToUpload']['name']). '] has been uploaded and is ready to import (job id:'.$jobid.')';
   $logs->logSystemEvent('acesimport', isset($_SESSION['userid']), 'The file ['. basename( $_FILES['fileToUpload']['name']). '] has been uploaded and is ready to import (job id:'.$jobid.')');
  }
  else
  {
   $error_msg='Error uploading file';
  }
 }
 else
 {
  $error_msg='File already exists';
 }
}

$appcategories=$pim->getAppCategories();
$jobs=$pim->getBackgroundjobs('ACESxmlImport','%');

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
        <h1>Import ACES by file upload</h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain" style="flex-direction: column;">
              <?php if($jobs){ ?>
              <div style="border-style: groove;padding:10px;">
               <div>Existing ACES Import Jobs</div>
                <table border="1">
                 <tr><th>Job Type</th><th>Status</th><th>File</th><th>Created On</th><th>Actions</th></tr>
                 <?php foreach($jobs as $job){?>
                 <form method="post"><input type="hidden" name="jobid" value="<?php echo $job['id'];?>"/><tr><td><?php echo $job['jobtype'];?></td><td><?php echo $job['status'];?></td><td><?php echo basename($job['inputfile']);?> <?php echo basename($job['outputfile']);?></td><td><?php echo $job['datetimecreated'];?></td><td><?php if($job['status']=='uploaded'){echo '<input type="submit" name="submit" value="Start"/><input type="submit" name="submit" value="Cancel"/>';} if($job['status']=='complete' || $job['status']=='canceled'){echo '<input type="submit" name="submit" value="Hide"/>';}?></td></tr></form>
                 <?php }?>
                </table>
               </div>
              <?php }?>
              <div>
               <?php if($error_msg){echo $error_msg;}?>
               <form method="post" enctype="multipart/form-data">
                <div style="padding:10px;"><input type="file" name="fileToUpload" id="fileToUpload"></div>
                <div style="padding:10px;">App Category <select name="appcategory"><?php foreach($appcategories as $appcategory){?> <option value="<?php echo $appcategory['id'];?>"><?php echo $appcategory['name'];?></option><?php }?></select></div>
                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
               </form>
              </div>
            </div>
            <div class="contentRight"></div>
        </div>
    </body>
</html>
