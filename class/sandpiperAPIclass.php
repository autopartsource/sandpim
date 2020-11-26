<?php
include_once("mysqlClass.php");
include_once("configGetClass.php");
include_once('userClass.php');
include_once('logsClass.php');




// base class for sandpiper server 
class sandpiper
{
  protected $requesturi;
  protected $method;
  protected $body;
  protected $jwtpresented; // supplied by the client with the request to the endpoint


  public $response;
  protected $userid=false;
  protected $username;
  protected $secondarycompanyuuid;
  protected $primarycompanyuuid;


  protected $keyedparms=array();
    
  
  protected $limit=1;
  protected $sort='id';
  protected $sortdirection='asc';
  protected $nice=false;


  
  
  function extractParms($input)
  {// input is a string like: "activity?limit=10&sort=xyz&nice&sortdirection=desc"
      //name/value pairs will be added to $this->keyedparms, and several specific reserved name will be handled (limit,sort,sortdirection,nice)
      // return value will be everything before the first '?'
      
    $temp=explode('?',$input);
    if(isset($temp[1]) && trim($temp[1])!='')
    {// there is stuff to the right of the ?mark like:  /sandpiper/v1/activity?limit=10&sort=xyz    chop it up by & character
      // parse it into name-keyed array this->keyedparms
      // looks for several reserved named parms (limit, sort, sortdirection, nice) and sets them accordingly
      $parms=explode('&',$temp[1]);
      foreach($parms as $parm)
      {
           $parmparts=explode('=',$parm);
           $value=''; if(isset($parmparts[1])){$value=trim($parmparts[1]);}
           $this->keyedparms[trim($parmparts[0])]=$value;
      }

      if(array_key_exists('limit', $this->keyedparms)){$this->limit=intval($this->keyedparms['limit']);}
      if(array_key_exists('sort', $this->keyedparms)){$this->sort=$this->keyedparms['sort'];}
      if(array_key_exists('sortdirection', $this->keyedparms)){$this->sortdirection=$this->keyedparms['sortdirection'];}
      if(array_key_exists('nice', $this->keyedparms)){$this->nice=true;}
     }
     return $temp[0]; // the original input with the parms stripped off 
   }


   function authenticateUser($username, $password, $address=false)
   {// uses the same users as the rest of the PIM system
     $returnvalue=false;
     $logs=new logs;
     $configGet=new configGet;
     $user=new user;
     $pepper = $configGet->getConfigValue('pepper');
     $password_peppered = hash_hmac("sha256", $password, $pepper);
     if ($this->userid = $user->getUserByUsername($username))
     { // known user - now verify password
      if(password_verify($password_peppered, $user->hash))
      { // valid user and password
        $this->username=$username;
        $expiresepoch=(mktime()+900); // 15 minutes from now
        $secret=$this->getJWTsecret();
        $jwt= $this->generateJWT($this->userid, $this->username, '9f937bf7-ac59-490a-9632-45ed9562f0b3', $expiresepoch, $secret);
        $logs->logSystemEvent('login', $user->id, $user->name.' sandpiper API log in from '.$address);
        $returnvalue= json_encode(['token'=>$jwt,'expires'=>date('Y-m-d\TH:i:s-00:00',$expiresepoch),'refresh_token'=>'xxxx']);
      } 
      else
      {// log the failure event
        $logs->logSystemEvent('loginfailure', $this->userid, 'sandpiper API login failed from '.$address);
        $returnvalue='{"message":"Authentication Error."}';
      }
     }
     else
     { // unknown user
       //  burn the amount of time that a password verification would have taken had this been a known username. This is to thwart a timing attack: Baddie could determine validity of arbitrary usernames thrown at the api because they all take a similar hmac time (several hundred mS)
       $trash= password_verify('asdkjflkasjdfkl', '$argon2id$v=19$m=65536,t=4,p=1$NnBsSTgvZmpNbmdoeXo2eA$LWpqCgHuxVmgEwDMSf3o5SM1AWT7qbCtkV8ckxBCr94');      
       $logs->logSystemEvent('loginfailure', 0, 'sandpiper API unknown user ('.$username.') from '.$address);
       $returnvalue='{"message":"Authentication Error."}';
     }
     return $returnvalue; 
    }
 
    
    function generateJWT($userid,$username,$companyuuid,$expiration,$secret)
    {
      // generate JWT  -  based on the example at https://dev.to/robdwaller/how-to-create-a-json-web-token-using-php-3gml
     $encodedjwtheader = $this->base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
     $encodedjwtpayload = $this->base64url_encode(json_encode(['c'=>$companyuuid, 'id' => $userid,'u'=> $username,'exp'=>$expiration]));
     $encodedjwtsignature = $this->base64url_encode(hash_hmac('sha256', $encodedjwtheader . "." . $encodedjwtpayload, $secret, true));
     return $encodedjwtheader . "." . $encodedjwtpayload . "." . $encodedjwtsignature;
    }
    
    function verifyJWT($jwt,$extractidentity)
    {// the $extractidentity (boolean) parameter will cause the user specifics in the payload to be applied to the current instance of the sandpiper class
     $returnval=false;
     $tokenParts = explode('.', $jwt);
     if(count($tokenParts)==3)
     {
      $secret=$this->getJWTsecret();
      $header = base64_decode($tokenParts[0]);
      $payload = base64_decode($tokenParts[1]);
      $signature_provided = $tokenParts[2];

      // check the expiration time
      $payload_array = json_decode($payload,true);
      if(array_key_exists('exp', $payload_array) && array_key_exists('c', $payload_array) && array_key_exists('id', $payload_array))
      {
       $is_token_expired = ($payload_array['exp'] - time()) < 0;

       // build a signature based on the header and payload using the secret
       $signature = hash_hmac('SHA256', $this->base64url_encode($header) . "." . $this->base64url_encode($payload), $secret, true);

       // verify it matches the signature provided in the jwt
       $is_signature_valid = ($this->base64url_encode($signature) === $signature_provided);
	
       if($is_signature_valid && !$is_token_expired)
       {  
        $returnval=true;
        if($extractidentity)
        {// apply payload identy to properties of this instance of the class
            
         $this->userid=$payload_array['id'];
         $this->secondarycompanyuuid=$payload_array['c'];            
        }
       }
      }
     }
     return $returnval;
    }
    
    // returns the PIM userid that is making this request (assuming their JWT validated) 
    function userIdOfRequest()
    {
        return $this->userid;
    }


    function base64url_encode($str) 
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }
        
    
    function getJWTsecret()
    {
     $configGet=new configGet;
     $secret=$configGet->getConfigValue('sandpiperJWTsecret');
     if(!$secret)
     {      
      $randodata = file_get_contents('/dev/urandom', NULL, NULL, 0, 16);
      $secret=$configGet->getConfigValue('sandpiperJWTsecret',bin2hex($randodata));
     }
     return $secret;
    }
    
    function looksLikeAUUID($input)
    {//10000000-0000-0000-0000-000000000000
     $parts=explode('-',$input);
     if(count($parts)==5)
     {
      if(strlen($parts[0])==8 && strlen($parts[1])==4 && strlen($parts[2])==4 && strlen($parts[3])==4 && strlen($parts[4])==12)
      {
       return true;
      }
     }
     return false;      
    }
    
    
}

 

 
 
    
    




class slices extends sandpiper
{
 private $slicesdata=array();
    
 function __construct($_requesturi, $_method, $_body, $_jwt) 
 {
    $this->requesturi=$_requesturi;
    $this->body=$_body;
    $this->method=$_method;
    $this->jwtpresented=$_jwt;
    $this->verifyJWT($_jwt,true);
 }    
    
    
 function processRequest()
 {
     switch($this->method)
    {
        case 'GET':
            //ele 3 is "slices"

            
            if(isset($this->requesturi[4]))
            {// more levels exist after the slices verb 
                //  /v1/slices/name/slice2
                //  /v1/slices/2bea8308-1840-4802-ad38-72b53e31594c
                $uripart=$this->extractParms($this->requesturi[4]);

                if($uripart=='name')
                {
                    if(isset($this->requesturi[5]) && trim($this->requesturi[5])!='')
                    {
                        $slicename=trim($this->requesturi[5]);
                        $this->response='get grains in slice named:'.$slicename;
                    }
                    else
                    {// missing name
                        $this->response='missing name after slices/name/...';
                    }
                }
                else
                {// something other than "name" after the slices verb - likely a UUID
                    
                    if($this->looksLikeAUUID($uripart))
                    {//
                        $sliceuuid=$uripart;
                        $this->response='get grains in slice:'.$uripart;
                        
                    }
                    else
                    {// not a UUID 
                        
                        $this->response='expected a UUID got something else ('.$uripart.')';
                    }
                }
            }
            else
            {// no more slashed levels after slice verb
                $this->extractParms($this->requesturi[3]);
                if(count($this->keyedparms)>0)
                {
                    // /v1/slices?tags=brake_products
                    $this->response='get slices with parms: '.print_r($this->keyedparms,true);
                }
                else
                {// no parms
                    // /v1/slices
                    $this->response='get slices (no parms)';
                }
            }
            
            break;
        
        
        case 'POST':
            
            
            break;
        
        
        
        default:
            // unhandled method
            
            break;;
    }
 }
 
 
}





// for handling the /v1/grains endpoint
class grains extends sandpiper
{
 private $graindata=array();
    
 function __construct($_requesturi, $_method, $_body, $_jwt) 
 {
    $this->requesturi=$_requesturi;
    $this->body=$_body;
    $this->method=$_method;
    $this->jwtpresented=$_jwt;
    $this->verifyJWT($_jwt,true);  
 }

 
 function processRequest()
 {
    $this->extractParms($this->requesturi[3]);

    switch($this->method)
    {
        case 'GET':

            if(isset($this->requesturi[4]))
            { //more slashed levels exist after the grains verb. 

                if($this->looksLikeAUUID($this->requesturi[4]))
                {// level after the verb smells like a UUID. It is either a specific grain ID or a sliceid (depending on the next slashed level)
                    
                    
                    if(isset($this->requesturi[5]))
                    {// grain by key within a given slice 
                     // /v1/grains/2bea8308-1840-4802-ad38-72b53e31594c/level-1
                            $this->response='grain within slice: '.$this->requesturi[4].' that has grain-key: '.$this->requesturi[5];
                    }
                    else
                    {// specific grain by UUID

                        $this->response='specific grain: '.$this->requesturi[4];
                    }
                }
                else
                {// something other than a UUID was one level after the verb. likely "slice"

                    if($this->requesturi[4]=='slice')
                    {
                        if(isset($this->requesturi[5]))
                        {
                            $uripart=$this->extractParms($this->requesturi[5]);
                            if($this->looksLikeAUUID($uripart))
                            {//
                                $this->response='grain within slice: '.$uripart.' with parms:'. print_r($this->keyedparms,true);
                            }
                            else
                            {// unexpected - /v1/grains/slice/not-a-uuid
                                $this->response='expected a UUID representing a slice. got this insteaad: '.$this->requesturi[5];
                            }
                        }
                    }
                    else
                    {// someing other than "slice" 
                        $this->response='expected slice verb, got this instead:'.$this->requesturi[4];
                    }                   
                }
                
            } 
            else
            {// no more levels past the grains verb
                if(count($this->keyedparms)>0)
                {// parms exist (example: /v1/grains?payload=yes)
  
                    
                        $this->response='grains list with parms '.print_r($this->keyedparms,true);
                       
                }
                else
                {// no parms exist(example: /v1/grains)
                    
                        $this->response='grains list without parms';                    
                }
            }
            
             break;
         
        case 'POST':
            // post to the grains endpoint

            if(isset($this->requesturi[4]))
            {
             //more slashed levels exist after the grains verb - should not happen                
                $this->response =  'unexpected input - more elements after the grains verb ('.$this->requesturi[4].')';                
            }
            else
            {
                    // we expect some body data
                    if(array_key_exists('id', $this->body) && array_key_exists('slice_id', $this->body) && array_key_exists('grain_key', $this->body) && array_key_exists('encoding', $this->body) && array_key_exists('payload', $this->body))
                    {
                        if(count($this->keyedparms)>0)
                        {// parms exist (example: /v1/grains?replace=yes)
                                $this->response='payload (with parms):'.$this->body['payload'];
                        }
                        else
                        {// no parms exist(example: /v1/grains)
                                $this->response='payload (with no parms):'.$this->body['payload'];
                        }
                    }
                    else
                    {// we're missing elements from the body data
                        $this->response='POST body is missing elements. Expected: id, slice_id, grain_key, encoding, payload';                     
                    }
            }
             
            break;
         
        default :
        // un-handled method
            break;
    }
     
 }
  

}


class subs extends sandpiper
{
 private $subsdata=array();
    
 function __construct($_requesturi, $_method, $_body, $_jwt) 
 {
    $this->requesturi=$_requesturi;
    $this->body=$_body;
    $this->method=$_method;
    $this->jwtpresented=$_jwt;
    $this->verifyJWT($_jwt,true);
 }    
    
    
 function processRequest()
 {
     switch($this->method)
    {
        case 'GET':
            //ele 3 is "subs"
            
            if(isset($this->requesturi[4]))
            {// more levels exist after the subs verb 
                //  /v1/subs/1
                $uripart=$this->extractParms($this->requesturi[4]);
                    
                if($this->looksLikeAUUID($uripart))
                {//
                    $subscriptionuuid=$uripart;
                    $this->response='subscription UUID:'.$subscriptionuuid;
                }
                else
                {// not a UUID - must be a subscription name
                    $this->response='subscription name:'.$uripart;
                }
            }
            else
            {// no more slashed levels after subs verb
                //  /v1/subs

                $this->extractParms($this->requesturi[3]);
                if(count($this->keyedparms)>0)
                {
                    // /v1/slices?tags=brake_products
                    $this->response='get subs with parms: '.print_r($this->keyedparms,true);
                }
                else
                {// no parms
                    // /v1/slices
                    $this->response='get subs (no parms)';
                }
            }
            
            break;
        
        
        case 'POST':
            
            
            break;
        
        
        
        default:
            // unhandled method
            
            break;;
    }
 }
 
 
}








class activity extends sandpiper
{
 private $events=array();
         
 function __construct($_requesturi,$_jwt) 
 {
    $this->requesturi=$_requesturi;
    $this->jwtpresented=$_jwt;
    $this->verifyJWT($_jwt,true);    
    $this->extractParms($this->requesturi[3]);
 }
 
 function processRequest()
 {
    $this->getEvents($this->limit,$this->sort,$this->sortdirection);     
    if($this->nice)
    {
        $this->response='<pre>'.print_r($this->events,true).'</pre>';
    }
    else
    {
        $this->response=json_encode($this->events);
    }     
 }

 
 function getEvents($limit,$sorton,$sortdirection)
 {
  $db = new mysql; $db->connect(); $success=false;
    
  $orderby='id';
  $direction='desc';
  
  if($sorton=='planuuid' || $sorton=='subscriptionuuid' || $sorton=='timestamp' || $sorton=='action')
  {
   $orderby=$sorton;
  }
  if($sortdirection=='asc')
  {
   $direction=$sortdirection;
  }      
  
  
  if($stmt=$db->conn->prepare('select * from sandpiperactivity order by '.$orderby.' '.$direction.' limit ?'))
  { 
   if($stmt->bind_param('i', $limit))
   {
    if($stmt->execute())
    {
     if($db->result = $stmt->get_result())
     {
      $success=true;
      while($row = $db->result->fetch_assoc())
      {
       $this->events[]=array('id'=>$row['id'],'planuuid'=>$row['planuuid'],'subscriptionuuid'=>$row['subscriptionuuid'],'action'=>$row['action'],'timestamp'=>$row['timestamp']);
      }
     }
    }
   }
  }
  $db->close();
  return $success;
 }

    
}
?>
