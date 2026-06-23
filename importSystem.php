<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
$navCategory = 'utilities';

$pim = new pim;
$user= new user;
$configGet=new configGet();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importSystem.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}


session_start();
if (!isset($_SESSION['userid'])) {echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}
if(!$pim->userHasNavelement($_SESSION['userid'], 'UTILITIES/BACKUPRESTORE')){echo 'access denied'; $logs->logSystemEvent('accesscontrol', $_SESSION['userid'], 'denied:UTILITIES/BACKUPRESTORE'); exit;}

$sections=array('configs'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'users'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'receiverprofiles'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'partcategories'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'deliverygroups'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'pricesheets'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'favoritemakes'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'parttypes'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'positions'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'competitivebrands'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'partdescriptionrecipes'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'replicationpeers'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'assettags'=>['warnings'=>[],'errors'=>[],'actions'=>[]],'allowedhosts'=>['warnings'=>[],'errors'=>[],'actions'=>[]]);
$importresults=array();

$testing=true;
        
if (isset($_POST['input'])) 
{
 $xml = simplexml_load_string($_POST['input']);
 
 
 foreach($xml->configs[0] as $config)
 {
  if(!$configGet->validConfigOption((string)$config['name']))
  {
   $sections['configs']['warnings'][]='<strong>Unknown/non-standard config variable name: </strong>'.(string)$config['name'].' = '.base64_decode((string)$config['value']);
  }
 }
 
 foreach($xml->users[0] as $u)
 {
  $userexists=false;
  if($user->getUserByUsername($u['username']))
  {
   $sections['users']['warnings'][]='<strong>username already exists: </strong>'.(string)$u['username'].' ('.base64_decode((string)$u['name']).')';
   $userexists=true;
  }
  
  if(!$testing)
  {
   $newuserid=intval($u['id']);
   $newusername=$u['username'];
   $newuserrealname= base64_decode($u['name']);
   $newuserhash= base64_decode($u['hash']);
   $newuserenvironment= base64_decode($u['environment']);
   $newuserstatus=intval($u['status']);
   
   $actualuserid=$user->addUser($newusername, $newuserhash, $newuserrealname, $newuserid, $newuserstatus, $newuserenvironment);
   if($actualuserid)
   {// successful add to the user table - now add nav elements
    $sections['users']['actions'][]='Added user record: '.$actualuserid.' ('.$newusername.')';
    foreach($u->navelements[0] as $n)
    {
     $pim->addUserNavelement($actualuserid, $n['navid']);
     $sections['users']['actions'][]='Added nav element: '.$n['navid'];   
    }
   }
   else
   {// user insert failed
    $sections['users']['errors'][]='Failed to add user record: '.$newuserid.' ('.$newusername.')';
   }
  }
  
 }
 
 
}?>
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
                <!-- placeholder empty left column (1/6 of screen) -->
                <div class="col-xs-12 col-md-2 my-col colLeft"></div>

                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start">System Import Results</h3>
                        <div class="card-body">
                            
                            <?php foreach($sections as $sectionname=>$outcomes)
                            {?>
                            <div class="card shadow-sm">
                                <h4 class="card-header text-start"><?php echo $sectionname;?></h4>
                                <div class="card-body">                                    
                                 <?php
                                 foreach($outcomes['errors'] as $error)
                                 {
                                  echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';
                                 }
                                 
                                 foreach($outcomes['warnings'] as $warning)
                                 {
                                  echo '<div class="alert alert-warning" role="alert">'.$warning.'</div>';
                                 }
                                 
                                 foreach($outcomes['actions'] as $action)
                                 {
                                  echo '<div class="alert alert-success" role="alert">'.$action.'</div>';
                                 }
                                 
                                 ?>
                                </div>
                            </div>                                    
                            <?php }?>                            
                        </div>
                    </div>
                </div>
                
                <!-- Placeholder empty right colum (1/6 of screen) -->
                <div class="col-xs-12 col-md-2 my-col colRight">

                </div>
            </div>
        </div>    
        <!-- End of Content Container -->

        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>