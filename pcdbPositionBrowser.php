<?php
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
$navCategory = 'settings';

$pim=new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'pcdbPositionBrowser.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pcdb=new pcdb;

$allpositions=array();
$mypositions=$pim->getFavoritePositions(); 
$idkeyedpositions=array(); foreach($mypositions as $myposition){$idkeyedpositions[$myposition['id']]=$myposition['name'];}

$searchposition='';
if(isset($_GET['submit']) && isset($_GET['searchtype']) && isset($_GET['searchterm']))
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
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddRemoveFavoritePosition.php?positionid='+positionid+'&action=add');
              xhr.send();
             }
             else
             { // has been clicked off
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddRemoveFavoritePosition.php?positionid='+positionid+'&action=remove');
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
                        <h3 class="card-header text-start">Favorite Application Positions<div style="float:right"><a class="btn btn-secondary" href="./pcdbPositionBrowser.php?searchtype=selected&searchterm=&submit=Search">Show only favorites</a></div></h3>

                        <div class="card-body">
                            <form method="get">
                                Position
                                <select name="searchtype">
                                    <option value="contains"<?php if($searchposition=='contains'){echo ' selected';}?>>Contains</option>
                                    <option value="begins"<?php if($searchposition=='begins'){echo ' selected';}?>>Begins with</option>
                                    <option value="ends"<?php if($searchposition=='ends'){echo ' selected';}?>>Ends with</option>
                                </select>
                                <input type="text" name="searchterm" value="<?php if(isset($_GET['searchterm'])){echo $_GET['searchterm'];}?>"/> 
                                <input name="submit" type="submit" value="Search"/>
                                
                                <?php if(count($allpositions)){?>
                                <div class="card">
                                    <!-- Header -->
                                    <h6 class="card-header">
                                        Search Results
                                    </h6>

                                    <div class="card-body scroll">
                                        <div class="card">
                                            <!-- Header -->
                                            <h6 class="card-header alert-primary">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        Name
                                                    </div>
                                                    <div class="col-md-3">
                                                        ID
                                                    </div>
                                                    <div class="col-md-3">
                                                        Favorite
                                                    </div>
                                                </div>
                                            </h6>
                                        </div>
                                        <?php foreach ($allpositions as $position)
                                        {
                                            $checked=''; if(array_key_exists($position['id'], $idkeyedpositions)){$checked=' checked';}
                                            echo '<div class="card">';
                                                echo '<h6 class="card-header">';
                                                    echo '<div class="row">';
                                                        echo '<div class="col-md-6">';
                                                            echo $position['name'];
                                                        echo '</div>';
                                                        echo '<div class="col-md-3">';
                                                            echo $position['id'];
                                                        echo '</div>';
                                                        echo '<div class="col-md-3">';
                                                            echo '<input type="checkbox" id="parttypeid_'.$position['id'].'" name="parttypeid_'.$position['id'].'" onclick="addRemoveType(\''.$position['id'].'\')"  '.$checked.'>';
                                                        echo '</div>';
                                                    echo '</div>';
                                                echo '</h6>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php
                                }
                                else
                                { // no results found
                                    if(isset($_GET['submit']))
                                    { // user submitted a search
                                        echo '<hr>';
                                        echo '<div class="alert alert-danger m-2">No Results Found</div>';
                                    }
                                }
                                ?> 
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
