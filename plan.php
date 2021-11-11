<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/sandpiperPrimaryClass.php');


$navCategory = 'settings';

$pim=new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'sandpiper.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$sandpiperPrimary=new sandpiperPrimary;

$planid=intval($_GET['id']);

if(isset($_POST['submit']) && $_POST['submit']=='Add')
{
   
    
}


$plan=$sandpiperPrimary->getPlanById($planid);
$receiverprofile=$pim->getReceiverprofileById($plan['receiverprofileid']);
$slices=$sandpiperPrimary->getPlanSlices($planid);
$partcategories=$pim->getPartCategories();
        
//        print_r($plan);

?>
<!DOCTYPE html>
<html lang="en" xml:lang="en">
    <head>
        <?php include('./includes/header.php'); ?>
        
        <script>
            function updatePlanMetadata()
            {
             document.getElementById("updatingMetadataIndicator").innerHTML='<img src="./loading.gif" width="30"/>';
             var planmetadata=document.getElementById("planmetadata").value;
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdatePlan.php?planid=<?php echo $planid;?>&elementid=planmetadata&value='+btoa(planmetadata));
             xhr.onload = function()
             {
              var response=xhr.responseText;
              document.getElementById("updatingMetadataIndicator").innerHTML='';
             };
             xhr.send();
            }

            function updateStatusOn()
            {
             document.getElementById("staticStatusOn").value='';
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdatePlan.php?planid=<?php echo $planid;?>&elementid=planstatuson&value=1');
             xhr.onload = function()
             {
              var response=xhr.responseText;
              document.getElementById("staticStatusOn").value=response;
             };
             xhr.send();
            }

            function updatePrimaryApprovedOn()
            {
             document.getElementById("staticPrimaryApprovedOn").value='';
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdatePlan.php?planid=<?php echo $planid;?>&elementid=primaryapprovedon&value=1');
             xhr.onload = function()
             {
              var response=xhr.responseText;
              document.getElementById("staticPrimaryApprovedOn").value=response;
             };
             xhr.send();
            }
            
            function updateSecondaryApprovedOn()
            {
             document.getElementById("staticSecondaryApprovedOn").value='';
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdatePlan.php?planid=<?php echo $planid;?>&elementid=secondaryapprovedon&value=1');
             xhr.onload = function()
             {
              var response=xhr.responseText;
              document.getElementById("staticSecondaryApprovedOn").value=response;
             };
             xhr.send();
            }

        </Script>
        
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
                        <h3 class="card-header"><a href="./sandpiper.php">Plans</a> >> <?php echo $plan['description']?></h3>
                        <div class="card-body">
                            <div class="card">
                                <h6 class="card-header text-start">Receiver Profile: <?php echo'<a href="./receiverProfile.php?id='.$receiverprofile['id'].'">'.$receiverprofile['name'].'</a></h6>';?>
                                <div class="card-body">
                                    <?php
                                        echo '<div class="form-group row">';
                                            echo '<label for="static'.$plan['planuuid'].'" class="col-sm-3 col-form-label">Plan UUID:</label>';
                                            echo '<div class="col-sm-9">';
                                                echo '<input type="text" readonly class="form-control" id="static'.$plan['planuuid'].'" value="'.$plan['planuuid'].'">';
                                            echo '</div>';
                                        echo '</div>';
                                        
                                        
                                        
                                        echo '<div class="form-group row">';
                                            echo '<label for="staticLastSync" class="col-sm-3 col-form-label"">Last Sync: </label>';
                                            echo '<div class="col-sm-9">';
                                                echo '<input type="text" readonly class="form-control" id="staticLastSync" value="'.date('y-m-d').'">';
                                            echo '</div>';
                                        echo '</div>';
                                        
                                        echo '<div class="form-group row">';
                                            echo '<label for="staticStatusOn" class="col-sm-3 col-form-label"  onclick="updateStatusOn();">Status Verified on: </label>';
                                            echo '<div class="col-sm-9">';
                                                echo '<input type="text" readonly class="form-control" id="staticStatusOn" value="'.$plan['planstatuson'].'">';
                                            echo '</div>';
                                        echo '</div>';
                                        
                                        echo '<div class="form-group row">';
                                            echo '<label for="staticPrimaryApprovedOn" class="col-sm-3 col-form-label" onclick="updatePrimaryApprovedOn();">Primary Approved on: </label>';
                                            echo '<div class="col-sm-9">';
                                                echo '<input type="text" readonly class="form-control" id="staticPrimaryApprovedOn" value="'.$plan['primaryapprovedon'].'">';
                                            echo '</div>';
                                        echo '</div>';
                                        
                                        echo '<div class="form-group row">';
                                            echo '<label for="staticSecondaryApprovedOn" class="col-sm-3 col-form-label" onclick="updateSecondaryApprovedOn();">Secondary Approved on: </label>';
                                            echo '<div class="col-sm-9">';
                                                echo '<input type="text" readonly class="form-control" id="staticSecondaryApprovedOn" value="'.$plan['secondaryapprovedon'].'">';
                                            echo '</div>';
                                        echo '</div>';
                                        
                                        echo '<div class="card">';
                                            echo '<h6 class="card-header">Plan Metadata</h6>';
                                            echo '<div class="card-body">';
                                                echo '<div style="float:left;"><textarea id="planmetadata">'.$plan['planmetadata'].'</textarea></div>';
                                                echo '<div style="float:left;padding-left:5px;"><button type="button" class="btn btn-outline-primary" onclick="updatePlanMetadata();">Update</button><div id="updatingMetadataIndicator"></div></div>';
                                                echo '<div style="clear:both;"></div>';
                                            echo '</div>';
                                        echo '</div>';

                                    ?>
                                </div>
                            </div>

                            <div class="card">
                                <h6 class="card-header text-start">Slice Subscriptions (Part Categories)</h6>
                                <div class="card-body">
                                <?php


                                foreach($slices as $slice)
                                {
                                    $partcategory=$pim->getPartCategory($slice['partcategory']);
                                    $grainlist=$sandpiperPrimary->getSliceGrainList($slice['sliceid']);

                                    $grainlisthash= $sandpiperPrimary->updateSliceHash($slice['sliceid']);



                                    echo '<div class="card">';
                                        echo '<h6 class="card-header text-start">';
                                            echo '<button type="button" class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapse'.$slice['id'].'" aria-expanded="false">'.$slice['description'].'</button>';
                                        echo '</h6>';
                                        echo '<div class="collapse" id="collapse'.$slice['id'].'">';
                                            echo '<div id="" class="card-body">';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['slicetype'].'" class="col-sm-3 col-form-label text-start">PIM Part Category</label>';
                                                    echo '<div class="col-sm-9 text-start">';
                                                        echo '<a class="btn btn-secondary" href="./partCategory.php?id='.$slice['partcategory'].'">'.$partcategory['name'].'</a>';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['slicetype'].'" class="col-sm-3 col-form-label text-start">Slice Type</label>';
                                                    echo '<div class="col-sm-9">';
                                                        echo '<input type="text" readonly class="form-control" id="static'.$slice['slicetype'].'" value="'.$slice['slicetype'].'">';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['subscriptionuuid'].'" class="col-sm-3 col-form-label text-start">Subscription UUID:</label>';
                                                    echo '<div class="col-sm-9">';
                                                        echo '<input type="text" readonly class="form-control" id="static'.$slice['subscriptionuuid'].'" value="'.$slice['subscriptionuuid'].'">';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['subscriptionmetadata'].'" class="col-sm-3 col-form-label text-start">Subscription Metadata:</label>';
                                                    echo '<div class="col-sm-9">';
                                                        echo '<input type="text" readonly class="form-control" id="static'.$slice['subscriptionmetadata'].'" value="'.$slice['subscriptionmetadata'].'">';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['slicehash'].'" class="col-sm-3 col-form-label text-start">Hash of grain UUIDs:</label>';
                                                    echo '<div class="col-sm-9">';
                                                        echo '<input type="text" readonly class="form-control" id="static'.$slice['slicehash'].'" value="'.$grainlisthash.'">';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['slicehash'].'" class="col-sm-3 col-form-label text-start">Grain Count:</label>';
                                                    echo '<div class="col-sm-9">';
                                                        echo '<input type="text" readonly class="form-control" id="static'.$slice['slicehash'].'" value="'.count($grainlist).'">';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<hr>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="static'.$slice['sliceuuid'].'" class="col-sm-3 col-form-label text-start">Slice UUID:</label>';
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
                                }?>


                                    <div class="card">
                                        <div class="card-body">
                                            <form method="post">
                                                <div>Part Category <select name="partcategory"><?php foreach($partcategories as $partcategory){echo '<option value="'.$partcategory['id'].'">'.$partcategory['name'].'</option>';} ?></select></div>
                                                <div>Slice Type <select name="slicetype"><option value="pies-item">pies-item</option><option value="pies-item">aces-item</option><option value="pies-item">aces-app</option><option value="pies-item">asset</option></select></div>
                                                <div>Subscription Metadata <input type="text" name="metadata"/></div>
                                                <hr>
                                                <input type="submit" name="submit" value="Add"/>
                                            </form>
                                        </div>
                                    </div>

                                </div>
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