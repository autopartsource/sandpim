<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/sandpiperAPIclass.php');
include_once('./class/logsClass.php');
$navCategory = 'assets';

$pim=new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'createFilegrainFromURI.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset = new asset;
$sp=new sandpiper();
$error_msg = '';



if (isset($_POST['submit']) && $_POST['submit'] == 'Create') 
{
 $data=array();
 $data['grain_uuid']=$_POST['grainuuid'];
 $data['slice_uuid']=$_POST['grainuuid'];
 $data['grain_key']=$_POST['basename'];
 $data['encoding']='raw';
 $data['payload'] = file_get_contents($_POST['uri']);
    
 if(true)
 {
  $error_msg.='filesize:'.strlen($data['payload']).'; ';      
  $grainid=$sp->addGrain($data, true, true);
  $error_msg.='grainid:'.$grainid.'; ';
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
        <h1>Create Sandpiper filegrain from URI</h1>

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
                        <form method="post">
                            <div style="padding:5px;">URI Path <input type="text" name="uri"/></div>
                            <div style="padding:5px;">Filename <input type="text" name="basename"/></div>
                            <div style="padding:5px;">Grain UUID <input type="text" name="sliceuuid" /></div>
                            <div style="padding:5px;">Grain UUID <input type="text" name="grainuuid" value="<?php echo $pim->uuidv4();?>"/></div>
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