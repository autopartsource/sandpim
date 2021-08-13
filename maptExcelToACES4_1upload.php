<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'import';
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

        <!-- Header -->
        <h1>Build ACES (4.1) xml from <a title="This is the template spreadsheet (Excel .xlsx) to use as a guide. Fill in your own application data and upload it using the form on this page." href="./Flat_ACES_4_1_template_A.xlsx">spreadsheet</a> of flat application data</h1>

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <form method="post" action="mapExcelToACES4_1process.php" enctype="multipart/form-data">
                        <div style="padding:5px;text-align: left;"><input type="file" name="fileToUpload" id="fileToUpload" accept=".xlsx"/></div>
                        <div style="padding:5px;text-align: left;"><input type="checkbox" id="showtext" name="showtext"/><label for="showtext">Display output xml in text area (un-check to auto download ACES xml file)</label></div>
                        <div style="padding:5px;text-align: left;"><input type="checkbox" id="ignorelookupfails" name="ignorelookupfails"/><label for="ignorelookupfails">Ignore and exclude apps with lookup fails</label></div>
                        <div style="padding:5px;text-align: left;">VCdb for code lookup <select name="vcdbversion"><?php foreach($vcdbversions as $vcdbversion){ echo '<option value="'.$vcdbversion['name'].'">'.$vcdbversion['versiondate'].'</option>';}?></select></div>
                        <div style="padding:5px;text-align: left;">PCdb for code lookup <select name="pcdbversion"><?php foreach($pcdbversions as $pcdbversion){ echo '<option value="'.$pcdbversion['name'].'">'.$pcdbversion['versiondate'].'</option>';}?></select></div>
                        <div style="padding:5px;text-align: left;">Qdb for code lookup <select name="qdbversion"><?php foreach($qdbversions as $qdbversion){ echo '<option value="'.$qdbversion['name'].'">'.$qdbversion['versiondate'].'</option>';}?></select></div>
                        <div style="padding:5px;"><input name="submit" type="submit" value="Generate ACES xml"/></div>
                    </form>
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