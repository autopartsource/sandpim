<?php
include_once('./class/interchangeClass.php');
$navCategory = 'import';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$interchange = new interchange;
$errors=array();
$brands=array();

if(isset($_POST['input'])) 
{
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
 $interchange->importBrandTable($brands);
 $errors[]='Imported '.count($brands).' brand records';
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
                        <h3 class="card-header text-start">Import Part Prices</h3>

                        <div class="card-body">
                            <?php foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}?>
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste pipe-delimited data:</h6>
                                    <p>BrandID|BrandName|BrandOwnerID|BrandOwner|ParentID|ParentCompany</p>
                                </div>
                                <textarea name="input" rows="20" cols="100"></textarea>
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