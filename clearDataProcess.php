<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/configGetClass.php');
$navCategory = 'utilities';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'clearData.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$logs=new logs;
$configGet=new configGet;

$pepper = $configGet->getConfigValue('pepper');
$resultmessage=''; $deletedcount=0;

$partcategory=intval($_GET['partcategory']);
$parts = $pim->getParts('', 'contains', $partcategory, 'any', 'any', 'any', 1000);

if(isset($_POST['submit']) && $_POST['submit']=='Delete' && $_POST['confirm']==$pepper)
{
 $records = explode("\r\n", $_POST['parts']);
 foreach ($records as $record) 
 {
  $part=$pim->getPart(trim($record));
  if($part)
  {
   // alert_part
   // interchange
   // package
   // part_VIO
   // part_application_summary
   // part_asset
   // part_attribute
   // part_balance
   // part_description
   // part_history
   // receiverprofile_parttranslation
   // application (and related)
   // part
   
   $pim->deletePart($part['partnumber']);
   $logs->logSystemEvent('UTILITIES', $_SESSION['userid'], 'Cleared ALL data for part ['.$part['partnumber'].']');       
   $deletedcount++;
  }
 }
 $resultmessage=$deletedcount.' parts were deleted - including their applications and asset connections';
}


?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        
        <script>
        </script>
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
                    <?php if($resultmessage==''){?>
                    
                    <div class="card shadow-sm">
                        <!-- Header -->
                        <h3 class="card-header text-start">Clear ALL Data for partnumbers</h3>
                        <div class="card-body">
                            
                            <form action="./clearDataProcess.php?partcategory=<?php echo $partcategory;?>" method="post" autocomplete="off">
                                <div style="color:red;">You are about to delete ALL DATA for the <?php echo count($parts);?> parts listed below - including the parts themselves. You can edit the list before proceeding. This is not reversible.</div>
                                <div style="padding:20px;">
                                    <textarea name="parts" style="height: 300px;"><?php foreach($parts as $part){echo $part['partnumber']."\r\n";}?></textarea>
                                </div>
                                <div style="padding:10px;color:red;">Enter the system "pepper" in the box and click Delete to execute the deletion of the parts listed above.</div>
                                <input type="text" name="confirm" autocomplete="off"/>
                                <input type="hidden" name="partcategory" value="<?php echo $partcategory;?>"/>
                                <input type="submit" name="submit" value="Delete"/>
                            </form>
                            
                        </div>
                    </div>
                    
                    <?php }else{?>
         
                        <div style="color:red;"><?php echo $resultmessage;?></div>
                    
                    <?php }?>
                    
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