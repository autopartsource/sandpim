<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'import/export';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;
$pim = new pim;

$partcategories = $pim->getPartCategories();
$receiverprofiles=$pim->getReceiverprofiles();
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
               Export data for this list of part numbers (header elements are pulled from the "Global" Receiver template)
               <div><textarea style="width: 95%;height:100px;" name="parts"/></textarea></div>
               Profile to use for header and marketing copy <select name="receiverprofile"><?php foreach($receiverprofiles as $receiverprofile){?><option value="<?php echo $receiverprofile['id'];?>"><?php echo $receiverprofile['name'];?></option><?php }?></select>
               <input type="submit" name="submit" value="Export"/>
               <input type="hidden" name="exporttype" value="itemlist"/>
           </div>
          </form>
          <form action="exportPIESstream.php" method="post">
           <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f0f0f0">
               Export data for these part type id's from parts table (header elements are pulled from the "Global" Receiver template)
               <div><textarea style="width: 95%;height:100px;" name="parttypes"/></textarea></div>
               <input type="submit" name="submit" value="Export"/>
           </div>
          </form>
          <form action="exportPIESstream.php" method="post">
           <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f0f0f0">
               Export data for these part category id's (header elements are pulled from the "Global" Receiver template)
               <div><textarea style="width: 95%;height:100px;" name="partcategories"/></textarea></div>
               <input type="submit" name="submit" value="Export"/>
           </div>
          </form>
          <form action="exportPIESstream.php" method="post">
           <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f0f0f0">
               Export data for parts found in applications that have these application category id's (header elements are pulled from the "Global" Receiver template)
               <div><textarea style="width: 95%;height:100px;" name="appcategories"/></textarea></div>
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