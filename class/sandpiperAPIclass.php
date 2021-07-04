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

     $planuuid=''; $resources='/plans/*,/slices/*,/activity/*,/touch/*,/admin/*,/feedback/*,/castle/*';
     if($plandocumentencoded)
     {
         $plandocument=$this->getPlanFromPlandocument(base64_decode($plandocumentencoded));
         $planuuid=$plandocument['planuuid'];
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
        $returnvalue= json_encode(['token'=>$jwt,'expires'=>date('Y-m-d\TH:i:s-00:00',$expiresepoch),'planschemaerrors'=>$plandocument['schemaerrors'],'message'=>'successful authentication with plan: '.$planuuid]);
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

    
    function getPlanRecord($planuuid)
    {
        //ccc
        $db = new mysql; $db->connect(); $returnvalue=false;
        if($stmt=$db->conn->prepare('select * from plan where planuuid=?'))
        {
            if($stmt->bind_param('s', $planuuid))
            {
                if($stmt->execute())
                {
                    if($db->result = $stmt->get_result())
                    {
                        if($row = $db->result->fetch_assoc())
                        {
                            $returnvalue=array('id'=>$row['id'],'description'=>$row['description'],'plannmetadata'=>$row['plannmetadata'],'receiverprofileid'=>$row['receiverprofileid']);
                        }
                    }
                }
            }
        }
        $db->close();
        return $returnvalue;     
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

     if($stmt=$db->conn->prepare('select slice.id, slice.description, sliceuuid,slicetype,slicemetadata,slicehash from plan,plan_slice,slice where plan.id=plan_slice.planid and plan_slice.sliceid=slice.id and plan.planuuid=?'))
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
          $slices[]=array('slice_uuid'=>$row['sliceuuid'],'slice_type'=>$row['slicetype'],'slice_description'=>$row['description'],'slice_meta_data'=>$row['slicemetadata'],'slice_grainlist_hash'=>$hash);
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
      $sql="select slice.description, grainuuid,sliceuuid,slicetype,source,encoding,grainkey,payload,length(payload) as payloadsize,slicemetadata from plan,plan_slice,slice, slice_filegrain, filegrain where plan.id=plan_slice.planid and plan_slice.sliceid=slice.id and slice.id=slice_filegrain.sliceid and slice_filegrain.grainid=filegrain.id and plan.planuuid=? and slice.sliceuuid like ? and filegrain.grainuuid like ?";
     }
     else  
     {// don't include the payload column in the query if we dont need it
      $sql="select slice.description, grainuuid,sliceuuid,slicetype,source,encoding,grainkey,'' as payload,length(payload) as payloadsize,slicemetadata from plan,plan_slice,slice, slice_filegrain, filegrain where plan.id=plan_slice.planid and plan_slice.sliceid=slice.id and slice.id=slice_filegrain.sliceid and slice_filegrain.grainid=filegrain.id and plan.planuuid=? and slice.sliceuuid like ? and filegrain.grainuuid like ?";            
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
           $grains[]=array('grain_uuid'=>$row['grainuuid'],'slice_uuid'=>$row['sliceuuid'],'grain_key'=>$row['grainkey'],'file_name'=>$row['source'],'encoding'=>$row['encoding'],'payload'=>$payload,'payload_len'=>$row['payloadsize']);
          }
         }
        }
       }
      }         
     }
     return array('grains'=>$grains);
    }
    
    

    function isClientPrimary()
    {// look at the current plan to determine is the client is the primary actor in the relationship. This is for determining if they are allowed to do things like add and drop my grains
        
        // the only way to know who's who in the relationship is to parse the our copy 
        // of the plandocument and see which side (primary / secondary)
        
        
        
        
        $returnvalue=true;
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
        $source='';
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
            
            if($stmt=$db->conn->prepare('insert into slice_filegrain values(null,?,?)'))
            {
                if($stmt->bind_param('ss', $slicerecordid, $grainrecordid))
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
     $db = new mysql; $db->connect(); $slices=false;

     if($stmt=$db->conn->prepare('select slice.id, slice.description, sliceuuid,slicetype,slicemetadata,slicehash from plan,plan_slice,slice where plan.id=plan_slice.planid and plan_slice.sliceid=slice.id and plan.planuuid=? and slice.id=?'))
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
          $slice=array('slice_uuid'=>$row['sliceuuid'],'slice_type'=>$row['slicetype'],'slice_description'=>$row['description'],'slice_meta_data'=>$row['slicemetadata'],'slice_grainlist_hash'=>$hash);
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
        if($doc->schemaValidateSource('<?xml version="1.0" encoding="UTF-8"?><xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:vc="http://www.w3.org/2007/XMLSchema-versioning" vc:minVersion="1.1" elementFormDefault="qualified"><xs:simpleType name="uuid"><xs:restriction base="xs:string"><xs:length value="36" fixed="true"/><xs:pattern value="[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}"/>          </xs:restriction></xs:simpleType><xs:simpleType name="String_Medium"><xs:restriction base="xs:string"><xs:maxLength value="255"/></xs:restriction></xs:simpleType><xs:simpleType name="String_Short"><xs:restriction base="xs:string"><xs:maxLength value="40"/></xs:restriction></xs:simpleType><xs:simpleType name="Email"><xs:restriction base="xs:string"><xs:maxLength value="255"/><xs:pattern value="[^\s]+@[^\s]+"/></xs:restriction></xs:simpleType><xs:simpleType name="FieldName"><xs:restriction base="xs:string"><xs:minLength value="1"/><xs:maxLength value="63"/><xs:pattern value="[A-Za-z][A-Za-z0-9_\-]+"/></xs:restriction></xs:simpleType><xs:simpleType name="FieldValue"><xs:restriction base="xs:string"><xs:minLength value="1"/><xs:maxLength value="255"/></xs:restriction></xs:simpleType><xs:simpleType name="Levels"><xs:restriction base="xs:string"><xs:enumeration value="1-1"/><xs:enumeration value="1-2"/><xs:enumeration value="2"/><xs:enumeration value="3"/></xs:restriction></xs:simpleType><xs:attributeGroup name="Model"><xs:attribute name="uuid" type="uuid" use="required"/></xs:attributeGroup><xs:attributeGroup name="Description_Main"><xs:attribute name="description" type="String_Medium" use="required"/></xs:attributeGroup><xs:attributeGroup name="Description_Optional"><xs:attribute name="description" type="String_Medium" use="optional"/></xs:attributeGroup><xs:complexType name="LinkGroup"><xs:sequence><xs:element name="UniqueLink" minOccurs="0" maxOccurs="unbounded"><xs:complexType><xs:attributeGroup ref="Model"/><xs:attribute name="keyfield" type="FieldName" use="required"/><xs:attribute name="keyvalue" type="FieldValue" use="required"/><xs:attributeGroup ref="Description_Optional"/></xs:complexType></xs:element><xs:element name="MultiLink" minOccurs="0" maxOccurs="unbounded"><xs:complexType><xs:sequence><xs:element name="MultLinkEntry" minOccurs="1" maxOccurs="unbounded"><xs:complexType><xs:attributeGroup ref="Model"/><xs:attribute name="keyvalue" type="FieldValue" use="required"/><xs:attributeGroup ref="Description_Optional"/></xs:complexType></xs:element>                       </xs:sequence><xs:attribute name="keyfield" type="FieldName" use="required"/></xs:complexType></xs:element></xs:sequence></xs:complexType>    <xs:complexType name="Instance"><xs:sequence><xs:element name="Software" minOccurs="1" maxOccurs="1"><xs:complexType><xs:attributeGroup ref="Description_Main"/><xs:attribute name="version" type="String_Short" use="required"/></xs:complexType></xs:element><xs:element name="Capability" minOccurs="1" maxOccurs="1"><xs:complexType><xs:sequence><xs:element name="Response" minOccurs="0" maxOccurs="1"><xs:complexType><xs:attribute name="uri" type="xs:string" use="required"/><xs:attribute name="role"><xs:simpleType><xs:restriction base="xs:string"><xs:enumeration value="Synchronization"/><xs:enumeration value="Authentication"/></xs:restriction></xs:simpleType></xs:attribute><xs:attribute name="description" type="String_Medium" use="optional"/></xs:complexType></xs:element></xs:sequence><xs:attribute name="level" type="Levels"/></xs:complexType></xs:element></xs:sequence><xs:attributeGroup ref="Model"/></xs:complexType><xs:element name="Plan"><xs:complexType><xs:sequence><xs:element name="Primary" minOccurs="1" maxOccurs="1"><xs:complexType><xs:sequence><xs:element name="Instance" type="Instance" minOccurs="1" maxOccurs="1"/><xs:element name="Controller" minOccurs="1" maxOccurs="1"><xs:complexType><xs:sequence><xs:element name="Admin"><xs:complexType><xs:attribute name="contact" type="String_Medium" /><xs:attribute name="email" type="Email"/></xs:complexType></xs:element>    </xs:sequence><xs:attributeGroup ref="Model"/><xs:attributeGroup ref="Description_Main"/></xs:complexType></xs:element><xs:element name="Links" type="LinkGroup"><xs:unique name="PrimaryInstanceLinkUniqueKeyField"><xs:selector xpath="MultiLink|UniqueLink"/><xs:field xpath="@keyfield"/></xs:unique></xs:element><xs:element name="Pools" maxOccurs="1"><xs:complexType><xs:sequence><xs:element name="Pool" minOccurs="1"><xs:complexType><xs:sequence><xs:element name="Links" type="LinkGroup"><xs:unique name="PrimaryPoolLinkUniqueKeyField"><xs:selector xpath="MultiLink|UniqueLink"/><xs:field xpath="@keyfield"/></xs:unique></xs:element><xs:element name="Slices" minOccurs="0"><xs:complexType><xs:sequence><xs:element name="Slice" minOccurs="1"><xs:complexType><xs:sequence><xs:element name="Links" type="LinkGroup"><xs:unique name="SliceLinkUniqueKeyField"><xs:selector xpath="MultiLink|UniqueLink"/><xs:field xpath="@keyfield"/></xs:unique></xs:element></xs:sequence><xs:attributeGroup ref="Model"/><xs:attributeGroup ref="Description_Main"/></xs:complexType></xs:element></xs:sequence></xs:complexType></xs:element></xs:sequence><xs:attributeGroup ref="Model"/><xs:attributeGroup ref="Description_Main"/></xs:complexType></xs:element></xs:sequence></xs:complexType></xs:element></xs:sequence><xs:attributeGroup ref="Model"/></xs:complexType></xs:element><xs:element name="Communal" minOccurs="1" maxOccurs="1"><xs:complexType><xs:sequence><xs:element name="Subscriptions" minOccurs="0" maxOccurs="1"><xs:complexType><xs:sequence><xs:element name="Subscription" minOccurs="1" maxOccurs="unbounded"><xs:complexType><xs:sequence><xs:element name="DeliveryProfiles" minOccurs="0" maxOccurs="1"><xs:complexType><xs:sequence><xs:element name="DeliveryProfile" minOccurs="1" maxOccurs="unbounded"><xs:complexType><xs:attributeGroup ref="Model"/></xs:complexType></xs:element></xs:sequence></xs:complexType></xs:element></xs:sequence><xs:attributeGroup ref="Model"/><xs:attribute name="sliceuuid" type="uuid"/></xs:complexType></xs:element></xs:sequence></xs:complexType></xs:element></xs:sequence></xs:complexType></xs:element><xs:element name="Secondary" minOccurs="1" maxOccurs="1"><xs:complexType><xs:sequence><xs:element name="Instance" type="Instance" minOccurs="1" maxOccurs="1"/><xs:element name="Links" type="LinkGroup"><xs:unique name="SecondaryInstanceLinkUniqueKeyField"><xs:selector xpath="MultiLink|UniqueLink"/><xs:field xpath="@keyfield"/></xs:unique></xs:element></xs:sequence><xs:attributeGroup ref="Model"/></xs:complexType></xs:element></xs:sequence><xs:attribute name="uuid" type="uuid"/></xs:complexType></xs:element></xs:schema>'))
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
                    
                    $slices=$this->getSubscribedSlices($this->planuuid);
                    $this->response= json_encode($slices);
                    $this->logEvent($this->planuuid, '', '', 'list slices');
                    break;

                case 5:
                    //            /slices/[uuid]
                    //Get non-grain details about a specific slice
                    $sliceuuid=$this->extractParms($this->requesturi[4]);
                    if($this->looksLikeAUUID($uripart))
                    {// /slices/[uuid]
                        
                        if($this->isSliceInPlan($this->planuuid, $sliceuuid))
                        {// slice is part of user's plan
                            
                            $slice=$this->getSlice($this->planuuid, $sliceuuid);
                            $this->response= json_encode($slice);
                            $this->logEvent($this->planuuid, $sliceuuid, '', 'list a single slice');
                        }
                        else
                        {// supplied slice is not in user's plan
                            $this->response= json_encode(array('message'=>'slice UUID ('.$uripart.') is not part of the current plan'));                            
                        }
                    }
                    else
                    {// not a UUID
                        $this->response= json_encode(array('message'=>'unexpected input. Expected a UUID representing a specific slice. Got this instead:'.$uripart));
                    }

                    break;
                
                case 6:
                //            /slices/[uuid]/grains  (detail=GRAIN_WITH_PAYLOAD|GRAIN_WITHOUT_PAYLOAD|GRAIN_ID)
                    
                    $uripart=$this->extractParms($this->requesturi[5]);
                    if($uripart=='grains')
                    {
                        $sliceuuid=$this->requesturi[4];
                        if($this->looksLikeAUUID($sliceuuid))
                        {
                            if($this->isSliceInPlan($this->planuuid, $sliceuuid))
                            {// slice is part of user's plan
                            
                                
                                $detaillevel='GRAIN_ID_ONLY';
                                if(array_key_exists('detail',$this->keyedparms) && ($this->keyedparms['detail']=='GRAIN_WITH_PAYLOAD' || $this->keyedparms['detail']=='GRAIN_WITHOUT_PAYLOAD' || $this->keyedparms['detail']=='GRAIN_ID_OLNY'))
                                {
                                    $detaillevel=$this->keyedparms['detail'];
                                }                        

                                $inflatepayload=false;
                                if(array_key_exists('inflate',$this->keyedparms) && $this->keyedparms['inflate']=='yes')
                                {//
                                    $inflatepayload=true;
                                }

                                $this->response= json_encode($this->getSubscribedFilegrains($this->planuuid,$sliceuuid,'%', $detaillevel, $inflatepayload));  
                                $this->logEvent($this->planuuid, $sliceuuid, '', 'list grains in slice. Detail:'.$detaillevel);
                            }
                            else
                            {// supplied slice is not in user's plan
                                $this->response= json_encode(array('message'=>'slice UUID ('.$sliceuuid.') is not part of the current plan'));                            
                            }
                        }
                        else
                        {
                            $this->response= json_encode(array('message'=>'Unexpected input. Expected a UUID representing a specific slice. Got this instead:'.$uripart));                                                        
                        }
                    }
                    else  
                    {// not "grains" in the last element
                        $this->response= json_encode(array('message'=>'Unexpected input. Expected grains verb (like: /slices/[uuid]/grains). Got this instead:'.$uripart));
                    }
                    break;

                CASE 7:
                //            /slices/[uuid]/grains/[uuid]            
                    
                    $grainuuid=$this->extractParms($this->requesturi[6]);
                    
                    if($this->looksLikeAUUID($grainuuid))
                    {                    
                        if($this->requesturi[5]=='grains')
                        {
                            $sliceuuid=$this->requesturi[4];
                            if($this->looksLikeAUUID($sliceuuid))
                            {
                                if($this->isSliceInPlan($this->planuuid, $sliceuuid))
                                {// slice is part of user's plan


                                    $detaillevel='GRAIN_ID_ONLY';
                                    if(array_key_exists('detail',$this->keyedparms) && ($this->keyedparms['detail']=='GRAIN_WITH_PAYLOAD' || $this->keyedparms['detail']=='GRAIN_WITHOUT_PAYLOAD'))
                                    {
                                        $detaillevel=$this->keyedparms['detail'];
                                    }                        

                                    $inflatepayload=false;
                                    if(array_key_exists('inflate',$this->keyedparms) && $this->keyedparms['inflate']=='yes')
                                    {//
                                        $inflatepayload=true;
                                    }

                                    $this->response= json_encode($this->getSubscribedFilegrains($this->planuuid,$sliceuuid,$grainuuid, $detaillevel, $inflatepayload));  
                                    $this->logEvent($this->planuuid, $sliceuuid, '', 'list grains in slice. Detail:'.$detaillevel);
                                }
                                else
                                {// supplied slice is not in user's plan
                                    $this->response= json_encode(array('message'=>'slice UUID ('.$sliceuuid.') is not part of the current plan'));
                                }
                            }
                            else
                            {
                                $this->response= json_encode(array('message'=>'Unexpected input. Expected a UUID representing a specific slice. Got this instead:'.$sliceuuid));
                            }
                        }
                        else  
                        {// not "grains" in the last element
                            $this->response= json_encode(array('message'=>'Unexpected input. Expected grains verb (like: /slices/[uuid]/grains). Got this instead:'.$this->requesturi[5]));
                        }
                    }
                    else
                    {//     /slices/[uuid]/grains/[uuid]<- is not formatted like a uuid
                        $this->response= json_encode(array('message'=>'Unexpected input. Expected a specific uuid representing a specific grain (like: /slices/[uuid]/grains/[uuid]). Got this instead:'.$uripart));
                    }

                    break;
                    
                default:
                    
                    $this->response= json_encode(array('message'=>'Unexpected input. Got more or less parts than expected.'));
                    break;
            
            }
            
        
                        
            break;
        
        
        case 'POST':        
        //   /slices/[uuid]/grains
        //   /slices/[uuid]/grains/[uuid]


            switch(count($this->requesturi))
            {
                case 6:
                    //   /slices/[uuid]/grains

                    $uripart=$this->extractParms($this->requesturi[5]);
 
                    if($uripart=='grains')
                    {
                        $sliceuuid=$this->requesturi[4];
                        if($this->looksLikeAUUID($sliceuuid))
                        {
                            if($this->isSliceInPlan($this->planuuid, $sliceuuid))
                            {
                                //write multiple (array) grains to the given slice
                                if($this->isClientPrimary())
                                {
                                    $this->response= json_encode(array('message'=>'bulk grains add - not implimented yet.'));
                                }
                                else
                                {// client is not primary in this plan

                                    $this->response= json_encode(array('message'=>'client is not primary in this plan - it cannot bulk add grains to a slice.'));
                                }                        
                                
                            }
                            else
                            {// specificd slice is not in plan
                                
                                $this->response= json_encode(array('message'=>'slice UUID ('.$sliceuuid.') is not part of the current plan'));
                            }        
                        }
                        else
                        {// sliceuuid is not formatted like a uuid
                            
                            $this->response= json_encode(array('message'=>'Unexpected input. Expected a uuid representing a specific slice . Got this instead:'.$sliceuuid));
                        } 
                    }
                    else
                    {//   /slices/[uuid]/grains<- was not "grains"
                        
                        $this->response= json_encode(array('message'=>'Unexpected input. Expected grains verb (like: /slices/[uuid]/grains). Got this instead:'.$uripart));
                    }
                    
                    break;
                
                
                case 7:
                    //   /slices/[uuid]/grains/[uuid]

                    if($this->requesturi[5]=='grains')
                    {
                        $sliceuuid=$this->requesturi[4];
                        if($this->looksLikeAUUID($sliceuuid))
                        {
                            if($this->isSliceInPlan($this->planuuid, $sliceuuid))
                            {
                                $grainuuid=$this->extractParms($this->requesturi[6]);
                                if($this->looksLikeAUUID($grainuuid))
                                {
                                    if($this->isClientPrimary())
                                    {
                                        if(!$this->grainExists($grainuuid))
                                        {
                                            if(array_key_exists('grain_uuid', $this->body) && array_key_exists('slice_uuid', $this->body) && array_key_exists('grain_key', $this->body) && array_key_exists('encoding', $this->body) && array_key_exists('payload', $this->body))
                                            {// body data elements are present

                                            
                                                $this->response= json_encode(array('message'=>'grain added'));
                                                $grainrecordid=$this->addGrain($this->body,false,false);
                                                $this->logEvent($this->planuuid, $this->body['slice_uuid'], $this->body['grain_uuid'], 'grain added (record id:'.$grainrecordid.')');                                            
                                            }
                                            else
                                            {// body element(s) missing
                                                $this->response= json_encode(array('message'=>'POST body is missing elements. Expected: grain_uuid, slice_uuid, grain_key, encoding, payload'));
                                            }
                                        }
                                        else
                                        {// grain already exists 
                                            
                                            $this->response= json_encode(array('message'=>'Grain ('.$grainuuid.') already exists - cannot over-write.'));
                                        }
                                    }
                                    else
                                    {// client is not primary in this plan

                                        $this->response= json_encode(array('message'=>'client is not primary in this plan - it cannot add a grain to a slice.'));
                                    }
                                }
                                else
                                {// grainuuid not fromatted right
                                    
                                    $this->response= json_encode(array('message'=>'expected a grain uuid after grains/ got this instead:'.$grainuuid));
                                }
                            }
                            else
                            {// specificd slice is not in plan
                                
                                $this->response= json_encode(array('message'=>'slice UUID ('.$sliceuuid.') is not part of the current plan'));
                            }        
                        }
                        else
                        {// sliceuuid is not formatted like a uuid
                            
                            $this->response= json_encode(array('message'=>'Unexpected input. Expected a uuid representing a specific slice. Got this instead:'.$sliceuuid));
                        } 
                    }
                    else
                    {//   /slices/[uuid]/grains<- was not "grains"
                        
                        $this->response= json_encode(array('message'=>'Unexpected input. Expected grains verb (like: /slices/[uuid]/grains). Got this instead:'.$this->requesturi[5]));
                    }
                    
                                
                    break;
  
                
                default:
                    $this->response= json_encode(array('message'=>'Unexpected input. Got more or less parts than expected.'));            
                    break;
                    
            }
           

            break;
        
        case 'DELETE':
        // /v1/slices/[uuid]/grains/[uuid]
            
            switch(count($this->requesturi))
            {
                case 6:
                    // /v1/slices/[uuid]/grains            
                    // delete all grains from given slice
                    
                    $sliceuuid=$this->requesturi[4];
                    if($this->looksLikeAUUID($sliceuuid))
                    {
                        if($this->isSliceInPlan($this->planuuid,$sliceuuid))
                        {
                            $uripart=$this->extractParms($this->requesturi[5]);
                            if($uripart=='grains')
                            {

                                if($this->isClientPrimary())
                                {
                                    $this->response= json_encode(array('message'=>'bulk grains delete - not implimented yet.'));
                                }
                                else
                                {// client is not primary in this plan

                                    $this->response= json_encode(array('message'=>'client is not primary in this plan - it cannot bulk delete grains from a slice.'));
                                }                        
                            }
                            else
                            {// missing "grains" verb
                                $this->response= json_encode(array('message'=>'Unexpected input. expected [uuid]/grains/[uuid]. Got '.$this->requesturi[5].' instead of grains.'));                                
                            }
                        }
                        else
                        {// slice is not in plan                            
                            $this->response= json_encode(array('message'=>'request to delete a slices that is not in the plan'));
                        }
                    }
                    else
                    {// slice uuid not formatted right
                        $this->response= json_encode(array('message'=>'expected a slice uuid after slices/ got this instead:'.$sliceuuid));
                    }
                    
                    break;
                
                
                case 7:
                    // /v1/slices/[uuid]/grains/[uuid]
                    // delete a specific grain from a specific slice
                    
                    $sliceuuid=$this->requesturi[4];
                    if($this->looksLikeAUUID($sliceuuid))
                    {
                        if($this->isSliceInPlan($this->planuuid,$sliceuuid))
                        {
                            if($this->requesturi[5]=='grains')
                            {
                                $grainuuid=$this->extractParms($this->requesturi[6]);
                                if($this->looksLikeAUUID($grainuuid))
                                {
                                    if($this->isClientPrimary())
                                    {
                                        if($this->isGrainInPlan($this->planuuid, $grainuuid))
                                        {
                                            $this->deleteGrain($grainuuid);
                                            $this->response= json_encode(array('message'=>'grain '.$grainuuid.' deleted'));
                                            $this->logEvent($this->planuuid, $sliceuuid, $grainuuid, 'grain deleted');
                                        }
                                        else
                                        {// grain is not in plan
                                            $this->response= json_encode(array('message'=>'request to delete a grain ('.$grainuuid.') that is not in plan.'));
                                        }
                                    }
                                    else
                                    {// client is not primary in this plan
                                        $this->response= json_encode(array('message'=>'client is not primary in this plan - it cannot delte a grain.'));
                                    }
                                }
                                else
                                {// grainuuid is not formatted as a uuid
                                    $this->response= json_encode(array('message'=>'expected a grain uuid after grains/ got this instead:'.$grainuuid));
                                }
                            }
                            else
                            {// missing "grains" verb
                                $this->response= json_encode(array('message'=>'Unexpected input. expected [uuid]/grains/[uuid]. Got '.$this->requesturi[5].' instead of grains.'));                                
                            }
                        }
                        else
                        {// slice is not in plan
                            $this->response= json_encode(array('message'=>'request to delete a slices that is not in the plan'));
                        }
                    }
                    else
                    {// slice uuid not formatted right
                        $this->response= json_encode(array('message'=>'expected a slice uuid after slices/ got this instead:'.$this->requesturi[4]));
                    }
                    break;

                default:
                    $this->response= json_encode(array('message'=>'Unexpected input. Got more or less parts than expected.'));               
                    break;                
            }            
            
            break;

        
            
        default:
            // unhandled method
            $this->response= json_encode(array('message'=>'Un-handled http method'));               
            
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
