<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pimClass.php');
$navCategory = 'assets';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$v=new vcdb;
$pim= new pim;
$error_msg=false;


if(isset($_POST['submit']) && $_POST['submit']=='Upload')
{
 $target_dir = '/var/www/html/ACESuploads/'; $target_file = $target_dir.basename($_FILES['fileToUpload']['name']);

 // Check if file already exists
 if(!file_exists($target_file))
 {
  if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_file))
  {
   $outputfile=''; $parameters='appcategory:'.$_POST['appcategory'].';'; $datetimetostart=date('Y-m-d H:i:s');
   $error_msg='The file ['. basename( $_FILES['fileToUpload']['name']). '] has been uploaded.';
  }
  else
  {
   $error_msg='Error uploading file';
  }
 }
 else
 {
  $error_msg='File already exists';
 }
}

?>
<!DOCTYPE html>
<html>
 <head>
     <link rel="stylesheet" type="text/css" href="styles.css" />
 </head>
 <body>
  <?php include('topnav.php');?>
  <h1>Upload Asset File</h1>
  <div>
   <?php if($error_msg){echo $error_msg;}?>
   <form method="post" enctype="multipart/form-data">
    <div style="padding:10px;"><input type="file" name="fileToUpload" id="fileToUpload"></div>
    <div style="padding:10px;"><input name="submit" type="submit" value="Upload"/></div>
   </form>
  </div>
 </body>
</html>
