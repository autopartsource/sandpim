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
                    <div style="text-align: left;padding-bottom:20px;">Records for Asset: <?php echo $assetrecord['assetid'];?></div>
                    <table>
                        <tr><th>Description</th><td><?php echo $assetrecord['description'];?></td></tr>
                        <tr><th>File Type</th><td><?php echo $asset->niceExifTypeName($assetrecord['fileType']);?></td></tr>
                        <tr><th>Filename</th><td><?php echo $assetrecord['filename'];?></td></tr>
                        <tr><th>Height x Width (<?php echo $assetrecord['dimensionUOM'];?>)</th><td><?php echo $assetrecord['assetHeight'].' x '.$assetrecord['assetWidth'];?></td></tr>
                        <tr><th>Background</th><td><?php echo $assetrecord['background'];?></td></tr>
                        <tr><th>File Size</th><td><?php echo $assetrecord['filesize'];?></td></tr>
                        <tr><th>URI</th><td><?php echo $assetrecord['uri'];?></td></tr>
                        <tr><th>Local Path</th><td><?php echo $assetrecord['localpath'];?></td></tr>
                        <tr><th>Orientation</th><td><?php echo $assetrecord['orientationViewCode'];?></td></tr>
                        <tr><th>Color Mode</th><td><?php echo $assetrecord['colorModeCode'];?></td></tr>
                        <tr><th>Created Date</th><td><?php echo $assetrecord['createdDate'];?></td></tr>
                        <tr><th>Public</th><td><?php echo $asset->niceBoolText($assetrecord['public'],'Public','Private');?></td></tr>
                        <tr><th>File Hash</th><td><?php echo $assetrecord['fileHashMD5'];?></td></tr>
                    </table>
                   </div>
                <div>
                    <div style="padding:10px;"><img width="300" src="<?php echo $assetrecord['uri'];?>"/></div>
                </div>
            </div>

            <div class="contentRight">
                Connected Parts
                <table>
                    <tr><td>PartX</td><td>Primary</td></tr>
                    <tr><td>PartY</td><td>Primary</td></tr>
                    <tr><td>PartZ</td><td>Primary</td></tr>
                </table>
                
                
            </div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>