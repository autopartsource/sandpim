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
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>Export PIES xml</h1>
        
        <div class="wrapper">
         <div class="contentLeft"></div>

         <!-- Main Content -->
         <div class="contentMain">
          <form action="exportPIESstream.php" method="get">
           <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f0f0f0">
               Receiver Profile <select name="receiverprofile"><?php foreach($receiverprofiles as $receiverprofile){?><option value="<?php echo $receiverprofile['id'];?>"><?php echo $receiverprofile['name'];?></option><?php }?></select>
               <div><input type="checkbox" id="ignorelogic" name="ignorelogic"/><label for="ignorelogic">Ignore logic flaws</label></div>
               <div><input type="checkbox" id="showxml" name="showxml"/><label for="showxml">Display XML in a text area</label></div>

               
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