<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pcdbClass.php');
include_once('/var/www/html/class/pimClass.php');

$vcdb=new vcdb;
$pcdb=new pcdb;
$pim= new pim;

if(isset($_GET['partnumber']) && strlen($_GET['partnumber'])<=20 )
{
 $searchtype='equals'; if(isset($_GET['searchtype']) && ($_GET['searchtype']=='contains' || $_GET['searchtype']=='startswith')){$searchtype=$_GET['searchtype'];}
 $partnumber=strtoupper($_GET['partnumber']);
 $limit=30;
 $parts=$pim->getParts($partnumber,$searchtype,$limit);
}


?>
<!DOCTYPE html>
<html>
 <head>
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <h1>Parts</h1>
  <div style="padding:10px;">
   <form method="get" action="partsIndex.php">
    Show part numbers <select name="searchtype"><option value="equals">that are exactly</opton><option value="startswith">that starts with</opton><option value="contains">contains</opton></select> 
    <input type="text" name="partnumber" />

    in category <select name="partcategory"><option value="any">-- Any --</opton></select> 

    <input type="submit" name="submit" value="Search"/>
   </form>
   <div style="padding-top:10px;">
    <table border="1">
     <tr><th>Part Number</th><th>Type</th><th>Category</th><th>Status</th></tr>
     <?php foreach($parts as $part){echo '<tr><td><a href="showPart.php?partnumber='.$part['partnumber'].'">'. $part['partnumber'].'</a></td><td>'.$pcdb->parttypeName($part['parttypeid']).'</td><td>'.$part['partcategoryname'].'</td><td>'.$part['lifecyclestatus'].'</td><tr>';}?>
    </table>
   </div>
  </div>
 </div>
 </body>
</html>
