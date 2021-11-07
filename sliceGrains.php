<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/sandpiperPrimaryClass.php');


$navCategory = 'settings';

$pim=new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'sliceGrains.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}


$sandpiperPrimary=new sandpiperPrimary;


if (isset($_POST['submit']) && $_POST['submit'] == 'Add') 
{
 $data=array();
 $data['grain_uuid']=$_POST['grainuuid'];
 $data['slice_uuid']=$_POST['grainuuid'];
 $data['grain_key']=$_POST['basename'];
 $data['encoding']='raw';
 $data['payload'] = file_get_contents($_POST['uri']);
    
 if(true)
 {
  $error_msg.='filesize:'.strlen($data['payload']).'; ';      
  $grainid=$sp->addGrain($data, true, true);
  $error_msg.='grainid:'.$grainid.'; ';
 }
 else
 {
  $error_msg = 'Failed to get file';
 }
}







$slice=$sandpiperPrimary->getSlice(intval($_GET['sliceid']));
$grainlist=$sandpiperPrimary->getSliceGrainList(intval($_GET['sliceid']));

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
        <h2><?php echo $slice['description'];?></h2>
        <h3>(<?php echo $slice['sliceuuid'];?>)</3>
        <h3><?php echo count($grainlist). ' '.$slice['slicetype'] ;?>   grains in slice </h3>
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                   
                    <?php foreach($grainlist as $grain){
                    echo '<div style="float:left; padding:10px;"><a href="./grain.php?uuid='.$grain.'">'.$grain.'</a></div>';
                    }?>
                    <div style="clear: both;"></div> 
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