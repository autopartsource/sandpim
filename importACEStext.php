<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
$navCategory = 'import';

$pim = new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'importACEStext.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$v = new vcdb;

$partcategories = $pim->getPartCategories();
$resultmessage='';


/*
cosmetic	basevid	item	parttypeid	position	qty	vcdbattributes (name|value|sequence|cosmetic)	qdbqualifiers (id|p1|UoM1|p2|UoM2…)	notes (text|sequence|cosmetic)
0	144067	ITM20200619	1896	22	1	FrontBrakeType|5|3|1~SubModel|20|2|0~		Some nice notes|1|0~Additional Cosmetic notes|2|1~

*/

if (isset($_POST['input']) && $_POST['submit']='Import')
{ // this is the copy-n-paste option
 $rows= explode("\r\n", $_POST['input']);
 if(count($rows)<5000)
 {
  $app_count = $pim->createAppsFromText($_POST['input'],intval($_POST['partcategory']));
  $resultmessage=$app_count . ' apps created';
 }
 else
 {// dataset is too big. Write it to temp file for background process to handle next time it is called by cron
  $randomstring=$pim->uuidv4();
  $localfilename=__DIR__.'/ACESuploads/'.$randomstring;
  if(file_put_contents($localfilename, $_POST['input']))
  {
   $token=$pim->createBackgroundjob('ACESflatImport','started',$_SESSION['userid'],$localfilename,'','partcategory:'.intval($_POST['partcategory']).';',date('Y-m-d H:i:s'),'text/xml','');
   $logs->logSystemEvent('Import', $_SESSION['userid'], 'ACES flat text import setup for houskeeper by:'.$_SERVER['REMOTE_ADDR']);
   $resultmessage='Input data was queued for background process to import ('.count($rows).' input lines). Got to <a href="./backgroundJobs.php"> Settings -> Manage background import/export jobs</a> to monitor progress';      
  }
  else    
  {// file write was 0 bytes (failure)
   $resultmessage='Failed to write input text to local file';
  }
 }  
}

if(isset($_POST['submit']) && $_POST['submit']=='Upload')
{// this is the CSV upload option
 if($_FILES['fileToUpload']['type']=='text/csv')
 {
  if($_FILES['fileToUpload']['size']<5000000 || isset($_SESSION['userid']))   
  {
   $originalFilename= basename($_FILES['fileToUpload']['name']);
   $resultmessage=$originalFilename.' was uploaded';   
  }
  else
  {
   $resultmessage='upload was too big';
  }
 }
 else
 {
  $resultmessage='file uploaded was not a CSV ('.$_FILES['fileToUpload']['type'].')';     
 }
}



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
                        <?php if($resultmessage!=''){echo '<div>'.$resultmessage.'</div>';}?>
                        <h3 class="card-header text-start">Import applications from structured text</h3>

                        <div class="card-body">
                            <div class="card shadow-sm">
                                <h5 class="card-header">Paste 9 columns of tab-delimited application data for import (no header row))</h5>
                                <div class="card-body">
                                <form method="post">
                                    <div class="card"><div class="card-header"><i>[cosmetic (0 or 1), BaseVehicleID, Partnumber, PartTypeID, PositionID, Qty, VCdb Attributes, Qdb Qualifiers, Notes]</i></div></div>
                                    <textarea style="width:100%;height:200px;"  name="input"></textarea>
                                    <div>Category for part creation <select name="partcategory"><option value="0">Do not create parts</option> <?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"><?php echo $partcategory['name']; ?></option><?php } ?></select></div>
                                    <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                                    
                                    <div class="card"><div class="card-header"><strong>VCdb Attributes (tilde-delimited)</strong><br/> name|value|sequence|cosmetic</div></div>
                                    <div class="card"><div class="card-header"><strong>Qdb Qualifiers (tilde-delimited)</strong><br/> Qdbid|sequence|cosmetic|parm1value|parm1UoM|parm2value|parm2UoM|parm3value|parm3UoM...</div></div>
                                    <div class="card"><div class="card-header"><strong>Notes (tilde-delimited)</strong><br/> notetext|sequence|cosmetic</div></div>
                                    
                                </form>
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