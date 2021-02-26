<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'settings';


$pim = new pim;
$logs = new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'receiverProfiles.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

if (isset($_POST['submit']) && $_POST['submit']=='Add') 
{
 $pim->createReceiverprofile($_POST['profilename'],'');
 $logs->logSystemEvent('SETTINGS', $_SESSION['userid'], 'Receiver Profice '.$_POST['profilename'].' was created');
}

if (isset($_POST['submit']) && $_POST['submit']=='Delete') 
{

    
}

$profiles = $pim->getReceiverprofiles();

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
                        <h3 class="card-header text-start">Receiver Profiles</h3>
                        <div class="card-body">
                            <div class="d-grid gap-2 col-4 mx-auto">
                            <?php
                            foreach ($profiles as $profile) 
                            {
                                echo '<a class="btn btn-secondary" role="button" aria-disabled="true" href="./receiverProfile.php?id='.$profile['id'].'">' . $profile['name'].'</a>';
                            }
                            ?>
                            </div>
                            <hr>
                            <div><form method="post"><input type="text" name="profilename"/><input type="submit" name="submit" value="Add"/></form></div>
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