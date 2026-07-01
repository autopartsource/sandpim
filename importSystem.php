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
 
 // configs
 foreach($xml->configs[0] as $config)
 {
  if(!$configGet->validConfigOption((string)$config['name']))
  {
   $sections['configs']['warnings'][]='<strong>Unknown/non-standard config variable name: </strong>'.(string)$config['name'].' = '.base64_decode((string)$config['value']);
  }
 }
  
 // users
 foreach($xml->users[0] as $u)
 {
  $userexists=false;
  if($user->getUserByUsername($u['username']))
  {
   $sections['users']['errors'][]='<strong>username already exists: </strong>'.(string)$u['username'].' ('.base64_decode((string)$u['name']).')';
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
     if($pim->addUserNavelement($actualuserid, $n['navid']))
     {
      $sections['users']['actions'][]='Added nav element: '.$n['navid'];         
     }
     else
     {
      $sections['users']['errors'][]='Failed to addd nav element: '.$n['navid'];         
     }
    }
   }
   else
   {// user insert failed
    $sections['users']['errors'][]='Failed to add user record: '.$newuserid.' ('.$newusername.')';
   }
  }  
 }

 //receiverprofiles
 //  assettags
 //  deliverygroups
 //  lifecyclestatuses
 //  parttranslations
 //  pricesheetnumber   
 
 foreach($xml->receiverprofiles[0] as $r)
 {
  $receiverprofilexists=false;
  if($pim->getReceiverprofileById($r['id']))
  {
   $sections['receiverprofiles']['errors'][]='<strong>Receiver profile ('.$r['id'].') already exists: </strong>'. base64_decode($r['name']);
   $receiverprofilexists=true;
  }
  
  if(!$testing)
  {   
   $actualreceiverprofileid=$pim->createReceiverprofile(base64_decode($r['name']), base64_decode($r['data']), intval($r['id']));
   if($actualreceiverprofileid)
   {// successful add to the receiverprofile table - now add deliverygroups and lifecyclestatuses

    $sections['receiverprofiles']['actions'][]='Added receiverprofile record: '.$actualreceiverprofileid.' ('.base64_decode($r['name']).')';
    foreach($r->deliverygroups[0] as $d)
    {
     if($pim->addDeliverygroupToReceiverProfile($actualreceiverprofileid, intval($d['id'])))
     {
      $sections['receiverprofiles']['actions'][]='Added deliverygroup: '.$d['id'];
     }
     else
     {
      $sections['receiverprofiles']['errors'][]='Faile to add deliverygroup: '.$d['id'];         
     }
    }
        
    foreach($r->lifecyclestatuses[0] as $l)
    {
     if($pim->addLifecyclestatusToReceiverProfile($actualreceiverprofileid, $l['code']))
     {
      $sections['receiverprofiles']['actions'][]='Added lifecyclestatus: '.$l['code'];
     }
     else
     {
      $sections['receiverprofiles']['errors'][]='Failed to add lifecyclestatus: '.$l['code'];         
     }
    }    
   }
   else
   {// profile insert failed
    $sections['receiverprofiles']['errors'][]='Failed to add receiver profile record: '.$r['id'].' ('.base64_decode($r['name']).')';
   }
  }
 }
 
 //partcategories
 foreach($xml->partcategories[0] as $p)
 {
  $partcategoryexists=false;
  if($pim->getPartCategory($p['id']))
  {
   $sections['partcategories']['errors'][]='<strong>Partcategory ('.$p['id'].') already exists: </strong>'. base64_decode($p['name']);
   $partcategoryexists=true;
  }
  
  if(!$testing)
  {   
   if($pim->createPartcategory(base64_decode($p['name']), $p['id']))
   {// successful add to the partcategory table
    $sections['partcategories']['actions'][]='Added partcategory record: '.$p['id'].' ('.base64_decode($p['name']).')';
   }
   else
   {
    $sections['partcategories']['errors'][]='Failed to add partcategory record: '.$p['id'].' ('.base64_decode($p['name']).')';
   }
  }
 }
 

 //deliverygroups 
 foreach($xml->deliverygroups[0] as $d)
 {
  $deliverygroupsexists=false;
  if($pim->getDeliverygroup($d['id']))
  {
   $sections['deliverygroups']['errors'][]='<strong>Deliverygroup ('.$d['id'].') already exists: </strong>'. base64_decode($d['description']);
   $deliverygroupsexists=true;
  }
  
  if(!$testing)
  {   
   if($pim->createDeliverygroup(base64_decode($d['description']), $d['id']))
   {// successful add to the deliverygroup table
    $sections['deliverygroups']['actions'][]='Added deliverygroup record: '.$p['id'].' ('.base64_decode($d['description']).')';

    foreach($d->d as $p)
    {
     if($pim->addPartcategoryToDeliverygroup($d['id'], $p['id']))
     {
      $sections['deliverygroups']['actions'][]='Added partcategory: '.$p['id'];
     }
     else
     {
      $sections['deliverygroups']['errors'][]='Failed to add partcategory: '.$p['id'];         
     }
    }
   }
   else
   {
    $sections['deliverygroups']['errors'][]='Failed to add deliverygroup record: '.$d['id'].' ('.base64_decode($d['description']).')';
   }
  }
 }






 
 //pricesheets

 //favoritemakes

 //parttypes
 
 //positions
 
 //competitivebrands
 
 //partdescriptionrecipes

 //replicationpeers
 
 //assettags

 //allowedhosts


 
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