<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/pricingClass.php');
include_once('./class/replicationClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');
$navCategory = 'export';

$pim = new pim();


//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exportSystemStream.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}
if(!$pim->userHasNavelement($_SESSION['userid'], 'EXPORT/SYSTEM')){echo 'access denied'; $logs->logSystemEvent('accesscontrol', $_SESSION['userid'], 'denied:EXPORT/SYSTEM'); exit;}

$interchange=new interchange();
$assetclass=new asset();
$pricing=new pricing();
$replication=new replication();
$configGet=new configGet();
$logs=new logs();
$userclass= new user();

$configs=$configGet->getAllConfigValues();
$xml='<SandPIM schemaversion="1.0" systembuild="'.$pim->buildVersion().'" exportdatetime="'.date('Y-m-d').'T'.date('H:i:s').'">'."\r\n";
$xml.=" <configs>\r\n"; foreach($configs as $config){$xml.='  <config name="'.$config['configname'].'" value="'. base64_encode($config['configvalue']).'"/>'."\r\n";} $xml.=" </configs>\r\n";  

$users=$userclass->getUsers();//    $users[]=array('id'=>$row['id'],'username'=>$row['username'],'name'=>$row['name'],'status'=>$row['status'],'failedcount'=>$row['failedcount'],'hash'=>$row['hash'],'environment'=>$row['environment']);
$xml.=" <users>\r\n";
foreach($users as $user)
{
 $xml.='  <user id="'.$user['id'].'" username="'. $user['username'].'" name="'.base64_encode($user['name']).'" hash="'.base64_encode($user['hash']).'" environment="'.base64_encode($user['environment']).'" status="'.$user['status'].'">'."\r\n";
 $usernavelements=$pim->getUserNavelements($user['id']);
 $xml.='   <navelements>'."\r\n";
 foreach($usernavelements as $usernavelement)
 {
  $xml.='    <navelement navid="'.$usernavelement['navid'].'"/>'."\r\n";
 } 
 $xml.='   </navelements>'."\r\n";
 $userpartcategories=$userclass->getUserVisiblePartcategories($user['id']);
 $xml.='   <partcategories>'."\r\n";
 foreach($userpartcategories as $userpartcategory)
 {
  $xml.='    <partcategory id="'.$userpartcategory['id'].'"/>'."\r\n";
 }
 $xml.='   </partcategories>'."\r\n";
 $xml.='  </user>'."\r\n";
}
$xml.=" </users>\r\n";

$receiverprofiles=$pim->getReceiverprofiles();
$xml.=" <receiverprofiles>\r\n";
foreach($receiverprofiles as $receiverprofile)
{
 $xml.='  <receiverprofile id="'.$receiverprofile['id'].'" name="'.base64_encode($receiverprofile['name']).'" data="'.base64_encode($receiverprofile['data']).'" notes="'.base64_encode($receiverprofile['notes']).'">'."\r\n";

 $assettags=$pim->getAssettagsForReceiverprofile($receiverprofile['id']);
 if(count($assettags)>0)
 { 
  $xml.="   <assettags>\r\n";
  foreach($assettags as $assettag)
  {
   $xml.='    <assettag id="'.$assettag['assettagid'].'"/>'."\r\n";
  }
  $xml.="   </assettags>\r\n";
 } 

 $deliverygroups=$pim->getReceiverprofileDeliverygroupids($receiverprofile['id']);
 if(count($deliverygroups)>0)
 {
  $xml.="   <deliverygroups>\r\n";
  foreach($deliverygroups as $id)
  {
   $xml.='    <deliverygroup id="'.$id.'"/>'."\r\n";     
  }
  $xml.="   </deliverygroups>\r\n";
 }
 
 $lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($receiverprofile['id']);
 if(count($lifecyclestatuses)>0)
 {
  $xml.="   <lifecyclestatuses>\r\n";
  foreach($lifecyclestatuses as $lifecyclestatus)
  {
   $xml.='    <lifecyclestatus code="'.$lifecyclestatus['lifecyclestatus'].'"/>'."\r\n";
  }
  $xml.="   </lifecyclestatuses>\r\n";
 }

 $parttranslations=$pim->getReceiverprofileParttranslations($receiverprofile['id']);
 if(count($parttranslations)>0)
 {
  $xml.="   <parttranslations>\r\n";
  foreach($parttranslations as $internalpart=>$externalpart)
  {
   $xml.='    <parttranslation internalpart="'.$internalpart.'" externalpart="'.$externalpart.'"/>'."\r\n";
  }
  $xml.="   </parttranslations>\r\n";
 }
 
 $pricesheetnumber=$pim->getReceiverprofilePricesheetnumber($receiverprofile['id']);
 if($pricesheetnumber!=''){$xml.='   <pricesheetnumber>'.$pricesheetnumber."</pricesheetnumber>\r\n";} 
 
 $xml.='  </receiverprofile>'."\r\n";
}
$xml.=" </receiverprofiles>\r\n";


$partcategories=$pim->getPartCategories();
if(count($partcategories)>0)
{
 $xml.=" <partcategories>\r\n";
 foreach($partcategories as $partcategory)
 {
  $xml.='  <partcategory id="'.$partcategory['id'].'" name="'.base64_encode($partcategory['name']).'" logouri="'.$partcategory['logouri'].'"/>'."\r\n";
 }
 $xml.=" </partcategories>\r\n";
}



$deliverygroups=$pim->getDeliverygroups();
if(count($deliverygroups)>0)
{
 $xml.=" <deliverygroups>\r\n";
 foreach($deliverygroups as $deliverygroup)
 {
  $partcategories=$pim->getDeliverygroupPartcategories($deliverygroup['id']);
  $xml.='  <deliverygroup id="'.$deliverygroup['id'].'" description="'.base64_encode($deliverygroup['description']).'">'."\r\n";
  foreach($partcategories as $partcategory)
  {
   $xml.='   <partcategory id="'.$partcategory['id'].'"/>'."\r\n";      
  }
  $xml.='  </deliverygroup>'."\r\n"; 
 }
 $xml.=" </deliverygroups>\r\n";
}


$pricesheets=$pricing->getPricesheets(true);// the "true" arg is to include expired entries
//array('number'=>$row['pricesheetnumber'],'description'=>$row['description'],'currency'=>$row['currency'],'pricetype'=>$row['pricetype'],'effectivedate'=>$row['effectivedate'],'expirationdate'=>$row['expirationdate']);
if(count($pricesheets)>0)
{
 $xml.=" <pricesheets>\r\n";
 foreach($pricesheets as $pricesheet)
 {
  $xml.='  <pricesheet number="'.$pricesheet['number'].'" description="'.base64_encode($pricesheet['description']).'" currency="'.$pricesheet['currency'].'" pricetype="'.$pricesheet['pricetype'].'" effectivedate="'.$pricesheet['effectivedate'].'" expirationdate="'.$pricesheet['expirationdate'].'"/>'."\r\n";
 }
 $xml.=" </pricesheets>\r\n";
}

$favoritemakes=$pim->getFavoriteMakes();
if(count($favoritemakes)>0)
{
 $xml.=" <favoritemakes>\r\n";
 foreach($favoritemakes as $favoritemake)
 {
  $xml.='  <make id="'.$favoritemake['id'].'" name="'. base64_encode($favoritemake['name']).'"/>'."\r\n";
 }
 $xml.=" </favoritemakes>\r\n";
}

$parttypes=$pim->getFavoriteParttypes();
if(count($parttypes)>0)
{
 $xml.=" <parttypes>\r\n";
 foreach($parttypes as $parttype)
 {
  $xml.='  <parttype id="'.$parttype['id'].'" name="'. base64_encode($parttype['name']).'"/>'."\r\n";
 }
 $xml.=" </parttypes>\r\n";
}

$positions=$pim->getFavoritePositions();
if(count($positions)>0)
{
 $xml.=" <positions>\r\n";
 foreach($positions as $position)
 {
  $xml.='  <position id="'.$position['id'].'" name="'. base64_encode($position['name']).'"/>'."\r\n";
 }
 $xml.=" </positions>\r\n";
}

$competitivebrands=$interchange->getCompetitivebrands();
if(count($competitivebrands)>0)
{
 $xml.=" <competitivebrands>\r\n";
 foreach($competitivebrands as $competitivebrand)
 {
  $xml.='  <competitivebrand code="'.$competitivebrand['brandAAIAID'].'" description="'. base64_encode($competitivebrand['description']).'"/>'."\r\n";
 }
 $xml.=" </competitivebrands>\r\n";
}

$recipes=$pim->getPartDescriptionRecipes();//     $recipes[]=array('id'=>$row['id'], 'partcategory'=>$row['partcategory'],'parttypeid'=>$row['parttypeid'],'descriptioncode'=>$row['descriptioncode'],'languagecode'=>$row['languagecode']);
if(count($recipes)>0)
{
 $xml.=" <partdescriptionrecipes>\r\n";
 foreach($recipes as $recipe)
 {
  $blocks=$pim->getPartDescriptionRecipeBlocks($recipe['id']);//      $blocks[]=array('id'=>$row['id'],'blocktype'=>$row['blocktype'],'blockparameters'=>$row['blockparameters'],'sequence'=>$row['sequence']);
  $xml.='  <recipe id="'.$recipe['id'].'" partcategory="'.$recipe['partcategory'].'" parttypeid="'.$recipe['parttypeid'].'" descriptioncode="'.$recipe['descriptioncode'].'" languagecode="'.$recipe['languagecode'].'">'."\r\n";
  foreach($blocks as $block)
  {
   $xml.='   <block id="'.$block['id'].'" blocktype="'.$block['blocktype'].'" blockparameters="'. base64_encode($block['blockparameters']).'" sequence="'.$block['sequence'].'" />'."\r\n";
  }
  $xml.='  </recipe>'."\r\n"; 
 }
 $xml.=" </partdescriptionrecipes>\r\n";
}

$replicationpeers=$replication->getAllPeers();
if(count($replicationpeers)>0)
{
 $xml.=" <replicationpeers>\r\n";
 foreach($replicationpeers as $peer)
 {
  $xml.='  <peer id="'.$peer['id'].'" identifier="'. base64_encode($peer['identifier']).'" description="'.base64_encode($peer['description']).'" type="'.$peer['type'].'" role="'.$peer['role'].'" uri="'.$peer['uri'].'" objectlimit="'.$peer['objectlimit'].'" sharedsecret="'.base64_encode($peer['sharedsecret']).'" enabled="'.$peer['enabled'].'" />'."\r\n";
 }
 $xml.=" </replicationpeers>\r\n";
}

$assettags=$assetclass->getAssettags();
if(count($assettags)>0)
{
 $xml.=" <assettags>\r\n";
 foreach($assettags as $assettag)
 {
  $xml.='  <assettag id="'.$assettag['id'].'" tagtext="'. $assettag['tagtext'].'"/>'."\r\n";
 }
 $xml.=" </assettags>\r\n";
}

$allowedhosts=$pim->getAllowedHosts();
if(count($allowedhosts)>0)
{
 $xml.=" <allowedhosts>\r\n";
 foreach($allowedhosts as $allowedhost)
 {
  $xml.='  <host address="'.$allowedhost.'" />'."\r\n";
 }
 $xml.=" </allowedhosts>\r\n";
}




$xml.="</SandPIM>\r\n";

$logs->logSystemEvent('export', $_SESSION['userid'], 'Exported system backup. Client Address: '.$_SERVER['REMOTE_ADDR']);

$filename='sandpimbackup_'.date('Y-m-d').'.xml';
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Type: application/xml');
header('Content-Length: ' . strlen($xml));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');
echo $xml;