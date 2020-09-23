<?php
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');

$navCategory = 'settings';


session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pcdb=new pcdb;
$pim=new pim;

 $alltypes=array();
 $mytypes=$pim->getFavoriteParttypes(); $idkeyedmytypes=array(); foreach($mytypes as $mytype){$idkeyedmytypes[$mytype['id']]=$mytype['name'];}

$searchtype='';
if(isset($_GET['submit']) && isset($_GET['searchtype']) && isset($_GET['searchterm']))
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
        case 'selected':
            $alltypes=$mytypes;
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
              //console.log('add:'+parttypeid);
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddRemoveFavoriteParttype.php?parttypeid='+parttypeid+'&action=add');
              xhr.send();
             }
             else
             { // has been clocked off
              //console.log('remove:'+parttypeid);
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddRemoveFavoriteParttype.php?parttypeid='+parttypeid+'&action=remove');
              xhr.send();
             }
            }

        </script>
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
                        <h3 class="card-header text-left">Favorite PCdb PartTypes<div style="float:right"><a class="btn btn-secondary" href="pcdbTypeBrowser.php?searchtype=selected&searchterm=&submit=Search">Show only already selected types</a></div></h3>

                        <div class="card-body">
                            <form method="get">
                                Part Type Name
                                <select name="searchtype">
                                    <option value="begins"<?php if($searchtype=='begins'){echo ' selected';}?>>Begins with</option>
                                    <option value="contains"<?php if($searchtype=='contains'){echo ' selected';}?>>Contains</option>
                                    <option value="ends"<?php if($searchtype=='ends'){echo ' selected';}?>>Ends with</option>
                                </select>
                                <input type="text" name="searchterm" value="<?php if(isset($_GET['searchterm'])){echo $_GET['searchterm'];}?>"/> 
                                <input name="submit" type="submit" value="Search"/>
                                
                                <?php if(count($alltypes)){?>
                                <div class="card">
                                    <!-- Header -->
                                    <h6 class="card-header text-left">Search Results</h6>

                                    <div class="card-body scroll">
                                        <table><tr><th>Name</th><th>ID</th><th>Favorite</th></tr>
                                            <?php foreach ($alltypes as $type)
                                             {
                                                $checked=''; if(array_key_exists($type['id'], $idkeyedmytypes)){$checked=' checked';}
                                                 echo '<tr><td style="text-align:left;">'.$type['name'].'</td><td>'.$type['id'].'</td>';
                                                 echo '<td align="center"><input type="checkbox" id="parttypeid_'.$type['id'].'" name="parttypeid_'.$type['id'].'" onclick="addRemoveType(\''.$type['id'].'\')"  '.$checked.'></td>';
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
                                </div>
                                    
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