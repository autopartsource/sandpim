<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
$navCategory = 'export';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exportCompetitorInterchangeSelect.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
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
$competitors=$interchange->getCompetitors();
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
                        <h3 class="card-header text-start">Export competitor interchange to a spreadsheet</h3>

                        <div class="card-body">
                            <form action="exportCompetitorInterchangeStream.php" method="get">
                                <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f8f8f8">
                                    <div style="padding: 10px;">Competitor</div>
                                    <select name="competitorBrandAAIAID"><?php foreach ($competitors as $competitor) { ?><option value="<?php echo $competitor['brandAAIAID']; ?>" <?php if($competitor['brandAAIAID']==$preferedbrandid){echo ' selected';} ?>><?php echo $competitor['name']; ?></option><?php } ?></select>
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