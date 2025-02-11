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

if (isset($_GET['submit']) && $_GET['submit'] == 'delete') 
{
 $sp->deleteGrain($_GET['uuid']);
 $sp->logEvent('', '', $_GET['uuid'], 'grain deleted');   
}


$slice=$spp->getSlice($sliceid);


if (isset($_POST['submit']) && $_POST['submit'] == 'Add' && $slice) 
{
 $data=array();
 $data['grain_uuid']=$_POST['grainuuid'];
 $data['slice_uuid']=$slice['sliceuuid'];
 $data['grain_key']=$_POST['grainkey'];
 $data['source']=$_POST['source'];
 $data['encoding']='raw';
 $data['payload'] = file_get_contents($_POST['uri']);
    
 if(strlen($data['payload'])>0)
 {
  $grainid=$sp->addGrain($data, true, true);
  
  if($grainid)
  {
   $sp->logEvent('', '', $_POST['grainuuid'], 'grain created by manual upload');     
  }
 }
}

$grains=$spp->getSliceGrains($sliceid);

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
        <h3><?php echo $slice['description'];?></h3>
        <div>(<?php echo $slice['sliceuuid'];?>)</div>
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <table>
                        <tr><th>Grain UUID</th><th>Source</th><th>Grain Key</th><th>Encoding</th><th>Size (bytes)</th><th>Timestamp</th><th>Actions</th></tr>                  
                        <?php foreach($grains as $grain){echo '<tr><td>'.$grain['grain_uuid'].'</td><td>'.$grain['source'].'</td><td>'.$grain['grain_key'].'</td><td>'.$grain['encoding'].'</td><td>'.$grain['grain_size_bytes'].'</td><td>'.$grain['timestamp'].'</td><td><a href="./streamFilegrain.php?uuid='.$grain['grain_uuid'].'&sliceid='.$sliceid.'"/>Download</a><br/><a href="./sliceGrains.php?submit=delete&sliceid='.$sliceid.'&uuid='.$grain['grain_uuid'].'">Delete</a></td></tr>';}?>
                        
                    </table>
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                    <div style="background-color: #c0c0c0;">
                        <h6>Add filegrain to slice</h6>
                        <form method="post">
                            <input type="hidden" name="sliceid" value="<?php echo $sliceid;?>"/>
                            <div style="padding:5px;">URI Path <input type="text" name="uri"/></div>
                            <div style="padding:5px;">Source <input type="text" name="source"/></div>
                            <div style="padding:5px;">Grain Key <input type="text" name="grainkey"/></div>
                            <div style="padding:5px;">Grain UUID <input type="text" name="grainuuid" value="<?php echo $pim->uuidv4();?>"/></div>
                            <div style="padding:10px;"><input name="submit" type="submit" value="Add"/></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>