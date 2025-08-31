<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
include_once('./class/configGetClass.php');
$navCategory = 'export';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exportAssetBundle.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$user = new user;
$configGet = new configGet;
$exportsdirectory = $configGet->getConfigValue('ExportsDirectory', '');


$receiverprofiles=$pim->getReceiverprofiles();
$preferedreceiverprofileid = $user->getUserPreference($_SESSION['userid'], 'last receiverprofileid used');

if(isset($_POST['submit']) && $_POST['submit']=='Export')
{
 $receiverprofile=intval($_POST['receiverprofile']);
 $user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofile);
 
 $pim->createBackgroundjob('AssetBundle', 'started', $_SESSION['userid'], '', $exportsdirectory, 'receiverprofile:'.$receiverprofile.';verifyhashes:yes;', date('Y-m-d H:i:s'), 'application/zip', '');   
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./backgroundJobs.php'\" /></head><body></body></html>";
 exit;   
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
                        <h3 class="card-header text-start">Export zipfile of assets</h3>
                        <div class="card-body">
                            <form action="exportAssetBundle.php" method="post">
                                <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f0f0f0">
                                    <div style="padding:8px;">Receiver Profile <select name="receiverprofile"><?php foreach ($receiverprofiles as $receiverprofile) { ?><option value="<?php echo $receiverprofile['id']; ?>" <?php if($receiverprofile['id']==$preferedreceiverprofileid){echo ' selected';} ?>><?php echo $receiverprofile['name']; ?></option><?php } ?></select></div>
                                    <input type="submit" name="submit" value="Export"/>
                                </div>
                            </form>
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