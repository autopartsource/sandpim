<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'export';

$pim = new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'appSearchIndex.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$user=new user;
$vcdb = new vcdb;
$pcdb = new pcdb;


$favoriteparttypes=$pim->getFavoriteParttypes();
$favoritepositions=$pim->getFavoritePositions();
$lifecyclestatuses=$pcdb->getLifeCycleCodes();


$apps=array();

$appstatus='any';
$appcosmetic='any';
$selectedparttype='any';
$selectedposition='any';
$quantityperapp='any';
$secondsback='604800';
$limit=1000;


if(isset($_GET['submit']) && $_GET['submit']=='Display')
{
 if(in_array($_GET['appstatus'],['any','0','1','2'])){$appstatus=$_GET['appstatus'];}
 if(in_array($_GET['appcosmetic'],['any','0','1'])){$appcosmetic=$_GET['appcosmetic'];}
 if($_GET['parttype']!='any'){$selectedparttype=intval($_GET['parttype']);}
 if($_GET['position']!='any'){$selectedposition=intval($_GET['position']);}
 if($_GET['quantityperapp']!='any'){$quantityperapp=intval($_GET['quantityperapp']);}
 if($_GET['secondsback']!='any'){$secondsback=intval($_GET['secondsback']);}
 
 $apps=$pim->getAppsBySearch($appstatus, $appcosmetic, $selectedparttype, $selectedposition, $quantityperapp, $secondsback);
}


$limited=false;

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
                        <h3 class="card-header text-start">Search applications</h3>

                        <div class="card-body">
                            <form action="appSearchIndex.php" method="get">
                                <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f8f8f8">
                                    <div style="padding: 5px;">
                                        <select name="appstatus">
                                            <option value="any"<?php if($appstatus=='any'){echo ' selected';}?>>Any Status</option>
                                            <option value="0"<?php if($appstatus=='0'){echo ' selected';}?>>Active</option>
                                            <option value="2"<?php if($appstatus=='2'){echo ' selected';}?>>Hidden</option>
                                            <option value="1"<?php if($appstatus=='1'){echo ' selected';}?>>Deleted</option>
                                        </select>
                                    </div>
                                    <div style="padding: 5px;">
                                        <select name="appcosmetic">
                                            <option value="any"<?php if($appcosmetic=='any'){echo ' selected';}?>>Both (cosmetic and non-cosmetic)</option>
                                            <option value="1"<?php if($appcosmetic=='1'){echo ' selected';}?>>Cosmetic</option>
                                            <option value="0"<?php if($appcosmetic=='0'){echo ' selected';}?>>Non-Cosmetic</option>
                                        </select>
                                    </div>

                                    <div style="padding: 5px;">
                                        <select name="parttype"><option value="any"<?php if($selectedparttype=='any'){echo ' selected';}?>>Any Part Type</option><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"<?php if($parttype['id']==$selectedparttype){echo ' selected';}?>><?php echo $parttype['name'];?></option><?php }?></select>
                                    </div>

                                    <div style="padding: 5px;">                                                                                
                                        <select name="position">
                                            <option value="any">Any Position</option><?php foreach ($favoritepositions as $position) { ?> <option value="<?php echo $position['id']; ?>"<?php if ($position['id'] == $selectedposition){echo ' selected';}?>><?php echo $position['name']; ?></option><?php }?>
                                        </select> position
                                    </div>
                                    
                                    <div style="padding: 5px;">
                                        <select name="quantityperapp">
                                            <option value="any"<?php if($quantityperapp=='any'){echo ' selected';}?>>any</option>
                                            <option value="1"<?php if($quantityperapp=='1'){echo ' selected';}?>>1</option>
                                            <option value="2"<?php if($quantityperapp=='2'){echo ' selected';}?>>2</option>
                                            <option value="3"<?php if($quantityperapp=='3'){echo ' selected';}?>>3</option>
                                            <option value="4"<?php if($quantityperapp=='4'){echo ' selected';}?>>4</option>
                                            <option value="5"<?php if($quantityperapp=='5'){echo ' selected';}?>>4</option>
                                            <option value="6"<?php if($quantityperapp=='6'){echo ' selected';}?>>4</option>
                                        </select> quantity per app
                                    </div>
                                                                       
                                    <div style="padding: 5px;">Created or Modified
                                        <select name="secondsback">
                                            <option value="3600"<?php if($secondsback=='3600'){echo ' selected';}?>>in the past hour</option>
                                            <option value="21600"<?php if($secondsback=='21600'){echo ' selected';}?>>in the past 6 hours</option>
                                            <option value="86400"<?php if($secondsback=='86400'){echo ' selected';}?>>in the past 24 hours</option>
                                            <option value="259200"<?php if($secondsback=='259200'){echo ' selected';}?>>in the past 3 days</option>
                                            <option value="604800"<?php if($secondsback=='604800'){echo ' selected';}?>>in the past week</option>
                                            <option value="2592000"<?php if($secondsback=='2592000'){echo ' selected';}?>>in the past month</option>
                                            <option value="7776000"<?php if($secondsback=='7776000'){echo ' selected';}?>>in the 3 months</option>
                                            <option value="15552000"<?php if($secondsback=='15552000'){echo ' selected';}?>>in the 6 months</option>
                                            <option value="31536000"<?php if($secondsback=='31536000'){echo ' selected';}?>>in the past year</option>
                                        </select>
                                    </div>

                                    
                                    <div style="padding: 20px;">                                    
                                        <input type="submit" name="submit" value="Display"/>
                                        <input type="submit" name="submit" value="Export"/>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <?php if(count($apps)){?>
                    <table class="table">
                        <tr><th>Make</th><th>Model</th><th>Year</th><th>Part Type</th><th>Position</th><th>Qty</th><th>Part</th><th>Last Touched</th></tr>
                        
                        <?php foreach($apps as $index=>$app)
                        {
                         if($index>=$limit){$limited=true; break;}
                         
                         $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
                         
                         echo '<tr><td>'.$mmy['makename'].'</td><td>'.$mmy['modelname'].'</td><td>'.$mmy['year'].'</td><td>'.$pcdb->parttypeName($app['parttypeid']).'</td><td>'.$pcdb->positionName($app['positionid']).'</td><td>'.$app['quantityperapp'].'</td><td><a href="./showPart.php?partnumber='.$app['partnumber'].'">'.$app['partnumber'].'</a></td><td><a href="./appHistory.php?appid='.$app['id'].'">'.$app['latesttouch'].'</a></td></tr>';
                        }?>                   
                    </table>
                    
                    <?php if($limited){echo '<div>Display results are limited to '.$limit.' records. Use the export feature to see all in a spreadsheet.</div>';}?>
                    
                    <?php }?>
                    
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