<?php
include_once('./class/assetClass.php');
include_once('./class/pcdbClass.php');
$navCategory = 'assets';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}



$asset = new asset();
$pcdb=new pcdb();

$assetrecords=array();

if(isset($_GET['submit']) && $_GET['submit']=='Search')
{
    $assetrecords=$asset->getAssets($_GET['assetid'],$_GET['assetidsearchtype'],$_GET['filetype'],$_GET['orientation'],$_GET['createddate'],$_GET['createdsearchtype'],$_GET['publicprivate'],$_GET['filehash'],$_GET['limit']);
}





$assets = $asset->getRecentAssets(20);
$orientationviewcodes=$pcdb->getAssetOrientationViewCodes();

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>Assets</h1>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div>
                        <form method="get" action="assetsIndex.php">
                            <div>Asset ID's
                                <select name="assetidsearchtype">
                                    <option value="startswith">starting with</option>
                                    <option value="contains" <?php if(isset($_GET['assetidsearchtype']) && $_GET['assetidsearchtype']=='contains'){echo 'selected';}?>>containing</option>
                                    <option value="endswith" <?php if(isset($_GET['assetidsearchtype']) && $_GET['assetidsearchtype']=='endswith'){echo 'selected';}?>>ending with</option>
                                    <option value="equals" <?php if(isset($_GET['assetidsearchtype']) && $_GET['assetidsearchtype']=='equals'){echo 'selected';}?>>exactly equal to</option>
                                </select> <input type="text" name="assetid" value="<?php if(isset($_GET['assetid'])){echo substr(strtoupper(trim($_GET['assetid'])),0,20); }?>"/>
                            </div>
                            <div>File type 
                                <select name="filetype">
                                    <option value="any">Any</option>
                                    <option value="JPG" <?php if(isset($_GET['filetype']) && $_GET['filetype']=='JPG'){echo 'selected';}?>>JPG</option>
                                    <option value="TIFF" <?php if(isset($_GET['filetype']) && $_GET['filetype']=='TIFF'){echo 'selected';}?>>TIFF</option>
                                    <option value="PDF" <?php if(isset($_GET['filetype']) && $_GET['filetype']=='PDF'){echo 'selected';}?>>PDF</option>
                                    <option value="PNG" <?php if(isset($_GET['filetype']) && $_GET['filetype']=='PNG'){echo 'selected';}?>>PNG</option>
                                    <option value="BMP" <?php if(isset($_GET['filetype']) && $_GET['filetype']=='BMP'){echo 'selected';}?>>BMP</option>
                                    <option value="MP3" <?php if(isset($_GET['filetype']) && $_GET['filetype']=='MP3'){echo 'selected';}?>>MP3</option>
                                    <option value="MP4" <?php if(isset($_GET['filetype']) && $_GET['filetype']=='MP4'){echo 'selected';}?>>MP4</option>
                                    <option value="ZIP" <?php if(isset($_GET['filetype']) && $_GET['filetype']=='ZIP'){echo 'selected';}?>>ZIP</option>
                                </select>
                            </div>
                            <div>Orientation <select name="orientation"><option value="any">Any</option><?php foreach($orientationviewcodes as $orientationviewcode){?> <option value="<?php echo $orientationviewcode['code'];?>" <?php if(isset($_GET['orientation']) && $_GET['orientation']==$orientationviewcode['code']){echo ' selected';}?>><?php echo $orientationviewcode['description'];?></option><?php }?></select></div>
                            <div>Public/Private
                                <select name="publicprivate"><option value="any">Any</option>
                                    <option value="public" <?php if(isset($_GET['publicprivate']) && $_GET['publicprivate']=='public'){echo ' selected';}?>>Public</option>
                                    <option value="private" <?php if(isset($_GET['publicprivate']) && $_GET['publicprivate']=='private'){echo ' selected';}?>>Private</option>
                                </select>
                            </div>
                            <div>Created Date
                                <select name="createdsearchtype">
                                    <option value="any">Any Date</option>
                                    <option value="from" <?php if(isset($_GET['createdsearchtype']) && $_GET['createdsearchtype']=='from'){echo 'selected';}?>>On or After</option>
                                    <option value="to" <?php if(isset($_GET['createdsearchtype']) && $_GET['createdsearchtype']=='to'){echo 'selected';}?>>On or Before</option>
                                    <option value="on" <?php if(isset($_GET['createdsearchtype']) && $_GET['createdsearchtype']=='on'){echo 'selected';}?>>On</option>
                                </select>
                                <input type="text" name="createddate" value="<?php if(isset($_GET['createddate']) && strlen($_GET['createddate'])==10){echo $_GET['createddate'];}else{echo  date('Y-m-d', strtotime('-1 day'));}?>"/></div>
                            <div>File hash<input type="text" name="filehash" value="<?php if(isset($_GET['filehash'])){ echo substr(strtolower($_GET['filehash']),0,32);}?>"/></div>
                            <div>Limit results to <select name="limit"><option value="10">10</option><option value="20" selected>20</option><option value="50">50</option><option value="100">100</option><option value="200">200</option><option value="500">500</option></select></div>
                            <div><input type="submit" name="submit" value="Search"/></div>
                        </form>
                    </div>
                    
                    
                    <div>
                    <?php if(count($assetrecords)){foreach($assetrecords as $assetrecord){
                        echo '<div><a href="./showAsset.php?assetid='.$assetrecord['assetid'].'">'.$assetrecord['assetid'].'</a></div>';
                    }}?>
                    </div>
                    



                    
                    <div style="padding:5px;border: 1px solid;margin:3px;">
                        Create image asset from upload
                        <form method="post" enctype="multipart/form-data" action="assetCreate.php">
                            <div style="padding:10px;"><input type="file" name="fileToUpload" id="fileToUpload"></div>
                            <div style="padding:10px;"><input name="submit" type="submit" value="Upload"/></div>
                        </form>
                    </div>
                    <div style="padding:5px;border: 1px solid;margin:3px;">
                        <form action="assetExistingResourceForm.php" method="post">
                            <div>Create image asset from uri <input type="text" name="uri"/></div>
                            <div>Save as filename <input type="text" name="basename"/></div>
                            <div><input type="submit" name="submit" value="Retrieve"/></div>
                        </form>
                    </div>
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                    <?php
                    if (count($assets)) {
                        echo '<div style="padding:5px;border: 1px solid;margin:3px;">Recent Assets ';
                        echo '<table><tr><th>AssetID</th><th>Description</th></tr>';
                        foreach ($assets as $record) {
                            echo '<tr><td><a href="showAsset.php?assetid='.$record['assetid'].'">' . $record['assetid'] . '</a></td><td>' . $record['description'] . '</td></tr>';
                        }
                        echo '</table></div>';
                    }
                    ?>
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>
