<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsClass.php');
$navCategory = 'settings';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pim= new pim;
$user= new user;
$configGet= new configGet;
$configSet= new configSet;
$logs= new logs;



$userid=intval($_GET['userid']);

$error='';

if(isset($_POST['submit']) && $_POST['submit']=='Update Password')
{
 if(strlen($_POST['password'])>=8)
 {
  if($_POST['password']== $_POST['repassword'])
  {
   $pepper = $configGet->getConfigValue('pepper');
   if(!$pepper)
   { // new installation - pepper value is not present - create it
    $pepper=bin2hex(random_bytes(16));
    $configSet->setConfigValue('pepper',$pepper);
   }
   $pwd = $_POST['password'];
   $pwd_peppered = hash_hmac("sha256", $pwd, $pepper);
   $pwd_hashed = password_hash($pwd_peppered, PASSWORD_ARGON2ID);
   $user->updateUserPassword($userid,$pwd_hashed);
   $logs->logSystemEvent('userchange',$_SESSION['userid'],'password changed for:'.$user->realNameOfUserid($userid));
   $error='password successfully changed';
  }
  else
  { // mismatch in confirmation password
   $error='passwords do not match - no change made';
  }
 }
 else
 { // too short
  $error='password is too short - no change made';
 }
}


if(isset($_POST['submit']) && $_POST['submit']=='Update Name')
{
 if(strlen($_POST['realname'])>0)
 {
  $user->updateUserRealname($userid,$_POST['realname']);
  $logs->logSystemEvent('userchange',$_SESSION['userid'],'real name changed for:'.$user->realNameOfUserid($userid));
  $error='user real name successfully changed';
 }
 else
 { // user real name too short
  $error='user realname can not be blank';
 }
}

$allowedpartcategories=$user->getUserVisiblePartcategories($userid);
$idkeyedallowlist=array(); foreach($allowedpartcategories as $allowed){$idkeyedallowlist[$allowed['id']]='';}
$partcategories=$pim->getPartCategories();
$user->getUserByID($userid);

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        <script>
            function addRemovePartcategory(userid,partcategory)
            {
             if(document.getElementById('partcategory_'+partcategory).checked) 
             { // category has been clicked on 
         //     console.log(partcategory);
              var xhr = new XMLHttpRequest();
              
              document.getElementById('categorySelectButton_'+partcategory).className = "btn btn-success";
              console.log(document.getElementById('categorySelectButton_'+partcategory).className);
              
              xhr.open('GET', 'ajaxAddRemoveUserPartcategory.php?userid='+userid+'&partcategory='+partcategory+'&permissionname=canView&action=add');
              xhr.send();
             }
             else
             { // category has been clicked off
              var xhr = new XMLHttpRequest();
              
              document.getElementById('categorySelectButton_'+partcategory).className = "btn btn-secondary";
              console.log(document.getElementById('categorySelectButton_'+partcategory).className);
              
              xhr.open('GET', 'ajaxAddRemoveUserPartcategory.php?userid='+userid+'&partcategory='+partcategory+'&permissionname=canView&action=remove');
              xhr.send();
             }
            }

        </script>
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
                    <div class="row padding my-row">
                        <div class="col-md-6 my-col">
                            <div class="card shadow-sm">
                                <!-- Header -->
                                <h3 class="card-header text-left">Edit User Account - <?php echo $user->name;?></h3>

                                <div class="card-body">
                                    <form method="post" action="./user.php?userid=<?php echo $userid; ?>">
                                        <div style="width:400px;padding:3px;border:1px solid;">
                                            <div style="padding:3px;">
                                                <div style="float:left;">Real Name</div>
                                                <div style="float:right;">
                                                    <input type="text" name="realname" value="<?php echo $user->name; ?>"/>
                                                    <input type="submit" name="submit" value="Update Name"/>
                                                </div>
                                                <div style="clear:both;"></div>
                                            </div>
                                            <div style="padding:3px;"><div style="float:left;">Password</div> <div style="float:right;"><input type="password" name="password"/></div><div style="clear:both;"></div></div>
                                            <div style="padding:3px;"><div style="float:left;">Confirm Password</div> <div style="float:right;"><input type="password" name="repassword"/></div><div style="clear:both;"></div></div>
                                            <div style="padding:3px;"><div style="float:right;"><input type="submit" name="submit" value="Update Password"/></div><div style="clear:both;"></div></div>
                                            <div style="padding:4px;color:red;"><?php echo $error; ?></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 my-col">
                            <div class="card shadow-sm">
                                <!-- Header -->
                                <h3 class="card-header text-left">Application Category Permissions</h3>

                                <div class="card-body">
                                    <?php foreach($partcategories as $partcategory){
                                        $checked=''; 
                                        if(array_key_exists($partcategory['id'],$idkeyedallowlist)){
                                            $checked='checked';
                                            $buttonClass = 'btn btn-success';
                                        } 
                                        else {
                                            $buttonClass = 'btn btn-secondary';
                                        }
                                        echo '<div><label id="categorySelectButton_' . $partcategory['id'] . '" class="'. $buttonClass .'" for="partcategory_'.$partcategory['id'].'">'.$partcategory['name'].'<input type="checkbox" id="partcategory_'.$partcategory['id'].'" onclick="addRemovePartcategory(\''.$userid.'\',\''.$partcategory['id'].'\')" name="partcategory_'.$partcategory['id'].'" '.$checked.' style="display:none"></label></div>';}?>
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