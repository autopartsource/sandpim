<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/vcdbClass.php');

$navCategory = 'utilities';

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'MMYtoBasevidsInput.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$logs = new logs;

$databaseversions=$pim->getAutocareDatabaseList('vcdb');
$vcdbversion=false;
$output='';

if (isset($_POST['submit']) && strlen($_POST['input'])>0) 
{
 foreach($databaseversions as $databaseversion)
 {
  if($_POST['vcdbversion']==$databaseversion['name'])
  {
      $vcdbversion=$databaseversion['name'];
      break;
  }
 }
 $vcdb = new vcdb($vcdbversion);
 
 $input = $_POST['input'];
 $records = explode("\r\n", $input);
 $output="MakeName\tModelName\tYear\tBaseVID(".$vcdbversion.")\r\n";

 foreach ($records as $record) 
 {
  $fields = explode("\t", $record);
  if(count($fields)>=3 && intval($fields[2])>0)
  {
   $basevehicleid=$vcdb->getBasevehicleidForMMY($fields[0],$fields[1],$fields[2]);
   $output.=$fields[0]."\t".$fields[1]."\t".$fields[2]."\t".$basevehicleid."\r\n";
  }
 }
 
 $logs->logSystemEvent('UTILITIES', $_SESSION['userid'], 'Convert MMYs to BaseVIDs '.count($records).' records, '.$vcdbversion);
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
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Convert make/model/year text to VCdb BaseVehicle IDs</h3>

                        <div class="card-body">
                            <form method="post">
                                <div>MakeName (tab) ModelName (tab) Year</div>
                                <div><textarea name="input" rows="10" cols="80"><?php echo $output;?></textarea></div>
                                <div style="padding:5px;">Vcdb 
                                    <select name="vcdbversion">
                                    <?php foreach($databaseversions as $databaseversion){?>
                                        <option value="<?php echo $databaseversion['name'];?>"<?php if($vcdbversion==$databaseversion['name']){echo ' selected';}?>><?php echo $databaseversion['versiondate'];?></option>
                                        <?php }?>
                                    </select> <input type="submit" name="submit" value="Convert"/>
                                </div>
                            </form>
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