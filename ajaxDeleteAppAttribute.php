<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/qdbClass.php');
session_start();



if(isset($_SESSION['userid']) && isset($_GET['id']) && isset($_GET['appid']))
{
 $pim=new pim;
 $vcdb=new vcdb;
 $qdb=new qdb;
 
 $result=array('success'=>false, 'oid'=>'');
 $attributeid=intval($_GET['id']);
 $appid=intval($_GET['appid']);
 $userid=$_SESSION['userid'];
 
 if($attribute=$pim->getAppAttribute($attributeid))
 {
  if($attribute['applicationid']==$appid)
  {
   $pim->deleteAppAttribute($appid,$attributeid);
   $result['oid']=$pim->getOIDofApp($appid);
   
   $niceattribute='';
   if($attribute['type']=='vcdb'){$niceattribute='VCdb attribute ['.$vcdb->niceVCdbAttributePair($attribute);}
   if($attribute['type']=='qdb'){$niceattribute='Qdb attribute ['.$qdb->qualifierText(intval($attribute['name']),explode('~', str_replace('|','',$attribute['value'])));}
   if($attribute['type']=='note'){$niceattribute='Note attribute ['.$attribute['value'];}

   $pim->logAppEvent($appid,$userid, $niceattribute.'] removed',$result['oid']);
   $result['success']=true;
  }
 }
 echo json_encode($result);
}?>
