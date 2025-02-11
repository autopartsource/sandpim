<?php
include_once("mysqlClass.php");

class configGet
{

 function getAllConfigValues()
 {
  $db=new mysql; $db->connect();
  $configs=array();
  if($stmt=$db->conn->prepare('select * from config order by configname'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $configs[]=array('configname'=>$row['configname'],'configvalue'=>$row['configvalue']);
   }
  }
  $db->close();
  return $configs;
 }

 function getConfigOptions()
 {   
  $db=new mysql; $db->connect();
  $configs=array();
  if($stmt=$db->conn->prepare('select * from config_options order by configname'))
  {
   if($stmt->execute())
   {
    $db->result = $stmt->get_result();
    while($row = $db->result->fetch_assoc())
    {
     $configs[]=array('configname'=>$row['configname'],'validvalues'=>$row['validvalues'],'format'=>$row['format'],'defaultvalue'=>$row['defaultvalue'],''=>$row['description']);
    }
   }
  }
  $db->close();
  return $configs;
 }
 
 function getConfigValue($configname,$defaultvalue=false)
 {
 // if name is not found, and $defaultvalue is not false, write a new config record with the $defaultvalue
    $db=new mysql; 
    //$db->dbname='pim'; 
    $db->connect();
    $value=false;
    if($stmt=$db->conn->prepare('select * from config where configname=?'))
    {
        $stmt->bind_param('s',$configname);
        $stmt->execute();
        $db->result = $stmt->get_result();
        if($row = $db->result->fetch_assoc())
        {
            $value=$row['configvalue'];
        }
        else
        {// name not found
            if($defaultvalue)
            {// write the $configname/$defaultvalue as new record
                if($stmt=$db->conn->prepare('insert into config (configname,configvalue) values(?,?)'))
                {
                    if($stmt->bind_param('ss',$configname,$defaultvalue))
                    {
                        if($stmt->execute())
                        {
                            $value=$defaultvalue;
                        }
                    }
                }           
            }
        }
    }
    $db->close();
    return $value;
 }
}?>
