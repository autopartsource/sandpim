<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'import/export';
session_start();

$logs=new logs();

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
        <div style="padding:5px;"><img src="./rhubarb_from_pies.png" width="120"/></div>
        <h1>Flatten PIES (7.1) xml file to an Excel spreadsheet</h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <form method="post" action="convertPIES7_1toExcelProcess.php" enctype="multipart/form-data">
                    <div style="padding:5px;text-align: left;"><input type="file" name="fileToUpload" id="fileToUpload" accept=".xml"/></div>
                    <div style="padding:5px;text-align: left;"><input type="checkbox" id="ignorelogic" name="ignorelogic"/><label for="ignorelogic">Ignore logic flaws</label></div>
                    <div style="padding:5px;"><input name="submit" type="submit" value="Generate Excel file"/></div>

                </form>
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
 $logs->logSystemEvent('rhubarb', 0, 'upload page load by:'.$_SERVER['REMOTE_ADDR']);
 include('./includes/storageDisclaimer.php');
?></div><?php  
}
?>
    </body>
</html>