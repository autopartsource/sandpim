<?php
include_once('/var/www/html/class/pimClass.php');
include_once('/var/www/html/class/userClass.php');
include_once('/var/www/html/class/configClass.php');
include_once('/var/www/html/class/logsClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pim= new pim;
$user= new user;
$config= new config;
$logs= new logs;



$userid=intval($_GET['userid']);

$error='';

if(isset($_POST['submit']) && $_POST['submit']=='Update Password')
{
 if(strlen($_POST['password'])>=8)
 {
  if($_POST['password']== $_POST['repassword'])
  {
   $pepper = $config->getConfigValue('pepper');
   if(!$pepper)
   { // new installation - pepper value is not present - create it
    $pepper=bin2hex(random_bytes(16));
    $config->setConfigValue('pepper',$pepper);
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

$allowedappcategories=$user->getUserVisibleAppcategories($userid);
$idkeyedallowlist=array(); foreach($allowedappcategories as $allowed){$idkeyedallowlist[$allowed['id']]='';}
$appcategories=$pim->getAppCategories();
$user->getUserByID($userid);

?>
<!DOCTYPE html>
<html>
 <head>
  <style>
   .apppart {padding: 1px; border: 1px solid #808080; margin: 0px; background-color:#d0f0c0;}
   .apppart-cosmetic {padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#33FFD7;}
   .apppart-hidden {padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#FFD433;}
   .apppart-deleted { padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#FF5533;}

   a:link {color: blue; text-decoration: none;}
   a:visited {color: blue; text-decoration: none;}
   a:hover {color: gray; text-decoration: none;}
   a:active {color: blue; text-decoration: none;}

   table {border-collapse: collapse;}
   table, th, td {border: 1px solid black;}
  </style>
  <script>
   function addRemoveAppcategory(userid,appcategory)
   {
    if(document.getElementById('appcategory_'+appcategory).checked) 
    { // appcategory has been clocked on 
//     console.log(appcategory);
     var xhr = new XMLHttpRequest();
     xhr.open('GET', 'ajaxAddRemoveUserAppcateory.php?userid='+userid+'&appcategory='+appcategory+'&permissionname=canView&action=add');
     xhr.send();
    }
    else
    { // appcategory has been clocked off
     var xhr = new XMLHttpRequest();
     xhr.open('GET', 'ajaxAddRemoveUserAppcateory.php?userid='+userid+'&appcategory='+appcategory+'&permissionname=canView&action=remove');
     xhr.send();
    }
   }

   </script>
 </head>
 <body>
  <?php include('topnav.php');?>
  <h1>Edit User Account - <?php echo $user->name;?></h1>
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
    <?php foreach($appcategories as $appcategory){$checked=''; if(array_key_exists($appcategory['id'],$idkeyedallowlist)){$checked='checked';} echo '<div><input type="checkbox" id="appcategory_'.$appcategory['id'].'" onclick="addRemoveAppcategory(\''.$userid.'\',\''.$appcategory['id'].'\')" name="appcategory_'.$appcategory['id'].'" '.$checked.'><label for="appcategory_'.$appcategory['id'].'">'.$appcategory['name'].'</label></div>';}?>
   </div>
  </div>
 </body>
</html>
