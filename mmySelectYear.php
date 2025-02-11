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
 $logs->logSystemEvent('accesscontrol',0, 'mmySelectYear.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb=new vcdb;

$makeid=intval($_GET['makeid']);
$modelid=intval($_GET['modelid']);
$years=$vcdb->getYears($makeid,$modelid);

$groupcount=5;
$yearcount=count($years);
if($yearcount<=70){$groupcount=6;}
if($yearcount<=60){$groupcount=5;}
if($yearcount<=40){$groupcount=4;}
if($yearcount<=30){$groupcount=3;}
if($yearcount<=20){$groupcount=2;}
if($yearcount<=10){$groupcount=1;}
// comment -  

$yearcount=count($years);
$groupsize=intval(count($years)/$groupcount);
$i=0; $groupnumber=0; $groupedyears=array();
foreach($years as $year)
{
 $groupedyears[$groupnumber][]=$year;
 $i++; if($i>$groupsize){$i=0; $groupnumber++;}
}


$groupedYearsCount = count($groupedyears);
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
                <div class="col col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Apps > <?php echo '<a href="appsIndex.php">'.$vcdb->makeName($makeid).'</a> > <a href="mmySelectModel.php?makeid='.$makeid.'">'.$vcdb->modelName($modelid).'</a>';?></h3>

                        <div class="card-body">
                            <div class="container">
                                <div class="row row-cols-1 row-cols-sm-2 <?php if($groupedYearsCount > 4) {echo "row-cols-md-"; echo $groupedYearsCount-2;} else {echo "row-cols-md-".$groupedYearsCount;} echo" row-cols-lg-"; echo $groupedYearsCount;?>">
                                <?php
                                    for($y = 0;$y < $groupedYearsCount;$y++) {
                                        echo '<div class="col-sm">';
                                        echo '<div class="d-grid gap-2 mx-auto">';
                                        foreach ($groupedyears[$y] as $year) {
                                            echo '<a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'"class="btn btn-secondary" role="button" aria-disabled="true">'.$year['id'].'</a>';
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