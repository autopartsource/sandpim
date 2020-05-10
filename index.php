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
        <?php include('/var/www/html/includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>Dashboard</h1>
        
        <div class="wrapper">
            <div class="contentLeft">L</div>

            <!-- Main Content -->
            <div class="contentMain">
                <?php
                if (count($appshistory)) 
                {
                    echo '<div style="padding:10px;">Apps History</div><table><tr><th>Date/Time</th><th>User</th><th>AppID</th><th>Change Description</th></tr>';
                    foreach ($appshistory as $record) {
                        $nicedescription = $record['description'];
                        if (strlen($nicedescription) > $logpreviewlength) {
                            $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                        }
                        echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td><a href="showApp.php?appid='.$record['applicationid'].'">'.$record['applicationid'].'</a></td><td>' . $nicedescription . '</td></tr>';
                    }
                    echo '</table>';
                }

                if (count($assetshistory)) 
                {
                    echo '<div style="padding:10px;">Assets History</div><table><tr><th>Date/Time</th><th>User</th><th>AssetID</th><th>Change Description</th></tr>';
                    foreach ($assetshistory as $record) {
                        $nicedescription = $record['description'];
                        if (strlen($nicedescription) > $logpreviewlength) {
                            $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                        }
                        echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td><a href="showAsset.php?assetid='.$record['assetid'].'">'.$record['assetid'].'</a></td><td>' . $nicedescription . '</td></tr>';
                    }
                    echo '</table>';
                }

                if (count($partshistory)) 
                {
                    echo '<div style="padding:10px;">Parts History</div><table><tr><th>Date/Time</th><th>User</th><th>Partnumber</th><th>Change Description</th></tr>';
                    foreach ($partshistory as $record) {
                        $nicedescription = $record['description'];
                        if (strlen  ($nicedescription) > $logpreviewlength) {
                            $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                        }
                        echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td><a href="showPart.php?partnumber='.$record['partnumber'].'">'.$record['partnumber'].'</a></td><td>' . $nicedescription . '</td></tr>';
                    }
                    echo '</table>';
                }


                if(count($systemhistory))
                {
                    echo '<div style="padding:10px;">System History</div><table><tr><th>Date/Time</th><th>User</th><th>Eventtype</th><th>Change Description</th></tr>';
                    foreach ($systemhistory as $record) {
                        $nicedescription = $record['description'];
                        if (strlen  ($nicedescription) > $logpreviewlength) {
                            $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                        }
                        echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td>'.$record['eventtype'].'</td><td>' . $nicedescription . '</td></tr>';
                    }
                    echo '</table>';
                }
                
                ?>
            </div>

            <div class="contentRight">R</div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>
