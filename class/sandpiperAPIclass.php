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

     $planuuid=''; $resources='activity';
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
          $slices[]=array('slice_id'=>$row['sliceuuid'],'slice_type'=>$row['slicetype'],'name'=>$row['description'],'slicemetadata'=>$row['slicemetadata'],'hash'=>$hash);
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
           $grains[]=array('id'=>$row['grainuuid'],'description'=>$row['description'],'slice_id'=>$row['sliceuuid'],'grain_key'=>$row['grainkey'],'source'=>$row['source'],'encoding'=>$row['encoding'],'payload'=>$payload,'payload_len'=>$row['payloadsize']);
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
//      "id": "10000000-1111-0000-0000-000000000000",
//	"slice_id": "2bea8308-1840-4802-ad38-72b53e31594c",
//	"grain_key": "level-1",
//	"encoding": "raw",
//	"payload": "Sandpiper Rocks!"

        $db = new mysql; $db->connect(); $grainrecordid=false;
        
        $sliceuuid=$data['slice_id'];
        $grainuuid=$data['id'];
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
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($randodata), 4));
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
                                    $this->response= json_encode(array('message'=>'get subscription: '.$uripart.' for company '.$companyuuid));
                                }
                                else
                                {// /v1/companies/20000000-0000-0000-0000-000000000000/subs/not-a-uuid
                                    
                                    $this->response= json_encode(array('message'=>'unexpected input. expected a UUID representing a subscription for company '.$companyuuid.'.  got this instead:'.$uripart));
                                }
                            }
                            else
                            {// /v1/companies/20000000-0000-0000-0000-000000000000/subs
                                // this implies all subs
                                $this->response= json_encode(array('message'=>'get all subs for company:'.$companyuuid));
                            }
                        }
                        else
                        {//something else besides "subs" after /v1/companies/20000000-0000-0000-0000-000000000000
                            
                            $this->response= json_encode(array('message'=>'unexpected input - expected subs, got this instead:'.$uripart));
                        }
                    }
                }
                else
                {// /v1/companies/not-a-uuid
                    $this->response= json_encode(array('message'=>'unexpected input. expected a UUID representing a company got this instead:'.$uripart));
                }
            }
            else
            {// no more slashed levels after tags verb
                //  /v1/companies

                $this->extractParms($this->requesturi[3]);
                if(count($this->keyedparms)>0)
                {
                    // /v1/companies?erere=34
                    $this->response= json_encode(array('message'=>'get companies with parms: '.print_r($this->keyedparms,true)));
                }
                else
                {// no parms
                    // /v1/companies
                    $this->response= json_encode(array('message'=>'get companies (no parms)'));
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
                        $this->response= json_encode(array('message'=>'get grains in slice named:'.$slicename));
                        $this->logEvent($this->planuuid, '', '', 'get grains in slice named:'.$slicename);
                    }
                    else
                    {// missing name
                        $this->response= json_encode(array('message'=>'missing name after slices/name/...'));
                    }
                }
                else
                {// something other than "name" after the slices verb - likely a UUID
                    
                    if($this->looksLikeAUUID($uripart))
                    {// specific sliceid was given
                     //  /v1/slices/2bea8308-1840-4802-ad38-72b53e31594c
                     //  /v1/slices/2bea8308-1840-4802-ad38-72b53e31594c?detail=GRAIN_WITH_PAYLOAD
                     //  /v1/slices/2bea8308-1840-4802-ad38-72b53e31594c?detail=GRAIN_WITHOUT_PAYLOAD
                     //  /v1/slices/2bea8308-1840-4802-ad38-72b53e31594c?detail=GRAIN_WITH_PAYLOAD&inflate=yes
                     //  /v1/slices/2bea8308-1840-4802-ad38-72b53e31594c?detail=GRAIN_WITHOUT_PAYLOAD&inflate=yes

                        $sliceuuid=$uripart;
                        
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

                        $this->response= json_encode($this->getSubscribedFilegrains($this->planuuid,$sliceuuid,'%', $detaillevel, $inflatepayload));
                    }
                    else
                    {// not a UUID 
                        
                        $this->response= json_encode(array('message'=>'expected a UUID got something else ('.$uripart.')'));
                    }
                }
            }
            else
            {// no more slashed levels after slice verb
                // get all slices in plan
                              
                $slices=$this->getSubscribedSlices($this->planuuid);
                $this->response= json_encode($slices);
                $this->logEvent($this->planuuid, '', '', 'list slices');
            }
            
            break;
        
        
        case 'POST':
            // create (or refresh) a slice
            
            if(isset($this->requesturi[4]))
            {// more levels exist after the slices verb 
                //maybe   /v1/slices/refresh/2bea8308-1840-4802-ad38-72b53e31594c
                $uripart=$this->extractParms($this->requesturi[4]);

                if($uripart=='refresh')
                {
                    if(isset($this->requesturi[5]) && $this->looksLikeAUUID($this->requesturi[5]))
                    {
                        $sliceuuid=trim($this->requesturi[5]);
                        $this->response= json_encode(array('message'=>'refresh slice:'.$sliceuuid));
//                        $this->logEvent($this->planuuid, '', '', 'get grains in slice named:'.$slicename);
                    }
                    else
                    {// missing uuid
                        $this->response= json_encode(array('message'=>'missing sliceid after slices/refresh/...'));
                    }
                }
                else
                {// something other than "refresh" after the slices verb - likely a UUID
                    
                    $this->response= json_encode(array('message'=>'expected refresh after the slices verb. Got something else ('.$uripart.')'));
                }
            }
            else
            {// no more slashed levels after slice verb
                // we are creating a new slice from the contents of the body
                
                $this->extractParms($this->requesturi[3]);// was 4 before 1/1/2021
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
                if($this->looksLikeAUUID($this->body['id']))
                {
                    if($this->sliceExists($this->body['id']))
                    {// alice already exists
                        $this->response= json_encode(array('message'=>'slice id ('.$this->body['id'].') already exists.'));
                    }
                    else
                    {// slice does not already exist

                        if($this->isSliceTypeValid($this->body['slice_type']))
                        {
                            $slicerecordid=$this->addSlice($this->body, $this->planuuid);
                            $this->response= json_encode(array('message'=>'slice added (internal record ID:'.$slicerecordid.')'));
                        }
                        else
                        {// not a valid slice_type
                            $this->response= json_encode(array('message'=>'slice_type ('.$this->body['slice_type'].') is not valid'));
                        }
                    }
                }
                else
                {// id given in the body is not formatted as a UUID                    
                    $this->response= json_encode(array('message'=>'id does not appear to be a UUID'));
                }                
            }
            
            break;
        
        case 'DELETE':

            if($this->looksLikeAUUID($this->requesturi[4]))
            {// level after the verb smells like a UUID. It is a grain ID
                // verify that the plan actually included this grain and that the plan stipulates that the client is primary
                               
                if($this->isClientPrimary())
                {// connecting client is the primary - they are allowed to drop slices
                    if($this->isSliceInPlan($this->planuuid,$this->requesturi[4]))
                    {
                        $this->deleteSlice($this->requesturi[4]);
                        $this->response= json_encode(array('message'=>'slice deleted'));
                        $this->logEvent($this->planuuid, $this->body['slice_id'], $this->body['id'], 'slice deleted');
                    }
                    else
                    {// requested grain does not exist to delete
                        $this->response= json_encode(array('message'=>'request to delete a slices that is not in the plan'));
                    }
                }
                else
                {// client is not primary in the plan - not allowed to delete this grain
                    $this->response= json_encode(array('message'=>'Client is not primary in this plan - It is not authorized to delete slices.'));
                }
            }
            else
            {// something other than a sliceid was after the verb
                $this->response= json_encode(array('message'=>'expected a slice uuid after slices/ got this instead:'.$this->requesturi[4]));
            }
            
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

    switch($this->method)
    {
        case 'GET':

            if(isset($this->requesturi[4]))
            { //more slashed levels exist after the grains verb. 

                $uripart=$this->extractParms($this->requesturi[4]);
                if($this->looksLikeAUUID($uripart))
                {// level after the verb smells like a UUID. It is either a specific grain ID or a sliceid (depending on the next slashed level)
                    $grainid=$uripart;
                    
                    if(isset($this->requesturi[5]))
                    {// grain by key within a given slice /v1/grains/2bea8308-1840-4802-ad38-72b53e31594c/level-1
                            $this->response= json_encode(array('message'=>'grain within slice: '.$this->requesturi[4].' that has grain-key: '.$this->requesturi[5]));
                    }
                    else
                    {// specific grain by UUID
                     //   /v1/grains/2bea8308-1840-4802-ad38-72b53e31594c
                        
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

                        $this->response= json_encode($this->getSubscribedFilegrains($this->planuuid,'%',$grainid, $detaillevel, $inflatepayload));                        
                    }
                }
                else
                {// something other than a UUID was one level after the verb. likely "slice"

                    if($uripart=='slice')
                    {
                        if(isset($this->requesturi[5]))
                        {
                            $uripart=$this->extractParms($this->requesturi[5]);
                            if($this->looksLikeAUUID($uripart))
                            {
                                //$this->response= json_encode(array('message'=>'grains within slice: '.$uripart.' with parms:'. print_r($this->keyedparms,true)));

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

                                $this->response= json_encode($this->getSubscribedFilegrains($this->planuuid,'%','%', $detaillevel, $inflatepayload));                        
                            }
                            else
                            {// unexpected - /v1/grains/slice/not-a-uuid
                                $this->response= json_encode(array('message'=>'expected a UUID representing a slice. got this insteaad: '.$this->requesturi[5]));
                            }
                        }
                    }
                    else
                    {// someing other than "slice" 
                        $this->response= json_encode(array('message'=>'expected slice verb, got this instead:'.$uripart));
                    }                   
                }
            } 
            else
            {// no more levels past the grains verb - report all grains in the provided plan
                $this->extractParms($this->requesturi[3]);
                                
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

                $this->response= json_encode($this->getSubscribedFilegrains($this->planuuid,'%','%', $detaillevel, $inflatepayload));                            
            }
            
             break;
         
        case 'POST':
            // add a grain

            
            if(isset($this->requesturi[4]))
            {
             //more slashed levels exist after the grains verb - should not happen 
                $uripart= $this->extractParms($this->requesturi[4]);
                $this->response= json_encode(array('message'=>'unexpected input - more elements after the grains verb ('.$uripart.')'));
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
                            
                            $compresspayload=false; if(array_key_exists('deflate',$this->keyedparms) && $this->keyedparms['deflate']=='yes'){$compresspayload=true;}
                            
                            if($this->grainExists($this->body['id']))
                            {
                                if(array_key_exists('replace',$this->keyedparms) && $this->keyedparms['replace']=='yes')
                                {// replace the existing grain

                                    $this->addGrain($this->body,true,$compresspayload);
                                    $this->response= json_encode(array('message'=>'grain replaced'));
                                }
                                else
                                {// "replace" parameter was not specified and the grain already exists
                                    $this->response= json_encode(array('message'=>'you must specify replace=yes when writing a grain that already exists'));
                                }
                            }
                            else
                            {// grain UUID does not already exist. Add it.
                                
                                $this->response= json_encode(array('message'=>'grain added'));
                                $grainrecordid=$this->addGrain($this->body,false,$compresspayload);
                                
                                $this->logEvent($this->planuuid, $this->body['slice_id'], $this->body['id'], 'grain added (record id:'.$grainrecordid.')');
                            }
                        }
                        else
                        {// slice specificed is not in our plan
                            $this->response= json_encode(array('message'=>'request to add a grain to a slice ('.$this->body['slice_id'].') that is not in the plan ('.$this->planuuid.')'));                            
                        }
                    }
                    else
                    {// we're missing elements from the body data
                        $this->response= json_encode(array('message'=>'POST body is missing elements. Expected: id, slice_id, grain_key, encoding, payload'));
                    }
                }
                else
                {// client is not primary in the plan - not allowed to add a grain
                    $this->response= json_encode(array('message'=>'Client is not primary in this plan - It is not authorized to add grains.'));
                }
            }
             
            break;
            
        case 'DELETE':

            if($this->looksLikeAUUID($this->requesturi[4]))
            {// level after the verb smells like a UUID. It is a grain ID
                // verify that the plan actually included this grain and that the plan stipulates that the client is primary
                               
                if($this->isClientPrimary())
                {// connecting client is the primary - they are allowed to add/drop grains
                    if($this->isGrainInPlan($this->planuuid,$this->requesturi[4]))
                    {
                        $this->deleteGrain($this->requesturi[4]);
                        $this->response= json_encode(array('message'=>'grain deleted'));
                        $this->logEvent($this->planuuid, $this->body['slice_id'], $this->body['id'], 'grain deleted');
                    }
                    else
                    {// requested grain does not exist to delete
                        $this->response= json_encode(array('message'=>'request to delete a grain that is not in the plan'));
                    }
                }
                else
                {// client is not primary in the plan - not allowed to delete this grain
                    $this->response= json_encode(array('message'=>'Client is not primary in this plan - It is not authorized to delete grains.'));
                }
            }
            else
            {// something other than a grainid was after the verb
                $this->response= json_encode(array('message'=>'expected a grain uuid after grains/ got this instead:'.$this->requesturi[4]));
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
                    $this->response= json_encode(array('message'=>'subscription UUID:'.$subscriptionuuid));
                }
                else
                {// not a UUID - must be a subscription name
                    $this->response= json_encode(array('message'=>'subscription name:'.$uripart));
                }
            }
            else
            {// no more slashed levels after subs verb
                //  /v1/subs

                $this->extractParms($this->requesturi[3]);
                if(count($this->keyedparms)>0)
                {
                    // /v1/slices?tags=brake_products
                    $this->response= json_encode(array('message'=>'get subs with parms: '.print_r($this->keyedparms,true)));
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
                $this->response= json_encode(array('message'=>'tag name:'.$uripart));
            }
            else
            {// no more slashed levels after tags verb
                //  /v1/tags

                $this->extractParms($this->requesturi[3]);
                if(count($this->keyedparms)>0)
                {
                    // /v1/tags?erere=34
                    $this->response= json_encode(array('message'=>'get tags with parms: '.print_r($this->keyedparms,true)));
                }
                else
                {// no parms
                    // /v1/tags
                    $this->response= json_encode(array('message'=>'get tags (no parms)'));
                }
            }
            
            break;
        
        
        case 'POST':
            
            
            break;
        
        
        
        default:
            // unhandled method
            
            break;
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
            $this->response= json_encode(array('message'=>'sync GET method'));
            break;
        
        case 'POST':
            $this->response= json_encode(array('message'=>'sync POST method'));
            break;
        
        default:
            // unhandled method
            $this->response= json_encode(array('message'=>'sync - unhandled HTTP method'));
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
                $this->response= json_encode(array('message'=>'user id:'.$uripart));
            }
            else
            {// no more slashed levels after users verb
                //  /v1/users

                $this->extractParms($this->requesturi[3]);
                if(count($this->keyedparms)>0)
                {
                    // /v1/users?erere=34
                    $this->response= json_encode(array('message'=>'get users with parms: '.print_r($this->keyedparms,true)));
                }
                else
                {// no parms
                    // /v1/user
                    $this->response= json_encode(array('message'=>'get users (no parms)'));
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
