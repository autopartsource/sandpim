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
 $logs->logSystemEvent('accesscontrol',0, 'testDescriptionRecipe.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pcdb= new pcdb();
$configGet= new configGet;
$configSet= new configSet;

$recipe=$pim->getPartDescriptionRecipe(intval($_GET['id']));
$parts=$pim->getParts('', 'contains', $recipe['partcategory'], $recipe['parttypeid'], 'any', 'any', 100000);

echo '<a href="./descriptionRecipes.php">Back</a>';

echo '<div style="padding:10px;"><strong>Recipe:</strong> '.$recipe['id'].'<br/><strong>Part type:</strong> '.$pcdb->parttypeName($recipe['parttypeid']).'<br/><strong>Part Category:</strong> '.$pim->partCategoryName($recipe['partcategory']).'<br/><strong>Description Code:</strong> '.$recipe['descriptioncode'].'<br/>Applies to '.count($parts).' parts</div>';

echo '<textarea style="width:100%;height:600px;">';

 foreach($parts as $part)
 {
  echo $part['partnumber']."\t";
      
  $blocks=$pim->getPartDescriptionRecipeBlocks($recipe['id']);
  $descriptionbits=array();
  foreach($blocks as $block)
  {
   switch($block['blocktype'])
   {
       case 'LITERAL':
           $descriptionbits[]=trim($block['blockparameters']);
           break;

       case 'COMPONENTTOUTER':
           //parameters will be like: 
           // 16113|16122~ w/Pin Boots
           //    or
           // 943C~ w/Moly Lube Packet
           $kitcomponents=$pim->getKitComponents($part['partnumber']); //array('id'=>$row['id'],'partnumber'=>$row['rightpartnumber'],'units'=>round($row['units'],2),'sequence'=>$row['sequence']);
           $parameterbits=explode('~',$block['blockparameters']);
           if(count($parameterbits)==2)
           {
            $toutedcomponents=explode('|',$parameterbits[0]);
            foreach($kitcomponents as $kitcomponent)
            {
             if(in_array($kitcomponent['partnumber'], $toutedcomponents))
             {
              $descriptionbits[]=trim($parameterbits[1]);
              break;
             }                
            }
           }
           
           break;

       case 'ATTRIBUTE':
           // parameters will be like:
           //  9076~Yes~ w/Chamfered Edges
           //  170~Yes~ Slotted
           //  9478~~ [] Thick 
           //  Chamfer Type~~ [] Chamfered Edges
           
           $parameterbits=explode('~',$block['blockparameters']);
           if(count($parameterbits)==3)
           {
            $partattributes=$pim->getPartAttributes($part['partnumber']);
            
            foreach($partattributes as $partattribute)
            {
             if((intval($partattribute['PAID'])>0 && $partattribute['PAID']==$parameterbits[0]) || (intval($partattribute['PAID'])==0 && $partattribute['name']==$parameterbits[0]))
             {// this attribute (id or user-defined) matched the recipe block - now do something with the value
              if($parameterbits[1]==$partattribute['value'])
              {// value is an exact match for recipe block. ex: blockparameters="170~Yes~ Slotted"    attribute is PAID=170,value=Yes
               $descriptionbits[]=trim($parameterbits[2]);
               break;
              }
              else
              {// value is not an exact match for recipe block - maybe it's a scalar value like: blockparameters="9478~~ [] Thick"    attribute is PAID=9478,value=0.705IN
               if(strpos($parameterbits[2],'[]')!==false)
               {
                $descriptionbits[]= trim(str_replace('[]',$partattribute['value'].$partattribute['uom'],$parameterbits[2]));
                break;
               }                  
              }
             }                
            }            
           }
           
           
           break;
       
       case 'BUYERSGUIDE':
           break;
       
       default:
           break;       
   }
  }

  $newdescription=trim(implode('; ',$descriptionbits));
  $existingdescriptons=$pim->getPartDescriptions($part['partnumber']); //$descriptions[]=array('id'=>$row['id'],'description'=>$row['description'],'descriptioncode'=>$row['descriptioncode'],'sequence'=>$row['sequence'],'languagecode'=>$row['languagecode'],'inheritedfrom'=>'');       
  $foundexistingcode=false;
  foreach($existingdescriptons as $existingdescripton)
  {
   if($existingdescripton['inheritedfrom']!=''){continue;}
   if($recipe['descriptioncode']==$existingdescripton['descriptioncode'])
   {
    $foundexistingcode=true;
    if(trim($existingdescripton['description'])==$newdescription)
    {// existing description for this part and description code is already the same as the recipe dictates - no action needed
     echo "unchanged\t".$newdescription."\r\n";
    }
    else
    {// existing description for this part and description code is different then recipe dictates - need to update (drop/add)
     echo "change\t".$newdescription."\r\n";
    }
   }
  }
  

  
  if(!$foundexistingcode)
  {// no existing description for this part / description code exists - add it
   echo "create\t".$newdescription."\r\n";       
  }

 } 
 

echo '</textarea>';

?>
