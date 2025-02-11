<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxUpdateAssetRecord.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$asset=new asset;

if(isset($_SESSION['userid']) && isset($_GET['id']) && isset($_GET['elementid']) && isset($_GET['value']))
{
    $id=intval($_GET['id']);
    $userid=$_SESSION['userid'];
    $returnval='';
    
    switch($_GET['elementid'])
    {
      case 'public':
            if($_GET['value']=='toggle')
            {
                $asset->toggleAssetPublic($id);
                $returnval=$asset->niceBoolText($asset->getAssetById($id)['public'],'Public','Private');
            }
            //  $pim->logAppEvent($appid,$userid,'cosmetic toggled',$oid);
            break;

        case 'uripublic':
            if($_GET['value']=='toggle')
            {
                $asset->toggleAssetUriPublic($id);
            }
            //$pim->logAppEvent($appid,$userid,'cosmetic toggled',$oid);
            break;


        default:
            break;
    }
  echo $returnval;
 }?>
