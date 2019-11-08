<?php
include_once('/var/www/html/class/pcdbClass.php');
include_once('/var/www/html/class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pcdb=new pcdb;
$pim=new pim;

$types=array();

$searchtype='';
if(isset($_GET['searchtype']) && isset($_GET['searchterm']) && $_GET['searchterm']!='')
{
    $searchtype=$_GET['searchtype'];
    $searchterm=$_GET['searchterm'];
    
    switch ($searchtype)
    {
        case 'begins':
            $types=$pcdb->getPartTypes($searchterm.'%');
            break;
        case 'contains':
            $types=$pcdb->getPartTypes('%'.$searchterm.'%');
            break;
        case 'ends':
            $types=$pcdb->getPartTypes('%'.$searchterm);
            break;
        
        default :break;
    }
}



?>
<!DOCTYPE html>
<html>
 <head>
  <script>
   function addRemoveType(parttypeid)
   {
    if(document.getElementById('parttypeid_'+parttypeid).checked) 
    { // parttype has been clicked on 
     console.log('add:'+parttypeid);
     //var xhr = new XMLHttpRequest();
     //xhr.open('GET', 'ajaxAddRemoveFavoriteParttype.php?parttypeid='+parttypeid+'&action=add');
     //xhr.send();
    }
    else
    { // appcategory has been clocked off
     console.log('remove:'+parttypeid);

    //var xhr = new XMLHttpRequest();
    // xhr.open('GET', 'ajaxAddRemoveFavoriteParttype.php?parttypeid='+parttypeid+'&action=remove');
    // xhr.send();
    }
   }

  </script>
     
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
  <h1>PCdb parttypes and positions</h1>
  <form method="get">
      Part Type Name
      <select name="searchtype">
          <option value="begins"<?php if($searchtype=='begins'){echo ' selected';}?>>Begins with</option>
          <option value="contains"<?php if($searchtype=='contains'){echo ' selected';}?>>Contains</option>
          <option value="ends"<?php if($searchtype=='ends'){echo ' selected';}?>>Ends with</option>
      </select>
      <input type="text" name="searchterm"/> 
      <input name="submit" type="submit" value="Search"/>
   <div style="padding-left:10px;">
       <table><tr><th>Name</th><th>ID</th><th>Favorite</th></tr>
       <?php foreach ($types as $type)
       {
        echo '<tr><td>'.$type['name'].'</td><td>'.$type['id'].'</td>';
        echo '<td align="center"><input type="checkbox" id="parttypeid_'.$type['id'].'" name="parttypeid_'.$type['id'].'" onclick="addRemoveType(\''.$type['id'].'\')" name="appcategory_'.$appcategory['id'].'"  ></td>';
        echo '</tr>';
       }
       ?>
    </table>
   </div>
  </form>
 </body>
</html>
