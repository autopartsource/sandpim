<?php
include_once("mysqlClass.php");
include_once("vcdbClass.php");
include_once("pcdbClass.php");


class book
{

    
    
    
    
    
    
 function getContent($partcategories)
 {
  $categoryarray=array(); foreach($partcategories as $partcategory){$categoryarray[]=intval($partcategory);} $categorylist=implode(',',$categoryarray); // sanitize input
  $db = new mysql; 
  
  $vcdb=new vcdb;  
  $pcdb=new pcdb;  
  
  
  $db->connect();
  $data=array();
  if($stmt=$db->conn->prepare('select application.* from application left join part on application.partnumber=part.partnumber where part.partcategory in('.$categorylist.') '))
  {
   //$stmt->bind_param('i', $basevehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributes=$this->getAppAttributes($row['id']);
    $niceattributes = array();
    foreach ($attributes as $appattribute)
    {
     if ($appattribute['type'] == 'vcdb')
     {
      $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute));
     }
     if ($appattribute['type'] == 'note')
     {
      $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value']);
     }
    }
   
    // may need to sort attributes by sequence here
    $nicefitmentarray = array();
    foreach ($niceattributes as $niceattribute){$nicefitmentarray[] = $niceattribute['text'];}
    $qualifiers = implode('; ', $nicefitmentarray);
    $mmy=$vcdb->getMMYforBasevehicleid($row['basevehicleid']);
    $makename=$mmy['makename']; $modelname=$mmy['modelname']; $year=$mmy['year'];
    
    
    $data[$makename][$modelname][] = array(
        'year'=>$year,
        'parttypeid'=>$row['parttypeid'],
        'positionid'=>$row['positionid'],
        'quantityperapp'=>$row['quantityperapp'],
        'partnumber'=>$row['partnumber'],
        'qualifiers'=>$qualifiers);
   }
   
   //sort data by make/mode
   ksort($data, SORT_NATURAL | SORT_FLAG_CASE);
   foreach($data as $makename=>$models)
   {
      ksort($data[$makename], SORT_NATURAL | SORT_FLAG_CASE);
   }   
   
   // sort each model by years/qualifiers
   foreach($data as $makename=>$models)
   {
    foreach($models as $modelname=>$rows)
    {
      $yearskey = array_column($rows, 'year');
      $qualifierskey = array_column($rows, 'qualifiers');
      array_multisort($yearskey, SORT_ASC, $qualifierskey, SORT_ASC, $data[$makename][$modelname]);
    }
   }

   // --------- compression stage one
   //  combine contiguous years, equal qualifiers, positions, parttypes and part
   //  column-ize parts with a column key (partype+position)
   
   $compressed=array();
   foreach($data as $makename=>$models)
   {
    foreach($models as $modelname=>$apps)
    {
     foreach($apps as $app)
     {
      $appwasplaced=false;
      
      if(array_key_exists($makename, $compressed))
      {
       if(array_key_exists($modelname, $compressed[$makename]))
       {// make/model already exist - roll through existing rows to find a compatibles one to glom onto 

        $existingrow=$this->existingStage1Row($compressed[$makename][$modelname], $app['partnumber'], $app['year'], $app['qualifiers'], $app['parttypeid'], $app['positionid']);

        if($existingrow>=0)
        {// found existing row that is year-contiguous with the row we are trying to add
         $compressed[$makename][$modelname][$existingrow]['endyear']=$app['year'];
         $appwasplaced=true;
        } 
       }   
      }
      
      if(!$appwasplaced)
      {
       $compressed[$makename][$modelname][]=array('startyear'=>$app['year'],'endyear'=>$app['year'],'qualifiers'=>$app['qualifiers'],'partnumber'=>$app['partnumber'],'parttypeid'=>$app['parttypeid'],'positionid'=>$app['positionid'],'quantityperapp'=>$app['quantityperapp']);
      }
     }
    }
   }
  }
  
  //------- compression stage 2. Merge equiv rows (year range and qualifiers). Establish "columns" in this step (parttype+position)
  
  
  $output=array();
  foreach($compressed as $makename=>$models)
  {
   foreach($models as $modelname=>$rows)
   {
    foreach($rows as $row)
    {
     $rowwasplaced=false;
     if(array_key_exists($makename, $output))
     {
      if(array_key_exists($modelname, $output[$makename]))
      {// make/model already exist - roll through existing rows to find a compatibles one to glom onto 
       $existingrow=$this->existingStage2Row($output[$makename][$modelname], $row['startyear'], $row['endyear'], $row['qualifiers']);
       if($existingrow>=0)
       {
        $columnkey=$row['parttypeid'].'_'.$row['positionid'];
        
        $output[$makename][$modelname][$existingrow]['columns'][$columnkey][]=array('part'=>$row['partnumber'],'qty'=>$row['quantityperapp']);
        $rowwasplaced=true;
       }   
      }
     }
     if(!$rowwasplaced)
     {
      $columnkey=$row['parttypeid'].'_'.$row['positionid'];
      $output[$makename][$modelname][]=array('startyear'=>$row['startyear'],'endyear'=>$row['endyear'],'qualifiers'=>$row['qualifiers'],'columns'=>array($columnkey=>array(array('part'=>$row['partnumber'],'qty'=>$row['quantityperapp']))));
     }
    }
   }
  }
  
  // stage 3 - combine equal year blocks
  
  $compressed=array();
  foreach($output as $makename=>$models)
  {
   foreach($models as $modelname=>$blocks)
   {
    foreach($blocks as $block)
    {
     $rowwasplaced=false;
     if(array_key_exists($makename, $compressed))
     {
      if(array_key_exists($modelname, $compressed[$makename]))
      {// make/model already exist - roll through existing rows to find a compatibles one to glom onto 

          
          
       if($existingblock>=0)
       {
        $compressed[$makename][$modelname][$existingblock]['rows'][]=$block['columns'];
        $rowwasplaced=true;
       }   
      }
     }
     if(!$rowwasplaced)
     {
      $compressed[$makename][$modelname][]=array('startyear'=>$row['startyear'],'endyear'=>$row['endyear'],'rows'=>array(array('qualifiers'=>$row['qualifiers'],'columns'=>array($columnkey=>array(array('part'=>$row['partnumber'],'qty'=>$row['quantityperapp']))))));
     }
    }
   }
  }
  
  
  
  
  
  
  
  
  
  
  
  $db->close();
  return $output;
 }

 function getAppAttributes($appid)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  $attributes=array();
  if($stmt=$db->conn->prepare('select * from application_attribute where applicationid=? order by sequence'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $pairtemp=array('name'=>$row['name'],'value'=>$row['value']);
    $attributes[]=array('id'=>$row['id'],'name'=>$row['name'],'value'=>$row['value'],'type'=>$row['type'],'sequence'=>$row['sequence'],'cosmetic'=>$row['cosmetic']);
   }
  }
  $db->close();
  return $attributes;
 }


// $output[$makename][$modelname][$existingrow]['columns'][$columnkey][]=array('part'=>$row['partnumber'],'qty'=>$row['quantityperapp']);
// $output[$makename][$modelname][]=array('startyear'=>$row['startyear'],'endyear'=>$row['endyear'],'qualifiers'=>$row['qualifiers'],'columns'=>array($columnkey=>array(array('part'=>$row['partnumber'],'qty'=>$row['quantityperapp']))));

 function existingStage3Block($blocks, $startyear, $endyear, $qualifiers, $columns)
{
     /* evey block in the input array looks like this :
      * 
      * 'startyear'=>2020, 'endyear'=>2025, 'qualifiers'=>'2.5L L6; 4WD',
      * 'columns'=>
      *  array(
      *  'Front Metalic Pads'=>
      *  (
      *   [0]=('part'=>'MF591', 'qty'=>1) 
      *   [1]=('part'=>'MF591K', 'qty'=>1)
      *  )
      */
         
 $existingblock=-1;
 for($i=0;$i<count($blocks);$i++)
 {
  if($blocks[$i]['startyear'] == $startyear && $blocks[$i]['endyear'] == $endyear && trim($blocks[$i]['qualifiers']) == trim($qualifiers) && $blocks[$i]['columns']==count($columns))
  {// found existing block (equal startyears, endyears and qualifiers and count(columns)
   // Now compare all columns all columns
   $columsmatch=true;
   foreach($columns as $columnkey=>$column)
   {
    if(count($column)!=count($blocks[$i]['columns'][$columnkey])){break;}
    foreach($column as $parts_a)
    {
     $foundpart=false;
     foreach($blocks[$i]['columns'][$columnkey] as $parts_b)
     {
      if($parts_a['part']==$parts_b['part']){$foundpart=true; break;}
     }
     if(!$foundpart){break;}
    }
    if(!$foundpart){$columsmatch=false; break;}
   }
  }
  if($columsmatch){$existingblock=$i;break;}
 }
 return $existingblock;
}

 
 
 

 

function existingStage2Row($rows, $startyear, $endyear, $qualifiers)
{
 $existingrow=-1;
 for($i=0;$i<count($rows);$i++)
 {
  if($rows[$i]['startyear'] == $startyear && $rows[$i]['endyear'] == $endyear && trim($rows[$i]['qualifiers']) == trim($qualifiers))
  {// found existing row (contiguous endyear and equal qualifier string
   $existingrow=$i; break;
  }
 }
 return $existingrow;
}

function existingStage1Row($rows,$partnumber,$year,$qualifiers,$parttypeid,$positionid)
{
  // return -1 or the row where the given partnumber,year+1,qualifiers,parttypeid,positionid already exists
  // each "row" looks like this: array('year'=>'2020', 'parttypeid'=>1896, 'positionid'=>22, 'quantityperapp'=2, 'partnumber'=>'PR72400', 'qualifiers'=>'V6 2.8L; 4WD; Green bumpers' 
   $existingrow=-1;
   for($i=0;$i<count($rows);$i++)
   {
       if(($rows[$i]['endyear'] == $year || ($rows[$i]['endyear']+1) == $year) && 
               trim($rows[$i]['qualifiers']) == trim($qualifiers) &&
               $rows[$i]['parttypeid'] == $parttypeid &&
               $rows[$i]['positionid'] == $positionid &&
               $rows[$i]['partnumber'] == $partnumber)
       {// found existing row (contiguous endyear and equal qualifier string, equal parttype, equal position)
           $existingrow=$i; break;
       }
   }
   return $existingrow;
}
 
 
 

}?>
