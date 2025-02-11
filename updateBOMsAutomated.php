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
$changedcount=0;
$badparts=array();

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol', 0, 'updateBOMsAutomated.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
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
    $logs->logSystemEvent('loginfailure', $userid, 'updateBOMsAutomated.php - failed login (invalid password) from '.$_SERVER['REMOTE_ADDR']);
    $response=array('message'=>'authentication failed');
    echo json_encode($response);
    exit;
   }
   
   // successfull auth
   
  }
  else
  { // unknown user
   $logs->logSystemEvent('loginfailure', 0, 'updateBOMsAutomated.php - unknow user ('.$username.') from '.$_SERVER['REMOTE_ADDR']);
   $response=array('message'=>'authentication failed');
   echo json_encode($response);
   exit;
  }
 }
 else
 { // missing user or password in post
  $logs->logSystemEvent('loginfailure', 0, 'updateBOMsAutomated.php - missing user or password in post vars from '.$_SERVER['REMOTE_ADDR']);
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
  if(count($fields)<2){continue;}
  
  $partnumber = trim(strtoupper($fields[0]));
      
  if($pim->validPart($partnumber)) 
  {
   $bom=array();
   
   for($i=1; $i<=count($fields)-1;$i++)
   {
    $subfields=explode('|',$fields[$i]);
    if(count($subfields)==4)
    {
     $bom[]=array('partnumber'=>$subfields[0], 'units'=>round($subfields[1],2), 'uom'=>$subfields[2],'sequence'=>$subfields[3]);
    }
   }
   
   if(count($bom)>0)
   {
    $result=$pim->addPartBOM($partnumber, $bom);
    if($result)
    {
      $changedcount++;        
    }
    $importcount++;
   }
  }
  else
  {// invalid part - make a note of it
   // $errors[]='invalid partnumber ['.$partnumber.']'; // avoid filling up log storage with the hundreds of potential invalid items several times a day
   $invalidcount++;
   $badparts[]=$partnumber;
  }
  
 }
}
else
{
 $errors[]='No form variable named input found. POST must be url-encoded (application/x-www-form-urlencoded) form data in a variable named input';
}

$badpartlist=implode(',',$badparts); if(strlen($badpartlist)>100){$badpartlist=substr($badpartlist, 100).'...';}

$logs->logSystemEvent('externalsystem', 0, 'Bulk import of '.$importcount.'; new and changed: '.$changedcount.'; Badparts: '.$badpartlist.'; via updateBOMsAutomated.php. '.implode(';',$errors));

$response=array('message'=>'Successful BOM imports: '.$importcount.'; new and changed: '.$changedcount.'  ; invalid items('.$invalidcount.'): '.$badpartlist.'; '.implode(';',$errors));
echo json_encode($response);
?>
