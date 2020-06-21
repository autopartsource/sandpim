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
              xhr.open('GET', 'ajaxAddRemoveUserPartcateory.php?userid='+userid+'&partcategory='+partcategory+'&permissionname=canView&action=add');
              xhr.send();
             }
             else
             { // category has been clicked off
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddRemoveUserPartcateory.php?userid='+userid+'&partcategory='+partcategory+'&permissionname=canView&action=remove');
              xhr.send();
             }
            }

        </script>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>Edit User Account - <?php echo $user->name;?></h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain" >
                <div style="padding:10px;">
                    <form method="post" action="./user.php?userid=<?php echo $userid;?>">
                        <div style="width:400px;padding:3px;border:1px solid;">
                         <div style="padding:3px;">
                          <div style="float:left;">Real Name</div>
                          <div style="float:right;">
                           <input type="text" name="realname" value="<?php echo $user->name;?>"/>
                           <input type="submit" name="submit" value="Update Name"/>
                          </div>
                          <div style="clear:both;"></div>
                         </div>


                         <div style="padding:3px;"><div style="float:left;">Password</div> <div style="float:right;"><input type="password" name="password"/></div><div style="clear:both;"></div></div>
                         <div style="padding:3px;"><div style="float:left;">Confirm Password</div> <div style="float:right;"><input type="password" name="repassword"/></div><div style="clear:both;"></div></div>
                         <div style="padding:3px;"><div style="float:right;"><input type="submit" name="submit" value="Update Password"/></div><div style="clear:both;"></div></div>
                         <div style="padding:4px;color:red;"><?php echo $error;?></div>
                        </div>
                    </form>
                </div>
                <div>
                    <h3>Application Category Permissions</h3>
                    <div style="padding:20px;">
                        <?php foreach($partcategories as $partcategory){$checked=''; if(array_key_exists($partcategory['id'],$idkeyedallowlist)){$checked='checked';} echo '<div><input type="checkbox" id="partcategory_'.$partcategory['id'].'" onclick="addRemovePartcategory(\''.$userid.'\',\''.$partcategory['id'].'\')" name="partcategory_'.$partcategory['id'].'" '.$checked.'><label for="partcategory_'.$partcategory['id'].'">'.$partcategory['name'].'</label></div>';}?>
                    </div>
                </div>
            </div> <!-- End Main Content -->

            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>