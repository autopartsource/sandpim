<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
$navCategory = 'applications';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'equipmentAppsIndex.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;

if (isset($_GET['all'])) {
    $makes = $vcdb->getMakes();
} else {
    $makes = $pim->getFavoriteMakes();
    ;
}

$makecount = count($makes);
$groupsize = intval(count($makes) / 6);
$i = 0;
$groupnumber = 0;
$groupedmakes = array();
foreach ($makes as $make) {
    $groupedmakes[$groupnumber][] = $make;
    $i++;
    if ($i > $groupsize) {
        $i = 0;
        $groupnumber++;
    }
}
$groupedMakesCount = count($groupedmakes);
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
                        <h3 class="card-header text-start">Make/Equipment Applications</h3>
                        
                        <div class="card-body">
                            <div class="container">
                                <div class="row row-cols-1 row-cols-sm-2 <?php if($groupedMakesCount > 4) {echo "row-cols-md-"; echo $groupedMakesCount-2;} else {echo "row-cols-md-"+$groupedMakesCount;} echo" row-cols-lg-"; echo $groupedMakesCount;?>">
                                <?php
                                for($y = 0;$y < $groupedMakesCount;$y++) {
                                    echo '<div class="col-sm">';
                                    echo '<div class="d-grid gap-2 mx-auto">';
                                    foreach ($groupedmakes[$y] as $make) {
                                        echo '<a href="meSelectModel.php?makeid=' . $make['id'] . '"class="btn btn-secondary" role="button" aria-disabled="true">' . $make['name'] . '</a>';
                                    }   
                                    echo '</div>';
                                    echo '</div>';
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
