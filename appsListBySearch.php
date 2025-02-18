<?php
include_once('./class/vcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
$navCategory = 'applications';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'appsListBySearch.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$userid=$_SESSION['userid'];

$vcdb=new vcdb;
$qdb=new qdb;
$pcdb=new pcdb;
$user=new user;

$searchmode=''; $searchterm='';


if(isset($_GET['term']) && isset($_GET['mode']))
{
 switch($_GET['mode'])
 {
  case 'note':
   $searchmode='note';
   $searchterm=base64_decode(urldecode($_GET['term']));
   $apps=$pim->getAppsByAttribute('note', $searchterm);   
   
   break;
     
  default: break;
 }   
}


?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        <script>
        </script>
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
                    <?php echo '<div style="padding:10px;">Search Term ('.$searchmode.'): *'.$searchterm.'*</div>';
                    
                    echo '<table><tr><th>App ID</th><th>Vehilce</th><th>Position</th><th>Partnumber</th><th>Qualifiers</th><th>Cosmetics</th></tr>';
                    foreach($apps as $app)
                    {
                     $niceattributes = array();
                     foreach ($app['attributes'] as $appattribute) 
                     {
                      if($appattribute['type'] == 'vcdb') {$niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']);}
                      if($appattribute['type'] == 'qdb') {$niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $qdb->qualifierText($appattribute['name'], explode('~', str_replace('|','',$appattribute['value']))), 'cosmetic' => $appattribute['cosmetic']);}
                      if($appattribute['type'] == 'note') {$niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']);}
                     }
                     $nicefitmentarray = array(); foreach ($niceattributes as $niceattribute){$nicefitmentarray[] = $niceattribute['text'];}
                        
                     echo '<tr><td><a href="./showApp.php?appid='.$app['id'].'">'.$app['id'].'</a></td><td>'.$vcdb->niceMMYofBasevid($app['basevehicleid']).'</td><td>'.$pcdb->positionName($app['positionid']).'</td><td><a href="./showPart.php?partnumber='.urlencode($app['partnumber']).'">'.$app['partnumber'].'</a></td><td>'.implode('; ', $nicefitmentarray).'</td><td>'.$app['cosmeticattributecount'].'</td></tr>';                        
                    }
                    echo '</table>';

                   
                    ?>
                </div>
                
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