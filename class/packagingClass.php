<?php
include_once("mysqlClass.php");

class packaging
{

 function addPackage($partnumber,$packageuom,$quantityofeaches,$innerquantity,$innerquantityuom,$weight,$weightsuom,$packagelevelGTIN,$packagebarcodecharacters,$shippingheight,$shippingwidth,$shippinglength,$merchandisingheight,$merchandisingwidth,$merchandisinglength,$dimensionsuom,$orderable)
 {
  $db=new mysql; $db->connect();
  $id=false;
  if($stmt=$db->conn->prepare('insert into package (id,partnumber,packageuom,quantityofeaches,innerquantity,innerquantityuom,weight,weightsuom,packagelevelGTIN,packagebarcodecharacters,shippingheight,shippingwidth,shippinglength,merchandisingheight,merchandisingwidth,merchandisinglength,dimensionsuom,orderable) values(null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'))
  {
   if($stmt->bind_param('ssddsdsssddddddss',$partnumber,$packageuom,$quantityofeaches,$innerquantity,$innerquantityuom,$weight,$weightsuom,$packagelevelGTIN,$packagebarcodecharacters,$shippingheight,$shippingwidth,$shippinglength,$merchandisingheight,$merchandisingwidth,$merchandisinglength,$dimensionsuom,$orderable))
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
      $merchandisinglength=$this->decimalsFormat($row['merchandisinglength']);
      $merchandisingwidth=$this->decimalsFormat($row['merchandisingwidth']);
      $merchandisingheight=$this->decimalsFormat($row['merchandisingheight']);
      $niceshippingdims=''; if($shippinglength+$shippingwidth+$shippingheight>0){$niceshippingdims=', Ship L*W*H ('.$row['dimensionsuom'].'): '.$shippinglength.' * '.$shippingwidth.' * '.$shippingheight;}
      $nicemerchdims=''; if($merchandisinglength+$merchandisingwidth+$merchandisingheight>0){$nicemerchdims=', Merch L*W*H ('.$row['dimensionsuom'].'): '.$merchandisinglength.' * '.$merchandisingwidth.' * '.$merchandisingheight;}
      $nicepackage=$row['packageuom'].' '.$innerquantity.', '.$weight.' '.$row['weightsuom'].$niceshippingdims.' '.$nicemerchdims;
      $package=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'packageuom'=>$row['packageuom'],'quantityofeaches'=>$row['quantityofeaches'],'innerquantity'=>$innerquantity,'innerquantityuom'=>$row['innerquantityuom'],'weight'=>$weight,'weightsuom'=>$row['weightsuom'],'packagelevelGTIN'=>$row['packagelevelGTIN'],'packagebarcodecharacters'=>$row['packagebarcodecharacters'],'shippingheight'=>$shippingheight,'shippingwidth'=>$shippingwidth,'shippinglength'=>$shippinglength, 'merchandisingheight'=>$merchandisingheight,'merchandisingwidth'=>$merchandisingwidth,'merchandisinglength'=>$merchandisinglength,'dimensionsuom'=>$row['dimensionsuom'],'orderable'=>$row['orderable'],'nicepackage'=>$nicepackage);
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
      $quantityofeaches=$this->decimalsFormat($row['quantityofeaches']);
      $weight=$this->decimalsFormat($row['weight']);
      $shippinglength=$this->decimalsFormat($row['shippinglength']);
      $shippingwidth=$this->decimalsFormat($row['shippingwidth']);
      $shippingheight=$this->decimalsFormat($row['shippingheight']);
      $merchandisinglength=$this->decimalsFormat($row['merchandisinglength']);
      $merchandisingwidth=$this->decimalsFormat($row['merchandisingwidth']);
      $merchandisingheight=$this->decimalsFormat($row['merchandisingheight']);
      $niceshippingdims=''; if($shippinglength+$shippingwidth+$shippingheight>0){$niceshippingdims='Ship: '.$shippinglength.'*'.$shippingwidth.'*'.$shippingheight.' '.$row['dimensionsuom'];}
      $nicemerchdims=''; if($merchandisinglength+$merchandisingwidth+$merchandisingheight>0){$nicemerchdims='Merch: '.$merchandisinglength.'*'.$merchandisingwidth.'*'.$merchandisingheight.' '.$row['dimensionsuom'] ;}
      $nicepackage=$row['packageuom'].' '.$innerquantity.', '.$weight.' '.$row['weightsuom'].' '.$niceshippingdims.' '.$nicemerchdims;
      
      if($row['packagelevelGTIN']!=''){$packagelevelGTINhtml='<div>Package GTIN: '.$row['packagelevelGTIN'].'</div>';}else{$packagelevelGTINhtml='';}
      if($row['packagebarcodecharacters']!=''){$packagebarcodecharactershtml='<div>Package Barcode: '.$row['packagebarcodecharacters'].'</div>';}else{$packagebarcodecharactershtml='';}        
      if($row['orderable']!=''){$orderablehtml='<div>Orderable: '.$row['orderable'].'</div>';}else{$orderablehtml='';} 
      $nicepackagehtml='<div style="padding:4px;border:1px solid black; text-align:left;"><div>Package: '.$row['packageuom'].'</div><div>Quantity of Eaches: '.$quantityofeaches.'</div><div>Weight: '.$weight.' '.$row['weightsuom'].'</div><div>Inner Quantity: '.$innerquantity.' '.$row['innerquantityuom'].'</div><div>'.$niceshippingdims.' '.$nicemerchdims.'</div>'.$packagelevelGTINhtml.$packagebarcodecharactershtml.$orderablehtml.'</div>';
      
      $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'packageuom'=>$row['packageuom'],'quantityofeaches'=>$row['quantityofeaches'],'innerquantity'=>$innerquantity,'innerquantityuom'=>$row['innerquantityuom'],'weight'=>$weight,'weightsuom'=>$row['weightsuom'],'packagelevelGTIN'=>$row['packagelevelGTIN'],'packagebarcodecharacters'=>$row['packagebarcodecharacters'],'shippingheight'=>$shippingheight,'shippingwidth'=>$shippingwidth,'shippinglength'=>$shippinglength,'merchandisingheight'=>$merchandisingheight,'merchandisingwidth'=>$merchandisingwidth,'merchandisinglength'=>$merchandisinglength,'dimensionsuom'=>$row['dimensionsuom'],'orderable'=>$row['orderable'],'nicepackage'=>$nicepackage,'nicepackagehtml'=>$nicepackagehtml);
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
