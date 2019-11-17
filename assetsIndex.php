<?php
include_once('./class/assetClass.php');
$navCategory = 'assets';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset = new asset;
$assets = $asset->getRecentAssets(20);
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
        <h1>Assets</h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <div style="padding:5px;border: 1px solid;margin:3px;">
                    Create image asset from upload
                    <form method="post" enctype="multipart/form-data" action="assetCreate.php">
                        <div style="padding:10px;"><input type="file" name="fileToUpload" id="fileToUpload"></div>
                        <div style="padding:10px;"><input name="submit" type="submit" value="Upload"/></div>
                    </form>
                </div>
                <div style="padding:5px;border: 1px solid;margin:3px;">
                    <form action="assetExistingResourceForm.php" method="post">
                        <div>Create image asset from uri <input type="text" name="uri"/></div>
                        <div>Save as filename <input type="text" name="filename"/></div>
                        <div><input type="submit" name="submit" value="Retrieve"/></div>
                    </form>
                </div>
            </div>

            <div class="contentRight">
                <?php
                if (count($assets)) {
                    echo '<div style="padding:5px;border: 1px solid;margin:3px;">Recent Assets ';
                    echo '<table><tr><th>AssetID</th><th>Description</th></tr>';
                    foreach ($assets as $record) {
                        echo '<tr><td><a href="showAssetRecord.php?id='.$record['id'].'">' . $record['assetid'] . '</a></td><td>' . $record['description'] . '</td></tr>';
                    }
                    echo '</table></div>';
                }
                ?>
            </div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>
