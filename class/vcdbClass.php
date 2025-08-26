<?php
include_once("mysqlClass.php");

class vcdb
{
 public $vcdbversion;
    
 public function __construct($_vcdbversion=false)
 {
  $this->vcdbversion=$_vcdbversion;  // default to the hard-coded dbname from the class file (prob "vcdb")
  if(!$_vcdbversion)
  { // no secific vsersion was passed in. Consult pim database for the name
    // of the active vcdb database. It will be something like vcdb20210827
      
   $db = new mysql; $db->connect();
   if($stmt=$db->conn->prepare("select configvalue from config where configname='vcdbProductionDatabase'"))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      if($row = $db->result->fetch_assoc())
      {
       $this->vcdbversion=$row['configvalue'];
      }
     }
    }
    $db->close();
   }
  }  
 }
/*
 function getMakes()
 {
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
*/
 function getMakes($name=false)
 {
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
  $makes=array();

  $searchterm='%'; if($name){$searchterm=$name;}
  
  if($stmt=$db->conn->prepare('SELECT MakeID,MakeName FROM Make where MakeName like ? order by MakeName'))
  {
   $stmt->bind_param('s', $searchterm);
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

 
 
 
 
 
 
 
 function getModels($makeid,$regionid=false)
 {
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
  $models=array();
  

  if($regionid===false)
  {// no region was passed - leave it out of the modelname query
   $sql='select distinct ModelName, Model.ModelID from BaseVehicle,Make,Model where BaseVehicle.MakeID = Make.MakeID and BaseVehicle.ModelID = Model.ModelID and Make.MakeID = ? and Model.VehicleTypeID in(5,6,7,2187) ORDER BY Modelname';
  }
  else
  {// a region was passed - include it in the modelname query
   $sql='select distinct ModelName, Model.ModelID from BaseVehicle,Make,Model,Vehicle where BaseVehicle.MakeID = Make.MakeID and BaseVehicle.ModelID = Model.ModelID and BaseVehicle.BaseVehicleID=Vehicle.BaseVehicleID and Vehicle.RegionID=? and Make.MakeID =? and Model.VehicleTypeID in(5,6,7,2187) ORDER BY Modelname';
  }
  
//  if($stmt=$db->conn->prepare('select distinct ModelName, Model.ModelID from BaseVehicle,Make,Model where BaseVehicle.MakeID = Make.MakeID and BaseVehicle.ModelID = Model.ModelID and Make.MakeID = ? and Model.VehicleTypeID in(5,6,7,2187) ORDER BY Modelname'))
  if($stmt=$db->conn->prepare($sql))
  {
   if($regionid===false)
   {// no region was passed
    $stmt->bind_param('i', $makeid);
   }
   else
   {// a region was passed
    $stmt->bind_param('ii', $regionid, $makeid);       
   }
   
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
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
 
 function vehicleTypeName($vehicletypeid)
 {
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
  $name='not found';
  if($stmt=$db->conn->prepare('select VehicleTypeName from VehicleType where VehicleTypeID=?'))
  {
   $stmt->bind_param('i', $vehicletypeid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $name=$row['VehicleTypeName'];
   }
  }
  $db->close();
  return $name;
 }
 
 

 function niceMMYofBasevid($basevehicelid)
 {
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname; 
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname; 
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
 
 function getAllBaseVehicles()
 {
  $db = new mysql; $db->dbname=$db->vcdbname; 
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;} $db->connect();
  $basevehicles=array();
  
  if($stmt=$db->conn->prepare('SELECT BaseVehicle.BaseVehicleID, MakeName, ModelName,YearID,VehicleTypeID FROM BaseVehicle,Make,Model WHERE BaseVehicle.MakeID=Make.MakeID AND BaseVehicle.ModelID=Model.ModelID'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $basevehicles[$row['BaseVehicleID']] = array('makename'=>$row['MakeName'],'modelname'=>$row['ModelName'],'year'=>$row['YearID'],'vehicletypeid'=>$row['VehicleTypeID']);
   }
  }
  $db->close();
  return $basevehicles;   
 }

 function regionIDofRegionName($regionname)
 {
  $db = new mysql; $db->dbname=$db->vcdbname; if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;} $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('SELECT RegionID from Region WHERE RegionName = ?'))
  {
   $stmt->bind_param('s', $regionname);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id = $row['RegionID'];
   }
  }
  $db->close();
  return $id;
 }

 function bedlengthIDofBedlength($bedlength)
 {
  $db = new mysql; $db->dbname=$db->vcdbname; if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;} $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('SELECT BedLengthID from BedLength WHERE BedLength = ?'))
  {
   $stmt->bind_param('s', $bedlength);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id = $row['BedLengthID'];
   }
  }
  $db->close();
  return $id;
 }
 
 function bedtypeIDofBedtypeName($bedtypename)
 {
  $db = new mysql; $db->dbname=$db->vcdbname; if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;} $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('SELECT BedTypeID from BedType WHERE BedTypeName = ?'))
  {
   $stmt->bind_param('s', $bedtypename);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id = $row['BedTypeID'];
   }
  }
  $db->close();
  return $id;
 }
 
 
 
 function bodynumdoorsIDofBodyNumDoors($bodynumdoors)
 {
  $db = new mysql; $db->dbname=$db->vcdbname; if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;} $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('Select BodyNumDoorsID from BodyNumDoors WHERE BodyNumDoors = ?'))
  {
   $stmt->bind_param('s', $bodynumdoors);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id = $row['BodyNumDoorsID'];
   }
  }
  $db->close();
  return $id;
 }
  
 function bodytypeIDofBodyTypeName($bodytypename)
 {
  $db = new mysql; $db->dbname=$db->vcdbname; if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;} $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('select BodyTypeID from BodyType WHERE BodyTypeName = ?'))
  {
   $stmt->bind_param('s', $bodytypename);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id = $row['BodyTypeID'];
   }
  }
  $db->close();
  return $id;
 }
 
 function brakeabsIDofBrakeAbsName($brakeabsname)
 {
  $db = new mysql; $db->dbname=$db->vcdbname; if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;} $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('SELECT BrakeABSID from BrakeABS WHERE BrakeABSName = ?'))
  {
   $stmt->bind_param('s', $brakeabsname);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id = $row['BrakeABSID'];
   }
  }
  $db->close();
  return $id;
 }

 
 function brakesystemIDofBrakeAbsName($brakesystemname)
 {
  $db = new mysql; $db->dbname=$db->vcdbname; if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;} $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('SELECT BrakeSystemID from BrakeSystem WHERE  BrakeSystemName = ?'))
  {
   $stmt->bind_param('s', $brakesystemname);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id = $row['BrakeSystemID'];
   }
  }
  $db->close();
  return $id;
 }
 
 
 
 
 function niceVCdbAttributePair($attributePair)
 {
  $db=new mysql; $db->dbname=$db->vcdbname; if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;} $db->connect(); $value=$attributePair['value'];
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

  if($attributePair['name'] == 'FuelType')
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

  if($attributePair['name'] == 'BrakeSystem')
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
 function getACESattributesForBasevehicle($basevehicleid, $includeunknowns=false)
 {
  $attributesList=array(); $wildcard='U/K'; if($includeunknowns){$wildcard='';}
  $vehicles = $this->getVehiclesForBaseVehicleid($basevehicleid);
  foreach($vehicles as $vehicle) 
  {
   $attributesList[]=array('name'=>'SubModel', 'value'=>$vehicle['submodelid'],'display'=>$vehicle['submodelname']);
   $attributesList[]=array('name'=>'Region', 'value'=>$vehicle['regionid'],'display'=>$vehicle['regionname']);       
   $engines = $this->getEnginesForVehicleid($vehicle['vehicleid']);  
   $drivetypes = $this->getDriveTypesForVehicleid($vehicle['vehicleid']);
   $brakeconfigs = $this->getBrakeConfigsForVehicleid($vehicle['vehicleid']);
   $bodystyles = $this->getBodyStylesForVehicleid($vehicle['vehicleid']);
   $bodycodes = $this->getBodyCodesForVehicleid($vehicle['vehicleid']);
   $transmissions = $this->getTransmissionsForVehicleid($vehicle['vehicleid']);
   $wheelbases = $this->getWheelbasesForVehicleid($vehicle['vehicleid']);
   $steerings = $this->getSteeringsForVehicleid($vehicle['vehicleid']);
   $beds = $this->getBedsForVehicleid($vehicle['vehicleid']);
   $springs = $this->getSpringsForVehicleid($vehicle['vehicleid']);

   foreach($engines as $engine)
   {
    $attributesList[]=array('name'=>'EngineBase','value'=>$engine['enginebaseid'],'display'=>$engine['blocktype'].$engine['cylinders'].' '.$engine['liter'].'L ('.$engine['cc'].' cc)');
     
    if($engine['enginedesignationname']!='-' && $engine['enginedesignationname']!=$wildcard)
    {
     $attributesList[]=array('name'=>'EngineDesignation','value'=>$engine['enginedesignationid'],'display'=>$engine['enginedesignationname']);
    }
    if($engine['enginevinname']!='-' && $engine['enginevinname']!=$wildcard && $engine['enginevinname']!='N/A' && $engine['enginevinname']!='N/R') { $attributesList[]=array('name'=>'EngineVIN','value'=>$engine['enginevinid'],'display'=>$engine['enginevinname']);}
    if($engine['engineversion']!='-' && $engine['engineversion']!=$wildcard && $engine['engineversion']!='N/A' && $engine['engineversion']!='N/R') { $attributesList[]=array('name'=>'EngineVersion','value'=>$engine['engineversionid'],'display'=>$engine['engineversion']);}

    if($engine['mfrname']!=$wildcard){$attributesList[]=array('name'=>'EngineMfr','value'=>$engine['enginemfrid'],'display'=>$engine['mfrname']); }
    if($engine['fueldeliverytypename']!=$wildcard && $engine['fueldeliverytypename']!='-'){$attributesList[]=array('name'=>'FuelDeliveryType','value'=>$engine['fueldeliverytypeid'],'display'=>$engine['fueldeliverytypename']); }
    if($engine['aspirationname']!=$wildcard && $engine['aspirationname']!='-'){$attributesList[]=array('name'=>'Aspiration','value'=>$engine['aspirationid'],'display'=>$engine['aspirationname']); }
    if($engine['cylinderheadtypename']!=$wildcard && $engine['cylinderheadtypename']!='N/R'){$attributesList[]=array('name'=>'CylinderHeadType','value'=>$engine['cylinderheadtypeid'],'display'=>$engine['cylinderheadtypename']); }
    if($engine['fueltypename']!=$wildcard){$attributesList[]=array('name'=>'FuelType','value'=>$engine['fueltypeid'],'display'=>$engine['fueltypename']);}
    if($engine['valvesperengine']!='-' && $engine['valvesperengine']!='N/R' && $engine['valvesperengine']!=$wildcard){$attributesList[]=array('name'=>'ValvesPerEngine','value'=>$engine['valvesid'],'display'=>$engine['valvesperengine']);}
   }

   foreach($drivetypes as $drivetype)
   {
    if($drivetype['value']!=$wildcard){$attributesList[]=array('name'=>'DriveType','value'=>$drivetype['drivetypeid'], 'display'=>$drivetype['value']);}
   }
//print_r($brakeconfigs);
   foreach($brakeconfigs as $brakeconfig)
   {
    if($brakeconfig['frontbraketypename']!=$wildcard){$attributesList[]=array('name'=>'FrontBrakeType','value'=>$brakeconfig['frontbraketypeid'],'display'=>'Front '.$brakeconfig['frontbraketypename']);}
    if($brakeconfig['rearbraketypename']!=$wildcard){$attributesList[]=array('name'=>'RearBrakeType','value'=>$brakeconfig['rearbraketypeid'],'display'=>'Rear '.$brakeconfig['rearbraketypename']);}
    if($brakeconfig['brakeabsname']!=$wildcard && $brakeconfig['brakeabsname']!='N/A'){$attributesList[]=array('name'=>'BrakeABS','value'=>$brakeconfig['brakeabsid'],'display'=>$brakeconfig['brakeabsname']);}
    if($brakeconfig['brakesystemname']!=$wildcard){$attributesList[]=array('name'=>'BrakeSystem','value'=>$brakeconfig['brakesystemid'],'display'=>$brakeconfig['brakesystemname']);}
   }

   foreach($bodystyles as $bodystyle)
   {
    if($bodystyle['bodynumdoors']!=$wildcard){$attributesList[]=array('name'=>'BodyNumDoors','value'=>$bodystyle['bodynumdoorsid'],'display'=>$bodystyle['bodynumdoors'].' Door'); }
    if($bodystyle['bodytypename']!=$wildcard){$attributesList[]=array('name'=>'BodyType','value'=>$bodystyle['bodytypeid'],'display'=>$bodystyle['bodytypename']);}
   }

   foreach($bodycodes as $bodycode)
   {
    if($bodycode['mfrbodycodename']!='N/A' && $bodycode['mfrbodycodename']!=$wildcard){$attributesList[]=array('name'=>'MfrBodyCode','value'=>$bodycode['mfrbodycodeid'],'display'=>$bodycode['mfrbodycodename']);}
   }
  
   foreach($transmissions as $transmission)
   {
    if($transmission['transmissioncontroltypename']!=$wildcard && $transmission['transmissioncontroltypename']!='N/A' && $transmission['transmissioncontroltypename']!='N/R'){$attributesList[]=array('name'=>'TransmissionControlType','value'=>$transmission['transmissioncontroltypeid'],'display'=>$transmission['transmissioncontroltypename']);}
    if($transmission['transmissionnumspeeds']!=$wildcard && $transmission['transmissionnumspeeds']!='N/A' && $transmission['transmissionnumspeeds']!='N/R'){$attributesList[]=array('name'=>'TransmissionNumSpeeds','value'=>$transmission['transmissionnumspeedsid'],'display'=>$transmission['transmissionnumspeeds'].' Speed');}
    if($transmission['transmissiontypename']!='N/A' && $transmission['transmissiontypename']!=$wildcard){$attributesList[]=array('name'=>'TransmissionType','value'=>$transmission['transmissiontypeid'],'display'=>$transmission['transmissiontypename']);}
    if($transmission['transmissionmfrcode']!='N/A' && $transmission['transmissionmfrcode']!=$wildcard){$attributesList[]=array('name'=>'TransmissionMfrCode','value'=>$transmission['transmissionmfrcodeid'],'display'=>$transmission['transmissionmfrcode']);}
   }

   foreach($wheelbases as $wheelbase)
   {
    if($wheelbase['wheelbase']!='-' && $wheelbase['wheelbase']!=$wildcard && $wheelbase['wheelbase']!='N/A'){$attributesList[]=array('name'=>'WheelBase','value'=>$wheelbase['wheelbaseid'],'display'=>$wheelbase['wheelbase'].' inches');}
   }

   foreach($steerings as $steering)
   {
    if($steering['steeringtypename']!=$wildcard && $steering['steeringtypename']!='N/A'){$attributesList[]=array('name'=>'SteeringType','value'=>$steering['steeringtypeid'],'display'=>$steering['steeringtypename']); }
    if($steering['steeringsystemname']!=$wildcard && $steering['steeringsystemname']!='N/A'){$attributesList[]=array('name'=>'SteeringSystem','value'=>$steering['steeringsystemid'],'display'=>$steering['steeringsystemname']);}
   }

   foreach($beds as $bed)
   {
    
    if($bed['bedtypename']!='N/A' && $bed['bedtypename']!='N/R' && $bed['bedtypename']!=$wildcard)
    {
     $attributesList[]=array('name'=>'BedType','value'=>$bed['bedtypeid'],'display'=>$bed['bedtypename'].' Bed');
    }
    if($bed['bedlength']!='N/A' && $bed['bedlength']!='N/R' && $bed['bedlength']!=$wildcard)
    {
     $attributesList[]=array('name'=>'BedLength','value'=>$bed['bedlengthid'],'display'=>'Bed Length: '.$bed['bedlength']);
    }
   }

   foreach($springs as $spring)
   {
    if($spring['frontspringtypename']!=$wildcard){$attributesList[]=array('name'=>'FrontSpringType','value'=>$spring['frontspringtypeid'],'display'=>'Front '.$spring['frontspringtypename']);}
    if($spring['rearspringtypename']!=$wildcard){$attributesList[]=array('name'=>'RearSpringType','value'=>$spring['rearspringtypeid'],'display'=>'Rear '.$spring['rearspringtypename']);}
   }
   
  }
  
  //ksort($attributesList);
  return $attributesList;
 }


 function getEnginesForVehicleid($vehicleid)
 {
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname; 
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname; 
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
  $brakeconfigs=array();

  if($stmt=$db->conn->prepare('SELECT BrakeConfig.BrakeSystemID, BrakeSystem.BrakeSystemName, BrakeConfig.BrakeABSID, BrakeABS.BrakeABSName, BrakeConfig.FrontBrakeTypeID, BrakeType.BrakeTypeName as FrontBrakeTypeName, BrakeConfig.RearBrakeTypeID, BrakeTypeAgain.BrakeTypeName as RearBrakeTypeName FROM Vehicle join VehicleToBrakeConfig on Vehicle.VehicleID=VehicleToBrakeConfig.VehicleID join BrakeConfig on VehicleToBrakeConfig.BrakeConfigID=BrakeConfig.BrakeConfigID join BrakeSystem on BrakeConfig.BrakeSystemID=BrakeSystem.BrakeSystemID  join BrakeABS on BrakeConfig.BrakeABSID=BrakeABS.BrakeABSID join BrakeType on BrakeConfig.FrontBrakeTypeID=BrakeType.BrakeTypeID join BrakeType BrakeTypeAgain on BrakeConfig.RearBrakeTypeID=BrakeTypeAgain.BrakeTypeID  WHERE Vehicle.VehicleID=?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $brakeconfigs[]=array("frontbraketypename"=>$row['FrontBrakeTypeName'],"rearbraketypename"=>$row['RearBrakeTypeName'],"brakeabsname"=>$row['BrakeABSName'],"brakesystemname"=>$row['BrakeSystemName'],"brakesystemid"=>$row['BrakeSystemID'],"brakeabsid"=>$row['BrakeABSID'],"frontbraketypeid"=>$row['FrontBrakeTypeID'],"rearbraketypeid"=>$row['RearBrakeTypeID']);
   }
  }
  $db->close();
  return $brakeconfigs;
}


 function getBodyStylesForVehicleid($vehicleid)
 {
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname; 
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname; 
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname; 
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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
  $db = new mysql; $db->dbname=$db->vcdbname;
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
  $springs=array();
  if($stmt=$db->conn->prepare('SELECT SpringTypeConfig.FrontSpringTypeID,FrontSpringType.SpringTypeName as FrontSpringTypeName,SpringTypeConfig.RearSpringTypeID,RearSpringType.SpringTypeName as RearSpringTypeName FROM Vehicle join VehicleToSpringTypeConfig on Vehicle.VehicleID=VehicleToSpringTypeConfig.VehicleID join SpringTypeConfig on VehicleToSpringTypeConfig.SpringTypeConfigID=SpringTypeConfig.SpringTypeConfigID join SpringType FrontSpringType on SpringTypeConfig.FrontSpringTypeID=FrontSpringType.SpringTypeID join SpringType RearSpringType on SpringTypeConfig.RearSpringTypeID=RearSpringType.SpringTypeID WHERE Vehicle.VehicleID=?'))
  {
   $stmt->bind_param('i', $vehicleid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $springs[]=array('frontspringtypeid'=>$row['FrontSpringTypeID'],'frontspringtypename'=>$row['FrontSpringTypeName'], 'rearspringtypeid'=>$row['RearSpringTypeID'],'rearspringtypename'=>$row['RearSpringTypeName']);
   }
  }
  $db->close();
  return $springs;
 }

 function getTransmissionsForVehicleid($vehicleid)
 {
  $db = new mysql; $db->dbname=$db->vcdbname; 
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
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

 function getEbayVehicleStuff($basevehicleid,$regionid)
 {
  $db = new mysql; $db->dbname=$db->vcdbname; 
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
  $results=array();
  if($stmt=$db->conn->prepare('select RegionName, Region.RegionID, MakeName,ModelName,YearID,SubModelName, SubModel.SubModelID, EngineBaseID, EngineConfig2.EngineBlockID,Liter,CC,CID, BlockType,Cylinders,AspirationName, Aspiration.AspirationID,CylinderHeadTypeName, CylinderHeadType.CylinderHeadTypeID,FuelTypeName, FuelType.FuelTypeID,BodyTypeName, BodyType.BodyTypeID,BodyNumDoors, BodyNumDoors.BodyNumDoorsID  from BaseVehicle,Vehicle,Make,Model,SubModel,Region,VehicleToEngineConfig, EngineConfig2,EngineBlock,VehicleToBodyStyleConfig,BodyStyleConfig,BodyType,BodyNumDoors,Aspiration,CylinderHeadType,FuelType where BaseVehicle.BaseVehicleID=Vehicle.BaseVehicleID and BaseVehicle.ModelID=Model.ModelID and BaseVehicle.MakeID =Make.MakeID and Vehicle.SubmodelID =SubModel.SubModelID and Vehicle.RegionID = Region.RegionID  and Vehicle.VehicleID=VehicleToEngineConfig.VehicleID  and VehicleToEngineConfig.EngineConfigID =EngineConfig2.EngineConfigID and EngineConfig2.EngineBlockID =EngineBlock.EngineBlockID and Vehicle.VehicleID = VehicleToBodyStyleConfig.VehicleID and VehicleToBodyStyleConfig.BodyStyleConfigID =BodyStyleConfig.BodyStyleConfigID and BodyStyleConfig.BodyNumDoorsID =BodyNumDoors.BodyNumDoorsID and BodyStyleConfig.BodyTypeID =BodyType.BodyTypeID and EngineConfig2.AspirationID =Aspiration.AspirationID and EngineConfig2.CylinderHeadTypeID =CylinderHeadType.CylinderHeadTypeID and EngineConfig2.FuelTypeID =FuelType.FuelTypeID and  BaseVehicle.BaseVehicleID=? and Vehicle.RegionID=? order by SubModelName, Liter, BodyTypeName'))
  {
   $stmt->bind_param('ii', $basevehicleid,$regionid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $results[]=array('regionid'=>$row['RegionID'],'makename'=>$row['MakeName'],'modelname'=>$row['ModelName'],'year'=>$row['YearID'],'submodelname'=>$row['SubModelName'],'submodelid'=>$row['SubModelID'],'enginebaseid'=>$row['EngineBaseID'],'engineblockid'=>$row['EngineBlockID'],'liter'=>$row['Liter'],'cc'=>$row['CC'],'cid'=>$row['CID'],'blocktype'=>$row['BlockType'],'clyinders'=>$row['Cylinders'],'aspirationname'=>$row['AspirationName'],'aspirationid'=>$row['AspirationID'],'cylinderHeadtypename'=>$row['CylinderHeadTypeName'],'cylinderheadtypeid'=>$row['CylinderHeadTypeID'],'fueltypename'=>$row['FuelTypeName'],'fueltypeid'=>$row['FuelTypeID'],'bodytypename'=>$row['BodyTypeName'],'bodytypeid'=>$row['BodyTypeID'],'bodynumdoors'=>$row['BodyNumDoors'],'bodyNumdoorsid'=>$row['BodyNumDoorsID']);
   }
  }
  $db->close();
  return $results;
 }

 
 
 
 
 function version()
 {
  $versiondate='not found';
  $db = new mysql; 
  $db->dbname=$db->vcdbname; 
  if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;}
  $db->connect();
  if($stmt=$db->conn->prepare('select VersionDate from Version'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $versiondate=$row['VersionDate'];
   }
  }
  $db->close();
  return $versiondate;
 }
 
 function integrityCheck()
 {
  $db = new mysql; $db->dbname=$db->vcdbname; if($this->vcdbversion!==false){$db->dbname=$this->vcdbversion;} $db->connect();
  $results=array();
  $targets=array();
  $targets[]=array('description'=>'BaseVehicle/Model','sql'=>'select BaseVehicle.ModelID as id from BaseVehicle left join Model on BaseVehicle.ModelID = Model.ModelID where Model.ModelID is null');
  $targets[]=array('description'=>'BaseVehicle/Make','sql'=>'select BaseVehicle.BaseVehicleID as id from BaseVehicle left join Make on BaseVehicle.MakeID = Make.MakeID where Make.MakeID is null');
  $targets[]=array('description'=>'BaseVehicle/Year','sql'=>'select BaseVehicle.BaseVehicleID as id from BaseVehicle left join Year on BaseVehicle.YearID = Year.YearID where Year.YearID is null');
  $targets[]=array('description'=>'Vehicle/BaseVehicle','sql'=>'select Vehicle.VehicleID as id from Vehicle left join BaseVehicle on Vehicle.BaseVehicleID = BaseVehicle.BaseVehicleID where BaseVehicle.BaseVehicleID is null');
  $targets[]=array('description'=>'Vehicle/SubModel','sql'=>'select Vehicle.VehicleID as id from Vehicle left join SubModel on Vehicle.SubmodelID = SubModel.SubModelID where SubModel.SubModelID is null');
  $targets[]=array('description'=>'Vehicle/Region','sql'=>'select Vehicle.VehicleID as id from Vehicle left join Region on Vehicle.RegionID=Region.RegionID where Region.RegionID is null;');
  $targets[]=array('description'=>'Vehicle/PublicationStage','sql'=>'select Vehicle.VehicleID as id from Vehicle left join PublicationStage on Vehicle.PublicationStageID=PublicationStage.PublicationStageID where PublicationStage.PublicationStageID is null');
  $targets[]=array('description'=>'VehicleToEngineConfig/Vehicle','sql'=>'select VehicleToEngineConfig.VehicleToEngineConfigID as id from VehicleToEngineConfig left join Vehicle on VehicleToEngineConfig.VehicleID = Vehicle.VehicleID where Vehicle.VehicleID is null');     
  $targets[]=array('description'=>'VehicleToBedConfig/Vehicle','sql'=>'select VehicleToBedConfig.VehicleToBedConfigID as id from VehicleToBedConfig left join Vehicle on VehicleToBedConfig.VehicleID =Vehicle.VehicleID where Vehicle.VehicleID is null');
  $targets[]=array('description'=>'VehicleToBodyConfig/Vehicle','sql'=>'select VehicleToBodyConfig.VehicleToBodyConfigID as id from VehicleToBodyConfig left join Vehicle on VehicleToBodyConfig.VehicleID =Vehicle.VehicleID where Vehicle.VehicleID is null');
  $targets[]=array('description'=>'VehicleToBodyStyleConfig/Vehicle','sql'=>'select VehicleToBodyStyleConfig.VehicleToBodyStyleConfigID as id from VehicleToBodyStyleConfig left join Vehicle on VehicleToBodyStyleConfig.VehicleID =Vehicle.VehicleID where Vehicle.VehicleID is null');
  $targets[]=array('description'=>'VehicleToBrakeConfig/Vehicle','sql'=>'select VehicleToBrakeConfig.VehicleToBrakeConfigID as id from VehicleToBrakeConfig left join Vehicle on VehicleToBrakeConfig.VehicleID = Vehicle.VehicleID where Vehicle.VehicleID is null');
  $targets[]=array('description'=>'VehicleToDriveType/Vehicle','sql'=>'select VehicleToDriveType.VehicleToDriveTypeID as id from VehicleToDriveType left join Vehicle on VehicleToDriveType.VehicleID=Vehicle.VehicleID where Vehicle.VehicleID is null');
  $targets[]=array('description'=>'VehicleToDriveType/DriveType','sql'=>'select VehicleToDriveType.VehicleToDriveTypeID as id from VehicleToDriveType left join DriveType on VehicleToDriveType.DriveTypeID=DriveType.DriveTypeID where DriveType.DriveTypeID is null');
  $targets[]=array('description'=>'VehicleToMfrBodyCode/Vehicle','sql'=>'select VehicleToMfrBodyCode.VehicleToMfrBodyCodeID as id from VehicleToMfrBodyCode left join Vehicle on VehicleToMfrBodyCode.VehicleID = Vehicle.VehicleID where Vehicle.VehicleID is null');
  $targets[]=array('description'=>'VehicleToSpringTypeConfig/Vehicle','sql'=>'select VehicleToSpringTypeConfig.VehicleToSpringTypeConfigID as id from VehicleToSpringTypeConfig left join Vehicle on VehicleToSpringTypeConfig.VehicleID = Vehicle.VehicleID where Vehicle.VehicleID is null');
  $targets[]=array('description'=>'VehicleToSpringTypeConfig/SpringTypeConfig','sql'=>'select VehicleToSpringTypeConfig.VehicleToSpringTypeConfigID as id from VehicleToSpringTypeConfig left join SpringTypeConfig on VehicleToSpringTypeConfig.SpringTypeConfigID=SpringTypeConfig.SpringTypeConfigID where SpringTypeConfig.SpringTypeConfigID is null');
  $targets[]=array('description'=>'SpringTypeConfig/SpringType (front)','sql'=>'select SpringTypeConfig.SpringTypeConfigID as id from SpringTypeConfig left join SpringType on SpringTypeConfig.FrontSpringTypeID=SpringType.SpringTypeID where SpringType.SpringTypeID is null');
  $targets[]=array('description'=>'SpringTypeConfig/SpringType (rear)','sql'=>'select SpringTypeConfig.SpringTypeConfigID as id from SpringTypeConfig left join SpringType on SpringTypeConfig.RearSpringTypeID=SpringType.SpringTypeID where SpringType.SpringTypeID is null');
  $targets[]=array('description'=>'VehicleToSteeringConfig/Vehicle','sql'=>'select VehicleToSteeringConfig.VehicleToSteeringConfigID as id from VehicleToSteeringConfig left join Vehicle on VehicleToSteeringConfig.VehicleID = Vehicle.VehicleID where Vehicle.VehicleID is null');
  $targets[]=array('description'=>'VehicleToTransmission/Vehicle','sql'=>'select VehicleToTransmission.VehicleToTransmissionID as id from VehicleToTransmission left join Vehicle on VehicleToTransmission.VehicleID =Vehicle.VehicleID where Vehicle.VehicleID is null');
  $targets[]=array('description'=>'VehicleToWheelbase/Vehicle','sql'=>'select VehicleToWheelbase.VehicleToWheelbaseID as id from VehicleToWheelbase left join Vehicle on VehicleToWheelbase.VehicleID =Vehicle.VehicleID where Vehicle.VehicleID is null');
  $targets[]=array('description'=>'VehicleToWheelbase/WheelBase','sql'=>'select VehicleToWheelbase.VehicleToWheelbaseID as id from VehicleToWheelbase left join WheelBase on VehicleToWheelbase.WheelbaseID=WheelBase.WheelBaseID where WheelBase.WheelBaseID is null');
  $targets[]=array('description'=>'VehicleToBedConfig/BedConfig','sql'=>'select VehicleToBedConfig.VehicleToBedConfigID as id from VehicleToBedConfig left join BedConfig on VehicleToBedConfig.BedConfigID = BedConfig.BedConfigID where BedConfig.BedConfigID is null');
  $targets[]=array('description'=>'BedConfig/BedLength','sql'=>'select BedConfig.BedConfigID as id from BedConfig left join BedLength on BedConfig.BedLengthID = BedLength.BedLengthID where BedLength.BedLengthID is null');
  $targets[]=array('description'=>'BedConfig/BedType','sql'=>'select BedConfig.BedConfigID as id from BedConfig left join BedType on BedConfig.BedTypeID = BedType.BedTypeID where BedType.BedTypeID is null');
  $targets[]=array('description'=>'BodyStyleConfig/BodyNumDoors','sql'=>'select BodyStyleConfig.BodyStyleConfigID as id from BodyStyleConfig left join BodyNumDoors on BodyStyleConfig.BodyNumDoorsID =BodyNumDoors.BodyNumDoorsID where BodyNumDoors.BodyNumDoorsID is null');
  $targets[]=array('description'=>'BodyStyleConfig/BodyType','sql'=>'select BodyStyleConfig.BodyStyleConfigID as id from BodyStyleConfig left join BodyType on BodyStyleConfig.BodyTypeID = BodyType.BodyTypeID where BodyType.BodyTypeID is null');
  $targets[]=array('description'=>'VehicleToMfrBodyCode/MfrBodyCode','sql'=>'select VehicleToMfrBodyCode.VehicleToMfrBodyCodeID as id from VehicleToMfrBodyCode left join MfrBodyCode on VehicleToMfrBodyCode.MfrBodyCodeID =MfrBodyCode.MfrBodyCodeID where MfrBodyCode.MfrBodyCodeID is null');
  $targets[]=array('description'=>'VehicleToBrakeConfig/BrakeConfig','sql'=>'select VehicleToBrakeConfig.VehicleToBrakeConfigID as id from VehicleToBrakeConfig left join BrakeConfig on VehicleToBrakeConfig.BrakeConfigID = BrakeConfig.BrakeConfigID where BrakeConfig.BrakeConfigID is null');
  $targets[]=array('description'=>'BrakeConfig/BrakeType (front)','sql'=>'select BrakeConfig.BrakeConfigID as id from BrakeConfig left join BrakeType on BrakeConfig.FrontBrakeTypeID = BrakeType.BrakeTypeID where BrakeType.BrakeTypeID is null');
  $targets[]=array('description'=>'BrakeConfig/BrakeType (rear)','sql'=>'select BrakeConfig.BrakeConfigID as id from BrakeConfig left join BrakeType on BrakeConfig.RearBrakeTypeID = BrakeType.BrakeTypeID where BrakeType.BrakeTypeID is null');
  $targets[]=array('description'=>'BrakeConfig/BrakeSystem','sql'=>'select BrakeConfig.BrakeConfigID as id from BrakeConfig left join BrakeSystem on BrakeConfig.BrakeSystemID = BrakeSystem.BrakeSystemID where BrakeSystem.BrakeSystemID is null');
  $targets[]=array('description'=>'BrakeConfig/BrakeABS','sql'=>'select BrakeConfig.BrakeConfigID as id from BrakeConfig left join BrakeABS on BrakeConfig.BrakeABSID = BrakeABS.BrakeABSID where BrakeABS.BrakeABSID is null');
  $targets[]=array('description'=>'VehicleToEngineConfig/EngineConfig','sql'=>'select VehicleToEngineConfig.VehicleToEngineConfigID as id from VehicleToEngineConfig left join EngineConfig on VehicleToEngineConfig.EngineConfigID = EngineConfig.EngineConfigID where EngineConfig.EngineConfigID is null');
  $targets[]=array('description'=>'EngineConfig/EngineDesignation','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join EngineDesignation on EngineConfig.EngineDesignationID = EngineDesignation.EngineDesignationID where EngineDesignation.EngineDesignationID is null');
  $targets[]=array('description'=>'EngineConfig/EngineVIN','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join EngineVIN on EngineConfig.EngineVINID  = EngineVIN.EngineVINID where EngineVIN.EngineVINID is null');
  $targets[]=array('description'=>'EngineConfig/Valves','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join Valves on EngineConfig.ValvesID = Valves.ValvesID where Valves.ValvesID is null');
  $targets[]=array('description'=>'EngineConfig/EngineBase','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join EngineBase on EngineConfig.EngineBaseID = EngineBase.EngineBaseID where EngineBase.EngineBaseID is null');
  $targets[]=array('description'=>'EngineConfig/FuelDeliveryConfig','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join FuelDeliveryConfig on EngineConfig.FuelDeliveryConfigID = FuelDeliveryConfig.FuelDeliveryConfigID where FuelDeliveryConfig.FuelDeliveryConfigID is null');
  $targets[]=array('description'=>'EngineConfig/Aspiration','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join Aspiration on EngineConfig.AspirationID = Aspiration.AspirationID where Aspiration.AspirationID is null');
  $targets[]=array('description'=>'EngineConfig/CylinderHeadType','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join CylinderHeadType on EngineConfig.CylinderHeadTypeID = CylinderHeadType.CylinderHeadTypeID where CylinderHeadType.CylinderHeadTypeID is null');
  $targets[]=array('description'=>'EngineConfig/FuelType','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join FuelType on EngineConfig.FuelTypeID = FuelType.FuelTypeID where FuelType.FuelTypeID is null');
  $targets[]=array('description'=>'EngineConfig/IgnitionSystemType','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join IgnitionSystemType on EngineConfig.IgnitionSystemTypeID=IgnitionSystemType.IgnitionSystemTypeID where IgnitionSystemType.IgnitionSystemTypeID is null');
  $targets[]=array('description'=>'EngineConfig/Mfr','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join Mfr on EngineConfig.EngineMfrID=Mfr.MfrID where Mfr.MfrID is null');
  $targets[]=array('description'=>'EngineConfig/EngineVersion','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join EngineVersion on EngineConfig.EngineVersionID = EngineVersion.EngineVersionID where EngineVersion.EngineVersionID is null');
  $targets[]=array('description'=>'EngineConfig/PowerOutput','sql'=>'select EngineConfig.EngineConfigID as id from EngineConfig left join PowerOutput on EngineConfig.PowerOutputID=PowerOutput.PowerOutputID where PowerOutput.PowerOutputID is null');
  $targets[]=array('description'=>'VehicleToTransmission/Transmission','sql'=>'select VehicleToTransmission.VehicleToTransmissionID as id from VehicleToTransmission left join Transmission on VehicleToTransmission.TransmissionID = Transmission.TransmissionID where Transmission.TransmissionID is null');
  $targets[]=array('description'=>'Transmission/TransmissionBase','sql'=>'select Transmission.TransmissionID as id from Transmission left join TransmissionBase on Transmission.TransmissionBaseID = TransmissionBase.TransmissionBaseID where TransmissionBase.TransmissionBaseID is null');
  $targets[]=array('description'=>'Transmission/TransmissionMfrCode','sql'=>'select Transmission.TransmissionID as id from Transmission left join TransmissionMfrCode on Transmission.TransmissionMfrCodeID=TransmissionMfrCode.TransmissionMfrCodeID where TransmissionMfrCode.TransmissionMfrCodeID is null');
  $targets[]=array('description'=>'Transmission/ElecControlled','sql'=>'select Transmission.TransmissionID as id from Transmission left join ElecControlled on Transmission.TransmissionElecControlledID=ElecControlled.ElecControlledID where ElecControlled.ElecControlledID is null');
  $targets[]=array('description'=>'Transmission/Mfr','sql'=>'select Transmission.TransmissionID as id from Transmission left join Mfr on Transmission.TransmissionMfrID = Mfr.MfrID where Mfr.MfrID is null');  
  $targets[]=array('description'=>'TransmissionBase/TransmissionType','sql'=>'select TransmissionBase.TransmissionBaseID as id from TransmissionBase left join TransmissionType on TransmissionBase.TransmissionTypeID = TransmissionType.TransmissionTypeID where TransmissionType.TransmissionTypeID is null');
  $targets[]=array('description'=>'TransmissionBase/TransmissionNumSpeeds','sql'=>'select TransmissionBase.TransmissionBaseID as id from TransmissionBase left join TransmissionNumSpeeds on TransmissionBase.TransmissionNumSpeedsID=TransmissionNumSpeeds.TransmissionNumSpeedsID where TransmissionNumSpeeds.TransmissionNumSpeedsID is null');
  $targets[]=array('description'=>'TransmissionBase/TransmissionControlType','sql'=>'select TransmissionBase.TransmissionBaseID as id from TransmissionBase left join TransmissionControlType on TransmissionBase.TransmissionControlTypeID=TransmissionControlType.TransmissionControlTypeID where TransmissionControlType.TransmissionControlTypeID is null');
//  $targets[]=array('description'=>'','sql'=>'');
//  $targets[]=array('description'=>'','sql'=>'');
//  $targets[]=array('description'=>'','sql'=>'');
//  $targets[]=array('description'=>'','sql'=>'');
//  $targets[]=array('description'=>'','sql'=>'');
  
  
  
  foreach($targets as $target)
  {  
   $db->sql = $target['sql'];
   $db->result = $db->conn->query($db->sql);
   while($row = $db->result->fetch_assoc()) 
   {
    $results[]=$target['description'].':'.$row['id'];
   }  
  }
  $db->close();
  return $results;
 }

}

?>
