<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');
$navCategory = 'assets';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'assetCreate.php - access denied (404 returned) to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) 
{
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset = new asset;
$error_msg = false;

if (isset($_POST['submit']) && $_POST['submit'] == 'Create') 
{
    $BrandID=$_POST['brandid'];
    $assetid=$_POST['assetid'];
    $filename=$_POST['filename'];
    $localpath=$_POST['localpath'];
    $uri=$_POST['uri'];
    $orientationviewcode=$_POST['orientationviewcode'];
    $colormodecode=$_POST['colormodecode'];
    $assetheight=intval($_POST['assetheight']);
    $assetwidth=intval($_POST['assetwidth']);
    $dimensionUOM=$_POST['dimensionUOM'];
    $resolution=intval($_POST['resolution']);
    $background=$_POST['background'];
    $filetype=$_POST['filetype'];
    $public=0; if(isset($_POST['public']) && $_POST['public']=='on'){$public=1;}
    $approved=1;
    $description=$_POST['description'];
    $oid = $pim->newoid();
    $filehash=$_POST['filehash'];
    $filesize=intval($_POST['filesize']);
    $uripublic=0; if(isset($_POST['uripublic']) && $_POST['uripublic']=='on'){$uripublic=1;}
    $languagecode=''; if(isset($_POST['languagecode'])){$languagecode=$_POST['languagecode'];}
    $assetlabel=$_POST['assetlabel']; // internal label like "Assembly Guide" or "QC Drawing"    
    $createddate='2000-01-01';
    if($id = $asset->addAsset($assetid, $filename, $localpath, $uri, $orientationviewcode, $colormodecode, $assetheight, $assetwidth, $dimensionUOM,$resolution, $background, $filetype, $public, $approved, $description, $oid, $filehash,$filesize,$uripublic,$languagecode,$assetlabel,$createddate,1,1,1,1))
    {
        $error_msg = 'Asset '.$assetid.' was created.';
        $assetoid=$asset->updateAssetOID($assetid);
        $asset->logAssetEvent($assetid, $_SESSION['userid'], 'Asset created' ,$assetoid);
        
        $connectionid=$asset->connectBrandToAsset($BrandID,$assetid,$_POST['assettypecode'],1);
        $asset->logAssetEvent($assetid, $_SESSION['userid'], 'brand ['.$BrandID.'] was connected', $assetoid);
    }
    else 
    { // asset not created
        $error_msg = 'Error creating asset';
    }
    if(isset($_POST['discardlocal']))
    { // torch local copy that was brought down from uri
        unlink($_POST['localfilepath']);
    }    
}




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
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Create brand asset</h3>

                        <div class="card-body">
                            <h4>
                                <?php if ($error_msg) {
                                    echo $error_msg;
                                } ?>
                            </h4>

                            <div><a href="./showBrand.php?brandid=<?php echo $BrandID;?>">Back to <?php echo $BrandID;?></a></div>                            
                        </div>
                    </div>
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->

        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>