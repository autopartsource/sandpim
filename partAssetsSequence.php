<?php
include_once('./includes/loginCheck.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');
$navCategory = 'parts';

$pim = new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'partAssetsSequence.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

$pcdb = new pcdb;
$asset = new asset;
$configGet = new configGet;

$partnumber = $pim->sanitizePartnumber($_GET['partnumber']);
$part = $pim->getPart($partnumber);

if(isset($_POST['submit']))
{
 foreach($_POST as $varname=>$value)
 {
  $varbits=explode('_',$varname);
  if(count($varbits)==2 && $varbits[0]=='sequence' && intval($varbits[1])>0)
  {
   $existingconnection=$asset->getPartAssetConnectionByID(intval($varbits[1]));   
   if($existingconnection!==false)
   {
    if($existingconnection['sequence']!=$value)
    {
     $newoid=$pim->updatePartOID($partnumber);
     $asset->updatePartAssetSequence($partnumber, intval($varbits[1]), intval($value));
     $pim->logPartEvent($partnumber, $_SESSION['userid'], 'connection to asset ['.$existingconnection['assetid'].'] re-sequenced from ['.$existingconnection['sequence'].'] to ['.intval($value).']', $newoid);
    }  
   }
  }
 }    
}

$connectedassets=$asset->getAssetsConnectedToPart($partnumber);
$primaryphotouri=$asset->primaryPhotoURIofPart($partnumber);
$dependantparts=$pim->getPartnumbersByBasepart($partnumber);
?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        
        <script>
            
            function disconnectAsset(connectionid)
            {
                var assetdiv = document.getElementById('assetconnectionid_'+connectionid);
                assetdiv.parentNode.removeChild(assetdiv);

                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxDisconnectPartAsset.php?connectionid='+connectionid+'&partnumber=<?php echo $partnumber;?>');
                xhr.onload = function()
                {
                 var response=JSON.parse(xhr.responseText);
                 document.getElementById("sandpiperoid").innerHTML=response.partoid;
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
                <div class="col-xs-12 col-md-2">
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8">
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start">Assets Sequence for <a href="./showPart.php?partnumber=<?php echo $partnumber?>"><span class="text-info"><?php echo $partnumber?></span></a></h3>
                        <div class="alert alert-danger" role="alert" id="heading-alert" style="display:none;">This is a danger alert—check it out!</div>
                        <div class="card-body">
                            <?php if ($part) {?>
                            <form method="post">
                                <div style="padding:10px;">
                                    <table class="table">
                                        <tr><th>Sequence</th><th>AssetID</th><th>Type</th><th>Thumbnail</th><th>Tags</th></tr>
                                        <?php 
                                        foreach($connectedassets as $connectedasset)
                                        {?>

                                        <tr>
                                            <td><div style="padding:10px;"><input style="text-align:right;" type="text" name="sequence_<?php echo $connectedasset['connectionid'];?>" value="<?php echo $connectedasset['sequence'];?>" size="2"/></div></td>

                                            <td><div style="padding:10px;"><?php echo $connectedasset['assetid'];?></div></td>

                                            <td>                                            
                                            <?php 
                                            $assettags=$asset->getAssettagsForAsset($connectedasset['assetid']);
                                            $assettagdivs='';

                                            foreach($assettags as $assettag)
                                            {
                                                $assettagdivs.='<div style="background-color:#c0c0c0;padding-left:5px;padding-right:5px;margin-left:5px;margin-bottom:5px;border-radius:4px;font-size:.75em;">'.$assettag['tagtext'].'</div>';
                                            }

                                            if($connectedasset['inheritedfrom']=='')
                                            {// this asset is conneted directly to this part (not inherited from a basepart)
                                                echo '<div style="padding:10px 10px 0px 10px;" id="assetconnectionid_'.$connectedasset['connectionid'].'">'.$pcdb->assetTypeCodeDescription($connectedasset['assettypecode']).'</div>';
                                            }
                                            ?>

                                            </td>


                                            <td>
                                                <div style="width:200px;">
                                                <?php
                                                if($connectedasset['uri']!='' && ($connectedasset['filetype']=='JPG' || $connectedasset['filetype']=='PNG'))
                                                {
                                                    echo '<div><a href="./showAsset.php?assetid='.$connectedasset['assetid'].'"><img class="img-thumbnail" src="'.$connectedasset['uri'].'" /></a></div>';
                                                }
                                                ?>
                                                </div>
                                            </td>

                                            <td>
                                                <div style="padding:10px;">
                                                <?php echo $assettagdivs; ?>
                                                </div>
                                            </td>


                                        </tr>

                                        <?php } ?>

                                    </table>
                                </div>
                                <input type="submit" name="submit" value="Update"/>
                            </form>
                            <?php
                            } else {
                                echo 'Part '.$partnumber.' not found (<a href="./newPart.php?partnumber='.$partnumber.'">add it</a>)';
                            }
                            ?>
                        </div>
                    </div>                    
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2">
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>