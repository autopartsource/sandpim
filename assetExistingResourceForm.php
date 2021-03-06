<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/pcdbClass.php');
$navCategory = 'assets';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset = new asset;
$pcdb=new pcdb();
$pim=new pim;
$error_msg = '';

$allassettypes=$pcdb->getAssetTypeCodes();
$guessedpartnumber='';


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
            $colormodecode = 'RBG';
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
            
            if($pim->validPart($filename))
            {// filename(sans ext) is found to be a valid partnumber
                $guessedpartnumber=$filename;
            }
            else
            {// filename(sans ext) is not found as a valid partnumber
                if(strpos($filename,'_'))
                {//filename contains '_' 
                    $namebits= explode('_', $filename);
                    if($pim->validPart($namebits[0]))
                    {
                        $guessedpartnumber=strtoupper($namebits[0]);
                    }
                }
            }
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
        <h1>Create image asset from existing uri</h1>

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

                        <form method="post" action="assetCreate.php">
                            <input type="hidden" name="filename" value="<?php echo $filename;?>"/>
                            <input type="hidden" name="basename" value="<?php echo $basename;?>"/>
                            <input type="hidden" name="localpath" value="<?php echo $basename;;?>"/>
                            <input type="hidden" name="dimensionUOM" value="<?php echo $dimensionUOM;?>"/>
                            <input type="hidden" name="filehash" value="<?php echo $filehash;?>"/>
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
                            <div style="padding:10px;">Orientation <input name="orientationviewcode" type="text" value="<?php echo $orientationviewcode;?>"/></div>
                            <div style="padding:10px;">Background <input name="background" type="text" value="<?php echo $background;?>"/></div>
                            <div style="padding:10px;">Color Mode Code<input name="colormodecode" type="text" value="<?php echo $colormodecode;?>"/></div>
                            <div style="padding:10px;">Resolution<input name="resolution" type="text" value="300"/></div>
                            <div style="padding:10px;">Public <input name="public" type="text" value="<?php echo $public;?>"/></div>
                            <div style="padding:10px;">URI <input name="uri" value="<?php echo $uri;?>"/></div>
                            <div style="padding:10px;"><label><input type="checkbox" id="uripublic" name="uripublic"/>URI is for public consumption</label></div>
                            <div style="padding:10px;"><input type="checkbox" name="discardlocal"/>Discard local copy</div>
                            
                            <div style="padding:10px;">Connect Part<input name="partnumber" value="<?php echo $guessedpartnumber;?>"/></div>
                            <select name="assettypecode" id="assettypecode"><?php foreach ($allassettypes as $assettype){ ?><option value="<?php echo $assettype['code']; ?>"<?php if($assettype['code']=='P04'){echo ' selected';} ?>><?php echo $assettype['description']; if($assettype['description']=='User Defined'){echo ' ('.$assettype['code'].')';} ?></option><?php }?></select>
                            <select name="representation" id="representation"><option value="A">Actual Depicted</option><option value="R">Similar Depicted</option></select>

                            
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