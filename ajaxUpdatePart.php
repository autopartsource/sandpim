<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxUpdatePart.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
// used for setting scalar values of parts ('parttypeid','lifecyclestatus','partcategory','replacedby','gtin','unspc', etc)
// not used for adding/removing one-to-many things like prices,interchanges,packages,assets,attributes.
 
if(isset($_SESSION['userid']) && isset($_GET['partnumber']) && isset($_GET['elementid']) && isset($_GET['value']))
{
 $partnumber=($_GET['partnumber']);
 $userid=$_SESSION['userid'];
 $part=$pim->getPart($partnumber);
 $oid=$part['oid'];

 
 switch($_GET['elementid'])
 {
  case 'parttypeid':
  if($part['parttypeid']!=$_GET['value'])
  {
   $pim->setPartParttype($partnumber,intval($_GET['value']),true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'parttype changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'lifecyclestatus':
  if($part['lifecyclestatus']!=$_GET['value'])
  {
   $pim->setPartLifecyclestatus($partnumber,$_GET['value'],true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'lifecycle status changed to:'.$_GET['value'],$oid);
  }
  break;

  case 'partcategory':
  if($part['partcategory']!=$_GET['value'])
  {
   $pim->setPartCategory($partnumber,intval($_GET['value']),true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'category changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'internalnotes':
   $pim->setPartInternalnotes($partnumber,$_GET['value'],true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'internal notes updated',$oid);
  break;

  case 'description':
   $pim->setPartDescription($partnumber,$_GET['value'],true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'description updated to:'.$_GET['value'],$oid);
  break;

  case 'gtin':
   $pim->setPartGTIN($partnumber,$_GET['value'],true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'GTIN updated to:'.$_GET['value'],$oid);
  break;

  case 'unspc':
   $pim->setPartUNSPC($partnumber,$_GET['value'],true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'UNSPC updated to:'.$_GET['value'],$oid);
  break;

  case 'replacedby':
           
   if($part['replacedby']!=$_GET['value'])
   {// existing replacedby is different that new replacedby - an actual change has happened
    if(trim($_GET['value'])=='')
    {// 
     $pim->setPartReplacedby($partnumber,'',true);
     //$pim->setPartLifecyclestatus($partnumber,'2',false);
     $oid=$pim->getOIDofPart($partnumber);
     $pim->logPartEvent($partnumber,$userid,'Replaced By updated to null',$oid);   
    }
    else
    {// 
     if($pim->validPart($_GET['value']))
     {
      $pim->setPartReplacedby($partnumber,$_GET['value'],true);
      $pim->setPartLifecyclestatus($partnumber,'7',false);
      $oid=$pim->getOIDofPart($partnumber);
      $pim->logPartEvent($partnumber,$userid,'Replaced By updated to:'.$_GET['value'].' and set lifecycle status to Superseded',$oid);   
     }
    }      
   }
      
  break;

  default:
   break;
 }

 echo $oid;
}?>
