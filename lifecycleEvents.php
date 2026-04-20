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

$favoriteparttypes=$pim->getFavoriteParttypes();

$status='PENDING';

$sincedays=30; if(isset($_GET['sincedays'])){$sincedays=intval($_GET['sincedays']);}
$sincedate=date('Y-m-d', time()-(24*3600*$sincedays));

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

$parttypeid=false;
if(isset($_GET['parttypeid']))
{
 if($_GET['parttypeid']!='any')
 {
  $parttypeid=intval($_GET['parttypeid']);
 }
}

$events=[];
$rawevents=$pim->getNotificationEvents($status);
foreach ($rawevents as $event)
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

 $part=$pim->getPart($partnumber);
 if($part)
 {
  if($parttypeid!==false && $part['parttypeid']!=$parttypeid){continue;}
  $effectivedate='';
  if($availabledate!=''){$effectivedate=$availabledate;}
  if($supersededdate!=''){$effectivedate=$supersededdate;}
  if($discontinueddate!=''){$effectivedate=$discontinueddate;}
  if($obsoleteddate!=''){$effectivedate=$obsoleteddate;}
  if($effectivedate<$sincedate){continue;}
  $event['partnumber']=$partnumber;
  $event['effectivedate']=$effectivedate;
  $events[]=$event;
 }
}

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
                                        
                    <div class="card text-start">
                        <h4 class="card-header">Show recent lifecycle events</h4>
                        <div class="card-body">
                            <form>
                                <select name="parttypeid">
                                    <option value="any">Any Part Type</option>
                                    <?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>" <?php if(isset($_GET['parttypeid']) && $_GET['parttypeid']==$parttype['id']){echo ' selected';}?>><?php echo $parttype['name'];?></option><?php }?>
                                </select>                                                
                                <select name="lifecyclegroup">
                                    <option value="all">All Lifecycle Events</option>
                                    <option value="birth"<?php if(isset($_GET['lifecyclegroup']) && $_GET['lifecyclegroup']=='birth'){echo ' selected';}?>>Birth Events</option>
                                    <option value="retirement"<?php if(isset($_GET['lifecyclegroup']) && $_GET['lifecyclegroup']=='retirement'){echo ' selected';}?>>Retirement Events</option>
                                    <option value="death"<?php if(isset($_GET['lifecyclegroup']) && $_GET['lifecyclegroup']=='death'){echo ' selected';}?>>Death Events</option>
                                </select>
                                <select name="sincedays">
                                    <option value="7"<?php if($sincedays==7){echo ' selected';}?>>In the past week</option> 
                                    <option value="30"<?php if($sincedays==30){echo ' selected';}?>>In the past month</option> 
                                    <option value="90"<?php if($sincedays==90){echo ' selected';}?>>In the past 90 days</option> 
                                    <option value="365"<?php if($sincedays==365){echo ' selected';}?>>In the past year</option> 
                                    <option value="1096"<?php if($sincedays==1096){echo ' selected';}?>>In the past 3 years</option> 
                                </select>
                                <input type="submit" name="submit" value="Search"/>                                                
                            </form>
                            
                            <?php if (count($events))
                            {?>
                                
                            <div class="card">
                                <h6 class="card-header">Search Results <?php echo '<span class="badge bg-primary rounded-pill">'.count($events).'</span>'; ?></h6>
                                <div class="card-body scroll">
                                    <table class="table" border="1">
                                        <tr><th>Part Number</th><th>Event Type</th><th>Event Effective Date</th></tr>                                
                                        <?php foreach ($events as $event){echo '<tr><td><a href="./showPart.php?partnumber='.$event['partnumber'].'">'.$event['partnumber'].'</a></td><td>'.$event['type'].'</td><td>'.$event['effectivedate'].'</td></tr>';} ?>                    
                                    </table>
                                </div>
                            </div>
                            <?php
                            }
                            else
                            {
                                echo 'No events found';
                            }
                            ?>
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

