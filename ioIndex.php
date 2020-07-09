<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'import/export';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;
$pim=new pim;

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>Import/Export</h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain button" style="flex-direction: column;">
                <div style="padding:10px;"><a href="exportACESselect.php">Export ACES xml</a></div>
                <div style="padding:10px;"><a href="exportPIESselect.php">Export PIES xml</a></div>
                <div style="padding:10px;"><a href="exportForPrintSelect.php">Export for print publishing</a></div>
                <div style="padding:10px;"><a href="exportFlatAppsSelect.php">Export flattened applications files</a></div>
                <div style="padding:10px;"><a href="exportFlatPartsSelect.php">Export flattened parts file</a></div>
                <div style="padding:10px;"><a href="importACESsnippet.php">Import small ACES xml text</a></div>
                <div style="padding:10px;"><a href="importACESupload.php">Upload & import ACES xml file</a></div>
                <div style="padding:10px;"><a href="importACEStext.php">Import applications from structured text</a></div>
                <div style="padding:10px;"><a href="importPartText.php">Import parts from template spreadsheet</a></div>
                <div style="padding:10px;"><a href="importPricesText.php">Import prices from structured text</a></div>
                <div style="padding:10px;"><a href="importPartAttributeText.php">Import part attributes from structured text</a></div>
                <div style="padding:10px;"><a href="importAssetData.php">Import assets from structured text</a></div>
                <div style="padding:10px;"><a href="importInterchangeText.php">Import Competitor Interchange from structured text</a></div>
                <div style="padding:10px;"><a href="importBrandTableText.php">Import Brand Table text</a></div>
                <div style="padding:10px;"><a href="exportCompetitorInterchangeSelect.php">Export Competitor Interchange</a></div>
                <div style="padding:10px;"><a href="backgroundJobs.php">Manage background import/export jobs</a></div>
                <div style="padding:10px;"><a href="rhubarb7_1Index.php">Rhubarb 7.1</a></div>
            </div>
            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body> 
</html>