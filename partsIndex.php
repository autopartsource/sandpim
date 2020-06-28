<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'parts';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;
$pcdb = new pcdb;
$pim = new pim;

$parts =array();

$lifecyclestatuses=$pcdb->getLifeCycleCodes();
$validlifecyclestatuscodes=array(); foreach ($lifecyclestatuses as $lifecyclestatus) {$validlifecyclestatuscodes[]=$lifecyclestatus['code'];}

$partcategories = $pim->getPartCategories();
$validpartcategoryids=array(); foreach ($partcategories as $partcategory) {$validpartcategoryids[]=$partcategory['id'];}

$favoriteparttypes=$pim->getFavoriteParttypes();

if(isset($_GET['partnumber']) && strlen($_GET['partnumber']) <= 20) 
{
 $searchtype = 'equals'; if (isset($_GET['searchtype']) && ($_GET['searchtype'] == 'contains' || $_GET['searchtype'] == 'startswith' || $_GET['searchtype'] == 'endswith')) {$searchtype = $_GET['searchtype'];}
 $partnumber = strtoupper($_GET['partnumber']);

 $limit = 10; if(isset($_GET['limit']) && intval($_GET['limit'])>0){$limit=intval($_GET['limit']);}
 
 $lifecyclestatus=$_GET['lifecyclestatus']; if(!in_array($_GET['lifecyclestatus'], $validlifecyclestatuscodes)){$lifecyclestatus='any';} 
 $partcategory=$_GET['partcategory']; if(!in_array($_GET['partcategory'], $validpartcategoryids)){$partcategory='any';} 
 $parttypeid='any'; if(intval($_GET['parttypeid'])>0){$parttypeid=intval($_GET['parttypeid']);}
        
 $parts = $pim->getParts($partnumber, $searchtype, $partcategory, $parttypeid, $lifecyclestatus, $limit);
}



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
        <h1>Parts</h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain" style="flex-direction: column;">
              <div style="text-align: left;padding: 10px;"><a href="./newPart.php">Create a new part</a></div>
                
              <div style="border: solid #808080 thin;padding: 10px;">
               <div style="font-size: 150%;">Find existing parts</div>
               <div style="text-align: left;padding: 5px;">
                <form method="get" action="partsIndex.php">
                 <div style="padding:3px;">Show parts <select name="searchtype"><option value="startswith">starting with</option><option value="contains">containing</option><option value="endswith">ending with</option><option value="equals">exactly equal to</option></select> <input type="text" name="partnumber" value="<?php if(isset($_GET['partnumber'])){echo substr(strtoupper(trim($_GET['partnumber'])),0,20); }?>"/></div>
                 <div style="padding:3px;">In <select name="partcategory"><option value="any">Any Category</option><?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>" <?php if(isset($_GET['partcategory']) && $_GET['partcategory']==$partcategory['id']){echo ' selected';}?>><?php echo $partcategory['name']; ?></option><?php } ?> </select></div>
                 <div style="padding:3px;">With <select name="parttypeid"><option value="any">Any Part Type</option><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>" <?php if(isset($_GET['parttypeid']) && $_GET['parttypeid']==$parttype['id']){echo ' selected';}?>><?php echo $parttype['name'];?></option><?php }?></select></div>
                 <div style="padding:3px;">With <select name="lifecyclestatus"><option value="any">Any Status</option><?php foreach($lifecyclestatuses as $lifecyclestatus){?> <option value="<?php echo $lifecyclestatus['code'];?>" <?php if(isset($_GET['lifecyclestatus']) && $_GET['lifecyclestatus']==$lifecyclestatus['code']){echo ' selected';}?>><?php echo $lifecyclestatus['description'];?></option><?php }?></select></div>
                 <div style="padding:3px;">Limit results to <select name="limit"><option value="10">10</option><option value="20" selected>20</option><option value="50">50</option><option value="100">100</option><option value="200">200</option><option value="500">500</option></select></div>
                 <div style="padding:3px;"><input type="submit" name="submit" value="Search"/></div>
                </form>
               </div>
              </div>

                <?php if (count($parts) > 0) { ?>
                    <div style="padding-top:10px;">
                        <table border="1">
                            <tr><th>Part Number</th><th>Type</th><th>Category</th><th>Description</th><th>Status</th></tr>
                            <?php
                            foreach ($parts as $part) {
                                echo '<tr><td><a href="showPart.php?partnumber=' . $part['partnumber'] . '">' . $part['partnumber'] . '</a></td><td>' . $pcdb->parttypeName($part['parttypeid']) . '</td><td>' . $part['partcategoryname'] . '</td><td>'.$part['description'].'</td><td>' . $pcdb->lifeCycleCodeDescription($part['lifecyclestatus']) . '</td><tr>';
                            }
                            ?>
                        </table>
                    </div>
                <?php } ?>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>