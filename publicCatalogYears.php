<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
$navCategory = 'search';
session_start();

$pim=new pim();
$vcdb=new vcdb();

$partcategories=array(122,123,133);


if(isset($_GET['makeid']) && isset($_GET['modelid']))
{
 $makeid=intval($_GET['makeid']);
 $makename=$vcdb->makeName($makeid);
 $modelid=intval($_GET['modelid']);
 $modelname=$vcdb->modelName($modelid);
 $years = $vcdb->getYears($makeid, $modelid);
}

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <div class="row">
            <!-- Main Content -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <h3 class="card-header text-start"><a href="./publicCatalog.php">Home</a> > <a href="./publicCatalogMakes.php"><?php echo $makename;?></a> > <a href="./publicCatalogModels.php?makeid=<?php echo $makeid;?>"><?php echo $modelname;?></a></h3>
                    <div class="card-body">
                    <?php foreach ($years as $year)
                    {
                        $basevid=$vcdb->getBasevehicleidForMidMidYid($makeid, $modelid, $year['id']);
                        $appcount=$pim->countAppsByBasevidsAndPartcategories(array($basevid), $partcategories);
                        $disabled=''; if($appcount==0){$disabled=' disabled';}
                        echo '<div style="font-size:1.5em;padding:8px;"><a href="publicCatalogBasevehicle.php?makeid=' . $makeid .'&modelid='.$modelid.'&yearid='.$year['id'].'" class="btn btn-secondary'.$disabled.'" role="button" aria-disabled="true" style="font-size:1.1em;">' . $year['id'] .'</a></div>';
                    }?>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>