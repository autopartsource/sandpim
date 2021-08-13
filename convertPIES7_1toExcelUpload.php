<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'utilities';
session_start();

$logs=new logs();
$pim=new pim();
$databaseversions=$pim->getAutocareDatabaseList('pcdb');
// no one ever is to blame
?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php if (isset($_SESSION['userid'])){include('topnav.php');} ?>

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
                        <h3 class="card-header text-start"><img src="./rhubarb_from_pies.png" class="img-thumbnail" width="120"/>Flatten PIES (7.1) xml file to an Excel spreadsheet</h3>

                        <div class="card-body">
                            <form method="post" action="convertPIES7_1toExcelProcess.php" enctype="multipart/form-data">
                                <div style="padding:5px;text-align: left;"><input type="file" name="fileToUpload" id="fileToUpload" accept=".xml"/></div>
                                <div style="padding:5px;text-align: left;"><input type="checkbox" id="ignorelogic" name="ignorelogic"/><label for="ignorelogic">Ignore logic flaws</label></div>
                                <div style="padding:5px;text-align: left;">PCdb Version for validation <select name="pcdbversion"><?php foreach($databaseversions as $databaseversion){ echo '<option value="'.$databaseversion['name'].'">'.$databaseversion['versiondate'].'</option>';}?></select></div>
                                <hr>
                                <div style="padding:5px;"><input name="submit" type="submit" value="Generate Excel file"/></div>

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
<?php 
if (isset($_SESSION['userid']))
{
 include('./includes/footer.php');
}
else
{
?><div style="font-size: .75em; font-style: italic; color: #808080;"><?php  
 $logs->logSystemEvent('rhubarb', 0, 'upload page load by:'.$_SERVER['REMOTE_ADDR']);
 include('./includes/storageDisclaimer.php');
?></div><?php  
}
?>
    </body>
</html>