<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/configGetClass.php');


$navCategory = 'assets';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$asset = new asset;
$pcdb = new pcdb;
$configGet= new configGet;

$allassettypes=$pcdb->getAssetTypeCodes();

if (isset($_POST['submit']) && $_POST['submit'] == 'Connect') {

    $asset->connectPartToAsset($_POST['partnumber'],$_POST['assetid'],$_POST['assettypecode'],0,$_POST['representation']);
    $asset->logAssetEvent($_POST['assetid'], $_SESSION['userid'], $_POST['partnumber'].' connected to asset '.$_POST['assetid'].' as type '.$_POST['assettypecode'] , '');
}

if (isset($_POST['submit']) && $_POST['submit'] == 'Delete') {

    $asset->deleteAssetRecord($_POST['id']);
    $asset->logAssetEvent($_GET['assetid'], $_SESSION['userid'], 'Asset record ('.$_POST['id'].') deleted' , '');
}



$assetid = $_GET['assetid'];
$assetrecords=$asset->getAssetRecordsByAssetid($assetid);
$connectedparts=$asset->getPartsConnectedToAsset($assetid);

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
            <script>
            
            function connectPart(assetid)
            {
                var partnumber=document.getElementById('partnumber').value;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxConnectPartAsset.php?assetid='+assetid+'&partnumber='+partnumber+'&assettypecode=P04&sequence=1&representation=A');
                xhr.onload = function()
                {
                 var response=JSON.parse(xhr.responseText);
                 if(response.success)
                 {
                  var e = document.getElementById('connected');
                  var d = document.createElement('div');
                  d.style='padding: 2px;';
                  d.id = 'assetconnectionid_'+response.connectionid;
                  d.innerHTML='<a class="btn btn-secondary" href="showPart.php?partnumber='+partnumber+'">'+partnumber+'</a> <button onclick="disconnectPart(\''+partnumber+'\',\''+response.connectionid+'\')">x</button>';
                  e.appendChild(d);
                 }
                };
                xhr.send();
            }

            function disconnectPart(partnumber,connectionid)
            {
                var assetdiv = document.getElementById('assetconnectionid_'+connectionid);
                assetdiv.parentNode.removeChild(assetdiv);

                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxDisconnectPartAsset.php?connectionid='+connectionid+'&partnumber='+partnumber);
                xhr.onload = function()
                {
                 var response=JSON.parse(xhr.responseText);
                };
                xhr.send();
            }


            
            
            </script>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1></h1>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-7 my-col colMain">
                    <?php foreach ($assetrecords as $assetrecord){
                         if(strlen($assetrecord['uri'])>0){$imgsrc=$assetrecord['uri'];}
                         if(strlen($assetrecord['localpath'])>0){$imgsrc=$configGet->getConfigValue('localImageStorePath').'/'.$assetrecord['localpath'];}
                         ?>
                        <div class="card shadow-sm">
                            <h3 class="card-header text-left">Record for Asset: <span class="text-info"><?php echo $assetid;?></span><div style="float:right;"><form method="post" action="showAsset.php?assetid=<?php echo $assetid; ?>"><input type="submit" name="submit" value="Delete"/><input type="hidden" name="id" value="<?php echo $assetrecord['id']; ?>"/><input type="hidden" name="assetid" value="<?php echo $assetid; ?>"/></form></div></h3>
                            <div class="row no-gutters">
                                <div class="card-body">
                                    <table>
                                        <tr><th>Description</th>
                                            <td><?php echo $assetrecord['description']; ?></td>
                                            <td class="mobile" rowspan="13"><img width="<?php echo $configGet->getConfigValue('imageAssetTumbnailRenderWidth', 350); ?>" src="<?php echo $imgsrc; ?>"/></td></tr>
                                        <tr><th>File Type</th><td><?php echo $assetrecord['fileType']; ?></td></tr>
                                        <tr><th>Filename</th><td><?php echo $assetrecord['filename']; ?></td></tr>
                                        <tr><th>Width x Height</th><td><?php echo $assetrecord['assetWidth'] . ' x ' . $assetrecord['assetHeight'] . ' (' . $assetrecord['dimensionUOM'] . ')'; ?></td></tr>
                                        <tr><th>Background</th><td><?php echo $assetrecord['background']; ?></td></tr>
                                        <tr><th>File Size</th><td><?php echo $asset->niceFileSize($assetrecord['filesize']); ?></td></tr>
                                        <tr><th>URI</th><td><?php echo $assetrecord['uri']; ?></td></tr>
                                        <tr><th>Local Path</th><td><?php echo $assetrecord['localpath']; ?></td></tr>
                                        <tr><th>Orientation</th><td><?php echo $assetrecord['orientationViewCode']; ?></td></tr>
                                        <tr><th>Color Mode</th><td><?php echo $assetrecord['colorModeCode']; ?></td></tr>
                                        <tr><th>Created Date</th><td><?php echo $assetrecord['createdDate']; ?></td></tr>
                                        <tr><th>Public</th><td><?php echo $asset->niceBoolText($assetrecord['public'], 'Public', 'Private'); ?></td></tr>
                                        <tr><th>File Hash</th><td><?php echo $assetrecord['fileHashMD5']; ?></td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php }?>
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-3 my-col colRight">
                    <div class="card shadow-sm">
                        <h5 class="card-header">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="connected-tab" data-toggle="tab" href="#connected" role="tab" aria-controls="connected" aria-selected="true">Connected</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="newconnection-tab" data-toggle="tab" href="#newconnection" role="tab" aria-controls="newconnection" aria-selected="false">New Connection</a>
                                </li>
                            </ul>
                        </h5>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active text-left m-3" id="connected" role="tabpanel" aria-labelledby="connected-tab">
                                <?php foreach($connectedparts as $connectedpart){?>
                                <div id="assetconnectionid_<?php echo $connectedpart['id'];?>" style="padding: 2px;"> 
                                   <a class="btn btn-secondary" href="showPart.php?partnumber=<?php echo $connectedpart['partnumber'];?>"><?php echo $connectedpart['partnumber'];?></a> <button onclick="disconnectPart('<?php echo $connectedpart['partnumber'];?>','<?php echo $connectedpart['id'];?>')">x</button>
                                </div>
                                <?php }?>
                            </div>
                            <div class="tab-pane fade m-3" id="newconnection" role="tabpanel" aria-labelledby="newconnection-tab">
                                <input type="text" id="partnumber" size="8"/> 
                                <select id="assettypecode"><?php foreach ($allassettypes as $assettype){ ?><option value="<?php echo $assettype['code']; ?>"<?php if($assettype['code']=='P04'){echo ' selected';} ?>><?php echo $assettype['description']; if($assettype['description']=='User Defined'){echo ' ('.$assettype['code'].')';} ?></option><?php }?></select>
                                <select id="representation"><option value="A">Actual Depicted</option><option value="R">Similar Depicted</option></select>
                                <button onclick="connectPart('<?php echo $assetid;?>')">Connect</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>