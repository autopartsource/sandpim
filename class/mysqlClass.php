<?php
class mysql
{
 var $host = "localhost";
 var $user = 'webservice';
 var $passwd = 'OsBBVrgJKwGBH7f';
 var $dbname = 'pim';
 var $vcdbname='vcdbcache';
 var $pcdbname='pcdbcache';
 var $padbname='padbcache';
 var $qdbname= 'qdbcache';
 var $debug;
 var $conn;
 var $sql;
 var $result;

 function connect()
 {
  $error='';
  $this->conn = mysqli_connect($this->host, $this->user, $this->passwd, $this->dbname);
  if(!$this->conn)
  {
   $error='Unable to connect to "'.$this->host.'/'.$this->dbname.'" with username "'.$this->user.'" (error:'.mysqli_connect_errno().')'; 
  }
  return $error;
 }

 function connect_nodb()
 {
  $error='';
  $this->conn = mysqli_connect($this->host, $this->user, $this->passwd);
  if(!$this->conn)
  {
   $error='Unable to connect to "'.$this->host.'/'.$this->dbname.'" with username "'.$this->user.'" (error:'.mysqli_connect_errno().')'; 
  }
  return $error;
 }
 
 function close()
 {
  if($this->conn)
  {
   $this->conn->close();
  }
 }


 function testConnection()
 {// test connectivity with no specific databases named
  $success=false;
  try
  {
   if($this->conn = mysqli_connect($this->host, $this->user, $this->passwd))
   { // connection success
    $this->conn->close();
    $success=true;   
   }
   else
   {
    throw new Exception('Unable to connect');
   }
  }
  catch(Exception $e)
  {//echo $e->getMessage();
  }
  return $success;
 }
 
 
 
 
}
