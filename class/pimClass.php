<?php
include_once("mysqlClass.php");

class pim
{

 function buildVersion()
 {
  return '2025-09-18';
 }

 function uuidv4()
 {
  $randodata = file_get_contents('/dev/urandom', NULL, NULL, 0, 16);
  $uuid=vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($randodata), 4));

  // all 128 bits are now randomly generated in the hex output. Set the "M" (version) nibble to "4" by over-writing it 
  $uuid= substr_replace($uuid,'4', 14, 1);
  
  // set the "N" (variant) nibble to a,b,8 or 9 to specify the MSB as set and the second most significant bit to clear
  $valid_n_hex_nibbles=array('a','b','8','9');
  $n_hex_nibble=$valid_n_hex_nibbles[random_int(0, 3)];
  $uuid= substr_replace($uuid, $n_hex_nibble, 19, 1);
          
  return $uuid;
 }

 function sanitizePartnumber($input)
 {
  $output=trim(strtoupper($input));
  if(strlen($input)>20){$output= substr($input, 0, 20);}
  $output=str_replace(array('"',';',"'","\t","\n","\r",'?',',','.','>','<','`','!','@','$','%','^','&','*','(',')','+','=',':','[',']','{','}','|','~'), '-', $output);
  return $output;
 }
 
 
 function timeAgoFromSeconds($secondsago)
 {
  if($secondsago<0){return 'in the future';}
  if($secondsago>189216000){return 'a long time ago';}
  if($secondsago>189216000){return '6 years ago';}      
  if($secondsago>157680000){return '5 years ago';}      
  if($secondsago>126144000){return '4 years ago';}      
  if($secondsago>94608000){return '3 years ago';}      
  if($secondsago>62072000){return '2 years ago';}      
  if($secondsago>46656000){return 'A year and a half ago';}      
  if($secondsago>31536000){return 'A year ago';}    
  if($secondsago>23328000){return '9 months ago';}  
  if($secondsago>20736000){return '8 months ago';}
  if($secondsago>18144000){return '7 months ago';}
  if($secondsago>15552000){return '6 months ago';}
  if($secondsago>12960000){return '5 months ago';}
  if($secondsago>10368000){return '4 months ago';}
  if($secondsago>7776000){return '3 months ago';}
  if($secondsago>5184000){return '2 months ago';}
  if($secondsago>2592000){return 'a month ago';}
  if($secondsago>1814400){return '3 weeks ago';}
  if($secondsago>1209600){return '2 weeks ago';}
  if($secondsago>604800){return 'a week ago';}
  if($secondsago>518400){return '6 days ago';}
  if($secondsago>432000){return '5 days ago';}
  if($secondsago>345600){return '4 days ago';}
  if($secondsago>259200){return '3 days ago';}
  if($secondsago>172800){return '2 days ago';}
  if($secondsago>86400){return 'yesterday';}     
  if($secondsago>43200){return '12 hours ago';}
  if($secondsago>36000){return '10 hours ago';}
  if($secondsago>32400){return '9 hours ago';}
  if($secondsago>28800){return '8 hours ago';}
  if($secondsago>25200){return '7 hours ago';}
  if($secondsago>21600){return '6 hours ago';}
  if($secondsago>18000){return '5 hours ago';}
  if($secondsago>14400){return '4 hours ago';}
  if($secondsago>10800){return '3 hours ago';}
  if($secondsago>7200){return '2 hours ago';}
  if($secondsago>3600){return 'an hour ago';}
  // diff is less than an hour
  return round($secondsago/60,0).' minutes ago';      
 } 
 
 function navbarColor()
 {
  $db=new mysql; $db->connect();
  $uri=false; $returnval='c0c0c0';
 
  if($stmt=$db->conn->prepare("select * from config where configname='navbarColorHex'"))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $returnval=$row['configvalue'];
   }
  }
  return $returnval;
 }
 
 function getNavelements($category=false)
 {
  $db = new mysql;  $db->connect(); $elements=array();
  if($category===false)
  {
   if($stmt=$db->conn->prepare('select * from navelement order by category,sequence'))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $elements[]=array('navid'=>$row['navid'],'category'=>$row['category'],'title'=>$row['title'],'path'=>$row['path'],'sequence'=>$row['sequence']);
     }
    }
   }
  }
  else
  {// category was passed into function call
   if($stmt=$db->conn->prepare('select * from navelement where category=? order by category,sequence'))
   {
    if($stmt->bind_param('s', $category))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      while($row = $db->result->fetch_assoc())
      {
       $elements[]=array('navid'=>$row['navid'],'category'=>$row['category'],'title'=>$row['title'],'path'=>$row['path'],'sequence'=>$row['sequence']);
      }
     }
    }
   }
  }
  $db->close();
  return $elements;
 }

 function userHasNavelement($userid,$navid)
 {
  //return true;
  $db = new mysql; $db->connect(); $returnval=false;
  if($stmt=$db->conn->prepare('select * from user_navelement where userid=? and navid=?'))
  {
   $stmt->bind_param('is', $userid,$navid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $returnval=true;
   }
  }
  $db->close();
  return $returnval;     
 } 

 function getUserNavelements($userid=false)
 {
  $db = new mysql; $db->connect(); $elements=array();
  $sql='select * from user_navelement,navelement where user_navelement.navid=navelement.navid and userid=? order by category,sequence';
  if($userid===false){$sql='select * from user_navelement,navelement where user_navelement.navid=navelement.navid order by category,sequence';}
  
  if($stmt=$db->conn->prepare($sql))
  {
   if($userid!==false){$stmt->bind_param('i', $userid);}
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $elements[]=array('userid'=>$row['userid'],'navid'=>$row['navid'],'category'=>$row['category'],'title'=>$row['title'],'path'=>$row['path'],'sequence'=>$row['sequence']);
   }
  }
  $db->close();
  return $elements;
 } 

 function addUserNavelement($userid,$navid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into user_navelement values(null,?,?)'))
  {
   $stmt->bind_param('is', $userid, $navid);
   $stmt->execute();
  }
  $db->close();
 }
 
 function removeUserNavelement($userid,$navid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from user_navelement where userid=? and navid=?'))
  {
   $stmt->bind_param('is', $userid, $navid);
   $stmt->execute();
  }
  $db->close();
 }
 
 function validNavelement($navid)
 {
  $db = new mysql; $db->connect(); $returnval=false;  
  if($stmt=$db->conn->prepare('select * from navelement where navid=?'))
  {
   $stmt->bind_param('s', $navid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $returnval=true;
   }
  }
  $db->close();
  return $returnval;
 } 

 
 function getAppsByBasevehicleid($basevehicleid,$partcategories)
 {
  $categoryarray=array(); foreach($partcategories as $partcategory){$categoryarray[]=intval($partcategory);} $categorylist=implode(',',$categoryarray); // sanitize input
  $db = new mysql; $db->connect();
  $apps=array();

  if(count($partcategories))
  {
   $sql='select application.*,part.partcategory,partcategory.mfrlabel from application left join part on application.partnumber=part.partnumber left join partcategory on part.partcategory=partcategory.id where part.partcategory in('.$categorylist.') and basevehicleid=? order by partnumber';  
  }
  else
  {// empty array of categories passed in. select any category
   $sql='select application.*,part.partcategory,partcategory.mfrlabel from application left join part on application.partnumber=part.partnumber left join partcategory on part.partcategory=partcategory.id where basevehicleid=? order by partnumber';
  }
  
  if($stmt=$db->conn->prepare($sql))
  {
   $stmt->bind_param('i', $basevehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   { // ddd
    $attributes=$this->getAppAttributes($row['id']);       
    $cosmeticattributecount=0;
    foreach ($attributes as $attribute){if($attribute['cosmetic']>0){$cosmeticattributecount++;}}
    $attributeshash=$this->appAttributesHash($attributes);
    $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'attributes'=>$attributes,'attributeshash'=>$attributeshash,'cosmeticattributecount'=>$cosmeticattributecount);
   }
  }
  $db->close();
  return $apps;
 }

 function getAppsByPartcategoriesOld($partcategories,$statuslist=false)
 {
  $categoryarray=array(); foreach($partcategories as $partcategory){$categoryarray[]=intval($partcategory);} $categorylist=implode(',',$categoryarray); // sanitize input
  
  $statusclause='';
  $cleanstatuses=array();
  if($statuslist)
  {
   foreach($statuslist as $status)
   {
    if(strlen($status)==1 && $status!=';' && $status!="'" && $status!='"' && $status!='\\' && $status!='%' &&  $status!='#')
    {
     $cleanstatuses[]="'".$status."'";
    }
   }
   if(count($cleanstatuses)){ $statusclause= ' and part.lifecyclestatus in('. implode(',',$cleanstatuses).')';}
  }
  
  $db = new mysql;  $db->connect();
  $apps=array();
  if($stmt=$db->conn->prepare('select application.*,part.partcategory,partcategory.mfrlabel from application left join part on application.partnumber=part.partnumber left join partcategory on part.partcategory=partcategory.id where status=0 and part.partcategory in('.$categorylist.') '.$statusclause.' order by partnumber'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributes=$this->getAppAttributes($row['id']);
    $attributeshash=$this->appAttributesHash($attributes);
    $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'partcategory'=>$row['partcategory'],'mfrlabel'=>$row['mfrlabel'],'attributes'=>$attributes,'attributeshash'=>$attributeshash);
   }
  }
  $db->close();
  return $apps;
 }

 function getAppsByPartcategories($partcategories,$statuslist=false)
 {  
  $db = new mysql;  $db->connect();
  
  //build list of partnumbers in given categoirs
  
  $tempstatuses=false;
  if($statuslist)
  {
   $tempstatuses=array();
   foreach($statuslist as $s){$tempstatuses[]=array('lifecyclestatus'=>$s);}
  }
  
  $partnumbers=$this->getPartnumbersByPartcategories($partcategories, $tempstatuses);
  
  $apps=array();  
  foreach($partnumbers as $partnumber)
  {
   $appcount=0;
   // look for apps with the direct partnumber
 //  if($stmt=$db->conn->prepare('select * from application where partnumber=?'))
   if($stmt=$db->conn->prepare('select application.*,part.partcategory,partcategory.mfrlabel from application left join part on application.partnumber=part.partnumber left join partcategory on part.partcategory=partcategory.id where status=0 and part.partnumber=?'))
   {
    $stmt->bind_param('s', $partnumber);
    $stmt->execute();
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     //$attributes=$this->getAppAttributes($row['id']);
     //$apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'attributes'=>$attributes,'inheritedfrom'=>'');
     $attributes=$this->getAppAttributes($row['id']);
     $attributeshash=$this->appAttributesHash($attributes);
     $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'partcategory'=>$row['partcategory'],'mfrlabel'=>$row['mfrlabel'],'attributes'=>$attributes,'attributeshash'=>$attributeshash);     
     $appcount++;
    }
   }

   if($appcount==0)
   {// part has no apps - see if it has a basepart and maybe ues the baseprt's apps if they exist       
    $basepartnumber=$this->basepartOfPart($partnumber);
    if($basepartnumber)
    {// this part has a base and no apps of its own - we need to deal with inheritance
  //   if($stmt=$db->conn->prepare('select * from application where partnumber=?'))
     if($stmt=$db->conn->prepare('select application.*,part.partcategory,partcategory.mfrlabel from application left join part on application.partnumber=part.partnumber left join partcategory on part.partcategory=partcategory.id where status=0 and part.partnumber=?'))
     {
      $stmt->bind_param('s', $basepartnumber);
      $stmt->execute();
      $db->result = $stmt->get_result();
      while($row = $db->result->fetch_assoc())
      {
//       $attributes=$this->getAppAttributes($row['id']);
//       $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$partnumber,'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'attributes'=>$attributes,'inheritedfrom'=>$basepart);
       $attributes=$this->getAppAttributes($row['id']);
       $attributeshash=$this->appAttributesHash($attributes);
       
       $mfrlabel=$row['mfrlabel'];
       //mfr lable in the apps result set 
       $mfrlabel=$this->partMfrLabel($partnumber);
       //fff
       $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$partnumber,'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'partcategory'=>$row['partcategory'],'mfrlabel'=>$mfrlabel,'attributes'=>$attributes,'attributeshash'=>$attributeshash);    
      }
     }
    }  
   }
  }
   
  $db->close();
  return $apps;     
 }
 
 
 function getAppsByParttype($parttypeid,$includeattributes)
 { // relies on the part-type in the part table - not the application table
  $db = new mysql;  $db->connect();    //xxx
  $apps=array();  
  if($stmt=$db->conn->prepare('select application.*,part.partcategory,partcategory.mfrlabel from application left join part on application.partnumber=part.partnumber left join partcategory on part.partcategory=partcategory.id where status=0 and part.parttypeid=?'))
  {
   $stmt->bind_param('i', $parttypeid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {       
    $attributes=array();
    $attributeshash='';
    if($includeattributes)
    {
     $attributes=$this->getAppAttributes($row['id']);
     $attributeshash=$this->appAttributesHash($attributes);        
    }
    $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'partcategory'=>$row['partcategory'],'mfrlabel'=>$row['mfrlabel'],'attributes'=>$attributes,'attributeshash'=>$attributeshash,'inheritedfrom'=>'');
   }
  }
   
  $db->close();
  return $apps;     
 }


//--------- 
 
 function getAppsByAttribute($attributetype, $searchterm)
 {
  $db = new mysql;  $db->connect();
  $apps=array();  
  if($stmt=$db->conn->prepare('select application.* from application,application_attribute where application.id=application_attribute.applicationid and status=0 and type=? and value like ?'))
  {
   $stmt->bind_param('ss', $attributetype, $searchterm);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    
    $attributes=$this->getAppAttributes($row['id']);
    $cosmeticattributecount=0;
    foreach ($attributes as $attribute){if($attribute['cosmetic']>0){$cosmeticattributecount++;}}
    $attributeshash=$this->appAttributesHash($attributes);
    $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'attributes'=>$attributes,'attributeshash'=>$attributeshash,'inheritedfrom'=>'','cosmeticattributecount'=>$cosmeticattributecount);
   }
  }   
  $db->close();
  return $apps;     
 }
 
 function getAppsBySearch($status,$cosmetic,$parttypeid,$positionid,$quantityperapp,$secondsback)
 {//qqq
  $db = new mysql;  $db->connect();
  $apps=array();


  $sql="create temporary table latestappdatetimes as select applicationid, max(eventdatetime) as latesttouch from application_history where eventdatetime > '".date('Y-m-d H:i:s',time()-$secondsback)."' group by applicationid order by applicationid";
  if($stmt=$db->conn->prepare($sql))
  {
   $stmt->execute();
  }
  
  $statusclause=''; if($status!='any'){$statusclause=' and status ='.intval($status);}
  $cosmeticclause=''; if($cosmetic!='any'){$cosmeticclause=' and cosmetic ='.intval($cosmetic);}
  $parttypeidclause=''; if($parttypeid!='any'){$parttypeidclause=' and parttypeid ='.intval($parttypeid);}
  $positionidclause=''; if($positionid!='any'){$positionidclause=' and positionid ='.intval($positionid);}
  $quantityperappclause=''; if($quantityperapp!='any'){$quantityperappclause=' and quantityperapp ='.intval($quantityperapp);}
  
  if($stmt=$db->conn->prepare('select application.*,latestappdatetimes.latesttouch from application,latestappdatetimes where application.id=latestappdatetimes.applicationid'.$statusclause.$cosmeticclause.$parttypeidclause.$positionidclause.$quantityperappclause.' order by latesttouch desc'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
//    $appageseconds=$this->ageSecondsOfApp($row['id']);
//    if($secondsback!='any')
//    {
//     if($appageseconds<intval($secondsback)){continue;}     
//    }
    
    $attributes=$this->getAppAttributes($row['id']);
    $cosmeticattributecount=0;
    foreach ($attributes as $attribute){if($attribute['cosmetic']>0){$cosmeticattributecount++;}}
    $attributeshash=$this->appAttributesHash($attributes);
    $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'attributes'=>$attributes,'attributeshash'=>$attributeshash,'inheritedfrom'=>'','cosmeticattributecount'=>$cosmeticattributecount,'ageseconds'=>0,'latesttouch'=>$row['latesttouch']);
   }
  }   
  $db->close();
  return $apps;     
 }
 
 
 
 
 
  

 function getAppOids()
 {
  $db = new mysql;  $db->connect(); $oids=array();
  if($stmt=$db->conn->prepare('select oid from application order by id'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $oids[]=$row['oid'];
   }
  }
  $db->close();
  return $oids;
 }

 function getAppIDsByRandom($limit)
 {
  $db = new mysql; $db->connect(); $ids=array();
  if($stmt=$db->conn->prepare('SELECT ROUND(RAND() * (SELECT COUNT(*) FROM application)) as rando'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {   
    $offset=$row['rando'];
    if($stmt=$db->conn->prepare('SELECT id FROM application LIMIT ? OFFSET ?'))
    {
     if($stmt->bind_param('ii', $limit,$offset))
     {
      $stmt->execute();
      $db->result = $stmt->get_result();
      while($row = $db->result->fetch_assoc())
      {
       $ids[]=$row['id'];    
      }
     }     
    }
   }
  }
  $db->close();
  return $ids;
 }
 
 function appPositions($partnumber)
 {
  $db = new mysql; $db->connect(); $positionids=[];
  if($stmt=$db->conn->prepare('select distinct positionid from application where partnumber=? order by positionid'))
  {
   if($stmt->bind_param('s', $partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $positionids[]=$row['positionid'];
     }
    }
   }
  }
  $db->close();
  return $positionids;
 }
 
 function typicalAppPosition($partnumber)
 {
  $db = new mysql;  $db->connect();
  $positionid=0;
  if($stmt=$db->conn->prepare('select positionid, count(*) as hits from application where partnumber=? group by positionid order by hits desc limit 1'))
  {
   if($stmt->bind_param('s', $partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $positionid=$row['positionid'];
     }
    }
   }
  }
  $db->close();
  return $positionid;
 }
 
 function typicalQuantityPerApp($partnumber)
 {
  $db = new mysql;  $db->connect();
  $qty=0;
  if($stmt=$db->conn->prepare('select quantityperapp, count(*) as hits from application where partnumber=? group by positionid order by hits desc limit 1;'))
  {
   if($stmt->bind_param('s', $partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $qty=$row['quantityperapp'];
     }
    }
   }
  }
  $db->close();
  return $qty;
 }

 
 
function countAppsByPartcategories($partcategories)
{
  $categoryarray=array(); foreach($partcategories as $partcategory){$categoryarray[]=intval($partcategory);} $categorylist=implode(',',$categoryarray); // sanitize input
  $db = new mysql;  $db->connect();
  $count=0;
  
  $sql='select count(*) as appcount from application left join part on application.partnumber=part.partnumber where part.partcategory in('.$categorylist.') and application.status=0';
  if(count($partcategories)==0)
  {
      $sql='select count(*) as appcount from application where status=0'; 
  }
  
  if($stmt=$db->conn->prepare($sql))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $count=$row['appcount'];
   }
  }
  $db->close();
  return $count;
}


 function getAppsByPartnumber($partnumber,$excludeattributes=false)
 {
  $db = new mysql; $db->connect(); $apps=array();
  // basepart's apps will only be returned if no apps are found for passed in to this function.
  if($stmt=$db->conn->prepare('select * from application where partnumber=?'))
  {
   $stmt->bind_param('s', $partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributes=array();
    if(!$excludeattributes)
    {   
     $attributes=$this->getAppAttributes($row['id']);
    }
    
    $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'attributes'=>$attributes,'inheritedfrom'=>'');
   }
  }

  if(count($apps)==0)
  {// the passed-in part has no apps. Look for baseparts's apps
   $basepart=$this->basepartOfPart($partnumber);
   if($basepart)
   {// this part has a base and no apps of its own - we need to deal with inheritance
    if($stmt=$db->conn->prepare('select * from application where partnumber=?'))
    {
     $stmt->bind_param('s', $basepart);
     $stmt->execute();
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $attributes=array();
      if(!$excludeattributes)
      {
       $attributes=$this->getAppAttributes($row['id']);
      }
      $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'attributes'=>$attributes,'inheritedfrom'=>$basepart);
     }
    }
   }  
  }
  
  $db->close();
  return $apps;
 }

 
 function getFavoriteMakes()
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $makes=array();
  if($stmt=$db->conn->prepare('select * from Make order by MakeName'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $makes[]=array('id'=>$row['MakeID'],'name'=>$row['MakeName']);
   }
  }
  $db->close();
  return $makes;
 }

 function getFavoriteParttypes()
 {
  $db = new mysql; $db->connect();
  $parttypes=array();
  if($stmt=$db->conn->prepare('select * from parttype order by `name`'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $parttypes[]=array('id'=>$row['id'],'name'=>$row['name']);
   }
  }
  $db->close();
  return $parttypes;
 }
 
 function getFavoritePositions()
 {
  $db = new mysql; $db->connect();
  $positions=array();
  if($stmt=$db->conn->prepare('select * from position order by `name`'))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $positions[]=array('id'=>$row['id'],'name'=>$row['name']);
    }
   }
  }
  $db->close();
  return $positions;
 }

 function addFavoritePosition($id,$name)
 {
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into position (id,name) values(?,?)'))
  {
   $stmt->bind_param('is', $id,$name);
   $stmt->execute();
  } // else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }

 function removeFavoritePosition($id)
 {
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from position where id=?'))
  {
   $stmt->bind_param('i', $id);
   $stmt->execute();
  } // else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }
 
 function getApp($appid)
 {
  $db = new mysql; $db->connect();
  $app=false;
  if($stmt=$db->conn->prepare('select * from application where id=?'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $attributes=$this->getAppAttributes($appid);
    $attributeshash=$this->appAttributesHash($attributes);
    $app=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'internalnotes'=>base64_decode($row['internalnotes']),'cosmetic'=>$row['cosmetic'],'attributes'=>$attributes,'attributeshash'=>$attributeshash);
   }
  }
  $db->close();
  return $app;
 }

 function getAppByOid($oid)
 {
  $db = new mysql; $db->connect(); $app=false;
  if($stmt=$db->conn->prepare('select * from application where oid=?'))
  {
   $stmt->bind_param('s', $oid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $attributes=$this->getAppAttributes($row['id']);
    $attributeshash=$this->appAttributesHash($attributes);
    $app=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'internalnotes'=>base64_decode($row['internalnotes']),'cosmetic'=>$row['cosmetic'],'attributes'=>$attributes,'attributeshash'=>$attributeshash);
   }
  }
  $db->close();
  return $app;
 }

 function getOIDofApp($appid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $oid='';
  if($stmt=$db->conn->prepare('select oid from application where id=?'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $oid=$row['oid'];
   }
  }
  $db->close();
  return $oid;
 }


 function getAppAttributes($appid)
 {
  $db = new mysql; $db->connect();
  $attributes=array();
  if($stmt=$db->conn->prepare('select * from application_attribute where applicationid=? order by sequence'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributes[]=array('id'=>$row['id'],'name'=>$row['name'],'value'=>$row['value'],'type'=>$row['type'],'sequence'=>$row['sequence'],'cosmetic'=>$row['cosmetic']);
   }
  }
  $db->close();
  return $attributes;
 }
 
 function getAppAttribute($attributeid)
 {
  $db = new mysql; $db->connect();
  $attribute=false;
  if($stmt=$db->conn->prepare('select * from application_attribute where id=?'))
  {
   if($stmt->bind_param('i', $attributeid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $attribute=array('id'=>$row['id'],'applicationid'=>$row['applicationid'],'name'=>$row['name'],'value'=>$row['value'],'type'=>$row['type'],'sequence'=>$row['sequence'],'cosmetic'=>$row['cosmetic']);
     }
    }
   }
  }
  $db->close();
  return $attribute;
 }
 
 

 function appAttributesHash($attributes)
 {
  $hashinput='';
  foreach($attributes as $attribute)
  {
   $hashinput.=$attribute['name'].$attribute['value'].$attribute['type'].$attribute['sequence'].$attribute['cosmetic'];
  }
  return md5($hashinput);
 }

 function cleansequenceAppAttributes($appid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $attributes=array();
  if($stmt=$db->conn->prepare('select id from application_attribute where applicationid=? order by sequence'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc()){$attributes[]=$row['id'];}
  }

  $sequence=10;
  if($stmt=$db->conn->prepare('update application_attribute set sequence=? where id=?'))
  {
   $stmt->bind_param('ii',$sequence,$id);
   foreach($attributes as $id)
   {
    $stmt->execute();
    $sequence+=10;
   }
  }
  $db->close();
 }





 function toggleAppAttributeCosmetic($appid,$attributeid)
 {
  $db = new mysql; $db->connect(); $oid=false;
  if($stmt=$db->conn->prepare('update application_attribute set cosmetic=cosmetic XOR 1 where applicationid=? and id=?'))
  {
   if($stmt->bind_param('ii', $appid,$attributeid))
   {
    if($stmt->execute())
    {
     $oid=$this->updateAppOID($appid);
    }
   } 
  }
  $db->close();
  return $oid;
 }

 function incAppAttributeSequence($appid,$attributeid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update application_attribute set sequence=sequence+15 where applicationid=? and id=?'))
  {
//   $this->updateAppOID($appid);
   $stmt->bind_param('ii', $appid,$attributeid);
   $stmt->execute();
  }
  $db->close();
 }

 function deleteAppByOid($oid)
 {
  $db = new mysql; $db->connect(); $appids=array();
  if($stmt=$db->conn->prepare('select id from application where oid=?'))
  {
   if($stmt->bind_param('s', $oid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $appids[]=$row['id'];
     }
    }
   }
  }

  if(count($appids))
  {
   $idtemp=0;

   if($stmt=$db->conn->prepare('delete from application_attribute where applicationid=?'))
   {
    $stmt->bind_param('i',$idtemp);
    foreach($appids as $appid)
    {
     $idtemp=$appid;
     $stmt->execute();
    }
   }

   if($stmt=$db->conn->prepare('delete from application_asset where applicationid=?'))
   {
    $stmt->bind_param('i',$idtemp);
    foreach($appids as $appid)
    {
     $idtemp=$appid;
     $stmt->execute();
    }
   }
  
   if($stmt=$db->conn->prepare('delete from application where oid=?'))
   {
    $stmt->bind_param('s',$oid);
    $stmt->execute();
   }
  }
  $db->close();
  return $appids;
 }
 
 
 
 function deleteAppsByPartnumber($partnumber)
 {
  $db = new mysql; $db->connect(); $appids=array();
  if($stmt=$db->conn->prepare('select id from application where partnumber=?'))
  {
   if($stmt->bind_param('s', $partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $appids[]=$row['id'];
     }
    }
   }
  }

  if(count($appids))
  {
   $idtemp=0;

   if($stmt=$db->conn->prepare('delete from application_attribute where applicationid=?'))
   {
    $stmt->bind_param('i',$idtemp);
    foreach($appids as $appid)
    {
     $idtemp=$appid;
     $stmt->execute();
    }
   }

   if($stmt=$db->conn->prepare('delete from application_asset where applicationid=?'))
   {
    $stmt->bind_param('i',$idtemp);
    foreach($appids as $appid)
    {
     $idtemp=$appid;
     $stmt->execute();
    }
   }
   
   if($stmt=$db->conn->prepare('delete from application where partnumber=?'))
   {
    $stmt->bind_param('s',$partnumber);
    $stmt->execute();
   }
  
  }
  $db->close();
  return $appids;
 }
 
 

 function removeDeletedApps()
 {
  $db = new mysql; $db->connect(); $appids=array();
  if($stmt=$db->conn->prepare('select id from application where status=1'))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $appids[]=$row['id'];
    }
   }
  }

  if(count($appids))
  {
   $idtemp=0;

   if($stmt=$db->conn->prepare('delete from application_attribute where applicationid=?'))
   {
    $stmt->bind_param('i',$idtemp);
    foreach($appids as $appid)
    {
     $idtemp=$appid;
     $stmt->execute();
    }
   }

   if($stmt=$db->conn->prepare('delete from application_asset where applicationid=?'))
   {
    $stmt->bind_param('i',$idtemp);
    foreach($appids as $appid)
    {
     $idtemp=$appid;
     $stmt->execute();
    }
   }
  
   if($stmt=$db->conn->prepare('delete from application where status=1'))
   {
    $stmt->execute();
   }
  }
  $db->close();
  return $appids;
 }
  
 function deleteAppAttribute($appid,$attributeid)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('delete from application_attribute where applicationid=? and id=?'))
  {
   $this->updateAppOID($appid);
   $stmt->bind_param('ii', $appid,$attributeid);
   $stmt->execute();
  } // else{print_r($db->conn->error);}
  $db->close();
 }

 function highestAppAttributeSequence($appid)
 {
  $topsequence=0;
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('select max(sequence) as topsequence from application_attribute where applicationid=?'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $topsequence=intval($row['topsequence']);
   }
  }  //else{print_r($db->conn->error);}
  $db->close();
  return $topsequence;
 }

 function getAppAssets($appid)
 {
  $db = new mysql; $db->connect();
  $assets=array();
  if($stmt=$db->conn->prepare('select * from application_asset where applicationid=? order by assetItemOrder'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $assets[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'representation'=>$row['representation'],'assetItemOrder'=>$row['assetItemOrder'],'cosmetic'=>$row['cosmetic']);
   }
  }
  $db->close();
  return $assets;
 }

 function addAssetToApp($applicationid,$assetid,$representation,$assetItemOrder,$cosmetic)
 {
  $db=new mysql; $db->connect();$id=false;
  if($stmt=$db->conn->prepare('insert into application_asset (id,applicationid,assetid,representation,assetItemOrder,cosmetic) values(null,?,?,?,?,?)'))
  {
   if($stmt->bind_param('issii', $applicationid,$assetid,$representation,$assetItemOrder,$cosmetic))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
     $this->updateAppOID($applicationid);
    }
   }
  }
  $db->close();
  return $id;
 }
 
 function deleteAppAsset($appid,$id)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from application_asset where applicationid=? and id=?'))
  {
   $this->updateAppOID($appid);
   $stmt->bind_param('ii', $appid,$id);
   $stmt->execute();
  }
  $db->close();
 }
 
 
 
 
 
 
 
 
 function getPart($partnumber)
 {
  $db = new mysql; $db->connect();
  $part=false;
  $typicalPosition=$this->typicalAppPosition($partnumber);
  $typicalQty=$this->typicalQuantityPerApp($partnumber);
  
  if($stmt=$db->conn->prepare('select part.*,partcategory.name,partcategory.brandID from part left join partcategory on part.partcategory=partcategory.id where partnumber=?'))
  {
   $stmt->bind_param('s', $partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $part=array('partnumber'=>$row['partnumber'],
        'oid'=>$row['oid'],
        'parttypeid'=>$row['parttypeid'],
        'lifecyclestatus'=>$row['lifecyclestatus'],
        'partcategory'=>$row['partcategory'],
        'replacedby'=>$row['replacedby'],
        'internalnotes'=> base64_decode($row['internalnotes']),
        'description'=>$row['description'],'GTIN'=>$row['GTIN'],'UNSPC'=>$row['UNSPC'],
        'brandid'=>$row['brandID'],
        'createdDate'=>$row['createdDate'],
        'firststockedDate'=>$row['firststockedDate'],
        'discontinuedDate'=>$row['discontinuedDate'],
        'obsoletedDate'=>$row['obsoletedDate'],
        'supersededDate'=>$row['supersededDate'],
        'availableDate'=>$row['availableDate'],
        'typicalposition'=>$typicalPosition,
        'typicalqtyperapp'=>$typicalQty,
        'basepart'=>$row['basepart']);
   }
  }
  $db->close();
  return $part;
 }

 function deletePart($partnumber)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from interchange where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }
  
  if($stmt=$db->conn->prepare('delete from alert_part where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }
  
  if($stmt=$db->conn->prepare('delete from interchange where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }
  
  if($stmt=$db->conn->prepare('delete from package where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }

  if($stmt=$db->conn->prepare('delete from part_VIO where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }
  
  if($stmt=$db->conn->prepare('delete from part_application_summary where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }
  
  if($stmt=$db->conn->prepare('delete from part_asset where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }
  
  if($stmt=$db->conn->prepare('delete from part_attribute where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }
  
  if($stmt=$db->conn->prepare('delete from part_balance where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }
  
  if($stmt=$db->conn->prepare('delete from part_description where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }

  if($stmt=$db->conn->prepare('delete from receiverprofile_parttranslation where internalpart = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }
  
  if($stmt=$db->conn->prepare('delete from part_history where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }
    
  if($stmt=$db->conn->prepare('delete from part where partnumber = ?'))
  {
   $stmt->bind_param('s', $partnumber); $stmt->execute();
  }
  
  $this->deleteAppsByPartnumber($partnumber);
  
  $db->close();
 }
 
 function getPartByOID($oid)
 {
  $db = new mysql; $db->connect();
  $part=false;
  
  if($stmt=$db->conn->prepare('select * from part where oid=?'))
  {
   $stmt->bind_param('s', $oid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $part=array('partnumber'=>$row['partnumber'],
        'partcategory'=>$row['partcategory'],
        'parttypeid'=>$row['parttypeid'],
        'replacedby'=>$row['replacedby'],
        'lifecyclestatus'=>$row['lifecyclestatus'],
        'internalnotes'=>$row['internalnotes'],
        'description'=>$row['description'],
        'GTIN'=>$row['GTIN'],
        'UNSPC'=>$row['UNSPC'],
        'createdDate'=>$row['createdDate'],
        'firststockedDate'=>$row['firststockedDate'],
        'discontinuedDate'=>$row['discontinuedDate'],
        'obsoletedDate'=>$row['obsoletedDate'],
        'supersededDate'=>$row['supersededDate'],
        'availableDate'=>$row['availableDate'],        
        'oid'=>$row['oid'],
        'basepart'=>$row['basepart']);    
   }
  }
  $db->close();
  return $part;
 }

 function getPartsSinceFirststockedDate($date)
 {
  $db = new mysql; $db->connect(); $parts=array();
  
  if($stmt=$db->conn->prepare('select * from part where firststockedDate>=? order by firststockedDate desc'))
  {
   $stmt->bind_param('s', $date);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $parts[]=array('partnumber'=>$row['partnumber'],
        'partcategory'=>$row['partcategory'],
        'parttypeid'=>$row['parttypeid'],
        'replacedby'=>$row['replacedby'],
        'lifecyclestatus'=>$row['lifecyclestatus'],
        'internalnotes'=>$row['internalnotes'],
        'description'=>$row['description'],
        'GTIN'=>$row['GTIN'],
        'UNSPC'=>$row['UNSPC'],
        'createdDate'=>$row['createdDate'],
        'firststockedDate'=>$row['firststockedDate'],
        'discontinuedDate'=>$row['discontinuedDate'],
        'obsoletedDate'=>$row['obsoletedDate'],
        'supersededDate'=>$row['supersededDate'],
        'availableDate'=>$row['availableDate'],
        'oid'=>$row['oid'],
        'basepart'=>$row['basepart']);    
   }
  }
  $db->close();
  return $parts;
 }
 
 
 function getParts($partnumber,$matchtype,$partcategory,$parttypeid,$lifecyclestatus,$basepart,$limit)
 {
  $db = new mysql; $db->connect();
  $parts=array();
  
  $partnumber=$this->sanitizePartnumber($partnumber);
  $basepart=$this->sanitizePartnumber($basepart);
          
  if($partcategory=='any'){$partcategoryclause='';}else{$partcategoryclause=' and partcategory='.intval($partcategory);}
  if($parttypeid=='any'){$parttypeclause='';}else{$parttypeclause=' and parttypeid='.intval($parttypeid);}
  if($basepart=='any' || $basepart=='ANY'){$basepartclause='';}else{$basepartclause=" and basepart='".$basepart."'";}
  
  $sql='select part.*,partcategory.name as partcategoryname from part left join partcategory on part.partcategory=partcategory.id where partnumber like ? '.$partcategoryclause.$parttypeclause.' and lifecyclestatus like ? '.$basepartclause.' order by partnumber limit ?';

  if($stmt=$db->conn->prepare($sql))
  {
   $searchstring=$partnumber;
   if($matchtype=='contains'){$searchstring='%'.$partnumber.'%';}
   if($matchtype=='startswith'){$searchstring=$partnumber.'%';}
   if($matchtype=='endswith'){$searchstring='%'.$partnumber;}
   if($lifecyclestatus=='any'){$lifecyclestatus='%';}

   if($stmt->bind_param('ssi', $searchstring, $lifecyclestatus, $limit))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $parts[]=array('partnumber'=>$row['partnumber'],'oid'=>$row['oid'],'parttypeid'=>$row['parttypeid'],'lifecyclestatus'=>$row['lifecyclestatus'],'partcategory'=>$row['partcategory'],'partcategoryname'=>$row['partcategoryname'],'replacedby'=>$row['replacedby'],'description'=>$row['description'],'basepart'=>$row['basepart']);
     }
    }
   }
  }
  $db->close();
  return $parts;
 }

 function getPartnumbersByPartcategories($partcategories,$statuses=false)
 {
  if($statuses===false)
  {// no status array was passeed. query for all of them
   $statusliststring="'4','A','2','8','3','1','6','7'";   
  }
  else
  {
   $statusesquoted=array(); foreach($statuses as $status){$statusesquoted[]="'".$status['lifecyclestatus']."'";}
   $statusliststring=implode(',',$statusesquoted);
  }
   
  $categoryarray=array(); foreach($partcategories as $partcategory){$categoryarray[]=intval($partcategory);} $categorylist=implode(',',$categoryarray); // sanitize input
  $db = new mysql; $db->connect(); $partnumbers=array();
  if($stmt=$db->conn->prepare('select partnumber from part where partcategory in('.$categorylist.') and lifecyclestatus in('.$statusliststring.') order by partnumber'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $partnumbers[]=$row['partnumber'];
   }
  }
  $db->close();
  return $partnumbers;
 }


 function getPartnumbersByGTIN($gtin)
 {
  $db = new mysql; $db->connect(); $partnumbers=array();
  if(strlen(trim($gtin))>0)
  {
   if($stmt=$db->conn->prepare('select partnumber from part where GTIN=?'))
   { 
    $stmt->bind_param('s', $gtin);
    $stmt->execute();
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $partnumbers[]=$row['partnumber'];
    }
   }
   $db->close();
  }
  return $partnumbers;
 }


 function getPartnumbersByBasepart($partnumber)
 {
  $db = new mysql; $db->connect(); $partnumbers=array();
  if(strlen(trim($partnumber))>0)
  {
   if($stmt=$db->conn->prepare('select partnumber from part where basepart=?'))
   { 
    $stmt->bind_param('s', $partnumber);
    $stmt->execute();
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $partnumbers[]=$row['partnumber'];
    }
   }
   $db->close();
  }
  return $partnumbers;
 }
 
 
 
 function getWhereUsedOfKitComponent($partnumber)
 {
  $db = new mysql; $db->connect(); $parts=array();
  if($stmt=$db->conn->prepare("select * from partrelationship where rightpartnumber=? and relationtype='kit' order by leftpartnumber"))
  {
   if($stmt->bind_param('s',$partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $parts[]=array('id'=>$row['id'],'partnumber'=>$row['leftpartnumber'],'units'=>$row['units'],'sequence'=>$row['sequence']);
     }
    }
   }
  }
  $db->close();
  return $parts;
 }
 
 
 function getKitComponents($partnumber)
 {
  $db = new mysql; $db->connect(); $parts=array();
  if($stmt=$db->conn->prepare("select * from partrelationship where leftpartnumber=? and relationtype='kit' order by sequence,rightpartnumber"))
  {
   if($stmt->bind_param('s',$partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $parts[]=array('id'=>$row['id'],'partnumber'=>$row['rightpartnumber'],'units'=>round($row['units'],2),'sequence'=>$row['sequence']);
     }
    }
   }
  }
  $db->close();
  return $parts;
 }
 
 
 function getKits()
 { // all parts that are in partrelationship table with type "kit"
     
  $db = new mysql; $db->connect(); $kits=array();
  if($stmt=$db->conn->prepare("select * from partrelationship where relationtype='kit' order by leftpartnumber, sequence, rightpartnumber"))
  {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {    
      $kits[$row['leftpartnumber']][]=array('id'=>$row['id'],'component'=>$row['rightpartnumber'],'units'=>round($row['units'],2),'sequence'=>$row['sequence']);
     }
    }
  }
  $db->close();
  return $kits;
 }
 
  
 
 
  
 function basepartOfPart($partnumber)
 {
  $db = new mysql; $db->connect(); $basepart=false;
  if(strlen(trim($partnumber))>0)
  {
   if($stmt=$db->conn->prepare('select basepart from part where partnumber=?'))
   { 
    $stmt->bind_param('s', $partnumber);
    $stmt->execute();
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $basepart=$row['basepart'];
    }
   }
   $db->close();
  }
  return $basepart;
 }
 
 
 function setPartBasepart($partnumber,$basepart,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set basepart=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $basepart,$partnumber);
   $stmt->execute();
  }
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }

 
 
 // for continuous background auditing (small selections of the entire part population)
 function getPartnumbersByRandom($limit)
 {
  $db = new mysql; $db->connect(); $partnumbers=array();
  if($stmt=$db->conn->prepare('select partnumber from part order by rand() limit ?'))
  {
   $stmt->bind_param('i', $limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $partnumbers[]=$row['partnumber'];
   }
  }
  $db->close();
  return $partnumbers;
 }
 
 function updateAppOID($appid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update application set oid=? where id=?'))
  {
   $oid=$this->newoid();
   $stmt->bind_param('si', $oid, $appid);
   $stmt->execute();
  }
  $db->close();
  return $oid;
 }

  function getOIDofPart($partnumber)
 {
  $db = new mysql; $db->connect();
  $oid='';
  if($stmt=$db->conn->prepare('select oid from part where partnumber=?'))
  {
   $stmt->bind_param('s', $partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $oid=$row['oid'];
   }
  }
  $db->close();
  return $oid;
 }


 function updatePartOID($partnumber)
 {
  $db = new mysql; $oid=false;
  $db->connect();
  if($stmt=$db->conn->prepare('update part set oid=? where partnumber=?'))
  {
   $oid=$this->newoid();
   $stmt->bind_param('ss', $oid, $partnumber);
   $stmt->execute();
  }
  $db->close();
  return $oid;
 }

 function setPartOID($partnumber,$oid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set oid=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $oid, $partnumber);
   $stmt->execute();
  }
  $db->close();
 }


 
 function setPartParttype($partnumber,$parttypeid,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set parttypeid=? where partnumber=?'))
  {
   $stmt->bind_param('is',$parttypeid,$partnumber);
   $stmt->execute();
   if($updateoid){$this->updatePartOID($partnumber);}
  }
  $db->close();
 }
 
 function setPartLifecyclestatus($partnumber,$lifecyclestatus,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set lifecyclestatus=? where partnumber=?'))
  {
   $stmt->bind_param('ss',$lifecyclestatus,$partnumber);
   $stmt->execute();
   if($updateoid){$this->updatePartOID($partnumber);}
  }
  $db->close();
 }
 
 function setPartCategory($partnumber,$partcategory,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set partcategory=? where partnumber=?'))
  {
   $stmt->bind_param('is',$partcategory,$partnumber);
   $stmt->execute();
   if($updateoid){$this->updatePartOID($partnumber);}
  }
  $db->close();
 }
 
 function setPartInternalnotes($partnumber,$internalnotes,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set internalnotes=? where partnumber=?'))
  {
   $encodednotes=base64_encode($internalnotes);
   $stmt->bind_param('ss', $encodednotes,$partnumber);
   $stmt->execute();
   if($updateoid){$this->updatePartOID($partnumber);}
  }
  $db->close();
 }
 
 function setPartDescription($partnumber,$description,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set description=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $description,$partnumber);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }

 function setPartGTIN($partnumber,$gtin,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set GTIN=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $gtin,$partnumber);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }

 function setPartUNSPC($partnumber,$unspc,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set UNSPC=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $unspc,$partnumber);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }
 
 function setPartReplacedby($partnumber,$replacedby,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set replacedby=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $replacedby,$partnumber);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }
   
 function setPartCreatedDate($partnumber,$createddate,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set createdDate=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $createddate, $partnumber);
   $stmt->execute();
  }
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }
  
 function setPartFirststockedDate($partnumber,$firststockeddate,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set firststockedDate=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $firststockeddate, $partnumber);
   $stmt->execute();
  }
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }
 
 function setPartDiscontinuedDate($partnumber,$discontinueddate,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set discontinuedDate=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $discontinueddate, $partnumber);
   $stmt->execute();
  }
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }
 
 function setPartSupersededdDate($partnumber,$supersededdate,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set supersededDate=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $supersededdate, $partnumber);
   $stmt->execute();
  }
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }
 
 function setPartObsoletedDate($partnumber,$obsoleteddate,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set obsoletedDate=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $obsoleteddate, $partnumber);
   $stmt->execute();
  }
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }

 function setPartAvailableDate($partnumber,$availabledate,$updateoid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part set availableDate=? where partnumber=?'))
  {
   $stmt->bind_param('ss', $availabledate, $partnumber);
   $stmt->execute();
  }
  if($updateoid){$this->updatePartOID($partnumber);}
  $db->close();
 }
 
 
 function getPartAttribute($partnumber,$PAID,$attributename,$uom=false)
 {
  $db = new mysql; $db->connect(); $attributes=false;
  
  if($uom)
  {// optional uom was given - include it in the selection      
   if($stmt=$db->conn->prepare('select id,PAID,userDefinedAttributeName,`value`,uom from part_attribute where partnumber=? and PAID=? and userDefinedAttributeName=? and uom=?'))
   {
    $stmt->bind_param('siss',$partnumber,$PAID,$attributename,$uom);
    $stmt->execute();
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $attributes[]=array('id'=>$row['id'],'PAID'=>$row['PAID'],'value'=>$row['userDefinedAttributeName'],'value'=>$row['value'],'uom'=>$row['uom']);
    }
   }
  }
  else
  {// no uom was given - leave it off the selection criteria
   if($stmt=$db->conn->prepare('select id,PAID,userDefinedAttributeName,`value`,uom from part_attribute where partnumber=? and PAID=? and userDefinedAttributeName=?'))
   {
    $stmt->bind_param('sis',$partnumber,$PAID,$attributename);
    $stmt->execute();
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $attributes[]=array('id'=>$row['id'],'PAID'=>$row['PAID'],'value'=>$row['userDefinedAttributeName'],'value'=>$row['value'],'uom'=>$row['uom']);
    }
   }    
  }
  $db->close();
  return $attributes;
 }

 function getPartAttributeById($attributeid)
 {
  $db = new mysql; $db->connect();
  $attribute=false;
  if($stmt=$db->conn->prepare('select id,partnumber,PAID,userDefinedAttributeName,`value`,uom from part_attribute where id=?'))
  {
   $stmt->bind_param('i',$attributeid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $attribute=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'name'=>$row['userDefinedAttributeName'],'PAID'=>$row['PAID'],'value'=>$row['userDefinedAttributeName'],'value'=>$row['value'],'uom'=>$row['uom']);
   }
  }
  $db->close();
  return $attribute;
 }

 function getPartDescriptions($partnumber)
 {
  $db = new mysql; $db->connect(); $descriptions=array();

  $keyeddescriptions=array();
  if($stmt=$db->conn->prepare('select * from part_description where partnumber=?'))
  {
   if($stmt->bind_param('s',$partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $keyeddescriptions[$row['description'].$row['descriptioncode'].$row['languagecode']]='';
      $descriptions[]=array('id'=>$row['id'],'description'=>$row['description'],'descriptioncode'=>$row['descriptioncode'],'sequence'=>$row['sequence'],'languagecode'=>$row['languagecode'],'inheritedfrom'=>'');       
     }
    }
   }
  }
  
  $basepart=$this->basepartOfPart($partnumber);
  if($basepart)
  {// this part has a base - we need to deal with inheritance
   if($stmt=$db->conn->prepare('select * from part_description where partnumber=?'))
   {
    if($stmt->bind_param('s',$basepart))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      while($row = $db->result->fetch_assoc())
      {
       if(!array_key_exists($row['description'].$row['descriptioncode'].$row['languagecode'], $keyeddescriptions))
       {// this exact descriptive text, code and language were not alread contributed from a base part         
        $descriptions[]=array('id'=>$row['id'],'description'=>$row['description'],'descriptioncode'=>$row['descriptioncode'],'sequence'=>$row['sequence'],'languagecode'=>$row['languagecode'],'inheritedfrom'=>$basepart);
       }
      }
     }
    }
   }      
  }
   
  $db->close();
  return $descriptions;
 }

 
 // get PIES item-segment elements that are not in the core "part" table
 function getPartPIESitemElements($partnumber)
 {
  $db = new mysql; $db->connect(); $elements=array();
  if($stmt=$db->conn->prepare('select * from part_PIESitem where partnumber=?'))
  {
   if($stmt->bind_param('s',$partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $elements[]=array('id'=>$row['id'],'ReferenceFieldNumber'=>$row['ReferenceFieldNumber'],'value'=>$row['value']);
     }
    }
   }
  }
  $db->close();  
  return $elements;
 }
 
 
 
 
 
 
 
 
 
 function addPartDescription($partnumber,$description,$descriptioncode,$sequence,$languagecode)
 {
  $id=false;
  $db=new mysql; $db->connect();
  
  if($stmt=$db->conn->prepare('insert into part_description (id,partnumber,description,descriptioncode,sequence,languagecode) values(null,?,?,?,?,?)'))
  {
   if($stmt->bind_param('sssis',$partnumber,$description,$descriptioncode,$sequence,$languagecode))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    }//else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
   }//else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  }//else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
  return $id;
 }

 function getPartDescriptionByID($descriptionid)
 {
  $description=false; 
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from part_description where id=?'))
  {
   if($stmt->bind_param('d',$descriptionid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $description=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'description'=>$row['description'],'descriptioncode'=>$row['descriptioncode'],'sequence'=>$row['sequence'],'languagecode'=>$row['languagecode']);
     }
    }
   }
  }
  $db->close();
  return $description;
 }

 
 function deletePartDescriptionById($descriptionid)
 {
  $db=new mysql; $db->connect();
  $result=false;
  
  if($stmt=$db->conn->prepare('delete from part_description where id=?'))
  {
   if($stmt->bind_param('i',$descriptionid))
   {
    if($stmt->execute())
    {
     $result=true;
    }
   }
  }
  $db->close();
  return $result;   
 }

 function addPartDescriptionRecipe($partcategory,$parttypeid,$descriptioncode,$languagecode)
 {
  $id=false; $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into descriptionrecipe (id,partcategory,parttypeid,descriptioncode,languagecode) values(null,?,?,?,?)'))
  {
   $stmt->bind_param('iiss',$partcategory,$parttypeid,$descriptioncode,$languagecode);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }
  $db->close();
  return $id;     
 }
 
 function getPartDescriptionRecipe($id)
 {
  $recipe=false;
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from descriptionrecipe where id=?'))
  {
   if($stmt->bind_param('i',$id))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $recipe=array('id'=>$row['id'], 'partcategory'=>$row['partcategory'],'parttypeid'=>$row['parttypeid'],'descriptioncode'=>$row['descriptioncode'],'languagecode'=>$row['languagecode']);
     }
    }
   }
  }
  $db->close();
  return $recipe;
 }
 
 function getPartDescriptionRecipes()
 {
  $recipes=array(); 
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select descriptionrecipe.* from descriptionrecipe left join partcategory on descriptionrecipe.partcategory=partcategory.id order by partcategory.name,descriptionrecipe.parttypeid,descriptionrecipe.descriptioncode,descriptionrecipe.languagecode'))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $recipes[]=array('id'=>$row['id'], 'partcategory'=>$row['partcategory'],'parttypeid'=>$row['parttypeid'],'descriptioncode'=>$row['descriptioncode'],'languagecode'=>$row['languagecode']);
    }
   }
  }
  $db->close();
  return $recipes;
 }
 
 function getPartDescriptionRecipeBlocks($recipeid)
 {
  $blocks=array(); 
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from descriptionrecipeblock where recipeid=? order by sequence'))
  {
   if($stmt->bind_param('i',$recipeid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $blocks[]=array('id'=>$row['id'],'blocktype'=>$row['blocktype'],'blockparameters'=>$row['blockparameters'],'sequence'=>$row['sequence']);
     }
    }
   }
  }
  $db->close();
  return $blocks;
 }
 
 function addPartDescriptionRecipeBlock($recipeid,$sequence,$blocktype,$blockparameters)
 {
  $id=false; $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into descriptionrecipeblock (id,recipeid,sequence,blocktype,blockparameters) values(null,?,?,?,?)'))
  {
   $stmt->bind_param('iiss',$recipeid,$sequence,$blocktype,$blockparameters);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }
  $db->close();
  return $id;
 }
 
 function deletePartDescriptionRecipeBlock($recipeid,$blockid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from descriptionrecipeblock where id=? and recipeid=?'))
  {
   $stmt->bind_param('ii',$blockid,$recipeid);
   $stmt->execute();
  }
  $db->close();
 }
 
 function updatePartDescriptionRecipeBlock($id,$blockparameters)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update descriptionrecipeblock set blockparameters=? where id=?'))
  {
   $stmt->bind_param('si',$blockparameters,$id);
   $stmt->execute();
  }
  $db->close();
 }
 
 

 function getPartAttributes($partnumber)
 {
  $db = new mysql; $db->connect(); $keyedattributes=array(); $attributes=array();

  $basepart=$this->basepartOfPart($partnumber);
  if($basepart)
  {// this part has a base - we need to deal with inheritance
   // unique key for identifying attributes is PAID+userDefinedAttributeName+uom
   
   if($stmt=$db->conn->prepare('select id,PAID,userDefinedAttributeName,`value`,uom from part_attribute where partnumber=?'))
   {
    $stmt->bind_param('s',$basepart);
    $stmt->execute();
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $key=$row['PAID'].'|'.$row['userDefinedAttributeName'].'|'.$row['uom'];
     $keyedattributes[$key]=array('id'=>$row['id'],'PAID'=>$row['PAID'],'name'=>$row['userDefinedAttributeName'],'value'=>$row['value'],'uom'=>$row['uom'],'inheritedfrom'=>$basepart);
    }
   }   
  }
  
  if($stmt=$db->conn->prepare('select id,PAID,userDefinedAttributeName,`value`,uom from part_attribute where partnumber=?'))
  {
   $stmt->bind_param('s',$partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $key=$row['PAID'].'|'.$row['userDefinedAttributeName'].'|'.$row['uom'];
    $keyedattributes[$key]=array('id'=>$row['id'],'PAID'=>$row['PAID'],'name'=>$row['userDefinedAttributeName'],'value'=>$row['value'],'uom'=>$row['uom'],'inheritedfrom'=>'');
   }
  }
  
  foreach($keyedattributes as $keyedattribute)
  {
      $attributes[]=$keyedattribute;
  }
  
  $db->close();
  return $attributes;
 }


 function writePartAttribute($partnumber,$PAID,$attributename,$attributevalue,$uom)
 { // PAID of 0 implies a user-defned attribute 
  $id=false; $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into part_attribute (id,partnumber,PAID,userDefinedAttributeName,`value`,uom) values(null,?,?,?,?,?)'))
  {
   $stmt->bind_param('sisss',$partnumber,$PAID,$attributename,$attributevalue,$uom);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }
  $db->close();
  return $id;
 }

 function updatePartAttribute($partnumber,$PAID,$attributename,$attributevalue,$uom)
 { // PAID of 0 implies a user-defned attribute 
  $id=false; $db = new mysql; 
  $db->connect();

  if($stmt=$db->conn->prepare('select id from part_attribute where partnumber=? and PAID=? and userDefinedAttributeName=? and uom=?'))
  {
   if($stmt->bind_param('siss',$partnumber,$PAID,$attributename,$uom))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $id=$row['id'];
     } 
    }
   }
  }
  
  if($id)
  {
   if($stmt=$db->conn->prepare('update part_attribute set `value`=? where partnumber=? and PAID=? and userDefinedAttributeName=? and uom=?'))
   {
    $stmt->bind_param('ssiss',$attributevalue,$partnumber,$PAID,$attributename,$uom);
    $stmt->execute();
   }
  }

  $db->close();
  return $id;
 }

 function deletePartAttribute($attributeid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from part_attribute where id=?'))
  {
   $stmt->bind_param('i',$attributeid);
   $stmt->execute();
  } // else{print_r($db->conn->error);}
  $db->close();
 }

 function deletePartAttributesByPartnumber($partnumber)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from part_attribute where partnumber=?'))
  {
   $stmt->bind_param('s', $partnumber);
   $stmt->execute();
  }
  $db->close();
 }
 
 function deletePartAttributesByPartnumberPAIDuom($partnumber,$PAID,$userDefinedAttributeName,$uom)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from part_attribute where partnumber=? and PAID=? and userDefinedAttributeName=? and uom=?'))
  {
   $stmt->bind_param('siss', $partnumber,$PAID,$userDefinedAttributeName,$uom);
   $stmt->execute();
  }
  $db->close();
 }

 function getPartEXPIs($partnumber)
 {
  $db = new mysql; $db->connect(); $keyedexpis=array(); $expis=array();

  $basepart=$this->basepartOfPart($partnumber);
  if($basepart)
  {// this part has a base - we need to deal with inheritance
   // unique key for identifying attributes is EXPIcode+language+
   if($stmt=$db->conn->prepare('select id,EXPIcode,EXPIvalue,languagecode from part_expi where partnumber=?'))
   {
    $stmt->bind_param('s',$basepart);
    $stmt->execute();
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $key=$row['EXPIcode'].'|'.$row['languagecode'];
     $keyedexpis[$key]=array('id'=>$row['id'],'EXPIcode'=>$row['EXPIcode'],'EXPIvalue'=>$row['EXPIvalue'],'languagecode'=>$row['languagecode'],'inheritedfrom'=>$basepart);
    }
   }   
  }
  
  if($stmt=$db->conn->prepare('select id,EXPIcode,EXPIvalue,languagecode from part_expi where partnumber=?'))
  {
   $stmt->bind_param('s',$partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
     $key=$row['EXPIcode'].'|'.$row['languagecode'];
     $keyedexpis[$key]=array('id'=>$row['id'],'EXPIcode'=>$row['EXPIcode'],'EXPIvalue'=>$row['EXPIvalue'],'languagecode'=>$row['languagecode'],'inheritedfrom'=>'');
   }
  }
  
  foreach($keyedexpis as $keyedexpi)
  {
      $expis[]=$keyedexpi;
  }
  
  $db->close();
  return $expis;
 }

 function partEXPIvalue($partnumber,$EXPIcode,$languagecode,$respectinheritance)
 {
  $db = new mysql; $db->connect(); $value=false;

  $basepart=$this->basepartOfPart($partnumber);
  if($basepart && $respectinheritance)
  {// this part has a base - we need to deal with inheritance
   // unique key for identifying attributes is EXPIcode+language+
   if($stmt=$db->conn->prepare('select EXPIvalue from part_expi where partnumber=? and EXPIcode=? and languagecode=?'))
   {
    $stmt->bind_param('sss',$basepart,$EXPIcode,$languagecode);
    $stmt->execute();
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$value=$row['EXPIvalue'];}
   }
  }
  
  if($stmt=$db->conn->prepare('select EXPIvalue from part_expi where partnumber=? and EXPIcode=? and languagecode=?'))
  {
   $stmt->bind_param('sss',$partnumber,$EXPIcode,$languagecode);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc()){$value=$row['EXPIvalue'];}
  }
  
  $db->close();
  return $value;
 }
 
 
 function updatePartEXPI($partnumber,$EXPIcode,$languagecode,$EXPIvalue)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update part_expi set EXPIvalue=? where partnumber=? and EXPIcode=? and languagecode=?'))
  {
   $stmt->bind_param('ssss',$EXPIvalue,$partnumber,$EXPIcode,$languagecode);
   $stmt->execute();
  }
  $db->close();
 }
 
 
 function writePartEXPI($partnumber,$EXPIcode,$EXPIvalue,$languagecode)
 {
  $id=false; $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into part_expi (id,partnumber,EXPIcode,EXPIvalue,languagecode) values(null,?,?,?,?)'))
  {
   $stmt->bind_param('ssss',$partnumber,$EXPIcode,$EXPIvalue,$languagecode);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }
  $db->close();
  return $id;
 }
 
 function deletetEXPIsByPartnumber($partnumber)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from part_expi where partnumber=?'))
  {
   $stmt->bind_param('s',$partnumber);
   $stmt->execute();
  }
  $db->close();
 }

 function deletePartEXPIbyId($id)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from part_expi where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
  }
  $db->close();
 }

 function getPartEXPIbyId($id)
 {
  $db = new mysql; $db->connect(); $expi=false;
  if($stmt=$db->conn->prepare('select * from part_expi where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $expi=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'EXPIcode'=>$row['EXPIcode'],'EXPIvalue'=>$row['EXPIvalue'],'languagecode'=>$row['languagecode']);
   }
  }
  return $expi;
  $db->close();
 }

 
 
 function createPartcategory($name,$partcategory)
 {
  $db=new mysql; $db->connect();
  $success=false; $brandid=''; $subbrandID=''; $mfrlabel=''; $logouri=''; $marketcopy=''; $fab=''; $warranty='';
  if(!$this->validPartcategoryid($partcategory) && !$this->existingPartcategoryName($name))
  {
   if($partcategory=='')
   {
    if($stmt=$db->conn->prepare('insert into partcategory (id,`name`,brandID,subbrandID,mfrlabel,logouri,marketcopy,fab,warranty) values(null,?,?,?,?,?,?,?,?)'))
    {
     if($stmt->bind_param('ssssssss', $name, $brandid, $subbrandID, $mfrlabel, $logouri,$marketcopy,$fab,$warranty))
     {
      $success=$stmt->execute();
     }  else{echo 'problem with bind';}
    }  else{echo 'problem with prepare';}
   }
   else
   {
    if($stmt=$db->conn->prepare('insert into partcategory (id,`name`,brandID,subbrandID,mfrlabel,logouri) values(?,?,?,?,?,?,?,?,?)'))
    {
     if($stmt->bind_param('issssssss', $partcategory, $name, $brandid, $subbrandID, $mfrlabel, $logouri,$marketcopy,$fab,$warranty))
     {
      $success=$stmt->execute();
     }  else{echo 'problem with bind';}
    }  else{echo 'problem with prepare';}
   }
  }  else{echo 'already exists';}
  $db->close();
  return $success;
 }

 function deletePartcategory($partcategory)
 {
  $db=new mysql; $db->connect();
  $success=false;
  if(!$this->countPartsByPartcategory($partcategory))
  {
    if($stmt=$db->conn->prepare('delete from partcategory where id=?'))
    {
     if($stmt->bind_param('i', $partcategory))
     {
      $success=$stmt->execute();
     }  //else{echo 'problem with bind';}
    }  //else{echo 'problem with prepare';}
  }  //else{echo 'already exists';}
  $db->close();
  return $success;
 }

 function updatePartcategoryName($partcategory,$name)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update partcategory set `name`=? where id=?'))
  {
   $stmt->bind_param('si', $name,$partcategory);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }
 
 function updatePartcategoryBrandID($partcategory,$brandID)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update partcategory set `brandID`=? where id=?'))
  {
   $stmt->bind_param('si', $brandID,$partcategory);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }

 function updatePartcategorySubbrandID($partcategory,$subbrandID)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update partcategory set `subbrandID`=? where id=?'))
  {
   $stmt->bind_param('si', $subbrandID,$partcategory);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }

 function updatePartcategoryMfrlabel($partcategory,$mfrlabel)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update partcategory set `mfrlabel`=? where id=?'))
  {
   $stmt->bind_param('si', $mfrlabel,$partcategory);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }

 function updatePartcategoryMarketcopy($partcategory,$marketcopy)
 {
  $db = new mysql; $db->connect();
  $marketcopyencoded= base64_encode($marketcopy);
  if($stmt=$db->conn->prepare('update partcategory set `marketcopy`=? where id=?'))
  {
   $stmt->bind_param('si', $marketcopyencoded,$partcategory);
   $stmt->execute();
  }
  $db->close();
 }

 function updatePartcategoryFAB($partcategory,$fab)
 {
  $db = new mysql; $db->connect();
  $fabencoded= base64_encode($fab);
  if($stmt=$db->conn->prepare('update partcategory set `fab`=? where id=?'))
  {
   $stmt->bind_param('si', $fabencoded,$partcategory);
   $stmt->execute();
  }
  $db->close();
 }

 function updatePartcategoryWarranty($partcategory,$warranty)
 {
  $db = new mysql; $db->connect();
  $warrantyencoded= base64_encode($warranty);
  if($stmt=$db->conn->prepare('update partcategory set `warranty`=? where id=?'))
  {
   $stmt->bind_param('si', $warrantyencoded,$partcategory);
   $stmt->execute();
  }
  $db->close();
 }
 
 function getPartCategories()
 {
  $categories=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select id,`name`,logouri from partcategory order by name'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $categories[]=array('id'=>$row['id'],'name'=>$row['name'],'logouri'=>$row['logouri']);
   }
  }
  $db->close();
  return $categories;
 }

 function getPartCategory($id)
 {
  $category=false;
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from partcategory where id=?'))
  {
   if($stmt->bind_param('i', $id))
   {
    $stmt->execute();
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $category=array('id'=>$row['id'],'name'=>$row['name'],'brandID'=>$row['brandID'],'subbrandID'=>$row['subbrandID'],'mfrlabel'=>$row['mfrlabel'],'logouri'=>$row['logouri'],'marketcopy'=>base64_decode($row['marketcopy']),'fab'=>base64_decode($row['fab']),'warranty'=>base64_decode($row['warranty']));
    }
   }
  }
  $db->close();
  return $category;
 }

 function countPartsByPartcategory($partcategory)
 {
  $db=new mysql; $db->connect();
  $count=0;
  if($stmt=$db->conn->prepare('select count(*) as partcount from part where partcategory=?'))
  {
   if($stmt->bind_param('i', $partcategory))
   {
    if($result=$stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $count=intval($row['partcount']);
     }
    } // else{echo 'problem with execute';}
   } // else{echo 'problem with bind';}
  } // else{echo 'problem with prepare';}
  $db->close();
  return $count;
 }



 
 function partCategoryName($partcategoryid)
 {
  $name='('.$partcategoryid.') Not Found';
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('select name from partcategory where id=?'))
  {
   $stmt->bind_param('i', $partcategoryid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $name=$row['name'];
   }
  }
  $db->close();
  return $name;
 }

 function validPartcategoryid($partcategoryid)
 {
  $returnval=false;
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select `name` from partcategory where id=?'))
  {
   $stmt->bind_param('i', $partcategoryid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $returnval=true;
   }
  }
  $db->close();
  return $returnval;
 }

 function existingPartcategoryName($name)
 {
  $returnval=false;
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select id from partcategory where `name`=?'))
  {
   $stmt->bind_param('s', $name);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $returnval=true;
   }
  }
  $db->close();
  return $returnval;
 }

 function addPartcategoryToDeliverygroup($deliverygroupid, $partcategoryid)
 {
  $db = new mysql; $db->connect(); $id=false;
  if($stmt=$db->conn->prepare('insert into deliverygroup_partcategory values(null,?,?)'))
  {
   if($stmt->bind_param('ii', $deliverygroupid, $partcategoryid))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    }
   }
  }
  $db->close();
  return $id;
 }

 function createDeliverygroup($name)
 {
  $db = new mysql; $db->connect(); $id=false;
  if($stmt=$db->conn->prepare('insert into deliverygroup values(null,?)'))
  {
   if($stmt->bind_param('s', $name))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    }
   }
  }
  $db->close();
  return $id;
 }

 
 
 function removePartcategoryFromDeliverygroup($deliverygroupid, $partcategoryid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from deliverygroup_partcategory where deliverygroupid=? and partcategory=?'))
  {
   if($stmt->bind_param('ii', $deliverygroupid, $partcategoryid))
   {
    $stmt->execute();
   }
  }
  $db->close();
 }


 
 
 
 function getBackgroundjobs($jobtype,$status)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  $jobs=array();
  if($stmt=$db->conn->prepare('select * from backgroundjob where jobtype like ? and status like ? order by datetimecreated'))
  {
   if($stmt->bind_param('ss', $jobtype,$status))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      if($row['status']=='hidden'){continue;}
      $jobs[]=array('id'=>$row['id'],'jobtype'=>$row['jobtype'],'status'=>$row['status'],'userid'=>$row['userid'],'inputfile'=>$row['inputfile'],'outputfile'=>$row['outputfile'],'parameters'=>$row['parameters'],'datetimecreated'=>$row['datetimecreated'],'datetimetostart'=>$row['datetimetostart'],'datetimestarted'=>$row['datetimestarted'],'datetimeended'=>$row['datetimeended'],'percentage'=>$row['percentage'],'token'=>$row['token'],'clientfilename'=>$row['clientfilename']);
     }
    }// else {echo 'problem with execute';}
   }// else{echo 'problem with bind';}
  }// else{echo 'problem with prepare';}
  $db->close();
  return $jobs;
 }

 function getBackgroundjob($id)
 {
  $db = new mysql; $db->connect(); 
  $job=false;
  if($stmt=$db->conn->prepare('select * from backgroundjob where id=?'))
  {
   if($stmt->bind_param('i', $id))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $job=array('id'=>$row['id'],'jobtype'=>$row['jobtype'],'status'=>$row['status'],'userid'=>$row['userid'],'inputfile'=>$row['inputfile'],'outputfile'=>$row['outputfile'],'parameters'=>$row['parameters'],'datetimecreated'=>$row['datetimecreated'],'datetimetostart'=>$row['datetimetostart'],'datetimestarted'=>$row['datetimestarted'],'datetimeended'=>$row['datetimeended'],'percentage'=>$row['percentage'],'contenttype'=>$row['contenttype'],'clientfilename'=>$row['clientfilename'],'token'=>$row['token']);
     }
    }// else {echo 'problem with execute';}
   }// else{echo 'problem with bind';}
  }// else{echo 'problem with prepare';}
  $db->close();
  return $job;
 }

 function getBackgroundjobByToken($token)
 {
  $db = new mysql; $db->connect(); 
  $job=false;
  if($stmt=$db->conn->prepare('select * from backgroundjob where token=?'))
  {
   if($stmt->bind_param('s', $token))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $job=array('id'=>$row['id'],'jobtype'=>$row['jobtype'],'status'=>$row['status'],'userid'=>$row['userid'],'inputfile'=>$row['inputfile'],'outputfile'=>$row['outputfile'],'parameters'=>$row['parameters'],'datetimecreated'=>$row['datetimecreated'],'datetimetostart'=>$row['datetimetostart'],'datetimestarted'=>$row['datetimestarted'],'datetimeended'=>$row['datetimeended'],'percentage'=>$row['percentage'],'contenttype'=>$row['contenttype'],'clientfilename'=>$row['clientfilename'],'token'=>$row['token']);
     }
    }// else {echo 'problem with execute';}
   }// else{echo 'problem with bind';}
  }// else{echo 'problem with prepare';}
  $db->close();
  return $job;
 }

 
 function deleteBackgroundjob($id)
 {
  $db = new mysql; $db->connect(); 
 
  if($stmt=$db->conn->prepare('select outputfile from backgroundjob where id=?'))
  {
   if($stmt->bind_param('i', $id))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      if(trim($row['outputfile'])!='' && file_exists($row['outputfile']))
      {
          unlink($row['outputfile']);
      }
     }
    }    
   }
  }
  
  if($stmt=$db->conn->prepare('delete from backgroundjob where id=?'))
  {
   if($stmt->bind_param('i', $id))
   {
    $stmt->execute();
   }// else{echo 'problem with bind';}
  }// else{echo 'problem with prepare';}
  $db->close();
 }
 
 
 
 function getBackgroundjob_log($jobid)
 {
  $db = new mysql; $db->connect(); $events=array();
  if($stmt=$db->conn->prepare('select * from backgroundjob_log where jobid=? order by timestamp'))
  {
   $stmt->bind_param('i', $jobid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $events[]=array('id'=>$row['id'],'jobid'=>$row['jobid'],'eventtext'=>$row['eventtext'],'timestamp'=>$row['timestamp']);
   }
  }
  $db->close();
  return $events;
 }


 function updateBackgroundjobStatus($jobid,$status,$percentage)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update backgroundjob set status=?,percentage=? where id=?'))
  {
   if($stmt->bind_param('sii', $status,$percentage,$jobid))
   {
    $stmt->execute();
   }
  }
  $db->close();
 }

 function updateBackgroundjobRunning($jobid,$datetimestarted)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare("update backgroundjob set status='running', datetimestarted=? where id=?"))
  {
   if($stmt->bind_param('si', $datetimestarted,$jobid))
   {
    $stmt->execute();
   }
  }
  $db->close();
 }

 function updateBackgroundjobDone($jobid,$status,$datetimeended)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update backgroundjob set status=?,percentage=100,datetimeended=? where id=?'))
  {
   if($stmt->bind_param('ssi', $status,$datetimeended,$jobid))
   {
    $stmt->execute();
   }
  }
  $db->close();
 }

 function updateBackgroundjobClientfilename($jobid,$clientfilename)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update backgroundjob set clientfilename=? where id=?'))
  {
   if($stmt->bind_param('si', $clientfilename,$jobid))
   {
    $stmt->execute();
   }
  }
  $db->close();
 }
 
 function updateBackgroundjobOutputfile($jobid,$outputfile)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update backgroundjob set outputfile=? where id=?'))
  {
   if($stmt->bind_param('si', $outputfile,$jobid))
   {
    $stmt->execute();
   }
  }
  $db->close();
 }

 
 function logBackgroundjobEvent($jobid,$text)
 {
  $db = new mysql; $db->connect();

  if($stmt=$db->conn->prepare('insert into backgroundjob_log (id,jobid,eventtext,timestamp) values(null,?,?,now())'))
  {
   if($stmt->bind_param('is',$jobid,$text))
   {
    $stmt->execute();
   }
  }
  $db->close();
 }

 function hideBackgroundjob($jobid)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  $status='hidden'; $currenttask='hidden by user';
  if($stmt=$db->conn->prepare('update backgroundjob set status=? where id=?'))
  {
   if($stmt->bind_param('si', $status,$jobid))
   {
    $stmt->execute();

    if($stmt=$db->conn->prepare('insert into backgroundjob_log (id,jobid,eventtext,timestamp) values(null,?,?,now())'))
    {
     if($stmt->bind_param('is',$jobid,$currenttask))
     {
      $stmt->execute();
     }
    }
   }
  }
  $db->close();
 }


 function addLock($type,$data)
 {
  $db = new mysql; $db->connect(); $lockid=false;
  if($stmt=$db->conn->prepare('insert into locks values(null,?,?,now())'))
  {
   $stmt->bind_param('ss',$type,$data);
   $stmt->execute();
   $lockid=$db->conn->insert_id;
  }
  $db->close();
  return $lockid;
 }

 function removeLockById($lockid)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from locks where id=?'))
  {
   $stmt->bind_param('i',$lockid);
   $stmt->execute();
  }
  $db->close();
 }

 function getLocksByType($type=false)
 {
  $db = new mysql; $db->connect(); $locks=array();
  $sql='select * from locks where type=? order by id';
  if($type===false){$sql='select * from locks order by id';}
  if($stmt=$db->conn->prepare($sql))
  {
   if($type!==false){$stmt->bind_param('s', $type);}
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $locks[]=array('id'=>$row['id'],'type'=>$row['type'],'data'=>$row['data'],'createdDatetime'=>$row['createdDatetime']);
   }
  }
  $db->close();
  return $locks;
 }

 function getLockById($lockid)
 {
  $db = new mysql; $db->connect(); $lock=false;
  if($stmt=$db->conn->prepare('select * from locks where id=?'))
  {
   $stmt->bind_param('i', $lockid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $lock=array('id'=>$row['id'],'type'=>$row['type'],'data'=>$row['data'],'createdDatetime'=>$row['createdDatetime']);
   }
  }
  $db->close();
  return $lock;
 }
 
 
 function createBackgroundjob($jobtype,$status,$userid,$inputfile,$outputfile,$parameters,$datetimetostart,$contenttype,$clientfilename)
 {
  $db = new mysql; $db->connect(); $jobid=false; $token=$this->newoid();
  if($stmt=$db->conn->prepare('insert into backgroundjob (id,jobtype,status,userid,inputfile,outputfile,parameters,datetimecreated,datetimetostart,datetimestarted,datetimeended,percentage,contenttype,clientfilename,token) values(null,?,?,?,?,?,?,now(),?,0,0,0,?,?,?)'))
  {
   $stmt->bind_param('ssisssssss',$jobtype,$status,$userid,$inputfile,$outputfile,$parameters,$datetimetostart,$contenttype,$clientfilename,$token);
   $stmt->execute();
   $jobid=$db->conn->insert_id;
  }//else{print_r($db->conn->error);}

  $currenttask='job created';
  if($stmt=$db->conn->prepare('insert into backgroundjob_log (id,jobid,eventtext,timestamp) values(null,?,?,now())'))
  {
   $stmt->bind_param('is',$jobid,$currenttask);
   $stmt->execute();
  }//else{print_r($db->conn->error);}
  $db->close();
  return $token;
 }

 function newoid()
 {
  $oid= $this->uuidv4();
  /*   
  $charset=array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
  $oid='';
  for($i=0;$i<10;$i++)
  {
   $oid.=$charset[random_int(0,61)];
  }
  */
  return $oid;
 }


 function validPart($partnumber)
 {
  $db=new mysql;  $db->connect(); $exists=false;
  if(trim($partnumber)==''){return false;}
  
  if($stmt=$db->conn->prepare('select oid from part where partnumber=?'))
  {
   if($stmt->bind_param('s', $partnumber))
   {
    if($result=$stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $exists=true;
     }
    } // else{echo 'problem with execute';}
   } // else{echo 'problem with bind';}
  } // else{echo 'problem with prepare';}
  $db->close();
  return $exists;
 }

 function createPart($partnumber,$partcategory,$parttypeid)
 {
  $db=new mysql; $db->connect();
  $success=false;
  if(!$this->validPart($partnumber))
  {
   $replacedby=''; $lifecyclestatus='0'; $oid=$this->newoid();
   if($stmt=$db->conn->prepare("insert into part (partnumber,partcategory,parttypeid,replacedby,lifecyclestatus,internalnotes,description,GTIN,UNSPC,createdDate,firststockedDate,discontinuedDate,obsoletedDate,supersededDate,availableDate,oid,basepart) values(?,?,?,?,?,'','','','',now(),'0000-00-00','0000-00-00','0000-00-00','0000-00-00','0000-00-00',?,'')"))
   {
    if($stmt->bind_param('siisss', $partnumber,$partcategory,$parttypeid,$replacedby,$lifecyclestatus,$oid))
    {
     $success=$stmt->execute();
    }// else{echo 'problem with bind';}
   }// else{echo 'problem with prepare';}
  }// else{echo 'already exists';}
  $db->close();
  return $success;
 }

 function addVCdbAttributeToApp($applicationid,$attributename,$attributevalue,$sequence,$cosmetic,$updateoid)
 {
  $db=new mysql; $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('insert into application_attribute (id,applicationid,`name`,`value`,`type`,sequence,cosmetic) values(null,?,?,?,?,?,?)'))
  {
   $attributetype='vcdb';
   if($stmt->bind_param('isssii', $applicationid,$attributename,$attributevalue,$attributetype,$sequence,$cosmetic))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
     if($updateoid){$this->updateAppOID($applicationid);}
    }
   }
  }
  $db->close();
  return $id;
 }

 function addNoteAttributeToApp($applicationid,$note,$sequence,$cosmetic,$updateoid)
 {
  $db=new mysql; $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('insert into application_attribute (id,applicationid,`name`,`value`,`type`,sequence,cosmetic) values(null,?,?,?,?,?,?)'))
  {
   $attributename='note'; $attributetype='note';
   if($stmt->bind_param('isssii', $applicationid,$attributename,$note,$attributetype,$sequence,$cosmetic))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
     if($updateoid){$this->updateAppOID($applicationid);}
    }
   }
  }
  $db->close();
  return $id;
 }

 function addQdbAttributeToApp($applicationid,$qdbid,$parmsstring,$sequence,$cosmetic,$updateoid)
 {
     /*
      * the "name" field in application_attribute will hold the numeric Qdb ID. the "value" 
      * field will hold parameter/uom pairs delimited by semicolon like this 3-parameter example
      * 4000,lbs;Bendix,;X7R,;
      * (The second and third parms in this examplare unitless)
      */
    
  $db=new mysql; $db->connect();
  $id=false;
  $attributetype='qdb';

  if($stmt=$db->conn->prepare('insert into application_attribute (id,applicationid,`name`,`value`,`type`,sequence,cosmetic) values(null,?,?,?,?,?,?)'))
  {
   if($stmt->bind_param('isssii', $applicationid,$qdbid,$parmsstring,$attributetype,$sequence,$cosmetic))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
     if($updateoid){$this->updateAppOID($applicationid);}
    }
   }
  }
  $db->close();
  return $id;
 }


 function removeAllAppAttributes($applicationid,$updateoid)
 {
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from application_attribute where applicationid=?'))
  {
   $stmt->bind_param('i', $applicationid);
   $stmt->execute();
   if($updateoid){$this->updateAppOID($applicationid);}
  }
  $db->close();
 }

 function setAppStatus($applicationid,$status)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set status=? where id=?'))
  {
   $stmt->bind_param('ii',$status,$applicationid);
   $stmt->execute();
  }
  $db->close();
 }

 function setAppPosition($applicationid,$positionid,$updateoid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set positionid=? where id=?'))
  {
   $stmt->bind_param('ii',$positionid,$applicationid);
   $stmt->execute();
   if($updateoid){$this->updateAppOID($applicationid);}
  }
  $db->close();
 }

 function setAppParttype($applicationid,$parttypeid,$updateoid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set parttypeid=? where id=?'))
  {
   $stmt->bind_param('ii',$parttypeid,$applicationid);
   $stmt->execute();
   if($updateoid){$this->updateAppOID($applicationid);}
  }
  $db->close();
 }

 function setAppQuantity($applicationid,$quantityperapp,$updateoid)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set quantityperapp=? where id=?'))
  {
   $stmt->bind_param('ii',$quantityperapp,$applicationid);
   $stmt->execute();
   if($updateoid){$this->updateAppOID($applicationid);}
  }
  $db->close();
 }

 function toggleAppCosmetic($appid)
 {
  $db = new mysql; $db->connect(); $success=false;
  if($stmt=$db->conn->prepare('update application set cosmetic=cosmetic XOR 1 where id=?'))
  {
   if($stmt->bind_param('i', $appid))
   {
    $success=$stmt->execute();
   }
  }
  $db->close();
  return $success;
 }

 function setAppInternalnotes($applicationid,$internalnotes)
 {
  $db = new mysql; 
  //$db->dbname='pim';
  $db->connect();
  if($stmt=$db->conn->prepare('update application set internalnotes=? where id=?'))
  {
   $encodednotes=base64_encode($internalnotes);
   $stmt->bind_param('si', $encodednotes,$applicationid);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }




 function conformApp($appid,$refappid,$copyfitment,$copyposition,$copyparttype,$copycategory)
 {
  // over-write app's fitment, position and parttype with that of the refapp
  // used for drag/drop in the app grid interface
  $refapp=$this->getApp($refappid);
  $app=$this->getApp($appid);
  $OID=$app['oid'];
  $neednewOID=false; $historytext='conformApp using reference app:'.$refappid;
  if($copyfitment && $refapp['attributeshash']!=$refapp['attributeshash'])
  {
   $neednewOID=true;
   $this->removeAllAppAttributes($appid,false);
   $historytext.='; All fitment attributes removed';
   foreach($refapp['attributes'] as $attribute)
   { // set attributes for "to" app

    switch($attribute['type'])
    {
     case 'vcdb':
      $this->addVCdbAttributeToApp($appid,$attribute['name'],$attribute['value'],$attribute['sequence'],$attribute['cosmetic'],false);
      $historytext.='; Added VCdb '.$attribute['name'].':'.$attribute['value'].';sequence:'.$attribute['sequence'].';cosmetic:'.$attribute['cosmetic'];
      break;
     case 'note':
      $this->addNoteAttributeToApp($appid,$attribute['value'],$attribute['sequence'],$attribute['cosmetic'],false);
      $historytext.='; Added Note:'.$attribute['value'].';sequence:'.$attribute['sequence'].';cosmetic:'.$attribute['cosmetic'];
      break;
     case 'qdb':
//      $this->addQdbAttributeToApp($appid,...
      break;
     default: break;
    }
   }
  }

  if($copyposition && $refapp['positionid']!=$app['positionid']){$this->setAppPosition($appid,$refapp['positionid'],false); $neednewOID=true; $historytext.='; changed position from:'.$app['positionid'].' to '.$refapp['positionid'];}
  if($copyparttype && $refapp['parttypeid']!=$app['parttypeid']){$this->setAppParttype($appid,$refapp['parttypeid'],false); $neednewOID=true; $historytext.='; changed parttype from:'.$app['parttypeid'].' to '.$refapp['parttypeid'];}
  if($neednewOID){$OID=$this->updateAppOID($appid);}
  $userid=0;
  $this->logAppEvent($appid,$userid,$historytext,$OID);
 }


 function applyAppAttributes($appid,$attributes,$updateoid)
 {
  $this->removeAllAppAttributes($appid,false);
  foreach($attributes as $attribute)
  {
   switch($attribute['type'])
   {
    case 'vcdb':
     $this->addVCdbAttributeToApp($appid,$attribute['name'],$attribute['value'],$attribute['sequence'],$attribute['cosmetic'],$updateoid);
     break;
    case 'note':
     $this->addNoteAttributeToApp($appid,$attribute['value'],$attribute['sequence'],$attribute['cosmetic'],$updateoid);
     break;
    case 'qdb':
     $this->addQdbAttributeToApp($appid, $attribute['name'], $attribute['value'], $attribute['sequence'], $attribute['cosmetic'],$updateoid);
     break;
    default: break;
   }
  }
  if($updateoid){$this->updateAppOID($appid);}
 }

 function getAllAppNoteAttributes()
 {
  $db=new mysql; $db->connect(); $attributes=array();
  if($stmt=$db->conn->prepare("select * from application_attribute where `type`='note' order by `value`"))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $attributes[]=array('id'=>$row['id'],'name'=>$row['name'],'value'=>$row['value'],'type'=>$row['type'],'sequence'=>$row['sequence'],'cosmetic'=>$row['cosmetic']);
    }
   }
  }
  $db->close();
  return $attributes;
 }

 
 function getAppNoteAttributeCounts()
 {
  $db=new mysql; $db->connect(); $attributes=array();
  if($stmt=$db->conn->prepare("select `value`, count(*) as notecount, max(id) as lastid from application_attribute where `type`='note' group by `value` order by notecount desc"))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $notes[]=array('note'=>$row['value'],'count'=>$row['notecount'],'lastid'=>$row['lastid']);
    }
   }
  }
  $db->close();
  return $notes;
 }

 function getAppAttributesByValue($type,$name,$value)
 {
  $db=new mysql; $db->connect(); $attributes=array();
//  if($stmt=$db->conn->prepare("select * from application_attribute where `type`=? and `name`=? and `value`=?")) // changed value search from = to like on 2/11/2025 as part of a new housekeeping effort
  if($stmt=$db->conn->prepare("select * from application_attribute where `type`=? and `name`=? and `value` like ?"))
  {  
   if($stmt->bind_param('sss', $type,$name,$value))
   {   
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $attributes[]=array('id'=>$row['id'],'applicationid'=>$row['applicationid'],'name'=>$row['name'],'value'=>$row['value'],'type'=>$row['type'],'sequence'=>$row['sequence'],'cosmetic'=>$row['cosmetic']);
     }
    }
   }
  }
  $db->close();
  return $attributes;
 }
 
 function updateApplicationAttribute($id,$type,$name,$value)
 {
  $db=new mysql; $db->connect();
  $success=false;
  if($stmt=$db->conn->prepare("update application_attribute set `type`=?,`name`=?,`value`=? where id=?"))
  {
   if($stmt->bind_param('sssi',$type,$name,$value,$id))
   {
    $success=$stmt->execute();
   }
  }
  $db->close();
  return $success;
 }
 
 function cloneAppsToNewBasevehicle($basevehicleid,$appids)
 {
     // duplicate every app in the array of app id's to the given new basevehilce
  $newappids=array();
  foreach($appids as $appid)
  {
   $existingapp=$this->getApp($appid);
   $newappids[]=$this->newApp($basevehicleid, $existingapp['parttypeid'], $existingapp['positionid'], $existingapp['quantityperapp'], $existingapp['partnumber'], $existingapp['cosmetic'], $existingapp['attributes'],'');
  }
  return $newappids;
 }
 
 function cloneAppsToPart($partnumber,$appids)
 {
     // duplicate every app in the array of app id's to the given new partnumber
     // use the destination part's parttypeid in the new apps - this is so that you could pull off a trick like
     // duplicating all the apps from a specific drum to a shoe
  $newappids=array();
  if($part=$this->getPart($partnumber))
  {
   foreach($appids as $appid)
   {
    $existingapp=$this->getApp($appid);
    $newappids[]=$this->newApp($existingapp['basevehicleid'], $part['parttypeid'], $existingapp['positionid'], $existingapp['quantityperapp'], $partnumber, $existingapp['cosmetic'], $existingapp['attributes'],'');
   }
  }
  return $newappids;
 }
 
 function newApp($basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber,$cosmetic,$attributes,$oid)
 {
  $db = new mysql; $db->connect();
  $applicationid=false;
  if($stmt=$db->conn->prepare('insert into application (id,oid,basevehicleid,makeid,equipmentid,parttypeid,positionid,quantityperapp,partnumber,status,cosmetic) values(null,?,?,0,0,?,?,?,?,0,?)'))
  {
   if($oid==''){$oid=$this->newoid();}
   $stmt->bind_param('siiiisi', $oid,$basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber,$cosmetic);
   $stmt->execute();
   $applicationid=$db->conn->insert_id;

   if(count($attributes))
   {
    $this->applyAppAttributes($applicationid,$attributes,false);
   }
  }
  $db->close();
  
  // delete app summary rec so it gets re-created on next request
  $this->deleteAppSummary($partnumber);
  return $applicationid;
 }

 function logAppEvent($applicationid,$userid,$description,$newoid)
 { // fff
  $db=new mysql; $db->connect();
  
  $app=$this->getApp($applicationid);
  $parteventdescription='app '.$applicationid.': '.$description;
  $partnumber=$app['partnumber'];
  $partoid='';
  
  if($stmt=$db->conn->prepare('insert into application_history (id,applicationid,eventdatetime,userid,description,new_oid) values(null,?,now(),?,?,?)'))
  {
   $stmt->bind_param('iiss', $applicationid,$userid,$description,$newoid);
   $stmt->execute();
  }
  
  if($stmt=$db->conn->prepare('insert into part_history (id,partnumber,eventdatetime,userid,description,new_oid) values(null,?,now(),?,?,?)'))
  {
   $stmt->bind_param('siss', $partnumber, $userid, $parteventdescription,$partoid);
   $stmt->execute();
  }
  $db->close();
 }

 
 
 function getAppEvents($applicationid,$limit)
 {
  $db=new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  $events=array();
  if($stmt=$db->conn->prepare('select * from application_history where applicationid=? order by eventdatetime desc limit ?'))
  {
   $stmt->bind_param('ii', $applicationid,$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $events[]=array('id'=>$row['id'],'applicationid'=>$row['applicationid'],'eventdatetime'=>$row['eventdatetime'],'userid'=>$row['userid'],'description'=>$row['description'],'new_oid'=>$row['new_oid']);
   }
  }
  $db->close();
  return $events;
 }

 function ageSecondsOfApp($applicationid)
 {// return the number of seconds since a given app was touched (based on records in application_history)
  $db=new mysql; $db->connect(); $latest=false;
  if($stmt=$db->conn->prepare('select eventdatetime from application_history where applicationid=? order by eventdatetime desc limit 1'))
  {
   $stmt->bind_param('i', $applicationid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $latest=$row['eventdatetime'];
   }
  }
  $db->close();
 
  if($latest===false)
  {// no history records found for app
   return -1;      
  }
  else
  {
   list($date, $time) = explode(' ', $latest);
   list($year, $month, $day) = explode('-', $date);
   list($hour, $minute, $second) = explode(':', $time);
   return mktime($hour, $minute, $second, $month, $day, $year);
  }
 }



 
 function logPartEvent($partnumber,$userid,$description,$newoid)
 {
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into part_history (id,partnumber,eventdatetime,userid,description,new_oid) values(null,?,now(),?,?,?)'))
  {
   $stmt->bind_param('siss', $partnumber,$userid,$description,$newoid);
   $stmt->execute();
  } // else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }

 
 function logVehicleEvent($basevehicleid,$userid,$description)
 {
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('insert into vehicle_history (id,basevehicleid,userid,eventdatetime,description) values(null,?,?,now(),?)'))
  {
   $stmt->bind_param('iis', $basevehicleid,$userid,$description);
   $stmt->execute();
  }
  $db->close();
 }
 
 
 

 function createAppFromACESsnippet($xml,$partcategory=false)
 {
  $db=new mysql;  $db->connect();
  $app_count=0;

  $partcategoryid=0; if($partcategory){$partcategoryid=intval($partcategory);}
  
  foreach($xml->App as $app)
  {
   if($stmt=$db->conn->prepare('insert into application (id,oid,basevehicleid,makeid,equipmentid,parttypeid,positionid,quantityperapp,partnumber,status,cosmetic) values(null,?,?,0,0,?,?,?,?,0,0)'))
   {
    $oid=$this->newoid();
    $stmt->bind_param('siiiis', $oid,$basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber);
    $cosmetic=0; $sequence=10; $basevehicleid=intval($app->BaseVehicle['id']); $quantityperapp=intval($app->Qty); $parttypeid=intval($app->PartType['id']); $positionid=intval($app->Position['id']); $partnumber=(string)$app->Part;
    $stmt->execute(); // insert the application record
    $applicationid=$db->conn->insert_id;

    // insert attribute records
    $attributes=array();
    if($id=$app->SubModel['id']){$attributes[]=array('type'=>'vcdb','name'=>'SubModel', 'value'=>intval($id));}
    if($id=$app->MfrBodyCode['id']){$attributes[]=array('type'=>'vcdb','name'=>'MfrBodyCode', 'value'=>intval($id));}
    if($id=$app->BodyNumDoors['id']){$attributes[]=array('type'=>'vcdb','name'=>'BodyNumDoors','value'=>intval($id));}
    if($id=$app->BodyType['id']){$attributes[]=array('type'=>'vcdb','name'=>'BodyType','value'=>intval($id));}
    if($id=$app->DriveType['id']){$attributes[]=array('type'=>'vcdb','name'=>'DriveType','value'=>intval($id));}
    if($id=$app->EngineBase['id']){$attributes[]=array('type'=>'vcdb','name'=>'EngineBase','value'=>intval($id));}
    if($id=$app->EngineDesignation['id']){$attributes[]=array('type'=>'vcdb','name'=>'EngineDesignation','value'=>intval($id));}
    if($id=$app->EngineVIN['id']){$attributes[]=array('type'=>'vcdb','name'=>'EngineVIN','value'=>intval($id));}
    if($id=$app->EngineVersion['id']){$attributes[]=array('type'=>'vcdb','name'=>'EngineVersion','value'=>intval($id));}
    if($id=$app->EngineMfr['id']){$attributes[]=array('type'=>'vcdb','name'=>'EngineMfr','value'=>intval($id));}
    if($id=$app->PowerOutput['id']){$attributes[]=array('type'=>'vcdb','name'=>'PowerOutput','value'=>intval($id));}
    if($id=$app->ValvesPerEngine['id']){$attributes[]=array('type'=>'vcdb','name'=>'ValvesPerEngine','value'=>intval($id));}
    if($id=$app->FuelDeliveryType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FuelDeliveryType','value'=>intval($id));}
    if($id=$app->FuelDeliverySubType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FuelDeliverySubType','value'=>intval($id));}
    if($id=$app->FuelSystemControlType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FuelSystemControlType','value'=>intval($id));}
    if($id=$app->FuelSystemDesign['id']){$attributes[]=array('type'=>'vcdb','name'=>'FuelSystemDesign','value'=>intval($id));}
    if($id=$app->Aspiration['id']){$attributes[]=array('type'=>'vcdb','name'=>'Aspiration','value'=>intval($id));}
    if($id=$app->CylinderHeadType['id']){$attributes[]=array('type'=>'vcdb','name'=>'CylinderHeadType','value'=>intval($id));}
    if($id=$app->FuelType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FuelType','value'=>intval($id));}
    if($id=$app->IgnitionSystemType['id']){$attributes[]=array('type'=>'vcdb','name'=>'IgnitionSystemType','value'=>intval($id));}
    if($id=$app->TransmissionMfrCode['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionMfrCode','value'=>intval($id));}
    if($id=$app->TransmissionBase['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionBase','value'=>intval($id));}
    if($id=$app->TransmissionType['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionType','value'=>intval($id));}
    if($id=$app->TransmissionControlType['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionControlType','value'=>intval($id));}
    if($id=$app->TransmissionNumSpeeds['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionNumSpeeds','value'=>intval($id));}
    if($id=$app->TransElecControlled['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransElecControlled','value'=>intval($id));}
    if($id=$app->TransmissionMfr['id']){$attributes[]=array('type'=>'vcdb','name'=>'TransmissionMfr','value'=>intval($id));}
    if($id=$app->BedLength['id']){$attributes[]=array('type'=>'vcdb','name'=>'BedLength','value'=>intval($id));}
    if($id=$app->BedType['id']){$attributes[]=array('type'=>'vcdb','name'=>'BedType','value'=>intval($id));}
    if($id=$app->WheelBase['id']){$attributes[]=array('type'=>'vcdb','name'=>'WheelBase','value'=>intval($id));}
    if($id=$app->BrakeSystem['id']){$attributes[]=array('type'=>'vcdb','name'=>'BrakeSystem','value'=>intval($id));}
    if($id=$app->FrontBrakeType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FrontBrakeType','value'=>intval($id));}
    if($id=$app->RearBrakeType['id']){$attributes[]=array('type'=>'vcdb','name'=>'RearBrakeType','value'=>intval($id));}
    if($id=$app->BrakeABS['id']){$attributes[]=array('type'=>'vcdb','name'=>'BrakeABS','value'=>intval($id));}
    if($id=$app->FrontSpringType['id']){$attributes[]=array('type'=>'vcdb','name'=>'FrontSpringType','value'=>intval($id));}
    if($id=$app->RearSpringType['id']){$attributes[]=array('type'=>'vcdb','name'=>'RearSpringType','value'=>intval($id));}
    if($id=$app->SteeringSystem['id']){$attributes[]=array('type'=>'vcdb','name'=>'SteeringSystem','value'=>intval($id));}
    if($id=$app->SteeringType['id']){$attributes[]=array('type'=>'vcdb','name'=>'SteeringType','value'=>intval($id));}
    if($id=$app->Region['id']){$attributes[]=array('type'=>'vcdb','name'=>'Region','value'=>intval($id));}
    if($id=$app->VehicleType['id']){$attributes[]=array('type'=>'vcdb','name'=>'VehicleType','value'=>intval($id));}

    foreach($app->Note as $note){$attributes[]=array('type'=>'note','name'=>'note','value'=>(string)$note);}

    foreach($app->Qual as $qual)
    {
     $params=array();
     foreach($qual->param as $param){$params[]=(string)$param['value'].':'.(string)$param['uom'];}
     $attributes[]=array('type'=>'qdb','name'=>(string)$qual['id'],'value'=>implode(';',$params));
    }

    if($stmt=$db->conn->prepare('insert into application_attribute (id,applicationid,`name`,`value`,`type`,sequence,cosmetic) values(null,?,?,?,?,?,?)'))
    {
     $stmt->bind_param('isssii', $applicationid,$attribute_name,$attribute_value,$attribute_type,$sequence,$cosmetic);
     foreach($attributes as $attribute)
     {
      $sequence+=10; $attribute_name=$attribute['name']; $attribute_value=$attribute['value']; $attribute_type=$attribute['type'];
      $stmt->execute(); // insert the application record
     }
    }
   }
   $app_count++;
   $this->createPart($partnumber,$partcategoryid,$parttypeid);
  }
  $db->close();
  return $app_count;
 }

 
 //----
 
 function createAppsFromText($data,$partcatagory)
 {
  $db=new mysql; $db->connect();
  $app_count=0;

  // validate that the txt has the proper number of tab-delimited columns
  $rows= explode("\r\n", $data);
  foreach($rows as $row)
  {
   $fields=explode("\t",$row);
   if(count($fields)==9)
   {// row has the correct number of fields
    if($stmt=$db->conn->prepare('insert into application (id,oid,basevehicleid,makeid,equipmentid,parttypeid,positionid,quantityperapp,partnumber,status,cosmetic) values(null,?,?,0,0,?,?,?,?,0,?)'))
    {
     $oid=$this->newoid();
     $stmt->bind_param('siiiisi', $oid,$basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber,$cosmetic);
     
     $cosmetic=intval($fields[0]);
     $basevehicleid=intval($fields[1]); 
     $partnumber=trim(strtoupper($fields[2]));
     $parttypeid=intval($fields[3]); 
     $positionid=intval($fields[4]); 
     $quantityperapp=intval($fields[5]); 

     $stmt->execute();
     
     $applicationid=$db->conn->insert_id;
     
     //insert attribute records
     //vcdbattributes (name|value|sequence|cosmetic)
     // example: "FrontBrakeType|5|1|0;SubModel|20|3|1;"

     $attributes=array();
     
     if(strlen($fields[6]))
     {// VCdb attributes are present. parse them.
      $arrivalsequence=1;
      $attributestrings=explode('~',$fields[6]);
      foreach($attributestrings as $attributestring)
      {
       $attributechunks=explode('|',$attributestring);
       if(count($attributechunks)==4)
       {// FrontBrakeType|5|1|0   (Disc, sequence 1, non-cosmetic)
        $attributes[]=array('type'=>'vcdb','name'=>$attributechunks[0], 'value'=>intval($attributechunks[1]),'cosmetic'=>intval($attributechunks[3]),'sequence'=>intval($attributechunks[2]));
       }
       if(count($attributechunks)==2)
       {// FrontBrakeType|5   (sequence will be defaulted to 1 and non-cosmetic)
        $attributes[]=array('type'=>'vcdb','name'=>$attributechunks[0], 'value'=>intval($attributechunks[1]),'cosmetic'=>0,'sequence'=>$arrivalsequence);
       }
       $arrivalsequence++;
      }
     }

     if(strlen($fields[7]))
     {// Qdb is present.
      //  1234|1|0|parm1value|parm1uom|parm2value~1234|parm1value  (qdbid|sequence|cosmetic|parm1value|parm1uom)
      $chunks=explode('~',trim($fields[7]));
      foreach ($chunks as $chunk)
      {//1234|1|0|parm1value|parm1uom|parm2value
       $subchunks=explode('|',$chunk);
       if(count($subchunks)==3)
       {//qdb has no parameters (qdbid|sequence|cosmetic)
        $attributes[]=array('type'=>'qdb','name'=>intval($subchunks[0]), 'value'=>'','cosmetic'=>intval($subchunks[2]),'sequence'=>intval($subchunks[1]));
       }
       else
       {//qdb is either mal-formed or contains parms (not exactly 3 chuncks)
        if(count($subchunks)>3)
        {
         $params=array();
         for($i=3;$i<count($subchunks);$i++){$params[]=$subchunks[$i];}
         $attributes[]=array('type'=>'qdb','name'=>intval($subchunks[0]), 'value'=>implode('|',$params),'cosmetic'=>intval($subchunks[2]),'sequence'=>intval($subchunks[1]));               
        }                     
       }
      }
     }
 
     if(strlen($fields[8]))
     {// notes are present.
      $notestrings=explode('~',$fields[8]);
      foreach($notestrings as $notestring)
      {
       $notechunks=explode('|',$notestring);
       if(count($notechunks)==3)
       {// Some more Notes|2|1  (notetext, sequence 2, cosmetic)
        $attributes[]=array('type'=>'note','name'=>'note','value'=>$notechunks[0],'cosmetic'=>intval($notechunks[2]),'sequence'=>intval($notechunks[1]));
       }
      }      
     }     

     if($stmt=$db->conn->prepare('insert into application_attribute (id,applicationid,`name`,`value`,`type`,sequence,cosmetic) values(null,?,?,?,?,?,?)'))
     {
      $sequence=10;
      $stmt->bind_param('isssii', $applicationid,$attribute_name,$attribute_value,$attribute_type,$sequence,$cosmetic);
      foreach($attributes as $attribute)
      {
       $sequence=$attribute['sequence']; $attribute_name=$attribute['name']; $attribute_value=$attribute['value']; $attribute_type=$attribute['type']; $cosmetic=$attribute['cosmetic'];
       $stmt->execute(); // insert the application record
      }
     }
     
     $app_count++;
     if($partcatagory>0){$this->createPart($partnumber,$partcatagory,$parttypeid);}
    }
   }
  }
  
  $db->close();
  return $app_count;
 }

 //----
 
 
 function addFavoriteParttype($parttypeid,$myname)
 {
  $db = new mysql; $db->connect(); $success=false;
  if($stmt=$db->conn->prepare('insert into parttype values(?,?)'))
  {
   if($stmt->bind_param('is', $parttypeid,$myname))
   {
    $success=$stmt->execute();
   }
  }
  $db->close();
  return $success;
 }

 
 function addFavoriteMake($makeid,$myname)
 {
  $db = new mysql; $db->connect(); $success=false;
  $this->removeFavoriteMake($makeid);// just in case it already exists...
  if($stmt=$db->conn->prepare('insert into Make values(?,?)'))
  {
   if($stmt->bind_param('is', $makeid,$myname))
   {
    $success=$stmt->execute();
   }
  }
  $db->close();
  return $success;
 }

 function removeFavoriteMake($makeid)
 {
  $db = new mysql; $db->connect(); $success=false;
  if($stmt=$db->conn->prepare('delete from Make where MakeID=?'))
  {
   if($stmt->bind_param('i', $makeid))
   {
    $success=$stmt->execute();
   }
  }
  $db->close();
  return $success;
 }
  
 function removeFavoriteParttype($parttypeid)
 {
  $db = new mysql; $db->connect(); $success=false;
  $parttypes=array();
  if($stmt=$db->conn->prepare('delete from parttype where id=?'))
  {
   if($stmt->bind_param('i', $parttypeid))
   {
    $success=$stmt->execute();
   }
  }
  $db->close();
  return $success;
 }
 
 function getDeliverygroups()
 {
  $groups=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from deliverygroup order by description'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $groups[]=array('id'=>$row['id'],'description'=>$row['description']);
   }
  }
  $db->close();
  return $groups;
 }

 function getDeliverygroup($id)
 {
  $deliverygroup=false;
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from deliverygroup where id=?'))
  {
   if($stmt->bind_param('i', $id))
   {
    $stmt->execute();
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $deliverygroup=array('id'=>$row['id'],'description'=>$row['description']);
    }
   }
  }
  $db->close();
  return $deliverygroup;
 }

 function getDeliverygroupPartcategories($deliverygroupid)
 {
  $partcategories=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select deliverygroup_partcategory.partcategory as id, partcategory.name as `name` from deliverygroup_partcategory,partcategory where deliverygroup_partcategory.partcategory=partcategory.id and deliverygroupid=?'))
  {
   if($stmt->bind_param('i', $deliverygroupid))
   {  
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $partcategories[]=array('id'=>$row['id'],'name'=>$row['name']);
     }
    }
   }
  }
  $db->close();
  return $partcategories;            
 }
 
 function getReceiverprofiles()
 {
  $profiles=false; $status=0;
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('select * from receiverprofile where status=? order by `name`'))
  {
   $stmt->bind_param('i',$status);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $profiles[]=array('id'=>$row['id'],'name'=>$row['name'],'data'=>$row['data'],'status'=>$row['status'],'intervaldays'=>$row['intervaldays'],'lastexport'=>$row['lastexport'],'notes'=>base64_decode($row['notes']));
   }
  }
  $db->close();
  return $profiles;
 }
 
 function setDeliverygroupDescription($deliverygroupid,$description)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update deliverygroup set description=? where id=?'))
  {
   $stmt->bind_param('si', $description, $deliverygroupid);
   $stmt->execute();
  } //else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }
 
 
 

 function getReceiverprofileById($id)
 {
  $db = new mysql; $db->connect(); $profile=false;
  if($stmt=$db->conn->prepare('select * from receiverprofile where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    //convert "data" blob of name/value pairs into name-keyed array
    $keyeddata=array();
    $profiledata=$row['data'];
    $dataelements=explode(';',$profiledata);
    foreach($dataelements as $element)
    {
     $bits=explode(':',$element);
     if(count($bits)==2){$keyeddata[$bits[0]]=$bits[1];}
    }
       
    $profile=array('id'=>$row['id'],'name'=>$row['name'],'data'=>$row['data'],'keyeddata'=>$keyeddata,'status'=>$row['status'],'intervaldays'=>$row['intervaldays'],'lastexport'=>$row['lastexport'],'notes'=>base64_decode($row['notes']));
   }
  }
  $db->close();
  return $profile;
 }
 
 function receiverprofileName($receiverprofileid)
 {
  $db = new mysql; $db->connect(); $name='('.$receiverprofileid.') Not Found';
  if($stmt=$db->conn->prepare('select name from receiverprofile where id=?'))
  {
   $stmt->bind_param('i', $receiverprofileid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc()){$name=$row['name'];}
  }
  $db->close();
  return $name;
 }
 
 
 function getReceiverprofilePartcategories($receiverprofileid)
 {  // return and array of partcategory id's for a given receiverprofile
  $partcategories=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select deliverygroup_partcategory.partcategory from receiverprofile_deliverygroup,deliverygroup_partcategory where receiverprofile_deliverygroup.deliverygroupid=deliverygroup_partcategory.deliverygroupid and receiverprofile_deliverygroup.receiverprofileid=?'))
  {
   $stmt->bind_param('i',$receiverprofileid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $partcategories[]=$row['partcategory'];
   }
  }
  $db->close();
  return $partcategories;
 }
 
 function getReceiverprofileLifecyclestatuses($receiverprofileid)
 {  // return and array of partcategory id's for a given receiverprofile
  $lifecyclestatuses=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from receiverprofile_lifecycleststus where receiverprofileid=?'))
  {
   $stmt->bind_param('i',$receiverprofileid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $lifecyclestatuses[]=array('id'=>$row['id'],'lifecyclestatus'=>$row['lifecyclestatus']);
   }
  }
  $db->close();
  return $lifecyclestatuses;
 }
 
  
 function getAssettagsForReceiverprofile($receiverprofileid)
 {  // return and array of assettag id's for a given receiverprofile
  $assettags=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select receiverprofile_assettag.id, assettag.id as assettagid,tagtext from receiverprofile_assettag,assettag where receiverprofile_assettag.assettagid=assettag.id and receiverprofileid=? order by tagtext;'))
  {
   $stmt->bind_param('i',$receiverprofileid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $assettags[]=array('id'=>$row['id'],'assettagid'=>$row['assettagid'],'tagtext'=>$row['tagtext']);
   }
  }
  $db->close();
  return $assettags;
 }
 
 function addAssettagToReceiverProfile($receiverprofileid, $assettagid)
 {
  $db = new mysql; $db->connect(); $id=false;
  if($stmt=$db->conn->prepare("insert into receiverprofile_assettag values(null,?,?)"))
  {
   if($stmt->bind_param('is',$receiverprofileid,$assettagid))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    }
   }
  }
  $db->close();
  return $id;     
 }
 
 function removeAssettagFromReceiverProfile($id, $receiverprofileid)
 {
  $db = new mysql; $db->connect(); $success=false;
  if($stmt=$db->conn->prepare("delete from receiverprofile_assettag where id=? and receiverprofileid=?"))
  {
   if($stmt->bind_param('ii',$id, $receiverprofileid))
   {
    $success=$stmt->execute();
   }
  }
  $db->close();
  return $success;     
 }
 
   
 function getReceiverprofileDeliverygroupids($receiverprofileid)
 {  // return and array of partcategory id's for a given receiverprofile
  $deliverygroupids=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select deliverygroupid from receiverprofile_deliverygroup where receiverprofileid=?'))
  {
   $stmt->bind_param('i',$receiverprofileid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $deliverygroupids[]=$row['deliverygroupid'];
   }
  }
  $db->close();
  return $deliverygroupids;
 }

 function getReceiverprofilePricesheetnumber($receiverprofileid)
 {
  $db = new mysql; $db->connect(); $pricesheetnumber='';
  if($stmt=$db->conn->prepare('select pricesheetnumber from receiverprofile_pricesheet where receiverprofileid=?'))
  {
   $stmt->bind_param('i',$receiverprofileid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc()){$pricesheetnumber=$row['pricesheetnumber'];}
  }
  $db->close();
  return $pricesheetnumber;
 }
 
 function createReceiverprofile($name, $data)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare("insert into receiverprofile values(null,0,?,?,30,'0000-00-00','')"))
  {
   $stmt->bind_param('ss',$name,$data);
   $stmt->execute();
  }
  $db->close();
 }
 
 function updateReceiverprofile($id, $name, $data, $notes)
 {
  $db = new mysql; $db->connect();
  $encodednotes= base64_encode($notes);
  if($stmt=$db->conn->prepare('update receiverprofile set `name`=?, `data`=?, notes=? where id=?'))
  {
   $stmt->bind_param('sssi',$name,$data,$encodednotes,$id);
   $stmt->execute();
  }
  $db->close();
 }

 
 function getReceiverprofileParttranslations($receiverprofileid)
 {  // return and array of partcategory id's for a given receiverprofile
  $translations=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from receiverprofile_parttranslation where receiverprofileid=?'))
  {
   $stmt->bind_param('i',$receiverprofileid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $translations[$row['internalpart']]=$row['externalpart'];
   }
  }
  $db->close();
  return $translations;
 }
 
 
 function writeReceiverprofileParttranslation($id, $translations)
 {
  $db = new mysql; $db->connect(); $internalpart=''; $externalpart='';
    //delete all the old translation recs for this profile first
  $stmt=$db->conn->prepare('delete from receiverprofile_parttranslation where receiverprofileid=?');
  $stmt->bind_param('i',$id);
  $stmt->execute();
  
  if($stmt=$db->conn->prepare('insert into receiverprofile_parttranslation values(null,?,?,?)'))
  {
   if($stmt->bind_param('iss',$id,$internalpart,$externalpart))
   { 
    foreach($translations as $key=>$value)
    {
     $internalpart=$key; $externalpart=$value;
     $stmt->execute();
    }   
   }
  }
  $db->close();
 }
 
 function receiverPart($receiverprofileid, $internalpart)
 {
  $db = new mysql; $db->connect(); $externalpart=false;
  if($stmt=$db->conn->prepare('select externalpart from receiverprofile_parttranslation where receiverprofileid=? and internalpart=?'))
  {
   if($stmt->bind_param('is',$receiverprofileid,$internalpart))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $externalpart=$row['externalpart'];
     }
    }
   }
  }
  $db->close();
  return $externalpart;
 }
 
 
 
 
 
 
 function deleteReceiverprofile($id)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update receiverprofile set status=1 where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
  }
  $db->close();
 }

 
 function setReceiverprofilePricesheet($receiverprofileid,$pricesheetnumber)
 {
  $db = new mysql; $db->connect();
  
  if($stmt=$db->conn->prepare("delete from receiverprofile_pricesheet where receiverprofileid=?"))
  {
   if($stmt->bind_param('i',$receiverprofileid))
   {
    $stmt->execute();
   }
  }
  
  if($pricesheetnumber)
  {  
   if($stmt=$db->conn->prepare("insert into receiverprofile_pricesheet values(null,?,?)"))
   {
    if($stmt->bind_param('is',$receiverprofileid,$pricesheetnumber))
    {
     $stmt->execute();
    }
   }
  }
  $db->close();
 }
 
 function addDeliverygroupToReceiverProfile($receiverprofileid,$deliverygroupid)
 {
  $db = new mysql; $db->connect(); $id=false;
  if($stmt=$db->conn->prepare("insert into receiverprofile_deliverygroup values(null,?,?)"))
  {
   if($stmt->bind_param('ii',$receiverprofileid,$deliverygroupid))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    }
   }
  }
  $db->close();
  return $id;
 }
 
 
 function removeDeliverygroupFromReceiverProfile($receiverprofileid,$deliverygroupid)
 {
  $db = new mysql; $db->connect(); $id=false;
  if($stmt=$db->conn->prepare("delete from receiverprofile_deliverygroup where receiverprofileid=? and deliverygroupid=?"))
  {
   if($stmt->bind_param('ii',$receiverprofileid,$deliverygroupid))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    }
   }
  }
  $db->close();
  return $id;
 }
  
 function addLifecyclestatusToReceiverProfile($receiverprofileid,$lifecyclestatus)
 {
  $db = new mysql; $db->connect(); $id=false;
  if($stmt=$db->conn->prepare("insert into receiverprofile_lifecycleststus values(null,?,?)"))
  {
   if($stmt->bind_param('is',$receiverprofileid,$lifecyclestatus))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    }
   }
  }
  $db->close();
  return $id;
 }
 
 function removeLifecyclestatusFromReceiverProfile($id,$receiverprofileid)
 {
  $db = new mysql; $db->connect(); $success=false;
  if($stmt=$db->conn->prepare("delete from receiverprofile_lifecycleststus where id=? and receiverprofileid=?"))
  {
   if($stmt->bind_param('ii',$id, $receiverprofileid))
   {
    $success=$stmt->execute();
   }
  }
  $db->close();
  return $success;
 }
 
 
 
 
 
 

 function removePartcategoryFromReceiverProfile($receiverprofileid,$partcategoryid)
 {
  $db = new mysql; $db->connect(); $success=false;
  if($stmt=$db->conn->prepare("delete from receiverprofile_partcategory where receiverprofileid=? and partcategory=?"))
  {
   if($stmt->bind_param('ii',$receiverprofileid,$partcategoryid))
   {
    $success=$stmt->execute();
   }
  }
  $db->close();
  return $success;
 }
 
 
 function getMarketingcopyByReceiverprofileId($receiverprofileid)
 {
  $marketingcopy=array();$db = new mysql; $db->connect();
  
  if($stmt=$db->conn->prepare('select * from receiverprofile_marketingcopy where receiverprofileid=?'))
  {
   $stmt->bind_param('i',$receiverprofileid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $marketingcopy[]=array('id'=>$row['id'],'receiverprofileid'=>$row['receiverprofileid'],'marketcopycontent'=>$row['marketcopycontent'],'marketcopycode'=>$row['marketcopycode'],'marketcopyreference'=>$row['marketcopyreference'],'marketcopytype'=>$row['marketcopytype'],'recordsequence'=>$row['recordsequence'],'languagecode'=>$row['languagecode']);
   }
  }
  $db->close();
  return $marketingcopy;
 }


 function isValidBarcode($barcode) 
 {
  //checks validity of: GTIN-8, GTIN-12, GTIN-13, GTIN-14, GSIN, SSCC
  //see: http://www.gs1.org/how-calculate-check-digit-manually
  $barcode = (string) $barcode;
  //we accept only digits
  if (!preg_match("/^[0-9]+$/", $barcode)) {return false;}
  //check valid lengths:
  $l = strlen($barcode);
  if(!in_array($l, [8,12,13,14,17,18])){ return false;}
  //get check digit
  $check = substr($barcode, -1);
  $barcode = substr($barcode, 0, -1);
  $sum_even = $sum_odd = 0;
  $even = true;
  while(strlen($barcode)>0) 
  {
   $digit = substr($barcode, -1);
   if($even)
   {
    $sum_even += 3 * $digit;
   }
   else 
   {
    $sum_odd += $digit;
   }
   $even = !$even;
   $barcode = substr($barcode, 0, -1);
  }
  $sum = $sum_even + $sum_odd;
  $sum_rounded_up = ceil($sum/10) * 10;
  return ($check == ($sum_rounded_up - $sum));
 }

function gtinCheckDigit($barcode)
{
  //checks validity of: GTIN-8, GTIN-12, GTIN-13, GTIN-14, GSIN, SSCC
  //see: http://www.gs1.org/how-calculate-check-digit-manually
  $barcode = (string) $barcode;
  //we accept only digits
  if (!preg_match("/^[0-9]+$/", $barcode)) {return false;}
  //check valid lengths:
  $l = strlen($barcode);
  if(!in_array($l, [8,12,13,14,17,18])){ return false;}
  //get check digit
  $check = substr($barcode, -1);
  $barcode = substr($barcode, 0, -1);
  $sum_even = $sum_odd = 0;
  $even = true;
  while(strlen($barcode)>0) 
  {
   $digit = substr($barcode, -1);
   if($even)
   {
    $sum_even += 3 * $digit;
   }
   else 
   {
    $sum_odd += $digit;
   }
   $even = !$even;
   $barcode = substr($barcode, 0, -1);
  }
  $sum = $sum_even + $sum_odd;
  $sum_rounded_up = ceil($sum/10) * 10;
  return ($sum_rounded_up - $sum);
}








 function getAutocareDatabaseList($type)
 {
  $db=new mysql; $db->connect();
  $databases=array();
  if($stmt=$db->conn->prepare('select * from autocare_databases where databasetype like ? order by versiondate desc'))
  {
   $stmt->bind_param('s',$type);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $databases[]=array('name'=>$row['databasename'],'type'=>$row['databasetype'],'versiondate'=>$row['versiondate']);
   }
  }
  $db->close();
  return $databases;
 }

 function recordAutocareDatabaseList($name,$type,$versiondate)
 {
  $db=new mysql; $db->connect(); 
  if($stmt=$db->conn->prepare('insert into autocare_databases values(?,?,?)'))
  {
   $stmt->bind_param('sss',$name,$type,$versiondate);
   $stmt->execute();
  }
  $db->close();
 }
 

 function getAutoCareReleaseList($type)
 {
  $db=new mysql; $db->connect();
  $uri=false; $list=array();
 
  if($stmt=$db->conn->prepare("select * from config where configname='AutoCareResourceListURI'"))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $uri=$row['configvalue'];
   }
  }
       
  if($uri)
  {  
   $listJSON= @file_get_contents($uri);
   if($listJSON!==false)
   {
    if(substr($listJSON,0,1)=='{')
    {// looks like a JSON-encoded string (starts with a "{")
     $listdata= json_decode($listJSON,true);
     
     
     switch($type)
     {
      case 'VCdb':
       if(isset($type, $listdata))
       {
        $list=$listdata[$type]['MySQL']['complete']['releases'];
       }
       break;
         
      case 'PCdb':
       if(isset($type, $listdata))
       {
        $list=$listdata[$type]['MySQL']['releases'];
       }
       break;

      case 'PAdb':
       if(isset($type, $listdata))
       {
        $list=$listdata[$type]['MySQL']['releases'];
       }
       break;

      case 'Qdb':
       if(isset($type, $listdata))
       {
        $list=$listdata[$type]['MySQL']['releases'];
       }
       break;
         
         default :
             break;
         
     }
     
     
     
     
     
     
     
    }
   }
  }
  return $list;
 }
 
 
 

 function recordIssue($issuetype,$issuekeyalpha,$issuekeynumeric,$description,$source,$issuehash)
 {
  $db=new mysql; $db->connect(); $id=false;
  if($stmt=$db->conn->prepare("insert into issue (id,status,issuedatetime,issuetype,issuekeyalpha,issuekeynumeric,description,notes,resolvedby,resolvedon,snoozeduntil,source,issuehash) values(null,1,NOW(),?,?,?,?,'',0,'0000-00-00 00:00:00','0000-00-00 00:00:00',?,?)"))
  {
   $stmt->bind_param('ssisss', $issuetype,$issuekeyalpha,$issuekeynumeric,$description,$source,$issuehash);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }
  $db->close();
  return $id;
 }

 
 function getIssueByHash($hash)
 {
  $db=new mysql; $db->connect(); $issue=false;
  if($stmt=$db->conn->prepare('select * from issue where issuehash=?'))
  {
   $stmt->bind_param('s',$hash);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $issue=array('id'=>$row['id'],'status'=>$row['status'],'issuedatetime'=>$row['issuedatetime'],'issuetype'=>$row['issuetype'],'issuekeyalpha'=>$row['issuekeyalpha'],'issuekeynumeric'=>$row['issuekeynumeric'],'description'=>$row['description'],'notes'=>base64_decode($row['notes']),'source'=>$row['source'],'issuehash'=>$row['issuehash']);
   }
  }
  $db->close();
  return $issue;     
 }
 
 function getIssueById($id)
 {
  $db=new mysql; $db->connect(); $issue=false;
  if($stmt=$db->conn->prepare('select * from issue where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $issue=array('id'=>$row['id'],'status'=>$row['status'],'issuedatetime'=>$row['issuedatetime'],'issuetype'=>$row['issuetype'],'issuekeyalpha'=>$row['issuekeyalpha'],'issuekeynumeric'=>$row['issuekeynumeric'],'description'=>$row['description'],'notes'=>base64_decode($row['notes']),'source'=>$row['source'],'issuehash'=>$row['issuehash']);
   }
  }
  $db->close();
  return $issue;     
 }
 
 function getIssues($type,$keyalpha,$keynumeric,$rawstatuses,$limit)
 {
  $db=new mysql; $db->connect(); $issues=array();
  $statuses=array();
  foreach($rawstatuses as $rawstatus){$statuses[]=intval($rawstatus);}
  // status 0=closed, status 1=open, status 2=in-review, status 3=snoozed
  
  $statusclause= 'and status in('.implode(',',$statuses).')';
  $numericclause='';
  if(intval($keynumeric)!=0)
  {
   $numericclause=' and issuekeynumeric='.intval($keynumeric);
  }
  
  if($stmt=$db->conn->prepare('select * from issue where issuetype like ? and issuekeyalpha like ? '.$numericclause.' '.$statusclause.' limit ?'))
  {
   $stmt->bind_param('ssi',$type,$keyalpha,$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $issues[]=array('id'=>$row['id'],'status'=>$row['status'],'issuedatetime'=>$row['issuedatetime'],'issuetype'=>$row['issuetype'],'issuekeyalpha'=>$row['issuekeyalpha'],'issuekeynumeric'=>$row['issuekeynumeric'],'description'=>$row['description'],'notes'=>base64_decode($row['notes']),'source'=>$row['source'],'issuehash'=>$row['issuehash']);
   }
  }
  $db->close();
  return $issues;     
 }
 
 function getPartIssuesPrioritized($limit)
 {
  $db=new mysql; $db->connect(); $issues=array();
  if($stmt=$db->conn->prepare("select issue.* from issue, part, part_balance where issue.issuekeyalpha=part.partnumber and issuekeyalpha=part_balance.partnumber and issue.issuetype like 'PART/%' and issue.status=1 and part.lifecyclestatus='2'  order by amd desc limit ?"))
  {
   $stmt->bind_param('i',$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $issues[]=array('id'=>$row['id'],'status'=>$row['status'],'issuedatetime'=>$row['issuedatetime'],'issuetype'=>$row['issuetype'],'issuekeyalpha'=>$row['issuekeyalpha'],'issuekeynumeric'=>$row['issuekeynumeric'],'description'=>$row['description'],'notes'=>base64_decode($row['notes']),'source'=>$row['source'],'issuehash'=>$row['issuehash']);
   }
  }
  $db->close();
  return $issues;     
 }
 
  
 
 
 
 
 
 
 function deleteIssue($id)
 {
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from issue where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
  }
  $db->close();
 }
 
 function updateIssueNotes($id, $notes) {
      $db = new mysql; 
        $db->connect();
        if($stmt=$db->conn->prepare('update issue set notes=? where id=?'))
        {
         $encodednotes=base64_encode($notes);
         $stmt->bind_param('si', $encodednotes,$id);
         $stmt->execute();
        }
        $db->close();
 }
 
 function updateIssueStatus($id, $status) {
      $db = new mysql; 
        $db->connect();
        if($stmt=$db->conn->prepare('update issue set status=? where id=?'))
        {
         $stmt->bind_param('ii', $status,$id);
         $stmt->execute();
        }
        $db->close();
 }
 
 function snoozeIssue($id, $days)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update issue set status=3, snoozeduntil=date_add(NOW(),INTERVAL '.intval($days).' DAY) where id=?'))
  {
   $stmt->bind_param('i',$id);
   $stmt->execute();
  }
  $db->close();
 }
 
 function updateSnoozes()
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('update issue set status=1 where status=3 and snoozeduntil<now()'))
  {
   $stmt->execute();
  }
  $db->close();
 }
 
  
 
/* "path" and "language" together make up the primary key for the documentation table
 * ex: 'EN','APPS/FITMENT ASSETS/REPRESENTATION'
 *  is the record that describes the backstory and implications of this 
 * valuse and what effect it has on other areas of the system. A popup explainer 
 * widget on the app page (and maybe other places) could query explicitly for this 
 * record to display one small chunk of text beside the input field in question.
 * 
 * To get the multiple records for building "the whole story" as one document, 
 * the select critera would be less selective ( path like 'APPS/FITMENT ASSETS/%'
 * 
 * 
 */
 
 function getDocumentationText($path,$language)
 {
  $db=new mysql; $db->connect(); $records=array();
  if($stmt=$db->conn->prepare('select * from documentation where language=? and path like ? order by path,sequence'))
  {
   $stmt->bind_param('ss',$language,$path);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $records[]=array('id'=>$row['id'], 'language'=>$row['language'],'path'=>$row['path'],'sequence'=>$row['sequence'],'doctext'=>$row['doctext']);
   }
  }
  $db->close();
  return $records;
 }

 function validAutoCareVersionFormat($version)
 {
  // validate the 10-character version string (used mostly for input sanitation)
  // format: 2020-10-24
  if(strlen($version)!=10){return false;}
  $bits=explode('-', $version);
  if(count($bits)!=3){return false;}
  $year=intval($bits[0]); $month=intval($bits[1]); $day=intval($bits[2]);
  if($year < 2000 || $year > 2050){return false;}
  if($month < 1 || $month > 12){return false;}
  if($day < 1 || $day > 31){return false;}
  return true;
 }
 
 
 function validAutoCareLocalDatabaseName($name)
 {
  $validdatabases=$this->getAutocareDatabaseList('%'); // all types with wildcard search
  foreach($validdatabases as $validdatabase)
  {
   if($name==$validdatabase['name'])
   {
    return true;
   }
  }
  return false; 
 }
 
 
 function createAutoCareDatabase($newdatabasename,$databaseuser)
 {
  $db=new mysql; $db->connect(); $resulttext='';
  if($stmt=$db->conn->prepare('create database '.$newdatabasename))
  {
   if($stmt->execute())
   { // grant myself select privileges on the new (empty) database
    if($stmt=$db->conn->prepare("grant select on ".$newdatabasename.".* to '".$databaseuser."'@'localhost'"))
    {
     if($stmt->execute())
     {
      $resulttext='success';
     }
     else
     {// problem with execute
      $resulttext='execute error on db user permission grant - '.$db->conn->error;         
     }  
    }
    else
    {// problem with prepare
     $resulttext='prepare error on db user permission grant - '.$db->conn->error;
    }
   }
   else
   {// problem with execute
    $resulttext='execute error on db create - '.$db->conn->error;
   }
  }
  else
  {// problem with prepare
   $resulttext='prepare error on db create - '.$db->conn->error;       
  }
   
  $db->close();
  return $resulttext;
 }
 
 
function allowedHost($address)
{
 $db = new mysql; $db->connect();
 $returnval=true;
 if($stmt=$db->conn->prepare('select * from allowedhosts'))
 {
  if($stmt->execute())
  {
   if($db->result = $stmt->get_result())
   {
    $returnval=false; // we have a working database connection, so we are in in lock-it-down mode
    while($row = $db->result->fetch_assoc())
    {
     $hosts[]=$row['address'];
    }
   }
  }
  
  $foundmatch=true;
  while($foundmatch)
  {
   $foundmatch=false;
   // look for a verbatim entry for this address in the table
   if(in_array($address, $hosts))
   {
    $returnval=true;
    break;
   }

   // no exact match was found - if it's dot-notation numeric IPV4 address, do a wildcard compare on each record
   $addressoctets=explode('.',$address);
   if(count($addressoctets)==4)
   {// this is an a.b.c.d (IPV4) address 

     foreach($hosts as $allowed)
     {
      $allowedoctets=explode('.',$allowed);
      if(count($allowedoctets)==4)
      {// this database entry is for an a.b.c.d notation address
        if(($addressoctets[0]==$allowedoctets[0] || $allowedoctets[0]=='*')&&
         ($addressoctets[1]==$allowedoctets[1] || $allowedoctets[1]=='*')&&
         ($addressoctets[2]==$allowedoctets[2] || $allowedoctets[2]=='*')&&
         ($addressoctets[3]==$allowedoctets[3] || $allowedoctets[3]=='*'))
        {
         $returnval=true;
         break;
        }
       }       
     }
   }
   else
   {// some other address notation (IPV6?
    break;   
   }
  }
 }
 $db->close();
 return $returnval;
}
 




 function addClipboardObject($userid,$description,$objecttype,$objectkey,$objectdata)
 {
  $db=new mysql; $db->connect(); $id=false;
  $encodedobjectdata= base64_encode($objectdata);
  $encodeddescription= base64_encode($description);
  if($stmt=$db->conn->prepare('insert into clipboard (id,userid,description,objecttype,objectkey,objectdata,capturedate) values(null,?,?,?,?,?,now() )'))
  {
   if($stmt->bind_param('issss', $userid,$encodeddescription,$objecttype,$objectkey,$encodedobjectdata))
   {
    if($stmt->execute())
    {
     $id=$db->conn->insert_id;
    }
   }
  } // else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
  return $id;
 }

 
 function getClipboard($userid,$objecttype)
 {
  $db=new mysql; $db->connect(); $objects=array();
  
  if($stmt=$db->conn->prepare('select * from clipboard where userid=? and objecttype like ? order by objecttype,id desc'))
  {
   $stmt->bind_param('is',$userid, $objecttype);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $objects[]=array('id'=>$row['id'],'description'=>base64_decode($row['description']),'objecttype'=>$row['objecttype'],'objectkey'=>$row['objectkey'],'objectdata'=>base64_decode($row['objectdata']));
   }
  }
  $db->close();
  return $objects;
 }
 
 function clipboardHasAppsFromSinglePart($userid)
 {
  $db=new mysql; $db->connect(); $result=false; $appids=array(); $distinctpartnumbers=array();
  
  if($stmt=$db->conn->prepare("select * from clipboard where userid=? and objecttype ='app'"))
  {
   $stmt->bind_param('i',$userid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc()){$appids[]=intval($row['objectkey']);}
  }

  foreach($appids as $appid)
  {
   if($appid)
   {
    $app=$this->getApp($appid);
    if($app)
    {
        $distinctpartnumbers[$app['partnumber']]='';
    }
   }
  }

  if(count($distinctpartnumbers)==1){$result=true;}

  $db->close();
  return $result;
 }
 
 
 
 

  function deleteClipboardObject($userid,$id)
 {
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from clipboard where userid=? and id=?'))
  {
   $stmt->bind_param('ii', $userid, $id);
   $stmt->execute();
  } // else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }

 
 function deleteClipboardObjects($userid)
 {
  $db=new mysql; $db->connect();
  
  if($stmt=$db->conn->prepare('delete from clipboard where userid=?'))
  {
   $stmt->bind_param('i', $userid);
   $stmt->execute();
  } // else{$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $db->conn->error."\n");fclose($fp);}
  $db->close();
 }
 
 function deleteOldClipboardObjects($agedays)
 {  // delete all objects (any user) on the clipboard older than (or equal to) agedays
  $db=new mysql; $db->connect();
  if($stmt=$db->conn->prepare('delete from clipboard where datediff(now(),capturedate)>=?'))
  {
   $stmt->bind_param('i', $agedays);
   $stmt->execute();
  }
  $db->close();
 }
 
function getAppSummary($partnumber)
{
 $db=new mysql; $db->connect(); $returnval=array('summary'=>'','age'=>-1);
 if($stmt=$db->conn->prepare('select summary,firstyear,lastyear,DATEDIFF(now(),capturedatetime) as age from part_application_summary where partnumber=?'))
 {
  $stmt->bind_param('s',$partnumber);
  $stmt->execute();
  $db->result = $stmt->get_result();
  if($row = $db->result->fetch_assoc())
  {
   $returnval['summary']=$row['summary'];
   $returnval['age']=intval($row['age']);
   $returnval['firstyear']=intval($row['firstyear']);   
   $returnval['lastyear']=intval($row['lastyear']);   
  }    
 }
 $db->close();
 return $returnval;
}

function updateAppSummary($partnumber,$summary,$firstyear,$lastyear)
{
 $db=new mysql; $db->connect(); $insertednew=false;
 if($stmt=$db->conn->prepare('select summary,DATEDIFF(now(),capturedatetime) as age from part_application_summary where partnumber=?'))
 {
  $stmt->bind_param('s',$partnumber);
  $stmt->execute();
  $db->result = $stmt->get_result();
  if($row = $db->result->fetch_assoc())
  {// record exists for this part
   if($stmt=$db->conn->prepare('update part_application_summary set summary=?,firstyear=?,lastyear=?,capturedatetime=now() where partnumber=?'))
   {
    $stmt->bind_param('siis',$summary, $firstyear, $lastyear, $partnumber);
    $stmt->execute();
   }
  }
  else
  {// record does not exist for this part
   if($summary!='')
   {
    if($stmt=$db->conn->prepare('insert into part_application_summary (partnumber,summary,firstyear,lastyear,capturedatetime) values(?,?,?,?,now())'))
    {
     $stmt->bind_param('ssii', $partnumber, $summary, $firstyear, $lastyear);
     $stmt->execute();
     $insertednew=true;
    }
   }
  }
 }
 $db->close();
 return $insertednew;
}


function deleteAppSummary($partnumber)
{
 $db=new mysql; $db->connect();
 if($stmt=$db->conn->prepare('delete from part_application_summary where partnumber=?'))
 {
  if($stmt->bind_param('s',$partnumber))
  {
   $stmt->execute();
  }
 }
 $db->close();
}

 function getPartBalance($partnumber)
 {
  $db = new mysql; $db->connect(); $balance=false;
  if($stmt=$db->conn->prepare('select * from part_balance where partnumber=? and updateddate >= date_sub(NOW(),INTERVAL 30 DAY)'))
  {
   if($stmt->bind_param('s',$partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $balance=array('qoh'=>$row['qoh'],'amd'=>$row['amd'], 'updateddate'=>$row['updateddate']);
     }
    }
   }
  }
  $db->close();
  return $balance;
 }


 
 function updatePartBalance($partnumber,$qoh,$amd,$cost)
 {
  $db=new mysql; $db->connect(); $insertednew=false;
  if($stmt=$db->conn->prepare('select part_balance.qoh,part.firststockedDate from part_balance left join part on part_balance.partnumber=part.partnumber where part_balance.partnumber=?'))
  {
   $stmt->bind_param('s',$partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {// record exists for this part
 
    if($row['qoh']==0 && $row['firststockedDate']=='0000-00-00' && $qoh>0)
    {// first stocking event on a part that existed in the balance table
     $this->setPartFirststockedDate($partnumber, date('Y-m-d'), true);
     $this->logPartEvent($partnumber, 0, 'first stocked date set - existing balance record changed to non-zero qoh', '');
    }
    
    if($stmt=$db->conn->prepare('update part_balance set qoh=?, amd=?, cost=?, updateddate=now() where partnumber=?'))
    {
     $stmt->bind_param('ddds',$qoh, $amd, $cost, $partnumber);
     $stmt->execute();
    }
   }
   else
   {// record does not exist for this part
    if($stmt=$db->conn->prepare('insert into part_balance (partnumber,qoh,amd,cost,updateddate) values(?,?,?,?,now())'))
    {
     $stmt->bind_param('sddd', $partnumber, $qoh, $amd,$cost);
     $stmt->execute();
     $insertednew=true;

     if($qoh>0)
     {// first stocking event on a part that is just now being written into the part_balance table
      $this->setPartFirststockedDate($partnumber, date('Y-m-d'), true);
      $this->logPartEvent($partnumber, 0, 'first stocked date set - new balance record created with non-zero qoh', '');
     }
     
    }      
   }
  }
  $db->close();
  return $insertednew;
 }

 function addPartBOM($partnumber,$bom)
 {
  $db=new mysql; $db->connect(); $success=false;
  
  // see if BOM already exists given (left) partnumber
  //ccc

  $seqindex=array(); $cmpnindex=array(); foreach($bom as $id=>$component){$seqindex[$id]=$component['sequence']; $cmpnindex[$id]=$component['partnumber'];}
  array_multisort($seqindex,SORT_ASC,$cmpnindex, SORT_ASC,$bom);

  $existing=$this->getKitComponents($partnumber);   // $parts[]=array('id'=>$row['id'],'partnumber'=>$row['rightpartnumber'],'units'=>$row['units'],'sequence'=>$row['sequence']); 
  $seqindex=array(); $cmpnindex=array(); foreach($existing as $id=>$component){$seqindex[$id]=$component['sequence']; $cmpnindex[$id]=$component['partnumber'];}
  array_multisort($seqindex,SORT_ASC,$cmpnindex, SORT_ASC,$existing);    
  

  $newhash=''; foreach($bom as $component){$newhash.=$component['partnumber'].'~'.$component['units'].'~'.$component['sequence']."\t";}
  $existinghash=''; foreach($existing as $component){$existinghash.=$component['partnumber'].'~'.$component['units'].'~'.$component['sequence']."\t";}

  
  if($newhash==$existinghash)
  {// new and existing are euqal - no need to update anything
  }
  else
  {// incomming BOM is different from exisging one

   $this->logPartEvent($partnumber, 0, 'BOM change (new != old):'.$newhash.'!='.$existinghash , '');
   
   $this->addPartBOMhistory($partnumber,$existing);
   
      
   if($stmt=$db->conn->prepare("delete from partrelationship where leftpartnumber=? and relationtype='kit'"))
   {
    if($stmt->bind_param('s',$partnumber))
    {
     $stmt->execute();
     $componentpartnumber=''; $componentuom=''; $componentunits=0; $componentsequence=0;
    
     if($stmt=$db->conn->prepare("insert into partrelationship values(null,?,?,'kit',?,?)"))
     {
      if($stmt->bind_param('ssdi',$partnumber,$componentpartnumber,$componentunits,$componentsequence))
      {
       foreach($bom as $component)
       {
        $componentpartnumber=$component['partnumber']; $componentunits=$component['units']; $componentsequence=$component['sequence'];
        $stmt->execute();
        $success=true;
       }
      } 
     }
    }   
   }
   $db->close();
  }
  return $success;
 }
 
 
 function addPartBOMhistory($partnumber,$bom)
 {
  $db=new mysql; $db->connect();
  $componentpartnumber=''; $componentunits=0; $componentsequence=0;
  if($stmt=$db->conn->prepare("insert into partrelationship_history values(null,?,?,'kit',?,?,now())"))
  {
   if($stmt->bind_param('ssii',$partnumber,$componentpartnumber,$componentunits,$componentsequence))
   {
    foreach($bom as $component)
    {
     $componentpartnumber=$component['partnumber']; $componentunits=$component['units']; $componentsequence=$component['sequence'];
     $stmt->execute();
     $success=true;
    }
   } 
  }
  $db->close();
 }

 
 function partMfrLabel($partnumber)
 {
  $mfrlabel='';
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select part.partcategory,partcategory.mfrlabel from part left join partcategory on part.partcategory=partcategory.id where part.partnumber=?'))
  {
   if($stmt->bind_param('s', $partnumber))
   {
    $stmt->execute();
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $mfrlabel=$row['mfrlabel'];
    }
   }
  }
  $db->close();
  return $mfrlabel;
 }

 
 
 
 

//$yearquarter,$geography,$vehicleid,$basevehicleid,$yearid,$makeid,$modelid,$submodelid,$bodytypeid,$bodynumdoorsid,$drivetypeid,$fueltypeid,$enginebaseid,$enginevinid,$fueldeliverysubtypeid,$transcontroltypeid,$transnumspeedid,$aspirationid,$vehicletypeid,$vehiclecount
 function addExperianVIOrecords($records)
 {
  $db=new mysql; $db->connect();     
  if($stmt=$db->conn->prepare('insert into experianVIO (id,yearQuarter,geography,vehicleID,baseVehicleID,yearID,makeID,modelID,subModelID,bodyTypeID,bodyNumDoorsID,driveTypeID,fuelTypeID,engineBaseID,engineVINID,fuelDeliverySubTypeID,transControlTypeID,transNumSpeedID,aspirationID,vehicleTypeID,vehicleCount) values(null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'))
  {
   $yearquarter=''; $geography=''; $vehicleid=0; $basevehicleid=0; $yearid=0; $makeid=0; $modelid=0; $submodelid=0; $bodytypeid=0; $bodynumdoorsid=0; $drivetypeid=0; $fueltypeid=0; $enginebaseid=0; $enginevinid=0; $fueldeliverysubtypeid=0; $transcontroltypeid=0; $transnumspeedid=0; $aspirationid=0; $vehicletypeid=0; $vehiclecount=0;
   $stmt->bind_param('ssiiiiiiiiiiiiiiiiii', $yearquarter,$geography,$vehicleid,$basevehicleid,$yearid,$makeid,$modelid,$submodelid,$bodytypeid,$bodynumdoorsid,$drivetypeid,$fueltypeid,$enginebaseid,$enginevinid,$fueldeliverysubtypeid,$transcontroltypeid,$transnumspeedid,$aspirationid,$vehicletypeid,$vehiclecount);      
   foreach($records as $record)
   {
    $yearquarter=$record['yearquarter']; $geography=$record['geography']; $vehicleid=$record['vehicleid']; $basevehicleid=$record['basevehicleid']; $yearid=$record['yearid']; $makeid=$record['makeid']; $modelid=$record['modelid']; $submodelid=$record['submodelid']; $bodytypeid=$record['bodytypeid']; $bodynumdoorsid=$record['bodynumdoorsid']; $drivetypeid=$record['drivetypeid']; $fueltypeid=$record['fueltypeid']; $enginebaseid=$record['enginebaseid']; $enginevinid=$record['enginevinid']; $fueldeliverysubtypeid=$record['fueldeliverysubtypeid']; $transcontroltypeid=$record['transcontroltypeid']; $transnumspeedid=$record['transnumspeedid'];$aspirationid=$record['aspirationid']; $vehicletypeid=$record['vehicletypeid']; $vehiclecount=$record['vehiclecount'];
    $stmt->execute();
   }
  }
  $db->close();     
 }
 
 function appVIOexperian($appid,$geography, $yearquarter)
 {// get the parts-in-operation count for a scpecific app (VIO*app_qty)
  $returnVal=0;
  if($app=$this->getApp($appid))
  {//look through app's attributes for stuff that would mapp to experian's data. if we don't get any usable attributes, vio will be determined by basevehicle (mmy) alone
   $vcdbattributes=array();
   foreach($app['attributes'] as $attribute)
   {
    if($attribute['type']=='vcdb')
    {
     $vcdbattributes[$attribute['name']]=$attribute['value']; //$attribute['name'],$attribute['value']
    }
   }
   $returnVal=$this->experianVehicleCount($geography, $yearquarter, $app['basevehicleid'], $vcdbattributes);   
  }
  return $returnVal;
 }
 
 
 function experianVehicleCount($geography,$yearquarter,$basevehicleid, $vcdbattributes)
 {
  $returnVal=-1;
  
  $whereclause='';
  foreach($vcdbattributes as $vcdbattributename=>$vcdbattributevalue)
  {
   switch($vcdbattributename)
   {
    case 'VehicleID': $whereclause.=' and vehicleID ='.intval($vcdbattributevalue); break;
    case 'SubModel': $whereclause.=' and subModelID ='.intval($vcdbattributevalue); break;
    case 'BodyType': $whereclause.=' and bodyTypeID ='.intval($vcdbattributevalue); break;
    case 'BodyNumDoors': $whereclause.=' and bodyNumDoorsID ='.intval($vcdbattributevalue); break;
    case 'DriveType': $whereclause.=' and driveTypeID ='.intval($vcdbattributevalue); break;
    case 'FuelType': $whereclause.=' and fuelTypeID ='.intval($vcdbattributevalue); break;
    case 'EngineBase': $whereclause.=' and engineBaseID ='.intval($vcdbattributevalue); break;
    case 'EngineVIN': $whereclause.=' and engineVINID ='.intval($vcdbattributevalue); break;
    case 'FuelDeliverySubType': $whereclause.=' and fuelDeliverySubTypeID ='.intval($vcdbattributevalue); break;
    case 'TransmissionControlType': $whereclause.=' and transControlTypeID ='.intval($vcdbattributevalue); break;
    case 'TransmissionNumSpeeds': $whereclause.=' and transNumSpeedID ='.intval($vcdbattributevalue); break;
    case 'Aspiration': $whereclause.=' and aspirationID ='.intval($vcdbattributevalue); break;
    default: break;
   }
  }

  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select sum(vehicleCount) as vcount from experianVIO where yearQuarter=? and geography=? and baseVehicleID=? '.$whereclause))
  {
   if($stmt->bind_param('ssi', $yearquarter, $geography, $basevehicleid))
   {  
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $returnVal=$row['vcount'];
     }
    }
   }
  }
  $db->close();
  return $returnVal;
 }

 
/*
 function partVIOexperian($partnumber, $geography, $yearquarter)
 {     
 // start with list of all apps for a given partnumber
 // build a associative array keyed by basvid and the values being array of apps (basevid-keyed apps)
 // get the vehilceCount for the basevid
 // determine usaable vs un-usable apps within each basevid grouping. Usable are apps that have one or more Experian VCdb attributes, un-usable are apps that dont have any of the Experian attributes
  $viototal=0;
  $allappsforpart=$this->getAppsByPartnumber($partnumber);
  $basevidkeyedapps=array(); foreach($allappsforpart as $app){$basevidkeyedapps[$app['basevehicleid']][]=$app;}
  
  foreach($basevidkeyedapps as $basevehicleid=>$apps)
  {  // get the vio total for this basevid (mmy only, no qualifiers)
   $bsaevidtotal=0;
   $usableapps=array();
   $unusableappexists=false;
   
   foreach($apps as $app)
   {
    if($this->attributesAreExperianUseful($app['attributes']))
    {// this app contains experian-usable attributed
     $usableapps[]=$app;
    }
    else
    {
     $unusableappexists=true;
    }
   }
       
   if($unusableappexists)
   {
    $bsaevidtotal=$this->experianVehicleCount($geography, $yearquarter, $basevehicleid, []);
   }
   else
   {
    foreach($usableapps as $usableapp)
    {
     $vcdbattributes=array();
     foreach($usableapp['attributes'] as $attribute)
     {
      if($attribute['type']=='vcdb')
      {
       $vcdbattributes[$attribute['name']]=$attribute['value']; //$attribute['name'],$attribute['value']
      }
     }
     $bsaevidtotal+=$this->experianVehicleCount($geography, $yearquarter, $basevehicleid, $vcdbattributes);   
    }
   }   
   $viototal+=$bsaevidtotal;
  }
  $this->updatePartVIO($partnumber,$geography,$yearquarter,$viototal);
  return $viototal;
 }
 
*/
 
   
 function computePartVIO($partnumber, $geography, $yearquarter, $basevehicles)
 {
 // return an array that contains the pio, startyear, endyear, meanyear    
 // "mean" model-year is the population-weighted average year. If all years had equal VIO populations, the the mean will equal the mid-point 
 // in the year-range. If earlier model-years had a higher populatuon, the mean would skew earlier than the mid-point
 // start with list of all apps for a given partnumber
 // build a associative array keyed by basvid and the values being array of apps (basevid-keyed apps)
 // get the vehilceCount for the basevid
 // determine usaable vs un-usable apps within each basevid grouping. Usable are apps that have one or more Experian VCdb attributes, un-usable are apps that dont have any of the Experian attributes
 // we're passing in the basevehicle reference list because we've generally avoided instancing vcdb closs opjects at this low level.
  $viototal=0;
  $startyear=9999;
  $endyear=0;

  $allappsforpart=$this->getAppsByPartnumber($partnumber);
  $basevidkeyedapps=array(); foreach($allappsforpart as $app){$basevidkeyedapps[$app['basevehicleid']][]=$app;}
  $yearpopulation=array(); //2010=>23232,2011=>324322,2012=>2432323
  
  foreach($basevidkeyedapps as $basevehicleid=>$apps)
  {  // get the vio total for this basevid (mmy only, no qualifiers)
   $bsaevidtotal=0;
   $usableapps=array();
   $unusableappexists=false;
   
   foreach($apps as $app)
   {
    if($this->attributesAreExperianUseful($app['attributes']))
    {// this app contains experian-usable attributed
     $usableapps[]=$app;
    }
    else
    {
     $unusableappexists=true;
    }
   }
   
   if($unusableappexists)
   {
    $bsaevidtotal=$this->experianVehicleCount($geography, $yearquarter, $basevehicleid, []);
   }
   else
   {
    foreach($usableapps as $usableapp)
    {
     $vcdbattributes=array();
     foreach($usableapp['attributes'] as $attribute)
     {
      if($attribute['type']=='vcdb')
      {
      $vcdbattributes[$attribute['name']]=$attribute['value']; //$attribute['name'],$attribute['value']
      }
     }
     $bsaevidtotal+=$this->experianVehicleCount($geography, $yearquarter, $basevehicleid, $vcdbattributes);   
    }
   }
   $viototal+=$bsaevidtotal;

   if(array_key_exists($basevehicleid,$basevehicles))
   {
    $year=$basevehicles[$basevehicleid]['year'];
    if($year<$startyear){$startyear=$year;}
    if($year>$endyear){$endyear=$year;}    
    if(array_key_exists($year,$yearpopulation))
    {
     $yearpopulation[$year]+=$bsaevidtotal;    
    }
    else
    {
     $yearpopulation[$year]=$bsaevidtotal;   
    }
   }
  }
  
  $ypsum=0; $psum=0; foreach($yearpopulation as $y=>$p){$ypsum+=($y*$p); $psum+=$p;}  
  $meanyear=0; if($psum>0){$meanyear=round(($ypsum/$psum),0);}
  $this->updatePartVIO($partnumber,$geography,$yearquarter,$viototal,$startyear,$endyear,$meanyear);
  return array('vio'=>$viototal,'startyear'=>$startyear,'endyear'=>$endyear,'meanyear'=>$meanyear);
 }

 function updatePartVIOgrowthtrend($partnumber,$geography,$yearquarter,$growthtrend)
 {
  $db=new mysql; $db->connect(); $idupdated=false;
  if($stmt=$db->conn->prepare('select id from part_VIO where partnumber=? and geography=? and yearquarter=?'))
  {
   $stmt->bind_param('sss',$partnumber,$geography,$yearquarter);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {// record exists for this part
    $idupdated=$row['id'];
    if($stmt=$db->conn->prepare('update part_VIO set growthtrend=? where partnumber=? and geography=? and yearquarter=?'))
    {
     $stmt->bind_param('dsss',$growthtrend, $partnumber, $geography, $yearquarter);
     $stmt->execute();
    }
   }
  }
  $db->close();
  return $idupdated;
 }

  function updatePartVIO($partnumber,$geography,$yearquarter,$vehiclecount,$startyear,$endyear,$meanyear)
 {
  $db=new mysql; $db->connect(); $insertednew=false;
  if($stmt=$db->conn->prepare('select id from part_VIO where partnumber=? and geography=? and yearquarter=?'))
  {
   $stmt->bind_param('sss',$partnumber,$geography,$yearquarter);
   $stmt->execute();
   $db->result = $stmt->get_result(); //ccc
   if($row = $db->result->fetch_assoc())
   {// record exists for this part
    if($stmt=$db->conn->prepare('update part_VIO set capturedate=now(),vehicleCount=?, startyear=?,endyear=?,meanyear=? where partnumber=? and geography=? and yearquarter=?'))
    {
     $stmt->bind_param('iiiisss',$vehiclecount, $startyear, $endyear, $meanyear, $partnumber, $geography, $yearquarter);
     $stmt->execute();
    }
   }
   else
   {// record does not exist for this part. Insert a new one
    if($stmt=$db->conn->prepare('insert into part_VIO (id,partnumber,yearQuarter,geography,capturedate,vehicleCount,startyear,endyear,meanyear) values(null,?,?,?,now(),?,?,?,?)'))
    {
     $stmt->bind_param('sssiiii', $partnumber, $yearquarter, $geography, $vehiclecount, $startyear,$endyear,$meanyear);
     $stmt->execute();
     $insertednew=true;
    }      
   }
  }
  $db->close();
  return $insertednew;
 }

 
 
 
 
 
 
 
 
 
 
 
 
 function partVIOtotal($partnumber,$geography,$yearquarter)
 {
  $viototal=0;
  $records=$this->getPartVIOrecords($partnumber, $geography, $yearquarter);
  if(count($records))
  {
   $viototal=$records[0]['vehiclecount'];      
  }
  return $viototal;
 }
 
 function partVIOmeanYear($partnumber,$geography,$yearquarter)
 {
  $meanyear=0;
  $records=$this->getPartVIOrecords($partnumber, $geography, $yearquarter);
  if(count($records))
  {
   $meanyear=$records[0]['meanyear'];      
  }
  return $meanyear;
 }
 
 function partVIOstartYear($partnumber,$geography,$yearquarter)
 {
  $startyear=0;
  $records=$this->getPartVIOrecords($partnumber, $geography, $yearquarter);
  if(count($records))
  {
   $startyear=$records[0]['startyear'];      
  }
  return $startyear;
 }
 
 function partVIOendYear($partnumber,$geography,$yearquarter)
 {
  $endyear=0;
  $records=$this->getPartVIOrecords($partnumber, $geography, $yearquarter);
  if(count($records))
  {
   $endyear=$records[0]['endyear'];      
  }
  return $endyear;
 }
 
 function partVIOgrowthTrend($partnumber,$geography,$yearquarter)
 {
  $growthtrend=0;
  $records=$this->getPartVIOrecords($partnumber, $geography, $yearquarter);
  if(count($records))
  {
   $growthtrend=$records[0]['growthtrend'];      
  }
  return $growthtrend;
 }

 function getPartVIOrecords($partnumber,$geography,$yearquarter)
 {
  $db = new mysql; $db->connect(); $returnval=array();
  if($stmt=$db->conn->prepare('select id,partnumber,yearQuarter,geography,capturedate,vehicleCount,startyear,endyear,meanyear,growthtrend,DATEDIFF(now(),capturedate) as age from part_VIO where partnumber=? and geography=? and yearQuarter=?'))
  {
   if($stmt->bind_param('sss',$partnumber,$geography,$yearquarter))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $returnval[]=array('id'=>$row['id'], 'partnumber'=>$row['partnumber'], 'yearquarter'=>$row['yearQuarter'],'geography'=>$row['geography'], 'capturedate'=>$row['capturedate'],'vehiclecount'=>$row['vehicleCount'],'recordage'=>$row['age'],'startyear'=>$row['startyear'],'endyear'=>$row['endyear'],'meanyear'=>$row['meanyear'],'growthtrend'=>$row['growthtrend']);
     }
    }
   }
  }
  $db->close();
  return $returnval;
 }






function attributesAreExperianUseful($attributes)
 {
  $returnval=false;

  foreach($attributes as $attribute)
  {
   if($attribute['type']=='vcdb')
   {
    $n=$attribute['name'];
    if($n=='VehicleID' || $n=='SubModel' || $n=='BodyType' || $n=='BodyNumDoors' || $n=='DriveType' || $n=='FuelType' || $n=='EngineBase' || $n=='EngineVIN' || $n=='FuelDeliverySubType' || $n=='TransmissionControlType' || $n=='TransmissionNumSpeeds' || $n=='Aspiration')
    {// this attribute is meaningful to experian's VIO data
     $returnval=true;
     break;
    }
   }
  }
  
  return $returnval;  
 }
 
 function getExperianBasevehicleRecords($geography,$yearquarter,$basevehicleid)
 {
  $records=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select yearQuarter,geography,yearQuarter,geography,vehicleID,baseVehicleID,subModelID,bodyTypeID,bodyNumDoorsID,driveTypeID,fuelTypeID,engineBaseID,engineVINID,fuelDeliverySubTypeID,transControlTypeID,transNumSpeedID,aspirationID,vehicleTypeID,vehicleCount from experianVIO where yearQuarter=? and geography=? and baseVehicleID=?'))
  {
   if($stmt->bind_param('ssi', $yearquarter, $geography, $basevehicleid))
   {  
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $records[]=array('yearquarter'=>$row['yearQuarter'],'geography'=>$row['geography'],'basevehicleid'=>$row['baseVehicleID'],'vehicleid'=>$row['vehicleID'],'submodelid'=>$row['subModelID'],'bodytypeid'=>$row['bodyTypeID'],'bodynumdoorsid'=>$row['bodyNumDoorsID'],'drivetypeid'=>$row['driveTypeID'],'fueltypeid'=>$row['fuelTypeID'],'enginebaseid'=>$row['engineBaseID'],'enginevinid'=>$row['engineVINID'],'fueldeliverysubtypeid'=>$row['fuelDeliverySubTypeID'],'transmissioncontroltypeid'=>$row['transControlTypeID'],'transmissionnumspeedsid'=>$row['transNumSpeedID'],'aspirationid'=>$row['aspirationID'],'vehicletypeid'=>$row['vehicleTypeID'],'vehiclecount'=>$row['vehicleCount']);
     }
    }
   }
  }
  $db->close();
  return $records;
 }
 

 function getExperianRecords($geography,$yearquarter,$vehiclecount)
 {
  $records=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select yearQuarter,geography,yearQuarter,geography,vehicleID,baseVehicleID,subModelID,bodyTypeID,bodyNumDoorsID,driveTypeID,fuelTypeID,engineBaseID,engineVINID,fuelDeliverySubTypeID,transControlTypeID,transNumSpeedID,aspirationID,vehicleTypeID,vehicleCount from experianVIO where yearQuarter=? and geography=? and vehicleCount>?'))
  {
   if($stmt->bind_param('ssi', $yearquarter, $geography, $vehiclecount))
   {  
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $records[]=array('yearquarter'=>$row['yearQuarter'],'geography'=>$row['geography'],'basevehicleid'=>$row['baseVehicleID'],'vehicleid'=>$row['vehicleID'],'submodelid'=>$row['subModelID'],'bodytypeid'=>$row['bodyTypeID'],'bodynumdoorsid'=>$row['bodyNumDoorsID'],'drivetypeid'=>$row['driveTypeID'],'fueltypeid'=>$row['fuelTypeID'],'enginebaseid'=>$row['engineBaseID'],'enginevinid'=>$row['engineVINID'],'fueldeliverysubtypeid'=>$row['fuelDeliverySubTypeID'],'transmissioncontroltypeid'=>$row['transControlTypeID'],'transmissionnumspeedsid'=>$row['transNumSpeedID'],'aspirationid'=>$row['aspirationID'],'vehicletypeid'=>$row['vehicleTypeID'],'vehiclecount'=>$row['vehicleCount']);
     }
    }
   }
  }
  $db->close();
  return $records;
 }



 function deletePartsByOID($oid)
 {
     // delete part records and all part_x mid-table records associated to the part
     // return an array of partnumbers that were deleted (likely only one)
  $db = new mysql; $db->connect(); $partnumbers=array();
  
  if($stmt=$db->conn->prepare('select partnumber from part where oid=?'))
  {// compile partnumber(s) that are to be deleted (most likely one)
   if($stmt->bind_param('s', $oid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $partnumbers[]=$row['partnumber'];
     }
    }
   }
  }

  
  if(count($partnumbers)>0)
  {
   $partnumbertemp='';

   if($stmt=$db->conn->prepare('delete from part where partnumber=?'))
   {
    $stmt->bind_param('s', $partnumbertemp);    
    foreach($partnumbers as $partnumber)
    {
     $partnumbertemp=$partnumber;
     $stmt->execute();
    }   
   }
   
   if($stmt=$db->conn->prepare('delete from part_asset where partnumber=?'))
   {
    $stmt->bind_param('s', $partnumbertemp);    
    foreach($partnumbers as $partnumber)
    {
     $partnumbertemp=$partnumber;
     $stmt->execute();
    }   
   }
   
   if($stmt=$db->conn->prepare('delete from part_attribute where partnumber=?'))
   {
    $stmt->bind_param('s', $partnumbertemp);    
    foreach($partnumbers as $partnumber)
    {
     $partnumbertemp=$partnumber;
     $stmt->execute();
    }   
   }
   
   if($stmt=$db->conn->prepare('delete from part_description where partnumber=?'))
   {
    $stmt->bind_param('s', $partnumbertemp);    
    foreach($partnumbers as $partnumber)
    {
     $partnumbertemp=$partnumber;
     $stmt->execute();
    }   
   }
   
   if($stmt=$db->conn->prepare('delete from price where partnumber=?'))
   {
    $stmt->bind_param('s', $partnumbertemp);    
    foreach($partnumbers as $partnumber)
    {
     $partnumbertemp=$partnumber;
     $stmt->execute();
    }   
   }
   
   if($stmt=$db->conn->prepare('delete from package where partnumber=?'))
   {
    $stmt->bind_param('s', $partnumbertemp);    
    foreach($partnumbers as $partnumber)
    {
     $partnumbertemp=$partnumber;
     $stmt->execute();
    }   
   }
   
   if($stmt=$db->conn->prepare('delete from interchange where partnumber=?'))
   {
    $stmt->bind_param('s', $partnumbertemp);    
    foreach($partnumbers as $partnumber)
    {
     $partnumbertemp=$partnumber;
     $stmt->execute();
    }   
   }
   
   
  } 
  
  $db->close();
  return $partnumbers;
 }


 function getDashboardEmbeds()
 {
  $records=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from dashboardembed order by sequence'))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $records[]=array('id'=>$row['id'],'description'=>$row['description'],'type'=>$row['type'],'data'=> base64_decode($row['data']),'sequence'=>$row['sequence']);
    }
   }
  }
  $db->close();
  return $records;
 }
 
 function addNotificationToQueue($notificationtype,$notificationdata)
 {
  $id=false; $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare("insert into notificationqueue values(null,'PENDING',?,?,now(),'0000-00-00')"))
  {
   $stmt->bind_param('ss',$notificationtype,$notificationdata);
   $stmt->execute();
   $id=$db->conn->insert_id;
  }
  $db->close();
  return $id;     
 } 

 function getNotificationEvents($status)
 {
  $db = new mysql; $db->connect(); $events=array();
  if($stmt=$db->conn->prepare('select * from notificationqueue where status=? order by createdDate'))
  {
   $stmt->bind_param('s', $status);      
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $events[]=array('id'=>$row['id'],'status'=>$row['status'],'type'=>$row['type'],'data'=>$row['data'],'createdDate'=>$row['createdDate'],'completedDate'=>$row['completedDate']);
    }
   }
  }
  $db->close();
  return $events;
 }
 
 function getHousekeepingRequests($requesttype)
 {
  $records=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from housekeepingrequest where requesttype=? order by id'))
  {
   $stmt->bind_param('s', $requesttype);
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $records[]=array('id'=>$row['id'],'requesttype'=>$row['requesttype'],'requestdata'=>$row['requestdata']);
    }
   }
  }
  $db->close();
  return $records;
 }
 

function deleteHousekeepingRequest($id)
{
 $db = new mysql; $db->connect();
 if($stmt=$db->conn->prepare('delete from housekeepingrequest where id=?'))
 {
  $stmt->bind_param('i', $id);
  $stmt->execute();
 }
 $db->close();
}

function addHousekeepingRequest($requesttype,$requestdata)
{
 $db = new mysql; $db->connect();
 if($stmt=$db->conn->prepare('insert into housekeepingrequest values(null,?,?)'))
 {
  $stmt->bind_param('ss', $requesttype, $requestdata);
  $stmt->execute();
 }
 $db->close();
}


 function getAuditRequests($requesttype)
 {
  $records=array();
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare('select * from auditrequest where requesttype=? order by id'))
  {
   $stmt->bind_param('s', $requesttype);
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $records[]=array('id'=>$row['id'],'requesttype'=>$row['requesttype'],'requestdata'=>$row['requestdata']);
    }
   }
  }
  $db->close();
  return $records;
 }

function deleteAuditRequest($id)
{
 $db = new mysql; $db->connect();
 if($stmt=$db->conn->prepare('delete from auditrequest where id=?'))
 {
  $stmt->bind_param('i', $id);
  $stmt->execute();
 }
 $db->close();
}

function addAuditRequest($requesttype,$requestdata)
{
 $db = new mysql; $db->connect();
 if($stmt=$db->conn->prepare('insert into auditrequest values(null,?,?)'))
 {
  $stmt->bind_param('ss', $requesttype, $requestdata);
  $stmt->execute();
 }
 $db->close();
}
 
function logAudit($audittype,$objectkeyalpha,$objectkeynumeric,$result,$oidataudit)
{
 $db = new mysql; $db->connect();
 if($stmt=$db->conn->prepare('insert into auditlog values(null,?,?,?,?,now(),?)'))
 {
  $stmt->bind_param('ssiss', $audittype,$objectkeyalpha,$objectkeynumeric,$result,$oidataudit);
  $stmt->execute();
 }
 $db->close();
}

function needAudit($audittype,$objectkeyalpha,$objectkeynumeric,$oidataudit)
{
 $db = new mysql; $db->connect(); $need=true;
 if($stmt=$db->conn->prepare('select result from auditlog where audittype=? and objectkeyalpha=? and objectkeynumeric=? and oidataudit=?'))
 {
  $stmt->bind_param('ssis', $audittype,$objectkeyalpha,$objectkeynumeric,$oidataudit);
  if($stmt->execute())
  {
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $need=false;
   }
  }
 }
 $db->close();
 return $need;
}


function partHealthScore($partnumber)
{
 // 10 points each: 
 // has package(s) with weight
 // has package(s) with dims
 // has GTIN
 // has public primary photo
 // has public non-primary photo
 // has competitive interchange
 // has apps
 // has attributes
 // has prices
 // has descriptions
    
 $db = new mysql; $db->connect(); $score=0;

 if($stmt=$db->conn->prepare("select part_asset.id from part_asset,asset where part_asset.partnumber=asset.assetId and partnumber=? and public=1 and part_asset.assettypecode='P04'"))
 {
  if($stmt->bind_param('s', $partnumber))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $score+=10;
    }
   }
  }
 }

 if($stmt=$db->conn->prepare("select part_asset.id from part_asset,asset where part_asset.partnumber=asset.assetId and partnumber=? and public=1 and part_asset.assettypecode<>'P04'"))
 {
  if($stmt->bind_param('s', $partnumber))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $score+=10;
    }
   }
  }
 }

 if($stmt=$db->conn->prepare('select price.id from price where partnumber=?'))
 {
  if($stmt->bind_param('s', $partnumber))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $score+=10;
    }
   }
  }
 }

 if($stmt=$db->conn->prepare('select part_attribute.id from part_attribute where partnumber=? and PAID>0'))
 {
  if($stmt->bind_param('s', $partnumber))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $score+=10;
    }
   }
  }
 }
  
 if($stmt=$db->conn->prepare('select id from interchange where partnumber=?'))
 {
  if($stmt->bind_param('s', $partnumber))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $score+=10;
    }
   }
  }
 }
   
 if($stmt=$db->conn->prepare('select GTIN from part where partnumber=?'))
 {
  if($stmt->bind_param('s', $partnumber))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     if(strlen(trim($row['GTIN']))==12)
     {
      $score+=10;
     }
    }
   }
  }
 }
  
 if($stmt=$db->conn->prepare('select id from package where partnumber=? and weight>0'))
 {
  if($stmt->bind_param('s', $partnumber))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $score+=10;
    }
   }
  }
 }
  
 if($stmt=$db->conn->prepare('select id from package where partnumber=? and (shippingheight+shippingwidth+shippinglength)>0'))
 {
  if($stmt->bind_param('s', $partnumber))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $score+=10;
    }
   }
  }
 }
 
  
 if($stmt=$db->conn->prepare('select id from application where partnumber=? and status=0'))
 {
  if($stmt->bind_param('s', $partnumber))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $score+=10;
    }
   }
  }
 }
 
 if($stmt=$db->conn->prepare('select id from part_description where partnumber=?'))
 {
  if($stmt->bind_param('s', $partnumber))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc())
    {
     $score+=10;
    }
   }
  }
 }

 $db->close(); 
 return $score;
}

}?>