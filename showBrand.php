<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');
$navCategory = 'parts';

$pim = new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'showBrand.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

$asset = new asset;
$pcdb = new pcdb;
$interchange=new interchange;
$configGet = new configGet;

$BrandID='';
if(strlen($_GET['brandid'])==4 && $interchange->validBrand($_GET['brandid']))
{
 $BrandID = $_GET['brandid'];
}

$connectedassets=$asset->getAssetsConnectedToBrand($BrandID);


?>
<!DOCTYPE html>
<html>
    <head>        
        <script>
            
            function disconnectAsset(connectionid)
            {
                var assetdiv = document.getElementById('assetconnectionid_'+connectionid);
                assetdiv.parentNode.removeChild(assetdiv);

                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxDisconnectBrandAsset.php?connectionid='+connectionid+'&brandid=<?php echo $brandid;?>');
                xhr.onload = function()
                {
                 var response=JSON.parse(xhr.responseText);
                 document.getElementById("sandpiperoid").innerHTML=response.partoid;
                };
                xhr.send();
            }
            
            function showhideAssetForm()
            {
             var x = document.getElementById("assetform");
             var y = document.getElementById("showAssetFormIcon");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
              y.style.display="none";
             }
             else
             {
              x.style.display = "none";
              y.style.display="block";
             }
            }

        </script>
        <?php include('./includes/header.php'); ?>        
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                                                           
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-7 my-col colMain">
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start">
                            Brand <span class="text-info"><?php echo $interchange->brandName($BrandID).' ('.$BrandID.')'; ?></span>
                        </h3>
                        <div class="card-body">
                            
                            <table class="table" border="1" cellpadding="5">
                                <tr>
                                    <th>Assets</th>
                                    <td>
                                        <?php 
                                        foreach($connectedassets as $connectedasset)
                                        {
                                              echo '<div style="padding-bottom:3px;" id="assetconnectionid_'.$connectedasset['connectionid'].'"><div style="float:left;"><button class="btn btn-sm btn-outline-danger" title="Disconnect this asset from this brand" onclick="disconnectAsset(\''.$connectedasset['connectionid'].'\')">x</button></div><div style="border:1px solid;padding:3px;margin-left:4px;background:#7ad0fe;float:left;"><a class="btn btn-info" role="button" href="showAsset.php?assetid='.$connectedasset['assetid'].'">'.$connectedasset['assetid'].'</a></div>  <div style="float:left;padding-left:5px;">'. $pcdb->assetTypeCodeDescription($connectedasset['assettypecode']).'</div>     <div style="clear:both;"></div></div>';
                                        }
                                        ?>
                                        <div id="showAssetFormIcon" style="display:block;" onclick="showhideAssetForm()"><img src="./expandmore.png" title="Expand to show assets form"/></div>
                                        <div  id="assetform" style="display:none; padding:25px;">
                                            <form action="brandAssetExistingResourceForm.php" method="post">
                                                <div>Create a new asset (from URI) connected to this brand</div>
                                                <div>URI <input type="text" name="uri" size="50"/></div>
                                                <div>Filename <input type="text" size="25" name="basename"/><input type="hidden" name="brandid" value="<?php echo $BrandID;?>"/>
                                                <input type="submit" name="submit" value="Retrieve"/></div>
                                            </form>
                                        <div id="hideAssetFormIcon" onclick="showhideAssetForm()"><img src="./expandless.png" title="Hide assets form"/></div>
                                        </div>
                                    </td>
                                <tr>
                            </table>
                        </div>
                    </div>                    
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-3 my-col colRight">
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->

        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>