<?php
include_once('/var/www/html/class/pimClass.php');
$navCategory = 'settings';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pim=new pim;

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
        <h1>Settings</h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain button" style="flex-direction: column;">
                <div style="padding:10px;"><a href="./users.php">User Maintenance</a></div>
                <div style="padding:10px;"><a href="./config.php">Configuration Parameters</a></div>
                <div style="padding:10px;"><a href="./pcdbTypeBrowser.php">Manage PCdb favorite parttypes</a></div>
                <div style="padding:10px;"><a href="./pcdbPositionBrowser.php">Manage PCdb favorite positions</a></div>
            </div>

            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>