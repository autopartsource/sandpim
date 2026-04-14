<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
$navCategory = 'parts';

$pim = new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'partsIndex.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;
$pcdb = new pcdb;

$parts =array();

$lifecyclestatuses=$pcdb->getLifeCycleCodes();
$validlifecyclestatuscodes=array(); foreach ($lifecyclestatuses as $lifecyclestatus) {$validlifecyclestatuscodes[]=$lifecyclestatus['code'];}

$partcategories = $pim->getPartCategories();
$validpartcategoryids=array(); foreach ($partcategories as $partcategory) {$validpartcategoryids[]=$partcategory['id'];}

$favoriteparttypes=$pim->getFavoriteParttypes();
$searchtype = 'contains';
$availdays=9999;
$limit = 20;
 
if(isset($_GET['partnumber']) && strlen($_GET['partnumber']) <= 20) 
{
 if (isset($_GET['searchtype']) && ($_GET['searchtype'] == 'contains' || $_GET['searchtype'] == 'startswith' || $_GET['searchtype'] == 'endswith')) {$searchtype = $_GET['searchtype'];}

 $partnumber = strtoupper($_GET['partnumber']);
 $basepart = strtoupper($_GET['basepart']);
 
 if(isset($_GET['limit']) && intval($_GET['limit'])>0){$limit=intval($_GET['limit']);} 

 if(isset($_GET['availdays'])){$availdays=intval($_GET['availdays']);}
 
 $lifecyclestatus=$_GET['lifecyclestatus']; if(!in_array($_GET['lifecyclestatus'], $validlifecyclestatuscodes)){$lifecyclestatus='any';} 
 $partcategory=$_GET['partcategory']; if(!in_array($_GET['partcategory'], $validpartcategoryids)){$partcategory='any';} 
 $parttypeid='any'; if(intval($_GET['parttypeid'])>0){$parttypeid=intval($_GET['parttypeid']);}

 $partstemp = $pim->getParts($partnumber, $searchtype, $partcategory, $parttypeid, $lifecyclestatus, $basepart, 100000);
 $thresholdepoch=time()-(24*3600*$availdays);

 foreach($partstemp as $part)
 {
  $datetemp=$part['availableDate']; if(strlen($datetemp)!=10){$datetemp='1995-01-01';}
  
//  echo '*'.$datetemp.'*<br/>';
  if(strtotime($datetemp) < $thresholdepoch){continue;}
  
  $parts[]=$part;
  
  if(count($parts)>=$limit){break;}
 }

 
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

        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                            
                    <div class="card text-start">
                        <h6 class="card-header">Search Our Parts</h6>
                        <div class="card-body">
                            <form method="get" action="partsIndex.php">
                            <div style="padding:3px;">Show part numbers 
                                <select name="searchtype">
                                    <option value="startswith" <?php if($searchtype=='startswith'){echo ' selected';}?>>starting with</option>
                                    <option value="contains" <?php if($searchtype=='contains'){echo ' selected';}?>>containing</option>
                                    <option value="endswith" <?php if($searchtype=='endswith'){echo ' selected';}?>>ending with</option>
                                    <option value="equals" <?php if($searchtype=='equals'){echo ' selected';}?>>exactly equal to</option>
                                </select>
                                <input type="text" name="partnumber" value="<?php if(isset($_GET['partnumber'])){echo substr(strtoupper(trim($_GET['partnumber'])),0,20); }?>"/></div>

                            <div style="padding:3px;">Made Available 
                                <select name="availdays">
                                    <option value="9999"<?php if($availdays==9999){echo ' selected';}?>>Ever</option> 
                                    <option value="7"<?php if($availdays==7){echo ' selected';}?>>In the past week</option> 
                                    <option value="30"<?php if($availdays==30){echo ' selected';}?>>In the past month</option> 
                                    <option value="90"<?php if($availdays==90){echo ' selected';}?>>In the past 90 days</option> 
                                    <option value="365"<?php if($availdays==365){echo ' selected';}?>>In the past year</option> 
                                </select>
                            </div>
                                
                                
                            <div style="padding:3px;">In Category 
                                <select name="partcategory">
                                    <option value="any">Any Category</option><?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>" <?php if(isset($_GET['partcategory']) && $_GET['partcategory']==$partcategory['id']){echo ' selected';}?>><?php echo $partcategory['name']; ?></option><?php } ?> 
                                </select>
                            </div>
                                
                            <div style="padding:3px;">Of type <select name="parttypeid"><option value="any">Any Part Type</option><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>" <?php if(isset($_GET['parttypeid']) && $_GET['parttypeid']==$parttype['id']){echo ' selected';}?>><?php echo $parttype['name'];?></option><?php }?></select></div>
                            <div style="padding:3px;">With status <select name="lifecyclestatus"><option value="any">Any Status</option><?php foreach($lifecyclestatuses as $lifecyclestatus){?> <option value="<?php echo $lifecyclestatus['code'];?>" <?php if(isset($_GET['lifecyclestatus']) && $_GET['lifecyclestatus']==$lifecyclestatus['code']){echo ' selected';}?>><?php echo $lifecyclestatus['description'];?></option><?php }?></select></div>
                            <div style="padding:3px;">With basepart <input type="text" name="basepart" value="<?php if(isset($_GET['basepart'])){echo substr(strtoupper(trim($_GET['basepart'])),0,20); }?>"/></div>
                            <div style="padding:3px;">Limit results to <select name="limit"><option value="10"<?php if($limit==10){echo ' selected';}?>>10</option><option value="20"<?php if($limit==20){echo ' selected';}?>>20</option><option value="50"<?php if($limit==50){echo ' selected';}?>>50</option><option value="100"<?php if($limit==100){echo ' selected';}?>>100</option><option value="200"<?php if($limit==200){echo ' selected';}?>>200</option><option value="500"<?php if($limit==500){echo ' selected';}?>>500</option><option value="1000"<?php if($limit==1000){echo ' selected';}?>>1000</option></select></div>
                            <div style="padding:3px;"><input type="submit" name="submit" value="Search"/></div>
                        </form>
                        </div>
                    </div>

                    <?php if (count($parts) > 0) { ?>
                    <div class="card">
                        <h6 class="card-header">Search Results <?php echo '<span class="badge bg-primary rounded-pill">'.count($parts).'</span>'; ?></h6>
                        <div class="card-body scroll">
                            <table class="table" border="1">
                                <tr><th>Part Number</th><th>Type</th><th>Category</th><th>Description</th><th>Status</th></tr>
                                <?php
                                foreach ($parts as $part)
                                {
                                    echo '<tr><td><a href="showPart.php?partnumber=' . urlencode($part['partnumber']) . '" class="btn btn-secondary">' . $part['partnumber'] . '</a></td><td>' . $pcdb->parttypeName($part['parttypeid']) . '</td><td>' . $part['partcategoryname'] . '</td><td>'.$part['description'].'</td><td>' . $pcdb->lifeCycleCodeDescription($part['lifecyclestatus']) . '</td><tr>';
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                    <?php 
                    } else { // no results found
                            if(isset($_GET['submit']))
                            { // user submitted a search
                                echo '<hr>';
                                echo '<div class="alert alert-danger m-2">No Results Found</div>';
                            }
                        }
                    ?>
                    
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight"></div>
                
            </div>
        </div>    
        <!-- End of Content Container -->
        
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>