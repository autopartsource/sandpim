<?php
include_once('/var/www/html/class/pcdbClass.php');
include_once('/var/www/html/class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pcdb=new pcdb;
$pim=new pim;

 $allpositions=array();
 $mypositions=$pim->getFavoritePositions(); 
 $idkeyedpositions=array(); foreach($mypositions as $myposition){$idkeyedpositions[$myposition['id']]=$myposition['name'];}

$searchposition='';
if(isset($_GET['submit']) && isset($_GET['searchtype']) && isset($_GET['searchterm']) && $_GET['searchterm']!='')
{
    $searchtype=$_GET['searchtype'];
    $searchterm=$_GET['searchterm'];
    
    switch ($searchtype)
    {
        case 'begins':
            $allpositions=$pcdb->getPositions($searchterm.'%');
            break;
        case 'contains':
            $allpositions=$pcdb->getPositions('%'.$searchterm.'%');
            break;
        case 'ends':
            $allpositions=$pcdb->getPositions('%'.$searchterm);
            break;
        
        default :break;
    }
}

?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="styles.css" />
        <script>
            function addRemovePosition(positioneid)
            {
             if(document.getElementById('positionid_'+positionid).checked) 
             { // parttype has been clicked on 
              console.log('add:'+positionid);
              //var xhr = new XMLHttpRequest();
              //xhr.open('GET', 'ajaxAddRemoveFavoritePosition.php?positionid='+positionid+'&action=add');
              //xhr.send();
             }
             else
             { // appcategory has been clocked off
              console.log('remove:'+positionid);

             //var xhr = new XMLHttpRequest();
             // xhr.open('GET', 'ajaxAddRemoveFavoritePosition.php?positionid='+positionid+'&action=remove');
             // xhr.send();
             }
            }

        </script>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>PCdb positions</h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
            <form method="get">
                Position
                <select name="searchtype">
                    <option value="begins"<?php if($searchtype=='begins'){echo ' selected';}?>>Begins with</option>
                    <option value="contains"<?php if($searchtype=='contains'){echo ' selected';}?>>Contains</option>
                    <option value="ends"<?php if($searchtype=='ends'){echo ' selected';}?>>Ends with</option>
                </select>
                <input type="text" name="searchterm" value="<?php if(isset($_GET['searchterm'])){echo $_GET['searchterm'];}?>"/> 
                <input name="submit" type="submit" value="Search"/>
             <div style="padding-left:10px;">
                 <?php if(count($allpositions)){?>
                 <table><tr><th>Name</th><th>ID</th><th>Favorite</th></tr>
                 <?php foreach ($allpositions as $position)
                  {
                     $checked=''; if(array_key_exists($position['id'], $idkeyedpositions)){$checked=' checked';}
                      echo '<tr><td>'.$position['name'].'</td><td>'.$position['id'].'</td>';
                      echo '<td align="center"><input type="checkbox" id="positionid_'.$position['id'].'" name="positionid_'.$position['id'].'" onclick="addRemovePosition(\''.$position['id'].'\')" '.$checked.'></td>';
                      echo '</tr>';
                  }
                 }
                 else
                 { // no results found
                     if(isset($_GET['submit']))
                     { // user submitted a search
                         echo '<div style="padding:10px;">No Results Found</div>';
                     }
                 }
                 ?>
              </table>
             </div>
            </form>
            </div>

            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>
