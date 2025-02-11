<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
$navCategory = 'applications';

$pim = new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'mmySelectModel.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb=new vcdb;

$regionid=false; if(isset($_GET['regionid']) && intval($_GET['regionid'])>0){$regionid=intval($_GET['regionid']);}
$makeid=intval($_GET['makeid']);
$models=$vcdb->getModels($makeid,$regionid);

$groupcount=4;
$modelcount=count($models);
if($modelcount<=30){$groupcount=3;}
if($modelcount<=20){$groupcount=2;}
if($modelcount<=10){$groupcount=1;}
//comment
//$groupcount=20;

$groupsize=intval(count($models)/$groupcount);
$i=0; $groupnumber=0; $groupedmodels=array();

foreach($models as $model)
{
 $groupedmodels[$groupnumber][]=$model;
 $i++; if($i>$groupsize){$i=0; $groupnumber++;}
}

$groupedModelsCount = count($groupedmodels);
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
                        <h4 class="card-header text-start"><?php echo '<a href="appsIndex.php">'.$vcdb->makeName($makeid).'</a>'; ?> Models from 
                            <form id="region">
                                <input type="hidden" name="makeid" value="<?php echo $makeid;?>"/>
                                <select onchange="document.getElementById('region').submit();" name="regionid"><option value="0">All Regions</option><option value="1"<?php if($regionid==1){echo ' selected';}?>>US</option><option value="2"<?php if($regionid==2){echo ' selected';}?>>Canada</option><option value="3"<?php if($regionid==3){echo ' selected';}?>>Mexico</option></select>
                            </form>
                        </h4>

                        <div class="card-body">
                            <div class="container">
                                <div class="row row-cols-1 row-cols-sm-2 <?php if(intval($groupedModelsCount) > 4) {echo "row-cols-md-"; echo intval($groupedModelsCount-2);} else {echo "row-cols-md-".intval($groupedModelsCount);} echo " row-cols-lg-"; echo intval($groupedModelsCount);?>">
                                <?php
                                    for($y = 0;$y < $groupedModelsCount;$y++) {
                                        echo '<div class="col-sm">';
                                        echo '<div class="d-grid gap-2 mx-auto">';
                                        foreach ($groupedmodels[$y] as $model) {
                                            echo '<a href="mmySelectYear.php?makeid='.$makeid.'&modelid='.$model['id'].'"class="btn btn-secondary" role="button" aria-disabled="true">'.$model['name'].'</a>';
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                ?>
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