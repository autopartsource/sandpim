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
  protected $planschemaresults;

  public $response;
  protected $userid=false;
  protected $username;
  protected $userrealname;
  //protected $secondarycompanyuuid;
  //protected $primarycompanyuuid;


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

          // now validate the plan presented 
       $planuuid=''; 
       if($plandocumentencoded)
       {
        $plandocument=$this->getPlanFromPlandocument(base64_decode($plandocumentencoded));
        $planuuid=$plandocument['planuuid'];
        if($plandocument['schemaerrors']=='')
        {// plan presented has no XSD errors

         $resources='/plans/*,/slices/*,/activity/*,/touch/*,/admin/*,/feedback/*,/castle/*';
         
         // determine and modify resource list here
         
         $this->username=$username;
         $expiresepoch=(mktime()+900); // 15 minutes from now
         $secret=$this->getJWTsecret();
         $jwt= $this->generateJWT($this->userid, $this->username, $planuuid, $resources, $expiresepoch, $secret);
         $logs->logSystemEvent('login', $user->id, $user->name.' sandpiper API log in from '.$address. ' using plan:'.$planuuid);
         $this->logEvent($planuuid, '', '', $user->name.' authenticated with plan ['.$planuuid.'] from '.$address);
         $returnvalue= array('token'=>$jwt,'message'=>array('message_code'=>3001,'message_text'=>'authentication success'),'http response code'=>200);
        }
        else
        {// plan presented had XSD errors
         $this->logEvent('', '', '', $user->name.' authenticated from '.$address.', but the plandocument presented contained XSD errors ['.$plandocument['schemaerrors'].']');
         $returnvalue= array('token'=>'','message'=>array('message_code'=>3000,'message_text'=>'authentication failure (xml schema errors):'.$plandocument['schemaerrors']),'http response code'=>401);
        }        
       }
       else
       {// no plan was presented - issue resource-limited JWT
         $resources='/plans/*,/activity/*';

         $this->username=$username;
         $expiresepoch=(mktime()+900); // 15 minutes from now
         $secret=$this->getJWTsecret();
         $jwt= $this->generateJWT($this->userid, $this->username, $planuuid, $resources, $expiresepoch, $secret);
         $logs->logSystemEvent('login', $user->id, $user->name.' sandpiper API log in from '.$address. ' with null plan');
         $this->logEvent('', '', '', $user->name.' authenticated with a null plan from '.$address);
         $returnvalue= array('token'=>$jwt,'message'=>array('message_code'=>1001,'message_text'=>'authentication success'),'http response code'=>200);         
        }
      } 
      else
      {// log the failure event
        $logs->logSystemEvent('loginfailure', $this->userid, 'sandpiper API login failed from '.$address);
        $this->logEvent('', '', '', $user->name.' failed authentication with wrong password ['.$password.'] from '.$address);
        $returnvalue= array('token'=>'','message'=>array('message_code'=>3000,'message_text'=>'authentication failure (bad password)'),'http response code'=>401);
      }
     }
     else
     { // unknown user
       //  burn the amount of time that a password verification would have taken had this been a known username. This is to thwart a timing attack: Baddie could determine validity of arbitrary usernames thrown at the api because they all take a similar hmac time (several hundred mS)
       $trash= password_verify('asdkjflkasjdfkl', '$argon2id$v=19$m=65536,t=4,p=1$NnBsSTgvZmpNbmdoeXo2eA$LWpqCgHuxVmgEwDMSf3o5SM1AWT7qbCtkV8ckxBCr94');      
       $logs->logSystemEvent('loginfailure', 0, 'unknown user on sandpiper API ('.$username.', password:'.$password.') from '.$address);
       $this->logEvent('', '', '', 'unknown user ['.$username.'] attempted authentication with password ['.$password.'] from '.$address);
       $returnvalue= array('token'=>'','message'=>array('message_code'=>3000,'message_text'=>'authentication failure (unknown user)'),'http response code'=>401);

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
            
         $u = new user();
         $this->userid=$payload_array['id'];
         $this->userrealname=$u->realNameOfUserid($payload_array['id']);
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

    
    function getPlanRecord($planuuid)
    {
        $db = new mysql; $db->connect(); $returnvalue=false;
        if($stmt=$db->conn->prepare('select * from plan where planuuid=?'))
        {// need to lock this down to the scope of the specific user requesting!
            if($stmt->bind_param('s', $planuuid))
            {
                if($stmt->execute())
                {
                    if($db->result = $stmt->get_result())
                    {
                        if($row = $db->result->fetch_assoc())
                        {
                            $returnvalue=array('id'=>$row['id'],'planuuid'=>$row['planuuid'],'description'=>$row['description'],'status'=>$row['status'],'plandocument'=>$row['plandocument'],'planmetadata'=>$row['planmetadata'],'receiverprofileid'=>$row['receiverprofileid'],'planstatuson'=>$row['planstatuson'],'primaryapprovedon'=>$row['primaryapprovedon'],'secondaryapprovedon'=>$row['secondaryapprovedon']);
                        }
                    }
                }
            }
        }
        $db->close();
        return $returnvalue;     
    }
    

    function invokePlan()
    {       
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElementNS('http://www.autocare.org', 'Plan');
 
        $root = $doc->appendChild($root);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xs' ,'http://www.w3.org/2001/XMLSchema');
        $root->setAttribute('uuid','00000000-0000-0000-0000-000000000000');
        
        $primaryElement=new DOMElement('Primary');
        $root->appendChild($primaryElement);
        
        $instanceElement=new DOMElement('Instance');
        $primaryElement->appendChild($instanceElement);
        $instanceElement->setAttribute('uuid', 'b829e2a5-9037-4e45-a801-b10d138b0fe0');
        
        $softwareElement=new DOMElement('Software');
        $instanceElement->appendChild($softwareElement);
        $softwareElement->setAttribute('description', 'SandPIM');
        $softwareElement->setAttribute('version', '0.8');
        
        $capabilityElement=new DOMElement('Capability');
        $instanceElement->appendChild($capabilityElement);
        $capabilityElement->setAttribute('level', '2');
                
        $responseElement=new DOMElement('Response');
        $capabilityElement->appendChild($responseElement);
        $responseElement->setAttribute('uri', 'https://aps.dev/sandpim/sandpiper');
        $responseElement->setAttribute('role', 'All');
        $responseElement->setAttribute('description', 'SandPIM Sandpiper Respondent');
        
        
        $controlerElement=new DOMElement('Controller');
        $primaryElement->appendChild($controlerElement);
        $controlerElement->setAttribute('uuid','3297051d-d0dd-44ae-8bba-a2247425ad89');
        $controlerElement->setAttribute('description','AutoPartSource');
        
        $adminElement=new DOMElement('Admin');
        $controlerElement->appendChild($adminElement);
        $adminElement->setAttribute('contact', 'Luke Smith');
        $adminElement->setAttribute('email', 'lsmith@autopartsource.com');

        
        $primaryLinksElement=new DOMElement('Links');
        $primaryElement->appendChild($primaryLinksElement);
        
        $primaryUniqueLinksElement=new DOMElement('UniqueLink');
        $primaryLinksElement->appendChild($primaryUniqueLinksElement);
        $primaryUniqueLinksElement->setAttribute('uuid', '68e5b203-d19e-4d33-9a61-1879e13e3616');
        $primaryUniqueLinksElement->setAttribute('description', 'AutoPartSource');
        $primaryUniqueLinksElement->setAttribute('keyfield', 'autocare-brand-parent');
        $primaryUniqueLinksElement->setAttribute('keyvalue', 'BQMD');
        
        $poolsElement=new DOMElement('Pools');
        $primaryElement->appendChild($poolsElement);
        
        $poolElement=new DOMElement('Pool');
        $poolsElement->appendChild($poolElement);
        $poolElement->setAttribute('uuid', '631dd42f-46f2-4d6a-94b3-a327d2c4ad88');
        $poolElement->setAttribute('description', 'AmeriBrakes - Basic Content Offering');
        
        $poolLinksElement=new DOMElement('Links');
        $poolElement->appendChild($poolLinksElement);
        $poolLinksUniqueLinksElement=new DOMElement('UniqueLink');
        $poolLinksElement->appendChild($poolLinksUniqueLinksElement);
        $poolLinksUniqueLinksElement->setAttribute('uuid', 'f08c601c-a402-42af-939f-c480eae5faab');
        $poolLinksUniqueLinksElement->setAttribute('keyfield', 'autocare-brand');
        $poolLinksUniqueLinksElement->setAttribute('keyvalue', 'GQBF');
        $poolLinksUniqueLinksElement->setAttribute('description', 'AmeriBrakes');
        
        
        $slicesElement=new DOMElement('Slices');
        $poolElement->appendChild($slicesElement);
        
        
        $sliceElement=new DOMElement('Slice');
        $slicesElement->appendChild($sliceElement);
        $sliceElement->setAttribute('uuid', 'bab3d740-51dc-4726-bf79-d7df41e8f9a1');
        $sliceElement->setAttribute('description', 'Full ACES (L1) - Pads, Shoes, Drums and Wear Sensors');
        $sliceElement->setAttribute('slicetype', 'aces-file');
        $sliceElement->setAttribute('filename', 'AmeriBrakes_PadsShoesDrumsWearSensors_ACES_FULL_2021-11-09.xml');
        
        $sliceLinksElement=new DOMElement('Links');
        $sliceElement->appendChild($sliceLinksElement);
        $sliceLinksUniqueLinkElement=new DOMElement('UniqueLink');
        $sliceLinksElement->appendChild($sliceLinksUniqueLinkElement);
        $sliceLinksUniqueLinkElement->setAttribute('uuid', 'c5bce7ee-d05a-4893-9575-9c3c70674f9e');
        $sliceLinksUniqueLinkElement->setAttribute('keyfield', 'primary-reference');
        $sliceLinksUniqueLinkElement->setAttribute('keyvalue', '390239');
        
        $sliceLinksUniqueLinkElement=new DOMElement('UniqueLink');
        $sliceLinksElement->appendChild($sliceLinksUniqueLinkElement);
        $sliceLinksUniqueLinkElement->setAttribute('uuid', '237751f9-8f7a-4898-8d06-fbf0e4ea8fa4');
        $sliceLinksUniqueLinkElement->setAttribute('keyfield', 'autocare-brand');
        $sliceLinksUniqueLinkElement->setAttribute('keyvalue', 'GQBF');
        $sliceLinksUniqueLinkElement->setAttribute('description', 'AmeriBrakes');
        
        
        $sliceLinksMultiLinkElement=new DOMElement('MultiLink');
        $sliceLinksElement->appendChild($sliceLinksMultiLinkElement);
        $sliceLinksMultiLinkElement->setAttribute('keyfield', 'autocare-pcdb-parttype');
        
        
        $multiLinkEntryElement=new DOMElement('MultiLinkEntry');
        $sliceLinksMultiLinkElement->appendChild($multiLinkEntryElement);
        $multiLinkEntryElement->setAttribute('uuid', 'e47d750b-77a5-46ac-acae-2bf0a042bf81');
        $multiLinkEntryElement->setAttribute('keyvalue', '1684');
        $multiLinkEntryElement->setAttribute('description','Disc Brake Pad Set');
        
        $multiLinkEntryElement=new DOMElement('MultiLinkEntry');
        $sliceLinksMultiLinkElement->appendChild($multiLinkEntryElement);
        $multiLinkEntryElement->setAttribute('uuid', 'd2b626f8-ec28-42c8-b458-58edbcdecf8d');
        $multiLinkEntryElement->setAttribute('keyvalue', '1688');
        $multiLinkEntryElement->setAttribute('description','Drum Brake Shoe');
        
        
        $doc->formatOutput=true;
        return $doc->saveXML();    
    }
    
    

    function base64url_encode($str) 
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }
        
    /*** Get the local server's secret that is used to sign all JWT's
     * if it has not been set in the config table, this function will set it 
     * to a random value
     */
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

    function getSubscribedSlices($planuuid)
    {
     $db = new mysql; $db->connect(); $slices=array();

     if($stmt=$db->conn->prepare('select slice.id, slice.description, sliceuuid,slicetype,filename,slicemetadata,subscriptionmetadata,sliceorder,slicehash from plan,plan_slice,slice where plan.id=plan_slice.planid and plan_slice.sliceid=slice.id and plan.planuuid=? order by sliceorder'))
     {
      if($stmt->bind_param('s', $planuuid))
      {
       if($stmt->execute())
       {
        if($db->result = $stmt->get_result())
        {
         while($row = $db->result->fetch_assoc())
         {
          $hash=$this->calculateSliceHash($row['id']);
          $slices[]=array('slice_uuid'=>$row['sliceuuid'],'slice_description'=>$row['description'],'slice_type'=>$row['slicetype'],'file_name'=>$row['filename'],'slice_meta_data'=>$row['slicemetadata'],'subscription_meta_data'=>$row['subscriptionmetadata'],'slice_order'=>$row['sliceorder'],'slice_grainlist_hash'=>$hash);
         }
        }
       }
      }         
     }
     return $slices;
    }





    
    function getSubscribedFilegrains($planuuid,$sliceuuid,$grainuuid, $detaillevel, $inflatepayload)
    {
        //plan is required
        //
        // slice and grain optional and add additional "where" constraints to the query
        
        /*
         * $detaillevel valid values are: 'GRAIN_ID_ONLY', 'GRAIN_WITHOUT_PAYLOAD', 'GRAIN_WITH_PAYLOAD'
         * 
         * 
         * 
         */
        
        
        
     $db = new mysql; $db->connect(); $grains=array();

     if($detaillevel=='GRAIN_WITH_PAYLOAD')
     {
      $sql="select slice.description, grainuuid,sliceuuid,slicetype,source,encoding,grainkey,grainorder,payload,length(payload) as payloadsize,slicemetadata from plan,plan_slice,slice, slice_filegrain, filegrain where plan.id=plan_slice.planid and plan_slice.sliceid=slice.id and slice.id=slice_filegrain.sliceid and slice_filegrain.grainid=filegrain.id and plan.planuuid=? and slice.sliceuuid like ? and filegrain.grainuuid like ? order by grainorder";
     }
     else  
     {// don't include the payload column in the query if we dont need it
      $sql="select slice.description, grainuuid,sliceuuid,slicetype,source,encoding,grainkey,grainorder,'' as payload,length(payload) as payloadsize,slicemetadata from plan,plan_slice,slice, slice_filegrain, filegrain where plan.id=plan_slice.planid and plan_slice.sliceid=slice.id and slice.id=slice_filegrain.sliceid and slice_filegrain.grainid=filegrain.id and plan.planuuid=? and slice.sliceuuid like ? and filegrain.grainuuid like ? order by grainorder";            
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
          $payload=$row['payload'];
          
          if($row['encoding']=='z64' && strlen($payload)>0 && $inflatepayload)
          {
           $payload= $this->unZ64($payload);
          }
          
          if($detaillevel=='GRAIN_ID_ONLY')
          {
           $grains[]=$row['grainuuid'];   
          }
          else   
          {// return a structure of full verbosity
           //$grains[]=array('grain_uuid'=>$row['grainuuid'],'slice_uuid'=>$row['sliceuuid'],'grain_key'=>$row['grainkey'],'file_name'=>$row['source'],'encoding'=>$row['encoding'],'payload'=>$payload,'payload_len'=>$row['payloadsize']);
           $grains[]=array('grain_uuid'=>$row['grainuuid'],'grain_order'=>$row['grainorder'],'grain_key'=>$row['grainkey'],'grain_reference'=>'','grain_size_bytes'=>$row['payloadsize'],'payload'=>$payload);
          }
         }
        }
       }
      }         
     }
     //return array('grains'=>$grains);
     return $grains;
    }
    

    function getFilegrainByUUID($grainuuid,$inflatepayload=false)
    {
     $db = new mysql; $db->connect(); $grain=false;
     
     $sql="select grainuuid,encoding,grainkey,source,length(payload) as payloadsize, payload, timestamp from filegrain where grainuuid=?";
     
     if($stmt=$db->conn->prepare($sql))
     {
      if($stmt->bind_param('s', $grainuuid))
      {
       if($stmt->execute())
       {
        if($db->result = $stmt->get_result())
        {
         if($row = $db->result->fetch_assoc())
         {
          $payload=$row['payload'];
          if($row['encoding']=='z64' && strlen($payload)>0 && $inflatepayload)
          {
           $payload= $this->unZ64($payload);
          }
          
          $grain=array('grain_uuid'=>$row['grainuuid'],'grain_key'=>$row['grainkey'],'source'=>$row['source'],'encoding'=>$row['encoding'],'grain_size_bytes'=>$row['payloadsize'],'payload'=>$payload,'timestamp'=>$row['timestamp']);
         }
        }
       }
      }         
     }
     return $grain;
    }





    

    function isClientPrimary()
    {// look at the current plan to determine is the client is the primary actor in the relationship. This is for determining if they are allowed to do things like add and drop my grains
        
        // the only way to know who's who in the relationship is to parse the our copy 
        // of the plandocument and see which side (primary / secondary)
        
        
        
        $returnvalue=false;
        return $returnvalue;
    }
    
    function sliceExists($sliceuuid)
    {
        $db = new mysql; $db->connect(); $recordid=false;
        if($stmt=$db->conn->prepare('select id from slice where sliceuuid=?'))
        {
            if($stmt->bind_param('s', $sliceuuid))
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
        $grains=$this->getSubscribedFilegrains($planuuid, '%', $grainuuid, 'GRAIN_ID_ONLY',false);
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
    

    function addGrain($data,$replace,$compressrawpayload)
    {
        
//    $data was presented in the POST body JSON-encoded like this and then converted to an associative array  
//      "grain_uuid": "10000000-1111-0000-0000-000000000000",
//	"slice_uuid": "2bea8308-1840-4802-ad38-72b53e31594c",
//	"grain_key": "level-1",
//	"encoding": "raw",
//	"payload": "Sandpiper Rocks!"

        $db = new mysql; $db->connect(); $grainrecordid=false;
        
        $sliceuuid=$data['slice_uuid'];
        $grainuuid=$data['grain_uuid'];
        $grainkey=$data['grain_key'];
        $source=$data['source'];
        $encoding=$data['encoding'];
        if($encoding=='raw' && $compressrawpayload)
        {
            $payload= $this->Z64($data['payload']);
            $encoding='z64';
        }
        else
        {
            $payload=$data['payload'];
        }
        
        if($stmt=$db->conn->prepare('insert into filegrain values(null,?,?,?,?,?,now())'))
        {
            if($stmt->bind_param('sssss', $grainuuid,$grainkey,$source,$encoding,$payload))
            {
                if($stmt->execute())
                {
                    $grainrecordid=$db->conn->insert_id;
                }
            }         
        }
        
        if($grainrecordid)
        {// seccessful grain create            
            // figure out what the recordid of the slice is 
            $slicerecordid=$this->sliceExists($sliceuuid);
            
            $grainorder=1;
            
            if($stmt=$db->conn->prepare('insert into slice_filegrain values(null,?,?,?)'))
            {
                if($stmt->bind_param('ssi', $slicerecordid, $grainrecordid, $grainorder))
                {
                    $stmt->execute();
                }         
            }
        }
        
        $db->close();
        return $grainrecordid;
    }
    

    function deleteGrain($grainuuid)
    {
        $db = new mysql; $db->connect();  
        if($grainrecordid=$this->grainExists($grainuuid))
        {
            
            if($stmt=$db->conn->prepare('delete from filegrain where grainuuid=?'))
            {
                if($stmt->bind_param('s', $grainuuid))
                {
                    $stmt->execute();
                }         
            }

            if($stmt=$db->conn->prepare('delete from slice_filegrain where grainid=?'))
            {
                if($stmt->bind_param('i', $grainrecordid))
                {
                    $stmt->execute();
                }         
            }
        }
        $db->close();
    }
 
    function getSlice($planuuid,$sliceuuid)
    {
     $db = new mysql; $db->connect(); $slice=array();

     if($stmt=$db->conn->prepare('select slice.id, slice.description, sliceuuid,slicetype,slicemetadata,slicehash from plan,plan_slice,slice where plan.id=plan_slice.planid and plan_slice.sliceid=slice.id and plan.planuuid=? and slice.sliceuuid=?'))
     {
      if($stmt->bind_param('ss', $planuuid,$sliceuuid))
      {
       if($stmt->execute())
       {
        if($db->result = $stmt->get_result())
        {
         if($row = $db->result->fetch_assoc())
         {
          $hash=$this->calculateSliceHash($row['id']);
          $slice=array('slice_uuid'=>$row['sliceuuid'],'slice_description'=>$row['description'],'slice_type'=>$row['slicetype'],'file_name'=>'', 'slice_meta_data'=>$row['slicemetadata'],'slice_order'=>0,'slice_grainlist_hash'=>$hash);
         }
        }
       }
      }         
     }
     return $slice;
    }







    function addSlice($data,$planuuid)
    {
//    $data was presented in the POST body JSON-encoded like this and then converted to an associative array  
//    connect (subscribe) this new slice to the plan (if provided)        
        $db = new mysql; $db->connect(); $slicerecordid=false;
        
//    {
//	"id": "2bea8308-1840-4802-ad38-72b53e31594c",
//	"name": "Slice2",
//	"slice_type": "aces-file",
//	"created_at": "2020-01-05T03:56:36.373565Z",
//	"updated_at": "2020-01-05T03:56:36.373565Z",
//	"metadata": {
//		"pcdb-version": "2019-09-27",
//		"vcdb-version": "2019-09-27"
//	}
//    }            

        $sliceuuid=$data['id'];
        $description=$description=$data['name'];
        $slicetype=$data['slice_type'];
        $slicemetadata=$data['metadata'];
        $slicehash='';
        
        if($stmt=$db->conn->prepare('insert into slice values(null,?,?,?,0,?,?)'))
        {
            if($stmt->bind_param('sssss',$description,$sliceuuid,$slicetype,$slicemetadata,$slicehash))
            {
                if($stmt->execute())
                {
                    $slicerecordid=$db->conn->insert_id;                    
                    $planrecord=$this->getPlanRecord($planuuid);
                    
                    // plan was provieded - subscribe this new slice to it                     
                    if($planrecord)
                    {
                        $planrecordid=$planrecord['id'];
                        $subscriptionuuid= $this->uuidv4();
                        $subscriptionmetadata='';

                        if($stmt=$db->conn->prepare('insert into plan_slice values (null,?,?,?,?)'))
                        {
                            if($stmt->bind_param('iiss',$planrecordid,$slicerecordid,$subscriptionuuid,$subscriptionmetadata))
                            {
                                if($stmt->execute())
                                {
                                    $subscriptionrecordid=$db->conn->insert_id;
                                }
                            }         
                        }
                    }
                }
            } 
        }
        
        $db->close();
        return $slicerecordid;
    }

    /***
     * Delete a slice by UUID
     * 
     * Arguments
     *  $sliceuuid - UUID string representing a slice
     * 
     * Returns: nothing
     * 
     * Does not consider context of a plan or user or permission or any kind.
     * Any grains in this slice are unlinked and then garbage-collected if the
     * un-linking orphans the grain.
     */
    function deleteSlice($sliceuuid)
    {
        
        if($slicerecordid=$this->sliceExists($sliceuuid))
        {
            $db = new mysql; $db->connect();
            if($stmt=$db->conn->prepare('delete from slice_filegrain where sliceid=?'))
            {
                if($stmt->bind_param('i', $slicerecordid))
                {
                    if($stmt->execute())
                    {                        
                        $this->logEvent('',  '',  'all grains unlinked from slice '.$sliceuuid);   
                    }
                }         
            }
 
            if($stmt=$db->conn->prepare('delete from slice where sliceid=?'))
            {
                if($stmt->bind_param('i', $slicerecordid))
                {
                    if($stmt->execute())
                    {
                        $this->logEvent('',  '',  'slice '.$sliceuuid.' (record id '.$slicerecordid.')');   
                    }
                }
            }
            
            $db->close();
            $this->deleteOrphanFilegrains();
        }
    }
    
    
    /**
     * Deletes records from filegrains table that have not reference in slice_filegrain
     * Logs the activity 
     * 
     * Returns the number of records deleted
     */
    function deleteOrphanFilegrains()
    {
        // quantify the records that will be deleted so we can log them
        $db = new mysql; $db->connect(); $recordcount=0;
        if($stmt=$db->conn->prepare('select grainuuid, source from filegrain where filegrain.id not in (select grainid from slice_filegrain)'))
        {
            if($stmt->execute())
            {
                if($db->result = $stmt->get_result())
                {
                    while($row = $db->result->fetch_assoc())
                    {
                        $this->deleteGrain($row['grainuuid']);
                        $this->logEvent('',  '',  $row['grainuuid'], 'grain ('.$row['source'].') was found to be orphaned and delted');
                        $recordcount++;
                    } 
                }
            }
        }
        $db->close();
        return $recordcount;
    }
    
    
    
    function isSliceTypeValid($type)
    {
        $validtypes=array('aces-file','pies-file');
        return in_array($type, $validtypes);
    }
    
    function unZ64($input)
    {
        return gzdecode(base64_decode($input));   
    }
    
    
    function Z64($input)
    {
        return base64_encode(gzencode($input));
    }
    
    
    
    
    //consume a plandocument into an array structure and validate it against the sandpiper plan xsd
    function getPlanFromPlandocument($xml)
    {
        $plandocument=array('planuuid'=>'', 'primary'=>'','secondary'=>'', 'subscriptions'=>array(),'schemaerrors'=>'');
                
        $doc=new DOMDocument();
        $doc->loadXML($xml); 
        $schemaresults=array();
 
        libxml_use_internal_errors(true);
        
        if($doc->schemaValidateSource('<?xml version="1.0" encoding="UTF-8"?><xs:schema elementFormDefault="qualified" vc:minVersion="1.0" xmlns:vc="http://www.w3.org/2007/XMLSchema-versioning" xmlns:xs="http://www.w3.org/2001/XMLSchema"><xs:simpleType name="uuid"><xs:restriction base="xs:string"><xs:length fixed="true" value="36"/><xs:pattern value="[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}" /></xs:restriction></xs:simpleType><xs:simpleType name="String_Medium">
<xs:restriction base="xs:string"><xs:maxLength value="255"/></xs:restriction></xs:simpleType><xs:simpleType name="String_Short"><xs:restriction base="xs:string"><xs:maxLength value="40"/></xs:restriction></xs:simpleType><xs:simpleType name="Email"><xs:restriction base="xs:string"><xs:maxLength value="255"/><xs:pattern value="[^\s]+@[^\s]+"/></xs:restriction></xs:simpleType><xs:simpleType name="FieldName">
<xs:restriction base="xs:string"><xs:minLength value="1"/><xs:maxLength value="63"/><xs:pattern value="[A-Za-z][A-Za-z0-9_\-]+"/></xs:restriction></xs:simpleType><xs:simpleType name="FieldValue"><xs:restriction base="xs:string"><xs:minLength value="1"/><xs:maxLength value="255"/></xs:restriction></xs:simpleType><xs:simpleType name="Levels"><xs:restriction base="xs:string"><xs:enumeration value="1-1"/>
<xs:enumeration value="1-2"/><xs:enumeration value="2"/><xs:enumeration value="3"/></xs:restriction></xs:simpleType><!-- Attribute templates used in multiple places --><xs:attributeGroup name="Model"><xs:attribute name="uuid" type="uuid" use="required"/></xs:attributeGroup><xs:attributeGroup name="Description_Main"><xs:attribute name="description" type="String_Medium" use="required"/></xs:attributeGroup>
<xs:attributeGroup name="Description_Optional"><xs:attribute name="description" type="String_Medium" use="optional"/></xs:attributeGroup><!-- Element templates used in multiple places --><xs:complexType name="LinkGroup"><xs:sequence><xs:element maxOccurs="unbounded" minOccurs="0" name="UniqueLink"><xs:complexType><xs:attributeGroup ref="Model"/><xs:attribute name="keyfield" type="FieldName" use="required"/>
<xs:attribute name="keyvalue" type="FieldValue" use="required"/><xs:attributeGroup ref="Description_Optional"/></xs:complexType></xs:element><xs:element maxOccurs="unbounded" minOccurs="0" name="MultiLink"><xs:complexType><xs:sequence><xs:element maxOccurs="unbounded" minOccurs="1" name="MultLinkEntry"><xs:complexType><xs:attributeGroup ref="Model"/><xs:attribute name="keyvalue" type="FieldValue" use="required"/>
<xs:attributeGroup ref="Description_Optional"/></xs:complexType></xs:element></xs:sequence><xs:attribute name="keyfield" type="FieldName" use="required"/></xs:complexType></xs:element></xs:sequence></xs:complexType><xs:complexType name="Instance"><xs:sequence><xs:element maxOccurs="1" minOccurs="1" name="Software"><xs:complexType><xs:attributeGroup ref="Description_Main"/><xs:attribute name="version" type="String_Short" use="required"/>
</xs:complexType></xs:element><xs:element maxOccurs="1" minOccurs="1" name="Capability"><xs:complexType><xs:sequence><!-- If a server is available, it is listed here --><xs:element minOccurs="0" name="Response" maxOccurs="unbounded"><xs:complexType><xs:attribute name="uri" type="xs:string" use="required"/><xs:attribute name="role"><xs:simpleType><xs:restriction base="xs:string"><xs:enumeration value="Synchronization"/>
<xs:enumeration value="Authentication"/></xs:restriction></xs:simpleType></xs:attribute><xs:attribute name="description" type="String_Medium" use="optional" /></xs:complexType></xs:element></xs:sequence><xs:attribute name="level" type="Levels"/></xs:complexType></xs:element></xs:sequence><xs:attributeGroup ref="Model"/></xs:complexType><xs:complexType name="Controller"><xs:sequence>
<xs:element maxOccurs="1" minOccurs="1" name="Controller"><xs:complexType><xs:sequence><xs:element name="Admin"><xs:complexType><xs:attribute name="contact" type="String_Medium"/><xs:attribute name="email" type="Email"/></xs:complexType></xs:element></xs:sequence><xs:attributeGroup ref="Model"/><xs:attributeGroup ref="Description_Main"/></xs:complexType></xs:element></xs:sequence>
</xs:complexType><!-- Main schema --><xs:element name="Plan"><xs:complexType><xs:sequence><xs:element maxOccurs="1" minOccurs="1" name="Primary"><xs:complexType><xs:sequence><xs:element maxOccurs="1" minOccurs="1" name="Instance" type="Instance"/><xs:element maxOccurs="1" minOccurs="1" name="Controller" type="Controller"/><xs:element maxOccurs="1" minOccurs="0" name="Links" type="LinkGroup">
<xs:unique name="PrimaryNodeLinkUniqueKeyField"><xs:selector xpath="MultiLink | UniqueLink"/><xs:field xpath="@keyfield"/></xs:unique></xs:element><xs:element maxOccurs="1" minOccurs="0" name="Pools"><xs:complexType><xs:sequence><xs:element maxOccurs="unbounded" minOccurs="1" name="Pool"><xs:complexType><xs:sequence><xs:element maxOccurs="1" minOccurs="0" name="Links" type="LinkGroup">
<xs:unique name="PrimaryPoolLinkUniqueKeyField"><xs:selector xpath="MultiLink | UniqueLink"/><xs:field xpath="@keyfield"/></xs:unique></xs:element><xs:element maxOccurs="1" minOccurs="0" name="Slices"><xs:complexType><xs:sequence><xs:element maxOccurs="unbounded" minOccurs="1" name="Slice"><xs:complexType><xs:sequence><xs:element maxOccurs="1" minOccurs="0" name="Links" type="LinkGroup">
<xs:unique name="SliceLinkUniqueKeyField"><xs:selector xpath="MultiLink | UniqueLink"/><xs:field xpath="@keyfield"/></xs:unique></xs:element></xs:sequence><xs:attributeGroup ref="Model"/><xs:attributeGroup ref="Description_Main"/><xs:attribute name="slicetype" type="String_Medium" use="required"/><xs:attribute name="filename" type="String_Medium" use="optional"/></xs:complexType></xs:element>
</xs:sequence></xs:complexType></xs:element></xs:sequence><xs:attributeGroup ref="Model"/><xs:attributeGroup ref="Description_Main"/></xs:complexType></xs:element></xs:sequence></xs:complexType></xs:element></xs:sequence><xs:attributeGroup ref="Model"/><xs:attributeGroup ref="Description_Main"/></xs:complexType></xs:element><xs:element maxOccurs="1" minOccurs="0" name="Communal"><xs:complexType>
<xs:sequence><xs:element maxOccurs="1" minOccurs="0" name="Subscriptions"><xs:complexType><xs:sequence><xs:element maxOccurs="unbounded" minOccurs="1" name="Subscription"><xs:complexType><xs:sequence><!-- Not part of Sandpiper 1.0 - future use --><xs:element maxOccurs="1" minOccurs="0" name="DeliveryProfiles"><xs:complexType><xs:sequence><xs:element maxOccurs="unbounded" minOccurs="1" name="DeliveryProfile">
<xs:complexType><xs:attributeGroup ref="Model"/></xs:complexType></xs:element></xs:sequence></xs:complexType></xs:element></xs:sequence><xs:attributeGroup ref="Model"/><xs:attribute name="sliceuuid" type="uuid"/></xs:complexType></xs:element></xs:sequence></xs:complexType></xs:element></xs:sequence></xs:complexType></xs:element><xs:element maxOccurs="1" minOccurs="0" name="Secondary"><xs:complexType>
<xs:sequence><xs:element maxOccurs="1" minOccurs="1" name="Instance" type="Instance"/><xs:element maxOccurs="1" minOccurs="1" name="Controller" type="Controller"/><xs:element maxOccurs="1" minOccurs="0" name="Links" type="LinkGroup"><xs:unique name="SecondaryNodeLinkUniqueKeyField"><xs:selector xpath="MultiLink | UniqueLink"/><xs:field xpath="@keyfield"/></xs:unique></xs:element>
</xs:sequence><xs:attributeGroup ref="Model"/><xs:attributeGroup ref="Description_Main"/></xs:complexType></xs:element></xs:sequence><xs:attribute name="uuid" type="uuid"/><xs:attributeGroup ref="Description_Main"/></xs:complexType></xs:element></xs:schema>'))
        {// xml passes xsd validation. extract the meaningful bits
            
            $xpath = new DOMXpath($doc);

            $planElements=$xpath->query("/Plan");
            foreach($planElements as $planElement)
            {
             $plandocument['planuuid']=$planElement->getAttribute('uuid');
            }
            
            $subscriptionElements=$xpath->query("/Plan/Communal/Subscriptions/Subscription");
            foreach($subscriptionElements as $subscriptionElement)
            {
             $plandocument['subscriptions'][]=$subscriptionElement->getAttribute('uuid');
            }
            
            $primaryElements=$xpath->query("/Plan/Primary");
            foreach($primaryElements as $primaryElement)
            {
             $plandocument['primary']=$primaryElement->getAttribute('uuid');
            }
            
            $secondaryElements=$xpath->query("/Plan/Secondary");
            foreach($secondaryElements as $secondaryElement)
            {
             $plandocument['secondary']=$secondaryElement->getAttribute('uuid');
            }
            
        }
        else
        {
            $schemaerrors = libxml_get_errors();
            foreach ($schemaerrors as $schemaerror)
            {
                $errormessage='';
                switch ($schemaerror->level) 
                {
                    case LIBXML_ERR_WARNING:
                        //$errormessage .= 'Warning code '. $schemaerror->code;
                        break;
                    case LIBXML_ERR_ERROR:
                    //$errormessage .= 'Error code '.$schemaerror->code;
                        break;
                   case LIBXML_ERR_FATAL:
                    //$errormessage .= 'Fatal Error code '.$schemaerror->code;
                    break;
                }
                $errormessage.= trim($schemaerror->message);
                $schemaresults[]=$errormessage;   
            }
            libxml_clear_errors();
        }
        
        $plandocument['schemaerrors']=implode('; ',$schemaresults);
        return $plandocument; 
        
        
        
    }
    
    
    function calculateSliceHash($sliceid)
    {// id is the local record id of the the slice (not the UUID)
        
        $db = new mysql; $db->connect(); $idstring='';
        if($stmt=$db->conn->prepare('select grainuuid from slice_filegrain, filegrain where slice_filegrain.grainid =filegrain.id and slice_filegrain.sliceid=? order by grainuuid'))
        {
            if($stmt->bind_param('i', $sliceid))
            {
                if($stmt->execute())
                {
                    if($db->result = $stmt->get_result())
                    {
                        while($row = $db->result->fetch_assoc())
                        {
                            $idstring.=$row['grainuuid'];           
                        }
                    }
                }
            }
        }
        $db->close();
        return md5($idstring);
    }
    
    
    
    function uuidv4()
    {
        $randodata = file_get_contents('/dev/urandom', NULL, NULL, 0, 16);
        $uuid=vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($randodata), 4));

        // all 128 bits are now randomly generated in the hex output. Set the "M" (version) nibble to "4" by over-writing it 
        $uuid= substr_replace($uuid,'4', 14, 1);

        // set the "N" (variant) nibble to a,b,8 or 9 to specify the MSB as set and the second most significant bit to clear
        $valid_n_hex_nibbles=array('a','b','8','9');
        $n_hex_nibble=$valid_n_hex_nibbles[random_int(0, 3)];
        $uuid= substr_replace($uuid, $n_hex_nibble, 19, 1);

        return $uuid;
    }

    function getPlansForUser($userid)
    {
        $db = new mysql; $db->connect(); $plans=array();
        if($stmt=$db->conn->prepare('select plan.* from plan,plan_user where plan.id=plan_user.planid and plan_user.userid=?'))
        {
            if($stmt->bind_param('i', $userid))
            {
                if($stmt->execute())
                {
                    if($db->result = $stmt->get_result())
                    {
                        if($row = $db->result->fetch_assoc())
                        {
                            $plans[]=array('id'=>$row['id'],'planuuid'=>$row['planuuid'],'description'=>$row['description'],'status'=>$row['status'],'plandocument'=>$row['plandocument'],'planmetadata'=>$row['planmetadata'],'receiverprofileid'=>$row['receiverprofileid'],'planstatuson'=>$row['planstatuson'],'primaryapprovedon'=>$row['primaryapprovedon'],'secondaryapprovedon'=>$row['secondaryapprovedon']);
                        }
                    }
                }
            }
        }
        $db->close();
        return $plans;   
    }
}
// ----------------- end of base sandpiper class ---------------------------
 



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
            
            
            switch(count($this->requesturi))
            {
                case 4:
                    //            /slices
                    //Get list of slices available to this plan
                    // does it make sense to give a list of slices ?
                    //$slices=$this->getSubscribedSlices($this->planuuid);
                    $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'you must specify a slice uuid like /v1/slices/{uuid}'));
                    break;

                case 5:
                    //            /slices/[uuid]
                    //Get non-grain details about a specific slice
                    $sliceuuid=$this->extractParms($this->requesturi[4]);
                    
                    if($this->looksLikeAUUID($sliceuuid))
                    {// /slices/[uuid]
                        
                        if($this->isSliceInPlan($this->planuuid, $sliceuuid))
                        {// slice is part of user's plan
                            
                            $slice=$this->getSlice($this->planuuid, $sliceuuid);
                            $this->response=array('http response code'=>200, 'slice'=>$slice, 'message'=>array('message_code'=>1000, 'message_text'=>'here is your slice'));
                        }
                        else
                        {// supplied slice is not in user's plan
                            $this->response=array('http response code'=>403, 'message'=>array('message_code'=>3000, 'message_text'=>'slice ('.$sliceuuid.') is not part of this plan ('.$this->planuuid.')'));
                        }
                    }
                    else
                    {// not a UUID
                        $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'value supplied after /slices does not smell like a real uuid'));
                    }

                    break;
                    
                case 6:
                //             /slices/[uuid]/something   
                    $uripart=$this->extractParms($this->requesturi[5]);
                    if($uripart=='grains')
                    { //             /slices/[uuid]/grains   (detail=GRAIN_WITH_PAYLOAD|GRAIN_WITHOUT_PAYLOAD)
                        
                        $sliceuuid=$this->requesturi[4];
                        if($this->looksLikeAUUID($sliceuuid))
                        {
                            if($this->isSliceInPlan($this->planuuid, $sliceuuid))
                            {// slice is part of user's plan
                                                            
                                $detaillevel='GRAIN_WITHOUT_PAYLOAD';
                                if(array_key_exists('grain_detail',$this->keyedparms) && ($this->keyedparms['grain_detail']=='GRAIN_WITH_PAYLOAD' || $this->keyedparms['grain_detail']=='GRAIN_WITHOUT_PAYLOAD'))
                                {
                                    $detaillevel=$this->keyedparms['grain_detail'];
                                }                        

                                $inflatepayload=false;
                                if(array_key_exists('inflate',$this->keyedparms) && $this->keyedparms['inflate']=='yes')
                                {//
                                    $inflatepayload=true;
                                }

                                $grains=$this->getSubscribedFilegrains($this->planuuid,$sliceuuid,'%', $detaillevel, $inflatepayload);  
                                $this->response=array('http response code'=>200, 'grains'=>$grains, 'message'=>array('message_code'=>1000, 'message_text'=>'here is your list of grains in plan '.$this->planuuid.', slice '.$sliceuuid));                                    
                                $this->logEvent($this->planuuid, $sliceuuid, '', 'list grains in slice. Detail:'.$detaillevel);
                            }
                            else
                            {// supplied slice is not in user's plan
                                $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'slice UUID ('.$sliceuuid.') is not part of the current plan'));
                                $this->logEvent($this->planuuid, $sliceuuid, '', 'out-of-plan slice requested from ['.$address.']');
                            }
                        }
                        else
                        {// the expected uuid in /slices/[uuid]/grains was not formatted as a UUID
                            $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. Was expecting /v1/slices/{sliceuuid}/grains. sliceuuid was not formatted as a uuid. '));
                            $this->logEvent($this->planuuid, '', '', 'mal-formed slice uuid ['.$sliceuuid.'] requested from ['.$address.']');
                        }
                    }
                    else
                    {
                        if($uripart=='grain_id_list')
                        {   
                            $sliceuuid=$this->requesturi[4];
                            $inflatepayload=false; if(array_key_exists('inflate',$this->keyedparms) && $this->keyedparms['inflate']=='yes'){$inflatepayload=true;}
                            $grains=$this->getSubscribedFilegrains($this->planuuid,$sliceuuid,'%', 'GRAIN_ID_ONLY', $inflatepayload);  
                            $this->response=array('http response code'=>200, 'grain_ids'=>$grains, 'message'=>array('message_code'=>1000, 'message_text'=>'here is your lean list of grains (uuids only) in plan '.$this->planuuid.', slice '.$sliceuuid)); 
                            $this->logEvent($this->planuuid, $sliceuuid, '', 'list grains (lean) in slice');
                        }
                        else
                        {
                            $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. Was expecting grains verb or grain_id_list after slice uuid: like /v1/slices/{sliceuuid}/grain_id_list'));
                        }
                    }
                    break;
                
                case 7:
                //             /slices/[uuid]/something/something
                    $grainuuidid=$this->extractParms($this->requesturi[6]);
                    $uripart=$this->requesturi[5];
                    $sliceuuid=$this->requesturi[4];
 
                    if($uripart=='grains')
                    {
                        if($this->looksLikeAUUID($grainuuidid) && $this->looksLikeAUUID($sliceuuid))
                        {//             /slices/[uuid]/grains/[uuid]
                            
                            // check to see if the client has access to this slice
                            if($this->isSliceInPlan($this->planuuid, $sliceuuid))
                            {// slice is part of user's plan
                            
                                
                                $detaillevel='GRAIN_ID_ONLY';
                                if(array_key_exists('grain_detail',$this->keyedparms) && ($this->keyedparms['grain_detail']=='GRAIN_WITH_PAYLOAD' || $this->keyedparms['grain_detail']=='GRAIN_WITHOUT_PAYLOAD' || $this->keyedparms['grain_detail']=='GRAIN_ID_OLNY'))
                                {
                                    $detaillevel=$this->keyedparms['grain_detail'];
                                }                        

                                $inflatepayload=false;
                                if(array_key_exists('inflate',$this->keyedparms) && $this->keyedparms['inflate']=='yes')
                                {//
                                    $inflatepayload=true;
                                }

                                $grains=$this->getSubscribedFilegrains($this->planuuid,$sliceuuid,$grainuuidid, $detaillevel, $inflatepayload);

                                if(count($grains))
                                {
                                    $this->response=array('http response code'=>200,'grain'=>$grains[0], 'message'=>array('message_code'=>1000, 'message_text'=>'here is your specific grain ('.$grainuuidid.')'));
                                    $this->logEvent($this->planuuid, $sliceuuid, $grainuuidid, 'specific grain was requested');
                                }
                                else
                                {// no grains found in the given plan/slice
                                                                        
                                    $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'slice / grain ('.$sliceuuid.' / '.$grainuuidid.') was not found'));
                                    $this->logEvent($this->planuuid, $sliceuuid, '', 'slice / grain ('.$sliceuuid.' / '.$grainuuidid.') was not found');
                                }
                            }
                            else
                            {// supplied slice is not in user's plan

                                $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'slice ('.$sliceuuid.') is not part of plan ('. $this->planuuid.')'));
                                $this->logEvent($this->planuuid, $sliceuuid, '', 'slice ('.$sliceuuid.') is not part of plan ('. $this->planuuid.')');
                            }
                        }
                        else  
                        {//             /slices/[non-uuid]/grains/[non-uuid]
                            $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. was expecting /slices/[sliceuuid]/grains/[grainuuid]. Did not get properly formatted uuids in grains and/or slices positions'));
                            $this->logEvent($this->planuuid, $sliceuuid, '', 'unexpected input. was expecting /slices/[sliceuuid]/grains/[grainuuid]. Did not get properly formatted uuids in grains and/or slices positions');
                        }
                    }
                    else
                    {//             /slices/[uuid]/!grains/something
                        $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. was expecting /slices/[sliceuuid]/grains/[gtainuuid]. Did not get grains keyword'));
                        $this->logEvent($this->planuuid, $sliceuuid, '', 'unexpected input. was expecting /slices/[sliceuuid]/grains/[gtainuuid]. Did not get grains keyword');
                    }
                    
                    break;
                
                    
                default:
                    $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'wrong number of inputs'));
                    $this->logEvent('', '', '', 'wrong number of inputs at /slices GET ['.$this->requesturi.']');
                    break;
            }
            
            break;

            
            
        case 'DELETE':
            
            //  /slices/{sliceuuid}/grains/{grainuuid}
            
            switch(count($this->requesturi))
            {
                case 7:

                    $grainuuidid=$this->extractParms($this->requesturi[6]);
                    $uripart=$this->requesturi[5];
                    $sliceuuid=$this->requesturi[4];
 
                    if($uripart=='grains')
                    {
                        if($this->looksLikeAUUID($grainuuidid) && $this->looksLikeAUUID($sliceuuid))
                        {//             /slices/[uuid]/grains/[uuid]
                            
                            // check to see if the client has access to this slice
                            if($this->isSliceInPlan($this->planuuid, $sliceuuid))
                            {// slice is part of user's plan

                                if($this->isClientPrimary())
                                {
                                    $grains=$this->getSubscribedFilegrains($this->planuuid,$sliceuuid,$grainuuidid, 'GRAIN_WITHOUT_PAYLOAD', false);

                                    if(count($grains))
                                    {
                                        $this->response=array('http response code'=>200,'message'=>array('message_code'=>1000, 'message_text'=>'Grain '.$grainuuidid.' was disconnected from slice '.$sliceuuid));
                                        $this->logEvent($this->planuuid, $sliceuuid, $grainuuidid, 'specific grain was deleted');
                                    }
                                    else
                                    {// no grains found in the given plan/slice                                                   
                                        $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'slice / grain ('.$sliceuuid.' / '.$grainuuidid.') was not found for delete. No action taken.'));
                                    }   
                                }
                                else
                                {// client is secondary in plan
                                    $this->response=array('http response code'=>403, 'message'=>array('message_code'=>3000, 'message_text'=>'Secondary actor is not allowed to modify Content. No action taken.'));
                                }
                            }
                            else
                            {// supplied slice is not in user's plan

                                $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'slice ('.$sliceuuid.') is not part of plan ('. $this->planuuid.')'));
                            }
                        }
                        else  
                        {//             /slices/[non-uuid]/grains/[non-uuid]
                            $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. was expecting /slices/[sliceuuid]/grains/[grainuuid]. Did not get properly formatted uuids in grains and/or slices positions'));
                        }
                    }
                    else
                    {//             /slices/[uuid]/!grains/something
                        $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. was expecting /slices/[sliceuuid]/grains/[gtainuuid]. Did not get grains keyword'));
                    }
                    
                    break;
                    
                    
                default:
                    $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. Expected /slices/{sliceuuid}/grains/{grainuuid}'));
                    
                    break;
            }            
                        
            break;
        
        
        
        
        case 'POST':
            
            //  /slices/{sliceuuid}/grains/{grainuuid}            
            switch(count($this->requesturi))
            {
                case 7:

                    $grainuuidid=$this->extractParms($this->requesturi[6]);
                    $uripart=$this->requesturi[5];
                    $sliceuuid=$this->requesturi[4];
 
                    if($uripart=='grains')
                    {
                        if($this->looksLikeAUUID($grainuuidid) && $this->looksLikeAUUID($sliceuuid))
                        {//             /slices/[uuid]/grains/[uuid]
                            
                            // check to see if the client has access to this slice
                            if($this->isSliceInPlan($this->planuuid, $sliceuuid))
                            {// slice is part of user's plan

                                if($this->isClientPrimary())
                                {
                                    if($this->grainExists($grainuuid))
                                    {
                                    
                                       // verify that this slice/grain combo does not already exist
                                        $grains=$this->getSubscribedFilegrains($this->planuuid,$sliceuuid,$grainuuidid, 'GRAIN_WITHOUT_PAYLOAD', false);

                                        if(count($grains))
                                        {// slice/grain combo already exists - do not add it again
                                            $this->response=array('http response code'=>200,'message'=>array('message_code'=>1000, 'message_text'=>'Slice / Grain ('.$sliceuuid.' / '.$grainuuidid.') already exists. No action taken.'));
                                        }
                                        else
                                        {// no grains found in the given slice, and the specified grain exist - go ahead and link it 
                                            
                                            $this->response=array('http response code'=>200, 'message'=>array('message_code'=>1000, 'message_text'=>'pre-existing grain ('.$grainuuidid.') was added to slice '.$sliceuuid));
                                        }
                                    }
                                    else
                                    {// specified grain does not exist
                                        
                                        $this->response=array('http response code'=>404, 'message'=>array('message_code'=>1000, 'message_text'=>'Grain '.$grainuuidid.' does not exist. No action taken.'));                                        
                                    }
                                }
                                else
                                {// client is secondary in plan
                                    $this->response=array('http response code'=>403, 'message'=>array('message_code'=>3000, 'message_text'=>'Secondary actor is not allowed to modify Content. No action taken.'));
                                }
                            }
                            else
                            {// supplied slice is not in user's plan

                                $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'slice ('.$sliceuuid.') is not part of plan ('. $this->planuuid.')'));
                            }
                        }
                        else  
                        {//             /slices/[non-uuid]/grains/[non-uuid]
                            $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. was expecting /slices/[sliceuuid]/grains/[grainuuid]. Did not get properly formatted uuids in grains and/or slices positions'));
                        }
                    }
                    else
                    {//             /slices/[uuid]/!grains/something
                        $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. was expecting /slices/[sliceuuid]/grains/[gtainuuid]. Did not get grains keyword'));
                    }
                    
                    break;
                    
                    
                default:
                    $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. Expected /slices/{sliceuuid}/grains/{grainuuid}'));
                    
                    break;
            }            
            
            
            break;
        
        
        case 'PUT':

            //  /slices/{sliceuuid}/grains/{grainuuid}            
            switch(count($this->requesturi))
            {
                case 7:

                    $grainuuidid=$this->extractParms($this->requesturi[6]);
                    $uripart=$this->requesturi[5];
                    $sliceuuid=$this->requesturi[4];
 
                    if($uripart=='grains')
                    {
                        if($this->looksLikeAUUID($grainuuidid) && $this->looksLikeAUUID($sliceuuid))
                        {//             /slices/[uuid]/grains/[uuid]
                            
                            // check to see if the client has access to this slice
                            if($this->isSliceInPlan($this->planuuid, $sliceuuid))
                            {// slice is part of user's plan

                                if($this->isClientPrimary())
                                {
                                    // verify that this slice/grain combo does not already exist
                                    $grains=$this->getSubscribedFilegrains($this->planuuid,$sliceuuid,$grainuuidid, 'GRAIN_WITHOUT_PAYLOAD', false);

                                    if(count($grains))
                                    {
                                        $this->response=array('http response code'=>200,'message'=>array('message_code'=>1000, 'message_text'=>'Slice / Grain ('.$sliceuuid.' / '.$grainuuidid.') already exists. No action taken.'));
                                    }
                                    else
                                    {// no grains found in the given plan/slice - go ahead and add                                                   
                                        
                                        // take the body of the PUT and encode it 
                                        
                                        $this->response=array('http response code'=>200, 'message'=>array('message_code'=>1000, 'message_text'=>'New grain ('.$grainuuidid.') was added and linked to slice '.$sliceuuid));
                                    }   
                                }
                                else
                                {// client is secondary in plan
                                    $this->response=array('http response code'=>403, 'message'=>array('message_code'=>3000, 'message_text'=>'Secondary actor is not allowed to modify Content. No action taken.'));
                                }
                            }
                            else
                            {// supplied slice is not in user's plan

                                $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'slice ('.$sliceuuid.') is not part of plan ('. $this->planuuid.')'));
                            }
                        }
                        else  
                        {//             /slices/[non-uuid]/grains/[non-uuid]
                            $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. was expecting /slices/[sliceuuid]/grains/[grainuuid]. Did not get properly formatted uuids in grains and/or slices positions'));
                        }
                    }
                    else
                    {//             /slices/[uuid]/!grains/something
                        $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. was expecting /slices/[sliceuuid]/grains/[gtainuuid]. Did not get grains keyword'));
                    }
                    
                    break;
                    
                    
                default:
                    $this->response=array('http response code'=>404, 'message'=>array('message_code'=>3000, 'message_text'=>'unexpected input. Expected /slices/{sliceuuid}/grains/{grainuuid}'));
                    
                    break;
            }            
            
            break;
            

        
        default: break;
    }
            
 }
 
}



class plans extends sandpiper
{
    private $events=array();
         
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

            /**  these are the GET scenarios
             *    /plans
             *    /plans?plan_detail=PLAN_WITH_DOCUMENT
             *    /plans?plan_detail=PLAN_WITHOUT_DOCUMENT
             *    /plans/invoke
             *    /plans/{uuid}
             */

                switch(count($this->requesturi))
                {//ccc
                    case 4:
                        /**  these are the GET scenarios
                         *    /plans
                         */
                        $plans=$this->getPlansForUser($this->userid);
                        $plansresponse=array();
                        foreach($plans as $plan)
                        {
                            $plansresponse[]=array('plan_uuid'=>$plan['planuuid'],'replaces_plan_uuid'=>'','plan_description'=>$plan['description'],'plan_status'=>'proposed', 'plan_status_on'=>$plan['planstatuson'], 'primary_approved_on'=>$plan['primaryapprovedon'], 'secondary_approved_on'=>$plan['secondaryapprovedon'],'payload'=>$plan['plandocument']);
                        }

                        $this->response=array('plans'=>$plansresponse,'message'=>array('message_code'=>1000, 'message_text'=>'Here are the plans'), 'http response code'=>200);
                        $this->logEvent('', '', '', $this->userrealname.' requested existing plans list ('.count($plansresponse).' plans in response)');
 
                        break;
                
                    case 5:
                        /**
                         *    /plans?plan_detail=PLAN_WITH_DOCUMENT
                         *    /plans?plan_detail=PLAN_WITHOUT_DOCUMENT
                         *    /plans/invoke
                         *    /plans/{uuid}
                         */
                                                
                        $requestedplanuuid=$this->extractParms(requesturi[4]);   
                        if($this->looksLikeAUUID($requestedplanuuid))
                        {// specific plan was requested
                            $plan=$this->getPlanRecord($requestedplanuuid);
                            if($plan)
                            {
                                $this->response=array('plan'=>array('plan_uuid'=>$this->uuidv4(),'replaces_plan_uuid'=>'','plan_description'=>'fragment plan','plan_status'=>'proposed', 'plan_status_on'=>date('Y-m-d h:i:s'), 'primary_approved_on'=>date('Y-m-d h:i:s'), 'secondary_approved_on'=>date('Y-m-d h:i:s'),'payload'=>'plan xml placeholder'), 'message'=>array('message_code'=>1000, 'message_text'=>'Here is the plan'), 'http response code'=>200);
                                $this->logEvent($requestedplanuuid, '', '', 'existing plan ['.$requestedplanuuid.'] requested by '.$this->userrealname);
                            }
                            else
                            {// specific plan was not found
                                $this->response=array('message_code'=>'3000','message'=>'plan ['.$requestedplanuuid.'] not found','http response code'=>403);
                                $this->logEvent($requestedplanuuid, '', '', 'plan ['.$requestedplanuuid.'] not found requested by '.$this->userrealname);                                
                            }
                        }
                        else 
                        {// /plans/{not-a-uuid}
                            if($this->requesturi[4]=='invoke')
                            {
                                $this->response=array('plan'=>array('plan_uuid'=>$this->uuidv4(),'replaces_plan_uuid'=>'','plan_description'=>'fragment plan','plan_status'=>'proposed', 'plan_status_on'=>date('Y-m-d h:i:s'), 'primary_approved_on'=>date('Y-m-d h:i:s'), 'secondary_approved_on'=>date('Y-m-d h:i:s'),'payload'=> $this->invokePlan()), 'message'=>array('message_code'=>1000, 'message_text'=>'Here is a new fragment plan'), 'http response code'=>200);
                                $this->logEvent('', '', '', 'fragment plan invoked by '.$this->userrealname);
                            }
                            else
                            {// something besides "invoke"
                                $this->response=array('message_code'=>'3000','message'=>'unexpected generator action('.$this->requesturi[4].') after .../v1/plans/. Was expecting .../v1/plans/invoke','http response code'=>403);
                                $this->logEvent('', '', '', 'unexpected input after /plans ['.$this->requesturi[4].'] by '.$this->userrealname.'. Was expecting invoke verb.');
                            }
                        }
                        break;
                    
                    default:
                        // wrong number of inputs
                        $this->response=array('message_code'=>'3000','message'=>'unexpected number of slashed levels. Was expecting .../v1/plans/invoke','http response code'=>403);                                        
                        $this->logEvent('', '', '', 'unexpected input at /plans ['.$this->requesturi.'] by '.$this->userrealname.'. Was expecting something like .../plans/invoke.');
                        break;                    
                }
                
                
                break;
        
        
            case 'POST':        

                /*
                 * /v1/plans/invoke
                 * /v1/plans/propose
                 * /v1/plans/[uuid]/propose
                 * /v1/plans/[uuid]/approve
                 * /v1/plans/[uuid]/hold
                 * /v1/plans/[uuid]/terminate
                 * /v1/plans/[uuid]/obsolete
                 */
                                
                switch(count($this->requesturi))
                {
                    case 5:
                        // /plans/invoke
                        // /plans/propose
                            $uripart=$this->extractParms($this->requesturi[4]);
                        
                            if($uripart=='invoke')
                            {//   /plans/invoke
                                $this->response=array('message_code'=>1000,'message'=>'client asked for plan invocation','http response code'=>200);
                            }
                            
                            if($uripart=='propose')
                            {//   /plans/invoke
                                $this->response=array('message_code'=>1000,'message'=>'client proposed a new plan','http response code'=>200);
                            }
                                                       
                        break;
                        
                
                
                    case 6:
                        /*
                        * /v1/plans/[uuid]/propose
                        * /v1/plans/[uuid]/approve
                        * /v1/plans/[uuid]/hold
                        * /v1/plans/[uuid]/terminate
                        * /v1/plans/[uuid]/obsolete
                        */
                        
                        if($this->looksLikeAUUID($this->requesturi[4]))
                        { 
                            $this->response=array('message_code'=>'3xxx','message'=>'Unknown verb after /plans/[uuid]/', 'http response code'=>400);

                            
                            $planuuid=$this->requesturi[4];
                            $uripart=$this->extractParms($this->requesturi[5]);
                            
                            if($uripart=='propose')
                            {//   /plans/[uuid]/propose
                                $this->response=array('message_code'=>'2xxx','message'=>'Client proposed plan '.$planuuid, 'http response code'=>200);                                
                            }

                            if($uripart=='approve')
                            {//   /plans/[uuid]/approve
                                $this->response=array('message_code'=>'2xxx','message'=>'Client approved plan '.$planuuid, 'http response code'=>200);                                
                            }

                            if($uripart=='hold')
                            {//   /plans/[uuid]/hold         
                                $this->response=array('message_code'=>'2xxx','message'=>'Client placed plan '.$planuuid, ' on hold','http response code'=>200);                                
                            }

                            if($uripart=='terminate')
                            {//   /plans/[uuid]/terminate
                                $this->response=array('message_code'=>'2xxx','message'=>'Client terminated plan '.$planuuid, 'http response code'=>200);                                
                            }

                            if($uripart=='obsolete')
                            {//   /plans/[uuid]/obsolete
                                $this->response=array('message_code'=>'2xxx','message'=>'Client obsoleted plan '.$planuuid, 'http response code'=>200);                                
                            }
                            
                        }
                        else
                        {//   /v1/plans/{not-a-uuid}/xxx

                            $this->response=array('message_code'=>'3xxx','message'=>'unexpected input after /plans/. Expected a uuid, got this instead: '.$this->requesturi[4],'http response code'=>400);
                        }
              
                        break;
                        
               
                    default:
                        
                        $this->response=array('message_code'=>'3xxx','message'=>'unexpected input - too many url parts after /v1/plans','http response code'=>400);
                        
                        break;
                }
                
                                
                break;  // this break closes POST method case
                       
            
            default:
                // unhandled method
                $this->response=array('message'=>'Un-handled http method');
            
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
