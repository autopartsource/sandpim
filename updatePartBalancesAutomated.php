<?php
include_once('./class/userClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/configGetClass.php');

$pim = new pim;
$logs=new logs;
$errors=array();
$importcount=0;
$invalidcount=0;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol', 0, 'updatePartBalancesAutomated.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$configGet = new configGet;


if($configGet->getConfigValue('requireCredentialsForBalanceUpdate')=='yes')
{   
 $user = new user;
       
 if (isset($_POST['username']) && isset($_POST['password'])) 
 {
  $username = $_POST['username'];
  $pepper = $configGet->getConfigValue('pepper');
  $pwd = $_POST['password'];
  $pwd_peppered = hash_hmac("sha256", $pwd, $pepper);
  if ($userid = $user->getUserByUsername($username)) 
  { // known user - now verify password
   if (!password_verify($pwd_peppered, $user->hash))
   {   // log the login event
    $logs->logSystemEvent('loginfailure', $userid, 'updatePartBalancesAutomated.php - failed login (invalid password) from '.$_SERVER['REMOTE_ADDR']);
    $response=array('message'=>'authentication failed');
    echo json_encode($response);
    exit;
   }
   
   // successfull auth
   
  }
  else
  { // unknown user
   $logs->logSystemEvent('loginfailure', 0, 'updatePartBalancesAutomated.php - unknow user ('.$username.') from '.$_SERVER['REMOTE_ADDR']);
   $response=array('message'=>'authentication failed');
   echo json_encode($response);
   exit;
  }
 }
 else
 { // missing user or password in post
  $logs->logSystemEvent('loginfailure', 0, 'updatePartBalancesAutomated.php - missing user or password in post vars from '.$_SERVER['REMOTE_ADDR']);
  $response=array('message'=>'username and password are requred post vars');
  echo json_encode($response);
  exit;
 }
}



if(isset($_POST['input'])) 
{    
 $input = $_POST['input'];
 $records = explode("\r\n", $_POST['input']);
 foreach ($records as $record) 
 {
  $fields = explode("\t", $record);  
  if(count($fields)==1 && $fields[0]==''){continue;}
    
  if(count($fields) == 3 || count($fields) == 4) 
  {
   $partnumber = trim(strtoupper($fields[0]));
   $qoh= (double)filter_var($fields[1],FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
   $amd= (double)filter_var($fields[2],FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
   $cost=0; if(count($fields) == 4){$cost=(double)filter_var($fields[3],FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);}
   
      
   if(strlen($partnumber) <= 20 && strlen($partnumber) > 0 && $pim->validPart($partnumber)) 
   {
    $pim->updatePartBalance($partnumber, $qoh, $amd, $cost); 
    $importcount++;
   }
   else
   {// invalid part - make a note of it
    // $errors[]='invalid partnumber ['.$partnumber.']'; // avoid filling up log storage with the hundreds of potential invalid items several times a day
    $invalidcount++;
   }
  }
  else
  {// field count is wrong
   $errors[]='Field count was wrong (expected exactly 3 or 4 tab-delimited columns)';
  }
 }
}
else
{
 $errors[]='No form variable named input found. POST must be url-encoded (application/x-www-form-urlencoded) form data in a variable named input';
}

$logs->logSystemEvent('externalsystem', 0, 'Bulk import of '.$importcount.' via updatePartBalancesAutomated.php. '.implode(';',$errors));

$response=array('message'=>'Successful balance imports: '.$importcount.'; invalid items: '.$invalidcount.'; '.implode(';',$errors));
echo json_encode($response);
?>
