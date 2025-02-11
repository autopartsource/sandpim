<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
$navCategory = 'import';

$pim = new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'importPartText.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

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
                        <h3 class="card-header text-start">Create parts from tab-delimited text</h3>

                        <div class="card-body">
                            <h5 class="card-subtitle mb-2 text-muted"></h5>
                            <form method="post" action="importPartTextProcess.php">

                                <div style="padding:10px;"><div>Items</div>
                                    <textarea name="items" style="width:100%;height:200px;"></textarea>
                                </div>
                                <div style="padding:10px;"><div>Descriptions</div>
                                    <textarea name="descriptions" rows="5" cols="100"></textarea>
                                </div>

                                <div style="padding:10px;"><div>Prices</div>
                                    <textarea name="prices" rows="5" cols="100"></textarea>
                                </div>

                                <div style="padding:10px;"><div>EXPI</div>
                                    <textarea name="expi" rows="5" cols="100"></textarea>
                                </div>

                                <div style="padding:10px;"><div>Attributes</div>
                                    <textarea name="attributes" rows="5" cols="100"></textarea>
                                </div>

                                <div style="padding:10px;"><div>Packages</div>
                                    <textarea name="packages" rows="5" cols="100"></textarea>
                                </div>

                                <div style="padding:10px;"><div>Interchanges</div>
                                    <textarea name="interchanges" rows="5" cols="100"></textarea>
                                </div>

                                <div style="padding:10px;"><div>Digital Assets</div>
                                    <textarea name="assets" rows="5" cols="100"></textarea>
                                </div>
                                <div><select name="partcategory"><option value="">Do not create new parts</option><?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"><?php echo $partcategory['name']; ?></option><?php } ?></select></div>
                                <div><input type="checkbox" name="doimport"/>Do import (uncheck for test run)</div>
                                <div><input name="submit" type="submit" value="Import"/></div>
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