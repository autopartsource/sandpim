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
 $logs->logSystemEvent('accesscontrol',0, 'wmFeeds.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
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
$output=false;

if(isset($_POST['apiaction']))
{
 switch ($_POST['apiaction'])
 {   
      
  case 'Request Feeds History':
   $wm->accesstoken=$session['accesstoken'];
   $wm->correlationid=$session['correlationid'];
   $feedhistory=$wm->apiGetFeedEvents();
   $output='<textarea style="width:95%;height:350px;">'.print_r($feedhistory,true).'</textarea>';
   $logs->logSystemEvent('WalmartAPI', $_SESSION['userid'], 'feeds history requested');
   break;

  case 'Post Test ACES file':
   $wm->accesstoken=$session['accesstoken'];
   $wm->correlationid=$session['correlationid'];
   if($wm->apiPostACESfile('/var/www/html/ACESuploads/ACES4BAD.zip', 'ACES4BAD.zip'))
   {
    $wm->saveFeed($wm->feedid,'ACES', '/var/www/html/ACESuploads/ACES1.zip', 'ACES1.zip', 0, '', 0, 0);
    $output='<textarea style="width:95%;height:350px;">success. feedid:'.$wm->feedid.'</textarea>';
   }
   else
   {
    $output='<textarea style="width:95%;height:350px;">error:'.$wm->errormessage.'</textarea>';
   } 
   break;

  case 'uploadACES':
   if($_FILES['fileToUpload']['type']=='application/x-zip-compressed' || $_FILES['fileToUpload']['type']=='application/zip')
   {
    $originalFilename= basename($_FILES['fileToUpload']['name']);
    $localtempfile=$_FILES['fileToUpload']['tmp_name'];
    $uploadtype='FITMENT_ACES';
    
    $wm->accesstoken=$session['accesstoken'];
    $wm->correlationid=$session['correlationid'];
    if($wm->apiPostFile($localtempfile, $originalFilename, $uploadtype))
    {
     $wm->saveFeed($wm->feedid,'FITMENT_ACES', $localtempfile, $originalFilename, 0, '', 0, 0);
     $logs->logSystemEvent('WalmartAPI', $_SESSION['userid'], 'uploaded ACES: '.$originalFilename);
     $output='<textarea style="width:95%;height:350px;">success. feedid:'.$wm->feedid.'</textarea>';
    }
    else
    {
     $logs->logSystemEvent('WalmartAPI', $_SESSION['userid'], 'failure uploading file: '.$originalFilename);
     $output='<textarea style="width:95%;height:350px;">error:'.$wm->errormessage.'</textarea>';
    } 
   }
   else
   {
    $logs->logSystemEvent('WalmartAPI', $_SESSION['userid'], 'Error uploading ACESS file - un-supported file format (must be a .zip file containing a ACES xml file) actual MIME type:'.$_FILES['fileToUpload']['type']);
    $output='<textarea style="width:95%;height:350px;">wrong type:'.$_FILES['fileToUpload']['type'].'. Must be a zip containing a ACES xml file.</textarea>';
   }
   break;
  

  case 'uploadPIES':
   if($_FILES['fileToUpload']['type']=='application/x-zip-compressed' || $_FILES['fileToUpload']['type']=='application/zip')
   {
    $originalFilename= basename($_FILES['fileToUpload']['name']);
    $localtempfile=$_FILES['fileToUpload']['tmp_name'];
    $uploadtype='FITMENT_PIES';
    
    $wm->accesstoken=$session['accesstoken'];
    $wm->correlationid=$session['correlationid'];
    if($wm->apiPostFile($localtempfile, $originalFilename, $uploadtype))
    {
     $wm->saveFeed($wm->feedid,'FITMENT_PIES', $localtempfile, $originalFilename, 0, '', 0, 0);
     $logs->logSystemEvent('WalmartAPI', $_SESSION['userid'], 'uploaded PIES: '.$originalFilename);
     $output='<textarea style="width:95%;height:350px;">success. feedid:'.$wm->feedid.'</textarea>';
    }
    else
    {
     $logs->logSystemEvent('WalmartAPI', $_SESSION['userid'], 'failure uploading file: '.$originalFilename);
     $output='<textarea style="width:95%;height:350px;">error:'.$wm->errormessage.'</textarea>';
    } 
   }
   else
   {
    $logs->logSystemEvent('WalmartAPI', $_SESSION['userid'], 'Error uploading PIES file - un-supported file format (must be a .zip file containing a PIES xml file) actual MIME type:'.$_FILES['fileToUpload']['type']);
    $output='<textarea style="width:95%;height:350px;">wrong type:'.$_FILES['fileToUpload']['type'].'. Must be a zip containing a PIES xml file.</textarea>';
   }
   break;

   
   
   
  default: break;
 }// close of switch
}

$feeds=$wm->getFeeds(10);

foreach($feeds as $feed)
{// call walmart about the status of each and update them locally   
 if($feed['state']=='NEW' || $feed['state']=='INPROGRESS')
 {
  $wm->accesstoken=$session['accesstoken'];
  $wm->correlationid=$session['correlationid'];
  $wm->feedid=$feed['feedid'];
  $feeddetails=$wm->apiGetFeedEvents();
 
  if(array_key_exists('feedStatus', $feeddetails))
  {
   $wm->updateFeedState($feed['id'], $feeddetails['feedStatus']);
  }
  
  if(array_key_exists('itemsReceived', $feeddetails) && array_key_exists('itemsSucceeded', $feeddetails) && intval($feeddetails['itemsReceived'])>0)
  {
   $wm->updateFeedProgress($feed['id'], intval($feeddetails['itemsSucceeded'])/intval($feeddetails['itemsReceived']));
  }
  
  if(array_key_exists('itemsFailed', $feeddetails))
  {
   $wm->updateFeedErrors($feed['id'], intval($feeddetails['itemsFailed']));
  }

  
 }
}

$feeds=$wm->getFeeds(10);


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

                        <h4 class="card-header text-start"><a href="./wmSessions.php">Walmart API sessions</a> > <?php echo $session['correlationid'];?></h4>

                        <div class="card-body">
                        <h4 class="card-header text-start">Existing Feeds</h4>
                        <div style="padding:10px;text-align:left;">
                            <table class="table">
                                <tr>
                                    <th>Feed</th>
                                    <th>Status</th>
                                    <th>Filename</th>
                                    <th>Created</th>
                                    <th>Errors</th>
                                    <th>Progress</th>
                                </tr>
                            
                                <?php
                                foreach($feeds as $feed)
                                {
                                    echo '<tr>';
                                    echo '<td><div style="padding-bottom:5px;"><a href="./wmFeed.php?feedid='.$feed['id'].'&sessionid='.$session['id'].'">'.substr($feed['feedid'],0,20).'</a></div>';
                                    if($feed['state']=='PROCESSED' && intval($feed['errors'])>0){echo '<form action="./wmStreamReport.php"><input type="hidden" name="sessionid" value="'.$session['id'].'"/><input type="hidden" name="feedid" value="'.$feed['id'].'"/><input type="submit" name="apiaction" value="Download Feed Report"/></form>';}
                                    echo '</td>';
                                    echo '<td>'.$feed['state'].'</td>';
                                    echo '<td>'.$feed['postfilename'].'</td>';
                                    echo '<td>'.$wm->timeAgo($feed['epochstart'],time()).'</td>';
                                    echo '<td style="text-align:right;">'.$feed['errors'].'</td>';
                                    echo '<td style="text-align:right;">'.round(100*$feed['progress'],0).'%</td>';
                                    echo '</tr>';
                                }?>
                                                
                            </table>
                        </div>
                        
                        <div class="card-body">
                            <form method="post" action="./wmFeeds.php?sessionid=<?php echo intval($_GET['sessionid']);?>" enctype="multipart/form-data">
                                <div style="text-align: left;margin:4px;padding:5px; border:1px solid #c0c0c0;">
                                    <h6>Upload a zipped ACES or PIES file to create a new feed</h6>
                                    <div style="padding-bottom: 10px;"><select name="apiaction"><option value="uploadACES">ACES</option><option value="uploadPIES">PIES</option></select></div>
                                    <input type="file" name="fileToUpload" accept=".zip"/><div style="float: right;"><input name="submit" type="submit" value="Upload"/></div><div style="clear:both;"></div>
                                </div>
                                
                            </form>
                            <form method="post" action="./wmFeeds.php?sessionid=<?php echo intval($_GET['sessionid']);?>">                                
                                <div style="text-align: left;margin:4px;padding-top:25px;"><input type="submit" name="apiaction" value="Request Feeds History"/></div>
                            </form>
                        </div>
                        
                        <?php if($output){echo $output;}?>

                        </div>
                </div>
                <!-- End of Main Content -->
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
   
                    
                </div>
            </div>
        </div>    
        </div>    
        <!-- End of Content Container -->

        <!-- Footer -->
        
    </body>
</html>