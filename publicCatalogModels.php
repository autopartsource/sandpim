<?php
include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/vcdbClass.php');
$navCategory = 'search';
session_start();

$pim=new pim();
$vcdb=new vcdb();
$configGet = new configGet();

$partcategories= array();
$categoriesstrings=explode(',',$configGet->getConfigValue('publicCatalogCategories'));
foreach($categoriesstrings as $categoriesstring){$partcategories[]=intval($categoriesstring);}

if(isset($_GET['makeid']))
{
 $makeid=intval($_GET['makeid']);
 $makename=$vcdb->makeName($makeid);
 $models = $vcdb->getModels($makeid);
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
                    <h3 class="card-header text-start"><a href="./publicCatalog.php">Home</a> > Search <a href="./publicCatalogMakes.php"><?php echo $makename;?></a> Models</h3>
                    <div class="card-body">
                    <?php foreach ($models as $model)
                    {
                        $basevids=$vcdb->getBaseVidsForMakeModelRegion($makeid, $model['id'], false);
                        $appcount=$pim->countAppsByBasevidsAndPartcategories($basevids, $partcategories);
                        if($appcount==0){continue;}
                        echo '<div style="font-size:1.5em;padding:8px;"><a href="publicCatalogYears.php?makeid=' . $makeid .'&modelid='.$model['id'].'" class="btn btn-secondary" role="button" aria-disabled="true" style="font-size:1.1em;">' . $model['name'] .'</a></div>';
                    }?>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>