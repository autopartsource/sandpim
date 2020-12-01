<?php
include_once('../class/sandpiperAPIclass.php');
include_once('../class/pimClass.php');

$pim = new pim;

 //ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'sandpiper index.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

// split the request URI into an array of levels by "/"
$uriparts= explode('/', $_SERVER['REQUEST_URI']);
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
        
        echo '"Sandpiper API OK"';
        break;

    case 'login':
        $sandpiper=new sandpiper;
        if(array_key_exists('username',$postbody) &&  array_key_exists('password',$postbody))
        {// user and pass were provided
            $plandocument=''; if(array_key_exists('plandocument',$postbody)){$plandocument=$postbody['plandocument'];}
            
            echo $sandpiper->authenticateUser($postbody['username'], $postbody['password'], $plandocument, $_SERVER['REMOTE_ADDR']);
        }
        else
        {// username or password not present in login post body
            echo 'bad request';
        }
        break;
    
    
    case 'v1':

       
        $temp=explode('?',$uriparts[3]);
        $root=$temp[0];
        
        
        switch($root)
        {
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
                }
                break;
            
            case 'apikey':
                
                break;
            
            case 'companies':
                $companies=new companies($uriparts,$method,$postbody,$jwtpresented);
                if($companies->userIdOfRequest()!==false)
                {// jtw validated, process request
                    $companies->processRequest();
                    echo $companies->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                }

                break;
                
            case 'grains':
                $grains=new grains($uriparts,$method,$postbody,$jwtpresented);
                if($grains->userIdOfRequest()!==false)
                {// jtw validated, process request
                    $grains->processRequest();
                    echo $grains->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                }
                break;
            
            case 'slices':
                $slices=new slices($uriparts,$method,$postbody,$jwtpresented);
                if($slices->userIdOfRequest()!==false)
                {// jtw validated, process request
                    $slices->processRequest();
                    echo $slices->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                }
                break;
            
            case 'subs':
                $subs=new subs($uriparts,$method,$postbody,$jwtpresented);
                if($subs->userIdOfRequest()!==false)
                {// jtw validated, process request
                    $subs->processRequest();
                    echo $subs->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                }

                break;

            case 'tags':
                $tags=new tags($uriparts,$method,$postbody,$jwtpresented);
                if($tags->userIdOfRequest()!==false)
                {// jtw validated, process request
                    $tags->processRequest();
                    echo $tags->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                }

                break;
                
            case 'users':
                $users=new users($uriparts,$method,$postbody,$jwtpresented);
                if($users->userIdOfRequest()!==false)
                {// jtw validated, process request
                    $users->processRequest();
                    echo $users->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                }
                break;

            case 'sync':
                $sync=new sync($uriparts,$method,$postbody,$jwtpresented);
                if($sync->userIdOfRequest()!==false)
                {// jtw validated, process request
                    $sync->processRequest();
                    echo $sync->response;      
                }
                else
                {// send the "not authorized" code
                    http_response_code(401);
                }
                break;
    
            default: break;
        }
        
        break;
    
    
    default: break;
}
?>