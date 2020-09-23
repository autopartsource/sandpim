<?php
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');

$navCategory = 'settings';


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
        case 'selected':
            $allpositions=$mypositions;
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
            function addRemovePosition(positionid)
            {
             if(document.getElementById('positionid_'+positionid).checked) 
             { // parttype has been clicked on 
              console.log('add:'+positionid);
//              var xhr = new XMLHttpRequest();
//              xhr.open('GET', 'ajaxAddRemoveFavoritePosition.php?positionid='+positionid+'&action=add');
//              xhr.send();
             }
             else
             { // has been clocked off
              console.log('remove:'+positionid);

//             var xhr = new XMLHttpRequest();
//              xhr.open('GET', 'ajaxAddRemoveFavoritePosition.php?positionid='+positionid+'&action=remove');
//              xhr.send();
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
                        <h3 class="card-header text-left">Favorite PCdb Positions<div style="float:right"><a class="btn btn-secondary" href="pcdbPositionBrowser.php?searchtype=selected&searchterm=&submit=Search">Show only already selected positions</a></div></h3>

                        <div class="card-body">
                            <form method="get">
                                Position
                                <select name="searchtype">
                                    <option value="begins"<?php if($searchposition=='begins'){echo ' selected';}?>>Begins with</option>
                                    <option value="contains"<?php if($searchposition=='contains'){echo ' selected';}?>>Contains</option>
                                    <option value="ends"<?php if($searchposition=='ends'){echo ' selected';}?>>Ends with</option>
                                </select>
                                <input type="text" name="searchterm" value="<?php if(isset($_GET['searchterm'])){echo $_GET['searchterm'];}?>"/> 
                                <input name="submit" type="submit" value="Search"/>
                                
                                <?php if(count($allpositions)){?>
                                <div class="card">
                                    <!-- Header -->
                                    <h6 class="card-header text-left">Search Results</h6>

                                    <div class="card-body scroll">
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
