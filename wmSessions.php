<?php

include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/walmartClass.php');
include_once('./class/logsClass.php');

// login check is intentionally left out so that this page can stand alone as an un-authenticaeted utility
$navCategory = 'utilities';

$pim=new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'wmSessions.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}


$errors=array();

$configGet = new configGet();

$WMclientid=$configGet->getConfigValue('WMclientid');
$WMsecret=$configGet->getConfigValue('WMsecret');
$WMconsumerid=$configGet->getConfigValue('WMconsumerid');
$WMconsumerchanneltype=$configGet->getConfigValue('WMconsumerchanneltype');

$wm=new walmart($WMclientid, $WMsecret, $WMconsumerid, $WMconsumerchanneltype);

if($WMclientid && $WMsecret && $WMconsumerid && $WMconsumerchanneltype)
{
 // config values exist - now look at action
 if(isset($_GET['apiaction']))
 {
  switch ($_GET['apiaction'])
  {   
      
   case 'Start New Session':
    if($wm->apiGetAccessToken())
    {
     $wm->saveSession('NEW', $wm->accesstoken, $wm->correlationid, time(), '');
     $logs->logSystemEvent('WalmartAPI', $_SESSION['userid'], 'new access token:'.$wm->accesstoken.' correlationid:'.$wm->correlationid);
    }
    else
    {
     $logs->logSystemEvent('WalmartAPI', $_SESSION['userid'], 'failed to get new access token. Error: '.$wm->errormessage);
     $errors[]='error:'.$wm->errormessage;
    }
    break;
    
   default:
    break;

  }// close of switch
 }
}
else
{
 $logs->logSystemEvent('WalmartAPI', $_SESSION['userid'], 'Declined to request access token. config value(s) missing from config. Must have WMclientid, WMsecret, WMconsumerid and WMconsumerchanneltype');
 $errors[]='config value(s) missing from config. Must have WMclientid, WMsecret, WMconsumerid and WMconsumerchanneltype';    
}

$sessions=$wm->getRecentSessions(10);

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
                        <?php
                        foreach($errors as $error)
                        {
                            echo '<div style="background-color:#c0a0a0;padding:10px;border:solid 1px;">'.$error.'</div>';
                        }?>

                        <h2 class="card-header text-start">Walmart Uploader Utility (ACES)</h2>
                        <div class="card-body">
                            <h3 class="card-header text-start">Existing Sessions</h3>
                            <div style="text-align:left;padding:10px;">
                                <?php foreach($sessions as $session)
                                {
                                    if((time()-$session['startepoch']<830))
                                    {
                                        echo '<div style="padding:5px;"><a href="./wmFeeds.php?sessionid='.$session['id'].'">'.$session['correlationid'].'</a></div>';
                                    }
                                }?>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <form method="get">
                                <input type="submit" name="apiaction" value="Start New Session"/>
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
        
    </body>
</html>