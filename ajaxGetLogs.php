<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxGetLogs.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$logs= new logs;
$events=array();


if(isset($_SESSION['userid']) && isset($_GET['logname']) && isset($_GET['requesttype']))
{

    switch($_GET['logname'])
    {
        case 'system':
 
            switch($_GET['requesttype'])
            {
                case 'latest':

                    break;

                case 'specific':

                    break;

                default:
                    break;
            }
            break;
        
        case 'application':
 
            switch($_GET['requesttype'])
            {
                case 'latest':
                    $events=$logs->getAppsEvents(intval($_GET['size']));
                    break;

                case 'specific':

                    break;

                default:
                    break;
            }
            break;

        case 'part':
 
            switch($_GET['requesttype'])
            {
                case 'latest':
                    $events = $logs->getPartsEvents(intval($_GET['size']));
                    break;

                case 'specific':

                    break;

                default:
                    break;
            }
            break;
        
        case 'asset':
 
            switch($_GET['requesttype'])
            {
                case 'latest':
                    $events = $logs->getAssetsEvents(intval($_GET['size']));

                    break;

                case 'specific':

                    break;

                default:
                    break;
            }
            break;
        
        case 'sandpiper':
 
            switch($_GET['requesttype'])
            {
                case 'latest':

                    break;

                case 'specific':

                    break;

                default:
                    break;
            }
            break;


        default:
            break;
    }
    echo json_encode($events);
}?>
