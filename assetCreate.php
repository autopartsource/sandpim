<?php
include_once('/var/www/html/class/pimClass.php');
include_once('/var/www/html/class/assetClass.php');
$navCategory = 'assets';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset = new asset;
$pim = new pim;
$error_msg = false;


if (isset($_POST['submit']) && $_POST['submit'] == 'Upload' && isset($_POST['assetrecordid'])) {
    $target_dir = '/var/www/html/ACESuploads/';
    $target_file = $target_dir . basename($_FILES['fileToUpload']['name']);

    if (!file_exists($target_file)) {
        if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_file)) {
            $pathparts = pathinfo($_FILES['fileToUpload']['name']);

            if ($exiftype = exif_imagetype($target_file)) {
                $filesize = getimagesize($target_file);

                $orientationViewCode = 'front';
                $colorModeCode = 'RBG';
                $assetHeight = $filesize[0];
                $assetWidth = $filesize[1];
                $dimensionUOM = 'PX';
                $background = 'WHI';
                $fileType = $exiftype;
                $public = 1;
                $approved = 1;
                $description = 'some descriptive text';
                $oid = $pim->newoid();
                $fileHashMD5 = md5_file($target_file);

                if ($id = $asset->addAsset($pathparts['filename'], $pathparts['basename'], 'http://', $orientationViewCode, $colorModeCode, $assetHeight, $assetWidth, $dimensionUOM, $background, $fileType, $public, $approved, $description, $oid, $fileHashMD5)) {
                    $error_msg = 'Asset id ' . $id . ' was created.';
                } else { // asset not created
                    $error_msg = 'Error creating asset';
                }
            } else { // not a supported image type
                $error_msg = 'Not a supported image type';
            }
        } else {
            $error_msg = 'Error uploading file';
        }
    } else {
        $error_msg = 'File already exists';
    }
}

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
                    <form method="post" enctype="multipart/form-data">
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