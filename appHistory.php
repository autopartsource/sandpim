<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
$navCategory = 'applications';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'sandpiper index.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


function niceAppAttributes($appattributes) {
    $vcdb = new vcdb;
    $niceattributes = array();
    foreach ($appattributes as $appattribute) {
        if ($appattribute['type'] == 'vcdb') {
            $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']);
        }
        if ($appattribute['type'] == 'note') {
            $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']);
        }
    }
    $nicefitmentstring = '';
    $nicefitmentarray = array();
    foreach ($niceattributes as $niceattribute) {
        // exclude cosmetic elements from the compiled list
        $nicefitmentarray[] = $niceattribute['text'];
    }
    return implode('; ', $nicefitmentarray);
}


$vcdb = new vcdb;
$pcdb = new pcdb;
$user=new user;

$appid = intval($_GET['appid']);
$app = $pim->getApp($appid);
$history = $pim->getAppEvents($appid, 25);
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
	<h2></h2>

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
                        <h3 class="card-header text-start">History for application <a href="./showApp.php?appid=<?php echo $appid?>"><span class="text-info"><?php echo $appid?></span></a></h3>

                        <div class="card-body">
                            
                            <!-- Vehicle and Attributes -->
                            <div class="card">
                                <!-- Header -->
                                <h4 class="card-header text-start"><?php echo $vcdb->niceMMYofBasevid($app['basevehicleid'])?></h4>

                                <div class="card-body">
                                    <?php echo niceAppAttributes($app['attributes']);?>
                                </div>
                            </div>
                            
                            <!-- Logs -->
                            <div class="card shadow-sm">
                                <!-- Header -->
                                <h5 class="card-header text-start">Logs</h5>

                                <div class="card-body scroll">
                                    <?php
                                    if ($app && count($history)) {
                                        echo '<table class="table"><tr><th>Date/Time</th><th>User</th><th>Change Description</th><th>OID After Change</th></tr>';
                                        foreach ($history as $record) {
                                            echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td>' . $record['description'] . '</td><td>' . $record['new_oid'] . '</td></tr>';
                                        }
                                        echo '</table>';
                                    } else { // no apps found
                                        echo 'No history found';
                                    }
                                    ?>
                                </div>
                            </div>
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

