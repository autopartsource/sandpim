<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pimClass.php');
$navCategory = 'import/export';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;
$pim = new pim;

$partcategories = $pim->getPartCategories();
?>

<!DOCTYPE html>
<html>
    <head>
        <?php include('/var/www/html/includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>Export PIES xml - select source and options</h1>
        
        <div class="wrapper">
         <div class="contentLeft"></div>

         <!-- Main Content -->
         <div class="contentMain">
          <form action="exportPIESstream.php" method="post">
           <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f0f0f0">
               Export data for these parts
               <div><textarea style="width: 95%;height:100px;" name="parts"/></textarea></div>
               <input type="submit" name="submit" value="Export"/>
               <input type="hidden" name="exporttype" value="itemlist"/>
           </div>
          </form>
          <form action="exportPIESstream.php" method="post">
           <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f0f0f0">
               Export data for these part type id's
               <div><textarea style="width: 95%;height:100px;" name="parts"/></textarea></div>
               <input type="submit" name="submit" value="Export"/>
           </div>
          </form>
         </div>
         <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>