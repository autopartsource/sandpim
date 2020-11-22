<?php
include_once('../class/sandpiperAPIclass.php');

$uriparts= explode('/', $_SERVER['REQUEST_URI']);
$method=$_SERVER['REQUEST_METHOD'];
$bodyraw=file_get_contents('php://input');
$postbody= json_decode($bodyraw,true);

$jwt='';
if(array_key_exists('HTTP_AUTHORIZATION',$_SERVER))
{
 if(substr($_SERVER['HTTP_AUTHORIZATION'], 0, 7)=='Bearer ')
 {
  $jwt=substr($_SERVER['HTTP_AUTHORIZATION'], 7);
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
            echo $sandpiper->authenticateUser($postbody['username'], $postbody['password'], $_SERVER['REMOTE_ADDR']);
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
                $activity=new activity($uriparts,$jwt);
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

                break;
                
            case 'grains':
                $grains=new grains($uriparts,$method,$postbody,$jwt);
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
                break;
            
            case 'subs':

                break;
                
            case 'users':

                break;
    
            default: break;
        }
        
        break;
    
    
    default: break;
}
?>