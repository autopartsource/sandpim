<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');


$navCategory = '';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;

$issue=$pim->getIssueById(intval($_GET['id']));


?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
            <script>
            
        
            
            </script>
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
                <div class="col-xs-12 col-md-7 my-col colMain">
                    <div class="card shadow-sm">
 
                    <?php print_r($issue);?>
                    
                    
                    </div>
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-3 my-col colRight">
                </div>

            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>