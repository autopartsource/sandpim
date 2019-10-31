<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pcdbClass.php');
include_once('/var/www/html/class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

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
  <style>
   .apppart {padding: 1px; border: 1px solid #808080; margin: 0px; background-color:#d0f0c0;}
   .apppart-cosmetic {padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#33FFD7;}
   .apppart-hidden {padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#FFD433;}
   .apppart-deleted { padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#FF5533;}

   a:link {color: blue; text-decoration: none;}
   a:visited {color: blue; text-decoration: none;}
   a:hover {color: gray; text-decoration: none;}
   a:active {color: blue; text-decoration: none;}

   table {border-collapse: collapse;}
   table, th, td {border: 1px solid black;}
  </style>
 </head>
 <body>
  <?php include('topnav.php');?>
  <h1>Parts</h1>
  <div style="padding:10px;">
   <form method="get" action="partsIndex.php">
    Show part numbers <select name="searchtype"><option value="equals">that are exactly</opton><option value="startswith">that starts with</opton><option value="contains">contains</opton></select> 
    <input type="text" name="partnumber" />

    in category <select name="partcategory"><option value="any">-- Any --</opton></select> 

    <input type="submit" name="submit" value="Search"/>
   </form>

   <?php if(count($parts)>0){?>
   <div style="padding-top:10px;">
    <table border="1">
     <tr><th>Part Number</th><th>Type</th><th>Category</th><th>Status</th></tr>
     <?php foreach($parts as $part){echo '<tr><td><a href="showPart.php?partnumber='.$part['partnumber'].'">'. $part['partnumber'].'</a></td><td>'.$pcdb->parttypeName($part['parttypeid']).'</td><td>'.$part['partcategoryname'].'</td><td>'.$part['lifecyclestatus'].'</td><tr>';}?>
    </table>
   </div>
   <?php }?>
  </div>
 </body>
</html>
