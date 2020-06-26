<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'reports';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;
$pim=new pim;

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
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain button" style="flex-direction: column;">
                <div style="padding:10px;"><a href="partReferencesReportForm.php">Part PCdb Validation</a></div>
                <div style="padding:10px;"><a href="missingProductDataReportForm.php">Part Missing Product Data</a></div>
                <div style="padding:10px;"><a href="applicationReferencesReportForm.php">Application VCdb validation</a></div>
                <div style="padding:10px;"><a href="applicationHolesReportForm.php">Application Holes</a></div>
                <div style="padding:10px;"><a href="applicationOverlapsReportForm.php">Application Overlaps</a></div>
                <div style="padding:10px;"><a href="applicationNotesReportForm.php">Application Note Usage</a></div>
            </div>

            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>