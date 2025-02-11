<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsClass.php');

$navCategory = 'settings';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'users.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

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
                        <h3 class="card-header text-start">User Accounts</h3>

                        <div class="card-body">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="existing-tab" data-bs-toggle="tab" href="#existing" role="tab" aria-controls="existing" aria-selected="true">Existing Accounts</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="create-tab" data-bs-toggle="tab" href="#create" role="tab" aria-controls="create" aria-selected="false">Create New Account</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade mt-3 show active" id="existing" role="tabpanel" aria-labelledby="existing-tab">
                                    <div style="padding:4px;color:red;"><?php echo $error; ?></div>
                                    <?php
                                    foreach ($users as $user) {
                                        $nicestatus = 'Inactive';
                                        if ($user['status'] == 1) {
                                            $nicestatus = 'Active';
                                        }
                                        echo '<div class="card">';
                                            echo '<h6 class="card-header text-start"><a href="./user.php?userid=' . $user['id'] . '">' . $user['username'] . '</a></h6>';
                                            echo '<div class="card-body">';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="staticRealName" class="col-sm-2 col-form-label">Name</label>';
                                                    echo '<div class="col-sm-10">';
                                                        echo '<input id="staticRealName" readonly type="text" class="form-control" name="realname" value="'.$user['name'].'"/>';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="staticStatus" class="col-sm-2 col-form-label">Status</label>';
                                                    echo '<div class="col-sm-10">';
                                                        echo '<input id="staticStatus" readonly type="text" class="form-control" name="realname" value="'.$nicestatus.'"/>';
                                                    echo '</div>';
                                                echo '</div>';
                                                echo '<div class="form-group row">';
                                                    echo '<label for="staticStatus" class="col-sm-2 col-form-label">Activity</label>';
                                                    echo '<div class="col-sm-10">';
                                                        echo '<a href="./userHistory.php?userid='.$user['id'].'">History</a>';
                                                    echo '</div>';
                                                echo '</div>';
                                            echo '</div>';
                                            
                                        echo '</div>';
                                        
                                    }
                                    ?>
                                </div>
                                <div class="tab-pane fade mt-3" id="create" role="tabpanel" aria-labelledby="create-tab">
                                    <form method="post">
                                        <div class="form-group row">
                                            <label for="inputUsername" class="col-sm-2 col-form-label">Username</label>
                                            <div class="col-sm-10">
                                                <input id="inputUsername" type="text" class="form-control" name="username"/></td></tr>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputRealName" class="col-sm-2 col-form-label">Real Name</label>
                                            <div class="col-sm-10">
                                                <input id="inputRealName" type="text" class="form-control" name="realname"/></td></tr>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputPassword" class="col-sm-2 col-form-label">Password</label>
                                            <div class="col-sm-10">
                                                <input id="inputPassword" type="password" class="form-control" name="password"/></td></tr>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputConfirmPass" class="col-sm-2 col-form-label">Confirm Password</label>
                                            <div class="col-sm-10">
                                                <input id="inputConfirmPass" type="password" class="form-control" name="repassword"/></td></tr>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col">
                                                <input type="submit" name="submit" value="Create User"/>
                                                <div style="padding:4px;color:red;"><?php echo $error; ?></div>
                                            </div>
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