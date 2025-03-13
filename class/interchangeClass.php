<?php
include_once("mysqlClass.php");

class interchange
{
 function addInterchange($partnumber,$competitorpartnumber,$brandAAIAID,$interchangequantity,$uom,$interchangenotes,$internalnotes,$deleteexisting=false)
 {
  $id=false;
  $db=new mysql; $db->connect();

  if($deleteexisting)
  {
   if($stmt=$db->conn->prepare('delete from interchange where partnumber=? and brandAAIAID=? and interchangequantity=? and uom=?'))
   {
    if($stmt->bind_param('ssis',$partnumber,$brandAAIAID,$interchangequantity,$uom))
    {
     $stmt->execute();
    }
   }  
  }
   
  $internalnotesencoded=base64_encode($internalnotes); $interchangenotesencoded=base64_encode($interchangenotes);
  if($stmt=$db->conn->prepare('insert into interchange (id,partnumber,competitorpartnumber,brandAAIAID,interchangequantity,uom,interchangenotes,internalnotes) values(null,?,?,?,?,?,?,?)'))
  {
   if($stmt->bind_param('sssisss',$partnumber,$competitorpartnumber,$brandAAIAID,$interchangequantity,$uom,$interchangenotesencoded,$internalnotesencoded))
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

 function getInterchangeById($id)
 {
  $db=new mysql; $db->connect();
  $interchange=false;
  
  if($stmt=$db->conn->prepare('select * from interchange where id=?'))
  {
   if($stmt->bind_param('i',$id))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     if($row = $db->result->fetch_assoc())
     {
      $interchange=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'competitorpartnumber'=>$row['competitorpartnumber'],'brandAAIAID'=>$row['brandAAIAID'],'interchangequantity'=>$row['interchangequantity'],'uom'=>$row['uom'],'interchangenotes'=>base64_decode($row['interchangenotes']),'internalnotes'=>base64_decode($row['internalnotes']));
     }
    }
   }
  }
  $db->close();
  return $interchange;   
 }

 function deleteInterchangeById($id)
 {
  $db=new mysql; $db->connect();
  $result=false;
  
  if($stmt=$db->conn->prepare('delete from interchange where id=?'))
  {
   if($stmt->bind_param('i',$id))
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

 function getInterchangesByCompetitorBrand($brandAAIAID)
 {
  $db=new mysql; $db->connect();
  $interchanges=array();
  if($stmt=$db->conn->prepare('select * from interchange where brandAAIAID=?'))
  {
   $stmt->bind_param('s',$brandAAIAID);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $interchanges[]=array('partnumber'=>$row['partnumber'],'competitorpartnumber'=>$row['competitorpartnumber'],'brandAAIAID'=>$row['brandAAIAID'],'interchangequantity'=>$row['interchangequantity'],'uom'=>$row['uom'],'interchangenotes'=>base64_decode($row['interchangenotes']),'internalnotes'=>base64_decode($row['internalnotes']));
   }
  }
  $db->close();
  return $interchanges;   
 }
 
 function getInterchangeByPartnumber($partnumber)
 {
  $db=new mysql; $db->connect();
  $records=array();
  
  
  
//  if($stmt=$db->conn->prepare('select * from interchange left join brand on interchange.brandAAIAID =brand.BrandID where interchange.partnumber=? order by brand.BrandName, interchange.competitorpartnumber'))
  if($stmt=$db->conn->prepare('select * from interchange where partnumber=? order by brandAAIAID, competitorpartnumber'))
  {
   $stmt->bind_param('s',$partnumber);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $brandcode=$row['brandAAIAID']; $subbrandcode='';
    if(strlen($row['brandAAIAID'])==9 && substr($row['brandAAIAID'],4,1)=='.')
    {  // see if the brandcode is in the form xxxx.xxxx this indicates that brand.subbrand are specified - use the new brand table
     $bits=explode('.',$row['brandAAIAID']); $brandcode=$bits[0]; $subbrandcode=$bits[1];
    }
            
    $records[]=array(
        'id'=>$row['id'],
        'partnumber'=>$row['partnumber'],
        'competitorpartnumber'=>$row['competitorpartnumber'],
        'brandAAIAID'=>$brandcode,
        'subbrandAAIAID'=>$subbrandcode,
        'interchangequantity'=>$row['interchangequantity'],
        'uom'=>$row['uom'],
        'interchangenotes'=>base64_decode($row['interchangenotes']),
        'internalnotes'=>base64_decode($row['internalnotes']));
   }
  }
  $db->close();
  return $records;   
 }

 
 function getInterchangeBySearch($competitorpartnumber,$matchtype,$brandAAIAID,$limit,$parttypeid=false)
 {
  $db=new mysql; $db->connect();
  $records=array();
  
  $parttypeclause=''; if($parttypeid){$parttypeclause=' and part.parttypeid='.intval($parttypeid);}

  $searchstring=$competitorpartnumber;
  if($matchtype=='contains'){$searchstring='%'.$competitorpartnumber.'%';}
  if($matchtype=='startswith'){$searchstring=$competitorpartnumber.'%';}
  if($matchtype=='endswith'){$searchstring='%'.$competitorpartnumber;}

  $sql='select id,competitorpartnumber,brandAAIAID,part.partnumber,interchangequantity,uom,partcategory,parttypeid from interchange, part where interchange.partnumber = part.partnumber and competitorpartnumber like ? and brandAAIAID like ? '.$parttypeclause.'  order by competitorpartnumber limit ?';

  if($stmt=$db->conn->prepare($sql))
  {
   if($stmt->bind_param('ssi',$searchstring,$brandAAIAID,$limit))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
      $records[]=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'competitorpartnumber'=>$row['competitorpartnumber'],'brandAAIAID'=>$row['brandAAIAID'],'interchangequantity'=>$row['interchangequantity'],'uom'=>$row['uom'],'parttypeid'=>$row['parttypeid']);
     }
    }//else{echo ' problem with execute';}
   }//else{echo ' problem with bind';}
  }//else{echo ' problem with prepare';}
  
  $db->close();
  return $records;   
 }
 
 
 
 
 
 function getCompetitors()
 { // distinct list of competitors extracted from interchange table
     
  $db=new mysql; $db->connect();
  $records=array();
  
  if($stmt=$db->conn->prepare('select distinct brandAAIAID from interchange left join brand on interchange.brandAAIAID =brand.BrandID order by brand.BrandName;'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $records[]=array('brandAAIAID'=>$row['brandAAIAID'],'name'=>$this->brandName($row['brandAAIAID']));
   }
  }
  $db->close();
  return $records;      
 }

 function brandsubbrandName($brandAAIAID,$subbrandAAIAID)
 {
  if($subbrandAAIAID=='')
  {
   return $this->brandName($brandAAIAID);   
  }
  else
  { // subbrand is non-blank      
   return $this->brandName($brandAAIAID.'.'.$subbrandAAIAID);
  }     
 }

 function brandName($brandAAIAID)
 {
  $db=new mysql; $db->connect();
  $name='not found ('.$brandAAIAID.')';
    
  if(strlen($brandAAIAID)==9 && substr($brandAAIAID,4,1)=='.')
  {  // see if the brandcode is in the form xxxx.xxxx
     //  this indicates that brand.subbrand are specified - use the new brand table
   $bits=explode('.',$brandAAIAID); $brandcode=$bits[0]; $subbrandcode=$bits[1];
      
   if($stmt=$db->conn->prepare('select BrandName,SubBrandName from autocarebrand where BrandID=? and SubBrandID=?'))
   {
    if($stmt->bind_param('ss',$brandcode,$subbrandcode))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      if($row = $db->result->fetch_assoc())
      {
       $name=$row['BrandName'].' / '.$row['SubBrandName'];
      }
     }
    }
   }
      
  }
  else
  {// brandcode is not in xxxx.xxxx format - treat it simply as brandcode lookup
   // in the old "brand" table
      
   if($stmt=$db->conn->prepare('select BrandName from brand where BrandID=?'))
   {
    if($stmt->bind_param('s',$brandAAIAID))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      if($row = $db->result->fetch_assoc())
      {
       $name=$row['BrandName'];
      }
     }
    }
   }
  }

  $db->close();
  return $name;   
 }
 
 function validBrand($brandAAIAID)
 {     
  $db=new mysql; $db->connect();
  $returnval=false;
  
  if(strlen($brandAAIAID)==9 && substr($brandAAIAID,4,1)=='.')
  {  // see if the brandcode is in the form xxxx.xxxx
     //  this indicates that brand.subbrand are specified - use the new brand table
   $bits=explode('.',$brandAAIAID); $brandcode=$bits[0]; $subbrandcode=$bits[1];  
   if($stmt=$db->conn->prepare('select BrandName,SubBrandName from autocarebrand where BrandID=? and SubBrandID=?'))
   {
    if($stmt->bind_param('ss',$brandcode,$subbrandcode))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      if($row = $db->result->fetch_assoc())
      {
       $returnval=true;
      }
     }
    }
   }      
  }
  else
  {  // brandcode is not in xxxx.xxxx format - treat it simply as brandcode lookup
     // in the old "brand" table
   if($stmt=$db->conn->prepare('select BrandName from brand where BrandID=?'))
   {
    if($stmt->bind_param('s',$brandAAIAID))
    {
     if($stmt->execute())
     {
      $db->result = $stmt->get_result();
      if($row = $db->result->fetch_assoc())
      {
       $returnval=true;
      }
     }
    }
   }
  }
  $db->close();
  return $returnval;     
 }
 
 function getBrands($searchstring)
 {
  $db = new mysql; $db->connect();
  $brands=array();
  $limit=1000;
  
  if($stmt=$db->conn->prepare('select * from brand where BrandName like ? order by BrandName limit ?'))
  {
   $stmt->bind_param('si', $searchstring,$limit);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $brands[]=array('BrandID'=>$row['BrandID'],'BrandName'=>$row['BrandName'],'BrandOwnerID'=>$row['BrandOwnerID'],'BrandOwner'=>$row['BrandOwner'],'ParentID'=>$row['ParentID'],'ParentCompany'=>$row['ParentCompany']);
   }
  }
  $db->close();
  return $brands;
 }

 
 function getCompetitivebrands()
 {
  $db=new mysql; $db->connect();
  $brands=array();
  if($stmt=$db->conn->prepare('select * from competitivebrand order by description'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $brands[]=array('brandAAIAID'=>$row['brandAAIAID'],'description'=>$row['description']);
   }
  }
  $db->close();
  return $brands;   
 }

 function addCompetitiveBrand($brandAAIAID,$description)
 {
  $db=new mysql; $db->connect();
  $success=false;
  if($stmt=$db->conn->prepare('insert into competitivebrand values(?,?)'))
  {
   if($stmt->bind_param('ss',$brandAAIAID,$description))
   {
    $success=$stmt->execute();
   }
  }
  $db->close();
  return $success;
 }
 
 function removeCompetitiveBrand($brandAAIAID)
 {
  $db=new mysql; $db->connect();
  $success=false;
  if($stmt=$db->conn->prepare('delete from competitivebrand where brandAAIAID=?'))
  {
   if($stmt->bind_param('s',$brandAAIAID))
   {
    $success=$stmt->execute();
   }
  }
  $db->close();
  return $success;
 }
 
 function importBrandTable($data, $clearfirst)
 {
  $db=new mysql; $db->connect();
  
  if($clearfirst)
  {
   $stmt=$db->conn->prepare('delete from brand');
   $stmt->execute();   
  }
  
  if($stmt=$db->conn->prepare('insert into brand values(?,?,?,?,?,?)'))
  {
   $BrandID=''; $BrandName=''; $BrandOwnerID=''; $BrandOwner=''; $ParentID=''; $ParentCompany='';
   $stmt->bind_param('ssssss',$BrandID, $BrandName, $BrandOwnerID, $BrandOwner, $ParentID, $ParentCompany);
   foreach($data as $record)
   {
    $BrandID=$record['BrandID']; $BrandName=$record['BrandName']; $BrandOwnerID=$record['BrandOwnerID']; $BrandOwner=$record['BrandOwner']; $ParentID=$record['ParentID']; $ParentCompany=$record['ParentCompany'];
    $stmt->execute();
   }
  }
  $db->close(); 
 } 
 
}?>
