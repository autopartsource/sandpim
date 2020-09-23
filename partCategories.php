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
                        <h3 class="card-header text-left">Part Categories</h3>

                        <div class="card-body">
                            <table>
                                <tr><th>Name</th><th>ID</th><th>Part Count</th><th>Action</th></tr>
                                <?php
                                foreach ($partcategories as $partcategory) 
                                {
                                    $count=$pim->countPartsByPartcategory($partcategory['id']);
                                    echo '<tr><td><a href="./partCategory.php?id='.$partcategory['id'].'">' . $partcategory['name'] . '</a></td><td>' . $partcategory['id'] . '</td><td>'.$count.'</td><td>';
                                    if(!$count){echo '<form method="post"><input type="hidden" name="categoryid" value="'.$partcategory['id'].'"/><input type="submit" name="submit" value="Delete"/></form>';}
                                    echo '</td></tr>';
                                }
                                ?>
                                <tr><form method="post"><td><input type="text" name="categoryname" size="30"/></td><td><input type="text" name="categoryid" size="50"/><input type="submit" name="submit" value="Add"/></td><td></td><td></td></form></tr>
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