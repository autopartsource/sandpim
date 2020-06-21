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

$partcategories = $pim->getPartCategories();



/*
cosmetic	basevid	item	parttypeid	position	qty	vcdbattributes (name|value|sequence|cosmetic)	qdbqualifiers (id|p1|UoM1|p2|UoM2…)	notes (text|sequence|cosmetic)
0	144067	ITM20200619	1896	22	1	FrontBrakeType|5|3|1;SubModel|20|2|0;		Some more nice notes|1|1



*/

if (isset($_POST['input'])) {
    $app_count = $pim->createAppsFromText($_POST['input'],intval($_POST['partcategory']));
    echo $app_count . ' apps created';
}?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
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
                    <div style="padding:10px;"><div>Paste tab-delimited application data for import<br/><i>[cosmetic (0 or 1), BaseVehicleID, Partnumber, PartTypeID, PositionID, Qty, VCdb Attributes, Qdb Qualifiers, Notes]</i></div>
                        <textarea name="input" rows="20" cols="120"></textarea>
                    </div>
                    <div>Category for part creation <select name="partcategory"><option value="0">Do not create parts</option> <?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"><?php echo $partcategory['name']; ?></option><?php } ?></select></div>
                    <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                    <div>VCdb Attributes format: name|value|sequence|cosmetic</div>
                    <div>Qdb Qualifiers format: id|p1|UoM1|p2|UoM2…</div>
                    <div>Notes Format: text|sequence|cosmetic</div>
                </form>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>