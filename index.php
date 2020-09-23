<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$navCategory = 'dashboard';


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$user = new user;
$asset=new asset;
$pim = new pim;
$logs = new logs;

$configGet = new configGet;
$appshistory = $logs->getAppsEvents(10);
$assetshistory = $logs->getAssetsEvents(10);
$partshistory = $logs->getPartsEvents(10);
$systemhistory = $logs->getSystemEvents('%', false, 10);

$logpreviewlength = intval($configGet->getConfigValue('logPreviewDescriptionLength', 80));
?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft"></div>
                
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
                        
                        <!-- Header -->
                        <h3 class="card-header text-left">Dashboard</h3>
                        
                        <!-- Main Content -->
                        <div class="card-body">
                            <?php
                            if(count($appshistory) || count($assetshistory) || count($partshistory) || count($systemhistory)) {
                            echo '<div class="card">
                                <h5 class="card-header text-left">History</h5>
                                <div class="card-body">';
                                echo '<ul class="nav nav-tabs" id="myTab" role="tablist">';
                                    if(count($appshistory)) {
                                    echo '<li class="nav-item">
                                        <a class="nav-link" id="applications-tab" data-toggle="tab" href="#applications" role="tab" aria-controls="applications" aria-selected="true">Applications</a>
                                    </li>';
                                    }

                                    if(count($assetshistory)) {
                                    echo '<li class="nav-item">
                                        <a class="nav-link" id="assets-tab" data-toggle="tab" href="#assets" role="tab" aria-controls="assets" aria-selected="false">Assets</a>
                                    </li>';
                                    }

                                    if(count($partshistory)) {
                                    echo '<li class="nav-item">
                                        <a class="nav-link" id="parts-tab" data-toggle="tab" href="#parts" role="tab" aria-controls="parts" aria-selected="false">Parts</a>
                                    </li>';
                                    }

                                    if(count($systemhistory)) {
                                    echo '<li class="nav-item">
                                        <a class="nav-link" id="system-tab" data-toggle="tab" href="#system" role="tab" aria-controls="system" aria-selected="false">System</a>
                                    </li>';
                                    }
                                        
                                echo '</ul>';
                                      
                                echo '<div class="tab-content" id="myTabContent">';
                                  echo '<div class="tab-pane fade show active mt-3" id="main" role="tabpanel" aria-labelledby="main-tab">
                                            Logged Events
                                        </div>';
                                        if(count($appshistory)) {
                                            echo '<div class="tab-pane fade mt-3" id="applications" role="tabpanel" aria-labelledby="applications-tab">'
                                            . '<table><tr><th>Date/Time</th><th>User</th><th>AppID</th><th>Change Description</th></tr>';
                                                foreach ($appshistory as $record) {
                                                    $nicedescription = $record['description'];
                                                    if (strlen($nicedescription) > $logpreviewlength) {
                                                        $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                                                    }
                                                    echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td><a href="showApp.php?appid='.$record['applicationid'].'">'.$record['applicationid'].'</a></td><td>' . $nicedescription . '</td></tr>';
                                                }
                                                echo '</table>'
                                            . '</div>';
                                        }
                                        
                                        if (count($assetshistory)) 
                                        {
                                            echo '<div class="tab-pane fade mt-3" id="assets" role="tabpanel" aria-labelledby="assets-tab">'
                                            . '<table><tr><th>Date/Time</th><th>User</th><th>AssetID</th><th>Change Description</th></tr>';
                                                foreach ($assetshistory as $record) {
                                                    $nicedescription = $record['description'];
                                                    if (strlen($nicedescription) > $logpreviewlength) {
                                                        $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                                                    }
                                                    echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td><a href="showAsset.php?assetid='.$record['assetid'].'">'.$record['assetid'].'</a></td><td>' . $nicedescription . '</td></tr>';
                                                }
                                            echo '</table>'
                                            . '</div>';
                                        }
                                        
                                        if (count($partshistory)) 
                                        {
                                            echo '<div class="tab-pane fade mt-3" id="parts" role="tabpanel" aria-labelledby="parts-tab">'
                                            . '<table><tr><th>Date/Time</th><th>User</th><th>Partnumber</th><th>Change Description</th></tr>';
                                            foreach ($partshistory as $record) {
                                                $nicedescription = $record['description'];
                                                if (strlen  ($nicedescription) > $logpreviewlength) {
                                                    $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                                                }
                                                echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td><a href="showPart.php?partnumber='.$record['partnumber'].'">'.$record['partnumber'].'</a></td><td>' . $nicedescription . '</td></tr>';
                                            }
                                            echo '</table></div>';
                                        }
                                        
                                        if(count($systemhistory))
                                        {
                                            echo '<div class="tab-pane fade mt-3" id="system" role="tabpanel" aria-labelledby="system-tab">'
                                            . '<table><tr><th>Date/Time</th><th>User</th><th>Eventtype</th><th>Change Description</th></tr>';
                                            foreach ($systemhistory as $record) {
                                                $nicedescription = $record['description'];
                                                if (strlen  ($nicedescription) > $logpreviewlength) {
                                                    $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                                                }
                                                echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td>'.$record['eventtype'].'</td><td>' . $nicedescription . '</td></tr>';
                                            }
                                            echo '</table></div>';
                                        }      
                            echo '</div>';
                            } else {
                                echo '<div></div>';
                            }
                            ?>
                        </div>
                        <!-- End of Main Content -->
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight"></div>
            </div>
        </div>
        
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>
