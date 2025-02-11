<?php
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');

$navCategory = 'settings';

$pim=new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'pcdbTypeBrowser.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pcdb=new pcdb;

 $alltypes=array();
 $mytypes=$pim->getFavoriteParttypes(); 
 $idkeyedmytypes=array(); foreach($mytypes as $mytype){$idkeyedmytypes[$mytype['id']]=$mytype['name'];}

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
                        <h3 class="card-header text-start">Favorite Part Types<div style="float:right"><a class="btn btn-secondary" href="pcdbTypeBrowser.php?searchtype=selected&searchterm=&submit=Search">Show only favorites</a></div></h3>

                        <div class="card-body">
                            <form method="get">
                                Part Type Name
                                <select name="searchtype">
                                    <option value="contains"<?php if($searchtype=='contains'){echo ' selected';}?>>Contains</option>
                                    <option value="begins"<?php if($searchtype=='begins'){echo ' selected';}?>>Begins with</option>
                                    <option value="ends"<?php if($searchtype=='ends'){echo ' selected';}?>>Ends with</option>
                                </select>
                                <input type="text" name="searchterm" value="<?php if(isset($_GET['searchterm'])){echo $_GET['searchterm'];}?>"/> 
                                <input name="submit" type="submit" value="Search"/>
                                
                                <?php if(count($alltypes)){?>
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
                                        <?php foreach ($alltypes as $type)
                                        {
                                            $checked=''; if(array_key_exists($type['id'], $idkeyedmytypes)){$checked=' checked';}
                                            echo '<div class="card">';
                                                echo '<h6 class="card-header">';
                                                    echo '<div class="row">';
                                                        echo '<div class="col-md-6">';
                                                            echo $type['name'];
                                                        echo '</div>';
                                                        echo '<div class="col-md-3">';
                                                            echo $type['id'];
                                                        echo '</div>';
                                                        echo '<div class="col-md-3">';
                                                            echo '<input type="checkbox" id="parttypeid_'.$type['id'].'" name="parttypeid_'.$type['id'].'" onclick="addRemoveType(\''.$type['id'].'\')"  '.$checked.'>';
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