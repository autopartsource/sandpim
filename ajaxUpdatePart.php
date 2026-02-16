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
// used for setting scalar values of parts ('parttypeid','lifecyclestatus','partcategory','replacedby','gtin','unspc', 'basepart', etc)
// not used for adding/removing one-to-many things like prices,interchanges,packages,assets,attributes,applications.
 
$success=false;
$message='';
if(isset($_SESSION['userid']) && isset($_GET['partnumber']) && isset($_GET['elementid']) && isset($_GET['value']))
{
 $partnumber=($_GET['partnumber']);
 $userid=$_SESSION['userid'];
 $part=$pim->getPart($partnumber);
 $oid=$part['oid'];

 
 // determine if this part being changed has dependant parts that need their oids changed as a result of this change
 $dependantparts=$pim->getPartnumbersByBasepart($partnumber);
 
 
 switch($_GET['elementid'])
 {
  case 'parttypeid':
  if($part['parttypeid']!=$_GET['value'])
  {
   $pim->setPartParttype($partnumber,intval($_GET['value']),true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'parttype changed to:'.intval($_GET['value']),$oid);
   $success=true;
  }
  break;

  case 'lifecyclestatus':
  if($part['lifecyclestatus']!=$_GET['value'] && in_array($_GET['value'], ['0','1','2','3','4','5','6','7','8','9','A','B']))
  {
   $pim->setPartLifecyclestatus($partnumber,$_GET['value'],true);
   // deal with updating date fields in the part table   
   if($_GET['value']=='2'){$pim->setPartAvailableDate($partnumber, date('Y-m-d'), false);}
   if($_GET['value']=='7'){$pim->setPartSupersededdDate($partnumber, date('Y-m-d'), false);}
   if($_GET['value']=='8'){$pim->setPartDiscontinuedDate($partnumber, date('Y-m-d'), false);}
   if($_GET['value']=='9'){$pim->setPartObsoletedDate($partnumber, date('Y-m-d'), false);}
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'lifecycle status changed to:'.$_GET['value'],$oid);
   $success=true;
  }
  break;

  case 'partcategory':
  if($part['partcategory']!=$_GET['value'])
  {
   $pim->setPartCategory($partnumber,intval($_GET['value']),true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'category changed to:'.intval($_GET['value']),$oid);
   $success=true;
  }
  break;

  case 'internalnotes':
   $pim->setPartInternalnotes($partnumber,$_GET['value'],true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'internal notes updated',$oid);
   $success=true;
  break;

  case 'description':
   $pim->setPartDescription($partnumber,$_GET['value'],true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'description updated to:'.$_GET['value'],$oid);
   $success=true;
  break;

  case 'gtin':
   if(strlen($_GET['value'])==12 && is_numeric($_GET['value']))
   {
    if($pim->isValidBarcode($_GET['value']))
    {
     $pim->setPartGTIN($partnumber,$_GET['value'],true);
     $oid=$pim->getOIDofPart($partnumber);
     $pim->logPartEvent($partnumber,$userid,'GTIN updated to:'.$_GET['value'],$oid);
     $success=true;
    }
    else
    {// checkdigit it not valid
     $message='GTIN has wrong check digit';   
    }
   }
   else
   {// input was not 12 digits of numeric digits
    $message='GTIN must be 12 numeric digits';              
   }
  break;

  case 'unspc':
   $pim->setPartUNSPC($partnumber,$_GET['value'],true);
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$userid,'UNSPC updated to:'.$_GET['value'],$oid);
   $success=true;
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
     $success=true;
    }
    else
    {// 
     if($pim->validPart($_GET['value']))
     {
      $pim->setPartReplacedby($partnumber,$_GET['value'],true);
      $pim->setPartLifecyclestatus($partnumber,'7',false);
      $oid=$pim->getOIDofPart($partnumber);
      $pim->logPartEvent($partnumber,$userid,'Replaced By updated to:'.$_GET['value'].' and set lifecycle status to Superseded',$oid);   
      $success=true;
     }
     else
     {// given part is not valid
       $message='replaced-by partnumber ['.$pim->sanitizePartnumber($_GET['value']).'] is not valid.';        
     }
    }     
   }
      
  break;

  case 'basepart':
           
   if($part['basepart']!=trim($_GET['value']))
   {// existing basepart is different that new basepart - an actual change has happened
    if(trim($_GET['value'])=='')
    {// we are un-basing a part (making basepart blank) 
     $pim->setPartBasepart($partnumber, '', true);
     $oid=$pim->getOIDofPart($partnumber);
     $pim->logPartEvent($partnumber,$userid,'Basepart updated to null',$oid);   
     $success=true;
    }
    else
    {// we are setting the base to something non-blank. Validate 
     if($pim->validPart($_GET['value']))
     {// given basepart is a valid part
      if($pim->basepartOfPart($_GET['value'])=='')
      { // given new basepart does not have a basepart (good)        
       $pim->setPartBasepart($partnumber, trim($_GET['value']), true);
       $oid=$pim->getOIDofPart($partnumber);
       $pim->logPartEvent($partnumber,$userid,'Basepart updated to:'.$_GET['value'],$oid);
       $success=true;
      }
      else
      {// given new basepart has a basepart (bad)
       $message='Base partnumber ['.$pim->sanitizePartnumber($_GET['value']).'] cannot be use as a base because it is based on another part. Inheritance can only go one generation back.';
      }
     }
     else
     {// non-valid part was given as a base
       $message='base partnumber ['.$pim->sanitizePartnumber($_GET['value']).'] is not valid.';         
     }
    }      
   }
  
  
  default:
   break;
 }
 $pim->addAuditRequest('part-general', $partnumber);
 
 $result=array('success'=>$success,'oid'=>$oid, 'message'=>$message);
 echo json_encode($result);
}?>
