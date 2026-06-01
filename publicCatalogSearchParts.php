<?php
include_once('./class/logsClass.php');
include_once('./class/pimClass.php');
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
$interchange=new interchange();

$results=array();
$compresults=array();
$qsanitized='';

if(isset($_GET['q']) && strlen(trim($_GET['q']))>1)
{
 $qsanitized=$pim->sanitizePartnumber($_GET['q']);
 $rawresults=$pim->getParts($qsanitized, 'contains', 'any', '1684', 'any', 'any', 30); 
 foreach ($rawresults as $rawresult)
 {
  if(!in_array($rawresult['partcategory'], $categorylist)){continue;}
  if(!in_array($rawresult['lifecyclestatus'], $lifecyclestatuses)){continue;}  
  if(count($parttypelist) && !in_array($rawresult['parttypeid'],$parttypelist)){continue;}

  $results[]=$rawresult;  
 }
 
 $rawcompresults=$interchange->getInterchangeBySearch($qsanitized, 'contains', '%', 30, false);
 foreach($rawcompresults as $rawcompresult)
 {
  if(count($parttypelist) && !in_array($rawcompresult['parttypeid'],$parttypelist)){continue;}
  $part=$pim->getPart($rawcompresult['partnumber']);
  if(!$part){continue;}
  if(!in_array($part['partcategory'], $categorylist)){continue;}
  if(!in_array($part['lifecyclestatus'], $lifecyclestatuses)){continue;}  

  $compresults[]=array(
      'competitorpartnumber'=>$rawcompresult['competitorpartnumber'],
      'partnumber'=>$rawcompresult['partnumber'],
      'brandname'=>$interchange->brandName($rawcompresult['brandAAIAID']),
      'partcategoryname'=>$part['partcategoryname'],
      'lifecyclestatusname'=>$pcdb->lifeCycleCodeDescription($part['lifecyclestatus']));
 }
 
// $compkey=[];
//   $compkey[$key]=$row['competitorpartnumber'];
// array_multisort($compkey,SORT_DESC,$compresults);
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
                        <h3 class="card-header text-start"><a href="./publicCatalog.php">Catalog Home</a> > Part Number Search</h3>
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
                            
                            <?php if(count($results)){?>
                            <div class="card shadow-sm">
                                <h5 class="card-header text-start">VGX Results</h5>
                                <div class="card-body">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Part</th>
                                                <th scope="col">Category</th>
                                                <th scope="col">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($results as $result){?>                            
                                            <tr>
                                               <td><a href="publicCatalogPart.php?partnumber=<?php echo $result['partnumber'];?>" class="btn btn-secondary"><?php echo $result['partnumber'];?></a></td>
                                               <td><?php echo $result['partcategoryname'];?></td>
                                               <td><?php echo $pcdb->lifeCycleCodeDescription($result['lifecyclestatus']);?></td>
                                            </tr>                           
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>                        
                            </div>              
                            <?php }?>

                            <?php if(count($compresults)){?>
                            <div class="card shadow-sm">                           
                                <h5 class="card-header text-start">Competitor Results</h5>
                                <div class="card-body">
                                    
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Partnumber</th>
                                                <th scope="col">Competitor</th>
                                                <th scope="col">VGX Part</th>
                                                <th scope="col">Category</th>
                                                <th scope="col">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($compresults as $compresult){?>
                                            <tr>
                                               <td><?php echo $compresult['competitorpartnumber'];?></td>
                                               <td><?php echo $compresult['brandname'];?></td>
                                               <td><a href="publicCatalogPart.php?partnumber=<?php echo $compresult['partnumber'];?>" class="btn btn-secondary"><?php echo $compresult['partnumber'];?></a></td>
                                               <td><?php echo $compresult['partcategoryname'];?></td>
                                               <td><?php echo $compresult['lifecyclestatusname'];?></td>
                                            </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php }?>
                            
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