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


$assetid = $_GET['assetid'];
$assetrecords=$asset->getAssetRecordsByAssetid($assetid);

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
            <div class="contentMain button">
                Asset ID <?php echo $assetid;?>
                <div>
                    <?php
                    foreach($assetrecords as $assetrecord)
                    {
                        echo '<div style="border:1px solid;margin:5px;padding:5px;"><div style="float:left;padding:10px;">'.$assetrecord['description'].'<hr/>'.$assetrecord['fileType'].'<hr/>'.$assetrecord['assetHeight'].'x'.$assetrecord['assetWidth'].'</div><div style="float:left;"><a href="showAssetRecord.php?id='.$assetrecord['id'].'"><img width="100" src="'.$assetrecord['uri'].'"/></a></div><div style="clear:both;"></div></div>';
                    }?>
                </div>
            </div>

            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>