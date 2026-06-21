<?php
include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
$navCategory = 'utilities';

$pim = new pim;
$configGet=new configGet();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importSystem.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}


session_start();
if (!isset($_SESSION['userid'])) {echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}
if(!$pim->userHasNavelement($_SESSION['userid'], 'UTILITIES/BACKUPRESTORE')){echo 'access denied'; $logs->logSystemEvent('accesscontrol', $_SESSION['userid'], 'denied:UTILITIES/BACKUPRESTORE'); exit;}



$errors=array();
$importresults=array();
        
if (isset($_POST['input'])) 
{
 $xml = simplexml_load_string($_POST['input']);
 foreach($xml->configs[0] as $config)
 {
  if(!$configGet->validConfigOption((string)$config['name']))
  {
   $errors[]=(string)$config['name'].' = '.base64_decode((string)$config['value']);
  }
 }
 
 
}?>
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
                       <h3 class="card-header text-start">System Import Results</h3>
                        <div class="card-body">
                            
                            
                        </div>
                    </div>
                    
                    <?php
                    foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}
                    foreach($importresults as $importresult){echo '<div class="alert alert-success" role="alert">'.$importresult.'</div>';}
                    ?>
                                        
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