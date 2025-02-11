<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
$navCategory = 'import';

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importACESxml.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$v=new vcdb;
$logs = new logs;
$error_msg=false;

$partcategories=$pim->getPartCategories();

if(isset($_POST['submit']) && intval($_POST['jobid'])>0)
{
 if($_POST['submit']=='Start')
 {
  //$pim->updateBackgroundjob_status(intval($_POST['jobid']),'started','starting process',0,'0000-00-00 00:00:00');
  $pim->updateBackgroundjobStatus(intval($_POST['jobid']), 'started', 0);
  
  $logs->logSystemEvent('acesimport', isset($_SESSION['userid']), 'import job '.intval($_POST['jobid']).' started');
 }
 if($_POST['submit']=='Cancel')
 {
  //$pim->updateBackgroundjob(intval($_POST['jobid']),'canceled','canceled by user',0,'0000-00-00 00:00:00');
  $pim->updateBackgroundjobStatus(intval($_POST['jobid']), 'canceled', 0);
  
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
   $userid=0; $outputfile=''; $parameters='partcategory:'.intval($_POST['partcategory']).';'; $datetimetostart=date('Y-m-d H:i:s');
   $jobid=$pim->createBackgroundjob('ACESxmlImport','uploaded',$userid,$target_file,$outputfile,$parameters,$datetimetostart, '', '');
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
  $error_msg='File already exists. Change the name of the file and upload it again.';
 }
}

$jobs=$pim->getBackgroundjobs('ACESxmlImport','%');
?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Import ACES by file upload</h3>

                        <div class="card-body">
                            <div class="card shadow-sm">
                                <!-- Header -->
                                <h5 class="card-header text-start">Existing ACES Import Jobs</h5>

                                <div class="card-body">
                                    <?php if(count($jobs)){ ?>
                                    <table class="table" border="1">
                                        <tr><th>Job Type</th><th>Status</th><th>File</th><th>Created On</th><th>Actions</th></tr>
                                        <?php foreach ($jobs as $job) { ?>
                                            <form method="post"><input type="hidden" name="jobid" value="<?php echo $job['id']; ?>"/><tr><td><?php echo $job['jobtype']; ?></td><td><?php echo $job['status']; ?></td><td><?php echo basename($job['inputfile']); ?> <?php echo basename($job['outputfile']); ?></td><td><?php echo $job['datetimecreated']; ?></td><td><?php if ($job['status'] == 'uploaded') {
                                        echo '<input type="submit" name="submit" value="Start"/><input type="submit" name="submit" value="Cancel"/>';
                                    } if ($job['status'] == 'complete' || $job['status'] == 'canceled') {
                                        echo '<input type="submit" name="submit" value="Hide"/>';
                                    } ?></td></tr></form>
                                    <?php } ?>
                                    </table>
                                    <?php }?>
                                </div>
                            </div>
                            <div class="card shadow-sm">

                                <div class="card-body">
                                    <?php if($error_msg){echo '<div class="alert alert-success" role="alert">'.$error_msg.'</div>';}?>
                                    <form method="post" enctype="multipart/form-data">
                                        <div style="padding:10px;"><input type="file" name="fileToUpload" id="fileToUpload"></div>

                                        <div>Category for parts created during import: <select name="partcategory" <?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"><?php echo $partcategory['name']; ?></option><?php } ?></select> <a href="./partCategories.php"><img src="./settings.png" width="18" alt="settings"/></a></div>

                                        <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>
