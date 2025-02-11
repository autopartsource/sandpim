<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/configGetClass.php');


$navCategory = 'import';


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$logs = new logs;
$config=new configGet;


$downloadsdirectory=$config->getConfigValue('AutoCareDownloadsDirectory');

$temps=$pim->getAutocareDatabaseList('vcdb');
$vcdbsinstalled=array();
foreach($temps as $temp){$vcdbsinstalled[]=$temp['versiondate'];}


$temps=$pim->getAutocareDatabaseList('pcdb');
$pcdbsinstalled=array();
foreach($temps as $temp){$pcdbsinstalled[]=$temp['versiondate'];}



$temps=$pim->getAutocareDatabaseList('padb');
$padbsinstalled=array();
foreach($temps as $temp){$padbsinstalled[]=$temp['versiondate'];}


$temps=$pim->getAutocareDatabaseList('qdb');
$qdbsinstalled=array();
foreach($temps as $temp){$qdbsinstalled[]=$temp['versiondate'];}




$vcdbsavailable=$pim->getAutoCareReleaseList('VCdb');
$pcdbsavailable=$pim->getAutoCareReleaseList('PCdb');
$padbsavailable=$pim->getAutoCareReleaseList('PAdb');
$qdbsavailable=$pim->getAutoCareReleaseList('Qdb');


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
                <?php if($downloadsdirectory)
                { 
                    $freespace=disk_free_space($downloadsdirectory);
                    echo '<div class="card shadow-sm">';
                        echo '<h3 class="card-header text-start">Free Space</h3>';
                        echo '<div class="card-body">';
                            echo number_format( $freespace/1000000000, 1).'GB'; 
                        echo '</div>';
                            
                    echo '</div>';
                }?>

                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">AutoCare Reference Databases</h3>
                        <div>
                            <div style="float:left;margin:20px;padding:5px;border:1px solid #d0d0d0;">
                                <div style="background-color: #c0c0c0;">VCdb</div>
                                <?php foreach($vcdbsavailable as $vcdbavailable){if(in_array($vcdbavailable['versiondate'], $vcdbsinstalled)){echo  '<div>'.$vcdbavailable['versiondate'].'</div>';}else{echo '<div><a href="./getAutoCareVCdb.php?versiondate='.$vcdbavailable['versiondate'].'">'.$vcdbavailable['versiondate'].'</a></div>';}}?>
                            </div>
                            
                            <div style="float:left;margin:20px;padding:5px;border:1px solid #d0d0d0;">
                                <div style="background-color: #c0c0c0;">PCdb</div>
                                <?php foreach($pcdbsavailable as $pcdbavailable){if(in_array($pcdbavailable['versiondate'], $pcdbsinstalled)){echo  '<div>'.$pcdbavailable['versiondate'].'</div>';}else{echo '<div><a href="./getAutoCarePCdb.php?versiondate='.$pcdbavailable['versiondate'].'">'.$pcdbavailable['versiondate'].'</a></div>';}}?>

                            </div>
                            <div style="float:left;margin:20px;padding:5px;border:1px solid #d0d0d0;">
                                <div style="background-color: #c0c0c0;">PAdb</div>
                                <?php foreach($padbsavailable as $padbavailable){if(in_array($padbavailable['versiondate'], $padbsinstalled)){echo  '<div>'.$padbavailable['versiondate'].'</div>';}else{echo '<div><a href="./getAutoCarePAdb.php?versiondate='.$padbavailable['versiondate'].'">'.$padbavailable['versiondate'].'</a></div>';}}?>
                            </div>
                            <div style="float:left;margin:20px;padding:5px;border:1px solid #d0d0d0;">
                                <div style="background-color: #c0c0c0;">Qdb</div>
                                <?php foreach($qdbsavailable as $qdbavailable){if(in_array($qdbavailable['versiondate'], $qdbsinstalled)){echo  '<div>'.$qdbavailable['versiondate'].'</div>';}else{echo '<div><a href="./getAutoCareQdb.php?versiondate='.$qdbavailable['versiondate'].'">'.$qdbavailable['versiondate'].'</a></div>';}}?>
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