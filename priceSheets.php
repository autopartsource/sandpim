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
                        <h3 class="card-header text-start">Price Sheets</h3>

                        <div class="card-body">
                            <?php
                                foreach($pricesheets as $pricesheet)
                                {
                                    echo '<div class="card">';
                                        echo '<h6 class="card-header text-start">Name: '.$pricesheet['number'].'</h6>';
                                        echo '<div class="card-body">';
                                            echo '<div class="form-group row">';
                                                echo '<label for="staticDescription" class="col-sm-2 col-form-label">Description</label>';
                                                echo '<div class="col-sm-10">';
                                                    echo '<input id="staticDescription" readonly type="text" class="form-control" name="description" value="'.$pricesheet['description'].'"/>';
                                                echo '</div>';
                                            echo '</div>';
                                            echo '<div class="form-group row">';
                                                echo '<label for="staticCurrency" class="col-sm-2 col-form-label">Currency</label>';
                                                echo '<div class="col-sm-10">';
                                                    echo '<input id="staticCurrency" readonly type="text" class="form-control" name="currency" value="'.$pricesheet['currency'].'"/>';
                                                echo '</div>';
                                            echo '</div>';
                                            echo '<div class="form-group row">';
                                                echo '<label for="staticPricetype" class="col-sm-2 col-form-label">Price Type</label>';
                                                echo '<div class="col-sm-10">';
                                                    echo '<input id="staticPricetype" readonly type="text" class="form-control" name="pricetype" value="'.$pricesheet['pricetype'].'"/>';
                                                echo '</div>';
                                            echo '</div>';
                                            echo '<div class="form-group row">';
                                                echo '<label for="staticEffectivefrom" class="col-sm-2 col-form-label">Effective From</label>';
                                                echo '<div class="col-sm-10">';
                                                    echo '<input id="staticEffectivefrom" readonly type="text" class="form-control" name="effectivedate" value="'.$pricesheet['effectivedate'].'"/>';
                                                echo '</div>';
                                            echo '</div>';
                                            echo '<div class="form-group row">';
                                                echo '<label for="staticExpiration" class="col-sm-2 col-form-label">Effective To</label>';
                                                echo '<div class="col-sm-10">';
                                                    echo '<input id="staticExpiration" readonly type="text" class="form-control" name="expirationdate" value="'.$pricesheet['expirationdate'].'"/>';
                                                echo '</div>';
                                            echo '</div>';
                                        echo '</div>';
                                    echo '</div>';
                                }
                            ?>
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