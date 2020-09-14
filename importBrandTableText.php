<?php
include_once('./class/interchangeClass.php');
$navCategory = 'import/export';

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

        <!-- Header -->
        <h1>Import Part Prices</h1>

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <?php foreach($errors as $error){echo '<div style="color:red;">'.$error.'</div>';}?>
                    <form method="post">
                        <div style="padding:10px;"><div>Paste pipe-delimited data:<br/>BrandID|BrandName|BrandOwnerID|BrandOwner|ParentID|ParentCompany</div>
                            <textarea name="input" rows="20" cols="100"></textarea>
                        </div>
                        <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                    </form>
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