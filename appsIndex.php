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
$groupedMakesCount = count($groupedmakes);
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
        <h1>Applications</h1>
        
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <div class="col-xs-12 col-md-2 my-col colLeft">
                </div>

                <!-- Main Content -->
                <div class="col col-xs-12 col-md-8 my-col colMain">
                    <div class="row padding my-row groupCol">
                        <?php
                            for($y = 0;$y < $groupedMakesCount;$y++) {
                                echo '<div class="col my-col">';
                                foreach ($groupedmakes[$y] as $make) {
                                    echo '<div class="groupButton" style="padding:5px;"><a href="mmySelectModel.php?makeid=' . $make['id'] . '"class="btn btn-secondary btn-block my-btn" role="button" aria-disabled="true">' . $make['name'] . '</a></div>';
                                }   
                                echo '</div>';
                            }
                        ?>
                    </div>
                </div>

                <div class="col-xs-12 col-md-2 my-col colRight">
                </div>
            </div>
        </div>
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>
