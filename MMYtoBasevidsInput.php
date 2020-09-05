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

$pim = new pim();
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

        <!-- Header -->
        <h3>Convert make/model/year text to VCdb BaseVehicle IDs</h3>

        <div class="wrapper">
            <div class="contentLeft"></div>
            <!-- Main Content -->
            <div class="contentMain">
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

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('./includes/footer.php'); ?>
    </body>
</html>