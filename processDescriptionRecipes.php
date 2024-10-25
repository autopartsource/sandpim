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
$pim = new pim();


include_once(__DIR__.'/class/pcdbClass.php');
include_once(__DIR__.'/class/padbClass.php');
include_once(__DIR__.'/class/logsClass.php');

$pcdb=new pcdb();
$padb=new padb();

$logs=new logs();

$recipes=$pim->getPartDescriptionRecipes();

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

foreach($recipes as $recipe)
{
 $parts=$pim->getParts('', 'contains', $recipe['partcategory'], $recipe['parttypeid'], 'any', 'any', 100000);
 echo '<br/>'.$recipe['id'].' - '.$recipe['parttypeid'].' - '.$recipe['partcategory'].' ('.count($parts).' parts)<br/>';
 foreach($parts as $part)
 {
  echo ' -- '.$part['partnumber'].' - ';
      
  $blocks=$pim->getPartDescriptionRecipeBlocks($recipe['id']);
  $descriptionbits=array();
  foreach($blocks as $block)
  {
   switch($block['blocktype'])
   {
       case 'LITERAL':
           $descriptionbits[]=$block['blockparameters'];
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
              $descriptionbits[]=$parameterbits[1];
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
           
           $parameterbits=explode('~',$block['blockparameters']);
           if(count($parameterbits)==3)
           {
           }
           
           
           break;
       
       case 'BUYERSGUIDE':
           break;
       
       default:
           break;
       
   }
//   echo ' --- '.$block['id'].' - '.$block['blocktype'].' - '.$block['blockparameters'].'<br/>';    
  }
  echo 'Desc:'.implode('; ',$descriptionbits).'<br/>';
  
          
 } 
 
}


?>
