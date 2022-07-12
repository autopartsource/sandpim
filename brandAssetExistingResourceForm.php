<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/logsClass.php');
$navCategory = 'assets';

$pim=new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'brandAssetExistingResourceForm.php - access denied (404 returned) to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset = new asset;
$pcdb=new pcdb();
$interchange=new interchange();
$error_msg = '';

$allassettypes=$pcdb->getAssetTypeCodes();


if (isset($_POST['submit']) && $_POST['submit'] == 'Retrieve') 
{
    $destinationpath = '/var/www/html/ACESuploads/'.$_POST['basename'];
    $pathparts = pathinfo($destinationpath);

    $filesize=file_put_contents($destinationpath, fopen($_POST['uri'], 'r'));
    
    if($filesize)
    {
        if($exiftype = exif_imagetype($destinationpath))
        {
            $valid_upload=true;
            $imagedims = getimagesize($destinationpath);
            $colormodecode = 'RGB';
            $assetwidth = $imagedims[0];
            $assetheight = $imagedims[1];
            $dimensionUOM = 'PX';
            $filetype = $exiftype;
            $approved = 1;
            $filepath=$destinationpath;
            $basename=$_POST['basename'];
            $filename=$pathparts['filename'];
            $filehash = md5_file($destinationpath);
            $filesize= filesize($destinationpath);
            $public=1;
            $description='Photo of '.$filename;
            $background='WHI';
            $uri=$_POST['uri'];
            $orientationviewcode='FRO';
            $BrandID = $_POST['brandid'];
            
        }
        else
        {
            $error_msg = 'Error uploading file';
        }
    }
    else
    {
        $error_msg = 'Failed to get file';
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

        <!-- Header -->
        <h1>Create brand asset from existing uri</h1>

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div>
                    <?php if ($error_msg) {
                        echo $error_msg;
                    } ?>
                        <div style="padding: 20px;"><img src="<?php echo $uri;?>" width="200"/></div>
                        <form method="post" action="brandAssetCreate.php">
                            <input type="hidden" name="filename" value="<?php echo $basename;?>"/>
                            <input type="hidden" name="basename" value="<?php echo $basename;?>"/>
                            <input type="hidden" name="localpath" value="<?php echo $basename;;?>"/>
                            <input type="hidden" name="dimensionUOM" value="<?php echo $dimensionUOM;?>"/>
                            <input type="hidden" name="filehash" value="<?php echo $filehash;?>"/>
                            <input type="hidden" name="orientationviewcode" value="FRO"/>
                            <div style="padding:10px;">File Type: <?php echo $asset->niceExifTypeName($filetype);?></div>
                            <input type="hidden" name="filetype" value="<?php echo $asset->niceExifTypeName($filetype);?>" />
                            <div style="padding:10px;">File Size: <?php echo $asset->niceFileSize($filesize);?></div>
                            <input type="hidden" name="filesize" value="<?php echo $filesize;?>"/>
                            <div style="padding:10px;">Width: <?php echo $imagedims[0];?></div>
                            <input type="hidden" name="assetheight" value="<?php echo $assetheight;?>"/>
                            <div style="padding:10px;">Height: <?php echo $imagedims[1];?></div>
                            <input type="hidden" name="assetwidth" value="<?php echo $assetwidth;?>"/>
                            <div style="padding:10px;">AssetID: <input type="text" name="assetid" value="<?php echo $filename;?>"/></div>
                            <div style="padding:10px;">Description <input name="description" type="text" value="<?php echo $description;?>"/></div>
                            <div style="padding:10px;">Label <input name="assetlabel" type="text"/></div>
                            <div style="padding:10px;">Background <input name="background" type="text" value="<?php echo $background;?>"/></div>
                            <div style="padding:10px;">Color Mode Code <input name="colormodecode" type="text" value="<?php echo $colormodecode;?>"/></div>
                            <div style="padding:10px;">Resolution <input name="resolution" type="text" value="300"/></div>
                            <div style="padding:10px;"><label><input name="public" type="checkbox" id="publicasset" <?php if($public==1){echo 'checked="checked"';}?>/>Public Asset</label></div>
                            <div style="padding:10px;">URI <input name="uri" value="<?php echo $uri;?>"/></div>
                            <div style="padding:10px;"><label><input type="checkbox" id="uripublic" name="uripublic" checked="checked"/>URI is for public consumption</label></div>
                            <div style="padding:10px;"><input type="checkbox" name="discardlocal" checked="checked"/>Discard local copy</div>
                            
                            <input type="hidden" name="brandid" value="<?php echo $BrandID;?>"/>
                            <div style="padding:10px;">Connect BrandID: <?php echo $BrandID.' ('.$interchange->brandName($BrandID).')';?></div>
                            <select name="assettypecode" id="assettypecode"><?php foreach ($allassettypes as $assettype){ ?><option value="<?php echo $assettype['code']; ?>"<?php if($assettype['code']=='P04'){echo ' selected';} ?>><?php echo $assettype['description']; if($assettype['description']=='User Defined'){echo ' ('.$assettype['code'].')';} ?></option><?php }?></select>
                            
                            <div style="padding:10px;"><input name="submit" type="submit" value="Create"/></div>
                        </form>
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