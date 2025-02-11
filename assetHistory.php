<?php
include_once('./class/assetClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'assets';


$pim = new pim;
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// ip-based ACL enforcement - bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'assetHistory.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset = new asset;
$logs=new logs;
$user=new user;

$assetid = $_GET['assetid'];
$a = $asset->validAsset($assetid);
$history = $logs->getAssetEvents($assetid, 50);
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
                        <h3 class="card-header text-start">History for <a href="./showAsset.php?assetid=<?php echo urlencode($assetid);?>"><span class="text-info"><?php echo $assetid?></span></a></h3>
                        <div class="card-body">
                            <?php
                            if ($a && count($history)) {
                                echo '<table class="table"><tr><th>Date/Time</th><th>User</th><th>Change Description</th><th>OID After Change</th></tr>';
                                foreach ($history as $record) {
                                    echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td>' . $record['description'] . '</td><td>' . $record['new_oid'] . '</td></tr>';
                                }
                                echo '</table>';
                            }
                            else
                            {
                                echo 'No history found';
                            }
                            ?>
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

