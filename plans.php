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
                        <h4 class="card-header text-left"><?php echo $plan['description']?></h4>
                        <div class="card-body">
                            <div class="card">
                                <h6 class="card-header text-left">Receiver Profile: <?php echo'<a href="./receiverProfile.php?id='.$receiverprofile['id'].'">'.$receiverprofile['name'].'</a></h6>';?>
                                <div class="card-body">
                                    <?php
                                        echo '<div class="form-group row">';
                                            echo '<label for="static'.$plan['planuuid'].'" class="col-sm-3 col-form-label">Plan UUID:</label>';
                                            echo '<div class="col-sm-9">';
                                                echo '<input type="text" readonly class="form-control" id="static'.$plan['planuuid'].'" value="'.$plan['planuuid'].'">';
                                            echo '</div>';
                                        echo '</div>';
                                        echo '<div class="form-group row">';
                                            echo '<label for="staticLastSync" class="col-sm-3 col-form-label">Last Sync: </label>';
                                            echo '<div class="col-sm-9">';
                                                echo '<input type="text" readonly class="form-control" id="staticLastSync" value="'.date('y-m-d').'">';
                                            echo '</div>';
                                        echo '</div>';
                                        echo '<div class="card">';
                                            echo '<h6 class="card-header">Plan Metadata</h6>';
                                            echo '<div class="card-body">';
                                                echo '<textarea name="profiledata" rows="5" style="width: 100%; max-width: 100%;">'.$plan['plannmetadata'].'</textarea>';
                                                echo '<div><button type="button" class="btn btn-outline-primary">Update</button></div>';
                                            echo '</div>';
                                        echo '</div>';
                                        
                                    ?>
                                </div>
                            </div>
                            
                            <div class="card">
                                <h6 class="card-header text-left">Slice Subscriptions (Part Categories)</h6>
                                <div class="card-body">
                                <?php
                                    
                                    
                                foreach($slices as $slice)
                                {
                                    $partcategory=$pim->getPartCategory($slice['partcategory']);
                                    $grainlist=$sandpiper->getSliceGrainList($slice['sliceid']);
                                    echo '<div class="card">';
                                        echo '<h6 class="card-header text-left">';
                                            echo '<button type="button" class="btn btn-outline-primary" type="button" data-toggle="collapse" data-target="#collapse'.$slice['id'].'" aria-expanded="false">'.$slice['description'].'</button>';
                                        echo '</h6>';
                                        echo '<div class="collapse" id="collapse'.$slice['id'].'">';
                                            echo '<div id="" class="card-body">';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['slicetype'].'" class="col-sm-3 col-form-label text-left">PIM Part Category</label>';
                                                    echo '<div class="col-sm-9 text-left">';
                                                        echo '<a class="btn btn-secondary" href="./partCategory.php?id='.$slice['partcategory'].'">'.$partcategory['name'].'</a>';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['slicetype'].'" class="col-sm-3 col-form-label text-left">Slice Type</label>';
                                                    echo '<div class="col-sm-9">';
                                                        echo '<input type="text" readonly class="form-control" id="static'.$slice['slicetype'].'" value="'.$slice['slicetype'].'">';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['subscriptionuuid'].'" class="col-sm-3 col-form-label text-left">Subscription UUID:</label>';
                                                    echo '<div class="col-sm-9">';
                                                        echo '<input type="text" readonly class="form-control" id="static'.$slice['subscriptionuuid'].'" value="'.$slice['subscriptionuuid'].'">';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['subscriptionmetadata'].'" class="col-sm-3 col-form-label text-left">Subscription Metadata:</label>';
                                                    echo '<div class="col-sm-9">';
                                                        echo '<input type="text" readonly class="form-control" id="static'.$slice['subscriptionmetadata'].'" value="'.$slice['subscriptionmetadata'].'">';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['slicehash'].'" class="col-sm-3 col-form-label text-left">Hash of grain UUIDs:</label>';
                                                    echo '<div class="col-sm-9">';
                                                        echo '<input type="text" readonly class="form-control" id="static'.$slice['slicehash'].'" value="'.$slice['slicehash'].'">';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['sliceuuid'].'" class="col-sm-3 col-form-label text-left">Slice UUID:</label>';
                                                    echo '<div class="col-sm-9">';
                                                        echo '<input type="text" readonly class="form-control" id="static'.$slice['sliceuuid'].'" value="'.$slice['sliceuuid'].'">';
                                                    echo '</div>';
                                                echo '</div>';

                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<div class="col-sm-12"><a class="btn btn-outline-secondary" href="./sliceGrains.php?sliceid='.$slice['sliceid'].'">Grain List</a></div>';
                                                echo '</div>';

                                                
                                            echo '</div>';
                                        echo '</div>';
                                    echo '</div>';
                                }
                                ?>
                                </div>
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