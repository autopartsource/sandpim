<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pricingClass.php');


$navCategory = 'settings';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$pricing = new pricing;
$logs = new logs;


if (isset($_POST['submit']) && $_POST['submit']=='Add' && isset($_POST['categoryname']) && trim($_POST['categoryname'])!='') 
{
    $name = $_POST['pricesheetdescription'];
    $logs->logSystemEvent('pricesheet', $_SESSION['userid'], 'Pricesheet '.$name.' was created');
}

if (isset($_POST['submit']) && $_POST['submit']=='Delete') 
{
 $name=$pim->partCategoryName(intval($_POST['categoryid']));
 $pim->deletePartcategory(intval($_POST['categoryid']));
 $logs->logSystemEvent('partcategorychange', $_SESSION['userid'], 'Part Category '.$name.' was deleted');
}

$pricesheets=$pricing->getPricesheets();
        
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
        <h3>Price Sheets</h3>

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <table>
                        <tr><th>Name</th><th>Description</th><th>Currency</th><th>Price Type</th><th>Effective From</th><th>Effective To</th></tr>
                        <?php
                        foreach($pricesheets as $pricesheet)
                        {
                            echo '<tr><td>'.$pricesheet['number'].'</td><td>'.$pricesheet['description'].'</td><td>'.$pricesheet['currency'].'</td><td>'.$pricesheet['pricetype'].'</td><td>'.$pricesheet['effectivedate'].'</td><td>'.$pricesheet['expirationdate'].'</td></tr>';
                        }
                        ?>
                    </table>
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