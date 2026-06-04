<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
$navCategory = 'search';
session_start();

$pim=new pim();
$vcdb=new vcdb();

if(isset($_GET['all']))
{
 $makes = $vcdb->getMakes();
}
else
{
 $makes = $pim->getFavoriteMakes();
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
                    <h3 class="card-header text-start"><a href="./publicCatalog.php">Home</a> > Search by Vehicle Make</h3>
                    <div class="card-body">
                    <?php foreach ($makes as $make)
                    {
                        echo '<div style="font-size:1.5em;padding:8px;"><a href="publicCatalogModels.php?makeid=' . $make['id'] . '"class="btn btn-secondary" role="button" aria-disabled="true" style="font-size:1.1em;">' . $make['name'] . '</a></div>';
                    }?>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>