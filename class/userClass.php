<?php
include_once("mysqlClass.php");


class user
{
 public $id;
 public $name;
 public $username;
 public $failedcount;
 public $hash;
 public $status;

 function addUser($username,$pwd_hashed,$realname)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
  $userid=false;
  if($stmt=$db->conn->prepare('insert into user (id,status,failedcount,`name`,username,hash) values(null,1,0,?,?,?)'))
  {
   if($stmt->bind_param('sss',$realname,$username,$pwd_hashed))
   {
    if($stmt->execute())
    {
     $userid=$db->conn->insert_id;
    }
   }
  }
  $db->close();
  return $userid;
 }


 function updateUserPassword($userid,$pwd_hashed)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare('update user set hash=? where id=?'))
  {
   $stmt->bind_param('si',$pwd_hashed,$userid);
   $stmt->execute();
  }
  $db->close();
 }


 function updateUserRealname($userid,$name)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare('update user set `name`=? where id=?'))
  {
   $stmt->bind_param('si',$name,$userid);
   $stmt->execute();
  }
  $db->close();
 }


 function getUserByUsername($username)
 {
  $user=false;
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare('select * from user where username=?'))
  {
   $stmt->bind_param('s',$username);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   { // username was found.
    $this->name=$row['name'];
    $this->id=$row['id'];
    $this->status=$row['status'];
    $this->hash=$row['hash'];
    $this->failedcount=$row['failedcount'];
   }
  }
  $db->close();
  return $this->id;
 }


 function getUserByID($userid)
 {
  $user=false;
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare('select * from user where id=?'))
  {
   $stmt->bind_param('i',$userid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   { // userid was found.
    $this->name=$row['name'];
    $this->id=$row['id'];
    $this->status=$row['status'];
    $this->hash=$row['hash'];
    $this->failedcount=$row['failedcount'];
   }
  }
  $db->close();
  return $this->id;
 }

 function realNameOfUserid($userid)
 {
  $name='not found (userid '.$userid.')';
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare('select `name` from user where id=?'))
  {
   $stmt->bind_param('i',$userid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $name=$row['name'];
   }
  }
  $db->close();
  return $name;
 }

 function getUsers()
 {
  $users=array();
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare('select * from user order by username'))
  {
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $users[]=array('id'=>$row['id'],'username'=>$row['username'],'name'=>$row['name'],'status'=>$row['status'],'failedcount'=>$row['failedcount']);
   }
  }
  $db->close();
  return $users;
 }


 function getUserVisibleAppcategories($userid)
 {
  $appcategories=array();
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare("select appcategory as id,`name` from user_appcategory,appcategory where user_appcategory.appcategory=appcategory.id and permissionname='canView' and userid=? order by `name`"))
  {
   $stmt->bind_param('i',$userid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $appcategories[]=array('id'=>$row['id'],'name'=>$row['name']);
   }
  }
  $db->close();
  return $appcategories;
 }

 function setUserVisibleAppcategories($userid,$appcategories)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare("delete from user_appcategory where permissionname='canView' and userid=?"))
  {
   $stmt->bind_param('i',$userid);
   $stmt->execute();

   if($stmt=$db->conn->prepare("insert into user_appcategory values(null,?,?,'canView',0)"))
   {
    $category=0;
    $stmt->bind_param('ii',$userid,$category);
    foreach($appcategories as $cat)
    {
     $category=$cat;
     $stmt->execute();
    }
   }
  }
  $db->close();
 }


 function addAppcategoryToUser($userid,$appcategory,$permissionname)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare("insert into user_appcategory (id,userid,appcategory,permissionname,permissionvalue) values(null,?,?,?,0)"))
  {
//   $fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $userid.','.$appcategory.','.$permissionname."\n");fclose($fp);
   $stmt->bind_param('iis',$userid,$appcategory,$permissionname);
   $stmt->execute();
  }
  $db->close();
 }

 function removeAppcategoryFromUser($userid,$appcategory,$permissionname)
 {
  $db = new mysql; $db->dbname='pim'; $db->connect();
  if($stmt=$db->conn->prepare("delete from user_appcategory where userid=? and appcategory=? and permissionname=?"))
  {
   $stmt->bind_param('iis',$userid,$appcategory,$permissionname);
   $stmt->execute();
  }
  $db->close();
 }

}?>
