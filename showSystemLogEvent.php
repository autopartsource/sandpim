<?php
include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');

$navCategory = 'logs';

$pim = new pim;
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// ip-based ACL enforcement - bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'showSystemLogEntry.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$configGet= new configGet;
$logs=new logs;

$id=intval($_GET['id']);
$event=$logs->getSystemEvent($id);

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
                <div class="col-xs-12 col-md-7 my-col colMain">                    
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <?php print_r($event);?>
                        </div>
                    </div>
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-3 my-col colRight">
                    <div class="card shadow-sm"></div>
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>