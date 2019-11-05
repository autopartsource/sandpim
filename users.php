<?php
include_once('/var/www/html/class/userClass.php');
include_once('/var/www/html/class/configClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$user= new user;
$config= new config;

$error='';

if(isset($_POST['submit']) && $_POST['submit']=='Create User')
{
 if(!$user->getUserByUsername($_POST['username']))
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
    $userid=$user->addUser($_POST['username'],$pwd_hashed,$_POST['realname']);
    $logs->logSystemEvent('usercreate',$_SESSION['userid'],$user->realNameOfUserid($userid).' created');
    $error='user account created for '.$_POST['realname'].' created';
   }
   else
   { // mismatch in confirmation password
    $error='passwords do not match - account not created';
   }
  }
  else
  { // too short
   $error='password is too short - account not created';
  }
 }
 else
 { // user already exists
  $error='username already exists - account not created';
 }
}

$users=$user->getUsers();

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
 </head>
 <body>
  <?php include('topnav.php');?>
  <h1>User Accounts</h1>

  <div style="padding:10px;">
   <h3>Existing Accounts</h3>
   <table>
    <tr><th>Username</th><th>Real Name</th><th>Status</th><th>Application Category Permissions</th><th>System Permissions</th></tr>
    <?php foreach($users as $user)
    {
     $nicestatus='Inactive';if($user['status']==1){$nicestatus='Active';}
     echo '<tr><td><a href="./user.php?userid='.$user['id'].'">'.$user['username'].'</a></td><td>'.$user['name'].'</td><td>'.$nicestatus.'</td><td></td><td></td></tr>';
    }?>
   </table>
  </div>

  <div style="padding:10px;">
   <h3>Create a new account</h3>
   <form method="post">
    <div style="width:350px;padding:3px;border:1px solid;">
     <div style="padding:3px;"><div style="float:left;">Username</div> <div style="float:right;"><input type="text" name="username"/></div><div style="clear:both;"></div></div>
     <div style="padding:3px;"><div style="float:left;">Real Name</div> <div style="float:right;"><input type="text" name="realname"/></div><div style="clear:both;"></div></div>
     <div style="padding:3px;"><div style="float:left;">Password</div> <div style="float:right;"><input type="password" name="password"/></div><div style="clear:both;"></div></div>
     <div style="padding:3px;"><div style="float:left;">Confirm Password</div> <div style="float:right;"><input type="password" name="repassword"/></div><div style="clear:both;"></div></div>
     <div style="padding:3px;"><div style="float:right;"><input type="submit" name="submit" value="Create User"/></div><div style="clear:both;"></div></div>
     <div style="padding:4px;color:red;"><?php echo $error;?></div>
    </div>
   </form>
  </div>
 </body>
</html>
