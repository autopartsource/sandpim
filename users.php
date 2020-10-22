<?php
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsSetClass.php');

$navCategory = 'settings';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$user= new user;
$logs=new logs;
$configGet= new configGet;
$configSet= new configSet;

$error='';

if(isset($_POST['submit']) && $_POST['submit']=='Create User')
{
 if(!$user->getUserByUsername($_POST['username']))
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
                        <h3 class="card-header text-left">User Accounts</h3>

                        <div class="card-body">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="existing-tab" data-toggle="tab" href="#existing" role="tab" aria-controls="existing" aria-selected="true">Existing Accounts</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="create-tab" data-toggle="tab" href="#create" role="tab" aria-controls="create" aria-selected="false">Create New Account</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade mt-3 show active" id="existing" role="tabpanel" aria-labelledby="existing-tab">
                                    <table>
                                        <tr><th>Username</th><th>Real Name</th><th>Status</th><th>Application Category Permissions</th><th>System Permissions</th></tr>
                                        <?php
                                        foreach ($users as $user) {
                                            $nicestatus = 'Inactive';
                                            if ($user['status'] == 1) {
                                                $nicestatus = 'Active';
                                            }
                                            echo '<tr><td><a href="./user.php?userid=' . $user['id'] . '">' . $user['username'] . '</a></td><td>' . $user['name'] . '</td><td>' . $nicestatus . '</td><td></td><td></td></tr>';
                                        }
                                        ?>
                                    </table>
                                </div>
                                <div class="tab-pane fade mt-3" id="create" role="tabpanel" aria-labelledby="create-tab">
                                    <form method="post">
                                        <div style="width:350px;padding:3px;border:1px solid;">
                                            <div style="padding:3px;"><div style="float:left;">Username</div> <div style="float:right;"><input type="text" name="username"/></div><div style="clear:both;"></div></div>
                                            <div style="padding:3px;"><div style="float:left;">Real Name</div> <div style="float:right;"><input type="text" name="realname"/></div><div style="clear:both;"></div></div>
                                            <div style="padding:3px;"><div style="float:left;">Password</div> <div style="float:right;"><input type="password" name="password"/></div><div style="clear:both;"></div></div>
                                            <div style="padding:3px;"><div style="float:left;">Confirm Password</div> <div style="float:right;"><input type="password" name="repassword"/></div><div style="clear:both;"></div></div>
                                            <div style="padding:3px;"><div style="float:right;"><input type="submit" name="submit" value="Create User"/></div><div style="clear:both;"></div></div>
                                            <div style="padding:4px;color:red;"><?php echo $error; ?></div>
                                        </div>
                                    </form>
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