<?php
include_once('/var/www/html/class/assetClass.php');
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
        <link rel="stylesheet" type="text/css" href="styles.css" />
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>

        <!-- Header -->
        <h1>Upload Asset File</h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <div>
                <?php if ($error_msg) {
                    echo $error_msg;
                } ?>
                    <form method="post" enctype="multipart/form-data" action="assetCreate.php">
                        <div style="padding:10px;"><input type="file" name="fileToUpload" id="fileToUpload"></div>
                        <div style="padding:10px;"><input name="submit" type="submit" value="Upload"/></div>
                    </form>
                </div>
                <div>
                    <?php
                    if (count($assets)) {
                        echo '<table><tr><th>AssetID</th><th>Filename</th><th>Description</th><th>File Attributes</th></tr>';
                        foreach ($assets as $record) {
                            echo '<tr><td>' . $record['assetid'] . '</td><td>' . $record['filename'] . '</td><td>' . $record['description'] . '</td><td>' . $record['fileType'] . ', ' . $record['assetHeight'] . ' x ' . $record['assetWidth'] . '</td></tr>';
                        }
                        echo '</table>';
                    }
                    ?>
                </div>                </div>

            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>