<?php
include_once('/var/www/html/class/pimClass.php');
include_once('/var/www/html/class/assetClass.php');
$navCategory = 'assets';

session_start();
if (!isset($_SESSION['userid'])) 
{
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset = new asset;
$pim = new pim;
$error_msg = false;

$valid_upload=false;

if (isset($_POST['submit']) && $_POST['submit'] == 'Create') 
{
    $assetid=$_POST['assetid'];
    $filename=$_POST['filename'];
    $localpath=$_POST['localpath'];
    $uri=$_POST['uri'];
    $orientationviewcode=$_POST['orientationviewcode'];
    $colormodecode=$_POST['colormodecode'];
    $assetheight=intval($_POST['assetheight']);
    $assetwidth=intval($_POST['assetwidth']);
    $dimensionUOM=$_POST['dimensionUOM'];
    $background=$_POST['background'];
    $filetype=$_POST['filetype'];
    $public=intval($_POST['public']);
    $approved=1;
    $description=$_POST['description'];
    $oid = $pim->newoid();
    $filehash=$_POST['filehash'];
    $filesize=intval($_POST['filesize']);
    $uripublic=intval($_POST['uripublic']);
        
    if($id = $asset->addAsset($assetid, $filename, $localpath, $uri, $orientationviewcode, $colormodecode, $assetheight, $assetwidth, $dimensionUOM, $background, $filetype, $public, $approved, $description, $oid, $filehash,$filesize,$uripublic))
    {
        $error_msg = 'Asset id ' . $id . ' was created.';
        $asset->logAppEvent($assetid, $_SESSION['userid'], 'Asset created' , '');
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


if (isset($_POST['submit']) && $_POST['submit'] == 'Upload') 
{
    $target_dir = '/var/www/html/ACESuploads/';
    $destinationpath = $target_dir . basename($_FILES['fileToUpload']['name']);

    if (!file_exists($destinationpath))
    {
        if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $destinationpath))
        {
            $pathparts = pathinfo($destinationpath);

            if($exiftype = exif_imagetype($destinationpath))
            {
                $valid_upload=true;
                $imagedims = getimagesize($destinationpath);
                $colormodecode = 'RBG';
                $assetheight = $imagedims[1];
                $assetwidth = $imagedims[0];
                $dimensionUOM = 'PX';
                $filetype = $exiftype;
                $approved = 1;
                $filepath=$destinationpath;
                $filename=$pathparts['filename'];
                $basename=$pathparts['basename'];
                $filehash = md5_file($destinationpath);
                $filesize= filesize($destinationpath);
                $public=1;
                $uripublic=1;
                $description='Photo of '.$filename;
                $background='WHI';
                $uri='';
                $localpath=$destinationpath;
                $orientationviewcode='FRONT';
                
            }
            else { // not a supported image type
                $error_msg = 'Not a supported image type';
            }
        } else {
            $error_msg = 'Error uploading file';
        }
    } else {
        $error_msg = 'File already exists';
    }
}







?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="styles.css" />
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>

        <!-- Header -->
        <h1>Create image asset</h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <div>
                <?php if ($error_msg) {
                    echo $error_msg;
                } ?>
                    
                    <?php if($valid_upload){?>
                    <form method="post">
                        <input type="hidden" name="filename" value="<?php echo $filename;?>"/>
                        <input type="hidden" name="basename" value="<?php echo $basename;?>"/>
                        <input type="hidden" name="localpath" value="<?php echo $localpath;?>"/>
                        <input type="hidden" name="colormodecode" value="<?php echo $colormodecode;?>"/>
                        <input type="hidden" name="assetheight" value="<?php echo $assetheight;?>"/>
                        <input type="hidden" name="assetwidth" value="<?php echo $assetwidth;?>"/>
                        <input type="hidden" name="dimensionUOM" value="<?php echo $dimensionUOM;?>"/>
                        <input type="hidden" name="filetype" value="<?php echo $filetype;?>"/>
                        <input type="hidden" name="filehash" value="<?php echo $filehash;?>"/>
                        <input type="hidden" name="filesize" value="<?php echo $filesize;?>"/>

                        <div style="padding:10px;">File Type: <?php echo $filetype;?></div>
                        <div style="padding:10px;">File Size: <?php echo $filesize;?></div>
                        <div style="padding:10px;">Width: <?php echo $imagedims[1];?></div>
                        <div style="padding:10px;">Height: <?php echo $imagedims[0];?></div>
                        <div style="padding:10px;">AssetID: <input type="text" name="assetid" value="<?php echo $filename;?>"/></div>
			<div style="padding:10px;">Description <input name="description" type="text" value="<?php echo $description;?>"/></div>
			<div style="padding:10px;">Orientation <input name="orientationviewcode" type="text" value="<?php echo $orientationviewcode;?>"/></div>
			<div style="padding:10px;">Background <input name="background" type="text" value="<?php echo $background;?>"/></div>
			<div style="padding:10px;">Public <input name="public" type="text" value="<?php echo $public;?>"/></div>
                        <div style="padding:10px;">URI <input name="uri" type="text" value="<?php echo $uri;?>"/></div>
			<div style="padding:10px;"><label><input type="checkbox" id="uripublic" name="uripublic"/>URI is for public consumption</label></div>
                        <div style="padding:10px;"><input name="submit" type="submit" value="Create"/></div>
                    </form>
                    <?php }?>
                </div>
            </div>
            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>