<?php
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
include_once('./class/configGetClass.php');
$navCategory = 'utilities';

$pim = new pim;


//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'pairPartsBulkInput.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb = new pcdb;
$logs=new logs;
$configGet = new configGet;
$user=new user;

$deliverygroups = $pim->getDeliverygroups();
$favoriteparttypes=$pim->getFavoriteParttypes();
$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');
$exportsdirectory = $configGet->getConfigValue('ExportsDirectory', '');


if(isset($_POST['submit']))
{
 $positionmode='same'; $pairwithparttypeid=0; $exportformat ='default'; $viodisplaymode='percentage';

 if(in_array($_POST['positionmode'], ['same','different'])){$viodisplaymode=$_POST['viodisplaymode'];}
 if(in_array($_POST['viodisplaymode'], ['percentage','actual'])){$positionmode=$_POST['positionmode'];}
 $pairwithparttypeid=intval($_POST['pairwith']);
 $deliverygroupid=intval($_POST['deliverygroup']);
 
 $clientfilename='pairedparts_'.$positionmode.'_'.$pairwithparttypeid.'_'.$deliverygroupid.'_'.date('Y-m-d').'.xlsx';
 $randomstring=$pim->newoid();
 $outputfile= $exportsdirectory.$randomstring;  
 
 $partnumbersarray=[];
 $records = explode("\r\n", $_POST['partnumbers']);
 foreach ($records as $record) 
 {
  $fields = explode("\t", $record);
  if(count($fields)>=1 && trim($fields[0])!='')
  {
   $partnumbersarray[]=$pim->sanitizePartnumber($fields[0]);
  }
 }
 $partnumbersencoded=base64_encode(implode("\t", $partnumbersarray));
 
// createBackgroundjob($jobtype,$status,$userid,$inputfile,$outputfile,$parameters,$datetimetostart,$contenttype,$clientfilename)
    
 $pim->createBackgroundjob('PairPartBulk', 'started', $_SESSION['userid'], '', $outputfile, 'exportformat:'.$exportformat.';positionmode:'.$positionmode.';pairwithparttypeid:'.$pairwithparttypeid.';viogeography:'.$viogeography.';vioyearquarter:'.$vioyearquarter.';viodisplaymode:'.$viodisplaymode.';deliverygroup:'.$deliverygroupid.';partnumbers:'.$partnumbersencoded, date('Y-m-d H:i:s'), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $clientfilename);
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./backgroundJobs.php'\" /></head><body></body></html>";
 exit;


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
                        <h3 class="card-header text-start">Two-Part paring - Bulk</h3>
                        <div class="card-body">

                            <form method="post">

                                <div style="float:left;">
                                    <div style="padding:5px;"><div>Part Numbers </div><div><textarea style="width:200px;height:300px;" name="partnumbers"></textarea></div></div>
                                </div>
                                
                                <div style="float:left;padding-left:20px;">
                                    <div style="padding:10px;">Pair with <select name="positionmode"><option value="same">Same</option><option value="different">Different</option></select> positioned parts</div>
                                    <div style="padding:10px;">with part type <select name="pairwith"><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"><?php echo $parttype['name'];?></option><?php }?></select></div>
                                    <div style="padding:10px;">in delivery group <select name="deliverygroup"><?php foreach ($deliverygroups as $deliverygroup) { ?> <option value="<?php echo $deliverygroup['id']; ?>"><?php echo $deliverygroup['description']; ?></option><?php } ?></select></div>
                                    <div style="padding:10px;">Show VIO as <select name="viodisplaymode"><option value="percentage">Percentage</option><option value="actual">Actual</option></select></div>
                                </div>
                                
                                <div style="clear:both;"></div>
                                
                                <div style="padding:5px;">                                    
                                    <input type="submit" name="submit" value="Create Job"/>                                    
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