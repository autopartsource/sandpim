<?php
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');
$navCategory = 'assets';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'assetCreate.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) 
{
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$asset = new asset;
$pcdb=new pcdb();
$error_msg = false;

$valid_upload=false;
$partnumber='';

if (isset($_POST['submit']) && $_POST['submit'] == 'Create') 
{
    $assetid=trim($_POST['assetid']);
    if(!$asset->validAsset($assetid))
    {
        $filename=trim($_POST['filename']);
        $localpath=trim($_POST['localpath']);
        $uri=trim($_POST['uri']);
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
        $description=trim($_POST['description']);
        $oid = $pim->newoid();
        $filehash=$_POST['filehash'];
        $filesize=intval($_POST['filesize']);
        $uripublic=0; if(isset($_POST['uripublic']) && $_POST['uripublic']=='on'){$uripublic=1;}
        $languagecode=''; if(isset($_POST['languagecode'])){$languagecode=$_POST['languagecode'];}
        $assetlabel=trim($_POST['assetlabel']); // internal label like "Assembly Guide" or "QC Drawing"    
        $createddate='2000-01-01';
        if($id = $asset->addAsset($assetid, $filename, $localpath, $uri, $orientationviewcode, $colormodecode, $assetheight, $assetwidth, $dimensionUOM,$resolution, $background, $filetype, $public, $approved, $description, $oid, $filehash,$filesize,$uripublic,$languagecode,$assetlabel,$createddate,1,1,1,1))
        {
            $error_msg = 'Asset record ' . $id . ' was created for asset ID '.$assetid;
            $assetoid=$asset->updateAssetOID($assetid);
            $asset->logAssetEvent($assetid, $_SESSION['userid'], 'Asset created' ,$assetoid);

            if(isset($_POST['partnumber']) && $pim->validPart($_POST['partnumber']))
            {
                $partnumber=trim(strtoupper($_POST['partnumber']));
                $partoid=$pim->updatePartOID($partnumber);
                $connectionid=$asset->connectPartToAsset($partnumber,$assetid,$_POST['assettypecode'],1,$_POST['representation']);
                $pim->logPartEvent($partnumber,$_SESSION['userid'], 'asset ['.$assetid.'] was connected' ,$partoid);
                $asset->logAssetEvent($assetid, $_SESSION['userid'], 'part ['.$partnumber.'] was connected', $assetoid);
            }
            // request audit from backend
            $pim->addAuditRequest('asset-general', $assetid);
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
    else
    {// assetid already exists - avoid creating a new record by the same id (even though it would be technically ok, we want to avoid the confusion)
    
        $error_msg = 'Asset ID ['.$assetid.'] already exists - no action taken';
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
                switch($exiftype)
                {
                    case 1:  $filetype ='GIF'; break;
                    case 2:  $filetype ='JPG'; break;
                    case 3:  $filetype ='PNG'; break;
                    case 6:  $filetype ='BMP'; break;
                    case 7:  $filetype ='TIF'; break;
                    case 8:  $filetype ='TIF'; break;
                    default : $filetype =''; break;
                }
                
                $valid_upload=true;
                $imagedims = getimagesize($destinationpath);
                $colormodecode = 'RGB';
                $assetheight = $imagedims[1];
                $assetwidth = $imagedims[0];
                $dimensionUOM = 'PX';
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
                $localpath=$pathparts['basename'];
                $orientationviewcode='FRO';
                
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
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Create image asset</h3>

                        <div class="card-body">
                            <h4>
                                <?php if ($error_msg) {
                                    echo $error_msg;
                                } ?>
                            </h4>
                            
                            <?php if($partnumber!=''){?>
                            <div><a href="./showPart.php?partnumber=<?php echo $partnumber;?>">Back to <?php echo $partnumber;?></a></div>
                            <?php }?>
                            
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
                                <div style="padding:10px;">Width: <?php echo $imagedims[0];?></div>
                                <div style="padding:10px;">Height: <?php echo $imagedims[1];?></div>
                                <div style="padding:10px;">AssetID: <input type="text" name="assetid" value="<?php echo $filename;?>"/></div>
                                <div style="padding:10px;">Description <input name="description" type="text" value="<?php echo $description;?>"/></div>
                                <div style="padding:10px;">Orientation 
                                <select name="orientationviewcode"><?php foreach ($orientationviewcodes as $orientationviewcode) { ?> <option value="<?php echo $orientationviewcode['code']; ?>"><?php echo $orientationviewcode['description']; ?></option><?php } ?></select>
                                </div>
                                <div style="padding:10px;">Resolution (DPI) <input name="resolution" type="text" value="<?php echo $resolution;?>"/></div>
                                <div style="padding:10px;">Background <input name="background" type="text" value="<?php echo $background;?>"/></div>
                                <div style="padding:10px;">Public <input name="public" type="text" value="<?php echo $public;?>"/></div>
                                <div style="padding:10px;">URI <input name="uri" type="text" value="<?php echo $uri;?>"/></div>
                                <div style="padding:10px;"><label><input type="checkbox" id="uripublic" name="uripublic"/>URI is for public consumption</label></div>
                                <div style="padding:10px;"><input name="submit" type="submit" value="Create"/></div>
                            </form>
                            <?php }?>
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