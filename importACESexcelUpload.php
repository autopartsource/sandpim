<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'import';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importACESexcelUpload.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$logs=new logs();

$partcategories=$pim->getPartCategories();


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
                        <h3 class="card-header text-start">Import applications from <a title="This is the template spreadsheet (Excel .xlsx) to use as a guide. Fill in your own application data and upload it using the form on this page. There is sample data in the spreadsheet that can be deleted." href="./APA_PDM_Template.xlsx">spreadsheet</a> of fixed-column VCdb attributes</h3>

                        <div class="card-body">
                            <form method="post" action="importACESexcelProcess.php" enctype="multipart/form-data">
                                <div style="padding:5px;text-align: left;"><input type="file" name="fileToUpload" id="fileToUpload" accept=".xlsx"/></div>
                                <div style="padding:5px;text-align: left;">Category for part creation <select name="partcategory"><option value="0">Do not create parts</option> <?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"><?php echo $partcategory['name']; ?></option><?php } ?></select></div>
                                <div style="padding:5px;text-align: left;"><input disabled="true" type="checkbox" id="test" name="test"/><label for="test">Test only (No apps will be created)</label></div>
                                <div style="padding:5px;"><input name="submit" type="submit" value="Import"/></div>
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
        <?php  include('./includes/footer.php'); ?>
    </body>
</html>