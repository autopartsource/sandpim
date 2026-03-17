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

$dependantapps=$pim->countReceiverAppStatesForExport($export['id']);

if($dependantapps==0)
{
 $deleteexplainer='There are no receiver app states that depend on this export for producing future net-change (UPDATE) files. It is safe to delete this export to free-up storage and processing resources. However, preserving some historical exports can provide forensic/trouble-shooting value.';    
}
else
{
 $deleteexplainer='This export contains apps that may be required for generating future net-change (UPDATE) files. It is bad idea to delete this export unless you really understand the risk.';        
}

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
                                <tr><th>Tracked apps depending on this export</th><td><?php echo $dependantapps;?></td></tr>
                                <tr><th>Actions</th>
                                    <td>
                                        <table class="table">
                                            <tr><td><a href="./exports.php?action=delete&exportid=<?php echo $export['id'];?>" class="btn btn-secondary">Delete</a></td><td><?php echo $deleteexplainer;?></td></tr>
                                            <tr><td><a href="./exports.php?action=capture-merge&exportid=<?php echo $export['id'];?>&receiverprofileid=<?php echo $export['receiverprofileid'];?>" class="btn btn-secondary">Capture (merge)</a></td><td>Apply this export to the receiver profile. Apps that are in the export and not in the profile will be added to the profile. Apps that only appear in the profile and not in the export will be deleted from the profile. Exported apps found in the profile will cause the profile app to reflect the export ID as its source. Use this option with receivers that process UPDATE files when you are confident that an export was consumed into their process.</td></tr>
                                            <tr><td><a href="./exports.php?action=capture-replace&exportid=<?php echo $export['id'];?>&receiverprofileid=<?php echo $export['receiverprofileid'];?>" class="btn btn-secondary">Capture (replace)</a></td><td>Apply this export to the receiver profile. Remove any all pre-existing apps from the receiver first. Use this option for receivers who get FULL files after when you are confident that an export was consumed into their process.</td></tr>
                                            <tr><td><a href="./exportStream.php?format=raw-apps&exportid=<?php echo $export['id'];?>" class="btn btn-secondary">Download (raw)</a></td><td>Download export as VCdb-coded apps in a text file with app IDs and OIDs (states).</td></tr>
                                            <tr><td><a href="./exportStream.php?format=decoded-apps&exportid=<?php echo $export['id'];?>" class="btn btn-secondary">Download (nice)</a></td><td>Download export as full human-readable apps in a text file with app IDs and OIDs (states).</td></tr>
                                            <tr><td><a href="./exportStream.php?format=meta&exportid=<?php echo $export['id'];?>" class="btn btn-secondary">Download (meta)</a></td><td>Download export as a two-column text file of the app IDs and OIDs (states).</td></tr>
                                        </table>
                                    </td>
                                </tr>
                                
                                

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