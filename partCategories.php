<?php
include_once('/var/www/html/class/pimClass.php');
include_once('/var/www/html/class/configGetClass.php');
include_once('/var/www/html/class/configSetClass.php');

$navCategory = 'settings';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$configGet = new configGet;
$configSet = new configSet;


if (isset($_POST['submit']) && isset($_POST['categoryname']) && trim($_POST['categoryname'])!='') 
{
    $name = $_POST['categoryname'];
    $id = $_POST['categoryid'];
    $pim->createPartcategory($name,$id);
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
                <form method="post">
                    <table>
                        <tr><th>Name</th><th>ID</th></tr>
                        <?php
                        foreach ($partcategories as $partcategory) {
                            echo '<tr><td>' . $partcategory['name'] . '</td><td>' . $partcategory['id'] . '</td></tr>';
                        }
                        ?>
                        <tr><td><input type="text" name="categoryname" size="30"/></td><td><input type="text" name="categoryid" size="50"/><input type="submit" name="submit" value="Add"/></td></tr>
                    </table>
                </form>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>