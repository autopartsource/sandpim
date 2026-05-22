<?php
include_once('./class/logsClass.php');
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
$navCategory = 'search';
session_start();

$logs=new logs();
$pim=new pim();
$interchange=new interchange();

$results=false;
$qsanitized='';

if(isset($_GET['q']) && strlen(trim($_GET['q']))>1)
{
 $qsanitized=$pim->sanitizePartnumber($_GET['q']);
 $results=$pim->getParts($qsanitized, 'contains', 'any', 'any', 'any', 'any', 30);
 
  $compresults=$interchange->getInterchangeBySearch($qsanitized, 'contains', '%', 30, 1684);     
 
 
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
                        <h3 class="card-header text-start">Part Number Search</h3>
                        <div class="card-body">
                            <form method="get">
                                <div style="float:left;">
                                    <label class="sr-only" for="inputPart">Part</label>
                                    <input type="text" name="q" class="form-control mb-2 mr-sm-2" id="inputPart" placeholder="part number" value="<?php echo $qsanitized;?>"/>
                                </div>
                                <div style="float:left;"> 
                                     <button type="submit" class="btn btn-primary mb-2">Search</button>
                                </div>
                                <div style="clear:both;"></div> 
                            </form>
                        </div>
                    </div>

                    <?php if($results){?>
                    <div class="card shadow-sm">
                        <h5 class="card-header text-start">VGX Results</h5>
                        <div class="card-body">                            
                        <?php foreach($results as $result){?>                            
                            <div>                                
                            <?php echo $result['partnumber'];?>                                                                
                            </div>
                        <?php }?>
                        </div>
                    </div>                    
                    <?php }?>
 
                    <?php if($compresults){?>
                    <div class="card shadow-sm">                           
                        <h5 class="card-header text-start">Competitor Results</h5>
                        <div class="card-body">                            
                        <?php foreach($compresults as $compresult){?>                            
                            <div>                                
                            <?php echo $compresult['competitorpartnumber'];?>                                                                
                            </div>
                        <?php }?>
                        </div>
                    </div>                    
                    <?php }?>

                    
                    <?php if(count($compresults)==0 && count($results)==0 && $qsanitized!=''){?>
                    <div class="card shadow-sm">                           
                        <div class="card-body">No Results found
                        </div>
                    </div>                    
                    <?php }?>
                    
                    
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