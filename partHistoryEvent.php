<?php
include_once('./includes/loginCheck.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'parts';

$pim = new pim;
$logs=new logs;


if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'partHistoryEvent.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;
$pcdb = new pcdb;
$user=new user;

$event = $logs->getPartEvent(intval($_GET['id']));
$partnumber='';
if($event)
{
 if($part=$pim->getPart($event['partnumber']))
 {
  $partnumber=$event['partnumber'];
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
                        <h3 class="card-header text-start">History Event for part <a href="./showPart.php?partnumber=<?php echo $partnumber;?>"><span class="text-info"><?php echo $partnumber?></span></a></h3>
                        <div class="card-body">
                            <?php
                            if($event)
                            {
                                echo '<table class="table">';
                                echo '<tr><th>Date/Time</th><td>'.$event['eventdatetime'].'</td></tr>';
                                echo '<tr><th>User Name</th><td>'.$user->realNameOfUserid($event['userid']).'</td></tr>';
                                echo '<tr><th>Description</th><td>'.$event['description'].'</td></tr>';
                                if($event['new_oid']!=''){echo '<tr><th>Part OID After Change</th><td>'.$event['new_oid'].'</td></tr>';}
                                echo '</table>';
                            }
                            ?>
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

