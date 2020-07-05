<?php
include_once("mysqlClass.php");

class packaging
{

 function addPackage($partnumber,$packageuom,$quantityofeaches,$innerquantity,$innerquantityuom,$weight,$weightsuom,$packagelevelGTIN,$packagebarcodecharacters,$shippingheight,$shippingwidth,$shippinglength,$dimensionsuom)
 {
  $db=new mysql; $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('insert into package (id,partnumber,packageuom,quantityofeaches,innerquantity,innerquantityuom,weight,weightsuom,packagelevelGTIN,packagebarcodecharacters,shippingheight,shippingwidth,shippinglength,dimensionsuom) values(null,?,?,?,?,?,?,?,?,?,?,?,?,?)'))
  {
   if($stmt->bind_param('ssddsdsssddds',$partnumber,$packageuom,$quantityofeaches,$innerquantity,$innerquantityuom,$weight,$weightsuom,$packagelevelGTIN,$packagebarcodecharacters,$shippingheight,$shippingwidth,$shippinglength,$dimensionsuom))
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
  $db=new mysql; $db->connect();
  $package=false;
  if($stmt=$db->conn->prepare('select * from package where id=?'))
  {
   if($stmt->bind_param('i',$packageid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $innerquantity=$this->decimalsFormat($row['innerquantity']); 
      $weight=$this->decimalsFormat($row['weight']);
      $shippinglength=$this->decimalsFormat($row['shippinglength']);
      $shippingwidth=$this->decimalsFormat($row['shippingwidth']);
      $shippingheight=$this->decimalsFormat($row['shippingheight']);
      $nicedims=''; if($shippinglength+$shippingwidth+$shippingheight>0){$nicedims=', L/W/H ('.$row['dimensionsuom'].'): '.$shippinglength.' x '.$shippingwidth.' x '.$shippingheight;}
      $nicepackage=$row['packageuom'].' '.$innerquantity.', '.$weight.' '.$row['weightsuom'].$nicedims;
      $package=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'packageuom'=>$row['packageuom'],'quantityofeaches'=>$row['quantityofeaches'],'innerquantity'=>$innerquantity,'innerquantityuom'=>$row['innerquantityuom'],'weight'=>$weight,'weightsuom'=>$row['weightsuom'],'packagelevelGTIN'=>$row['packagelevelGTIN'],'packagebarcodecharacters'=>$row['packagebarcodecharacters'],'shippingheight'=>$shippingheight,'shippingwidth'=>$shippingwidth,'shippinglength'=>$shippinglength,'dimensionsuom'=>$row['dimensionsuom'],'nicepackage'=>$nicepackage);
     }
    }
   }
  }
  $db->close();
  return $package;
 }

 function deletePackageById($packageid)
 {
  $db=new mysql; $db->connect();
  $result=array();
  if($stmt=$db->conn->prepare('delete from package where id=?'))
  {
   if($stmt->bind_param('i',$packageid))
   {
    $result=$stmt->execute();
   }
  }
  $db->close();
  return $result;   
 }
 
 function decimalsFormat($input)
 {
  $output=$input;
  while(true)
  {
   if(round($input,1)==$input){$output=round($input,1); break;}
   if(round($input,2)==$input){$output=round($input,2); break;}
   if(round($input,3)==$input){$output=round($input,3); break;}
   if(round($input,4)==$input){$output=round($input,4); break;}
   break;
  }
  return $output;
 }
 
 
 function getPackagesByPartnumber($partnumber)
 {
  $db=new mysql; $db->connect();
  $records=array();
  
  if($stmt=$db->conn->prepare('select * from package where partnumber=?'))
  {
   if($stmt->bind_param('s',$partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $innerquantity=$this->decimalsFormat($row['innerquantity']); 
      $weight=$this->decimalsFormat($row['weight']);
      $shippinglength=$this->decimalsFormat($row['shippinglength']);
      $shippingwidth=$this->decimalsFormat($row['shippingwidth']);
      $shippingheight=$this->decimalsFormat($row['shippingheight']);
      $nicedims=''; if($shippinglength+$shippingwidth+$shippingheight>0){$nicedims=', L/W/H ('.$row['dimensionsuom'].'): '.$shippinglength.' x '.$shippingwidth.' x '.$shippingheight;}
      $nicepackage=$row['packageuom'].' '.$innerquantity.', '.$weight.' '.$row['weightsuom'].$nicedims;
      $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'packageuom'=>$row['packageuom'],'quantityofeaches'=>$row['quantityofeaches'],'innerquantity'=>$innerquantity,'innerquantityuom'=>$row['innerquantityuom'],'weight'=>$weight,'weightsuom'=>$row['weightsuom'],'packagelevelGTIN'=>$row['packagelevelGTIN'],'packagebarcodecharacters'=>$row['packagebarcodecharacters'],'shippingheight'=>$shippingheight,'shippingwidth'=>$shippingwidth,'shippinglength'=>$shippinglength,'dimensionsuom'=>$row['dimensionsuom'],'nicepackage'=>$nicepackage);
     }
    }
   }
  }
  $db->close();
  return $records;   
 }
 
 function nicePackageStringByID($packageid)
 {
  $returnval='';
  if($package=$this->getPackageById($packageid))
  {
   $returnval=$package['nicepackage'];
  }
  return $returnval;
 }
 
 
}?>
