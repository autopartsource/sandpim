<?php
include_once("mysqlClass.php");
include_once("configGetClass.php");
include_once("configSetClass.php");

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
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  $userid=false; $environment='';
  if($stmt=$db->conn->prepare('insert into user (id,status,failedcount,`name`,username,hash,environment) values(null,1,0,?,?,?,?)'))
  {
   if($stmt->bind_param('ssss',$realname,$username,$pwd_hashed,$environment))
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
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare('update user set hash=? where id=?'))
  {
   $stmt->bind_param('si',$pwd_hashed,$userid);
   $stmt->execute();
  }
  $db->close();
 }


 function updateUserRealname($userid,$name)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
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
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
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
    $this->environment=$row['environment'];
   }
  }
  $db->close();
  return $this->id;
 }


 function getUserByID($userid)
 {
  $user=false;
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
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
  if($userid==0){return 'System';}
  $name='not found (userid '.$userid.')';
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
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
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
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

 
 function userHasSelectedPartcategory($userid,$partcategory)
 {
  $returnval=false;
  $db = new mysql;  $db->connect();
  if($stmt=$db->conn->prepare("select * from user_selected_partcategory where userid=? and partcategory=?"))
  {
   $stmt->bind_param('ii',$userid,$partcategory);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
       $returnval=true;
   }
  }
  $db->close();
  return $returnval; 
 }
 
 function userSelectPartcategory($userid,$partcategory)
 {
  $db = new mysql;  $db->connect();
  if($stmt=$db->conn->prepare("select * from user_selected_partcategory where userid=? and partcategory=?"))
  {
   $stmt->bind_param('ii',$userid,$partcategory);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   { // record alredy exists

   }
   else
   {// record does not already exist
    if($stmt=$db->conn->prepare("insert into user_selected_partcategory (id,userid,partcategory) values(null,?,?)"))
    {
     $stmt->bind_param('ii',$userid,$partcategory);
     $stmt->execute();
    }
   }
  }
  $db->close();
 }

 function userUnselectPartcategory($userid,$partcategory)
 {
  $db = new mysql;  $db->connect();
  if($stmt=$db->conn->prepare("delete from user_selected_partcategory where userid=? and partcategory=?"))
  {
   $stmt->bind_param('ii',$userid,$partcategory);
   $stmt->execute();
  }
  $db->close();
 }
 
 
 function getUserVisiblePartcategories($userid)
 {
  $partcategories=array();
  $db = new mysql;  $db->connect();
  if($stmt=$db->conn->prepare("select partcategory as id,`name`,logouri from user_partcategory,partcategory where user_partcategory.partcategory=partcategory.id and permissionname='canView' and userid=? order by `name`"))
  {
   $stmt->bind_param('i',$userid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $selected= $this->userHasSelectedPartcategory($userid, $row['id']);   
    $partcategories[]=array('id'=>$row['id'],'name'=>$row['name'],'logouri'=>$row['logouri'],'selected'=>$selected);
   }
  }
  $db->close();
  return $partcategories;
 }

 function setUserVisiblePartcategories($userid,$partcategories)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare("delete from user_partcategory where permissionname='canView' and userid=?"))
  {
   $stmt->bind_param('i',$userid);
   $stmt->execute();

   if($stmt=$db->conn->prepare("insert into user_partcategory values(null,?,?,'canView',0)"))
   {
    $category=0;
    $stmt->bind_param('ii',$userid,$category);
    foreach($partcategories as $cat)
    {
     $category=$cat;
     $stmt->execute();
    }
   }
  }
  $db->close();
 }


 function addPartcategoryToUser($userid,$partcategory,$permissionname)
 {
  $db = new mysql; $db->connect();
  if($stmt=$db->conn->prepare("insert into user_partcategory (id,userid,partcategory,permissionname,permissionvalue) values(null,?,?,?,0)"))
  {
//   $fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, $userid.','.$permissionname."\n");fclose($fp);
   $stmt->bind_param('iis',$userid,$partcategory,$permissionname);
   $stmt->execute();
  }
  $db->close();
 }

 function removePartcategoryFromUser($userid,$partcategory,$permissionname)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare("delete from user_partcategory where userid=? and partcategory=? and permissionname=?"))
  {
   $stmt->bind_param('iis',$userid,$partcategory,$permissionname);
   $stmt->execute();
  }
  $db->close();
 }
 
 function getUserPreference($userid, $preferencekey)
 {
  $value='';
  $db = new mysql;  $db->connect();
  if($stmt=$db->conn->prepare("select preferencevalue from user_preference where userid=? and preferencekey=?"))
  {
   $stmt->bind_param('is',$userid,$preferencekey);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $value=$row['preferencevalue'];
   }
  }
  $db->close();
  return $value;
 }

 
 function setUserPreference($userid, $preferencekey, $preferencevalue)
 {
  $db = new mysql;  $db->connect(); $id=-1;
  
  if($stmt=$db->conn->prepare("select id from user_preference where userid=? and preferencekey=?"))
  {
   $stmt->bind_param('is',$userid,$preferencekey);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   {
    $id=$row['id'];
   }
  }
  
  if($id >= 0)
  {// existing record for this user and preference key - update it
   if($stmt=$db->conn->prepare("update user_preference set preferencevalue=? where id=?"))
   {
    $stmt->bind_param('si',$preferencevalue,$id);
    $stmt->execute();
   }
  }
  else
  {// this is a new user/key combo - write it
   if($stmt=$db->conn->prepare("insert into user_preference values(null,?,?,?)"))
   {
    $stmt->bind_param('iss',$userid,$preferencekey, $preferencevalue);
    $stmt->execute();
   }
  }

  $db->close();
 }


 function testDatabase()
 {
  $db = new mysql; 
  return $db->testConnection();   
 }

 function installationState()
 {
    $configGet= new configGet;
    $setupusername=$configGet->getConfigValue('setupUsername');
     
    $users=array();
    
    $returnval=0;
     // 0 - fresh install. database is in default (empty) state
     // 1 - setup user established and is the only user
     // 2 - fully functional (multiple users)
     // 
    $db = new mysql; $db->connect();
    if($stmt=$db->conn->prepare('select id,username from user'))
    {  
        $stmt->execute();
        $db->result = $stmt->get_result();
        while($row = $db->result->fetch_assoc())
        {
          $users[$row['username']]=$row['id'];  
        }
    }

    if(count($users)>0)
    { // 1 or more users exist in the table
        if(count($users)==1)
        { // exactly 1 user exists
            if(array_key_exists($setupusername, $users))
            {// the lone user is the one named in the config table
                $returnval=1;
            }
            else
            {// the lone user is not the setupuser
                $returnval=2;
            }
        }
        else
        {// multiple users exist
            $returnval=2;
        }
    }
    else
    { // no users exist. system is in inital setup
        $returnval=0;
    }

    $db->close();
    return $returnval;
 }
 
 function createSetupUser()
 {
  $configGet= new configGet;
  $username=$configGet->getConfigValue('setupUsername');

  $configSet= new configSet;
  $returnvalue=false;

  if(!$username)
  { // configname 'setupUsername' does not already exist
   $charset=array('A','B','C','D','E','F','G','H','I','J','K','M','N','P','R','S','T','U','V','W','X','Y','Z','1','2','3','4','5','6','7','8','9');
   $username=''; for($i=0;$i<5;$i++){$username.=$charset[random_int(0,22)];}
   $password='';for($i=0;$i<6;$i++){$password.=$charset[random_int(23,31)];}
  
   $pepper = $configGet->getConfigValue('pepper');
   if(!$pepper)
   { // new installation - pepper value is not present - create it
    $pepper=bin2hex(random_bytes(16));
    $configSet->setConfigValue('pepper',$pepper);
   }
   $password_peppered = hash_hmac("sha256", $password, $pepper);
   $password_hashed = password_hash($password_peppered, PASSWORD_ARGON2ID);
   $userid= $this->addUser($username,$password_hashed,'Setup User');
   if($userid)
   {
    $returnvalue=array('username'=>$username,'password'=>$password,'userid'=>$userid);
    $configSet->setConfigValue('setupUsername', $username);
   }
  }
  return $returnvalue;
 }
 
 function sabotageSetupUser()
 {
     // torch the password hash of the setup user so that it can never be logged into again
    $configGet= new configGet;
    $username=$configGet->getConfigValue('setupUsername');
    if($username)
    {
        $userid=$this->getUserByUsername($username);
        if($userid)
        {
            $bogushash='$argon2id$v=19$m=65536,t=4,p=1$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
            $this->updateUserPassword($userid,$bogushash);
        }
    }
 }
 
 
}?>
