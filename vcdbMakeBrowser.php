<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');

$navCategory = 'settings';

$pim=new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'vcdbMakeBrowser.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;

 $allmakes=array();
 $mymakes=$pim->getFavoriteMakes();
 $idkeyedmymakes=array(); foreach($mymakes as $mymake){$idkeyedmymakes[$mymake['id']]=$mymake['name'];}

$searchtype='';
if(isset($_GET['submit']) && isset($_GET['searchtype']) && isset($_GET['searchterm']))
{
    $searchtype=$_GET['searchtype'];
    $searchterm=$_GET['searchterm'];
    
    switch ($searchtype)
    {
        case 'begins':
            $allmakes=$vcdb->getMakes($searchterm.'%');
            break;
        case 'contains':
            $allmakes=$vcdb->getMakes('%'.$searchterm.'%');
            break;
        case 'ends':
            $allmakes=$vcdb->getMakes('%'.$searchterm);
            break;
        case 'selected':
            $allmakes=$mymakes;
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
            function addRemoveMake(makeid)
            {
             if(document.getElementById('makeid_'+makeid).checked) 
             { // make has been clicked on 
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddRemoveFavoriteMake.php?makeid='+makeid+'&action=add');
              xhr.send();
             }
             else
             { // has been clocked off
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddRemoveFavoriteMake.php?makeid='+makeid+'&action=remove');
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
                        <h3 class="card-header text-start">Favorite Vehicle Makes<div style="float:right"><a class="btn btn-secondary" href="vcdbMakeBrowser.php?searchtype=selected&searchterm=&submit=Search">Show only favorites</a></div></h3>

                        <div class="card-body">
                            <form method="get">
                                Make Name
                                <select name="searchtype">
                                    <option value="contains"<?php if($searchtype=='contains'){echo ' selected';}?>>Contains</option>
                                    <option value="begins"<?php if($searchtype=='begins'){echo ' selected';}?>>Begins with</option>
                                    <option value="ends"<?php if($searchtype=='ends'){echo ' selected';}?>>Ends with</option>
                                </select>
                                <input type="text" name="searchterm" value="<?php if(isset($_GET['searchterm'])){echo $_GET['searchterm'];}?>"/> 
                                <input name="submit" type="submit" value="Search"/>
                                
                                <?php if(count($allmakes)){?>
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
                                        <?php foreach ($allmakes as $make)
                                        {
                                            $checked=''; if(array_key_exists($make['id'], $idkeyedmymakes)){$checked=' checked';}
                                            echo '<div class="card">';
                                                echo '<h6 class="card-header">';
                                                    echo '<div class="row">';
                                                        echo '<div class="col-md-6">';
                                                            echo $make['name'];
                                                        echo '</div>';
                                                        echo '<div class="col-md-3">';
                                                            echo $make['id'];
                                                        echo '</div>';
                                                        echo '<div class="col-md-3">';
                                                            echo '<input type="checkbox" id="makeid_'.$make['id'].'" name="makeid_'.$make['id'].'" onclick="addRemoveMake(\''.$make['id'].'\')"  '.$checked.'>';
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