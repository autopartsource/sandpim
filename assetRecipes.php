<?php
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/assetClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsClass.php');
$navCategory = 'settings';

$pim= new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'assetRecipes.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pcdb= new pcdb();
$asset=new asset();
$configGet= new configGet;
$configSet= new configSet;
$logs= new logs;

$defaultdescriptionlanguagecode=$configGet->getConfigValue('defaultDescriptionLanguageCode','EN');

if(isset($_GET['submit']) && $_GET['submit']=='Create Recipe')
{
 $partcategory=intval($_GET['partcategory']); 
 $parttypeid=intval($_GET['parttypeid']); 
 $assettypecode=$_GET['assettypecode']; 
// $pim->addPartDescriptionRecipe($partcategory, $parttypeid, $descriptioncode, $languagecode);
}

if(isset($_GET['action']) && $_GET['action']=='Add')
{
 $recipeid=intval($_GET['recipeid']); $sequence=intval($_GET['sequence']);
// $pim->addPartDescriptionRecipeBlock($recipeid, $sequence, $_GET['blocktype'], $_GET['blockparameters']);    
}

if(isset($_GET['action']) && $_GET['action']=='Delete')
{
 $recipeid=intval($_GET['recipeid']); $detailid=intval($_GET['detailid']);
// $pim->deletePartDescriptionRecipeBlock($recipeid, $blockid);    
}

if(isset($_GET['action']) && $_GET['action']=='Update')
{
 $detailid=intval($_GET['detailid']);
// $pim->updatePartDescriptionRecipeBlock($blockid,$_GET['blockparameters']);
}

$recipes=$pim->getAssetRecipes();  //

$favoriteparttypes=$pim->getFavoriteParttypes();
$partcategories=$pim->getPartCategories();
$assettypecodes=$pcdb->getAssetTypeCodes();

$assettagid=$asset->assetTagid('GLAMOUR');
$availableassetrecords=$asset->getAssetRecordsByAssettagid($assettagid);



?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        <script>
            
            function showHideRecipeBlock(elementid)
            {
             var x = document.getElementById(elementid);
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
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
                <div class="col-xs-12 col-md-1 my-col colLeft"></div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-10 my-col colMain">
                                        
                <div style="padding:20px;">
                    <form>
                        <select name="partcategory">
                        <?php foreach ($partcategories as $partcategory){echo '<option value="'.$partcategory['id'].'">'.$partcategory['name'].'</option>';}?>
                        </select>
                        <select name="parttypeid">
                        <?php foreach($favoriteparttypes as $parttype){echo '<option value="'.$parttype['id'].'">'.$parttype['name'].'</option>';}?>
                        </select>
                        <select name="assettypecode">
                        <?php foreach ($assettypecodes as $assettypecode){echo '<option value="'.$assettypecode['code'].'">'.$assettypecode['code'].' - '.$assettypecode['description'].'</option>';}?>
                        </select>                
                        <input type="submit" name="submit" value="Create Recipe"/>
                    </form>
                </div>
                    
                <?php
                foreach($recipes as $recipe)
                {
                    $displaystyle='block'; if(isset($_GET['recipeid']) && $_GET['recipeid']==$recipe['id']){$displaystyle='block';}
                    echo '<div class="card">';
                    echo '<h6 class="card-header text-start">'.$pim->partCategoryName($recipe['partcategory']).' / '.$pcdb->parttypeName($recipe['parttypeid']).' ['.$recipe['assettypecode'].'] <span onclick="showHideRecipeBlock(\'recipe_'.$recipe['id'].'\');">...</span> <div style="float:right;"><a href="./testAssetRecipe.php?id='.$recipe['id'].'">Test</a></div><div style="clear:both;"></div></h6>';
                    $details=$pim->getAssetRecipeDetails($recipe['id']);
                    echo '<div class="card-body" style="display:'.$displaystyle.';" id="recipe_'.$recipe['id'].'">';

                    echo '<div>';
                    foreach($details as $detail)
                    {
                     echo '<img class="img-thumbnail" src="'.$detail['uri'].'" width="20%;"/> ';
                    }
                    echo '</div>';
                                        
                    echo '<div style="padding:20px;"><form><input type="hidden" name="recipeid" value="'.$recipe['id'].'"/>';
                    echo ' <select name="assetid">';
                    foreach($availableassetrecords as $assetrecord)
                    {
                     $found=false;
                     foreach($details as $detail)
                     {
                      if($detail['assetid']==$assetrecord['assetid']){$found=true; break;}
                     }
                     if($found){continue;}
                        
                     echo '<option value="'.$assetrecord['assetid'].'">'.$assetrecord['assetid'].'</option>';
                    }
                    echo '</select> ';
                    echo '<input type="text" name="sequence" value="1" size="1"/> <input type="submit" name="action" value="Add"/></form></div>';
                    
                    echo '</div>';                     
                    echo '</div>';               
                }
                                
                ?>
    
                </div>
                <!-- End of Main Content -->

                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-1 my-col colRight">
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
                     
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>