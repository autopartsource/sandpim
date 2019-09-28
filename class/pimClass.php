<?php
include_once("mysqlClass.php");

class pim
{
 function getAppsByBasevehicleid($basevehicleid,$appcategories)
 {
  $categoryarray=array(); foreach($appcategories as $appcategory){$categoryarray[]=intval($appcategory);} $categorylist=implode(',',$categoryarray); // sanitize input
  $db = new mysql; $db->dbname='pim'; $db->connect();
  $apps=array();
  if($stmt=$db->conn->prepare('select * from application where basevehicleid=? and appcategory in('.$categorylist.')'))
  {
   $stmt->bind_param('i', $basevehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $attributes=$this->getAppAttributes($row['id']);
    $apps[]=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'cosmetic'=>$row['cosmetic'],'appcategory'=>$row['appcategory'],'attributes'=>$attributes);
   }
  }
  $db->close();
  return $apps;
 }

 function getFavoriteMakes()
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
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




 function getApp($appid)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
  $app=false;
  if($stmt=$db->conn->prepare('select * from application where id=?'))
  {
   $stmt->bind_param('i', $appid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $app=array('id'=>$row['id'],'oid'=>$row['oid'],'basevehicleid'=>$row['basevehicleid'],'makeid'=>$row['makeid'],'equipmentid'=>$row['equipmentid'],'parttypeid'=>$row['parttypeid'],'positionid'=>$row['positionid'],'quantityperapp'=>$row['quantityperapp'],'partnumber'=>$row['partnumber'],'status'=>$row['status'],'internalnotes'=>'','cosmetic'=>$row['cosmetic'],'appcategory'=>$row['appcategory'],'attributes'=>array());
    $app['attributes']=$this->getAppAttributes($appid);
   }
  }
  $db->close();
  return $app;
 }

 function getAppAttributes($appid)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
  $attributes=array();
  if($stmt=$db->conn->prepare('select * from application_attribute where applicationid=?'))
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


 function getAppCategories()
 {
  $categories=array();
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare('select id,name from appcategory order by name'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $categories[]=array('id'=>$row['id'],'name'=>$row['name']);
   }
  }
  $db->close();
  return $categories;
 }

 function appCategoryName($appcategoryid)
 {
  $name='not found';
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare('select name from appcategory where id=?'))
  {
   $stmt->bind_param('i', $appcategoryid);
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

 function getBackgroundjobs($jobtype,$status)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
  $jobs=false;
  if($stmt=$db->conn->prepare('select * from backgroundjob where jobtype=? and status like ? order by datetimecreated'))
  {
   $stmt->bind_param('ss', $jobtype,$status);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    if($row['status']=='hidden'){continue;}
    $jobs[]=array('id'=>$row['id'],'jobtype'=>$row['jobtype'],'status'=>$row['status'],'userid'=>$row['userid'],'inputfile'=>$row['inputfile'],'outputfile'=>$row['outputfile'],'parameters'=>$row['parameters'],'datetimecreated'=>$row['datetimecreated'],'datetimetostart'=>$row['datetimetostart'],'datetimestarted'=>$row['datetimestarted'],'datetimeended'=>$row['datetimeended'],'percentage'=>$row['percentage']);
   }
  }
  $db->close();
  return $jobs;
 }

 function getBackgroundjob_log($jobid)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
  $events=false;
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


 function updateBackgroundjob($jobid,$status,$currenttask,$percentage,$datetimeended)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare('update backgroundjob set status=?,percentage=?,datetimeended=? where id=?'))
  {
   if($stmt->bind_param('sisi', $status,$percentage,$datetimeended,$jobid))
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

 function hideBackgroundjob($jobid)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
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







 function createBackgroundjob($jobtype,$status,$userid,$inputfile,$outputfile,$parameters,$datetimetostart)
 {
  $jobid=false;
  $db = new mysql; $db->dbname='pim'; $db->connect();

  if($stmt=$db->conn->prepare('insert into backgroundjob (id,jobtype,status,userid,inputfile,outputfile,parameters,datetimecreated,datetimetostart,datetimestarted,datetimeended,percentage) values(null,?,?,?,?,?,?,now(),?,0,0,0)'))
  {
   $stmt->bind_param('ssissss',$jobtype,$status,$userid,$inputfile,$outputfile,$parameters,$datetimetostart,);
   $stmt->execute();
   $jobid=$db->conn->insert_id;
  }else{print_r($db->conn->error);}

  $currenttask='job created';
  if($stmt=$db->conn->prepare('insert into backgroundjob_log (id,jobid,eventtext,timestamp) values(null,?,?,now())'))
  {
   $stmt->bind_param('is',$jobid,$currenttask);
   $stmt->execute();
  }else{print_r($db->conn->error);}
  $db->close();
  return $jobid;
 }







 function newoid()
 {
  $charset=array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
  $oid='';
  for($i=0;$i<10;$i++)
  {
   $oid.=$charset[random_int(0,61)];
  }
  return $oid;
 }


 function createAppFromACESsnippet($xml,$appcategory)
 {
  $db=new mysql; $db->dbname='pim'; $db->connect();
  $app_count=0;

  foreach($xml->App as $app)
  {
   if($stmt=$db->conn->prepare('insert into application (id,oid,basevehicleid,makeid,equipmentid,parttypeid,positionid,quantityperapp,partnumber,status,cosmetic,appcategory) values(null,?,?,0,0,?,?,?,?,0,0,?)'))
   {
    $oid=$this->newoid();
    $stmt->bind_param('siiiisi', $oid,$basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber,$appcategory);
    $cosmetic=0; $sequence=0; $basevehicleid=intval($app->BaseVehicle['id']); $quantityperapp=intval($app->Qty); $parttypeid=intval($app->PartType['id']); $positionid=intval($app->Position['id']); $partnumber=(string)$app->Part;
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
      $sequence++; $attribute_name=$attribute['name']; $attribute_value=$attribute['value']; $attribute_type=$attribute['type'];
      $stmt->execute(); // insert the application record
     }
    }
   }
   $app_count++;
  }
  $db->close();
  return $app_count;
 }


}?>
