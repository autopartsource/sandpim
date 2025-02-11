<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/logsClass.php');
$navCategory = 'settings';

$pim=new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'competitiveBrandBrowser.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$interchange=new interchange;

$allbrands=array();
$competitivebrands=$interchange->getCompetitivebrands();
$brandAAIAIDkeyedcompetitivebrands=array(); foreach($competitivebrands as $competitivebrand){$brandAAIAIDkeyedcompetitivebrands[$competitivebrand['brandAAIAID']]=$competitivebrand['description'];}
$showowners=true;
$searchtype='';
if(isset($_GET['submit']) && isset($_GET['searchtype']) && isset($_GET['searchterm']))
{
    $searchtype=$_GET['searchtype'];
    $searchterm=$_GET['searchterm'];
    
    switch ($searchtype)
    {
        case 'begins':
            $allbrands=$interchange->getBrands($searchterm.'%');
            break;
        case 'contains':
            $allbrands=$interchange->getBrands('%'.$searchterm.'%');
            break;
        case 'ends':
            $allbrands=$interchange->getBrands('%'.$searchterm);
            break;
        case 'selected':
            $showowners=false; // we are not storing the owner codes in our local favorite brands table, we cant display owners when we show the favorites list
            foreach($competitivebrands as $competitivebrand)
            {
             $allbrands[]=array('BrandID'=>$competitivebrand['brandAAIAID'],'BrandName'=>$competitivebrand['description'],'BrandOwner'=>'');
            }
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
            function addRemoveBrand(brand)
            {
             if(document.getElementById('brand_'+brand).checked) 
             { // brand has been clicked on 
              //console.log('add:'+brand);
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddRemoveCompetitiveBrand.php?brand='+brand+'&action=add');
              xhr.send();
             }
             else
             { // brand has been clicked off
              //console.log('remove:'+brand);
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddRemoveCompetitiveBrand.php?brand='+brand+'&action=remove');
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
                        <h3 class="card-header text-start">Favorite Brands<div style="float:right;"><a class="btn btn-secondary" href="./competitiveBrandBrowser.php?searchtype=selected&searchterm=&submit=Search">Show only favorites</a></div></h3>

                        <div class="card-body">
                            <form method="get">
                                Brand Name
                                <select name="searchtype">
                                    <option value="contains"<?php if($searchtype=='contains'){echo ' selected';}?>>Contains</option>
                                    <option value="begins"<?php if($searchtype=='begins'){echo ' selected';}?>>Begins with</option>
                                    <option value="ends"<?php if($searchtype=='ends'){echo ' selected';}?>>Ends with</option>
                                </select>
                                <input type="text" name="searchterm" value="<?php if(isset($_GET['searchterm'])){echo $_GET['searchterm'];}?>"/> 
                                <input name="submit" type="submit" value="Search"/>
                                
                                <?php if($allbrands){
                                    $brandownercolumn='<div class="row">
                                                    <div class="col-md-6">
                                                        Name
                                                    </div>
                                                    <div class="col-md-3">
                                                        ID
                                                    </div>
                                                    <div class="col-md-3">
                                                        Favorite
                                                    </div>
                                                </div>'; 
                                    if($showowners){$brandownercolumn='<div class="row">
                                                    <div class="col-md-3">
                                                        Name
                                                    </div>
                                                    <div class="col-md-3">
                                                        ID
                                                    </div>
                                                    <div class="col-md-4">
                                                        Owner
                                                    </div>
                                                    <div class="col-md-2">
                                                        Favorite
                                                    </div>
                                                </div>';}
                                ?>
                                <div class="card">
                                    <!-- Header -->
                                    <h6 class="card-header">
                                        Search Results
                                    </h6>

                                    <div class="card-body scroll">
                                        <div class="card">
                                            <!-- Header -->
                                            <h6 class="card-header alert-primary">
                                                <?php 
                                                    echo $brandownercolumn; 
                                                ?>
                                            </h6>
                                        </div>
                                        <?php foreach ($allbrands as $brand)
                                        {
                                            $checked=''; if(array_key_exists($brand['BrandID'], $brandAAIAIDkeyedcompetitivebrands)){$checked=' checked';}
                                            $brandownercolumn='
                                                    <div class="col-md-6"><a href="./showBrand.php?brandid='.$brand['BrandID'].'">'.$brand['BrandName'].'</a></div>
                                                        <div class="col-md-3">
                                                        '.$brand['BrandID'].
                                                    '</div>
                                                    <div class="col-md-3">
                                                        <input type="checkbox" id="brand_'.$brand['BrandID'].'" name="brand_'.$brand['BrandID'].'" onclick="addRemoveBrand(\''.$brand['BrandID'].'\')"  '.$checked.'>
                                                    </div>'; 
                                            if($showowners){
                                                $brandownercolumn='
                                                    <div class="col-md-3">
                                                        '.$brand['BrandName'].
                                                    '</div>
                                                    <div class="col-md-3">
                                                        '.$brand['BrandID'].
                                                    '</div>
                                                    <div class="col-md-4">
                                                        '.$brand['BrandOwner'].
                                                    '</div>
                                                    <div class="col-md-2">
                                                        <input type="checkbox" id="brand_'.$brand['BrandID'].'" name="brand_'.$brand['BrandID'].'" onclick="addRemoveBrand(\''.$brand['BrandID'].'\')"  '.$checked.'>
                                                    </div>';
                                            }
                                            
                                            echo '<div class="card">';
                                                echo '<h6 class="card-header">';
                                                    echo '<div class="row">';
                                                        echo $brandownercolumn;
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