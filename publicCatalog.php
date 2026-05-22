<?php
include_once('./class/logsClass.php');
include_once('./class/pimClass.php');
$navCategory = 'search';
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
        
        <!-- Header -->
        <h3></h3>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-1 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-10 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->                           
                        <div class="card-body">
                            <h5 class="alert alert-secondary" type="alert">VGX Brakes Catalog</h5>
                            <div class="d-grid gap-2 col-6 mx-auto">
                                <a class="btn btn-secondary" href="./publicCatalogSearchParts.php" style="margin:5px">Part Number Search</a>
                                <a class="btn btn-secondary" href="" style="margin:5px">Vehicle Lookup - Make/Model/Year</a>
                                <a class="btn btn-secondary" href="" style="margin:5px">Vehicle Lookup - Make/System</a>
                                <a class="btn btn-secondary" href="" style="margin:5px">Contact Us</a>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-1 my-col colRight">
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
    </body> 
</html>