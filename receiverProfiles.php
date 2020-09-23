<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'settings';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$logs = new logs;

if (isset($_POST['submit']) && $_POST['submit']=='Add') 
{
    
 $pim->createReceiverprofile($_POST['profilename'],$_POST['profiledata']);
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
                        <h3 class="card-header text-left">Receiver Profiles</h3>

                        <div class="card-body">
                            <table>
                                <tr><th>Name</th><th>Data</th></tr>
                                <?php
                                foreach ($profiles as $profile) 
                                {
                                    echo '<tr><td><a href="./receiverProfile.php?id='.$profile['id'].'">'.$profile['name'].'</a></td>';
                                    echo '<td><div>'.$profile['data'].'</div></td>';
                                    echo '</tr>';
                                }
                                ?>
                                <tr><form method="post"><td><input type="text" name="profilename" /></td><td><textarea name="profiledata" style="width:95%;"/></textarea><div><input type="submit" name="submit" value="Add"/></div></td></form></tr>
                            </table>
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