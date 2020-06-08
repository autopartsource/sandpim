<?php
include_once('./class/pimClass.php');
$navCategory = 'import/export';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;

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
        <h1>Export ACES xml - select source and options</h1>
        
        <div class="wrapper">
         <div class="contentLeft"></div>

         <!-- Main Content -->
         <div class="contentMain">
          <form action="exportACESstream.php" method="post">
           <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f0f0f0">
               Receiver Profile <select name="receiverprofile"><?php foreach($receiverprofiles as $receiverprofile){?><option value="<?php echo $receiverprofile['id'];?>"><?php echo $receiverprofile['name'];?></option><?php }?></select>
               <input type="submit" name="submit" value="Export"/>
               <input type="hidden" name="exporttype" value="profile"/>
           </div>
          </form>
         </div>
         <div class="contentRight"></div>
        </div>
        
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>