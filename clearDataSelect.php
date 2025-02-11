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

$partcategories = $pim->getPartCategories();

if(isset($_GET['submit']) && $_GET['submit']=='Clear' && $_GET['confirm']=='DELETE')
{
 
 
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
                    <div class="card shadow-sm">
                        <!-- Header -->
                        <h3 class="card-header text-start">Clear Data by Part Category</h3>
                        <div class="card-body">

                            <form action="./clearDataProcess.php">
                                <div style="padding:5px;"><select name="partcategory"><?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"><?php echo $partcategory['name']; ?></option><?php } ?></select>
                                <input type="submit" name="submit" value="Next"/></div>
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