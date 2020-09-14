<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'applications';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;

$makeid=intval($_GET['makeid']);
$models=$vcdb->getModels($makeid);

$groupcount=7;
$modelcount=count($models);
if($modelcount<=70){$groupcount=6;}
if($modelcount<=60){$groupcount=5;}
if($modelcount<=40){$groupcount=4;}
if($modelcount<=30){$groupcount=3;}
if($modelcount<=20){$groupcount=2;}
if($modelcount<=10){$groupcount=1;}
//comment

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
        
        <!-- Header -->
        <h1>Applications (<?php echo $vcdb->makeName($makeid); ?>)</h1>
        
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <div class="col-xs-12 col-md-2 my-col colLeft">
                </div>

                <!-- Main Content -->
                <div class="col col-xs-12 col-md-8 my-col colMain">
                    <div class="row padding my-row groupCol">
                        <?php
                            for($y = 0;$y < $groupedModelsCount;$y++) {
                                echo '<div class="my-col inner-col">';
                                foreach ($groupedmodels[$y] as $model) {
                                    echo '<div class="groupButton" style="padding:5px;"><a href="mmySelectYear.php?makeid='.$makeid.'&modelid='.$model['id'].'"class="btn btn-secondary btn-block" role="button" aria-disabled="true">'.$model['name'].'</a></div>';
                                }
                                echo '</div>';
                            }
                        ?>
                    </div>
                </div>

                <div class="col-xs-12 col-md-2 my-col colRight">
                </div>
            </div>
        </div>
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>