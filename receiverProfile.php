<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'settings';

session_start();
if (!isset($_SESSION['userid']))
{
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
 exit;
}

$pim = new pim;
$logs = new logs;

if (isset($_POST['submit']) && $_POST['submit']=='Save') 
{
 $profiledata=str_replace("\r\n",';',$_POST['profiledata']);

 $pim->updateReceiverprofile(intval($_POST['id']), $_POST['profilename'],$profiledata,$_POST['notes']);
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
             
             document.getElementById('unapplieddeliverygroups').innerHTML+='<div style="text-align:left;padding:3px;" id="unapplieddeliverygroupid_'+deliverygroupid+'">'+deliverygroupdescription+' <button  onclick="addDeliverygroup(\''+deliverygroupid+'\',\''+deliverygroupdescription+'\')">+</button></div>';

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
             
             document.getElementById('applieddeliverygroups').innerHTML+='<div style="text-align:left;padding:3px;" id="applieddeliverygroupid_'+deliverygroupid+'">'+deliverygroupdescription+' <button  onclick="removeDeliverygroup(\''+deliverygroupid+'\',\''+deliverygroupdescription+'\')">x</button></div>';
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
                        <h3 class="card-header text-left">Receiver Profile</h3>

                        <div class="card-body">
                            <form action="" method="post">
                                <div class="card">
                                    <h5 class="card-header text-left">
                                        Name: <input type="text" name="profilename" value="<?php echo $profile['name']; ?>"/>
                                        <span style="float:right"><input name="submit" type="submit" value="Save"/> <input name="submit" type="submit" value="Delete"/></span>
                                    </h5>
                                    <div class="card-body">
                                        <div class="row padding">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <h6 class="card-header">Parameters</h6>
                                                    <div class="card-body">
                                                        <textarea name="profiledata" rows="15" style="width: 100%; max-width: 100%;"><?php echo $profile['data']; ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <h6 class="card-header">Delivery Groups</h6>
                                                    <div class="card-body">
                                                        <div id="applieddeliverygroups">
                                                        <?php
                                                        foreach ($applieddeliverygroupids as $deliverygroupid) 
                                                        {
                                                            $deliverygroup=$pim->getDeliverygroup($deliverygroupid);
                                                            echo '<div style="text-align:left;padding:3px;" id="applieddeliverygroupid_'.$deliverygroupid.'">'.$deliverygroup['description'].' <button  onclick="removeDeliverygroup(\''.$deliverygroup['id'].'\',\''.$deliverygroup['description'].'\')">x</button></div>';
                                                        }
                                                        ?>
                                                        </div>

                                                        <hr/>

                                                        <div id="unapplieddeliverygroups">
                                                        <?php
                                                        foreach ($alldeliverygroups as $deliverygroup) 
                                                        {   
                                                            if(in_array($deliverygroup['id'], $applieddeliverygroupids)){continue;}
                                                            echo '<div style="text-align:left;padding:3px;" id="unapplieddeliverygroupid_'.$deliverygroup['id'].'">'.$deliverygroup['description'] . ' <button  onclick="addDeliverygroup(\''.$deliverygroup['id'].'\',\''.$deliverygroup['description'].'\')">+</button></div>';
                                                        }
                                                        ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row padding">
                                            <div class="col-md-12">
                                                <div class="card">
                                                    <h6 class="card-header">Notes</h6>
                                                    <div class="card-body">
                                                        <textarea name="notes" rows="10" style="width: 100%; max-width: 100%;"><?php echo $profile['notes']; ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="id" value="<?php echo $profile['id']; ?>"/>
                                <input type="hidden" name="oldname" value="<?php echo $profile['name']; ?>"/>
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