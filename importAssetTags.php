<?php
include_once('./class/pimClass.php');
$navCategory = 'import';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importAssetTags.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

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
        <h1></h1>
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
                        <h3 class="card-header text-start">Import digital asset tags from text</h3>

                        <div class="card-body">
                            <form method="post" action="importAssetTagsProcess.php">
                                <div class="alert alert-secondary" role="alert">Paste 2 tab-delimited columns (no header row): AssetID, AssetTag</div>
                                <div style="padding:10px;">
                                    <textarea style="width:100%;" name="assets" rows="10"></textarea>
                                </div>
                                
                                <div style="padding:10px;"><input type="checkbox" name="removeexisting"/>Remove existing tags for the assets included before adding new ones</div>
                                <div style="padding:10px;"><input name="submit" type="submit" value="Next"/></div>
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