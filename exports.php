<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsClass.php');

$navCategory='settings';

$pim = new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exports.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$logs = new logs;

if(isset($_GET['exportid']) && isset($_GET['action']))
{
 $userid=$_SESSION['userid'];
 $export=$pim->getExport(intval($_GET['exportid']));
 if($export)
 {
  if($_GET['action']=='delete')
  {
   $pim->deleteExport(intval($_GET['exportid']));
   $logs->logSystemEvent('exports', $userid, 'deleted export '.$export['id'].' of type '.$export['type'].' that was created on ['.$export['datetimeexported'].']');
  }
  
  if($_GET['action']=='capture-merge')
  {
   $clientfilename='capture_report.txt';
   $localfilename=random_int(1000000, 9999999);
   $receiverprofileid=intval($_GET['receiverprofileid']);
   $token=$pim->createBackgroundjob('ReceiverAppStateCapture','started',$_SESSION['userid'],'',$localfilename,'receiverprofile:'.$receiverprofileid.';exportid:'.$export['id'].';CaptureMode:MERGE',date('Y-m-d H:i:s'),'text',$clientfilename);
   $logs->logSystemEvent('exports', $userid, 'Created background job to capture (merge) app states from export '.$export['id'].' into receiverprofile '.$receiverprofileid);
  }
  
  if($_GET['action']=='capture-replace')
  {
   $clientfilename='capture_report.txt';
   $localfilename=random_int(1000000, 9999999);
   $receiverprofileid=intval($_GET['receiverprofileid']);
   $token=$pim->createBackgroundjob('ReceiverAppStateCapture','started',$_SESSION['userid'],'',$localfilename,'receiverprofile:'.$receiverprofileid.';exportid:'.$export['id'].';CaptureMode:REPLACE',date('Y-m-d H:i:s'),'text',$clientfilename);
   $logs->logSystemEvent('exports', $userid, 'Created background job to capture (replace) app states from export '.$export['id'].' into receiverprofile '.$receiverprofileid);
  }
  
 }
}




$exports=$pim->getExports();

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
                        <h3 class="card-header text-start">Exports</h3>

                        <div class="card-body">
                            <table class="table">
                                <tr><th>Exported Date/Time</th><th>Receiver Profile</th><th>Type</th><th>Notes</th></tr>
                            <?php
                            foreach ($exports as $export)
                            {
                                echo '<tr><td><a href="./export.php?exportid='.$export['id'].'">'.$export['datetimeexported'].'</a></td><td>'.$pim->receiverprofileName($export['receiverprofileid']).'</td><td>'.$export['type'].'</td><td>'.$export['notes'].'</td></tr>';
                            } ?>
                            </table>
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