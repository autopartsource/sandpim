<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/logsClass.php');
$navCategory = 'import';

$pim=new pim;
$logs = new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'importBrandTable.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$interchange = new interchange;
$errors=array();
$results=array();
$brands=array();

if(isset($_POST['input'])) 
{
 $clearfirst=false; if(isset($_POST['clearfirst'])){$clearfirst=true;}
 $input = $_POST['input'];
 $records = explode("\r\n", $_POST['input']);
 foreach ($records as $record) 
 {
  $fields = explode('|', $record);
  
  if(count($fields)==1 && $fields[0]==''){continue;}
  
  if (count($fields) == 6) 
  {
   $partnumber = trim(strtoupper($fields[0]));

   $BrandID=str_replace('"','',trim($fields[0]));
   $BrandName=str_replace('"','',trim($fields[1]));
   $BrandOwnerID=str_replace('"','',trim($fields[2]));
   $BrandOwner=str_replace('"','',trim($fields[3]));
   $ParentID=str_replace('"','',trim($fields[4]));
   $ParentCompany=str_replace('"','',trim($fields[5]));
   $brands[]=array('BrandID'=>$BrandID,'BrandName'=>$BrandName,'BrandOwnerID'=>$BrandOwnerID,'BrandOwner'=>$BrandOwner,'ParentID'=>$ParentID,'ParentCompany'=>$ParentCompany);
  }
  else
  {// field count is wrong
   $errors[]='Field count was wrong (expected exactly 6 pipe-delimited columns)';
  }
 }
 $interchange->importBrandTable($brands,$clearfirst);
 
 if($clearfirst)
 {
  $results[]='Imported '.count($brands).' brand records after clearing existing data';
  $logs->logSystemEvent('import',$_SESSION['userid'], 'Imported '.count($brands).' brand records after clearing existing data');
 }
 else
 {
  $results[]='Imported '.count($brands).' brand records on top of existing data';     
  $logs->logSystemEvent('import',$_SESSION['userid'], 'Imported '.count($brands).' brand records on top of existing data');
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
                        <h3 class="card-header text-start">Import AutoCare Brand table (Original Format)</h3>

                        <div class="card-body">
                            <?php foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}?>
                            <?php foreach($results as $result){echo '<div class="alert alert-success" role="alert">'.$result.'</div>';}?>
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste pipe-delimited data (no header row):</h6>
                                    <p>BrandID, BrandName, BrandOwnerID, BrandOwner, ParentID, ParentCompany</p>
                                    <p>All data will be cleared from the brand list and replaced with the imported list. The double-quote character will be ignored.</p>
                                </div>
                                <textarea name="input" style="width:100%;" rows="15"></textarea>
                                <div><input type="checkbox" name="clearfirst"/>Clear existing data before import</div>

                                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
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