<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'utilities';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;
$pim= new pim;

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
        <h1>Utilities</h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain button" style="flex-direction: column;">
                <div style="padding:10px;"><a href="buyersGuideBuilder.php">Buyers Guide Builder</a></div>
                <div style="padding:10px;"><a href="basevidsToMMYinput.php">Convert BaseVehicleIDs to Makes/Models/Years</a></div>
                <div style="padding:10px;"><a href="MMYtoBasevidsInput.php">Convert Makes/Models/Years to BaseVehicleIDs</a></div>
                <div style="padding:10px;"><a href="noteManager.php">Fitment Note Management</a></div>
            </div>

            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>