<?php
include_once("mysqlClass.php");

class pricing
{

 function addPrice($partnumber,$pricesheetnumber,$amount,$currency,$priceuom,$pricetype,$effectivedate,$expirationdate)
 {
  $id=false;
  $db=new mysql; 
  $db->connect();
  if($stmt=$db->conn->prepare('insert into price (id,partnumber,pricesheetnumber,amount,currency,priceuom,pricetype,effectivedate,expirationdate) values(null,?,?,?,?,?,?,?,?)'))
  {
   if($stmt->bind_param('ssisssss',$partnumber,$pricesheetnumber,$amount,$currency,$priceuom,$pricetype,$effectivedate,$expirationdate))
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
  $records=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from price where id=?'))
  {
   $stmt->bind_param('i',$priceid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'pricesheetnumber'=>$row['pricesheetnumber'],'amount'=>$row['amount'],'currency'=>$row['currency'],'priceuom'=>$row['priceuom'],'pricetype'=>$row['pricetype'],'effectivedate'=>$row['effectivedate'],'expirationdate'=>$row['expirationdate']);
   }
  }
  $db->close();
  return $records;   
 }

 function deletePriceById($priceid)
 {
  $records=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('delete from price where id=?'))
  {
   $stmt->bind_param('i',$priceid);
   $stmt->execute();
  }
  $db->close();
  return $records;   
 }
 
 function getPricesByPartnumber($partnumber)
 {
  $records=false;
  $db=new mysql; 
  $db->connect();
  
  if($stmt=$db->conn->prepare('select * from price where partnumber=?'))
  {
   $stmt->bind_param('s',$partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
       $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'pricesheetnumber'=>$row['pricesheetnumber'],'amount'=>$row['amount'],'currency'=>$row['currency'],'priceuom'=>$row['priceuom'],'pricetype'=>$row['pricetype'],'effectivedate'=>$row['effectivedate'],'expirationdate'=>$row['expirationdate']);
   }
  }
  $db->close();
  return $records;   
 }
 
 
}?>
