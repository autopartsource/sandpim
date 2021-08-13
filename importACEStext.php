<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'import';

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
0	144067	ITM20200619	1896	22	1	FrontBrakeType|5|3|1~SubModel|20|2|0~		Some nice notes|1|0~Additional Cosmetic notes|2|1~



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
                        <h3 class="card-header text-start">Import applications from structured text</h3>

                        <div class="card-body">
                            <div class="card shadow-sm">
                                <h5 class="card-header">Paste tab-delimited application data for import</h5>
                                <div class="card-body">
                                <form method="post">
                                    <div class="card"><div class="card-header"><i>[cosmetic (0 or 1), BaseVehicleID, Partnumber, PartTypeID, PositionID, Qty, VCdb Attributes, Qdb Qualifiers, Notes]</i></div></div>
                                    <textarea name="input" rows="20" cols="120"></textarea>
                                    <div>Category for part creation <select name="partcategory"><option value="0">Do not create parts</option> <?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"><?php echo $partcategory['name']; ?></option><?php } ?></select></div>
                                    <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                                    
                                    <div class="card"><div class="card-header">VCdb Attributes format: name|value|sequence|cosmetic~name|value|sequence|cosmetic~...</div></div>
                                    <div class="card"><div class="card-header">Qdb Qualifiers format: id|p1|UoM1|p2|UoM2…~id|p1|UoM1|p2|UoM2…~</div></div>
                                    <div class="card"><div class="card-header">Notes Format: text|sequence|cosmetic~text|sequence|cosmetic~</div></div>
                                    
                                </form>
                                </div>
                                </div>
                            </div>
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