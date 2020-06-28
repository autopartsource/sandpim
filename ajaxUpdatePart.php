<?php
include_once('./class/pimClass.php');
session_start();
$pim= new pim;

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true).'*'); fclose($fp);
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
   $pim->setPartCategory($partnumber,intval($_GET['value']),false);
   $pim->logPartEvent($partnumber,$userid,'category changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'internalnotes':
   $pim->setPartInternalnotes($partnumber,$_GET['value']);
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


  default:
   break;
 }

 echo $oid;
}?>
