<?php
include_once('/var/www/html/class/userClass.php');
include_once('/var/www/html/class/configGetClass.php');
session_start();
$user = new user;
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

            echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./index.php'\" /></head><body></body></html>";
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    } else { // unknown user
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('/var/www/html/includes/header.php'); ?>
    </head>
    <body>
        <div class="wrapper">
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
    </body>
</html>
