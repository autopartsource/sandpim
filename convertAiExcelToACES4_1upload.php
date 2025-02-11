<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'utilities';
session_start();

$logs=new logs();
$pim=new pim();
$pcdbversions=$pim->getAutocareDatabaseList('pcdb');
$vcdbversions=$pim->getAutocareDatabaseList('vcdb');
$qdbversions=$pim->getAutocareDatabaseList('qdb');

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
                        <h3 class="card-header text-start">Build ACES (4.1) xml from <a title="This is the template spreadsheet (Excel .xlsx) to use as a guide. Fill in your own application data and upload it using the form on this page." href="./Flat_ACES_4_1_template_D.xlsx">spreadsheet</a> of flat application data</h3>
                            
                        <div class="card-body">
                            <form method="post" action="convertAiExcelToACES4_1process.php" enctype="multipart/form-data">
                                <div style="padding:5px;text-align: left;"><input type="file" name="fileToUpload" id="fileToUpload" accept=".xlsx"/></div>
                                <div style="padding:5px;text-align: left;"><input type="checkbox" id="showtext" name="showtext"/> <label for="showtext">Display output xml in text area (un-check to auto download ACES xml file)</label></div>
                                <div style="border:1px solid #e0e0e0;padding: 15px;">
                                    <div style="padding:5px;text-align: left;">AutoCare reference database versions for basic validation of BaseVehicleID, PartTypeID, PositionID and QdbID.</div>
                                    <div style="float:left;padding:5px;text-align: left;">VCdb <select name="vcdbname"><?php foreach($vcdbversions as $vcdbversion){ echo '<option value="'.$vcdbversion['name'].'">'.$vcdbversion['versiondate'].'</option>';}?><option value="">No validation</option></select></div>
                                    <div style="float:left;padding:5px;text-align: left;">PCdb <select name="pcdbname"><?php foreach($pcdbversions as $pcdbversion){ echo '<option value="'.$pcdbversion['name'].'">'.$pcdbversion['versiondate'].'</option>';}?><option value="">No validation</option></select></div>
                                    <div style="float:left;padding:5px;text-align: left;">Qdb <select name="qdbname"><?php foreach($qdbversions as $qdbversion){ echo '<option value="'.$qdbversion['name'].'">'.$qdbversion['versiondate'].'</option>';}?><option value="">No validation</option></select></div>
                                    <div style="clear:both;"></div>
                                </div>
                                <div style="padding:25px;"><input name="submit" type="submit" value="Generate ACES xml"/></div>
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
 $logs->logSystemEvent('flatACEStoXML', 0, 'upload page load by:'.$_SERVER['REMOTE_ADDR']);
 include('./includes/storageDisclaimer.php');
?></div><?php  
}
?>
    </body>
</html>