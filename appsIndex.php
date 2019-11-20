<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'applications';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;
$pim = new pim;

if (isset($_GET['all'])) {
    $makes = $vcdb->getMakes();
} else {
    $makes = $pim->getFavoriteMakes();
    ;
}

$makecount = count($makes);
$groupsize = intval(count($makes) / 6);
$i = 0;
$groupnumber = 0;
$groupedmakes = array();
foreach ($makes as $make) {
    $groupedmakes[$groupnumber][] = $make;
    $i++;
    if ($i > $groupsize) {
        $i = 0;
        $groupnumber++;
    }
}

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
        <h1>Applications</h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain button showRow">
            <?php
                echo '<div style="padding:15px;">';
                foreach ($groupedmakes[0] as $make) {
                    echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid=' . $make['id'] . '">' . $make['name'] . '</a></div>';
                } echo '</div>';
                echo '<div style="padding:15px;">';
                foreach ($groupedmakes[1] as $make) {
                    echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid=' . $make['id'] . '">' . $make['name'] . '</a></div>';
                } echo '</div>';
                echo '<div style="padding:15px;">';
                foreach ($groupedmakes[2] as $make) {
                    echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid=' . $make['id'] . '">' . $make['name'] . '</a></div>';
                } echo '</div>';
                echo '<div style="padding:15px;">';
                foreach ($groupedmakes[3] as $make) {
                    echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid=' . $make['id'] . '">' . $make['name'] . '</a></div>';
                } echo '</div>';
                echo '<div style="padding:15px;">';
                foreach ($groupedmakes[4] as $make) {
                    echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid=' . $make['id'] . '">' . $make['name'] . '</a></div>';
                } echo '</div>';
                echo '<div style="padding:15px;">';
                foreach ($groupedmakes[5] as $make) {
                    echo '<div style="padding:4px;"><a href="mmySelectModel.php?makeid=' . $make['id'] . '">' . $make['name'] . '</a></div>';
                } echo '</div>';
                echo '<div style="clear:both;"></div>';
            ?>
            </div>

            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>
