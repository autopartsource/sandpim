<?php
include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsClass.php');

$navCategory = 'settings';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'sandpiper index.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$configGet = new configGet;
$configSet = new configSet;
$logs = new logs;


if (isset($_POST['submit']) && $_POST['submit']=='Add' && isset($_POST['categoryname']) && trim($_POST['categoryname'])!='') 
{
    $name = $_POST['categoryname'];
    $pim->createPartcategory($name,'');
    $logs->logSystemEvent('partcategorychange', $_SESSION['userid'], 'Partcategory ('.$name.') was created');
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
                        <h3 class="card-header text-start">Part Categories</h3>

                        <div class="card-body">
                            <?php
                            echo '<table class="table">';
                            echo '<thead><tr><th scope="col">Category</th><th scope="col">Part Count</th><th scope="col"></th></tr></thead>';
                            
                            foreach ($partcategories as $partcategory) 
                            {
                                $count=$pim->countPartsByPartcategory($partcategory['id']);
                                echo '<tr>';
                                    echo '<td><a href="./partCategory.php?id='.$partcategory['id'].'">' . $partcategory['name'] . '</a></td>';
                                    echo '<td><strong><a href="./partsIndex.php?searchtype=startswith&partnumber=&partcategory='.$partcategory['id'].'&parttypeid=any&lifecyclestatus=any&limit='.$count.'&submit=Search">'.$count.'</a><strong></td>';
                                    echo '<td>';
                                        if(!$count){
                                            echo '<form method="post"><input type="hidden" name="categoryid" value="'.$partcategory['id'].'"/><input type="submit" name="submit" value="Delete"/></form>';
                                        }
                                    echo '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</table>';
                            ?>
                            <?php
//                            foreach ($partcategories as $partcategory) 
//                           {
//                                $count=$pim->countPartsByPartcategory($partcategory['id']);
//                                echo '<div class="card">';
//                                    echo '<h6 class="card-header text-start"><a href="./partCategory.php?id='.$partcategory['id'].'">' . $partcategory['name'] . '</a>';
//                                    if(!$count){echo '<div style="float:right;"><form method="post"><input type="hidden" name="categoryid" value="'.$partcategory['id'].'"/><input type="submit" name="submit" value="Delete"/></form></div>';}
//                                    echo '</h6>';
//                                    echo '<div class="card-body text-start">';
//                                        echo 'Part Count: <span style="font-weight: bold;">'.$count.'<span>';
//                                    echo '</div>';
//                                echo '</div>';
//                            }
                            ?>
                            <hr>
                            <div style="padding:10px;">
                                <form method="post">
                                    <input type="text" name="categoryname"/>
                                    <input type="submit" name="submit" value="Add"/>
                                </form>
                            </div>
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