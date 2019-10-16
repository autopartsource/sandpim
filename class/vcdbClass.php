<?php
include_once("mysqlClass.php");

class vcdb
{

 function getMakes()
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $db->sql = "SELECT MakeID,MakeName FROM Make order by MakeName";
  $db->result = $db->conn->query($db->sql);
  $makes=array();
  while($row = $db->result->fetch_assoc()) 
  {
   $makes[]=array('id'=>$row['MakeID'],'name'=>$row['MakeName']);
  }
  $db->close();
  return $makes;
 }

 function getModels($makeid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $models=array();
  if($stmt=$db->conn->prepare('select distinct ModelName, Model.ModelID from BaseVehicle,Make,Model where BaseVehicle.MakeID = Make.MakeID and BaseVehicle.ModelID = Model.ModelID and Make.MakeID = ? and Model.VehicleTypeID in(5,6,7,2187) ORDER BY Modelname'))
  {
   $stmt->bind_param('i', $makeid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $models[] = array('id'=>$row['ModelID'],'name'=>$row['ModelName']);
   }
  }
  $db->close();
  return $models;
 }



 function getYears($makeid,$modelid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $years=array();
  if($stmt=$db->conn->prepare('select distinct YearID from BaseVehicle,Make,Model where BaseVehicle.MakeID = Make.MakeID and BaseVehicle.ModelID = Model.ModelID and Make.MakeID = ? and Model.ModelID = ? order by YearID DESC'))
  {
   $stmt->bind_param('ii', $makeid,$modelid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $years[] = array("id"=>$row['YearID']);
   }
  }
  $db->close();
  return $years;
 }

 function makeName($makeid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $name='not found';
  if($stmt=$db->conn->prepare('select MakeName from Make where MakeID=?'))
  {
   $stmt->bind_param('i', $makeid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $name=$row['MakeName'];
   }
  }
  $db->close();
  return $name;
 }

 function modelName($modelid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $name='not found';
  if($stmt=$db->conn->prepare('select ModelName from Model where ModelID=?'))
  {
   $stmt->bind_param('i', $modelid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $name=$row['ModelName'];
   }
  }
  $db->close();
  return $name;
 }

 function niceMMYofBasevid($basevehicelid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $nice='not found';
  if($stmt=$db->conn->prepare('select MakeName,ModelName,YearID from BaseVehicle,Make,Model where Make.MakeID=BaseVehicle.MakeID and Model.ModelID=BaseVehicle.ModelID and BaseVehicle.BaseVehicleID=? order by MakeName, ModelName, YearID'))
  {
   $stmt->bind_param('i', $basevehicelid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $nice=$row['MakeName'].', '.$row['ModelName'].', '.$row['YearID'];
   }
  }else{echo 'problem with prepare:'.$db->conn->error;}
  $db->close();
  return $nice;
 }



 function getVehiclesForBaseVehicleid($basevehicleid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $vehicles=array();
  if($stmt=$db->conn->prepare('select Vehicle.VehicleID,Vehicle.SubModelID,SubModelName,Vehicle.RegionID, RegionName from Vehicle,SubModel,Region where Vehicle.SubModelID=SubModel.SubModelID and Vehicle.RegionID=Region.RegionID AND BaseVehicleID=? ORDER BY SubModelName, Vehicle.RegionID'))
  {
   $stmt->bind_param('i', $basevehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $vehicles[] = array("submodelname"=>$row['SubModelName'],"regionname"=>$row['RegionName'],"regionid"=>$row['RegionID'],"vehicleid"=>$row['VehicleID'],"submodelid"=>$row['SubModelID']);
   }
  }
  $db->close();
  return $vehicles;
 }

 function getBasevehicleidForMidMidYid($makeid,$modelid,$yearid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('SELECT BaseVehicleID FROM BaseVehicle WHERE MakeID=? and ModelID=? and YearID=?'))
  {
   $stmt->bind_param('iii', $makeid,$modelid,$yearid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id=$row['BaseVehicleID'];
   }
  }
  $db->close();
  return $id;
 }

 function getBasevehicleidForMMY($make,$model,$year)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('SELECT BaseVehicle.BaseVehicleID FROM BaseVehicle,Make,Model WHERE BaseVehicle.MakeID=Make.MakeID  AND BaseVehicle.ModelID=Model.ModelID AND MakeName = ? AND ModelName = ? AND YearID = ?'))
  {
   $stmt->bind_param('sss', $make,$model,$year);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id=$row['BaseVehicleID'];
   }
  }
  $db->close();
  return $id;
 }


 function getVehicelMMY($vehicleid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $returnval=false;
  if($stmt=$db->conn->prepare('select MakeName,ModelName,YearID,SubModelName, RegionName from Vehicle, BaseVehicle, Make, Model, SubModel, Region where Vehicle.BaseVehicleID = BaseVehicle.BaseVehicleID and BaseVehicle.MakeID = Make.MakeID and BaseVehicle.ModelID = Model.ModelID and Vehicle.SubModelID = SubModel.SubModelID and Vehicle.RegionID = Region.RegionID and Vehicle.VehicleID = ?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row =  $db->result->fetch_assoc())
   {
    $returnval=array('makename'=>$row['MakeName'],'modelname'=>$row['ModelName'],'year'=>$row['YearID'],'submodelname'=>$row['SubModelName'],'regionname'=>$row['RegionName']);
   }
  }
  $db->close();
  return $returnval;
 }


 function getMMYforBasevehicleid($basevehicleid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $mmy=false;
  if($stmt=$db->conn->prepare('SELECT  BaseVehicle.MakeID, BaseVehicle.ModelID, MakeName, ModelName,YearID FROM BaseVehicle,Make,Model WHERE BaseVehicle.MakeID=Make.MakeID AND BaseVehicle.ModelID=Model.ModelID AND BaseVehicle.BaseVehicleID = ?'))
  {
   $stmt->bind_param('i', $basevehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $mmy = array('makename'=>$row['MakeName'],'modelname'=>$row['ModelName'],'year'=>$row['YearID'],'MakeID'=>$row['MakeID'],'ModelID'=>$row['ModelID']);
   }
  }
  $db->close();
  return $mmy;
 }

 function niceVCdbAttributePair($attributePair)
 {
  $db=new mysql; $db->dbname='vcdb'; $db->connect(); $value=$attributePair['value'];
  $nicevalue='unknown - '.$attributePair['name'].'='.$attributePair['value'];

  if($attributePair['name'] == 'SubModel')
  {
   if($stmt=$db->conn->prepare('SELECT SubmodelName from SubModel WHERE SubmodelID = ?'))
   {
    $stmt->bind_param('i',$value); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['SubmodelName']; if(strlen($row['SubmodelName'])<2){$nicevalue.=' Submodel';}}
   }
  }

  if($attributePair['name'] == 'MfrBodyCode')
  {
   if($stmt=$db->conn->prepare('Select MfrBodyCodeName from MfrBodyCode WHERE MfrBodyCodeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue='Body code '.$row['MfrBodyCodeName'];}
   }
  }

  if($attributePair['name'] == 'BodyNumDoors')
  {
   if($stmt=$db->conn->prepare('Select BodyNumDoors from BodyNumDoors WHERE BodyNumDoorsID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['BodyNumDoors'].' Door';}
   }
  }

  if($attributePair['name'] == 'BodyType')
  {
   if($stmt=$db->conn->prepare('select BodyTypeName from BodyType WHERE BodyTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['BodyTypeName'];}
   }
  }

  if($attributePair['name'] == 'DriveType')
  {
   if($stmt=$db->conn->prepare('select DriveTypeName from DriveType WHERE DriveTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['DriveTypeName'];}
   }
  }

  if($attributePair['name'] == 'EngineBase')
  {
   if($stmt=$db->conn->prepare('SELECT Liter,cc,cid,Cylinders,BlockType from EngineBase WHERE EngineBaseID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['BlockType'].$row['Cylinders']." ".$row['Liter']."L";}
   }
  }

  if($attributePair['name'] == 'EngineDesignation')
  {
   if($stmt=$db->conn->prepare('select EngineDesignationName from EngineDesignation WHERE EngineDesignationID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['EngineDesignationName'].' Engine';}
   }
  }

  if($attributePair['name'] == 'EngineVIN')
  {
   if($stmt=$db->conn->prepare('select EngineVINName from EngineVIN WHERE EngineVINID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue='VIN:'.$row['EngineVINName'];}
   }
  }

  if($attributePair['name'] == 'EngineVersion')
  {
   if($stmt=$db->conn->prepare('SELECT EngineVersion from EngineVersion WHERE EngineVersionID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['EngineVersion'];}
   }
  }

  if($attributePair['name'] == 'EngineMfr')
  {
   if($stmt=$db->conn->prepare('SELECT MfrName from Mfr WHERE MfrID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['MfrName'];}
   }
  }

  if($attributePair['name'] == 'FuelDeliveryType')
  {
   if($stmt=$db->conn->prepare('SELECT FuelDeliveryTypeName from FuelDeliveryType WHERE FuelDeliveryTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['FuelDeliveryTypeName'];}
   }
  }

  if($attributePair['name'] == 'FuelDeliverySubType')
  {
   if($stmt=$db->conn->prepare('SELECT FuelDeliverySubTypeName from FuelDeliverySubType WHERE FuelDeliverySubTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['FuelDeliverySubTypeName'];}
   }
  }

  if($attributePair['name'] == 'FuelSystemControlType')
  {
   if($stmt=$db->conn->prepare('SELECT FuelSystemControlTypeName from FuelSystemControlType WHERE FuelSystemControlTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['FuelSystemControlTypeName'];}
   }
  }

  if($attributePair['name'] == 'FuelSystemDesign')
  {
   if($stmt=$db->conn->prepare('SELECT FuelSystemDesigNname from FuelSystemDesign WHERE FuelSystemDesignID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['FuelSystemDesignName'];}
   }
  }

  if($attributePair['name'] == 'Aspiration')
  {
   if($stmt=$db->conn->prepare('SELECT AspirationName from Aspiration WHERE AspirationID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['AspirationName'];}
   }
  }

  if($attributePair['name'] == 'CylinderHeadType')
  {
   if($stmt=$db->conn->prepare('SELECT CylinderHeadTypeName from CylinderHeadType WHERE CylinderHeadTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['CylinderHeadTypeName'];}
   }
  }

  if($attributePair['name'] == 'FuelTypeid')
  {
   if($stmt=$db->conn->prepare('SELECT FuelTypeName from FuelType WHERE FuelTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['FuelTypeName'];}
   }
  }

  if($attributePair['name'] == 'IgnitionSystemType')
  {
   if($stmt=$db->conn->prepare('SELECT ignitionsystemtypename from ignitionsystemtype WHERE ignitionsystemtypeid = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['ignitionsystemtypename'];}
   }
  }

  if($attributePair['name'] == 'TransmissionMfrCode')
  {
   if($stmt=$db->conn->prepare('SELECT transmissionmfrcode from transmissionmfrcode WHERE transmissionmfrcodeid = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['transmissionmfrcode']." Transmission";}
   }
  }

  if($attributePair['name'] == 'TransmissionBase')
  {
   if($stmt=$db->conn->prepare('SELECT TransmissionTypeName,TransmissionNumSpeeds,TransmissionControlTypeName from TransmissionBase,TransmissionType,TransmissionNumSpeeds,TransmissionControlType WHERE TransmissionBase.TransmissionTypeID=TransmissionType.TransmissionTypeID AND TransmissionBase.TransmissionNumSpeedsID=TransmissionNumSpeeds.TransmissionNumSpeedsID AND TransmissionBase.TransmissionControlTypeID=TransmissionControlType.TransmissionControlTypeID AND TransmissionBase.TransmissionBaseID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['TransmissionControlTypeName']." ".$row['TransmissioNnumSpeeds']." Speed ".$row['TransmissionTypeName'];}
   }
  }

  if($attributePair['name'] == 'TransmissionType')
  {
   if($stmt=$db->conn->prepare('SELECT TransmissionTypeName from TransmissionType WHERE TransmissionTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['TransmissionTypeName'];}
   }
  }

  if($attributePair['name'] == 'TransmissionControlType')
  {
   if($stmt=$db->conn->prepare('SELECT TransmissionControlTypeName from TransmissionControlType WHERE TransmissionControlTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['TransmissionControlTypeName'].' Transmission';}
   }
  }

  if($attributePair['name'] == 'TransmissionNumSpeeds')
  {
   if($stmt=$db->conn->prepare('SELECT TransmissionNumSpeeds from TransmissionNumSpeeds WHERE TransmissionNumSpeedsID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['TransmissionNumSpeeds']." Speed Transmission";}
   }
  }

  if($attributePair['name'] == 'TransmissionMfr')
  {
   if($stmt=$db->conn->prepare('SELECT MfrName from Mfr WHERE MfrID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['MfrName']." Transmission";}
   }
  }

  if($attributePair['name'] == 'BedLength')
  {
   if($stmt=$db->conn->prepare('SELECT BedLength from BedLength WHERE BedLengthID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['BedLength']." Inch Bed";}
   }
  }

  if($attributePair['name'] == 'BedType')
  {
   if($stmt=$db->conn->prepare('SELECT BedTypeName from BedType WHERE BedTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['BedTypeName']." Bed";}
   }
  }

  if($attributePair['name'] == 'WheelBase')
  {
   if($stmt=$db->conn->prepare('SELECT WheelBase from WheelBase WHERE WheelBaseid = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['WheelBase'].' Inch Wheelbase';}
   }
  }

  if($attributePair['name'] == 'BrakeSystemid')
  {
   if($stmt=$db->conn->prepare('SELECT BrakeSystemName from BrakeSystem WHERE BrakeSystemID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['BrakeSystemName'].' Brakes';}
   }
  }

  if($attributePair['name'] == 'FrontBrakeType')
  {
   if($stmt=$db->conn->prepare('SELECT BrakeTypeName from BrakeType WHERE BrakeTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue='Front '.$row['BrakeTypeName'];}
   }
  }

  if($attributePair['name'] == 'RearBrakeType')
  {
   if($stmt=$db->conn->prepare('SELECT BrakeTypeName from BrakeType WHERE BrakeTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue='Rear '.$row['BrakeTypeName'];}
   }
  }

  if($attributePair['name'] == 'BrakeABS')
  {
   if($stmt=$db->conn->prepare('SELECT BrakeABSName from BrakeABS WHERE BrakeABSID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['BrakeABSName'];}
   }
  }

  if($attributePair['name'] == 'Region')
  {
   if($stmt=$db->conn->prepare('SELECT RegionName from Region WHERE RegionID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['RegionName'];}
   }
  }

  if($attributePair['name'] == 'FrontSpringType')
  {
   if($stmt=$db->conn->prepare('SELECT SpringTypeName from SpringType WHERE SpringTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue='Front '.$row['SpringTypeName'].' Suspenssion';}
   }
  }

  if($attributePair['name'] == 'RearSpringType')
  {
   if($stmt=$db->conn->prepare('SELECT SpringTypeName from SpringType WHERE SpringTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue='Rear '.$row['SpringTypeName'].' Suspenssion';}
   }
  }

  if($attributePair['name'] == 'SteeringSystem')
  {
   if($stmt=$db->conn->prepare('SELECT SteeringSystemName from SteeringSystem WHERE SteeringSystemID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['SteeringSystemName'].' Steering';}
   }
  }

  if($attributePair['name'] == 'SteeringType')
  {
   if($stmt=$db->conn->prepare('SELECT SteeringTypeName from SteeringType WHERE SteeringTypeID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['SteeringTypeName'].' Steering';}
   }
  }

  if($attributePair['name'] == 'ValvesPerEngine')
  {
   if($stmt=$db->conn->prepare('SELECT ValvesPerEngine from Valves WHERE ValvesID = ?'))
   {
    $stmt->bind_param('i',$attributePair['value']); $stmt->execute(); $db->result = $stmt->get_result();
    if($row = $db->result->fetch_assoc()){$nicevalue=$row['ValvesPerEngine'].' Valve';}
   }
  }

  $db->close();
  return $nicevalue;
 }



 // return a name/value pair list for filling a select box of available attributes for a given bas vehicle
 function getACESattributesForBasevehicle($basevehicleid)
 {
  $vehicles = $this->getVehiclesForBaseVehicleid($basevehicleid);
  foreach($vehicles as $vehicle) 
  {
   $attributesList['SubModel_'.$vehicle['submodelid']]=$vehicle['submodelname'].' Submodel'; $attributesList['Region_'.$vehicle['regionid']]='Region: '.$vehicle['regionname'];
   $engines = $this->getEnginesForVehicleid($vehicle['vehicleid']);}
   $drivetypes = $this->getDriveTypesForVehicleid($vehicle['vehicleid']);
   $brakeconfigs = $this->getBrakeConfigsForVehicleid($vehicle['vehicleid']);
   $bodystyles = $this->getBodyStylesForVehicleid($vehicle['vehicleid']);
   $bodycodes = $this->getBodyCodesForVehicleid($vehicle['vehicleid']);
   $transmissions = $this->getTransmissionsForVehicleid($vehicle['vehicleid']);
   $wheelbases = $this->getWheelbasesForVehicleid($vehicle['vehicleid']);
   $steerings = $this->getSteeringsForVehicleid($vehicle['vehicleid']);
   $beds = $this->getBedsForVehicleid($vehicle['vehicleid']);
   $springs = $this->getSpringsForVehicleid($vehicle['vehicleid']);

   if(count($engines))
   {
    foreach($engines as $engine)
    {
     $attributesList['EngineBase_'.$engine['enginebaseid']]='Engine Base: '.$engine['blocktype'].$engine['cylinders'].' '.$engine['liter'].'L ('.$engine['cc'].' cc)'; if($engine['enginedesignationname']!='-')
     {
      $attributesList['EngineDesignation_'.$engine['enginedesignationid']]= 'Engine Designation: '.$engine['enginedesignationname'];
     }
     if($engine['enginevinname']!='-') { $attributesList['EngineVIN_'.$engine['enginevinid']]='Engine VIN: '.$engine['enginevinname'];}
     if($engine['engineversion']!='N/A') { $attributesList['EngineVersion_'.$engine['engineversionid']]='Engine Version: '.$engine['engineversion'];}

     $attributesList['EngineMfr_'.$engine['enginemfrid']]='Engine Mfr: '.$engine['mfrname']; 
     $attributesList['FuelDeliveryType_'.$engine['fueldeliverytypeid']]='Fuel Delivery Type: '.$engine['fueldeliverytypename']; 
     $attributesList['Aspiration_'.$engine['aspirationid']]='Aspiration: '.$engine['aspirationname']; 
     $attributesList['CylinderHeadType_'.$engine['cylinderheadtypeid']]='Cylinder Head Type: '.$engine['cylinderheadtypename']; 
     $attributesList['FuelType_'.$engine['fueltypeid']]='Fuel: '.$engine['fueltypename']; $attributesList['ValvesPerEngine_'.$engine['valvesid']]='Valves: '.$engine['valvesperengine'];
    }
   }

   if(count($drivetypes))
   {
    foreach($drivetypes as $drivetype)
    {
      $attributesList['DriveType_'.$drivetype['drivetypeid']]='Drive Type: '. $drivetype['value'];
    }
   }

   if(count($brakeconfigs))
   {
    foreach($brakeconfigs as $brakeconfig)
    {
     $attributesList['FrontBrakeType_'.$brakeconfig['frontbraketypeid']]='Front '.$brakeconfig['frontbraketypename'].' Brakes';
     $attributesList['RearBrakeType_'.$brakeconfig['rearbraketypeid']]='Rear '.$brakeconfig['rearbraketypename'].' Brakes';
     $attributesList['BrakeABS_'.$brakeconfig['brakeabsid']]=$brakeconfig['brakeabsname'];
     $attributesList['BrakeSystem_'.$brakeconfig['brakesystemid']]=$brakeconfig['brakesystemname'].' Brakes';
    }
   }

   if(count($bodystyles))
   {
    foreach($bodystyles as $bodystyle)
    {
     $attributesList['BodyNumDoors_'.$bodystyle['bodynumdoorsid']]=$bodystyle['bodynumdoors'].' Door'; $attributesList['BodyType_'.$bodystyle['bodytypeid']]=$bodystyle['bodytypename'];
    }
   }

   if(count($bodycodes))
   {
    foreach($bodycodes as $bodycode)
    {
     if($bodycode['mfrbodycodename']!='N/A')
     {
      $attributesList['MfrBodyCode_'.$bodycode['mfrbodycodeid']]='Body Code: '.$bodycode['mfrbodycodename'];
     }
    }
   }

   if(count($transmissions))
   {
    foreach($transmissions as $transmission)
    {
     $attributesList['TransmissionControlType_'.$transmission['transmissioncontroltypeid']]=$transmission['transmissioncontroltypename'].' Transmission';
     $attributesList['TransmissionNumSpeeds_'.$transmission['transmissionnumspeedsid']]=$transmission['transmissionnumspeeds'].' Speed Transmission';
     $attributesList['TransmissionType_'.$transmission['transmissiontypeid']]=$transmission['transmissiontypename'].' Transmission';
     if($transmission['transmissionmfrcode']!='N/A')
     {
      $attributesList['TransmissionMfrCode_'.$transmission['transmissionmfrcodeid']]='Transmission Mfr Code: '.$transmission['transmissionmfrcode'];
     }
    }
   }

   if(count($wheelbases))
   {
    foreach($wheelbases as $wheelbase) { if($wheelbase['wheelbase']!='-')
    {
     $attributesList['WheelBase_'.$wheelbase['wheelbaseid']]=$wheelbase['wheelbase'].' inch Wheelbase';
    }
   }

   if(count($steerings))
   {
    foreach($steerings as $steering)
    {
     $attributesList['SteeringType_'.$steering['steeringtypeid']]=$steering['steeringtypename'].' Steering'; $attributesList['SteeringSystem_'.$steering['steeringsystemid']]=$steering['steeringsystemname'].' Steering';
    }
   }

   if(count($beds))
   {
    foreach($beds as $bed)
    {
     if($bed['bedtypename']!='N/R')
     {
      $attributesList['BedType_'.$bed['bedtypeid']]=$bed['bedtypename'].' Bed';
     }

     if($bed['bedlength']!='N/R')
     {
      $attributesList['BedLength_'.$bed['bedlengthid']]='Bed Length: '.$bed['bedlength'];
     }
    }
   }

   if(count($springs))
   {
    foreach($springs as $spring)
    {
     $attributesList['FrontSpringType_'.$spring['frontspringtypeid']]='Front '.$spring['frontspringtypename'].' Springs';
     $attributesList['RearSpringType_'.$spring['rearspringtypeid']]='Rear '.$spring['rearspringtypename'].' Springs';
    }
   }
  }
  ksort($attributesList);
  return $attributesList;
 }


 function getEnginesForVehicleid($vehicleid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $engines=array();
  if($stmt=$db->conn->prepare('SELECT EngineConfig.EngineBaseID, EngineConfig.EngineDesignationID, EngineConfig.EngineVINID, EngineConfig.EngineVersionID, EngineConfig.EngineMfrID, FuelDeliveryConfig.FuelDeliveryTypeID, FuelDeliveryConfig.FuelDeliverySubTypeID, FuelDeliveryConfig.FuelSystemControlTypeID, FuelDeliveryConfig.FuelSystemDesignID, EngineConfig.AspirationID, EngineConfig.CylinderHeadTypeID, EngineConfig.FuelTypeID, EngineConfig.IgnitionSystemTypeID, EngineConfig.ValvesID, Liter, cc, cid, Cylinders, BlockType, EngBoreIn, EngBoreMetric, EngStrokeIn, EngStrokeMetric, EngineDesignationName, EngineVINName, EngineVersion, MfrName, FuelDeliveryTypeName, FuelDeliverySubTypeName, FuelSystemControlTypeName, FuelSystemDesignName, AspirationName, CylinderHeadTypeName, FuelTypeName, IgnitionSystemTypeName, ValvesPerEngine FROM Vehicle, VehicleToEngineConfig, EngineConfig, EngineBase, EngineVIN, Aspiration, CylinderHeadType, EngineVersion, EngineDesignation, FuelDeliveryConfig, FuelType, FuelDeliveryType, FuelDeliverySubType, FuelSystemControlType, FuelSystemDesign, IgnitionSystemType, Mfr, Valves WHERE Vehicle.VehicleID = VehicleToEngineConfig.VehicleID AND VehicleToEngineConfig.EngineConfigID = EngineConfig.EngineConfigID AND EngineConfig.EnginebaseID = EngineBase.EnginebaseID AND EngineConfig.EngineVINID = EngineVIN.EngineVINID AND EngineConfig.AspirationID=Aspiration.AspirationID AND EngineConfig.CylinderHeadTypeID = CylinderHeadType.CylinderHeadTypeID AND EngineConfig.EngineVersionID = EngineVersion.EngineVersionID AND EngineConfig.EngineDesignationID = EngineDesignation.EngineDesignationID AND EngineConfig.FuelTypeID = FuelType.FuelTypeID AND EngineConfig.IgnitionSystemTypeID = IgnitionSystemType.IgnitionSystemTypeID AND EngineConfig.EngineMfrID = Mfr.mfrID AND EngineConfig.FuelDeliveryConfigID = FuelDeliveryConfig.FuelDeliveryConfigID AND FuelDeliveryConfig.FuelDeliveryTypeID = FuelDeliveryType.FuelDeliveryTypeID AND FuelDeliveryConfig.FuelDeliverySubTypeID = FuelDeliverySubType.FuelDeliverySubTypeID AND FuelDeliveryConfig.FuelSystemControlTypeID = FuelSystemControlType.FuelSystemControlTypeID AND FuelDeliveryConfig.FuelsyStemDesignID = FuelSystemDesign.FuelSystemDesignID AND EngineConfig.ValvesID = Valves.ValvesID AND Vehicle.VehicleID = ?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $engines[] = array("enginebaseid"=>$row['EngineBaseID'],"enginedesignationid"=>$row['EngineDesignationID'],"enginevinid"=>$row['EngineVINID'],"engineversionid"=>$row['EngineVersionID'],"enginemfrid"=>$row['EngineMfrID'],"fueldeliverytypeid"=>$row['FuelDeliveryTypeID'],"fueldeliverysubtypeid"=>$row['FuelDeliverySubTypeID'],"fuelsystemcontroltypeid"=>$row['FuelSystemControlTypeID'],"fuelsystemdesignid"=>$row['FuelSystemDesignID'],"aspirationid"=>$row['AspirationID'],"cylinderheadtypeid"=>$row['CylinderHeadTypeID'],"fueltypeid"=>$row['FuelTypeID'],"ignitionsystemtypeid"=>$row['IgnitionSystemTypeID'],"liter"=>$row['Liter'], "cc"=>$row['cc'], "cid"=>$row['cid'], "cylinders"=>$row['Cylinders'], "blocktype"=>$row['BlockType'], "engborein"=>$row['EngBoreIn'], "engboremetric"=>$row['EngBoreMetric'], "engstrokein"=>$row['EngStrokeIn'], "engstrokemetric"=>$row['EngStrokeMetric'],"enginedesignationname"=>$row['EngineDesignationName'],"enginevinname"=>$row['EngineVINName'], "engineversion"=>$row['EngineVersion'], "mfrname"=>$row['MfrName'], "fueldeliverytypename"=>$row['FuelDeliveryTypeName'], "fueldeliverysubtypename"=>$row['FuelDeliverySubTypeName'],"fuelsystemcontroltypename"=>$row['FuelSystemControlTypeName'], "fuelsystemdesignname"=>$row['FuelSystemDesignName'], "aspirationname"=>$row['AspirationName'], "cylinderheadtypename"=>$row['CylinderHeadTypeName'], "fueltypename"=>$row['FuelTypeName'],"ignitionsystemtypename"=>$row['IgnitionSystemTypeName'],"valvesid"=>$row['ValvesID'],"valvesperengine"=>$row['ValvesPerEngine']);
   }
  }
  $db->close();
  return $engines;
 }



 function getDriveTypesForVehicleid($vehicleid)
 {

  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $drivetypes=array();

  if($stmt=$db->conn->prepare('SELECT DriveType.DriveTypeID, DriveTypeName FROM Vehicle, VehicleToDriveType, DriveType where Vehicle.VehicleID=VehicleToDriveType.VehicleID and VehicleToDriveType.DriveTypeID = DriveType.DriveTypeID AND Vehicle.VehicleID=?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $drivetypes[] = array("value"=>$row['DriveTypeName'],"drivetypeid"=>$row['DriveTypeID']);
   }
  }
  $db->close();
  return $drivetypes;
 }


function getBrakeConfigsForVehicleid($vehicleid)
{
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $brakeconfigs=array();

  if($stmt=$db->conn->prepare('SELECT BrakeConfig.BrakeSystemID, BrakeSystem.BrakeSystemName, BrakeConfig.BrakeABSID, BrakeABS.BrakeABSName, BrakeConfig.FrontBrakeTypeID, BrakeType.BrakeTypeName as FrontBrakeTypeName, BrakeConfig.RearBrakeTypeID, BrakeTypeAgain.BrakeTypeName as RearBrakeTypeName FROM Vehicle join VehicleToBrakeConfig on Vehicle.VehicleID=VehicleToBrakeConfig.VehicleID join BrakeConfig on VehicleToBrakeConfig.BrakeConfigID=BrakeConfig.BrakeConfigID join BrakeSystem on BrakeConfig.BrakeSystemID=BrakeSystem.BrakeSystemID  join BrakeABS on BrakeConfig.BrakeABSID=BrakeABS.BrakeABSID join BrakeType on BrakeConfig.FrontBrakeTypeID=BrakeType.BrakeTypeID join BrakeType BrakeTypeAgain on BrakeConfig.RearBrakeTypeID=BrakeTypeAgain.BrakeTypeID  WHERE Vehicle.VehicleID=?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $brakeconfigs[]=array("frontbraketypename"=>$row['FrontBrakeTypeName'],"rearbraketypename"=>$row['FrontBrakeTypeName'],"brakeabsname"=>$row['BrakeABSName'],"brakesystemname"=>$row['BrakeSystemName'],"brakesystemid"=>$row['BrakeSystemID'],"brakeabsid"=>$row['BrakeABSID'],"frontbraketypeid"=>$row['FrontBrakeTypeID'],"rearbraketypeid"=>$row['RearBrakeTypeID']);
   }
  }
  $db->close();
  return $brakeconfigs;
}


 function getBodyStylesForVehicleid($vehicleid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $bodystyles=array();
  if($stmt=$db->conn->prepare('SELECT BodyType.BodyTypeID,BodyNumDoors.BodyNumDoorsID,BodyTypeName,BodyNumDoors FROM Vehicle,VehicleToBodyStyleConfig,BodyStyleConfig,BodyNumDoors,BodyType WHERE Vehicle.VehicleID=VehicleToBodyStyleConfig.VehicleID AND VehicleToBodyStyleConfig.BodyStyleConfigID=BodyStyleConfig.BodyStyleConfigID AND BodyStyleConfig.BodyNumDoorsID=BodyNumDoors.BodyNumDoorsID AND BodyStyleConfig.BodyTypeID=BodyType.BodyTypeID AND Vehicle.VehicleID=?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
	$bodystyles[]=array('bodytypename'=>$row['BodyTypeName'],'bodynumdoors'=>$row['BodyNumDoors'],'bodynumdoorsid'=>$row['BodyNumDoorsID'],'bodytypeid'=>$row['BodyTypeID']);
   }
  }
  $db->close();
  return $bodystyles;
 }

 function getBodyCodesForVehicleid($vehicleid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $bodycodes=array();

  if($stmt=$db->conn->prepare('SELECT MfrBodyCodeName, MfrBodyCode.MfrBodyCodeID  FROM Vehicle,VehicleToMfrBodyCode,MfrBodyCode WHERE Vehicle.VehicleID=VehicleToMfrBodyCode.VehicleID AND VehicleToMfrBodyCode.MfrBodyCodeID=MfrBodyCode.MfrBodyCodeID AND Vehicle.VehicleID=?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $bodycodes[]=array('mfrbodycodename'=>$row['MfrBodyCodeName'],'mfrbodycodeid'=>$row['MfrBodyCodeID']);
   }
  }
  $db->close();
  return $bodycodes;
 }

 function getWheelbasesForVehicleid($vehicleid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $wheelbases=array();
  if($stmt=$db->conn->prepare('SELECT VehicleToWheelbase.WheelbaseID,Wheelbase FROM  Vehicle,VehicleToWheelbase,WheelBase WHERE Vehicle.VehicleID=VehicleToWheelbase.VehicleID  AND VehicleToWheelbase.WheelbaseID=WheelBase.WheelbaseID AND Vehicle.VehicleID=?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $wheelbases[]=array('wheelbase'=>$row['Wheelbase'],'wheelbaseid'=>$row['WheelbaseID']);
   }
  }
  $db->close();
  return $wheelbases;
 }

 function getSteeringsForVehicleid($vehicleid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $steerings=array();
  if($stmt=$db->conn->prepare('SELECT SteeringConfig.SteeringTypeID, SteeringType.SteeringTypeName, SteeringConfig.SteeringSystemID, SteeringSystem.SteeringSystemName FROM Vehicle,VehicleToSteeringConfig,SteeringConfig,SteeringType,SteeringSystem WHERE Vehicle.VehicleID=VehicleToSteeringConfig.VehicleID AND VehicleToSteeringConfig.SteeringConfigID=SteeringConfig.SteeringConfigID AND SteeringConfig.SteeringTypeID=SteeringType.SteeringTypeID AND SteeringConfig.SteeringSystemID=SteeringSystem.SteeringSystemID AND Vehicle.VehicleID=?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $steerings[]=array('steeringsystemname'=>$row['SteeringSystemName'],'steeringtypename'=>$row['SteeringTypeName'],'steeringsystemid'=>$row['SteeringSystemID'],'steeringtypeid'=>$row['SteeringTypeID']);
   }
  }
  $db->close();
  return $steerings;
 }

 function getBedsForVehicleid($vehicleid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $beds=array();
  if($stmt=$db->conn->prepare('SELECT BedConfig.BedTypeID,BedType.BedTypeName,BedConfig.BedLengthID,BedLength.BedLength FROM Vehicle,VehicleToBedConfig,BedConfig,BedLength,BedType WHERE Vehicle.VehicleID=VehicleToBedConfig.VehicleID AND VehicleToBedConfig.BedConfigID=BedConfig.BedConfigID AND BedConfig.BedLengthID=BedLength.BedLengthID AND BedConfig.BedTypeID=BedType.BedTypeID AND Vehicle.VehicleID=?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $beds[]=array('bedtypename'=>$row['BedTypeName'],'bedlength'=>$row['BedLength'],'bedtypeid'=>$row['BedTypeID'],'bedlengthid'=>$row['BedLengthID']);
   }
  }
  $db->close();
  return $beds;
 }

 function getSpringsForVehicleid($vehicleid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $springs=array();
  if($stmt=$db->conn->prepare('SELECT SpringTypeConfig.FrontSpringTypeID,FrontSpringType.SpringTypeName as FrontSpringTypeName,SpringTypeConfig.RearSpringTypeID,RearSpringType.SpringTypeName as RearSprintTypeName FROM Vehicle join VehicleToSpringTypeConfig on Vehicle.VehicleID=VehicleToSpringTypeConfig.VehicleID join SpringTypeConfig on VehicleToSpringTypeConfig.SpringTypeConfigID=SpringTypeConfig.SpringTypeConfigID join SpringType FrontSpringType on SpringTypeConfig.FrontSpringTypeID=FrontSpringType.SpringTypeID join SpringType RearSpringType on SpringTypeConfig.RearSpringTypeID=RearSpringType.SpringTypeID WHERE Vehicle.VehicleID=?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $springs[]=array('frontspringtypeid'=>$row['FrontSpringTypeID'],'frontspringtypename'=>$row['FrontSpringTypeName'], 'rearspringtypeid'=>$row['RearSpringTypeID'],'rearsprinttypename'=>$row['RearSprintTypeName']);
   }
  }
  $db->close();
  return $springs;
 }

 function getTransmissionsForVehicleid($vehicleid)
 {
  $db = new mysql; $db->dbname='vcdb'; $db->connect();
  $transmissions=array();
  if($stmt=$db->conn->prepare('SELECT TransmissionBase.TransmissionBaseID, TransmissionBase.TransmissionNumSpeedsID, TransmissionNumSpeeds.TransmissionNumSpeeds, TransmissionBase.TransmissionControlTypeID, TransmissionControlType.TransmissionControlTypeName, TransmissionBase.TransmissionTypeID, TransmissionType.TransmissionTypeName, Transmission.TransmissionMfrCodeID, TransmissionMfrCode.TransmissionMfrCode  FROM Vehicle,VehicleToTransmission,Transmission,TransmissionBase,TransmissionControlType,TransmissionMfrCode,TransmissionNumSpeeds, TransmissionType WHERE Vehicle.VehicleID=VehicleToTransmission.VehicleID AND VehicleToTransmission.TransmissionID=Transmission.TransmissionID AND Transmission.TransmissionBaseID=TransmissionBase.TransmissionBaseID AND Transmission.TransmissionMfrCodeID=TransmissionMfrCode.TransmissionMfrCodeID AND TransmissionBase.TransmissionTypeID=TransmissionType.TransmissionTypeID AND TransmissionBase.TransmissionNumSpeedsID=TransmissionNumSpeeds.TransmissionNumSpeedsID AND TransmissionBase.TransmissionControlTypeID=TransmissionControlType.TransmissionControlTypeID AND Vehicle.VehicleID=?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $transmissions[]=array('transmissionbaseid'=>$row['TransmissionBaseID'],'transmissionnumspeedsid'=>$row['TransmissionNumSpeedsID'],'transmissionnumspeeds'=>$row['TransmissionNumSpeeds'],'transmissioncontroltypeid'=>$row['TransmissionControlTypeID'],'transmissioncontroltypename'=>$row['TransmissionControlTypeName'],'transmissiontypeid'=>$row['TransmissionTypeID'],'transmissiontypename'=>$row['TransmissionTypeName'],'transmissionmfrcodeid'=>$row['TransmissionMfrCodeID'],'transmissionmfrcode'=>$row['TransmissionMfrCode']);
   }
  }
  $db->close();
  return $transmissions;
 }

}

?>
