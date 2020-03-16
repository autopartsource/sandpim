<?php
include_once("mysqlClass.php");

class packaging
{

 function addPackage($partnumber,$packageuom,$quantityofeaches,$innerquantity,$innerquantityuom,$weight,$weightsuom,$packagelevelGTIN,$packagebarcodecharacters,$shippingheight,$shippingwidth,$shippinglength,$dimensionsuom)
 {
  $id=false;
  $db=new mysql; 
  $db->connect();
  if($stmt=$db->conn->prepare('insert into packages (id,partnumber,packageuom,quantityofeaches,innerquantity,innerquantityuom,weight,weightsuom,packagelevelGTIN,packagebarcodecharacters,shippingheight,shippingwidth,shippinglength,dimensionsuom) values(null,?,?,?,?,?,?,?,?,?,?,?,?,?)'))
  {
   if($stmt->bind_param('ssiisisssiiis',$partnumber,$packageuom,$quantityofeaches,$innerquantity,$innerquantityuom,$weight,$weightsuom,$packagelevelGTIN,$packagebarcodecharacters,$shippingheight,$shippingwidth,$shippinglength,$dimensionsuom))
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

 function getPackageById($packageid)
 {
  $records=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from package where id=?'))
  {
   $stmt->bind_param('i',$packageid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'packageuom'=>$row['packageuom'],'quantityofeaches'=>$row['quantityofeaches'],'innerquantity'=>$row['innerquantity'],'innerquantityuom'=>$row['innerquantityuom'],'weight'=>$row['weight'],'weightsuom'=>$row['weightsuom'],'packagelevelGTIN'=>$row['packagelevelGTIN'],'packagebarcodecharacters'=>$row['packagebarcodecharacters'],'shippingheight'=>$row['shippingheight'],'shippingwidth'=>$row['shippingwidth'],'shippinglength'=>$row['shippinglength'],'dimensionsuom'=>$row['dimensionsuom']);
   }
  }
  $db->close();
  return $records;   
 }

 function deletePackageById($packageid)
 {
  $records=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('delete from package where id=?'))
  {
   $stmt->bind_param('i',$packageid);
   $stmt->execute();
  }
  $db->close();
  return $records;   
 }
 
 function getPackagesByPartnumber($partnumber)
 {
  $records=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from package where partnumber=?'))
  {
   $stmt->bind_param('s',$partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'packageuom'=>$row['packageuom'],'quantityofeaches'=>$row['quantityofeaches'],'innerquantity'=>$row['innerquantity'],'innerquantityuom'=>$row['innerquantityuom'],'weight'=>$row['weight'],'weightsuom'=>$row['weightsuom'],'packagelevelGTIN'=>$row['packagelevelGTIN'],'packagebarcodecharacters'=>$row['packagebarcodecharacters'],'shippingheight'=>$row['shippingheight'],'shippingwidth'=>$row['shippingwidth'],'shippinglength'=>$row['shippinglength'],'dimensionsuom'=>$row['dimensionsuom']);
   }
  }
  $db->close();
  return $records;   
 }
 
 
}?>
