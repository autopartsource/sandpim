<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsClass.php');

$navCategory='settings';

$pim = new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'processLocks.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$logs = new logs;

if(isset($_GET['lockid']))
{
 $lock=$pim->getLockById(intval($_GET['lockid']));
 if($lock)
 {
  $userid=$_SESSION['userid'];
  $pim->removeLockById($lock['id']);
  $logs->logSystemEvent('locks', $userid, 'deleted lock record '.$lock['id'].' of type '.$lock['type'].' that was created on ['.$lock['createdDatetime'].']');
 } 
}

$locks=$pim->getLocksByType();

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
                        <h3 class="card-header text-start">Lock Records</h3>

                        <div class="card-body">
                            <table class="table">
                                <tr><th>Lock Type</th><th>Lock Data</th><th>Created Date/Time</th><th></th></tr>
                            <?php
                            foreach ($locks as $lock)
                            {
                                echo '<tr><td>'.$lock['type'].'</td><td>'.$lock['data'].'</td><td>'.$lock['createdDatetime'].'</td><td><a class="btn btn-secondary" href="./processLocks.php?lockid='.$lock['id'].'">Remove</a></td></tr>';
                            } ?>
                            </table>
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