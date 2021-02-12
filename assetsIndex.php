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
                
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-7 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Assets</h3>

                        <div class="card-body">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="search-tab" data-bs-toggle="tab" href="#search" role="tab" aria-controls="search" aria-selected="true">Search</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="upload-tab" data-bs-toggle="tab" href="#upload" role="tab" aria-controls="upload" aria-selected="false">Upload Asset</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="uri-tab" data-bs-toggle="tab" href="#uri" role="tab" aria-controls="contact" aria-selected="false">Upload from URI</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active text-start" id="search" role="tabpanel" aria-labelledby="search-tab">
                                    <div style="padding:10px;">
                                    <form method="get" action="assetsIndex.php">
                                        <div style="padding:3px;">Asset ID's
                                            <select name="assetidsearchtype">
                                                <option value="startswith">starting with</option>
                                                <option value="contains" <?php if(isset($_GET['assetidsearchtype']) && $_GET['assetidsearchtype']=='contains'){echo 'selected';}?>>containing</option>
                                                <option value="endswith" <?php if(isset($_GET['assetidsearchtype']) && $_GET['assetidsearchtype']=='endswith'){echo 'selected';}?>>ending with</option>
                                                <option value="equals" <?php if(isset($_GET['assetidsearchtype']) && $_GET['assetidsearchtype']=='equals'){echo 'selected';}?>>exactly equal to</option>
                                            </select> <input type="text" name="assetid" value="<?php if(isset($_GET['assetid'])){echo substr(strtoupper(trim($_GET['assetid'])),0,20); }?>"/>
                                        </div>
                                        <div style="padding:3px;">File type 
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
                                        <div style="padding:3px;">Orientation <select name="orientation"><option value="any">Any</option><?php foreach($orientationviewcodes as $orientationviewcode){?> <option value="<?php echo $orientationviewcode['code'];?>" <?php if(isset($_GET['orientation']) && $_GET['orientation']==$orientationviewcode['code']){echo ' selected';}?>><?php echo $orientationviewcode['description'];?></option><?php }?></select></div>
                                        <div style="padding:3px;">Public/Private
                                            <select name="publicprivate"><option value="any">Any</option>
                                                <option value="public" <?php if(isset($_GET['publicprivate']) && $_GET['publicprivate']=='public'){echo ' selected';}?>>Public</option>
                                                <option value="private" <?php if(isset($_GET['publicprivate']) && $_GET['publicprivate']=='private'){echo ' selected';}?>>Private</option>
                                            </select>
                                        </div>
                                        <div style="padding:3px;">Created Date
                                            <select name="createdsearchtype">
                                                <option value="any">Any Date</option>
                                                <option value="from" <?php if(isset($_GET['createdsearchtype']) && $_GET['createdsearchtype']=='from'){echo 'selected';}?>>On or After</option>
                                                <option value="to" <?php if(isset($_GET['createdsearchtype']) && $_GET['createdsearchtype']=='to'){echo 'selected';}?>>On or Before</option>
                                                <option value="on" <?php if(isset($_GET['createdsearchtype']) && $_GET['createdsearchtype']=='on'){echo 'selected';}?>>On</option>
                                            </select>
                                            <input type="text" name="createddate" value="<?php if(isset($_GET['createddate']) && strlen($_GET['createddate'])==10){echo $_GET['createddate'];}else{echo  date('Y-m-d', strtotime('-1 day'));}?>"/></div>
                                        <div style="padding:3px;">File hash <input type="text" name="filehash" value="<?php if(isset($_GET['filehash'])){ echo substr(strtolower($_GET['filehash']),0,32);}?>"/></div>
                                        <div style="padding:3px;">Limit results to <select name="limit"><option value="10">10</option><option value="20" selected>20</option><option value="50">50</option><option value="100">100</option><option value="200">200</option><option value="500">500</option></select></div>
                                        <div style="padding:3px;"><input type="submit" name="submit" value="Search"/></div>
                                    </form>

                                    <div class="text-center">
                                        <?php if(count($assetrecords)){
                                            echo '
                                                <div class="card shadow-sm">
                                                    <!-- Header -->
                                                    <h5 class="card-header text-start">Search Results</h5>
                                                    <div class="card-body scroll">
                                                    <div class="d-grid gap-2 col-6 mx-auto">';
                                        
                                            foreach($assetrecords as $assetrecord){
                                                echo '<a href="./showAsset.php?assetid='.$assetrecord['assetid'].'" class="btn btn-secondary">'.$assetrecord['assetid'].'</a>';
                                            }
                                            
                                            echo '</div></div></div>';
                                        } else { // no results found
                                            if (isset($_GET['submit'])) { // user submitted a search
                                                echo '<hr>';
                                                echo '<div class="alert alert-danger m-2">No Results Found</div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="upload" role="tabpanel" aria-labelledby="upload-tab">
                                    <form method="post" enctype="multipart/form-data" action="assetCreate.php">
                                        <div style="padding:10px;"><input type="file" name="fileToUpload" id="fileToUpload"></div>
                                        <div style="padding:10px;"><input name="submit" type="submit" value="Upload"/></div>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="uri" role="tabpanel" aria-labelledby="uri-tab">
                                    <form action="assetExistingResourceForm.php" method="post">
                                        <div style="padding:5px;">URI Path <input type="text" name="uri"/></div>
                                        <div style="padding:5px;">Save as filename <input type="text" name="basename"/></div>
                                        <div style="padding:5px;"><input type="submit" name="submit" value="Retrieve"/></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-3 my-col colRight">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Recent Assets</h3>

                        <div class="card-body scroll">
                            <?php
                                if (count($assets)) {
                                    echo '<table class="table"><tr><th>AssetID</th><th>Description</th></tr>';
                                    foreach ($assets as $record) {
                                        echo '<tr><td><a href="showAsset.php?assetid='.$record['assetid'].'" class="btn btn-secondary">' . $record['assetid'] . '</a></td><td>' . $record['description'] . '</td></tr>';
                                    }
                                    echo '</table></div>';
                                }
                            ?>
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
