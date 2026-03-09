<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsClass.php');

$navCategory='utilities';

$pim = new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exports.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$logs = new logs;

if(isset($_GET['exportid']))
{
 $export=$pim->getExport(intval($_GET['exportid']));
 if($export)
 {
  $userid=$_SESSION['userid'];
  $logs->logSystemEvent('exports', $userid, 'deleted export '.$export['id'].' of type '.$export['type'].' that was created on ['.$export['datetimeexported'].']');
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
                                <tr><th>Exported Date/Time</th><th>Receiver Profile</th><th>Type</th><th>Notes</th><th></th></tr>
                            <?php
                            foreach ($exports as $export)
                            {
                                echo '<tr><td>'.$export['datetimeexported'].'</td><td>'.$pim->receiverprofileName($export['receiverprofileid']).'</td><td>'.$export['type'].'</td><td>'.$export['notes'].'</td><td><a class="btn btn-secondary" href="./exports.php?exportid='.$export['id'].'">Delete</a></td></tr>';
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