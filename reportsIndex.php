<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'reports';

$pim = new pim;
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// ip-based ACL enforcement - bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'reportsIndex.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;

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
        <h1>Reports</h1>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div style="padding:10px;"><a href="partReferencesReportForm.php">Parts PCdb Validation</a></div>
                    <div style="padding:10px;"><a href="missingProductDataReportForm.php">Parts Missing Product Data</a></div>
                    <div style="padding:10px;"><a href="applicationReferencesReportForm.php">Application VCdb validation</a></div>
                    <div style="padding:10px;"><a href="applicationHolesReportForm.php">Application Holes</a></div>
                    <div style="padding:10px;"><a href="applicationOverlapsReportForm.php">Application Overlaps</a></div>
                    <div style="padding:10px;"><a href="applicationNotesReportForm.php">Application Note Usage</a></div>
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