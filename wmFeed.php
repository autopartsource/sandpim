<?php

include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/walmartClass.php');
include_once('./class/logsClass.php');

// login check is intentionally left out so that this page can stand alone as an un-authenticaeted utility
$navCategory = 'utilities';

$pim=new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'wmFeed.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}





$configGet = new configGet();
$WMclientid=$configGet->getConfigValue('WMclientid');
$WMsecret=$configGet->getConfigValue('WMsecret');
$WMconsumerid=$configGet->getConfigValue('WMconsumerid');
$WMconsumerchanneltype=$configGet->getConfigValue('WMconsumerchanneltype');
$wm=new walmart($WMclientid, $WMsecret, $WMconsumerid, $WMconsumerchanneltype);

$session=$wm->getSession(intval($_GET['sessionid']));
$localfeed=$wm->getFeed(intval($_GET['feedid']));

$wm->accesstoken=$session['accesstoken'];
$wm->correlationid=$session['correlationid'];
$wm->feedid=$localfeed['feedid'];

$errors=array();


if(isset($_POST['apiaction']))
{
 switch ($_POST['apiaction'])
 {   
  default: break;
 }// close of switch
}

$feeddetails=$wm->apiGetFeedEvents();
$output='<textarea rows="20">'.print_r($feeddetails,true).'</textarea>';

?>
<!DOCTYPE html>
<html lang="en" xml:lang="en">
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
                        <h5 class="card-header text-start"><a href="./wmSessions.php">Walmart API Sessions</a> > <a href="./wmFeeds.php?sessionid=<?php echo intval($_GET['sessionid']);?>">Feeds</a> > <?php echo $localfeed['feedid'];?></h5>
                        <?php if($output){echo $output;}?>
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
        
    </body>
</html>