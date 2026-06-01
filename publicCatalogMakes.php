<?php
include_once('./class/logsClass.php');
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/interchangeClass.php');
$navCategory = 'search';
session_start();

$categorylist=array(122,123,133);
$parttypelist=array(1684);
$lifecyclestatuses=array('2','3','4','7','8');


$logs=new logs();
$pim=new pim();
$pcdb=new pcdb();
$vcdb=new vcdb();
$interchange=new interchange();

$results=array();
$compresults=array();
$qsanitized='';


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
        <!-- Navigation Bar -->
        
        <!-- Header -->
            
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-1 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-10 my-col colMain">
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start"><a href="./publicCatalog.php">Catalog Home</a> > Search by Vehicle</h3>
                        <div class="card-body">
                            
                        <?php foreach ($makes as $make)
                        {
                            echo '<div style="font-size:1.5em;padding:8px;"><a href="publicCatalogMakeModel.php?makeid=' . $make['id'] . '"class="btn btn-secondary" role="button" aria-disabled="true" style="font-size:1.1em;">' . $make['name'] . '</a></div>';
                        }?>

                            
                        </div>
                    </div>
                    
                    
                    
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-1 my-col colRight">
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
    </body> 
</html>