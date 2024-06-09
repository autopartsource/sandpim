<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');

$navCategory = 'reports';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'parttypeHolesReportForm.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$user=new user;
$configGet = new configGet;

$favoriteparttypes=$pim->getFavoriteParttypes();
$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');
$favoritepositions=$pim->getFavoritePositions();

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
                        <h3 class="card-header text-start">Report vehicles not covered by a part-type</h3>

                        <div class="card-body">
                            <form action="parttypeHolesReportStream.php" method="get">
                                <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f8f8f8">
                                    <div style="padding: 10px;">Part Type <select name="parttypeid"><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"><?php echo $parttype['name'];?></option><?php }?></select></div>
                                    <div style="padding:10px;">Include model-years since <input type="number" name="fromyear" value="<?php echo intval(date('Y')-3);?>"/></div>
                                    <div style="padding:10px;">Position <select name="positionid"><option value="0">Any</option><?php foreach ($favoritepositions as $position) { echo '<option value="'.$position['id'].'">'.$position['name'].'</option>'; } ?></select></div>
                                    <div style="padding:10px;">Include vehicles with a population (<?php echo $viogeography.' '.$vioyearquarter;?>) greater than <input type="number" name="countthreshold" value="10000" size="6"/></div>                                    
                                    <input type="submit" name="submit" value="Export"/>
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