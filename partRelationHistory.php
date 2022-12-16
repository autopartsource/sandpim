<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'parts';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}



$vcdb = new vcdb;
$pcdb = new pcdb;
$pim = new pim;
$logs=new logs;
$user=new user;

$partnumber = $_GET['partnumber'];
$part = $pim->getPart($partnumber);
$history = $logs->getPartEvents($partnumber, 1000);
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
                        <h3 class="card-header text-start">BOM History for <a href="./showPart.php?partnumber=<?php echo $partnumber?>"><span class="text-info"><?php echo $partnumber?></span></a></h3>
                        <div class="card-body">
                            <?php
                            if ($part && count($history)) {
                                echo '<table class="table"><tr><th>Date/Time</th><th>After Change</th><th>Before Change</th></tr>';
                                foreach ($history as $record) 
                                {
                                    if(strpos($record['description'],'BOM change')===false){continue;}
                                    
                                    $extractedboms=$logs->extractBOMsfromChangeText($record['description']);
                                    
                                    echo '<tr><td>' . $record['eventdatetime'] . '</td>';
                                      
                                    echo '<td><table>';
                                    foreach($extractedboms['after'] as $newbomline)
                                    {
                                        $partcolor='white';
                                        if(!array_key_exists($newbomline['partnumber'], $extractedboms['beforeparts'])){$partcolor='green';}
                                        echo '<tr><td style="text-align:left;padding:0px 10px 0px 3px;background-color:'.$partcolor.';">'.$newbomline['partnumber'].'</td><td style="text-align:right;padding:0px 5px 0px 10px;">'.$newbomline['units'].'</td></tr>';
                                    }
                                    echo '  </table></td>';


                                    echo '<td><table>';
                                    foreach($extractedboms['before'] as $oldbomline)
                                    {
                                        $partcolor='white';
                                        if(!array_key_exists($oldbomline['partnumber'], $extractedboms['afterparts'])){$partcolor='red';}
                                        echo '<tr><td style="text-align:left;padding:0px 10px 0px 3px;background-color:'.$partcolor.';">'.$oldbomline['partnumber'].'</td><td style="text-align:right;padding:0px 5px 0px 10px;">'.$oldbomline['units'].'</td></tr>';
                                    }
                                    echo '</table></td>';
                                    
                                    echo '</tr>';

                                }
                                echo '</table>';
                            } else { // no hist found
                                echo 'No changes found';
                            }
                            ?>
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

