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

if (isset($_POST['submit']) && $_POST['submit']=='Add' && isset($_POST['deliverygroupname']) && trim($_POST['deliverygroupname'])!='') 
{
    $name = $_POST['deliverygroupname'];
    $id=$pim->createDeliverygroup($name);
    $logs->logSystemEvent('deliverygroup', $_SESSION['userid'], 'Deliverygroup '.$id.' ('.$name.') was created');
}

$deliverygroups = $pim->getDeliverygroups();

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
                        <h3 class="card-header text-left">Delivery Groups</h3>

                        <div class="card-body">
                            
                                <?php 
                                foreach ($deliverygroups as $deliverygroup) 
                                {
                                    echo '<div style="text-align:left;background:#d0d0d0;margin:2px;padding:5px;"><a href="./deliveryGroup.php?id='.$deliverygroup['id'].'">' . $deliverygroup['description'].'</a></div>';
                                }
                                ?>
                                <div><form method="post"><input type="text" name="deliverygroupname" size="30"/><input type="submit" name="submit" value="Add"/></form></div>
                            
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