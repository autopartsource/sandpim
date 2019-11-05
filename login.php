<?php
include_once('/var/www/html/class/userClass.php');
include_once('/var/www/html/class/configClass.php');
session_start();
$user= new user;
$config= new config;


$error='';
if(isset($_POST['username']) && isset($_POST['password']))
{
 $username=$_POST['username'];
 $pepper = $config->getConfigValue('pepper');
 $pwd = $_POST['password'];
 $pwd_peppered = hash_hmac("sha256", $pwd, $pepper);
 if($userid=$user->getUserByUsername($username))
 { // known user - now verify password
  if(password_verify($pwd_peppered, $user->hash))
  { // valid user and password
   $_SESSION['userid']=$user->id; // sessionize the use id and name
   $_SESSION['name']=$user->name; // sessionize the use id and name

   echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./index.php'\" /></head><body></body></html>";
   exit;
  }
  else
  {
   $error='Invalid username or password';
  }
 }
 else
 { // unknown user
   $error='Invalid username or password';
 }
}

?>
<!DOCTYPE html>
<html>
 <head>
 </head>
 <body>
 <div>
  <form method="post">
   <div><?php echo $error;?></div>
   <div style="padding:5px;">Username <input type="text" name="username"/></div>
   <div style="padding:5px;">Password <input type="password" name="password"/></div>
   <div><input type="submit" name="submit" value="Login"/></div>
  </form>
 </div>
 </body>
</html>
