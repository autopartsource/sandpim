<?php
include_once('./class/pimClass.php');

$pim = new pim;
$partcategories = $pim->getPartCategories();

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
        <h1>Digital Assets (metadata) import from structured text</h1>
        <h3>Step 1: copy/paste data from the template spreadsheet</h3>

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <form method="post" action="importAssetTextProcess.php">

                        <div style="padding:10px;"><div>Tab-delimited text</div>
                            <textarea name="assets" rows="6" cols="130"></textarea>
                        </div>

                        <input type="checkbox" name="doimport"/>Do import (uncheck for test run)<div style="padding:10px;"><input name="submit" type="submit" value="Next"/></div>
                    </form>
                
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