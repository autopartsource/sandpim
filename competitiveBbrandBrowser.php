<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');

$navCategory = 'settings';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$interchange=new interchange;
$pim=new pim;

$allbrands=array();
$competitivebrands=$interchange->getCompetitivebrands();
$brandAAIAIDkeyedcompetitivebrands=array(); foreach($competitivebrands as $competitivebrand){$brandAAIAIDkeyedcompetitivebrands[$competitivebrand['brandAAIAID']]=$competitivebrand['description'];}

$searchtype='';
if(isset($_GET['submit']) && isset($_GET['searchtype']) && isset($_GET['searchterm']) && $_GET['searchterm']!='')
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
             { // parttype has been clicked on 
              //console.log('add:'+brand);
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddRemoveCompetitiveBrand.php?brand='+brand+'&action=add');
              xhr.send();
             }
             else
             { // has been clocked off
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
        
        <!-- Header -->
        <h1>Competitive Brands (system-wide)</h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <form method="get">
                    Brand Name
                    <select name="searchtype">
                        <option value="begins"<?php if($searchtype=='begins'){echo ' selected';}?>>Begins with</option>
                        <option value="contains"<?php if($searchtype=='contains'){echo ' selected';}?>>Contains</option>
                        <option value="ends"<?php if($searchtype=='ends'){echo ' selected';}?>>Ends with</option>
                    </select>
                    <input type="text" name="searchterm" value="<?php if(isset($_GET['searchterm'])){echo $_GET['searchterm'];}?>"/> 
                    <input name="submit" type="submit" value="Search"/>
                 <div style="padding:15px;">
                     <?php if(count($allbrands)){?>
                     <table><tr><th>Name</th><th>ID</th><th>Owner</th><th>Selected</th></tr>
                     <?php foreach ($allbrands as $brand)
                      {
                         $checked=''; if(array_key_exists($brand['BrandID'], $brandAAIAIDkeyedcompetitivebrands)){$checked=' checked';}
                          echo '<tr><td>'.$brand['BrandName'].'</td><td>'.$brand['BrandID'].'</td><td>'.$brand['BrandOwner'].'</td>';
                          echo '<td align="center"><input type="checkbox" id="brand_'.$brand['BrandID'].'" name="brand_'.$brand['BrandID'].'" onclick="addRemoveBrand(\''.$brand['BrandID'].'\')" name="brand_'.$brand['BrandID'].'"  '.$checked.'></td>';
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