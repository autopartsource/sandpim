<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxNewApp.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

if(isset($_SESSION['userid']) && isset($_GET['basevehicleid']) && isset($_GET['quantityperapp']) && isset($_GET['partnumber']) && isset($_GET['cosmetic']) && isset($_GET['fitment']) && isset($_GET['positionandparttype']))
{
 $userid=$_SESSION['userid'];

 $attributes=unserialize(base64_decode($_GET['fitment']));
 $positionandparttype=unserialize(base64_decode($_GET['positionandparttype']));
 $positionid=intval($positionandparttype['positionid']);
 $parttypeid=intval($positionandparttype['parttypeid']);
 $basevehicleid=intval($_GET['basevehicleid']);
 $quantityperapp=intval($_GET['quantityperapp']);
 $partnumber=$_GET['partnumber'];
 $cosmetic=intval($_GET['cosmetic']);
 $newappid=$pim->newApp($basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber,$cosmetic,$attributes,'');
 $oid=$pim->getOIDofApp($newappid);
 $pim->logAppEvent($newappid,$userid,'app created by drag/copy',$oid);
 echo $newappid;
}?>
