<?php
include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/assetClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/logsClass.php');
$navCategory = 'assets';

$pim=new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'assetExistingResourceForm.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$configGet = new configGet();
$localimagestorepath=$configGet->getConfigValue('localImageStorePath');


$asset = new asset;
$pcdb=new pcdb();
$error_msg = '';

$allassettypes=$pcdb->getAssetTypeCodes();
$guessedpartnumber='';
$valid_upload=false;
$displayableimage=false;
$assetid='';
$languagecode='EN';

if (isset($_POST['submit']) && $_POST['submit'] == 'Retrieve') 
{
    
    $destinationpath = $localimagestorepath.'/'.$_POST['basename'];
    $pathparts = pathinfo($destinationpath);
    $filesize=file_put_contents($destinationpath, fopen($_POST['uri'], 'r'));
    
    if($filesize)
    {
        $valid_upload=true;
        $approved = 1;
        $exiftype = exif_imagetype($destinationpath);
        
        if($exiftype!='')
        {
            $displayableimage=true;
            $imagedims = getimagesize($destinationpath);
            $colormodecode = 'RGB';
            $assetwidth = intval($imagedims[0]);
            $assetheight = intval($imagedims[1]);
            if($assetwidth==0 || $assetheight==0){$assetwidth=100;$assetheight=100;}
            $dimensionUOM = 'PX';
            $filetype = $asset->niceExifTypeName($exiftype);
            
            $filepath=$destinationpath;
            $basename=$_POST['basename'];
            
            if(strpos($basename,'.'))
            {//basename contains at least one '.' and the exif type was not detected as a know type                
                $namebits=explode('.', $basename);
                $assetid=$namebits[0];
                if($filetype=='')
                {
                    $filetype= strtoupper($namebits[count($namebits)-1]);
                }
            }

            $filename=$pathparts['filename'];
            $filehash = md5_file($destinationpath);
            $filesize= filesize($destinationpath);
            $public=1;
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
            
            $description='Photo of '.$guessedpartnumber;            
            
        }
        else
        {// does not seem to be an image
            
            $basename=$_POST['basename'];
            $colormodecode = 'RGB';
            $assetwidth=100;$assetheight=100;
            $dimensionUOM = 'PX';
            $filetype = '';

            $namebits=explode('.', $basename);
            
            if(strpos($basename,'.'))
            {//basename contains at least one '.' and the exif type was not detected as a know type
                $filetype= strtoupper($namebits[count($namebits)-1]);
                $assetid=$namebits[0];
            }

            $filename=$basename;
            $filehash = md5_file($destinationpath);
            $filesize= filesize($destinationpath);
            $public=1;
            $description='Photo of '.$filename;
            $background='WHI';
            $uri=$_POST['uri'];
            $orientationviewcode='FRO';
        }
        
        
        if($asset->getAssetById($assetid))
        {
            $assetid='';   
        }
        
        
    }
    else
    {
        $error_msg = 'Failed to get file';
    }
}

if(isset($_POST['partnumber']) && trim($_POST['partnumber'])!='' && $pim->validPart(trim($_POST['partnumber'])))
{
 $guessedpartnumber=trim($_POST['partnumber']);
}

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
                    <?php if ($error_msg) {echo $error_msg;} ?>
                        
                        <?php if($valid_upload){?>
                                                
                        <div style="padding: 20px;"><?php if($displayableimage){?><img src="<?php echo $uri;?>" width="200"/><?php }else{echo '';}?></div>
                        <form method="post" action="assetCreate.php">
                            <input type="hidden" name="filename" value="<?php echo $basename;?>"/>
                            <input type="hidden" name="basename" value="<?php echo $basename;?>"/>
                            <input type="hidden" name="localpath" value=""/>
                            <input type="hidden" name="dimensionUOM" value="<?php echo $dimensionUOM;?>"/>
                            <input type="hidden" name="filehash" value="<?php echo $filehash;?>"/>
                            <div style="padding:10px;">File Type: <?php echo $filetype;?></div>
                            <input type="hidden" name="filetype" value="<?php echo $filetype;?>" />
                            <div style="padding:10px;">File Size: <?php echo $asset->niceFileSize($filesize);?></div>
                            <input type="hidden" name="filesize" value="<?php echo $filesize;?>"/>
                            <div style="padding:10px;">File Hash: <?php echo $filehash;?></div>
                            <div style="padding:10px;">Width: <?php echo $assetwidth;?></div>
                            <input type="hidden" name="assetheight" value="<?php echo $assetheight;?>"/>
                            <div style="padding:10px;">Height: <?php echo $assetheight;?></div>
                            <input type="hidden" name="assetwidth" value="<?php echo $assetwidth;?>"/>
                            <input type="hidden" name="languagecode" value="<?php echo $languagecode;?>"/>
                            <div style="padding:10px;">AssetID: <input type="text" name="assetid" value="<?php echo $assetid;?>"/></div>
                            <div style="padding:10px;">Description <input name="description" type="text" value="<?php echo $description;?>"/></div>
                            <div style="padding:10px;">Label <input name="assetlabel" type="text"/></div>
                            <div style="padding:10px;">Orientation <select name="orientationviewcode"><?php foreach ($orientationviewcodes as $orientationviewcode) { ?> <option value="<?php echo $orientationviewcode['code']; ?>"<?php if($orientationviewcode['code']=='TOP'){echo ' selected';}?>><?php echo $orientationviewcode['description']; ?></option><?php } ?></select></div>
                            <div style="padding:10px;">Background <input name="background" type="text" value="<?php echo $background;?>"/></div>
                            <div style="padding:10px;">Color Mode Code <input name="colormodecode" type="text" value="<?php echo $colormodecode;?>"/></div>
                            <div style="padding:10px;">Resolution <input name="resolution" type="text" value="300"/></div>
                            <div style="padding:10px;"><label><input name="public" type="checkbox" id="publicasset" <?php if($public==1){echo 'checked="checked"';}?>/>Public Asset</label></div>
                            <div style="padding:10px;">URI <input name="uri" value="<?php echo $uri;?>"/></div>
                            <div style="padding:10px;"><label><input type="checkbox" id="uripublic" name="uripublic" checked="checked"/>URI is for public consumption</label></div>
                            <div style="padding:10px;"><input type="checkbox" name="discardlocal" checked="checked"/>Discard local copy</div>
                            
                            <div style="padding:10px;">Connect Part <input name="partnumber" value="<?php echo $guessedpartnumber;?>"/></div>
                            <select name="assettypecode" id="assettypecode"><?php foreach ($allassettypes as $assettype){ ?><option value="<?php echo $assettype['code']; ?>"<?php if($assettype['code']=='P04'){echo ' selected';} ?>><?php echo $assettype['description']; if($assettype['description']=='User Defined'){echo ' ('.$assettype['code'].')';} ?></option><?php }?></select>
                            <select name="representation" id="representation"><option value="A">Actual Depicted</option><option value="R">Similar Depicted</option></select>

                            
                            <div style="padding:10px;"><input name="submit" type="submit" value="Create"/></div>
                        </form>
                        <?php }?>
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