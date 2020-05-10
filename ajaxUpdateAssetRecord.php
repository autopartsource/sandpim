<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
session_start();
$pim= new pim;
$asset=new asset;

//$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)); fclose($fp);

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
