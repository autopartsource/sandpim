<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/userClass.php');
$navCategory = 'reports';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'competitorCoverageReportForm.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$interchange = new interchange;
$user=new user;

$receiverprofiles=$pim->getReceiverprofiles();
$competitivebrands=$interchange->getCompetitivebrands();
$preferedreceiverprofileid = $user->getUserPreference($_SESSION['userid'], 'last receiverprofileid used');
$preferedbrandid = $user->getUserPreference($_SESSION['userid'], 'last brandid used');
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
                        <h3 class="card-header text-start">Report our parts coverage of a competitor</h3>

                        <div class="card-body">
                            <form action="competitorCoverageReportStream.php" method="get">
                                <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f8f8f8">
                                    <div style="padding:5px;">Competitor Brand <select name="competitivebrand"><?php foreach($competitivebrands as $competitivebrand){ ?><option value="<?php echo $competitivebrand['brandAAIAID']; ?>" <?php if($competitivebrand['brandAAIAID']==$preferedbrandid){echo ' selected';} ?>><?php echo $competitivebrand['description']; ?></option><?php }?></select></div>
                                    <div style="padding:5px;">Receiver Profile <select name="receiverprofile"><option value="all">All of our parts</option><?php foreach ($receiverprofiles as $receiverprofile) { ?><option value="<?php echo $receiverprofile['id']; ?>" <?php if($receiverprofile['id']==$preferedreceiverprofileid){echo ' selected';} ?>><?php echo $receiverprofile['name']; ?></option><?php } ?></select></div>
                                    <div style="padding:15px;"><input type="submit" name="submit" value="Export"/></div>
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