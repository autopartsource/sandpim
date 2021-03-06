<?php
include_once("mysqlClass.php");

class pricing
{

 function addPrice($partnumber,$pricesheetnumber,$amount,$currency,$priceuom,$pricetype,$effectivedate,$expirationdate)
 {
  $id=false;
  $db=new mysql; $db->connect();
  
  if($stmt=$db->conn->prepare('delete from price where partnumber=? and pricesheetnumber=? and currency=? and priceuom=? and pricetype=?'))
  {
   if($stmt->bind_param('sssss',$partnumber, $pricesheetnumber, $currency, $priceuom, $pricetype))
   {
    $stmt->execute();
   }// else{print_r($db->conn->error);}
  }// else{print_r($db->conn->error);}
  
  if($stmt=$db->conn->prepare('insert into price (id,partnumber,pricesheetnumber,amount,currency,priceuom,pricetype,effectivedate,expirationdate) values(null,?,?,?,?,?,?,?,?)'))
  {
   if($stmt->bind_param('ssdsssss',$partnumber,$pricesheetnumber,$amount,$currency,$priceuom,$pricetype,$effectivedate,$expirationdate))
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

 
 function getPriceById($priceid)
 {
  $db=new mysql; $db->connect();
  $price=false;
  
  if($stmt=$db->conn->prepare('select * from price where id=?'))
  {
   if($stmt->bind_param('i',$priceid))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
       $niceprice= $row['pricetype'].': '.number_format($row['amount'],2).' '.$row['currency'].' '.$row['priceuom'];
       $price=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'pricesheetnumber'=>$row['pricesheetnumber'],'amount'=>$row['amount'],'currency'=>$row['currency'],'priceuom'=>$row['priceuom'],'pricetype'=>$row['pricetype'],'effectivedate'=>$row['effectivedate'],'expirationdate'=>$row['expirationdate'],'niceprice'=>$niceprice);
     }
    }
   }
  }
  $db->close();
  return $price;   
 }

 function deletePriceById($priceid)
 {
  $db=new mysql; $db->connect();
  $result=false;
  
  if($stmt=$db->conn->prepare('delete from price where id=?'))
  {
   if($stmt->bind_param('i',$priceid))
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
 
 function getPricesByPartnumber($partnumber)
 {
  $db=new mysql; $db->connect();
  $records=array();

  if($stmt=$db->conn->prepare('select * from price where partnumber=?'))
  {
   if($stmt->bind_param('s',$partnumber))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $niceprice= $row['pricetype'].': '.number_format($row['amount'],2).' '.$row['currency'].' '.$row['priceuom'];
      $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'pricesheetnumber'=>$row['pricesheetnumber'],'amount'=>$row['amount'],'currency'=>$row['currency'],'priceuom'=>$row['priceuom'],'pricetype'=>$row['pricetype'],'effectivedate'=>$row['effectivedate'],'expirationdate'=>$row['expirationdate'],'niceprice'=>$niceprice);
     }
    }
   }
  }
  $db->close();
  return $records;   
 }
 
 function getPricesheets()
 {
  $db=new mysql; $db->connect();
  $records=array();

  if($stmt=$db->conn->prepare('select * from pricesheet where expirationdate>=DATE(NOW()) order by description'))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $records[]=array('number'=>$row['pricesheetnumber'],'description'=>$row['description'],'currency'=>$row['currency'],'pricetype'=>$row['pricetype'],'effectivedate'=>$row['effectivedate'],'expirationdate'=>$row['expirationdate']);
    }
   }
  }
  $db->close();
  return $records;   
 }
 
 function getPricesheet($number)
 {
  $db=new mysql; $db->connect();
  $pricesheet=false;

  if($stmt=$db->conn->prepare('select * from pricesheet where pricesheetnumber=?'))
  {
   if($stmt->bind_param('s',$number))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $pricesheet=array('number'=>$row['pricesheetnumber'],'description'=>$row['description'],'pricetype'=>$row['pricetype'],'currency'=>$row['currency'],'effectivedate'=>$row['effectivedate'],'expirationdate'=>$row['expirationdate']);
     }
    }
   }
  }
  $db->close();
  return $pricesheet;   
 }



 
}?>
