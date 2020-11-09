<?php

include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

// login check is intentionally left out so that this page can stand alone as an un-authenticaeted utility
$navCategory = 'utilities';

session_start();

$pim = new pim();
$logs = new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'UUIDgenerator.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$count=1;
if(isset($_GET['count'])){
$count=intval($_GET['count']);
}
?>
<!DOCTYPE html>
<html lang="en" xml:lang="en">
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php if (isset($_SESSION['userid'])){include('topnav.php');} ?>

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-left">UUID Generator</h3>
                            <form>
                                <div style="padding:5px;">
                                <input name="submit" type="submit" value="Generate"/> <select name="count"><option value="1">1</option><option value="10">10</option><option value="100">100</option></select> UUIDs</div>
                            </form>
                        <div class="card-body">
                            <?php for($i=0; $i<=$count-1; $i++)
                            {
                                echo '<div>'.$pim->uuidv4().'</div>';
                            }
                            ?>
                        </div>
                    </div>
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
 $logs->logSystemEvent('utilities', 0, 'UUID genertor used by:'.$_SERVER['REMOTE_ADDR'].' to generate '.$count.' UUIDs');
 echo 'These UUIDs are are generated from the Linux system /dev/urandom';
?></div><?php  
}
?>

        
        
        
        
    </body>
</html>