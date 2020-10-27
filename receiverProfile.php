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
$appliedpartcategories=$pim->getReceiverprofilePartcategories($profile['id']);
$allpartcategories=$pim->getPartCategories();
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
             xhr.open('GET', 'ajaxAddRemoveReceiverPartcategory.php?receiverprofileid=<?php echo $profile['id'];?>&partcategoryid='+partcategoryid+'&action=remove');
             xhr.onload = function()
             {
             };
             xhr.send();
             
             document.getElementById('unappliedpartcategories').innerHTML+='<div style="text-align:left;padding:3px;" id="unappliedpartcategoryid_'+partcategoryid+'">'+partcategoryname+' <button  onclick="addPartcategory(\''+partcategoryid+'\',\''+partcategoryname+'\')">+</button></div>';

            }
            
            function addPartcategory(partcategoryid,partcategoryname)
            {
             var partcategorydiv = document.getElementById('unappliedpartcategoryid_'+partcategoryid);
             partcategorydiv.parentNode.removeChild(partcategorydiv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddRemoveReceiverPartcategory.php?receiverprofileid=<?php echo $profile['id'];?>&partcategoryid='+partcategoryid+'&action=add');
             xhr.onload = function()
             {
             };
             xhr.send();
             
             document.getElementById('appliedpartcategories').innerHTML+='<div style="text-align:left;padding:3px;" id="appliedpartcategoryid_'+partcategoryid+'">'+partcategoryname+' <button  onclick="removePartcategory(\''+partcategoryid+'\',\''+partcategoryname+'\')">x</button></div>';
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
                                <table>
                                    <tr><th>Name</th><td><input type="text" name="profilename" value="<?php echo $profile['name']; ?>"/></td></tr>
                                    <tr><th>Notes</th><td><textarea name="notes" rows="10" cols="50"><?php echo $profile['notes']; ?></textarea></td></tr>
                                    <tr><th>Parameters</th><td><textarea name="profiledata" rows="20" cols="50"><?php echo $profile['data']; ?></textarea></td></tr>
                                    <tr><th>Part Categories</th>
                                        <td>
                                            <div id="appliedpartcategories">
                                            <?php
                                            foreach ($appliedpartcategories as $partcategory) 
                                            {
                                                $partcategoryname=$pim->partCategoryName($partcategory);
                                                echo '<div style="text-align:left;padding:3px;" id="appliedpartcategoryid_'.$partcategory.'">'.$partcategoryname.' <button  onclick="removePartcategory(\''.$partcategory.'\',\''.$partcategoryname.'\')">x</button></div>';
                                            }
                                            ?>
                                            </div>

                                            <hr/>

                                            <div id="unappliedpartcategories">
                                            <?php
                                            foreach ($allpartcategories as $partcategory) 
                                            {   if(in_array($partcategory['id'], $appliedpartcategories)){continue;}
                                            
                                                echo '<div style="text-align:left;padding:3px;" id="unappliedpartcategoryid_'.$partcategory['id'].'">'.$pim->partCategoryName($partcategory['id']) . ' <button  onclick="addPartcategory(\''.$partcategory['id'].'\',\''.$partcategory['name'].'\')">+</button></div>';
                                            }
                                            ?>
                                            </div>

                                        </td>
                                    </tr>
                                    <tr><th></th><td><input name="submit" type="submit" value="Save"/> <input name="submit" type="submit" value="Delete"/></td></tr>
                                </table>
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