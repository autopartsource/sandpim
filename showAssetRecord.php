<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');

$navCategory = 'assets';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$asset = new asset;


if (isset($_POST['submit']) && $_POST['submit'] == 'Save') {
    $pim->updatePartOID($partnumber);
}



$id = intval($_GET['id']);
$assetrecord=$asset->getAssetById($id);



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
        <h1></h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <div>
                    <table>
                        <tr><th>AssetID</th><td><a href="showAsset.php?assetid=<?php echo $assetrecord['assetid'];?>"><?php echo $assetrecord['assetid'];?></a></td></tr>
                        <tr><th>Description</th><td><?php echo $assetrecord['description'];?></td></tr>
                        <tr><th>File Type</th><td><?php echo $assetrecord['fileType'];?></td></tr>
                        <tr><th>Filename</th><td><?php echo $assetrecord['filename'];?></td></tr>
                        <tr><th>Height x Width (<?php echo $assetrecord['dimensionUOM'];?>)</th><td><?php echo $assetrecord['assetHeight'].' x '.$assetrecord['assetWidth'];?></td></tr>
                        <tr><th>Background</th><td><?php echo $assetrecord['background'];?></td></tr>
                        <tr><th>File Size</th><td><?php echo $assetrecord['filesize'];?></td></tr>
                        <tr><th>URI</th><td><?php echo $assetrecord['uri'];?></td></tr>
                        <tr><th>Orientation</th><td><?php echo $assetrecord['orientationViewCode'];?></td></tr>
                        <tr><th>Color Mode</th><td><?php echo $assetrecord['colorModeCode'];?></td></tr>
                        <tr><th>Created Date</th><td><?php echo $assetrecord['createdDate'];?></td></tr>
                        <tr><th>Public</th><td><?php echo $assetrecord['public'];?></td></tr>
                        <tr><th>File Hash</th><td><?php echo $assetrecord['fileHashMD5'];?></td></tr>
                    </table>
                   </div>
                <div>
                    <div><img width="250" src="<?php echo $assetrecord['uri'];?>"/></div>
                </div>
            </div>

            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>