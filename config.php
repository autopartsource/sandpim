<?php
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsClass.php');

$navCategory='settings';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$user = new user;
$configGet = new configGet;
$configSet = new configSet;
$logs = new logs;


if (isset($_POST['submit']))
{
 $userid=$_SESSION['userid'];
 $configname = $_POST['configname'];
 $oldvalue=$configGet->getConfigValue($configname);
 $configvalue = $_POST['configvalue'];
 $configSet->setConfigValue($configname, $configvalue);
 $logs->logSystemEvent('config', $userid, $configname.' changed from:'.$oldvalue.' to:'.$configvalue);
}

$configs = $configGet->getAllConfigValues();
$configoptions=$configGet->getConfigOptions();

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
        <h3>Configuration Parameters</h3>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <form method="post">
                    <table>
                        <tr><th>Parameter</th><th>Value</th></tr>
                        <?php
                        foreach ($configs as $config) {
                            echo '<tr><td>' . $config['configname'] . '</td><td>' . $config['configvalue'] . '</td></tr>';
                        }
                        ?>
                        <tr><td><select name="configname"><?php foreach($configoptions as $configoption){echo '<option value="'.$configoption['configname'].'">'.$configoption['configname'].'</option>';}?></select></td><td><input type="text" name="configvalue" size="50"/><input type="submit" name="submit" value="Add/Update"/></td></tr>
                    </table>
                </form>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('./includes/footer.php'); ?>
    </body>
</html>