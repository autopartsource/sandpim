<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
$navCategory = 'settings';

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
        <h1>Settings</h1>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div style="padding:10px;"><a href="./users.php">Users</a></div>
                    <div style="padding:10px;"><a href="./config.php">Configuration</a></div>
                    <div style="padding:10px;"><a href="./pcdbTypeBrowser.php">Favorite PCdb parttypes</a></div>
                    <div style="padding:10px;"><a href="./pcdbPositionBrowser.php">Favorite PCdb positions</a></div>
                    <div style="padding:10px;"><a href="./competitiveBrandBrowser.php">Competitive Brands</a></div>
                    <div style="padding:10px;"><a href="./partCategories.php">Part Categories</a></div>
                    <div style="padding:10px;"><a href="./receiverProfiles.php">Receiver Profiles</a></div>
                    <div style="padding:10px;"><a href="./priceSheets.php">Price Sheets</a></div>
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