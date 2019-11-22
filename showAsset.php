<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/configGetClass.php');


$navCategory = 'assets';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$asset = new asset;
$configGet= new configGet;


if (isset($_POST['submit']) && $_POST['submit'] == 'Connect') {

    $asset->connectPartToAsset($_POST['partnumber'],$_POST['assetid'],$_POST['assettypecode'],0);
    $asset->logHistoryEvent($_POST['assetid'], $_SESSION['userid'], $_POST['partnumber'].' connected to asset '.$_POST['assetid'].' as type '.$_POST['assettypecode'] , '');
//    $pim->updatePartOID($partnumber);
}

if (isset($_POST['submit']) && $_POST['submit'] == 'Delete') {

    $asset->deleteAssetRecord($_POST['id']);
    $asset->logHistoryEvent($_GET['assetid'], $_SESSION['userid'], 'Asset record ('.$_POST['id'].') deleted' , '');
//    $pim->updatePartOID($partnumber);
}



$assetid = $_GET['assetid'];
$assetrecords=$asset->getAssetRecordsByAssetid($assetid);
$connectedparts=$asset->getPartsConnectedToAsset($assetid);

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

               <div style="text-align: left;padding-bottom:20px;">Records for Asset: <?php echo $assetid;?></div>

                <?php foreach ($assetrecords as $assetrecord){
                    if(strlen($assetrecord['uri'])>0){$imgsrc=$assetrecord['uri'];}
                    if(strlen($assetrecord['localpath'])>0){$imgsrc=$configGet->getConfigValue('localImageStorePath').'/'.$assetrecord['localpath'];}
                    ?>

                <div style="padding-bottom:30px;">
                    <table>
                        <tr><th>Description</th>
                            <td><?php echo $assetrecord['description'];?></td>
                            <td class="mobile" rowspan="14"><img width="300" src="<?php echo $imgsrc;?>"/></td></tr>
                        <tr><th>File Type</th><td><?php echo $asset->niceExifTypeName($assetrecord['fileType']);?></td></tr>
                        <tr><th>Filename</th><td><?php echo $assetrecord['filename'];?></td></tr>
                        <tr><th>Width x Height</th><td><?php echo $assetrecord['assetWidth'].' x '.$assetrecord['assetHeight'].' ('.$assetrecord['dimensionUOM'].')';?></td></tr>
                        <tr><th>Background</th><td><?php echo $assetrecord['background'];?></td></tr>
                        <tr><th>File Size</th><td><?php echo $asset->niceFileSize($assetrecord['filesize']);?></td></tr>
                        <tr><th>URI</th><td><?php echo $assetrecord['uri'];?></td></tr>
                        <tr><th>Local Path</th><td><?php echo $assetrecord['localpath'];?></td></tr>
                        <tr><th>Orientation</th><td><?php echo $assetrecord['orientationViewCode'];?></td></tr>
                        <tr><th>Color Mode</th><td><?php echo $assetrecord['colorModeCode'];?></td></tr>
                        <tr><th>Created Date</th><td><?php echo $assetrecord['createdDate'];?></td></tr>
                        <tr><th>Public</th><td><?php echo $asset->niceBoolText($assetrecord['public'],'Public','Private');?></td></tr>
                        <tr><th>File Hash</th><td><?php echo $assetrecord['fileHashMD5'];?></td></tr>
                        <tr><td></td><td><form method="post" action="showAsset.php?assetid=<?php echo $assetid;?>"><input type="submit" name="submit" value="Delete"/><input type="hidden" name="id" value="<?php echo $assetrecord['id'];?>"/><input type="hidden" name="assetid" value="<?php echo $assetid;?>"/></form></td><td></td></tr>
                    </table>
                </div>
                <?php }?>

            </div>

            <div class="contentRight">
                Connected Parts
                <table>
                    <?php foreach($connectedparts as $connectedpart){?>
                    <tr><td><a href="showPart.php?partnumber=<?php echo $connectedpart['partnumber'];?>"><?php echo $connectedpart['partnumber'];?></a></td><td><?php echo $connectedpart['assettypecode'];?></td></tr>
                    <?php }?>
                </table>
                <form method="post" action="showAsset.php?assetid=<?php echo $assetid;?>">
                    <div>
                        <input type="hidden" name="assetid" value="<?php echo $assetid;?>"/>
                        <input type="text" name="partnumber" size="8"/> 
                        <select name="assettypecode"><option value="P04">Primary</option></select>
                        <input type="submit" name="submit" value="Connect"/>
                    </div>
                </form>
            </div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>