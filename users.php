<?php
include_once('/var/www/html/class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pim= new pim;

$users=$pim->getUsers();


$error='';

if(isset($_POST['submit']) && $_POST['submit']=='Create User')
{
 $pepper = 'sdlfkjldskfj';// getConfigVariable("pepper");
 $pwd = $_POST['password'];
 $pwd_peppered = hash_hmac("sha256", $pwd, $pepper);
 $pwd_hashed = password_hash($pwd_peppered, PASSWORD_ARGON2ID);
 $userid=$pim->addUser($_POST['username'],$pwd_hashed,$_POST['realname']);
 $error='user '.$userid.' created';
}



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
  <h1>Users</h1>

  <div style="padding:3px;">
   <table>
    <tr><th>Username</th><th>Real Name</th><th>Status</th></tr>
    <?php foreach($users as $user){echo '<tr><td>'.$user['username'].'</td><td>'.$user['name'].'</td><td>'.$user['status'].'</td></tr>';}o?>
   </table>
  </div>

  <div>
   <form method="post">
    <div><?php echo $error;?></div>
    <div style="width:350px;padding:3px;border:1px solid;">
     <div style="padding:3px;"><div style="float:left;">Username</div> <div style="float:right;"><input type="text" name="username"/></div><div style="clear:both;"></div></div>
     <div style="padding:3px;"><div style="float:left;">Real Name</div> <div style="float:right;"><input type="text" name="realname"/></div><div style="clear:both;"></div></div>
     <div style="padding:3px;"><div style="float:left;">Password</div> <div style="float:right;"><input type="password" name="password"/></div><div style="clear:both;"></div></div>
     <div style="padding:3px;"><div style="float:left;">Confirm Password</div> <div style="float:right;"><input type="password" name="repassword"/></div><div style="clear:both;"></div></div>
     <div style="padding:3px;"><div style="float:right;"><input type="submit" name="submit" value="Create User"/></div><div style="clear:both;"></div></div>
    </div>
   </form>
  </div>
 </body>
</html>
