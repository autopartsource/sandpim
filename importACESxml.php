<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'import';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importACESxml.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$v = new vcdb;

$partcategories = $pim->getPartCategories();



$errors=array();
$importresults=array();
        
if (isset($_POST['input'])) 
{
 $xml = simplexml_load_string($_POST['input']);
 $app_count=count($xml->App);
 if($app_count)
 {
  $imported_app_count=$pim->createAppFromACESsnippet($xml,$_POST['partcategory']);
  $importresults[]= $app_count . ' apps created';
 }
 else
 {
  $errors[]='No app tags were found in input text';   
 }
}?>
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
                        <h3 class="card-header text-start">Import applications from ACES xml</h3>
                        <div class="card-body">
                            <form method="post">

                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste xml content from an ACES file</h6>
                                </div>
                                <textarea style="width:100%;height:200px;" name="input"></textarea>
                                <div>Category for part creation <select name="partcategory"><option value="0">Do not create parts</option> <?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"><?php echo $partcategory['name']; ?></option><?php } ?></select></div>
                                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>                                    
                            </form>
                        </div>
                    </div>
                    
                    <?php
                    foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}
                    foreach($importresults as $importresult){echo '<div class="alert alert-success" role="alert">'.$importresult.'</div>';}
                    ?>
                                        
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