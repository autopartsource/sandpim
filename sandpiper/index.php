<?php
include_once('../class/sandpiperAPIclass.php');
include_once('../class/pimClass.php');
include_once('../class/logsClass.php');


$pim = new pim;

 //ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'sandpiper index.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

 $logs = new logs;

// split the request URI into an array of levels by "/"
$uriparts= explode('/', $_SERVER['REQUEST_URI']);


// because the logic downstream of here depends on specific sections of the uri being reliably in specific absolute positions in the path,
// we potentially need to shift the elements left until the APIS's root is in the right array element. The entire sandpim app could be deeper than docroot on the server.
for($i=0; $i<4; $i++)
{
 if($uriparts[1]=='sandpiper')
 {// API root is now aligned to the right spot in the array
  break;
 }
 array_splice($uriparts,0,1);
}

$method=$_SERVER['REQUEST_METHOD'];
$bodyraw=file_get_contents('php://input');
$postbody= json_decode($bodyraw,true);

$jwtpresented='';
if(array_key_exists('HTTP_AUTHORIZATION',$_SERVER))
{
 if(substr($_SERVER['HTTP_AUTHORIZATION'], 0, 7)=='Bearer ')
 {
  $jwtpresented=substr($_SERVER['HTTP_AUTHORIZATION'], 7);
 }    
}




switch($uriparts[2])
{// uri is like /sandpiper/v1/users/1
// element 2 is the version ("v1")

    //versionless requests
    case 'check':
        
        echo 'API OK';
        break;
   
    
    case 'v1':

       
        $temp=explode('?',$uriparts[3]);
        $root=$temp[0];
        
        
        switch($root)
        {

            case 'login':
                $sandpiper=new sandpiper;

                if($method=='POST')
                {
                    if(array_key_exists('username',$postbody) &&  array_key_exists('password',$postbody))
                    {// user and pass were provided
                        $plandocument=''; if(array_key_exists('plandocument',$postbody)){$plandocument=$postbody['plandocument'];}
                        $response = $sandpiper->authenticateUser($postbody['username'], $postbody['password'], $plandocument, $_SERVER['REMOTE_ADDR']);
                        if(isset($response['http response code']))
                        {
                            http_response_code($response['http response code']);        
                        }
                        echo json_encode($response);
                    }
                    else
                    {// username or password not present in login post body
                        http_response_code(400);
                    }
                }

                if($method=='GET')
                {
                    echo 'API OK. Authentication is done with a POST at this endpoint (you used GET)';
                }

                break;


            
            case 'plans':
                $plans=new plans($uriparts,$method,$postbody,$jwtpresented);
                if($plans->userIdOfRequest()!==false)
                {// jtw validated, process request and send response
                    $plans->processRequest();
                   
                    if(isset($plans->response['http response code']))
                    {
                        http_response_code($plans->response['http response code']);
                        unset($plans->response['http response code']);
                    }
                    echo json_encode($plans->response);
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                    $plans->logEvent('', '', '', 'unauthenticated '.$method.' attempt at ['.$_SERVER['REQUEST_URI'].'] by '.$_SERVER['REMOTE_ADDR']);
                }
                break;

            case 'slices':
                $slices=new slices($uriparts,$method,$postbody,$jwtpresented);
                if($slices->userIdOfRequest()!==false)
                {// jtw validated, process request
                    $slices->processRequest();
                    $response=$slices->response;
                    if(array_key_exists('http response code',$response))
                    {
                        http_response_code($response['http response code']);
                        unset($response['http response code']);
                    }
                    echo json_encode($response);
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                    $slices->logEvent('', '', '', 'unauthenticated '.$method.' attempt at ['.$_SERVER['REQUEST_URI'].'] by '.$_SERVER['REMOTE_ADDR']);
                }
                break;

            
            
            case 'activity':
                $activity=new activity($uriparts,$jwtpresented);
                if($activity->userIdOfRequest()!==false)
                {// jtw validated, process request and send response
                    $activity->processRequest();
                    echo $activity->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                    $activity->logEvent('', '', '', 'unauthenticated '.$method.' attempt at ['.$_SERVER['REQUEST_URI'].'] by '.$_SERVER['REMOTE_ADDR']);
                }
                break;
            
            case 'touch':
                $touch=new touch($uriparts,$jwtpresented);
                if($touch->userIdOfRequest()!==false)
                {// jtw validated, process request and send response
                    $touch->processRequest();
                    echo $touch->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                }
                break;
           
            case 'admin':
                $admin=new admin($uriparts,$jwtpresented);
                if($admin->userIdOfRequest()!==false)
                {// jtw validated, process request and send response
                    $admin->processRequest();
                    echo $admin->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                }
                break;
                                
                
            case 'feedback':
                $feedback=new feedback($uriparts,$method,$postbody,$jwtpresented);
                if($feedback->userIdOfRequest()!==false)
                {// jtw validated, process request
                    $feedback->processRequest();
                    echo $feedback->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                }

                break;
                
            case 'castle':
                $castle=new castle($uriparts,$method,$postbody,$jwtpresented);
                if($castle->userIdOfRequest()!==false)
                {// jtw validated, process request
                    $castle->processRequest();
                    echo $castle->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                }

                break;
                        
    
            default:
                $sandpiper=new sandpiper;
                $sandpiper->logEvent('', '', '', 'unexpected '.$method.' to ['.$_SERVER['REQUEST_URI'].'] by '.$_SERVER['REMOTE_ADDR'].' - http 404 sent.');
                echo 'Unexpected input. Was expecting a verb like login, plans, slices or activity. Got this instead: '.$root;
                http_response_code(404);
                break;
        }
        
        break;
    
    
    default:
        $sandpiper=new sandpiper;
        $sandpiper->logEvent('', '', '', 'unexpected '.$method.' to ['.$_SERVER['REQUEST_URI'].'] by '.$_SERVER['REMOTE_ADDR'].' - http 404 sent.');
        echo 'Unexpected input. Was expecting a version number (like v1) or the check verb.';
        http_response_code(404);
        break;
}
?>