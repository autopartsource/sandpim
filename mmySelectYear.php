<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'applications';


session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

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
                        <h3 class="card-header text-left">Applications (<?php echo $vcdb->makeName($makeid).' '.$vcdb->modelName($modelid);?>)</h3>

                        <div class="card-body">
                            <div class="row padding my-row groupCol">
                                <?php
                                    for($y = 0;$y < $groupedYearsCount;$y++) {
                                        echo '<div class="my-col inner-col">';
                                        foreach ($groupedyears[$y] as $year) {
                                            echo '<div class="groupButton" style="padding:5px;"><a href="appsSelectCategory.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$year['id'].'"class="btn btn-secondary btn-block" role="button" aria-disabled="true">'.$year['id'].'</a></div>';
                                        }
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