<?php
include_once('./class/pimClass.php');
session_start();
$pim= new pim;

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
 $newappid=$pim->newApp($basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber,$cosmetic,$attributes);
 $oid=$pim->getOIDofApp($newappid);
 $pim->logAppEvent($newappid,$userid,'app created by drag/copy',$oid);
 echo $newappid;
}?>
