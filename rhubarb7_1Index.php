<?php
$navCategory = 'import/export';
session_start();
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
        <h1>Rhubarb 7.1</h1>
        <h2>Tools for converting between Excel spreadsheets and PIES xml</h2>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain button" style="flex-direction: column;">
                <div style="padding:10px;"><a href="convertExcelToPIES7_1upload.php">Create PIES xml from spreadsheet</a></div>
                <div style="padding:10px;"><a href="convertPIES7_1toExcelUpload.php">Flatten PIES xml to spreadsheet</a></div>
            </div>
            
            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
<?php 
if (isset($_SESSION['userid']))
{
 include('./includes/footer.php');
}
else
{
?><div style="font-size: .75em; font-style: italic; color: #808080;"><?php  
 $logs->logSystemEvent('rhubarb', 0, 'index page load by:'.$_SERVER['REMOTE_ADDR']);
 include('./includes/storageDisclaimer.php');
?></div><?php  
}
?>
    </body> 
</html>