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
  if($stmt=$db->conn->prepare('SELECT MakeName, ModelName,YearID FROM BaseVehicle,Make,Model WHERE BaseVehicle.MakeID=Make.MakeID AND BaseVehicle.ModelID=Model.ModelID AND BaseVehicle.BaseVehicleID = ?'))
  {
   $stmt->bind_param('i', $basevehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $mmy = array('makename'=>$row['MakeName'],'modelname'=>$row['ModelName'],'year'=>$row['YearID']);
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


}
?>
