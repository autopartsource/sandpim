<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddAppAttribute.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);

/*
 * add a VCdb, Qdb or Note attribute to an app
 * for vcdb, name like "RearBrakeType", value like "5"
 * for qdb,  name like "21627", value like "123, ABC, XYZ|~12|Inch~4|Inch"    (redered result would be: Axle #123, ABC, XYZ With 12 Inch x 4 Inch Brakes)
 * for note, name like "note",  value like "This is a free-form note."
 */

if(isset($_SESSION['userid']) && isset($_GET['appid']) && isset($_GET['type']) && isset($_GET['name']) && isset($_GET['value']) && isset($_GET['cosmetic']))
{
 $vcdb=new vcdb;
 $qdb=new qdb;
 
 $result=array('success'=>false,'id'=>0,'oid'=>'','nicetext'=>'asdsadsadsad');

 $appid=intval($_GET['appid']);
 $cosmetic= intval($_GET['cosmetic']);
 $name=$_GET['name'];
 $value=$_GET['value'];
 $userid=$_SESSION['userid'];
 $topsequence=$pim->highestAppAttributeSequence($appid);
  
 if($app=$pim->getApp($appid))
 {
  $oldoid=$app['oid'];
  switch($_GET['type'])
  {
   case 'vcdb':
    if($id=$pim->addVCdbAttributeToApp($appid,$name,$value,$topsequence+1,$cosmetic,true))
    {
     $pim->cleansequenceAppAttributes($appid);
     $result['oid']=$pim->getOIDofApp($appid);
     $pim->logAppEvent($appid,$userid,'VCdb attribute ['.$name.':'.$value.'] added ',$result['oid']);
     $result['success']=true; $result['id']=$id;$result['nicetext']=$vcdb->niceVCdbAttributePair(array('name'=>$name,'value'=>$value));
    }
    break;

   case 'qdb':
    //name like "21627", value like "123, ABC, XYZ|~12|mm~4|mm"    (redered result would be: Axle #123, ABC, XYZ With 12mm x 4mm Brakes)
    if($qdbid=intval($name))
    {
     if($id=$pim->addQdbAttributeToApp($appid,$qdbid,$value,$topsequence+1,$cosmetic,true))
     {
      $pim->cleansequenceAppAttributes($appid);
      $result['oid']=$pim->getOIDofApp($appid);
      $nicequalifier=$qdb->qualifierText($qdbid, explode('~', str_replace('|','',$value)));
      $pim->logAppEvent($appid,$userid,'Qdb attribute ['.$nicequalifier.'] added ', $result['oid']);
      $result['success']=true; $result['id']=$id; $result['nicetext']=$nicequalifier;
     }
    }
    break;

   case 'note':
    if($id=$pim->addNoteAttributeToApp($appid,$value,$topsequence,$cosmetic,true))
    {
     $pim->cleansequenceAppAttributes($appid);
     $result['oid']=$pim->getOIDofApp($appid);
     $pim->logAppEvent($appid,$userid,'Fitment note ['.$value.'] added', $result['oid']);
     $result['success']=true; $result['id']=$id; $result['nicetext']=$value;
    }
    break;

   default: break;
  }
 
 }
 echo json_encode($result);
}?>
