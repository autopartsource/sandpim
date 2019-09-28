<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');

$vcdb=new vcdb;
//$pim= new pim;

$makes=$vcdb->getMakes();
?>
<!DOCTYPE html>
<html>
 <head>
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <h1>Apps by Make/Equipment - Select Make</h1>

<?php foreach($makes as $make){echo '<div style="padding-left:20px;"><a href="mmySelectModel.php?makeid='.$make['id'].'">'.$make['name'].'</a></div>';}?>

 </div>
 </body>
</html>

