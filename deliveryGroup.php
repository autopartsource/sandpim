<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'settings';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'deliveryGroup.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$logs = new logs;

if (isset($_POST['submit']) && $_POST['submit']=='Save') 
{
 if($_POST['description']!=$_POST['olddescription'])   
 {
  $pim->updateDeliverygroupDescription(intval($_POST['id']), $_POST['description']); 
  $logs->logSystemEvent('deliverygroupchange', $_SESSION['userid'], 'Delivery Group '.$_POST['id'].' description was changed from '.$_POST['olddescription'].' to '.$_POST['description']);
 }
 
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./deliveryGroups.php'\" /></head><body></body></html>";
 exit;
}

$deliverygroup = $pim->getDeliverygroup(intval($_GET['id']));
$appliedpartcategories = $pim->getDeliverygroupPartcategories(intval($_GET['id']));
$allpartcategories=$pim->getPartCategories();


$appliedpartcategoryidlist=array();
foreach ($appliedpartcategories as $partcategory) 
{
 $appliedpartcategoryidlist[]=$partcategory['id'];
}





?>

<!DOCTYPE html>
<html>
    <head>
        <script>
            function removePartcategory(partcategoryid,partcategoryname)
            {
             var partcategorydiv = document.getElementById('appliedpartcategoryid_'+partcategoryid);
             partcategorydiv.parentNode.removeChild(partcategorydiv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddRemoveDeliverygroupPartcategory.php?deliverygroupid=<?php echo $deliverygroup['id'];?>&partcategoryid='+partcategoryid+'&action=remove');
             xhr.onload = function()
             {
             };
             xhr.send();
             
             document.getElementById('unappliedpartcategories').innerHTML+='<div style="text-align:right;padding:3px;" id="unappliedpartcategoryid_'+partcategoryid+'">'+partcategoryname+' <button class="btn btn-outline-success" onclick="addPartcategory(\''+partcategoryid+'\',\''+partcategoryname+'\')"><i class="bi bi-arrow-bar-right"></i></button></div>'
            }
            
            function addPartcategory(partcategoryid,partcategoryname)
            {
             var partcategorydiv = document.getElementById('unappliedpartcategoryid_'+partcategoryid);
             partcategorydiv.parentNode.removeChild(partcategorydiv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddRemoveDeliverygroupPartcategory.php?deliverygroupid=<?php echo $deliverygroup['id'];?>&partcategoryid='+partcategoryid+'&action=add');
             xhr.onload = function()
             {
             };
             xhr.send();
             
             document.getElementById('appliedpartcategories').innerHTML+='<div style="text-align:left;padding:3px;" id="appliedpartcategoryid_'+partcategoryid+'"><button class="btn btn-outline-danger" onclick="removePartcategory(\''+partcategoryid+'\',\''+partcategoryname+'\')"><i class="bi bi-arrow-bar-left"></i></button> '+partcategoryname+'</div>';
            }
            
            function updateDescription()
            {
             var xhr = new XMLHttpRequest();
             var descriptionvalue=document.getElementById('deliverygroupdescription').value;
             xhr.open('GET', 'ajaxUpdateDeliverygroup.php?deliverygroupid=<?php echo $deliverygroup['id'];?>&elementid=description&value='+encodeURIComponent(descriptionvalue));
             xhr.onload = function()
             {
              var response=xhr.responseText;
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
                        <h4 class="card-header text-start">Delivery Group: <input type="text" id="deliverygroupdescription" value="<?php echo $deliverygroup['description'];?>"/><button onclick="updateDescription();">Update</button></h4>

                        <div class="card-body">
                            <div class="card">
                                <h6 class="card-header">Part Categories</h6>
                                <div class="row padding">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <h6 class="card-header">Available Categories</h6>
                                            <div class="card-body">
                                                <div id="unappliedpartcategories">
                                                <?php
                                                    foreach ($allpartcategories as $partcategory) 
                                                    {   if(in_array($partcategory['id'], $appliedpartcategoryidlist)){continue;}
                                                        echo '<div style="text-align:right;padding:3px;" id="unappliedpartcategoryid_'.$partcategory['id'].'">'.$pim->partCategoryName($partcategory['id']) . ' <button class="btn btn-outline-success" onclick="addPartcategory(\''.$partcategory['id'].'\',\''.$partcategory['name'].'\')"><i class="bi bi-arrow-bar-right"></i></button></div>';
                                                    }
                                                ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <h6 class="card-header">Included Categories</h6>
                                            <div class="card-body">
                                                <div id="appliedpartcategories" class="card-body">
                                                <?php 
                                                    foreach ($appliedpartcategories as $partcategory) 
                                                    {
                                                        echo '<div style="text-align:left;padding:3px;" id="appliedpartcategoryid_'.$partcategory['id'].'"><button class="btn btn-outline-danger" onclick="removePartcategory(\''.$partcategory['id'].'\',\''.$partcategory['name'].'\')"><i class="bi bi-arrow-bar-left"></i></button> '.$partcategory['name'].'</div>';
                                                    }
                                                ?>
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