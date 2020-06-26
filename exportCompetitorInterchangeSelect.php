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
        
        <div class="wrapper">
         <div class="contentLeft"></div>

         <!-- Main Content -->
         <div class="contentMain">
          <form action="exportCompetitorInterchangeStream.php" method="get">
           <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f8f8f8">
            <div style="padding: 10px;">Competitor</div>
            <select name="competitorBrandAAIAID"><?php foreach($competitors as $competitor){?><option value="<?php echo $competitor['brandAAIAID'];?>"><?php echo $competitor['name'];?></option><?php }?></select>
            <input type="submit" name="submit" value="Export"/>
           </div>
          </form>
         </div>
         <div class="contentRight"></div>
        </div>
        
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>