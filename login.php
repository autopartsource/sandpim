<?php
include_once('./class/userClass.php');
include_once('./class/setupClass.php');
include_once('./class/logsClass.php');
include_once('./class/configGetClass.php');
include_once('./class/pimClass.php');

$user = new user;
if(!$user->testDatabase())
{ // if database connectivity failed, redirect to setup
    echo 'backend database connection failed. Verify that MySQL is running and accepting connections.';
    exit;
}


$setup=new setup;
if($setup->databaseNameExists('pim'))
{ // we can connect to db and pim exists. See it it's actually empty
 if($setup->databaseTableCount('pim')==0)
 {// pim database exists, but has not tables
  echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./setup.php'\" /></head><body></body></html>";
  exit;   
 }
}
else
{// pim database does not exist
  echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./setup.php'\" /></head><body></body></html>";
  exit;    
}


session_start();

$pim = new pim;
$logs = new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'index.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$configGet = new configGet;

$installationtate = $user->installationState();

$error = '';
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $pepper = $configGet->getConfigValue('pepper');
    $pwd = $_POST['password'];
    $pwd_peppered = hash_hmac("sha256", $pwd, $pepper);
    if ($userid = $user->getUserByUsername($username)) { // known user - now verify password
        if (password_verify($pwd_peppered, $user->hash)) { // valid user and password
            $_SESSION['userid'] = $user->id; // sessionize the use id and name
            $_SESSION['name'] = $user->name; // sessionize the use id and name
            $_SESSION['environment'] = $user->environment;            
            // log the login event
            $logs->logSystemEvent('login', $user->id, $user->name.' logged in from '.$_SERVER['REMOTE_ADDR']);
            // re-direct client to index page
            echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./index.php'\" /></head><body></body></html>";
            exit;
        } else {
            $error = 'Invalid username or password';
            // log the login event
            $logs->logSystemEvent('loginfailure', $userid, 'failed login from '.$_SERVER['REMOTE_ADDR']);
        }
    } else { // unknown user
        $error = 'Invalid username or password';
        $logs->logSystemEvent('loginfailure', 0, 'unknow user ('.$username.') from '.$_SERVER['REMOTE_ADDR']);
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div>
                        <form method="post">
                            <div><?php echo $error; ?></div>
                            <div style="padding:5px;">Username <input type="text" name="username"/></div>
                            <div style="padding:5px;">Password <input type="password" name="password"/></div>
                            <div><input type="submit" name="submit" value="Login"/></div>
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
        </div>
    </body>
</html>
