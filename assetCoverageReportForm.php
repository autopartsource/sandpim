<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/assetClass.php');
include_once('./class/userClass.php');
$navCategory = 'reports';

$pim = new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'assetCoverageReportForm.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset = new asset;
$user=new user();
$receiverprofiles=$pim->getReceiverprofiles();
$preferedreceiverprofileid = $user->getUserPreference($_SESSION['userid'], 'last receiverprofileid used');
$labels=$asset->getAssetlabels();

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
                        <h3 class="card-header text-start">Report Asset Coverage by part number</h3>

                        <div class="card-body">
                            <form action="assetCoverageReportStream.php" method="get">
                                <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f8f8f8">
                                    <div style="padding: 10px;">Receiver Profile
                                    <select name="receiverprofile"><?php foreach ($receiverprofiles as $receiverprofile) { ?><option value="<?php echo $receiverprofile['id']; ?>" <?php if($receiverprofile['id']==$preferedreceiverprofileid){echo ' selected';} ?>><?php echo $receiverprofile['name']; ?></option><?php } ?></select>
                                    </div>
                                    <div style="padding:5px;">
                                        Include assets with label <select name="assetlabel"><option value="any">- any -</option><option value="">- blank -</option><?php foreach ($labels as $label) { ?> <option value="<?php echo $label['labeltext']; ?>"><?php echo $label['labeltext']; ?></option><?php } ?></select>
                                    </div>
                                    <div style="padding:5px;">
                                        Data element to export <select name="format"><option value="assetid">AssetID</option><option value="uri">URI</option><option value="filename">Filename</option></select>
                                    </div>
                                    <div style="padding:5px;">
                                        <input type="submit" name="submit" value="Export"/>
                                    </div>
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