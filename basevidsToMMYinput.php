<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/vcdbClass.php');


$navCategory = 'utilities';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb();
$logs = new logs;

$output='';


if (isset($_POST['submit']) && strlen($_POST['input'])>0) 
{
 $input = $_POST['input'];
 $records = explode("\r\n", $input);
 $output="BaseVehicleID\tMakeName\tModelName\tYear\tMakeID\tModelID\r\n";

 foreach ($records as $record) 
 {
  $fields = explode("\t", $record);
  if($basevehicleid=intval($fields[0]))
  {
   $mmy=$vcdb->getMMYforBasevehicleid($basevehicleid);
   $output.=$basevehicleid."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$mmy['year']."\t".$mmy['MakeID']."\t".$mmy['ModelID']."\r\n";
  }
 }
 
 $logs->logSystemEvent('UTILITIES', $_SESSION['userid'], 'Convert BaseVIDs to MMYs. '.count($records).' records');
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

        <!-- Header -->
        <h3>Convert BaseVehicle IDs to make/model/year text</h3>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <form method="post">
                    <div>IDs (one per line)</div>
                    <div><textarea name="input" rows="10"><?php echo $output;?></textarea></div>
                    <input type="submit" name="submit" value="Convert"/>
                </form>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('./includes/footer.php'); ?>
    </body>
</html>