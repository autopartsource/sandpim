<?php
include_once('/var/www/html/class/pimClass.php');
include_once('/var/www/html/class/configGetClass.php');
include_once('/var/www/html/class/configSetClass.php');
include_once('/var/www/html/class/logsClass.php');

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
    $pim->createPartcategory($name,$id);
    $logs->logSystemEvent('partcategorychange', $_SESSION['userid'], 'Part Category '.$name.' was created');
}

if (isset($_POST['submit']) && $_POST['submit']=='Delete') 
{
    $name=$pim->partCategoryName(intval($_POST['categoryid']));
    $pim->deletePartcategory(intval($_POST['categoryid']));
    $logs->logSystemEvent('partcategorychange', $_SESSION['userid'], 'Part Category '.$name.' was deleted');
}

$partcategories = $pim->getPartCategories();

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
        <h3>Part Categories</h3>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <table>
                    <tr><th>Name</th><th>ID</th><th>Part Count</th><th>Action</th></tr>
                    <?php
                    foreach ($partcategories as $partcategory) 
                    {
                        $count=$pim->countPartsByPartcategory($partcategory['id']);
                        echo '<tr><td>' . $partcategory['name'] . '</td><td>' . $partcategory['id'] . '</td><td>'.$count.'</td><td>';
                        if(!$count){echo '<form method="post"><input type="hidden" name="categoryid" value="'.$partcategory['id'].'"/><input type="submit" name="submit" value="Delete"/></form>';}
                        echo '</td></tr>';
                    }
                    ?>
                    <tr><form method="post"><td><input type="text" name="categoryname" size="30"/></td><td><input type="text" name="categoryid" size="50"/><input type="submit" name="submit" value="Add"/></td><td></td><td></td></form></tr>
                </table>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>