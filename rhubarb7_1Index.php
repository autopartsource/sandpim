<?php
include_once('./class/logsClass.php');
include_once('./class/pimClass.php');
$navCategory = 'utilities';
session_start();

$logs=new logs();
$pim=new pim();

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php if (isset($_SESSION['userid'])){include('topnav.php');} ?>
        
        <!-- Header -->
        <h3></h3>
        
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
                        <h3 class="card-header text-start">Rhubarb 7.1</h3>
                            
                        <div class="card-body">
                            <h5 class="alert alert-secondary" type="alert">Tools for converting between Excel spreadsheets and PIES xml</h5>
                        
                            <div class="d-grid gap-2 col-6 mx-auto">
                                <a class="btn btn-secondary" href="convertExcelToPIES7_1upload.php" style="margin:5px">Create PIES xml from spreadsheet</a>
                                <a class="btn btn-secondary" href="convertPIES7_1toExcelUpload.php" style="margin:5px">Flatten PIES xml to spreadsheet</a>
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
<?php 
if (isset($_SESSION['userid']))
{
 include('./includes/footer.php');
}
else
{
?><div style="font-size: .75em; font-style: italic; color: #808080;"><?php  
 $logs->logSystemEvent('rhubarb', 0, 'index page load by:'.$_SERVER['REMOTE_ADDR']);
 include('./includes/storageDisclaimer.php');
?></div><?php  
}
?>
    </body> 
</html>