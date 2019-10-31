<?php
include_once('/var/www/html/class/pimClass.php');
session_start();
$pim= new pim;

$error='';
if(isset($_POST['username']) && isset($_POST['password']))
{
 $username=$_POST['username'];
 $pepper = 'sdlfkjldskfj'; //getConfigVariable("pepper");
 $pwd = $_POST['password'];
 $pwd_peppered = hash_hmac("sha256", $pwd, $pepper);
 $user = $pim->getUser($username);

 if(password_verify($pwd_peppered, $user['hash']))
 {
  $_SESSION['userid']=$user['id'];
  $_SESSION['name']=$user['name'];
  echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./index.php'\" /></head><body></body></html>";
  exit;
 }
 else
 {
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
