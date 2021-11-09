<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/sandpiperAPIclass.php');
include_once('./class/sandpiperPrimaryClass.php');


$navCategory = 'settings';

$pim=new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'sliceGrains.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}


$spp=new sandpiperPrimary();
$sp=new sandpiper();



$sliceid=intval($_GET['sliceid']);


if (isset($_GET['submit']) && $_GET['submit'] == 'Delete') 
{
    echo 'delete grain:',$_GET['uuid'];
    
}


$slice=$spp->getSlice($sliceid);


if (isset($_POST['submit']) && $_POST['submit'] == 'Add' && $slice) 
{
 $data=array();
 $data['grain_uuid']=$_POST['grainuuid'];
 $data['slice_uuid']=$slice['sliceuuid'];
 $data['grain_key']='Level-1';
 $data['source']=$_POST['filename'];
 $data['encoding']='raw';
 $data['payload'] = file_get_contents($_POST['uri']);
    
 if(strlen($data['payload'])>0)
 {
  $grainid=$sp->addGrain($data, true, true);
  
  if($grainid)
  {
    echo 'grain not created';    
      
  }
  else
  {// grain not created
    echo 'grain not created';  
  }
  
 }
 else
 {
     
 }
}



$grainlist=$spp->getSliceGrainList($sliceid);

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
                    echo '<div style="float:left; padding:10px;"><a href="./grain.php?uuid='.$grain.'&sliceid='.$sliceid.'">'.$grain.'</a></div>';
                    }?>
                    <div style="clear: both;"></div> 
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                    <h6>Upload file to slice</h6>
                    <form method="post">
                        <input type="hidden" name="sliceid" value="<?php echo $sliceid;?>"/>
                        <div style="padding:5px;">URI Path <input type="text" name="uri"/></div>
                        <div style="padding:5px;">Filename <input type="text" name="filename"/></div>
                        <div style="padding:5px;">Grain UUID <input type="text" name="grainuuid" value="<?php echo $pim->uuidv4();?>"/></div>
                        <div style="padding:10px;"><input name="submit" type="submit" value="Add"/></div>
                    </form>

                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>