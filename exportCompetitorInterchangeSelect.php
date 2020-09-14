<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
$navCategory = 'import/export';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$interchange = new interchange;
$competitors=$interchange->getCompetitors();
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
        <h1>Export competitor interchange to a spreadsheet</h1>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <form action="exportCompetitorInterchangeStream.php" method="get">
                        <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f8f8f8">
                            <div style="padding: 10px;">Competitor</div>
                            <select name="competitorBrandAAIAID"><?php foreach ($competitors as $competitor) { ?><option value="<?php echo $competitor['brandAAIAID']; ?>"><?php echo $competitor['name']; ?></option><?php } ?></select>
                            <input type="submit" name="submit" value="Export"/>
                        </div>
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
        <?php include('./includes/footer.php'); ?>
    </body>
</html>