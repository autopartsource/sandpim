<?php
/*
 * intended to be executed from the command-line be a cron call ("php processACESexport.php")
 * on a cycle (likely every 5 or 10 minutes). It will query the db for the oldest job that 
 * is status "started" and execute it. The job will be 
 * 
 * On my fedora 31 box, I had to apply a read/write SELinux policy to the 
 * directory where apache can write the exported files (/var/www/html/ACESexports
 * semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/ACESexports(/.*)?"
 * restorecon -Rv /var/www/html/ACESexports/
 * 
 * 
 */


include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening

$starttime=time();

$pim = new pim();


include_once(__DIR__.'/class/pcdbClass.php');
include_once(__DIR__.'/class/padbClass.php');
include_once(__DIR__.'/class/logsClass.php');

$pcdb=new pcdb();
$padb=new padb();

$logs=new logs();

$recipes=$pim->getPartDescriptionRecipes();
$evaluations=0; $updates=0; $writes=0;


// description recipie records are keyed by parttype/cattegory
/*
 * a recipe is made of blocks that are concatinated together left to right in sequence order
 * block types are: 
 *  literal string
 *  coponent touter
 *  padb (id and transform list)
 *  buyers guide
 * 
 * id
 * parttypeid
 * categoryid
 * blocktype (LITERAL,COMPONENTTOUTER,ATTRIBUTE,BUYERSGUIDE)
 * blockparameters (
 *  for literals: "Permium brake pads"
 *  for COMPONENTTOUTER: "parts~A2340,A567,A3325,A1203~Drop-in hardware kit included"
 *  for ATTRIBUTE: "4536~prefixtext~suffixtext"
 * sequence
 * language
 * descriptioncode
 * 
 */

/*  get all descriptionrecipe recs
 * MariaDB [pim]> select * from descriptionrecipe;
+----+--------------+------------+-----------------+--------------+
| id | partcategory | parttypeid | descriptioncode | languagecode |
+----+--------------+------------+-----------------+--------------+
|  1 |          100 |       1684 | EXT             | EN           |
|  2 |          102 |       1684 | EXT             | EN           |
+----+--------------+------------+-----------------+--------------+
 * 
 * 
 */


// foreach record, delete existing item (by partcategory and parttype) description records matching descriptioncode and language

//echo '<textarea>';

foreach($recipes as $recipe)
{
 $parts=$pim->getParts('', 'contains', $recipe['partcategory'], $recipe['parttypeid'], 'any', 'any', 100000);
 //echo "\r\n".$recipe['id'].' - '.$recipe['parttypeid'].' - '.$recipe['partcategory'].' ('.count($parts).' parts)'."\r\n";
 foreach($parts as $part)
 {
  $evaluations++;
  // echo ' -- '.$part['partnumber'].' - ';
      
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
//   echo ' --- '.$block['id'].' - '.$block['blocktype'].' - '.$block['blockparameters'].'<br/>';    
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
    
    if(trim($existingdescripton['description'])=='')
    { // special case - we just stumbled on a blank description - delete it
     $pim->deletePartDescriptionById($existingdescripton['id']);
     $oid=$pim->updatePartOID($part['partnumber']);
     $pim->logPartEvent($part['partnumber'],0, 'Blank ('.$existingdescripton['descriptioncode'].') description dropped by recipe '.$recipe['id'],$oid);     
     continue;
    }
    
    if(trim($existingdescripton['description'])==$newdescription)
    {// existing description for this part and description code is already the same as the recipe dictates - no action needed
     //echo 'Already good ('.$existingdescripton['descriptioncode'].')'."\r\n";
    }
    else
    {// existing description for this part and description code is different then recipe dictates - need to update (drop/add)
     //echo 'Need to update ('.$recipe['descriptioncode'].'): '.$existingdescripton['description'].'!='.$newdescription."\r\n";
     
     if(trim($newdescription)!='')
     { // normal case: non-blank new description is about to be written to place existing non-blank description
      $pim->deletePartDescriptionById($existingdescripton['id']);
      $pim->addPartDescription($part['partnumber'], $newdescription, $recipe['descriptioncode'], 1, $recipe['languagecode']);
      $oid=$pim->updatePartOID($part['partnumber']);
      $pim->logPartEvent($part['partnumber'],0, 'Description dropped and re-added by recipe '.$recipe['id'].': '.$newdescription,$oid);
     }
     else
     {// odd case: blank description is about to be written to replace non-blank description (delete the existing, but don't add new blank one)
      $pim->deletePartDescriptionById($existingdescripton['id']);
      $oid=$pim->updatePartOID($part['partnumber']);
      $pim->logPartEvent($part['partnumber'],0, 'Description dropped and blank not re-added by recipe '.$recipe['id'],$oid);         
     }     
     $updates++;
    }
   }
  }

  
  if(!$foundexistingcode && trim($newdescription)!='')
  {// no existing description for this part / description code exists - add it
   //echo 'no existing ('.$recipe['descriptioncode'].'), need to add:'.$newdescription."\r\n";       
   $pim->addPartDescription($part['partnumber'], $newdescription, $recipe['descriptioncode'], 1, $recipe['languagecode']);
   $oid=$pim->updatePartOID($part['partnumber']);
   $pim->logPartEvent($part['partnumber'],0, 'Description added by recipe '.$recipe['id'].': '.$newdescription,$oid);
   $writes++;
  }
 }  
}

//echo '</textarea>';
$runtime=time()-$starttime;
$logs->logSystemEvent('DESCRIPTIONS', 0, 'Part description recipes; Evaluated: '.$evaluations.', updates:'.$updates.', writes:'.$writes.' in '.$runtime.' seconds');

?>
