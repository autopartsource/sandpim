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


 
 function userHasSelectedAppcategory($userid,$appcategory)
 {
  $returnval=false;
  $db = new mysql;  $db->connect();
  if($stmt=$db->conn->prepare("select * from user_selected_appcategory where userid=? and appcategory=?"))
  {
   $stmt->bind_param('ii',$userid,$appcategory);
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
 
 function userSelectAppcategory($userid,$appcategory)
 {
  $db = new mysql;  $db->connect();
  if($stmt=$db->conn->prepare("select * from user_selected_appcategory where userid=? and appcategory=?"))
  {
   $stmt->bind_param('ii',$userid,$appcategory);
   $stmt->execute();
   $db->result = $stmt->get_result();
   if($row = $db->result->fetch_assoc())
   { // record alredy exists

   }
   else
   {// record does not already exist
    if($stmt=$db->conn->prepare("insert into user_selected_appcategory (id,userid,appcategory) values(null,?,?)"))
    {
     $stmt->bind_param('ii',$userid,$appcategory);
     $stmt->execute();
    }
   }
  }
  $db->close();
 }

 function userUnselectAppcategory($userid,$appcategory)
 {
  $db = new mysql;  $db->connect();
  if($stmt=$db->conn->prepare("delete from user_selected_appcategory where userid=? and appcategory=?"))
  {
   $stmt->bind_param('ii',$userid,$appcategory);
   $stmt->execute();
  }
  $db->close();
 }

 
 
 
 
 function getUserVisibleAppcategories($userid)
 {
  $appcategories=array();
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare("select appcategory as id,`name`,logouri from user_appcategory,appcategory where user_appcategory.appcategory=appcategory.id and permissionname='canView' and userid=? order by `name`"))
  {
   $stmt->bind_param('i',$userid);
   $stmt->execute();
   $db->result = $stmt->get_result();
   while($row = $db->result->fetch_assoc())
   {
    $selected= $this->userHasSelectedAppcategory($userid, $row['id']);   
    $appcategories[]=array('id'=>$row['id'],'name'=>$row['name'],'logouri'=>$row['logouri'],'selected'=>$selected);
   }
  }
  $db->close();
  return $appcategories;
 }

 function setUserVisibleAppcategories($userid,$appcategories)
 {
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
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
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
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
  $db = new mysql; 
  //$db->dbname='pim'; 
  $db->connect();
  if($stmt=$db->conn->prepare("delete from user_appcategory where userid=? and appcategory=? and permissionname=?"))
  {
   $stmt->bind_param('iis',$userid,$appcategory,$permissionname);
   $stmt->execute();
  }
  $db->close();
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
    $returnvalue=array('username'=>$username,'password'=>$password);
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
