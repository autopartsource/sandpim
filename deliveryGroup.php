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

if (isset($_POST['submit']) && $_POST['submit']=='Save') 
{
 if($_POST['description']!=$_POST['olddescription'])   
 {
  $pim->updateDeliverygroupDescription(intval($_POST['id']), $_POST['description']); 
  $logs->logSystemEvent('deliverygroupchange', $_SESSION['userid'], 'Delivery Group '.$_POST['id'].' description was changed from '.$_POST['olddescription'].' to '.$_POST['description']);
 }
 
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./deliveryGroups.php'\" /></head><body></body></html>";
 exit;
}

$deliverygroup = $pim->getDeliverygroup(intval($_GET['id']));

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
                        <h3 class="card-header text-left">Delivery Group</h3>

                        <div class="card-body">
                                <table>
                                    <tr><th>ID</th><td><?php echo $deliverygroup['id'];?><input type="hidden" name="id" value="<?php echo $deliverygroup['id'];?>"/></td></tr>
                                    <tr><th>Description</th><td><input type="text" name="description" value="<?php echo $deliverygroup['description'];?>"/><button>Update</button></td></tr>
                                    <tr><th>Part Categories</th><td></td></tr>
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