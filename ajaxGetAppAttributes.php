<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/logsClass.php');

$pim=new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxGetAppAttributes.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

if(isset($_SESSION['userid']) && isset($_GET['appid']))
{
 $vcdb=new vcdb;
 $qdb=new qdb;
 
 $result=array();
 $appid=intval($_GET['appid']);
 $userid=$_SESSION['userid'];
 
 $attributes=$pim->getAppAttributes($appid);
 foreach ($attributes as $attribute)
 {
  $niceattribute='';
  if($attribute['type']=='vcdb'){$niceattribute=$vcdb->niceVCdbAttributePair($attribute);}
  if($attribute['type']=='qdb'){$niceattribute=$qdb->qualifierText(intval($attribute['name']),explode('~', str_replace('|','',$attribute['value'])));}
  if($attribute['type']=='note'){$niceattribute=$attribute['value'];}
  $result[]=array('id'=>$attribute['id'],'name'=>$attribute['name'],'value'=>$attribute['value'],'type'=>$attribute['type'],'sequence'=>$attribute['sequence'],'cosmetic'=>$attribute['cosmetic'],'nicetext'=>$niceattribute);
 }
 echo json_encode($result);
}
?>
