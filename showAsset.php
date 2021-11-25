<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');


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
$logs=new logs;

$allassettypes=$pcdb->getAssetTypeCodes();

if (isset($_POST['submit']) && $_POST['submit'] == 'Connect') {

    $asset->connectPartToAsset($_POST['partnumber'],$_POST['assetid'],$_POST['assettypecode'],0,$_POST['representation']);
    $asset->logAssetEvent($_POST['assetid'], $_SESSION['userid'], $_POST['partnumber'].' connected to asset '.$_POST['assetid'].' as type '.$_POST['assettypecode'] , '');
}

if (isset($_POST['submit']) && $_POST['submit'] == 'Delete') {

    $asset->deleteAssetRecord($_POST['id']);
    $asset->logAssetEvent($_GET['assetid'], $_SESSION['userid'], 'Asset record ('.$_POST['id'].') deleted' , '');
}

$fixattributes=isset($_GET['fixattributes']);


$assetid = $_GET['assetid'];
$assetrecords=$asset->getAssetRecordsByAssetid($assetid);
$connectedparts=$asset->getPartsConnectedToAsset($assetid);
$orientationviewcodes=$pcdb->getAssetOrientationViewCodes();

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
            <script>
            
            function connectPart(assetid)
            {
                var partnumber=document.getElementById('partnumber').value;
                var assettypecodeselectionelement = document.getElementById("assettypecode");
                var selectedassettypecode = assettypecodeselectionelement.options[assettypecodeselectionelement.selectedIndex].value;
                
                var representationselectionelement = document.getElementById("representation");
                var selectedrepresentation = representationselectionelement.options[representationselectionelement.selectedIndex].value;
                
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxConnectPartAsset.php?assetid='+assetid+'&partnumber='+partnumber+'&assettypecode='+selectedassettypecode+'&sequence=1&representation='+selectedrepresentation);
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

            function updateAsset(assetrecordid,elementtype,elementid)
            {
             var value='';
             if(elementtype=='text'){value=document.getElementById(elementid).value;}
             if(elementtype=='select')
             {
              var e=document.getElementById(elementid);
              value=e.options[e.selectedIndex].value;
             }
             document.getElementById("sandpiperoid").innerHTML='';

             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdateAsset.php?assetrecordid='+assetrecordid+'&elementid='+elementid+'&value='+encodeURIComponent(value));
             xhr.onload = function()
             {
              var response=xhr.responseText;
              document.getElementById("sandpiperoid").innerHTML=response;
             };
             xhr.send();
            }
            
            function flagUnsavedLabel(){document.getElementById("btnUpdateLabel").className="btn btn-sm btn-danger";}
            function unflagUnsavedLabel(){document.getElementById("btnUpdateLabel").className="btn btn-sm btn-outline-secondary";}
            
            
            </script>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                    <?php $issues=$pim->getIssues('ASSET/%', $assetid, 0, array(1,2), 10);
                    if(count($issues)>0){?>
                    <div class="card shadow-sm">
                        <h5 class="card-header">
                            Issues
                        </h5>
                        <div class="card-body">
                            <?php
                            foreach($issues as $issue)
                            {
                                echo '<div><a href="showIssue.php?id='.$issue['id'].'">'.$issue['description'].'</a></div>';
                            }?>
                        </div>
                    </div>
                    <?php }?>
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-7 my-col colMain">
                    <?php foreach ($assetrecords as $assetrecord)
                    {
                        $urifilehash=''; $urifilesize=0; $badattributes=false;
                         if(strlen($assetrecord['uri'])>0)
                         {
                            $imgsrc=$assetrecord['uri'];
                            if(strlen(trim($assetrecord['uri']))>0)
                            {
                                $urifileattributes=$asset->attributesOfAssetAtURI($assetrecord['uri']);
                                if($urifileattributes)
                                {
                                    $urifilehash=$urifileattributes['fileHashMD5']; $urifilesize=$urifileattributes['filesize'];
                                    if($fixattributes)
                                    {
                                        $asset->setAssetHash($assetrecord['id'], $urifilehash);
                                        $asset->setAssetFilesize($assetrecord['id'], $urifilesize);
                                        $newoid=$asset->updateAssetOID($assetrecord['id']);
                                        $asset->logAssetEvent($assetrecord['id'], $_SESSION['userid'], 'hash and/or size updated based on downloaded uri', $newoid);
                                        
                                    }
                                }
                            }
                         }
                         ?>
                        <div class="card shadow-sm">
                            <h3 class="card-header text-start">
                                Asset <span class="text-info"><?php echo $assetid;?></span>
                                <div style="float:right;">
                                    <form method="post" action="showAsset.php?assetid=<?php echo $assetid; ?>">
                                        <input type="submit" name="submit" value="Delete"/>
                                        <input type="hidden" name="id" value="<?php echo $assetrecord['id']; ?>"/>
                                        <input type="hidden" name="assetid" value="<?php echo $assetid; ?>"/>
                                    </form>
                                </div>
                                <div style="float:right;">
                                    <a class="btn btn-secondary" href="./assetHistory.php?assetid=<?php echo urlencode($assetid); ?>">History</a>
                                </div>
                                <div style="clear:both;"></div>
                            </h3>
                            
                            <div class="card-body">
                                <div class="row g-0">
                                    <div class="col-xs-12 col-md-7">
                                        <table class="table">
                                            <tr><th>Description</th>
                                                <td><?php echo $assetrecord['description']; ?></td>
                                                <td class="mobile" rowspan="13"></td></tr>
                                            <tr><th>File Type</th><td><?php echo $assetrecord['fileType']; ?></td></tr>
                                            <tr><th>Filename</th><td><?php echo $assetrecord['filename']; ?></td></tr>
                                            <tr><th>Width x Height</th><td><?php echo $assetrecord['assetWidth'] . ' x ' . $assetrecord['assetHeight'] . ' (' . $assetrecord['dimensionUOM'] . ')'; ?></td></tr>
                                            <tr><th>Background</th><td><?php echo $assetrecord['background']; ?></td></tr>
                                            <tr><th>URI</th><td><a href="<?php echo $assetrecord['uri']; ?>">Link</a></td></tr>
                                            <tr><th>File Size / Hash</th>
                                                <td>
                                                    <div style="<?php if($urifilesize!=$assetrecord['filesize']){$badattributes=true; echo 'background-color:#ffff00;';}?>"><?php echo $asset->niceFileSize($assetrecord['filesize']); ?></div>
                                                    <div style="font-size:50%;<?php if($urifilehash!=$assetrecord['fileHashMD5']){$badattributes=true; echo 'background-color:#ffff00;';}?>"><?php echo $assetrecord['fileHashMD5']; ?></div>
                                                    <?php if($badattributes){echo '<div><a href="./showAsset.php?assetid='.urldecode($assetid).'&fixattributes">Fix</a></div>';}?>
                                                </td>
                                            </tr>
                                            <tr><th>Local Path</th><td><?php echo $assetrecord['localpath']; ?></td></tr>
                                            <tr><th>Orientation</th><td><select id="orientationviewcode"  onchange="updateAsset(<?php echo $assetrecord['id']; ?>,'select','orientationviewcode');"><?php foreach ($orientationviewcodes as $orientationviewcode) { ?> <option value="<?php echo $orientationviewcode['code']; ?>"<?php if($orientationviewcode['code']==$assetrecord['orientationViewCode']){echo ' selected';}?>><?php echo $orientationviewcode['description']; ?></option><?php } ?></select></td></tr>                                           
                                            <tr><th>Color Mode</th><td><?php echo $assetrecord['colorModeCode']; ?></td></tr>
                                            <tr><th>Created Date</th><td><?php echo $assetrecord['createdDate']; ?></td></tr>
                                            <tr><th>Public/Private</th><td><select id="public" onchange="updateAsset(<?php echo $assetrecord['id']; ?>,'select','public');"><option value="0"<?php if($assetrecord['public']==0){echo ' selected';} ?>>Private</option>            <option value="1"<?php if($assetrecord['public']==1){echo ' selected';} ?>>Public</option></select></td></tr>
                                            <tr><th>Internal Label</th><td><div style="float:left;"><input type="text" id="assetlabel" oninput="flagUnsavedLabel();" value="<?php echo $assetrecord['assetlabel'];?>"/></div><div style="float:left;"><button id="btnUpdateLabel" class="btn btn-sm btn-outline-secondary" onclick="updateAsset('<?php echo $assetrecord['id'];?>','text','assetlabel'); unflagUnsavedLabel();">Update</button></div><div style="clear:both;"></div></td></tr>
                                            <tr><th>Sandpiper OID</th><td><div id="sandpiperoid"><?php echo $assetrecord['oid']; ?></div></td><tr>
                                        </table>
                                    </div>
                                    <?php if($assetrecord['fileType']=='JPG' || $assetrecord['fileType']=='PNG'){?>
                                    <div class="col-xs-12 col-md-5">
                                        <a target="_blank" href="<?php echo $assetrecord['uri']; ?>"><img class="img-thumbnail" src="<?php echo $imgsrc; ?>"/></a>
                                    </div>
                                    <?php }?>
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
                                    <a class="nav-link active" id="connected-tab" data-bs-toggle="tab" href="#connected" role="tab" aria-controls="connected" aria-selected="true">Connected</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="newconnection-tab" data-bs-toggle="tab" href="#newconnection" role="tab" aria-controls="newconnection" aria-selected="false">New Connection</a>
                                </li>
                            </ul>
                        </h5>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active text-start m-3" id="connected" role="tabpanel" aria-labelledby="connected-tab">
                                <?php foreach($connectedparts as $connectedpart){?>
                                <div id="assetconnectionid_<?php echo $connectedpart['id'];?>" style="padding: 2px;"> 
                                   <a class="btn btn-secondary" href="showPart.php?partnumber=<?php echo $connectedpart['partnumber'];?>"><?php echo $connectedpart['partnumber'];?></a> <button type="button" class="btn btn-light" onclick="disconnectPart('<?php echo $connectedpart['partnumber'];?>','<?php echo $connectedpart['id'];?>')"><i class="bi bi-x"></i></button>
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