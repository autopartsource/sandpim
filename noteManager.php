<?php
include_once('./class/pimClass.php');

$navCategory = 'settings';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'sandpiper index.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$notes=$pim->getAppNoteAttributeCounts();


?>

<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>

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
                        <h3 class="card-header text-start">Fitment Notes</h3>

                        <div class="card-body scroll">
                            <table class="table">
                                <div id="top"></div>
                                <tr><th>Note</th><th>Use Count</th><th>Action</th></tr>
                                <?php
                                foreach ($notes as $note) 
                                {
                                 echo '<tr><td>'.$note['note'].'</td><td><a href="./appsListBySearch.php?&mode=note&term='. urlencode(base64_encode($note['note'])).'">'.$note['count'].'</a></td><td><a href="./convertNoteToQdb.php?attributeid='.$note['lastid'].'&source=noteManager">To Qdb</td></tr>';
                                }
                                ?>
                            </table>
                            <a href="#top">Back to Top</a>
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
    <?php include('./includes/footer.php'); ?>
    </body>
</html>