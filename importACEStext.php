<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'import/export';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$v = new vcdb;
$pim = new pim;
/*
categoryid	cosmetic	basevid	item	parttypeid	position	qty	vcdbattributes (name|value|sequence|cosmetic)	qdbqualifiers (id|p1|UoM1|p2|UoM2…)	notes (text|sequence|cosmetic)
16	0	144067	ITM80	1896	22	1	FrontBrakeType|5|3|1;SubModel|20|2|0;		Some more Notes|1|1



*/

if (isset($_POST['input'])) {
    $app_count = $pim->createAppsFromText($_POST['input']);
    echo $app_count . ' apps created';
}

$appcategories = $pim->getAppCategories();
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
        <h1>Import applications from structured text</h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <form method="post">
                    <div style="padding:10px;"><div>Paste tab-delimited application data for import<br/><i>(categoryid, cosmetic, basevid, item, parttypeid, position, qty, vcdb attributes, qdb qualifiers, notes)</i></div>
                        <textarea name="input" rows="20" cols="100"></textarea>
                    </div>
                    <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                </form>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>