<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'reports';

$pim = new pim;
$logs = new logs();

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR'])){$logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'partExpiReportForm.php - access denied to host '.$_SERVER['REMOTE_ADDR']); http_response_code(404); exit;}

session_start();
if(!isset($_SESSION['userid'])) {echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}
if(!$pim->userHasNavelement($_SESSION['userid'], 'REPORTS/EXPIMATRIX')){echo 'access denied'; $logs->logSystemEvent('accesscontrol', $_SESSION['userid'], 'denied:REPORTS/EXPIMATRIX'); exit;}

$user=new user;
$receiverprofiles=$pim->getReceiverprofiles();
$preferedreceiverprofileid = $user->getUserPreference($_SESSION['userid'], 'last receiverprofileid used');
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
                        <h3 class="card-header text-start">Report EXPI coverage by part number</h3>

                        <div class="card-body">
                            <form action="partExpiReportStream.php" method="get">
                                <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f8f8f8">
                                    <div style="padding: 10px;">Receiver Profile</div>
                                    <select name="receiverprofile"><?php foreach ($receiverprofiles as $receiverprofile) { ?><option value="<?php echo $receiverprofile['id']; ?>" <?php if($receiverprofile['id']==$preferedreceiverprofileid){echo ' selected';} ?>><?php echo $receiverprofile['name']; ?></option><?php } ?></select>
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