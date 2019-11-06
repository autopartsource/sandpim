<?php
include_once("mysqlClass.php");

class config
{

 function getAllConfigValues()
 {
  $db=new mysql; $db->dbname='pim'; $db->connect();
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

    function getConfigValue($configname,$defaultvalue=false)
    {
	echo '*'.$configname.','.$defaultvalue.'*';
     // if name is not found, and $defaultvalue is not false, write a new config record with the $defaultvalue
        $db=new mysql; $db->dbname='pim'; $db->connect();
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

 function setConfigValue($configname,$configvalue)
 {
  $db=new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare('select * from config where configname=?'))
  {
   $stmt->bind_param('s',$configname);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row=$db->result->fetch_assoc())
   { // existing record
    if($stmt=$db->conn->prepare('update config set configvalue=? where configname=?'))
    {
     $stmt->bind_param('ss',$configname,$configvalue);
     $stmt->execute();
    }
   }
   else
   { // non-existing record
    if($stmt=$db->conn->prepare('insert into config values(?,?)'))
    {
     $stmt->bind_param('ss',$configname,$configvalue);
     $stmt->execute();
    }
   }
  }
  $db->close();
 }


}?>