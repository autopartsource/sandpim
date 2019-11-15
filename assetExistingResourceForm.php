<?php
include_once('/var/www/html/class/assetClass.php');
$navCategory = 'assets';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset = new asset;
$error_msg = '';

if (isset($_POST['submit']) && $_POST['submit'] == 'Retrieve') 
{
    $destinationpath = '/var/www/html/ACESuploads/'.$_POST['filename'];
    $filesize=file_put_contents($destinationpath, fopen($_POST['uri'], 'r'));
    
    if($filesize)
    {
        if($exiftype = exif_imagetype($destinationpath))
        {
            $valid_upload=true;
            $imagedims = getimagesize($destinationpath);
            $colormodecode = 'RBG';
            $assetheight = $imagedims[0];
            $assetwidth = $imagedims[1];
            $dimensionUOM = 'PX';
            $filetype = $exiftype;
            $approved = 1;
            $filepath=$destinationpath;
            $filename=$pathparts['filename'];
            $filehash = md5_file($destinationpath);
            $filesize= filesize($destinationpath);
            $public=1;
            $description='Photo of '.$filename;
            $background='WHI';
            $uri=$_POST['uri'];
            $orientationviewcode='FRONT';
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
        <link rel="stylesheet" type="text/css" href="styles.css" />
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>

        <!-- Header -->
        <h1>Create image asset from uri</h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <div>
                <?php if ($error_msg) {
                    echo $error_msg;
                } ?>
                    
                    <form method="post" action="assetCreate.php">
                        <input type="hidden" name="assetid" value="<?php echo $filename;?>"/>
                        <input type="hidden" name="filename" value="<?php echo $filename;?>"/>
                        <input type="hidden" name="localfilepath" value="<?php echo $destinationpath;?>"/>
                        <input type="hidden" name="dimensionUOM" value="<?php echo $dimensionUOM;?>"/>
                        <input type="hidden" name="filehash" value="<?php echo $filehash;?>"/>
                        <div style="padding:10px;">File Type: <?php echo $filetype;?></div>
                        <input type="hidden" name="filetype" value="<?php echo $filetype;?>" />
                        <div style="padding:10px;">File Size: <?php echo $filesize;?></div>
                        <input type="hidden" name="filesize" value="<?php echo $filesize;?>"/>
                        <div style="padding:10px;">Width: <?php echo $imagedims[1];?></div>
                        <input type="hidden" name="assetheight" value="<?php echo $assetheight;?>"/>
                        <div style="padding:10px;">Height: <?php echo $imagedims[0];?></div>
                        <input type="hidden" name="assetwidth" value="<?php echo $assetwidth;?>"/>
                        <div style="padding:10px;">AssetID: <input type="text" name="assetid" value="<?php echo $filename;?>"/></div>
			<div style="padding:10px;">Description <input name="description" type="text" value="<?php echo $description;?>"/></div>
			<div style="padding:10px;">Orientation <input name="orientationviewcode" type="text" value="<?php echo $orientationviewcode;?>"/></div>
			<div style="padding:10px;">Background <input name="background" type="text" value="<?php echo $background;?>"/></div>
			<div style="padding:10px;">Color Mode Code<input name="colormodecode" type="text" value="<?php echo $colormodecode;?>"/></div>
                        <div style="padding:10px;">Public <input name="public" type="text" value="<?php echo $public;?>"/></div>
                        <div style="padding:10px;">URI <input name="uri" value="<?php echo $uri;?>"/></div>
                        <div style="padding:10px;"><input type="checkbox" name="discardlocal"/>Discard local copy</div>
                        <div style="padding:10px;"><input name="submit" type="submit" value="Create"/></div>
                    </form>
                </div>
                </div>
            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>