<?php
include_once('/var/www/html/class/userClass.php');
include_once('/var/www/html/class/configGetClass.php');
include_once('/var/www/html/class/configSetClass.php');

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$user = new user;
$configGet = new configGet;
$configSet = new configSet;


if (isset($_POST['submit'])) {
    $configname = $_POST['configname'];
    $configvalue = $_POST['configvalue'];
    $configSet->setConfigValue($configname, $configvalue);
}


$configs = $configGet->getAllConfigValues();
?>

<!DOCTYPE html>
<html>
    <head>
        <?php include('/var/www/html/includes/header.php'); ?>
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
                        <tr><td><input type="text" name="configname" size="30"/></td><td><input type="text" name="configvalue" size="50"/><input type="submit" name="submit" value="Add"/></td></tr>
                    </table>
                </form>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>