<?php
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'reports';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb = new pcdb;
$pim = new pim;
$logs=new logs;
$user=new user;

$status='PENDING';
$sincedate=date('Y').'-01-01';
$eventtypes=array('PART-AVAILABLE','PART-ELECTRONIC','PART-DISCONTINUED','PART-SUPERDEDED','PART-AVAILABLE-WHILE-SUPPLIES-LAST','PART-OBSOLETE');

if(isset($_GET['lifecyclegroup']) && $_GET['lifecyclegroup']=='birth')
{ 
 $eventtypes=array('PART-AVAILABLE','PART-ELECTRONIC');
}

if(isset($_GET['lifecyclegroup']) && $_GET['lifecyclegroup']=='retirement')
{ 
 $eventtypes=array('PART-DISCONTINUED','PART-SUPERDEDED','PART-AVAILABLE-WHILE-SUPPLIES-LAST');
}

if(isset($_GET['lifecyclegroup']) && $_GET['lifecyclegroup']=='death')
{ 
 $eventtypes=array('PART-OBSOLETE');
}

$events=$pim->getNotificationEvents($status);

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
                    <div class="card-body">
                        <form>
                            <select name="lifecyclegroup">
                                <option value="all">All Lifecycle Events</option>
                                <option value="birth"<?php if(isset($_GET['lifecyclegroup']) && $_GET['lifecyclegroup']=='birth'){echo ' selected';}?>>Birth Events</option>
                                <option value="retirement"<?php if(isset($_GET['lifecyclegroup']) && $_GET['lifecyclegroup']=='retirement'){echo ' selected';}?>>Retirement Events</option>
                                <option value="death"<?php if(isset($_GET['lifecyclegroup']) && $_GET['lifecyclegroup']=='death'){echo ' selected';}?>>Death Events</option>
                            </select>
                            <input type="submit" name="submit" value="Search"/>
                        </form>
                    </div>
                    <div class="card shadow-sm">
                        <!-- Header -->
                        <h3 class="card-header text-start"><?php echo $status;?> Lifecycle Events</h3>
                        <div class="card-body">
                            
                            <?php
                            if (count($events))
                            {
                                echo '<table class="table"><tr><th>Partnumber</th><th>Event Type</th><th>Effective Date</th></tr>';
                                foreach ($events as $event)
                                {
                                    if(!in_array($event['type'], $eventtypes)){continue;}
                                    $partnumber=''; $availabledate=''; $discontinueddate=''; $obsoleteddate=''; $supersededdate='';
                                    $datapairs=explode(';',$event['data']);
                                    foreach($datapairs as $datapair)
                                    {
                                        $namevalue=explode(':',$datapair);
                                        if(count($namevalue)==2)
                                        {
                                            if($namevalue[0]=='partnumber'){$partnumber=trim($namevalue[1]);}
                                            if($namevalue[0]=='availabledate' && strlen($namevalue[1])==10){$availabledate=trim($namevalue[1]);}                                            
                                            if($namevalue[0]=='supersededdate' && strlen($namevalue[1])==10){$supersededdate=trim($namevalue[1]);}                                            
                                            if($namevalue[0]=='discontinueddate' && strlen($namevalue[1])==10){$discontinueddate=trim($namevalue[1]);}
                                            if($namevalue[0]=='obsoleteddate' && strlen($namevalue[1])==10){$obsoleteddate=trim($namevalue[1]);}
                                        }                                        
                                    }
                                    $effectivedate='';
                                    if($availabledate!=''){$effectivedate=$availabledate;}
                                    if($supersededdate!=''){$effectivedate=$supersededdate;}
                                    if($discontinueddate!=''){$effectivedate=$discontinueddate;}
                                    if($obsoleteddate!=''){$effectivedate=$obsoleteddate;}

                                    if($effectivedate<$sincedate){continue;}
                                    
                                    echo '<tr><td><a href="./showPart.php?partnumber='.$partnumber.'">'.$partnumber.'</a></td><td>'.$event['type'].'</td><td>'.$effectivedate.'</td></tr>';
                                }
                                echo '</table>';
                            }
                            else
                            {
                                echo 'No events found';
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

