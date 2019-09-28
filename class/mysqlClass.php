<?php
class mysql
{
 var $host = "localhost";
 var $user = 'webservice';
 var $passwd = 'OsBBVrgJKw';
 var $dbname = '';
 var $debug;
 var $conn;
 var $sql;
 var $result;

 function connect()
 {
  $this->conn = mysqli_connect($this->host, $this->user, $this->passwd, $this->dbname);
  if(!$this->conn)
  {
   echo "Error: Unable to connect to pim." . PHP_EOL;
//   echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
//   echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
  }
 }

 function close()
 {
  if($this->conn)
  {
   $this->conn->close();
  }
 }


}
