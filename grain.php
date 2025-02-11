<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/sandpiperPrimaryClass.php');
include_once('./class/sandpiperAPIclass.php');


$navCategory = 'settings';

$pim=new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'grain.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$sandpiper = new sandpiper();
$sliceid=intval($_GET['sliceid']);


$grain=$sandpiper->getFilegrainByUUID($_GET['uuid']);

?>
<!DOCTYPE html>
<html lang="en" xml:lang="en">
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h2>Grain Data</h2>
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <?php
                    //print_r($grain);
                    ?>
                    
                    <table>
                        <tr><th>grain uuid</th><td><?php echo $grain['grain_uuid'];?></td></tr>
                        <tr><th>grain key</th><td><?php echo $grain['grain_key'];?></td></tr>
                        <tr><th>source</th><td><?php echo $grain['source'];?></td></tr>
                        <tr><th>encoding</th><td><?php echo $grain['encoding'];?></td></tr>
                        <tr><th>size (bytes)</th><td><?php echo $grain['grain_size_bytes'];?></td></tr>
                        <tr><th>timestamp</th><td><?php echo $grain['timestamp'];?></td></tr>
<?php /*                        <tr><th>payload</th><td><textarea><?php if($grain['encoding']=='raw' && strlen($grain['payload'])<100000){echo $grain['payload'];}?></textarea></td></tr> */ ?>
                        <tr><th>Actions</th>
                            <td><form action="./sliceGrains.php"><input type="hidden" name="sliceid" value="<?php echo $sliceid;?>"/><input type="hidden" name="uuid" value="<?php echo $grain['grain_uuid'];?>"/><input type="submit" name="submit" value="Delete"/></form>
                                <a href="./streamFilegrain.php?uuid=<?php echo $grain['grain_uuid'];?>&sliceid=<?php echo $sliceid;?>"/>Download</a>
                            </td>
                        </tr>
                    </table>
                    
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