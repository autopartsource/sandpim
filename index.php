<?php
include_once('/var/www/html/class/pimClass.php');
include_once('/var/www/html/class/userClass.php');
include_once('/var/www/html/class/configGetClass.php');
include_once('/var/www/html/class/assetClass.php');

$navCategory = 'dashboard';

$user = new user;
$asset=new asset;

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$configGet = new configGet;
$appshistory = $pim->getAppsEvents(20);
$assetshistory = $asset->getAppsEvents(20);
$partshistory = $pim->getPartsEvents(20);

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


                ?>
            </div>

            <div class="contentRight">R</div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>
