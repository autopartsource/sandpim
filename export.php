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
 $logs->logSystemEvent('accesscontrol',0, 'export.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$logs = new logs;

$export=$pim->getExport(intval($_GET['exportid']));


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
                        <h3 class="card-header text-start">Export <?php echo $export['id'];?></h3>

                        <div class="card-body">
                            <table class="table">
                                <tr><th>Receiver Profile</th><td><?php echo $pim->receiverprofileName($export['receiverprofileid']);?></td></tr>
                                <tr><th>Export Type</th><td><?php echo $export['type'];?></td></tr>
                                <tr><th>Date/Time Exported</th><td><?php echo $export['datetimeexported'];?></td></tr>
                                <tr><th>Notes</th><td><?php echo $export['notes'];?></td></tr>
                                <tr><th>Tracked apps depending on this export</th><td><?php echo $pim->countReceiverAppStatesForExport($export['id']);?></td></tr>
                                <tr><th>Actions</th><td><a href="./exports.php?action=delete&exportid=<?php echo $export['id'];?>">Delete</a></td></tr>
                                
                                

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