<?php
include_once('mysqlClass.php');
include_once('vcdbClass.php');
include_once('pcdbClass.php');
include_once('qdbClass.php');


class book
{
  // this is the advanced (multiple columns of part-type/position) book-generating backend. 
    
    
 function getContent($partcategories)
 {
  $categoryarray=array(); foreach($partcategories as $partcategory){$categoryarray[]=intval($partcategory);} $categorylist=implode(',',$categoryarray); // sanitize input
  $db = new mysql; 
  
  $vcdb=new vcdb;
  $pcdb=new pcdb;
  $qdb=new qdb;
  
  
  $db->connect();
  $data=array();
  if($stmt=$db->conn->prepare('select application.* from application left join part on application.partnumber=part.partnumber where part.partcategory in('.$categorylist.') and application.status=0'))
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
     
     if ($appattribute['type'] == 'qdb')
     {
      $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $qdb->qualifierText($appattribute['name'], explode('~', str_replace('|','',$appattribute['value']))), 'cosmetic' => $appattribute['cosmetic']);
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
    
    if($makename==''){continue;} // don't include apps that failed basevehicle ID lookup
    
    $data[$makename][$modelname][] = array(
        'year'=>$year,
        'parttypeid'=>$row['parttypeid'],
        'positionid'=>$row['positionid'],
        'quantityperapp'=>$row['quantityperapp'],
        'partnumber'=>$row['partnumber'],
        'qualifiers'=>trim($qualifiers));
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
  }

  
  //  combine equal qualified blocks
   
  $compressed=array();
  foreach($data as $makename=>$models)
  {
   foreach($models as $modelname=>$apps)
   {
    foreach($apps as $app)
    {
     $appwasplaced=false;
     $columnkey=$app['positionid'].'_'.$app['parttypeid'];          
     
     if(array_key_exists($makename, $compressed))
     {
      if(array_key_exists($modelname, $compressed[$makename]))
      {// make/model already exist - roll through existing rows to find a compatibles one to glom onto 
   
       $existingrow=-1;
       foreach($compressed[$makename][$modelname] as $i=>$rowtemp)
       {
        if($rowtemp['year']==$app['year'])
        {
         $existingrow=$i; break;
        }
       }
        
       if($existingrow>=0)
       {// found existing row that is year-equal with the row we are trying to add - tack its partnumber onto the list of parts
           
        $compressed[$makename][$modelname][$existingrow]['qualifierblocks'][$app['qualifiers']][$columnkey][]=$app['partnumber'];
        $appwasplaced=true;
       } 
      }   
     }
      
     if(!$appwasplaced)
     {
      $compressed[$makename][$modelname][]=array('year'=>$app['year'],'qualifierblocks'=>array($app['qualifiers']=>array($columnkey=>array($app['partnumber']))));
     }
    }
   }
  }
  
  // compression (stage 2) build ranges of years that have equal qualified blocks
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
       $existingrow=$this->existingrow($row, $output[$makename][$modelname]);
       if($existingrow>=0)
       { // found a yearblock to adsorb this app
        if($row['year']<$output[$makename][$modelname][$existingrow]['startyear']){$output[$makename][$modelname][$existingrow]['startyear']=$row['year'];}
        if($row['year']>$output[$makename][$modelname][$existingrow]['endyear']){$output[$makename][$modelname][$existingrow]['endyear']=$row['year'];}
        $rowwasplaced=true;
       }   
      }
     }
     if(!$rowwasplaced)
     {
      $output[$makename][$modelname][]=array('startyear'=>$row['year'],'endyear'=>$row['year'],'qualifierblocks'=>$row['qualifierblocks']);
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


 
  
function existingrow($needle,$haystack)
{
    /* needle will look like
     * 
    Array
    (
        [year] => 2018
        [qualifierblocks] => Array
            (
                [L4 1.5L] => Array
                    (
                        [0] => AQ1182
                    )

                [L4 2.4L; Naturally Aspirated] => Array
                    (
                        [0] => AQ1058
                        [1] => AQ1058C
                    )

            )
    )
     */
    
    
    /*haystack will look like
    [0] => Array
    (
        [startyear] => 2019
        [endyear] => 2019
        [qualifierblocks] => Array
            (
                [L4 1.5L] => Array
                    (
                        [0] => AQ1182
                    )

                [L4 2.4L; Naturally Aspirated] => Array
                    (
                        [0] => AQ1058
                        [1] => AQ1058C
                    )

            )
    )

    [1] => Array
    (
        [startyear] => 2020
        [endyear] => 2020
        [qualifierblocks] => Array
            (
                [] => Array
                    (
                        [0] => AQ1182
                    )

            )

    )
     *
     * 
     * return the haystack element number that the needle is compatible with (one year off, equal list of qualified blocks)
     * 
     * 
     */
       
 $needleblockhash=md5(serialize($needle['qualifierblocks']));
    
 $existingrow=-1;
 foreach($haystack as $i=>$element)
 {
  $blockshash= md5(serialize($element['qualifierblocks']));
  if(($blockshash==$needleblockhash) && ($needle['year']<=($element['endyear'])+1) && ($needle['year']>=($element['startyear'])-1))
  {
   $existingrow=$i; break;
  }
 }
       
 return $existingrow;
}

 
 

}?>
