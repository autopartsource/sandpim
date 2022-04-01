<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');

$navCategory = 'settings';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'receiverProfile.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid']))
{
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
 exit;
}

$logs = new logs;
$pcdb = new pcdb;

if (isset($_POST['submit']) && $_POST['submit']=='Save') 
{
 $profiledata=str_replace("\r\n",';',$_POST['profiledata']);

 $pim->updateReceiverprofile(intval($_POST['id']), $_POST['profilename'],$profiledata,$_POST['notes']);
 
 // part translation
 $translations=array();
 $records = explode("\r\n", $_POST['parttranslation']);
 foreach ($records as $record) 
 {
  $fields = explode("\t", $record);
  if(count($fields)==2 && trim($fields[0])!='' && trim($fields[1])!='' && strlen($fields[0])<=20 && strlen($fields[1])<=20)
  {
   $translations[trim($fields[0])]=trim($fields[1]);
  }
 }  
 $pim->writeReceiverprofileParttranslation(intval($_POST['id']),$translations);
 
 
 $logs->logSystemEvent('receiverprofilechange', $_SESSION['userid'], 'Receiver Profile '.$_POST['id'].' was changed.');
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./receiverProfiles.php'\" /></head><body></body></html>";
 exit;
}

if (isset($_POST['submit']) && $_POST['submit']=='Delete') 
{
 $pim->deleteReceiverprofile(intval($_POST['id'])); 
 $logs->logSystemEvent('receiverprofiledelete', $_SESSION['userid'], 'Receiver Profile '.$_POST['id'].' was deleted.');
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./receiverProfiles.php'\" /></head><body></body></html>";
 exit;
}

$profile = $pim->getReceiverprofileById(intval($_GET['id']));
$profile['data']=str_replace(';',"\r\n",$profile['data']);
$applieddeliverygroupids=$pim->getReceiverprofileDeliverygroupids($profile['id']);
$alldeliverygroups=$pim->getDeliverygroups();

$parttranslations=$pim->getReceiverprofileParttranslations($profile['id']);

$lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($profile['id']);
$alllifecyclestatuses=$pcdb->getLifeCycleCodes();

?>
<!DOCTYPE html>
<html>
    <head>
        <script>
            
            function removeDeliverygroup(deliverygroupid,deliverygroupdescription)
            {
             var deliverygroupdiv = document.getElementById('applieddeliverygroupid_'+deliverygroupid);
             deliverygroupdiv.parentNode.removeChild(deliverygroupdiv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddRemoveReceiverDeliverygroup.php?receiverprofileid=<?php echo $profile['id'];?>&deliverygroupid='+deliverygroupid+'&action=remove');
             xhr.onload = function()
             {
             };
             xhr.send();
             
             document.getElementById('unapplieddeliverygroups').innerHTML+='<div style="text-align:right;padding:3px;" id="unapplieddeliverygroupid_'+deliverygroupid+'">'+deliverygroupdescription+' <button class="btn btn-outline-success" onclick="addDeliverygroup(\''+deliverygroupid+'\',\''+deliverygroupdescription+'\')"> <i class="bi bi-arrow-bar-right"></i></button></div>';

            }
            
            function addDeliverygroup(deliverygroupid,deliverygroupdescription)
            {
             var deliverygroupdiv = document.getElementById('unapplieddeliverygroupid_'+deliverygroupid);
             deliverygroupdiv.parentNode.removeChild(deliverygroupdiv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddRemoveReceiverDeliverygroup.php?receiverprofileid=<?php echo $profile['id'];?>&deliverygroupid='+deliverygroupid+'&action=add');
             xhr.onload = function()
             {
             };
             xhr.send();
             
             document.getElementById('applieddeliverygroups').innerHTML+='<div style="text-align:left;padding:3px;" id="applieddeliverygroupid_'+deliverygroupid+'"><button class="btn btn-outline-danger" onclick="removeDeliverygroup(\''+deliverygroupid+'\',\''+deliverygroupdescription+'\')"><i class="bi bi-arrow-bar-left"></i></button> '+deliverygroupdescription+'</div>';
            }
            
            
            
            function addLifecyclestatus()
            {
             var lifecyclestatus = document.getElementById("lifecyclestatus").value;
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddRemoveReceiverLifecyclestatus.php?receiverprofileid=<?php echo $profile['id'];?>&lifecyclestatus='+lifecyclestatus+'&action=add');

             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              var container=document.getElementById('lifecyclestatuses');
              container.innerHTML+='<div style="padding-bottom:3px;" id="lifecyclestatusid_'+response.id+'"><div style="float:left;"><button class="btn btn-sm btn-outline-danger" title="Remove this lifecycle status from this profile" onclick="removeLifecyclestatus('+response.id+')">x</button></div><div style="float:left; background-color: #e8e8e8;margin-left:4px; padding:5px;font-size:85%;">'+response.lifecyclestatusdescription+'</div><div style="clear:both;"></div></div>';
             };
             xhr.send();
            }

            function removeLifecyclestatus(id)
            {
             var lifecyclestatusdiv = document.getElementById('lifecyclestatusid_'+id);
             lifecyclestatusdiv.parentNode.removeChild(lifecyclestatusdiv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddRemoveReceiverLifecyclestatus.php?recordid='+id+'&receiverprofileid=<?php echo $profile['id'];?>&action=remove');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
             };
             xhr.send();
            }
            
            
            
            
            
            
            
            
            
            
                        
        </script>
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
                        <h3 class="card-header text-start">Receiver Profile</h3>

                        <div class="card-body">
                            <div class="card">
                                <h5 class="card-header text-start">
                                    <form action="" method="post">
                                        <input type="hidden" name="id" value="<?php echo $profile['id']; ?>"/>
                                        <input type="hidden" name="oldname" value="<?php echo $profile['name']; ?>"/>
                                        Name: <input type="text" name="profilename" value="<?php echo $profile['name']; ?>"/>
                                        <span style="float:right"><input name="submit" type="submit" value="Save"/> <input name="submit" type="submit" value="Delete"/></span>
                                    </form>
                                </h5>
                                <div class="card-body">

                                    <div class="row padding">
                                        <div class="col-md-6">
                                            <div class="card">
                                                <h6 class="card-header">Available Delivery Groups</h6>
                                                <div class="card-body">
                                                    <div id="unapplieddeliverygroups">
                                                    <?php
                                                    foreach ($alldeliverygroups as $deliverygroup) 
                                                    {   
                                                        if(in_array($deliverygroup['id'], $applieddeliverygroupids)){continue;}
                                                        echo '<div style="text-align:right;padding:3px;" id="unapplieddeliverygroupid_'.$deliverygroup['id'].'">'.$deliverygroup['description'] . ' <button class="btn btn-outline-success" onclick="addDeliverygroup(\''.$deliverygroup['id'].'\',\''.$deliverygroup['description'].'\')"><i class="bi bi-arrow-bar-right"></i></button></div>';
                                                    }
                                                    ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card">
                                                <h6 class="card-header">Applied Delivery Groups</h6>
                                                <div class="card-body">
                                                    <div id="applieddeliverygroups">
                                                    <?php
                                                    foreach ($applieddeliverygroupids as $deliverygroupid) 
                                                    {
                                                        $deliverygroup=$pim->getDeliverygroup($deliverygroupid);
                                                        echo '<div style="text-align:left;padding:3px;" id="applieddeliverygroupid_'.$deliverygroupid.'"><button class="btn btn-outline-danger" onclick="removeDeliverygroup(\''.$deliverygroup['id'].'\',\''.$deliverygroup['description'].'\')"><i class="bi bi-arrow-bar-left"></i></button> '.$deliverygroup['description'].'</div>';
                                                    }
                                                    ?>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row padding">
                                        <div class="col">
                                            <div class="card">
                                                <h6 class="card-header">ACES & PIES header parameters</h6>
                                                <div class="card-body">
                                                    <textarea name="profiledata" rows="10" style="width: 100%; max-width: 100%;"><?php echo $profile['data']; ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row padding">
                                        <div class="col">
                                            <div class="card">
                                                <h6 class="card-header">Internal Notes</h6>
                                                <div class="card-body">
                                                    <textarea name="notes" rows="10" style="width: 100%; max-width: 100%;"><?php echo $profile['notes']; ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row padding">
                                        <div class="col">
                                            <div class="card">
                                                <h6 class="card-header">Partnumber Translation Table (internal TAB external)</h6>
                                                <div class="card-body">
                                                    <textarea style="width: 100%; max-width: 100%;" rows="10" name="parttranslation"><?php foreach ($parttranslations as $internalpart=>$externalpart){echo $internalpart."\t".$externalpart."\r\n";} ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row padding">
                                        <div class="col">
                                            <div class="card">
                                                <h6 class="card-header">Lifecycle Statuses to include in exports</h6>
                                                <div class="card-body">
                                                    <div style="float:left;">
                                                        <select id="lifecyclestatus"><?php foreach($alllifecyclestatuses as $lifecyclestatus){?> <option value="<?php echo $lifecyclestatus['code'];?>"><?php echo $lifecyclestatus['description'];?></option><?php }?></select>
                                                        <button class="btn btn-sm btn-success" id="addlifecyclestatus" title="Add a lifecycle status to this profile" onclick="addLifecyclestatus()">+</button>
                                                    </div>
                                                    <div id="lifecyclestatuses" style="float:left;padding-left: 80px;">
<?php 
foreach($lifecyclestatuses as $lifecyclestatus)
{
echo '<div style="text-align:left;padding-bottom:5px;" id="lifecyclestatusid_'.$lifecyclestatus['id'].'"><button class="btn btn-sm btn-outline-danger" title="Remove this lifecyclestatus from this profile" onclick="removeLifecyclestatus('.$lifecyclestatus['id'].')">x</button> '.$pcdb->lifeCycleCodeDescription($lifecyclestatus['lifecyclestatus']).'</div>';    
}
?>
                                                    </div>
                                                    <div style="clear: both;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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