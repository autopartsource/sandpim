<?php
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
$navCategory = 'apps';

$pim = new pim;
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// ip-based ACL enforcement - bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'newApp.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb = new pcdb;
$logs=new logs;

$partcategories = $pim->getPartCategories();
$favoriteparttypes=$pim->getFavoriteParttypes();

if(isset($_POST['partnumber']) && isset($_POST['parttypeid']) && isset($_POST['partcategory']))
{
 $partnumber=trim(strtoupper($_POST['partnumber']));
 $parttypeid=intval($_POST['parttypeid']);
 $partcategory=intval($_POST['partcategory']);
    
 if($pim->validPart($partnumber))
 {// part already esists - re-direct to showPart.php without making a fuss
  echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./showPart.php?partnumber=".$partnumber."'\" /></head><body></body></html>";
  exit;  
 }
 else
 {// part does not already exist. create it
  if($pim->createPart($partnumber, $partcategory, $parttypeid))
  {
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$_SESSION['userid'],'part created by form input',$oid);
   echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./showPart.php?partnumber=".$partnumber."'\" /></head><body></body></html>";
   exit;  
  }
  else
  {// failuer creating part
   $errormessage='part was not created';
  }
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
        
        <!-- Header -->
        <h1>Create A New Part</h1>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div style="padding:10px;">
                        <form method="post">
                            <table class="table" border="1" cellpadding="5">
                                <tr><th>Partnumber</th><td><input type="text" name="partnumber"/></td></tr>
                                <tr><th>Part Type</th><td><select name="parttypeid"><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"><?php echo $parttype['name'];?></option><?php }?></select></td></tr>
                                <tr><th>Part Category</th><td><select name="partcategory"><?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"><?php echo $partcategory['name']; ?></option><?php } ?></select></td></tr>
                            </table>
                            <div style="padding-top:15px;"><input type="submit" name="submit" value="Next"/></div>
                        </form>
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
