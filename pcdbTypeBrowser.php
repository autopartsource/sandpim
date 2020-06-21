<?php
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pcdb=new pcdb;
$pim=new pim;

 $alltypes=array();
 $mytypes=$pim->getFavoriteParttypes(); $idkeyedmytypes=array(); foreach($mytypes as $mytype){$idkeyedmytypes[$mytype['id']]=$mytype['name'];}

$searchtype='';
if(isset($_GET['submit']) && isset($_GET['searchtype']) && isset($_GET['searchterm']) && $_GET['searchterm']!='')
{
    $searchtype=$_GET['searchtype'];
    $searchterm=$_GET['searchterm'];
    
    switch ($searchtype)
    {
        case 'begins':
            $alltypes=$pcdb->getPartTypes($searchterm.'%');
            break;
        case 'contains':
            $alltypes=$pcdb->getPartTypes('%'.$searchterm.'%');
            break;
        case 'ends':
            $alltypes=$pcdb->getPartTypes('%'.$searchterm);
            break;
        
        default :break;
    }
}

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
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
             { // has been clocked off
              console.log('remove:'+parttypeid);

             //var xhr = new XMLHttpRequest();
             // xhr.open('GET', 'ajaxAddRemoveFavoriteParttype.php?parttypeid='+parttypeid+'&action=remove');
             // xhr.send();
             }
            }

        </script>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>PCdb parttypes and positions</h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <form method="get">
                    Part Type Name
                    <select name="searchtype">
                        <option value="begins"<?php if($searchtype=='begins'){echo ' selected';}?>>Begins with</option>
                        <option value="contains"<?php if($searchtype=='contains'){echo ' selected';}?>>Contains</option>
                        <option value="ends"<?php if($searchtype=='ends'){echo ' selected';}?>>Ends with</option>
                    </select>
                    <input type="text" name="searchterm" value="<?php if(isset($_GET['searchterm'])){echo $_GET['searchterm'];}?>"/> 
                    <input name="submit" type="submit" value="Search"/>
                 <div style="padding-left:10px;">
                     <?php if(count($alltypes)){?>
                     <table><tr><th>Name</th><th>ID</th><th>Favorite</th></tr>
                     <?php foreach ($alltypes as $type)
                      {
                         $checked=''; if(array_key_exists($type['id'], $idkeyedmytypes)){$checked=' checked';}
                          echo '<tr><td>'.$type['name'].'</td><td>'.$type['id'].'</td>';
                          echo '<td align="center"><input type="checkbox" id="parttypeid_'.$type['id'].'" name="parttypeid_'.$type['id'].'" onclick="addRemoveType(\''.$type['id'].'\')" name="partcategory_'.$partcategory['id'].'"  '.$checked.'></td>';
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
        <?php include('./includes/footer.php'); ?>
    </body>
</html>