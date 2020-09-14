<?php
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
include_once('./class/configGetClass.php');
session_start();
$user = new user;
$logs = new logs;
$configGet = new configGet;

$installationtate = $user->installationState();

//$user->sabotageSetupUser();


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
                    <?php
                    if ($installationtate == 0) {
                        $setupuser = $user->createSetupUser();
                        ?>
                        <div style="background-color: #FF5533">A temporary account was created for completing the setup process. Be sure to record these credentials - the password will not be shown again.  <br/>
                            username: <?php echo $setupuser['username']; ?> <br/>
                            password: <?php echo $setupuser['password']; ?> <br/>
                        </div>
                    <?php } ?>

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
