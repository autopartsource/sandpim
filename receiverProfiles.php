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

        <!-- Header -->
        <h3>Receiver Profiles</h3>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
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

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('./includes/footer.php'); ?>
    </body>
</html>