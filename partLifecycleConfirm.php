<?php
include_once('./includes/loginCheck.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');
$navCategory = 'parts';

$pim = new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'partLifecycleConfirm.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

//validate that user has access to lifecycle management
if(!$pim->userHasNavelement($_SESSION['userid'], 'PARTS/LIFECYCLE'))
{
 $logs->logSystemEvent('accesscontrol',0, 'partLifecycleConfirm.php - access denied to user '.$_SESSION['userid'].' at '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;    
}

$pcdb = new pcdb;
$configGet = new configGet;

$partnumber = $pim->sanitizePartnumber($_GET['partnumber']);

$part = $pim->getPart($partnumber);

$balance=$pim->getPartBalance($partnumber);


$action='';
if(in_array($_GET['action'], ['propose','electronic','available','supersede','discontinue','obsolete']))
{
 $action=$_GET['action'];
}


$showavailabledate=false;
$showdiscontinuedate=false;
$showobsoletedate=false;

$fromtostatus=$part['lifecyclestatus'].'-'.$action;
switch($fromtostatus)
{
 case '0-electronic': $message='You are about to change the status of this part from <strong>Proposed</strong> to <strong>Electronically Announced<strong>'; break;
 case '0-available': $message='You are about to change the status of this part from <strong>Proposed</strong> to <strong>Available to Order</strong>'; $showavailabledate=true; break;
 case '1-propose': $message='You are about to change the status of this part from <strong>Released</strong> back to <strong>Proposed</strong>. <span style="color:red;"><strong>This is not normal</strong></span>'; break;
 case '1-electronic': $message='You are about to change the status of this part from <strong>Released</strong> to <strong>Electronically Announced</strong>'; break;
 case '1-available': $message='You are about to change the status of this part from <strong>Released</strong> to <strong>Available to Order</strong>'; $showavailabledate=true; break;
 case '2-propose': $message='You are about to change the status of this part from <strong>Available to Order</strong> back to <strong>Proposed</strong>. <span style="color:red;"><strong>This is not normal</strong></span>'; break;
 case '2-supersede': $message='You are about to change the status of this part from <strong>Available to Order</strong> to <strong>Superseded</strong>'; break;
 case '2-discontinue': $message='You are about to change the status of this part from <strong>Available to Order</strong> to <strong>Discontinued</strong>'; $showdiscontinuedate=true; break;
 case '3-propose': $message='You are about to change the status of this part from <strong>Electronically Announced</strong> back to <strong>Proposed</strong>. <span style="color:red;"><strong>This is not normal</strong></span>'; break;
 case '3-available': $message='You are about to change the status of this part from <strong>Electronically Announced</strong> to <strong>Available to Order</strong>'; $showavailabledate=true; break;
 case '4-propose': $message='You are about to change the status of this part from <strong>Announced</strong> back to <strong>Proposed</strong>. <span style="color:red;"><strong>This is not normal</strong></span>'; break;
 case '4-electronic': $message='You are about to change the status of this part from <strong>Announced</strong> to <strong>Electronically Announced</strong>'; break;
 case '4-available': $message='You are about to change the status of this part from <strong>Announced</strong> to <strong>Available to Order</strong>'; $showavailabledate=true; break;
 case '5-available': $message='You are about to change the status of this part from <strong>Temporarily Unavailable</strong> to <strong>Available to Order</strong>'; $showavailabledate=true; break;
 case '6-supersede': $message='You are about to change the status of this part from <strong>Re-Numbered</strong> to <strong>Superseded</strong>'; break;
 case '6-discontinue': $message='You are about to change the status of this part from <strong>Re-Numbered</strong> to <strong>Superseded</strong>'; $showdiscontinuedate=true; break;
 case '7-available': $message='You are about to change the status of this part from <strong>Superseded</strong> back to <strong>Available to Order</strong>. <span style="color:red;"><strong>This is not normal</strong></span>'; $showavailabledate=true; break;
 case '7-discontinue': $message='You are about to change the status of this part from <strong>Superseded</strong> to <strong>Discontinued</strong>'; $showdiscontinuedate=true; break;
 case '8-available': $message='You are about to change the status of this part from <strong>Discontinued</strong> back to <strong>Available to Order</strong>. <span style="color:red;"><strong>This is not normal</strong></span>'; $showavailabledate=true; break;
 case '8-obsolete': $message='You are about to change the status of this part from <strong>Discontinued</strong> to <strong>Obsolete</strong>'; $showobsoletedate=true; break;
 case '9-discontinue': $message='You are about to change the status of this part from <strong>Obsolete</strong> back to <strong>Discontinued</strong>. <span style="color:red;"><strong>This is not normal</strong></span>'; $showdiscontinuedate=true; break;
 case 'A-available': $message='You are about to change the status of this part from <strong>Available only while supplies last</strong> to <strong>Available to Order</strong>'; $showavailabledate=true; break;
 case 'A-supersede': $message='You are about to change the status of this part from <strong>Available only while supplies last</strong> to <strong>Superseded</strong>'; break;
 case 'A-discontinue': $message='You are about to change the status of this part from <strong>Available only while supplies last</strong> to <strong>Discontinued</strong>'; $showdiscontinuedate=true; break;
 case 'A-obsolete': $message='You are about to change the status of this part from <strong>Available only while supplies last</strong> to <strong>Obsolete</strong>'; $showobsoletedate=true; break;
 case 'B-propose': $message='You are about to change the status of this part from <strong>Not yet available</strong> to <strong>Proposed</strong>'; break;
 case 'B-available': $message='You are about to change the status of this part from <strong>Not yet available</strong> to <strong>Available to Order</strong>'; $showavailabledate=true; break;
 default: $message='unknown from-to'; break;
}


?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        
        <script>
        </script>
        
    </head>
    <body onload="setStatusColor()">
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-1 my-col colLeft">
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-10 my-col colMain">
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start">
                            <div>Part Number  <a href="./showPart.php?partnumber=<?php echo $partnumber?>"><span class="text-info"><?php echo $partnumber?></span></a>    </div>
                        </h3>
                        <div class="alert alert-danger" role="alert" id="heading-alert" style="display:none;">This is a danger alert—check it out!</div>
                        <div class="card-body">
                            <?php if($part){

                                echo $message;
                                
                            } ?>
                            
                            <div style="padding:10px;">
                                <?php if($showavailabledate){ ?>                
                                Available Date <input style="text-align: center;" type="text" size="8" name="availabledate" value="<?php echo date('Y-m-d');?>"/>
                                <?php }?>
                                
                                <?php if($showdiscontinuedate){ ?>                
                                Discontinued Date <input style="text-align: center;" type="text" size="8" name="discontinuedate" value="<?php echo date('Y-m-d');?>"/>
                                <?php }?>
                                
                                <?php if($showobsoletedate){ ?>                
                                Obsoleted Date <input style="text-align: center;" type="text" size="8" name="obsoletedate" value="<?php echo date('Y-m-d');?>"/>
                                <?php }?>
                                
                            </div>
                        </div>
                    </div>                    
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-1 my-col colRight">
                                        
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>