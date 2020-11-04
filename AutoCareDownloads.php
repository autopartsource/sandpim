<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'import/export';


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$logs = new logs;


$temps=$pim->getAutocareDatabaseList('vcdb');
$vcdbsinstalled=array();
foreach($temps as $temp){$vcdbsinstalled[]=$temp['versiondate'];}


$temps=$pim->getAutocareDatabaseList('pcdb');
$pcdbsinstalled=array();
foreach($temps as $temp){$pcdbsinstalled[]=$temp['versiondate'];}




$padbsinstalled=$pim->getAutocareDatabaseList('padb');
$qdbsinstalled=$pim->getAutocareDatabaseList('qdb');



$vcdbsavailable=$pim->getAutoCareReleaseList('VCdb');
$pcdbsavailable=$pim->getAutoCareReleaseList('PCdb');


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
                        <h3 class="card-header text-left">AutoCare Reference Databases</h3>
                        <div>
                            <div style="float:left;margin:20px;padding:5px;border:1px solid #d0d0d0;">
                                <div style="background-color: #c0c0c0;">VCdb</div>
                                <?php foreach($vcdbsavailable as $vcdbavailable){if(in_array($vcdbavailable['versiondate'], $vcdbsinstalled)){echo  '<div>'.$vcdbavailable['versiondate'].'</div>';}else{echo '<div><a href="./getAutoCareVCdb.php?versiondate='.$vcdbavailable['versiondate'].'">'.$vcdbavailable['versiondate'].'</a></div>';}}?>
                            </div>
                            
                            <div style="float:left;margin:20px;padding:5px;border:1px solid #d0d0d0;">
                                <div style="background-color: #c0c0c0;">PCdb</div>
                                <?php foreach($pcdbsavailable as $pcdbavailable){if(in_array($pcdbavailable['versiondate'], $pcdbsinstalled)){echo  '<div>'.$pcdbavailable['versiondate'].'</div>';}else{echo '<div><a href="./getAutoCarePCdb.php?versiondate='.$pcdbavailable['versiondate'].'">'.$pcdbavailable['versiondate'].'</a></div>';}}?>

                            </div>
                            <div style="float:left;margin:20px;padding:5px;border:1px solid #d0d0d0;"><div style="background-color: #c0c0c0;">PAdb</div>
                                <?php foreach($padbsinstalled as $padbinstalled){echo '<div>'.$padbinstalled['versiondate'].'</div>';}?>
                            </div>
                            <div style="float:left;margin:20px;padding:5px;border:1px solid #d0d0d0;"><div style="background-color: #c0c0c0;">Qdb</div>
                                <?php foreach($qdbsinstalled as $qdbinstalled){echo '<div>'.$qdbinstalled['versiondate'].'</div>';}?>
                            </div>
                            <div style="clear:both;"></div>
                        </div>
                        <div class="card-body">

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