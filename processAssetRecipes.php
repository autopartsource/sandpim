<?php
/*
 * intended to be executed from the command-line be a cron call
 */


include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening

$starttime=time();

$pim = new pim();


include_once(__DIR__.'/class/pcdbClass.php');
include_once(__DIR__.'/class/padbClass.php');
include_once(__DIR__.'/class/assetClass.php');
include_once(__DIR__.'/class/logsClass.php');

$pcdb=new pcdb();
$padb=new padb();

$asset=new asset();
$logs=new logs();

$recipes=$pim->getAssetRecipes(); 
//     $recipes[]=array('id'=>$row['id'], 'partcategory'=>$row['partcategory'],'parttypeid'=>$row['parttypeid'],'constraints'=>$row['constraints'],'assettypecode'=>$row['assettypecode']);


$evaluations=0; $updates=0; $writes=0;


// description recipe records are keyed by parttype/category/[attributes]
//  and apply to an asset type like P03 ()
/*
 * 
 * 
 * 
 * 
 * 
 */


foreach($recipes as $recipe)
{
 $partmatchcount=0;
 $parts=$pim->getParts('', 'contains', $recipe['partcategory'], $recipe['parttypeid'], 'any', 'any', 100000);
 
 echo "\r\n".$recipe['id'].' - '.$recipe['parttypeid'].' - '.$recipe['partcategory'].' ('.count($parts).' parts)'."\r\n";

 foreach($parts as $part)
 {
     
  //disqualify parts not matching attribute constraints
  $metcount=0; $constraintcount=0;
  if(trim($recipe['constraints'])!='')
  {// contstaint examples -  9059:Yes;170:No;Slot Type:Straight;
   $attributes=$pim->getPartAttributes($part['partnumber']);
   $constraints=explode(';',trim($recipe['constraints']));
   foreach($constraints as $constraint)
   {
    $constraintbits=explode(':',$constraint);
    if(count($constraintbits)==2)
    {
     if(intval($constraintbits[0])>0)
     {// numeric (PAdb) addtribute
      $constraintcount++;
      foreach($attributes as $attribute)
      {
       if($attribute['PAID']==intval($constraintbits[0]) && $attribute['value']==$constraintbits[1])
       {
        $metcount++; break;
       }
      }
     }
     else
     {// free-form attribute                             
      if($attribute['name']==$constraintbits[0] && $attribute['value']==$constraintbits[1])
      {
       $metcount++; break;
      }  
     }
    }
   }
  }
  
  if($metcount!=$constraintcount)
  {
//   echo ' -- '.$part['partnumber']." does not meet constraints\n";
   continue;      
  }
      
  $partmatchcount++;     
  echo ' -- '.$part['partnumber']."\n";
  
  $partconnections=$asset->getAssetsConnectedToPart($part['partnumber']);  //array('id'=>$row['id'],'connectionid'=>$row['connectionid'],'assetid'=>$row['assetid'],'partnumber'=>$row['partnumber'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence'],'representation'=>$row['representation'],'uri'=>$row['uri'],'filename'=>$row['filename'],'filetype'=>$row['fileType'],'assetlabel'=>$row['assetlabel'],'inheritedfrom'=>'','frame'=>$row['frame'],'totalFrames'=>$row['totalFrames'],'plane'=>$row['plane'],'totalPlanes'=>$row['totalPlanes']);  
  $recipeconnections=$pim->getAssetRecipeDetails($recipe['id']);

  $correctconnectionscount=0;
  foreach($partconnections as $partconnection)
  {
   if(array_key_exists('inheritedfrom',$partconnection) && $partconnection['inheritedfrom']!='')
   {// this connection was inherited - not native - leave it alone
    continue;
   }
   
   if($partconnection['assettypecode']==$recipe['assettypecode'])
   {
    foreach($recipeconnections as $recipeconnection)
    {
     if($recipeconnection['assetid']==$partconnection['assetid'] && $recipeconnection['sequence']==$partconnection['sequence']){$correctconnectionscount++; break;}
    }       
   }   
  }
  
  if($correctconnectionscount == count($recipeconnections))
  {
   echo $part['partnumber']." has (".count($recipeconnections).") prescribed assets with correct sequences\n";
  }
  else
  {      
   echo $part['partnumber']." is missing prescribed assets\n";      
  }
  
  $evaluations++;
 }
}

echo 'matched parts: '.$partmatchcount."\n";

$runtime=time()-$starttime;
//$logs->logSystemEvent('ASSETS', 0, 'Part asset recipes; Evaluated: '.$evaluations.', updates:'.$updates.', writes:'.$writes.' in '.$runtime.' seconds');
