<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');

$navCategory = 'assets';

$pim = new pim;
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// ip-based ACL enforcement - bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'showAsset.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset = new asset;
$interchange=new interchange;
$pcdb = new pcdb;
$configGet= new configGet;
$logs=new logs;

$allassettypes=$pcdb->getAssetTypeCodes();
$orientationviewcodes=$pcdb->getAssetOrientationViewCodes();
$labels=$asset->getAssetlabels();


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


if($asset->validAsset($assetid))
{
 $assetrecords=$asset->getAssetRecordsByAssetid($assetid);
 $connectedparts=$asset->getPartsConnectedToAsset($assetid);
 $connectedbrands=$asset->getBrandsConnectedToAsset($assetid);
 $assettags=$asset->getAssettagsForAsset($assetid);
}
else
{// passed-in asset is not valid - blank it out of caution 
 $assetid='';
}

$brands=$interchange->getCompetitivebrands();


?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
            <script>
            
            function connectPart(assetid)
            {
                var partnumber=document.getElementById('partnumber').value;
                var assettypecodeselectionelement = document.getElementById("partassettypecode");
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
                  var e = document.getElementById('connectedparts');
                  var d = document.createElement('div');
                  d.style='padding: 2px;';
                  d.id = 'partassetconnectionid_'+response.connectionid;
                  d.innerHTML='<a class="btn btn-secondary" href="showPart.php?partnumber='+partnumber+'">'+partnumber+'</a> <button onclick="disconnectPart(\''+partnumber+'\',\''+response.connectionid+'\')">x</button>';
                  e.appendChild(d);
                 }
                };
                xhr.send();
            }

            function disconnectPart(partnumber,connectionid)
            {
             var assetdiv = document.getElementById('partassetconnectionid_'+connectionid);
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
             document.getElementById("changeddate").innerHTML='';

             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdateAsset.php?assetrecordid='+assetrecordid+'&elementid='+elementid+'&value='+encodeURIComponent(value));
             xhr.onload = function()
             {
              var response=xhr.responseText;
              document.getElementById("sandpiperoid").innerHTML=response;
              document.getElementById("changeddate").innerHTML = new Date().toISOString().slice(0, 10);
             };
             xhr.send();
            }
            
            function flagUnsavedLabel(){document.getElementById("btnUpdateLabel").className="btn btn-sm btn-danger";}
            function unflagUnsavedLabel(){document.getElementById("btnUpdateLabel").className="btn btn-sm btn-outline-secondary";}
            


            function connectBrand(assetid)
            {
             var assettypecodeselectionelement = document.getElementById("brandassettypecode");
             var selectedassettypecode = assettypecodeselectionelement.options[assettypecodeselectionelement.selectedIndex].value;

             var brandselectionelement = document.getElementById("brandid");
             var brandid = brandselectionelement.options[brandselectionelement.selectedIndex].value;

             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxConnectBrandAsset.php?assetid='+assetid+'&brandid='+brandid+'&assettypecode='+selectedassettypecode+'&sequence=1');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              if(response.success)
              {
               var e = document.getElementById('connectedbrands');
               var d = document.createElement('div');
               d.style='padding: 2px;';
               d.id = 'brandassetconnectionid_'+response.connectionid;
               d.innerHTML='<a class="btn btn-secondary" href="showBrand.php?brandid='+brandid+'">'+brandid+'</a> <button onclick="disconnectBrand(\''+brandid+'\',\''+response.connectionid+'\')">x</button>';
               e.appendChild(d);
              }
             };
             xhr.send();
            }

            function disconnectBrand(brandid,connectionid)
            {
             var assetdiv = document.getElementById('brandassetconnectionid_'+connectionid);
             assetdiv.parentNode.removeChild(assetdiv);

             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxDisconnectBrandAsset.php?connectionid='+connectionid+'&brandid='+brandid);
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
             };
             xhr.send();
            }


            function openLinkEdit()
            {
             document.getElementById("uriinput").style.display='block';
             document.getElementById("urilink").style.display='none';
             document.getElementById("btnEditLink").style.display='none';
             document.getElementById("btnSaveLinkEdit").style.display='block';
             document.getElementById("btnCancelLinkEdit").style.display='block';
            }

            function cancelLinkEdit()
            {
             document.getElementById("uriinput").style.display='none';
             document.getElementById("uriinput").value=document.getElementById("uriinputtemp").value;
             document.getElementById("urilink").style.display='block';
             document.getElementById("urilink").href=document.getElementById("uriinputtemp").value;
             document.getElementById("btnEditLink").style.display='block';
             document.getElementById("btnSaveLinkEdit").style.display='none';
             document.getElementById("btnCancelLinkEdit").style.display='none';
            }
            
            function saveLinkEdit(assetrecordid)
            {
             var uri=document.getElementById("uriinput").value;
             document.getElementById("uriinput").style.display='none';
             document.getElementById("uriinputtemp").href=uri;
             document.getElementById("urilink").style.display='block';
             document.getElementById("urilink").href=document.getElementById("uriinput").value;
             document.getElementById("btnEditLink").style.display='block';
             document.getElementById("btnSaveLinkEdit").style.display='none';
             document.getElementById("btnCancelLinkEdit").style.display='none';                

             document.getElementById("sandpiperoid").innerHTML='';
             document.getElementById("changeddate").innerHTML='';

             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdateAsset.php?assetrecordid='+assetrecordid+'&elementid=uri&value='+btoa(uri));
             xhr.onload = function()
             {
              var response=xhr.responseText;
              document.getElementById("sandpiperoid").innerHTML=response;
              document.getElementById("changeddate").innerHTML = new Date().toISOString().slice(0, 10);
             };
             xhr.send();

    
    
            }


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

                    <h3 class="card-header text-start">Asset <span class="text-info"><?php echo $assetid;?></span>
                        <div style="float:right;">
                            <a class="btn btn-secondary" href="./assetHistory.php?assetid=<?php echo urlencode($assetid); ?>">History</a>
                        </div>
                        <div style="clear:both;"></div>
                    </h3>
                    
                    
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
                            
                            <div class="card-body">
                                <div class="row g-0">
                                    <div class="col-xs-12 col-md-7">
                                        <table class="table">
                                            <tr><th>Description</th>
                                                <td><?php echo $assetrecord['description']; ?></td>
                                                <td class="mobile" rowspan="16"></td></tr>
                                            <tr><th>File Type</th><td><?php echo $assetrecord['fileType']; ?></td></tr>
                                            <tr><th>Filename</th><td><?php echo $assetrecord['filename']; ?></td></tr>
                                            <tr><th>Width x Height</th><td><?php echo $assetrecord['assetWidth'] . ' x ' . $assetrecord['assetHeight'] . ' (' . $assetrecord['dimensionUOM'] . ')'; ?></td></tr>
                                            <tr><th>Background</th><td><?php echo $assetrecord['background']; ?></td></tr>

                                            <tr><th>URI</th>
                                                <td>
                                                    <div style="float:left;width:60%;">
                                                        <input type="text" style="display:none;width:100%" id="uriinput" value="<?php echo $assetrecord['uri']; ?>"/>
                                                        <input type="text" style="display:none;" id="uriinputtemp" value="<?php echo $assetrecord['uri']; ?>"/>
                                                        <a id="urilink" style="display:block;" href="<?php echo $assetrecord['uri']; ?>">Link</a>                                                   
                                                    </div>                                                    
                                                    <div style="float:right;width:40%;">
                                                        <div style="float:left;padding-left:2px;"><button id="btnEditLink" style="display: block;" class="btn btn-sm btn-outline-secondary" onclick="openLinkEdit();">Edit</button></div>
                                                        <div style="float:left;padding-left:2px;"><button id="btnSaveLinkEdit" style="display: none;" class="btn btn-sm btn-outline-secondary" onclick="saveLinkEdit(<?php echo $assetrecord['id']; ?>);">Save</button></div>
                                                        <div style="float:left;padding-left:2px;"><button id="btnCancelLinkEdit" style="display: none;" class="btn btn-sm btn-outline-secondary" onclick="cancelLinkEdit();">Cancel</button></div>
                                                        <div style="clear:both;"></div>
                                                    </div>
                                                    <div style="clear:both;"></div>
                                                </td>
                                            </tr>



                                            <tr><th>File Size / Hash</th>
                                                <td>
                                                    <div style="<?php if($urifilesize!=$assetrecord['filesize']){$badattributes=true; echo 'background-color:#ffff00;';}?>"><?php echo $asset->niceFileSize($assetrecord['filesize']); ?></div>
                                                    <div style="font-size:50%;<?php if($urifilehash!=$assetrecord['fileHashMD5']){$badattributes=true; echo 'background-color:#ffff00;';}?>"><?php echo $assetrecord['fileHashMD5']; ?></div>
                                                    <?php if($badattributes){echo '<div><a href="./showAsset.php?assetid='.urlencode($assetid).'&fixattributes">Fix</a></div>';}?>
                                                </td>
                                            </tr>
                                            <tr><th>Local Path</th><td><?php echo $assetrecord['localpath']; ?></td></tr>
                                            <tr><th>Orientation</th><td><select id="orientationviewcode"  onchange="updateAsset(<?php echo $assetrecord['id']; ?>,'select','orientationviewcode');"><?php foreach ($orientationviewcodes as $orientationviewcode) { ?> <option value="<?php echo $orientationviewcode['code']; ?>"<?php if($orientationviewcode['code']==$assetrecord['orientationViewCode']){echo ' selected';}?>><?php echo $orientationviewcode['description']; ?></option><?php } ?></select></td></tr>                                           
                                            <tr><th>Color Mode</th><td><?php echo $assetrecord['colorModeCode']; ?></td></tr>
                                            <tr><th>Created Date</th><td><?php echo $assetrecord['createdDate']; ?></td></tr>
                                            
                                            
                                            <tr><th>Public/Private</th><td><select id="public" onchange="updateAsset(<?php echo $assetrecord['id']; ?>,'select','public');"><option value="0"<?php if($assetrecord['public']==0){echo ' selected';} ?>>Private</option>            <option value="1"<?php if($assetrecord['public']==1){echo ' selected';} ?>>Public</option></select></td></tr>
                                            <tr>
                                                <th>Tags</th>
                                                <td>
                                                    <?php foreach($assettags as $assettag)
                                                    {
                                                        echo '<div style="text-align:left;padding-bottom:5px;" id="assettagid_'.$assettag['id'].'"><button class="btn btn-sm btn-outline-danger" title="Remove this assettag from this asset" onclick="removeAssettag('.$assettag['id'].')">x</button> '.$assettag['tagtext'].'</div>';
                                                    }?>                                                    
                                                </td>
                                            </tr>
                                                                                        
                                            
                                            <tr>
                                                <th>Internal Label</th>
                                                <td>
                                                    <div style="float:left;">
                                                        <select id="assetlabel"  onchange="updateAsset(<?php echo $assetrecord['id']; ?>,'select','assetlabel');"><option value="">- blank -</option><?php foreach ($labels as $label) { ?> <option value="<?php echo $label['labeltext']; ?>"<?php if($label['labeltext']==$assetrecord['assetlabel']){echo ' selected';}?>><?php echo $label['labeltext']; ?></option><?php } ?></select>                               
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr><th>Last Changed Date</th><td><div id="changeddate"><?php echo $assetrecord['changedDate']; ?></div></td></tr>
                                            <tr><th>Sandpiper OID</th><td><div id="sandpiperoid"><?php echo $assetrecord['oid']; ?></div></td><tr>
                                        </table>
                                    </div>
                                    <?php if($assetrecord['fileType']=='JPG' || $assetrecord['fileType']=='PNG'){?>
                                    <div class="col-xs-12 col-md-5">
                                        <a target="_blank" href="<?php echo $assetrecord['uri']; ?>"><img class="img-thumbnail" src="<?php echo $imgsrc; ?>"/></a>
                                    </div>
                                    <?php }?>
                                    <div>
                                        <form method="post" action="showAsset.php?assetid=<?php echo $assetid; ?>">
                                            <input type="submit" name="submit" value="Delete"/>
                                            <input type="hidden" name="id" value="<?php echo $assetrecord['id']; ?>"/>
                                            <input type="hidden" name="assetid" value="<?php echo $assetid; ?>"/>
                                        </form>
                                    </div>
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
                            Part Connections
                            <ul class="nav nav-tabs" id="partconnectionstab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="connectedparts-tab" data-bs-toggle="tab" href="#connectedparts" role="tab" aria-controls="connectedparts" aria-selected="true">Existing</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="newpartconnection-tab" data-bs-toggle="tab" href="#newpartconnection" role="tab" aria-controls="newpartconnection" aria-selected="false">New</a>
                                </li>
                            </ul>
                        </h5>
                        <div class="tab-content" id="partconnectionscontent">
                            <div class="tab-pane fade show active text-start m-3" id="connectedparts" role="tabpanel" aria-labelledby="connectedparts-tab">
                                <?php foreach($connectedparts as $connectedpart){?>
                                <div id="partassetconnectionid_<?php echo $connectedpart['id'];?>" style="padding: 2px;"> 
                                   <button type="button" class="btn btn-light" onclick="disconnectPart('<?php echo $connectedpart['partnumber'];?>','<?php echo $connectedpart['id'];?>')"><i class="bi bi-x"></i></button> 
                                   <a class="btn btn-secondary" href="showPart.php?partnumber=<?php echo $connectedpart['partnumber'];?>"><?php echo $connectedpart['partnumber'];?></a>
                                   <?php echo $pcdb->assetTypeCodeDescription($connectedpart['assettypecode']);?>
                                   
                                </div>
                                <?php }?>
                            </div>
                            <div class="tab-pane fade m-3" id="newpartconnection" role="tabpanel" aria-labelledby="newpartconnection-tab">
                                Partnumber <input type="text" id="partnumber" size="8"/> 
                                <select id="partassettypecode"><?php foreach ($allassettypes as $assettype){ ?><option value="<?php echo $assettype['code']; ?>"<?php if($assettype['code']=='P04'){echo ' selected';} ?>><?php echo $assettype['description']; if($assettype['description']=='User Defined'){echo ' ('.$assettype['code'].')';} ?></option><?php }?></select>
                                <select id="representation"><option value="A">Actual Depicted</option><option value="R">Similar Depicted</option></select>
                                <button onclick="connectPart('<?php echo $assetid;?>')">Connect</button>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <h5 class="card-header">
                            Brand Connections
                            <ul class="nav nav-tabs" id="brandconnectionstab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="connectedbrands-tab" data-bs-toggle="tab" href="#connectedbrands" role="tab" aria-controls="connectedbrands" aria-selected="true">Existing</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="newbrandconnection-tab" data-bs-toggle="tab" href="#newbrandconnection" role="tab" aria-controls="newbrandconnection" aria-selected="false">New</a>
                                </li>
                            </ul>
                        </h5>
                        <div class="tab-content" id="brandconnectionscontent">
                            <div class="tab-pane fade show active text-start m-3" id="connectedbrands" role="tabpanel" aria-labelledby="connectedbrands-tab">
                                <?php foreach($connectedbrands as $connectedbrand){?>
                                <div id="brandassetconnectionid_<?php echo $connectedbrand['connectionid'];?>" style="padding: 2px;"> 
                                   <button type="button" class="btn btn-light" onclick="disconnectBrand('<?php echo $connectedbrand['BrandID'];?>','<?php echo $connectedbrand['connectionid'];?>')"><i class="bi bi-x"></i></button>
                                   <a class="btn btn-secondary" href="showBrand.php?brandid=<?php echo $connectedbrand['BrandID'];?>"><?php echo $connectedbrand['BrandID'];?></a>
                                   <?php echo $pcdb->assetTypeCodeDescription($connectedbrand['assettypecode']); ?>
                                </div>
                                <?php }?>
                            </div>
                            <div class="tab-pane fade m-3" id="newbrandconnection" role="tabpanel" aria-labelledby="newbrandconnection-tab">
                              <div style="padding:5px;"><select id="brandid"><?php foreach ($brands as $brand){ ?><option value="<?php echo $brand['brandAAIAID']; ?>"><?php echo substr($brand['description'],0,25);?></option><?php }?></select></div>
                              <div style="padding:5px;"><select id="brandassettypecode"><?php foreach ($allassettypes as $assettype){ ?><option value="<?php echo $assettype['code']; ?>"<?php if($assettype['code']=='P04'){echo ' selected';} ?>><?php echo $assettype['description']; if($assettype['description']=='User Defined'){echo ' ('.$assettype['code'].')';} ?></option><?php }?></select></div>
                              <button onclick="connectBrand('<?php echo $assetid;?>')">Connect</button>
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