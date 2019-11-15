<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pcdbClass.php');
include_once('/var/www/html/class/pimClass.php');
$navCategory = 'parts';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;
$pcdb = new pcdb;
$pim = new pim;

if (isset($_GET['partnumber']) && strlen($_GET['partnumber']) <= 20) {
    $searchtype = 'equals';
    if (isset($_GET['searchtype']) && ($_GET['searchtype'] == 'contains' || $_GET['searchtype'] == 'startswith')) {
        $searchtype = $_GET['searchtype'];
    }
    $partnumber = strtoupper($_GET['partnumber']);
    $limit = 30;
    $parts = $pim->getParts($partnumber, $searchtype, $limit);
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
        <h1>Parts</h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain" style="flex-direction: column;">
                <form method="get" action="partsIndex.php">
                    Show part numbers <select name="searchtype"><option value="equals">that are exactly</option><option value="startswith">that starts with</option><option value="contains">contains</option></select> 
                    <input type="text" name="partnumber" />

                    in category <select name="partcategory"><option value="any">-- Any --</option></select> 

                    <input type="submit" name="submit" value="Search"/>
                </form>

                <?php if (count($parts) > 0) { ?>
                    <div style="padding-top:10px;">
                        <table border="1">
                            <tr><th>Part Number</th><th>Type</th><th>Category</th><th>Status</th></tr>
                            <?php
                            foreach ($parts as $part) {
                                echo '<tr><td><a href="showPart.php?partnumber=' . $part['partnumber'] . '">' . $part['partnumber'] . '</a></td><td>' . $pcdb->parttypeName($part['parttypeid']) . '</td><td>' . $part['partcategoryname'] . '</td><td>' . $part['lifecyclestatus'] . '</td><tr>';
                            }
                            ?>
                        </table>
                    </div>
                <?php } ?>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>