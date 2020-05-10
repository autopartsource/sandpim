<?php
include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsClass.php');

$navCategory = 'settings';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$configGet = new configGet;
$configSet = new configSet;
$logs = new logs;


if (isset($_POST['submit']) && $_POST['submit']=='Add' && isset($_POST['categoryname']) && trim($_POST['categoryname'])!='') 
{
    $name = $_POST['categoryname'];
    $id = $_POST['categoryid'];
    $pim->createAppCategory($name,$id);
    $logs->logSystemEvent('appcategorychange', $_SESSION['userid'], 'App Category '.$name.' was created');
}

if (isset($_POST['submit']) && $_POST['submit']=='Delete') 
{
    $name=$pim->appCategoryName(intval($_POST['categoryid']));
    $pim->deleteAppcategory(intval($_POST['categoryid']));
    $logs->logSystemEvent('appcategorychange', $_SESSION['userid'], 'App Category '.$name.' was deleteted');
}

$appcategories = $pim->getAppCategories();

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
        <h3>Application Categories</h3>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <table>
                    <tr><th>Name</th><th>ID</th><th>App Count</th><th>Logo URI</th><th>Action</th></tr>
                    <?php
                    foreach ($appcategories as $appcategory)
                    {
                        $count=$pim->countAppsByAppcategory($appcategory['id']);
                        echo '<tr><td>' . $appcategory['name'] . '</td><td>' . $appcategory['id'] . '</td><td>'.$count.'</td><td><form method="update"><input type="text" name="logouri" value="'.$appcategory['logouri'].'"/><input type="submit" name="update" value="Update"></form></td><td>';
                        if(!$count){echo '<form method="post"><input type="hidden" name="categoryid" value="'.$appcategory['id'].'"/><input type="submit" name="submit" value="Delete"/></form>';}
                        echo '</td></tr>';
                    }
                    ?>
                    <tr>
                    <form method="post">
                        <td><input type="text" name="categoryname" size="30"/></td>
                        <td><input type="text" name="categoryid" size="50"/></td>
                        <td></td>
                        <td></td>
                        <td><input type="submit" name="submit" value="Add"/></td>
                    </form>
                </tr>
                
                </table>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('./includes/footer.php'); ?>
    </body>
</html>