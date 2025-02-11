<?php
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/configGetClass.php');
include_once('./class/configSetClass.php');
include_once('./class/logsClass.php');
$navCategory = 'settings';

$pim= new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'descriptionRecipes.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pcdb= new pcdb();
$configGet= new configGet;
$configSet= new configSet;
$logs= new logs;

$defaultdescriptionlanguagecode=$configGet->getConfigValue('defaultDescriptionLanguageCode','EN');

if(isset($_GET['submit']) && $_GET['submit']=='Create')
{
 $partcategory=intval($_GET['partcategory']); 
 $parttypeid=intval($_GET['parttypeid']); 
 $descriptioncode=$_GET['descriptioncode']; 
 $languagecode=$_GET['languagecode'];
 $pim->addPartDescriptionRecipe($partcategory, $parttypeid, $descriptioncode, $languagecode);
}

if(isset($_GET['action']) && $_GET['action']=='Add')
{
 $recipeid=intval($_GET['recipeid']); $sequence=intval($_GET['sequence']);
 $pim->addPartDescriptionRecipeBlock($recipeid, $sequence, $_GET['blocktype'], $_GET['blockparameters']);    
}

if(isset($_GET['action']) && $_GET['action']=='Delete')
{
 $recipeid=intval($_GET['recipeid']); $blockid=intval($_GET['blockid']);
 $pim->deletePartDescriptionRecipeBlock($recipeid, $blockid);    
}

if(isset($_GET['action']) && $_GET['action']=='Update')
{
 $blockid=intval($_GET['blockid']);
 $pim->updatePartDescriptionRecipeBlock($blockid,$_GET['blockparameters']);
}


$recipes=$pim->getPartDescriptionRecipes();  //     $recipes[]=array('id'=>$row['id'], 'partcategory'=>$row['partcategory'],'parttypeid'=>$row['parttypeid'],'descriptioncode'=>$row['descriptioncode'],'languagecode'=>$row['languagecode']);

$favoriteparttypes=$pim->getFavoriteParttypes();
$partcategories=$pim->getPartCategories();
$descriptioncodes=$pcdb->getPartDescriptionTypeCodes();
$languagecodes=$pcdb->getPartDescriptionLanguageCodes();


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
                <div class="col-xs-12 col-md-2 my-col colLeft">

                    
                </div>

                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                <?php
                foreach($recipes as $recipe)
                {
                    $displaystyle='none'; if(isset($_GET['recipeid']) && $_GET['recipeid']==$recipe['id']){$displaystyle='block';}
                    echo '<div class="card">';
                    echo '<h6 class="card-header text-start">'.$pim->partCategoryName($recipe['partcategory']).' / '.$pcdb->parttypeName($recipe['parttypeid']).' ['.$recipe['descriptioncode'].' '.$recipe['languagecode'].'] <span onclick="showHideRecipeBlock(\'recipe_'.$recipe['id'].'\');">...</span> <div style="float:right;"><a href="./testDescriptionRecipe.php?id='.$recipe['id'].'">Test</a></div><div style="clear:both;"></div></h6>';
                    $blocks=$pim->getPartDescriptionRecipeBlocks($recipe['id']); //      $blocks[]=array('id'=>$row['id'],'blocktype'=>$row['blocktype'],'blockparameters'=>$row['blockparameters']);
                    echo '<div class="card-body" style="display:'.$displaystyle.';" id="recipe_'.$recipe['id'].'">';
                    foreach($blocks as $block)
                    {
                     echo '<form action="./descriptionRecipes.php" method="get"><input type="hidden" name="recipeid" value="'.$recipe['id'].'"/><input type="hidden" name="blockid" value="'.$block['id'].'"/><div style="padding:5px;">'.$block['sequence'].' - '.$block['blocktype'].' <input style="width:50%;" name="blockparameters" type="text" id="parameters_'.$block['id'].'" value="'.$block['blockparameters'].'"/> <input type="submit" name="action" value="Update"/> <input type="submit" name="action" value="Delete"/></div></form>';
                    }
                    echo '<div style="padding:20px;"><form><input type="hidden" name="recipeid" value="'.$recipe['id'].'"/><input type="text" name="sequence" value="1" size="1"/> <select name="blocktype"><option value="LITERAL">Literal</option><option value="COMPONENTTOUTER">Component Touter</option><option value="ATTRIBUTE">Attribute</option></select> <input style="width:50%;" name="blockparameters" type="text"/> <input type="submit" name="action" value="Add"/></form></div>';
                    echo '</div>';                     
                    echo '</div>';               
                }
                
                echo '<div style="padding:20px;"><form>';

                echo '<select name="partcategory">';
                foreach ($partcategories as $partcategory){echo '<option value="'.$partcategory['id'].'">'.$partcategory['name'].'</option>';}
                echo '</select> ';

                echo '<select name="parttypeid">';
                foreach($favoriteparttypes as $parttype){echo '<option value="'.$parttype['id'].'">'.$parttype['name'].'</option>';}
                echo '</select> ';

                echo '<select name="descriptioncode">';
                foreach ($descriptioncodes as $descriptioncode){echo '<option value="'.$descriptioncode['code'].'">'.$descriptioncode['code'].' - '.$descriptioncode['description'].'</option>';}
                echo '</select> ';

                echo ' <select name="languagecode">';
                foreach ($languagecodes as $languagecode)
                {
                 $selected=''; if($languagecode['code']==$defaultdescriptionlanguagecode){$selected=' selected';}
                 echo '<option value="'.$languagecode['code'].'"'.$selected.'>'.$languagecode['code'].' - '.$languagecode['description'].'</option>';                 
                }
                 
                echo '</select> ';

                
                echo '<input type="submit" name="submit" value="Create"/>';
                echo '</form></div>';

                
                
                ?>

                    
                    
                    
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