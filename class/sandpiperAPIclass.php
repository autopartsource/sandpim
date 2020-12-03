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
  protected $planuuid;
  protected $jwtpresented; // supplied by the client with the request to the endpoint


  public $response;
  protected $userid=false;
  protected $username;
  protected $secondarycompanyuuid;
  protected $primarycompanyuuid;


  protected $keyedparms=array();
    
  
  protected $limit=10;
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


   function authenticateUser($username, $password, $plandocumentencoded, $address=false)
   {// uses the same users as the rest of the PIM system

     $planuuid=''; $resources='activity';
     if($plandocumentencoded)
     {
         $plandocument= json_decode(base64_decode($plandocumentencoded),true);
         if(array_key_exists('plan', $plandocument))
         {
             if(true)
             {// decide here if we actually like the plan presented
                $planuuid=$plandocument['plan'];
                $resources='activity,slices,grains,sync';
             }
         }
     }
      
       
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
        $jwt= $this->generateJWT($this->userid, $this->username, $planuuid, $resources, $expiresepoch, $secret);
        $logs->logSystemEvent('login', $user->id, $user->name.' sandpiper API log in from '.$address. ' using plan:'.$planuuid);
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
 
    
    function generateJWT($userid,$username,$planuuid,$resources,$expiration,$secret)
    {
      // generate JWT  -  based on the example at https://dev.to/robdwaller/how-to-create-a-json-web-token-using-php-3gml
     $encodedjwtheader = $this->base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
     $encodedjwtpayload = $this->base64url_encode(json_encode(['c'=>'', 'id' => $userid,'u'=> $username,'exp'=>$expiration,'plan'=>$planuuid,'resources'=>$resources]));
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
         if(array_key_exists('plan', $payload_array) && $this->looksLikeAUUID($payload_array['plan']))
         {
          $this->planuuid=$payload_array['plan'];
         }
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
    
    
    function logEvent($planuuid,$subscriptionuuid,$grainuuid,$action)
    {
     $db = new mysql; $db->connect(); $success=false;

     if($stmt=$db->conn->prepare('insert into sandpiperactivity (id,planuuid,subscriptionuuid,grainuuid,action,timestamp) values(null,?,?,?,?,now())'))
     { 
      if($stmt->bind_param('ssss', $planuuid,$subscriptionuuid,$grainuuid,$action))
      {
       $success=$stmt->execute();
      }
     }
     $db->close();
     return $success;
    }
    
    function getSubscribedFilegrains($planuuid,$sliceuuid,$grainuuid,$includepayload)
    {
        //plan is required
        // slice and grain optional and add additional "where" constraints to the query
        
     $db = new mysql; $db->connect(); $grains=array();
     $sql="select slice.description, grainuuid,sliceuuid,slicetype,source,encoding,grainkey,'' as payload,length(payload) as payloadsize,slicemetadata from plan,plan_slice,slice, slice_filegrain, filegrain where plan.id=plan_slice.planid and plan_slice.sliceid=slice.id and slice.id=slice_filegrain.sliceid and slice_filegrain.grainid=filegrain.id and plan.planuuid=? and slice.sliceuuid like ? and filegrain.grainuuid like ?";

     if($includepayload)
     {
      $sql="select slice.description, grainuuid,sliceuuid,slicetype,source,encoding,grainkey,payload,length(payload) as payloadsize,slicemetadata from plan,plan_slice,slice, slice_filegrain, filegrain where plan.id=plan_slice.planid and plan_slice.sliceid=slice.id and slice.id=slice_filegrain.sliceid and slice_filegrain.grainid=filegrain.id and plan.planuuid=? and slice.sliceuuid like ? and filegrain.grainuuid like ?";
     }
     
     if($stmt=$db->conn->prepare($sql))
     {
      if($stmt->bind_param('sss', $planuuid,$sliceuuid,$grainuuid))
      {
       if($stmt->execute())
       {
        if($db->result = $stmt->get_result())
        {
         while($row = $db->result->fetch_assoc())
         {
          $grains[]=array('id'=>$row['grainuuid'],'description'=>$row['description'],'slice_id'=>$row['sliceuuid'],'grain_key'=>$row['grainkey'],'source'=>$row['source'],'encoding'=>$row['encoding'],'payload'=>$row['payload'],'payload_len'=>$row['payloadsize']);
         }
        }
       }
      }         
     }
     return $grains;
    }
    
    function isClientPrimary()
    {// look at the current plan to determine is the client is the primary actor in the relationship. This is for determining if they are allowed to do things like add and drop my grains
        $returnvalue=true;
        return $returnvalue;
    }
    
    function grainExists($grainuuid)
    {
        $db = new mysql; $db->connect(); $recordid=false;
        if($stmt=$db->conn->prepare('select id from filegrain where grainuuid=?'))
        {
            if($stmt->bind_param('s', $grainuuid))
            {
                if($stmt->execute())
                {
                    if($db->result = $stmt->get_result())
                    {
                        if($row = $db->result->fetch_assoc())
                        {
                            $recordid=$row['id'];           
                        }
                    }
                }
            }
        }
        $db->close();
        return $recordid;
    }

    function isGrainInPlan($planuuid,$grainuuid)
    {
        $grains=$this->getSubscribedFilegrains($planuuid, '%', $grainuuid, false);
        if(count($grains)>0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    function isSliceInPlan($planuuid,$sliceuuid)
    {
        $db = new mysql; $db->connect(); $returnvalue=false;
        if($stmt=$db->conn->prepare('select slice.description from plan,plan_slice,slice where plan.id=plan_slice.planid and plan_slice.sliceid=slice.id and plan.planuuid=? and slice.sliceuuid=?'))
        {
            if($stmt->bind_param('ss', $planuuid,$sliceuuid))
            {
                if($stmt->execute())
                {
                    if($db->result = $stmt->get_result())
                    {
                        if($row = $db->result->fetch_assoc())
                        {
                            $returnvalue=true;
                        }
                    }
                }
            }
        }
        $db->close();
        return $returnvalue;
    }


    function addGrain($data,$replace)
    {
        
//    $data was presented in the POST body JSON-encoded like this and then converted to an associative array  
//      "id": "10000000-1111-0000-0000-000000000000",
//	"slice_id": "2bea8308-1840-4802-ad38-72b53e31594c",
//	"grain_key": "level-1",
//	"encoding": "raw",
//	"payload": "Sandpiper Rocks!"

        $db = new mysql; $db->connect(); $recordid=false;
        
        
        $grainuuid=$data['id'];
        $grainkey=$data['grainkey'];
        $source='';//$data['source'];
        $encoding=$data['encoding'];
        $payload=$data['payload'];
        
        if($stmt=$db->conn->prepare('insert into filegrain values(null,?,?,?,?,?,now())'))
        {
            if($stmt->bind_param('sssss', $grainuuid,$grainkey,$source,$encoding,$payload))
            {
                if($stmt->execute())
                {
                    $recordid=$db->conn->insert_id;
                }
            }         
        }        
        $db->close();
        return $recordid;
    }
    
    
}
// ----------------- end of base sandpiper class ---------------------------
 

class companies extends sandpiper
{
 private $companiesdata=array();
    
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
            //ele 3 is "companies"
            
            if(isset($this->requesturi[4]))
            {// more levels exist after the companies verb 
                //  /v1/companies/10000000-0000-0000-0000-000000000000
                $uripart=$this->extractParms($this->requesturi[4]);
                if($this->looksLikeAUUID($uripart))
                {// /v1/companies/20000000-0000-0000-0000-000000000000/subs
                 // /v1/companies/20000000-0000-0000-0000-000000000000/subs/2bea8308-1840-4802-ad38-72b53e31594c
                    
                    $companyuuid=$uripart;
  
                    if(isset($this->requesturi[5]))
                    {
                        $uripart=$this->extractParms($this->requesturi[5]);
                        if($uripart=='subs')
                        {
                            if(isset($this->requesturi[6]))
                            {// /v1/companies/20000000-0000-0000-0000-000000000000/subs/2bea8308-1840-4802-ad38-72b53e31594c
                                
                                $uripart=$this->extractParms($this->requesturi[6]);
                                if($this->looksLikeAUUID($uripart))
                                {
                                    $this->response='get subscription: '.$uripart.' for company '.$companyuuid;
                                }
                                else
                                {// /v1/companies/20000000-0000-0000-0000-000000000000/subs/not-a-uuid
                                    
                                    $this->response='unexpected input. expected a UUID representing a subscription for company '.$companyuuid.'.  got this instead:'.$uripart;
                                }
                            }
                            else
                            {// /v1/companies/20000000-0000-0000-0000-000000000000/subs
                                // this implies all subs
                                $this->response='get all subs for company:'.$companyuuid;                                
                            }
                        }
                        else
                        {//something else besides "subs" after /v1/companies/20000000-0000-0000-0000-000000000000
                            
                            $this->response='unexpected input - expected subs, got this instead:'.$uripart;
                        }
                    }
                }
                else
                {// /v1/companies/not-a-uuid
                    $this->response='unexpected input. expected a UUID representing a company got this instead:'.$uripart;
                }
            }
            else
            {// no more slashed levels after tags verb
                //  /v1/companies

                $this->extractParms($this->requesturi[3]);
                if(count($this->keyedparms)>0)
                {
                    // /v1/companies?erere=34
                    $this->response='get companies with parms: '.print_r($this->keyedparms,true);
                }
                else
                {// no parms
                    // /v1/companies
                    $this->response='get companies (no parms)';
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
                        $this->logEvent('', '', '', 'get grains in slice named:'.$slicename);
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
                    $this->response='get slices. plan presented in JWT:'.$this->planuuid;
                }
            }
            
            break;
        
        
        case 'POST':
  
            
            $this->response=print_r($this->body,true);
            
            
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
                    {// grain by key within a given slice /v1/grains/2bea8308-1840-4802-ad38-72b53e31594c/level-1
                            $this->response='grain within slice: '.$this->requesturi[4].' that has grain-key: '.$this->requesturi[5];
                    }
                    else
                    {// specific grain by UUID 
                        $includepayload=false;
                        if(array_key_exists('payload',$this->keyedparms) && $this->keyedparms['payload']=='yes')
                        {//  /v1/grains?payload=yes)
                               $includepayload=true;
                        }
                        
                        $this->response= json_encode($this->getSubscribedFilegrains($this->planuuid,'%',$this->requesturi[4], $includepayload));
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
            {// no more levels past the grains verb - report all grains in the provided plan
                $includepayload=false;
                if(array_key_exists('payload',$this->keyedparms) && $this->keyedparms['payload']=='yes')
                {//  /v1/grains?payload=yes)
                       $includepayload=true;
                }
                $this->response= json_encode($this->getSubscribedFilegrains($this->planuuid,'%','%', $includepayload));
            }
            
             break;
         
        case 'POST':
            // add a grain

            if(isset($this->requesturi[4]))
            {
             //more slashed levels exist after the grains verb - should not happen                
                $this->response =  'unexpected input - more elements after the grains verb ('.$this->requesturi[4].')';                
            }
            else
            {
                
                if($this->isClientPrimary())
                {
                    if(array_key_exists('id', $this->body) && array_key_exists('slice_id', $this->body) && array_key_exists('grain_key', $this->body) && array_key_exists('encoding', $this->body) && array_key_exists('payload', $this->body))
                    {// body data elements are present
                        
                                                /*
                         * {
	"id": "10000000-1111-0000-0000-000000000000",
	"slice_id": "2bea8308-1840-4802-ad38-72b53e31594c",
	"grain_key": "level-1",
	"encoding": "raw",
	"payload": "Sandpiper Rocks!"
}
                         */

                        
                        if($this->isSliceInPlan($this->planuuid,$this->body['slice_id']))
                        { // the specified slice is within the scope of the plan
                            if($this->grainExists($this->body['id']))
                            {
                                if(array_key_exists('replace',$this->keyedparms) && $this->keyedparms['replace']=='yes')
                                {// replace the existing grain

                                    $this->response='replace the existing grain';
                                    //$this->addGrain($this->body,true);
                                }
                                else
                                {// "replace" parameter was not specified and the grain already exists
                                    $this->response='you must specify the replace=yes when writing a grain that already exists';                            
                                }
                            }
                            else
                            {// grain UUID does not already exist
                                
                          //      $this->response='add grain that did not exist';
                                $this->addGrain($this->body,false);
                            }
                        }
                        else
                        {// slice specificed is not in our plan
                            $this->response='request to add a grain to a slice that is not in the plan ('.$this->planuuid.')';                            
                        }
                    }
                    else
                    {// we're missing elements from the body data
                        $this->response='POST body is missing elements. Expected: id, slice_id, grain_key, encoding, payload';                     
                    }
                }
                else
                {// client is not primary in the plan - not allowed to add a grain
                    $this->response='Client is not primary in this plan - It is not authorized to add grains.';
                }
            }
             
            break;
            
        case 'DELETE':

            if($this->looksLikeAUUID($this->requesturi[4]))
            {// level after the verb smells like a UUID. It is a grain ID
                // verify that the plan actually included this grain and that the plan stipulates that the client is primary
                               
                if($this->isClientPrimary())
                {// connecting client is the primary - they are allowed to add/drop grains
                    if($this->isGrainInPlan($this->requesturi[4]))
                    {
                        $this->response='delete grain';
                    }
                    else
                    {// requested grain does not exist to delete
                        $this->response='request to delete a grain that is not in the plan';
                    }
                }
                else
                {// client is not primary in the plan - not allowed to delete this grain
                    $this->response='Client is not primary in this plan - It is not authorized to delete grains.';
                }
            }
            else
            {// something other than a grainid was after the verb
                $this->response='expected a grain uuid after grains/ got this instead:'.$this->requesturi[4];
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


class tags extends sandpiper
{
 private $tagsdata=array();
    
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
            //ele 3 is "tags"
            
            if(isset($this->requesturi[4]))
            {// more levels exist after the subs verb 
                //  /v1/tags/1
                $uripart=$this->extractParms($this->requesturi[4]);
                $this->response='tag name:'.$uripart;
            }
            else
            {// no more slashed levels after tags verb
                //  /v1/tags

                $this->extractParms($this->requesturi[3]);
                if(count($this->keyedparms)>0)
                {
                    // /v1/tags?erere=34
                    $this->response='get tags with parms: '.print_r($this->keyedparms,true);
                }
                else
                {// no parms
                    // /v1/tags
                    $this->response='get tags (no parms)';
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



class sync extends sandpiper
{
 private $syncdata=array();
    
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
            //ele 3 is "sync"
            $this->response='sync GET method';
            break;
        
        case 'POST':
            $this->response='sync POST method';
            break;
        
        default:
            // unhandled method
            $this->response='sync - unhandled HTTP method';
            break;;
    }
 }
 
}




class users extends sandpiper
{
 private $usersdata=array();
    
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
            //ele 3 is "users"
            
            if(isset($this->requesturi[4]))
            {// more levels exist after the subs verb 
                //  /v1/users/1
                $uripart=$this->extractParms($this->requesturi[4]);
                $this->response='user id:'.$uripart;
            }
            else
            {// no more slashed levels after users verb
                //  /v1/users

                $this->extractParms($this->requesturi[3]);
                if(count($this->keyedparms)>0)
                {
                    // /v1/users?erere=34
                    $this->response='get users with parms: '.print_r($this->keyedparms,true);
                }
                else
                {// no parms
                    // /v1/user
                    $this->response='get users (no parms)';
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
