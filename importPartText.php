<?php
include_once('./class/pimClass.php');

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

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
                        <h3 class="card-header text-start">Product data structured text import</h3>

                        <div class="card-body">
                            <h5 class="card-subtitle mb-2 text-muted">Step 1: copy/paste data from the template spreadsheet</h5>
                            <form method="post" action="importPartTextProcess.php">

                                <div style="padding:10px;"><div>Items</div>
                                    <textarea name="items" rows="6" cols="130"></textarea>
                                </div>

                                <div style="padding:10px;"><div>Descriptions</div>
                                    <textarea name="descriptions" rows="6" cols="130"></textarea>
                                </div>

                                <div style="padding:10px;"><div>Prices</div>
                                    <textarea name="prices" rows="6" cols="130"></textarea>
                                </div>

                                <div style="padding:10px;"><div>EXPI</div>
                                    <textarea name="expi" rows="6" cols="130"></textarea>
                                </div>

                                <div style="padding:10px;"><div>Attributes</div>
                                    <textarea name="attributes" rows="6" cols="130"></textarea>
                                </div>

                                <div style="padding:10px;"><div>Packages</div>
                                    <textarea name="packages" rows="6" cols="130"></textarea>
                                </div>

                                <div style="padding:10px;"><div>Interchanges</div>
                                    <textarea name="interchanges" rows="6" cols="130"></textarea>
                                </div>

                                <div style="padding:10px;"><div>Digital Assets</div>
                                    <textarea name="assets" rows="6" cols="130"></textarea>
                                </div>

                                Category <select name="partcategory"><?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"><?php echo $partcategory['name']; ?></option><?php } ?></select>
                                <input type="checkbox" name="doimport"/>Do import (uncheck for test run)<div style="padding:10px;"><input name="submit" type="submit" value="Next"/></div>
                            </form>
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