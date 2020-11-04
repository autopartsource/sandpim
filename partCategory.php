<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'settings';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$logs = new logs;

if (isset($_POST['submit']) && $_POST['submit']=='Cancel') 
{
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./partCategories.php'\" /></head><body></body></html>";
 exit;
}


if (isset($_POST['submit']) && $_POST['submit']=='Save') 
{
 if($_POST['name']!=$_POST['oldname'])   
 {
  $pim->updatePartcategoryName(intval($_POST['id']), $_POST['name']); 
  $logs->logSystemEvent('partcategorychange', $_SESSION['userid'], 'Part Category '.$_POST['id'].' name was changed from '.$_POST['oldname'].' to '.$_POST['name']);
 }
 if($_POST['brandID']!=$_POST['oldbrandID'])   
 {
  $pim->updatePartcategoryBrandID(intval($_POST['id']), $_POST['brandID']); 
  $logs->logSystemEvent('partcategorychange', $_SESSION['userid'], 'Part Category '.$_POST['id'].' brandAAIAID was changed from '.$_POST['oldbrandID'].' to '.$_POST['brandID']);
 }
 
 if($_POST['subbrandID']!=$_POST['oldsubbrandID'])   
 {
  $pim->updatePartcategorySubbrandID(intval($_POST['id']), $_POST['subbrandID']); 
  $logs->logSystemEvent('partcategorychange', $_SESSION['userid'], 'Part Category '.$_POST['id'].' subbrandAAIAID was changed from '.$_POST['oldsubbrandID'].' to '.$_POST['subbrandID']);
 }
 
 if($_POST['mfrlabel']!=$_POST['oldmfrlabel'])   
 {
  $pim->updatePartcategoryMfrlabel(intval($_POST['id']), $_POST['mfrlabel']); 
  $logs->logSystemEvent('partcategorychange', $_SESSION['userid'], 'Part Category '.$_POST['id'].' Mfrlabel was changed from '.$_POST['oldmfrlabel'].' to '.$_POST['mfrlabel']);
 }
 
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./partCategories.php'\" /></head><body></body></html>";
 exit;
}


$partcategory = $pim->getPartCategory(intval($_GET['id']));

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
                        <h3 class="card-header text-left">Part Category</h3>

                        <div class="card-body text-left">
                            <form action="" method="post">
                                <div class="card">
                                    <h6 class="card-header">
                                        <div class="form-group row">
                                            <label for="inputName" class="col-sm-2 col-form-label">Name:</label>
                                            <div class="col-sm-10">
                                                <input id="inputName" type="text" class="form-control" type="text" name="name" value="<?php echo $partcategory['name'];?>"/>
                                            </div>
                                        </div>
                                    </h6>
                                    <div class="card-body">
                                        <div class="form-group row">
                                            <label for="inputBrandAAIAID" class="col-sm-2 col-form-label">BrandAAIAID</label>
                                            <div class="col-sm-10">
                                                <input id="inputBrandAAIAID" type="text" class="form-control" name="brandID" value="<?php echo $partcategory['brandID'];?>"/>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="form-group row">
                                            <label for="inputSubBrandAAIAID" class="col-sm-2 col-form-label">SubBrandAAIAID</label>
                                            <div class="col-sm-10">
                                                <input id="inputSubBrandAAIAID" type="text" class="form-control" name="subbrandID" value="<?php echo $partcategory['subbrandID'];?>"/></td></tr>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="form-group row">
                                            <label for="inputMfrLabel" class="col-sm-2 col-form-label">MfrLabel (ACES)</label>
                                            <div class="col-sm-10">
                                                <input id="inputMfrLabel" type="text" class="form-control" name="mfrlabel" value="<?php echo $partcategory['mfrlabel'];?>"/></td></tr>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="form-group row">
                                            <label for="staticSandpiperSliceUUID" class="col-sm-2 col-form-label">Sandpiper Slice UUID</label>
                                            <div class="col-sm-10">
                                                <input id="staticSandpiperSliceUUID" readonly type="text" class="form-control" value="<?php echo $partcategory['sandpipersliceuuid'];?>"/></td></tr>
                                            </div>
                                        </div>
                                        <hr>
                                        <div>
                                            <input name="submit" type="submit" value="Cancel"/> <input name="submit" type="submit" value="Save"/>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="id" value="<?php echo $partcategory['id'];?>"/>
                                <input type="hidden" name="oldname" value="<?php echo $partcategory['name'];?>"/>
                                <input type="hidden" name="oldbrandID" value="<?php echo $partcategory['brandID'];?>"/>
                                <input type="hidden" name="oldsubbrandID" value="<?php echo $partcategory['subbrandID'];?>"/>
                                <input type="hidden" name="oldmfrlabel" value="<?php echo $partcategory['mfrlabel'];?>"/>
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