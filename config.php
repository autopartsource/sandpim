<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsClass.php');

$navCategory='settings';

$pim = new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'config.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}


$user = new user;
$configGet = new configGet;
$configSet = new configSet;
$logs = new logs;


if (isset($_POST['submit']))
{
 $userid=$_SESSION['userid'];
 $configname = $_POST['configname'];
 $oldvalue=$configGet->getConfigValue($configname);
 $configvalue = $_POST['configvalue'];
 $configSet->setConfigValue($configname, $configvalue);
 $logs->logSystemEvent('config', $userid, $configname.' changed from:'.$oldvalue.' to:'.$configvalue);
}

$configs = $configGet->getAllConfigValues();
$configoptions=$configGet->getConfigOptions();

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
                        <h3 class="card-header text-start">Configuration Parameters</h3>

                        <div class="card-body">
                            <form method="post">
                                <?php
                                    foreach ($configs as $config) {
                                        echo '<div class="form-group row">';
                                            echo '<label for="static'.$config['configname'].'" class="col-sm-3 col-form-label text-start">'.$config['configname'].'</label>';
                                            echo '<div class="col-sm-9">';
                                                echo '<input type="text" readonly class="form-control" id="static'.$config['configname'].'" value="'.$config['configvalue'].'">';
                                            echo '</div>';
                                        echo '</div>';
                                        echo '<hr>';
                                    }
                                ?>
                                <div class="form-group row">
                                    <select name="configname" class="custom-select col-sm-4">
                                        <option selected disabled value="">Parameter...</option>
                                        <?php foreach($configoptions as $configoption){echo '<option value="'.$configoption['configname'].'">'.$configoption['configname'].'</option>';}?>
                                    </select>
                                    <div class="col-sm-6">
                                        <input type="text" name="configvalue" class="form-control"/>
                                    </div>
                                    <div class="col-sm-2">
                                        <input type="submit" name="submit" value="Add/Update"/>
                                    </div>
                                </div>
                            </form>
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