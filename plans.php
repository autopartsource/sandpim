<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/sandpiperClass.php');


$navCategory = 'import/export';

$pim=new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'sandpiper.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$sandpiper=new sandpiper;


$plans=$sandpiper->getPlans();


?>
<!DOCTYPE html>
<html lang="en" xml:lang="en">
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>Sandpiper Plans</h1>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                <?php
                foreach($plans as $plan)
                {
                    $receiverprofile=$pim->getReceiverprofileById($plan['receiverprofileid']);
                    $slices=$sandpiper->getPlanSlices($plan['id']);
                    ?>
                    
                    
                    
                   <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-left"><?php echo $plan['description']?></h3>
                        <div class="card-body">
<?php
                    echo '<div>Receiver Profile: <a href="./receiverProfile.php?id='.$receiverprofile['id'].'">'.$receiverprofile['name'].'</a></div>';
                    echo '<div>Plan UUID: '.$plan['planuuid'].'</div>';
                    echo '<div>Metadata: '.$plan['plannmetadata'].'</div>';
?>
                        <h3 class="card-header text-left">Slice Subscriptions (Part Categories)</h3>
                        <div class="card-body">
                        <?php
                        foreach($slices as $slice)
                        {
                            $partcategory=$pim->getPartCategory($slice['partcategory']);
                            echo '<div class="card-header text-left">';
                            echo '<div><a href="./partCategory.php?id='.$slice['id'].'">'.$slice['description'].'</a></div>';
                            echo '<div style="padding-left:10px;">Slice Type: '.$slice['slicetype'].'</div>';
                            echo '<div style="padding-left:10px;">Hash of grain UUIDs: '.$slice['slicehash'].'</div>';
                            echo '<div style="padding-left:10px;">Subscription UUID: '.$slice['subscriptionuuid'].'</div>';
                            echo '<div style="padding-left:10px;">Slice UUID: '.$slice['sliceuuid'].'</div>';
                            echo '<div style="padding-left:10px;">Subscription Metadata: '.$slice['subscriptionmetadata'].'</div>';
                            echo '</div>';
                        }
                        ?>
                        </div>



                        </div>
                                              
                    </div>
                     
                <?php }?>
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