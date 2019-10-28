<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');

$vcdb=new vcdb;
$pcdb=new pcdb;
$pim=new pim;
$userid=0;

$appid=intval($_GET['appid']);

if(isset($_POST))
{
 if(isset($_POST['submit']) && $_POST['submit']=='Undelete')
 {
  $pim->setAppStatus($appid,0);
  $pim->logHistoryEvent($appid,$userid,'app was un-deleted','');
 }
 if(isset($_POST['submit']) && $_POST['submit']=='Unhide')
 {
  $pim->setAppStatus($appid,0);
  $pim->logHistoryEvent($appid,$userid,'app was un-hidden','');
 }
 if(isset($_POST['submit']) && $_POST['submit']=='Delete')
 {
  $pim->setAppStatus($appid,1);
  $pim->logHistoryEvent($appid,$userid,'app was deleted','');
 }
 if(isset($_POST['submit']) && $_POST['submit']=='Hide')
 {
  $pim->setAppStatus($appid,2);
  $pim->logHistoryEvent($appid,$userid,'app was hidden','');
 }


 if(isset($_POST['submit']) && $_POST['submit']=='Update Qty')
 {
  $pim->setAppQuantity($appid,intval($_POST['quantityperapp']),true);
  $pim->logHistoryEvent($appid,$userid,'quantityperapp was changed to:'.intval($_POST['quantityperapp']),'');
 }

 if(isset($_POST['submit']) && $_POST['submit']=='Update Category')
 {
  $pim->setAppCategory($appid,intval($_POST['appcategory']));
  $pim->logHistoryEvent($appid,$userid,'appcategory was changed to:'.intval($_POST['appcategory']),'');
 }

 if(isset($_POST['submit']) && $_POST['submit']=='Update Type')
 {
  $pim->setAppParttype($appid,intval($_POST['parttype']),true);
  $pim->logHistoryEvent($appid,$userid,'parttype was changed to:'.intval($_POST['parttype']),'');
 }

 if(isset($_POST['submit']) && $_POST['submit']=='Update Position')
 {
  $pim->setAppPosition($appid,intval($_POST['position']),true);
  $pim->logHistoryEvent($appid,$userid,'quantityperapp was changed to:'.intval($_POST['position']),'');
 }

 if(isset($_POST['submit']) && $_POST['submit']=='Add Attribute')
 {
  $bits=explode('_',$_POST['vcdbattribute']);
  if(count($bits)==2 && intval($bits[1])>0)
  {
   $vcdbattributename=$bits[0]; $vcdbattributevalue=intval($bits[1]); $cosmetic=0;
   $topsequence=$pim->highestAppAttributeSequence($appid);
   $pim->addVCdbAttributeToApp($appid,$vcdbattributename,$vcdbattributevalue,$topsequence+1,$cosmetic);
   $pim->cleansequenceAppAttributes($appid);
   $pim->logHistoryEvent($appid,$userid,'VCdb attribute added '.$vcdbattributename.'='.$vcdbattributevalue,'');
  }
 }

 if(isset($_POST['submit']) && $_POST['submit']=='Add Note')
 {
  if(strlen(trim($_POST['note']))>0)
  {
   $cosmetic=0;
   $topsequence=$pim->highestAppAttributeSequence($appid);
   $pim->addNoteAttributeToApp($appid,trim($_POST['note']),$topsequence,$cosmetic);
   $pim->cleansequenceAppAttributes($appid);
   $pim->logHistoryEvent($appid,$userid,'Fitment note added: '.trim($_POST['note']),'');
  }
 }

 if(isset($_POST['submit']) && $_POST['submit']=='Cosmetic App')
 {
  $pim->toggleAppCosmetic($appid);
  $pim->logHistoryEvent($appid,$userid,'App cosmetic was toggled','');
 }

 foreach($_POST as $post_key=>$post_value)
 {
  if(strstr($post_key,'cosmetic_'))
  { // toggle cosmeticness for a specific app attribute
   $bits=explode('_',$post_key);
   $pim->toggleAppAttributeCosmetic($appid,$bits[1]);
   $pim->cleansequenceAppAttributes($appid);
  }

  if(strstr($post_key,'sequenceup_'))
  { // increase the sequence value for a specific app attribute to change its position relative to its peers
   $bits=explode('_',$post_key);
   $pim->incAppAttributeSequence($appid,$bits[1]);
  }

  if(strstr($post_key,'remove_'))
  { // delete a specific app attribute to change its position relative to its peers
   $bits=explode('_',$post_key);
   $pim->deleteAppAttribute($appid,$bits[1]);
   $pim->cleansequenceAppAttributes($appid);
  }
 }

}


$app=$pim->getApp($appid);
//print_r($app);

$appcolor='#d0f0c0'; if($app['cosmetic']>0){$appcolor='#33FFD7';} if($app['status']>1){$appcolor='#FFD433';} if($app['status']==1){$appcolor='#FF5533';}

$attributecolors=array('vcdb'=>'52BE80','qdb'=>'6060F0','note'=>'C0C0C0');
$niceattributes=array();
foreach($app['attributes'] as $appattribute)
{
 if($appattribute['type']=='vcdb'){$niceattributes[]=array('sequence'=>$appattribute['sequence'],'text'=>$vcdb->niceVCdbAttributePair($appattribute),'cosmetic'=>$appattribute['cosmetic'],'type'=>$appattribute['type'],'id'=>$appattribute['id']);}
 if($appattribute['type']=='note'){$niceattributes[]=array('sequence'=>$appattribute['sequence'],'text'=>$appattribute['value'],'cosmetic'=>$appattribute['cosmetic'],'type'=>$appattribute['type'],'id'=>$appattribute['id']);}
}

$nicefitmentarray=array(); foreach($niceattributes as $niceattribute){$nicefitmentarray[]=$niceattribute['text'];}
$nicefitmentstring=implode('; ',$nicefitmentarray);

$allattributes=$vcdb->getACESattributesForBasevehicle($app['basevehicleid']); //print_r($allattributes);

$assets=array();
$assets_linked_to_item=array();
$appcategories=$pim->getAppCategories();
$favoriteparttypes=$pim->getFavoriteParttypes();
$favoritepositions=$pim->getFavoritePositions();
$mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
$pcdbversion=$pcdb->version();
$historylimit=10;
$history=$pim->getHistoryEventsForApp($appid,$historylimit);

?>
<html>
 <head>
 </head>
 <body>
<?php include('topnav.inc');?>
  <?php if($app)
  {;?>
   <div style="padding:10px;">

    <?php
     if($pcdb->parttypeName($app['parttypeid'])=='not found'){echo '<div style="color:red;">Part Type id '.$app['parttypeid'].' is not found in the loaded ('.$pcdbversion.') PCdb</div>';}
     if($pcdb->positionName($app['positionid'])=='not found'){echo '<div style="color:red;">Position id '.$app['positionid'].' is not found in the loaded ('.$pcdbversion.') PCdb</div>';}
    ?>

    <table border="1" cellpadding="5">
     <tr><th bgcolor="<?php echo $appcolor;?>" align="left">Base Vehicle</th><td align="left"><?php echo '<a href="appsIndex.php">'.$vcdb->makeName($mmy['MakeID']).'</a>  <a href="mmySelectModel.php?makeid='.$mmy['MakeID'].'">'.$vcdb->modelName($mmy['ModelID']).'</a> <a href="mmySelectYear.php?makeid='.$mmy['MakeID'].'&modelid='.$mmy['ModelID'].'">'.$mmy['year'].'</a>';?></td></tr>
     <tr><th bgcolor="<?php echo $appcolor;?>" align="left">Part</th><td align="left"><a href="showPart.php?partnumber=<?php echo $app['partnumber'];?>"><?php echo $app['partnumber'];?></a></td></tr>

     <form method="post" action="showApp.php?appid=<?php echo $appid;?>">
      <tr><th bgcolor="<?php echo $appcolor;?>" align="left">Application<br/>Part Type</th><td align="right"><select name="parttype"><option value="0">Undefined</option><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"<?php if($parttype['id']==$app['parttypeid']){echo ' selected';}?>><?php echo $parttype['name'];?></option><?php }?></select><input type="submit" name="submit" value="Update Type"/></td></tr>
     </form>

     <form method="post" action="showApp.php?appid=<?php echo $appid;?>">
      <tr><th bgcolor="<?php echo $appcolor;?>" align="left">Position</th><td align="right"><select name="position"><option value="0">Undefined</option><?php foreach($favoritepositions as $position){?> <option value="<?php echo $position['id'];?>"<?php if($position['id']==$app['positionid']){echo ' selected';}?>><?php echo $position['name'];?></option><?php }?></select><input type="submit" name="submit" value="Update Position"/></td></tr>
     </form>

     <tr><th bgcolor="<?php echo $appcolor;?>" align="left">Fitment<br/>Qualifiers</th>
      <td align="left">
       <form method="post" action="showApp.php?appid=<?php echo $appid;?>">
        <?php foreach($niceattributes as $niceattribute){$text_decoration=''; if($niceattribute['cosmetic']==1){$text_decoration='text-decoration: line-through;';} echo '<div style="border:solid 1px;margin:5px;'.$text_decoration.'padding:2px;background-color:#'.$attributecolors[$niceattribute['type']].';">'.$nicefitmentarray[]=$niceattribute['text'].' <input type="submit" name="cosmetic_'.$niceattribute['id'].'" value="Cosmetic"/><input type="submit" name="sequenceup_'.$niceattribute['id'].'" value="Down"/><input type="submit" name="remove_'.$niceattribute['id'].'" value="Remove"/></div>';} ?>
       </form>

       <form method="post" action="showApp.php?appid=<?php echo $appid;?>">
        <div>
         <select name="vcdbattribute"><option value="">-- Select a VCdb Attribute --</option> <?php foreach($allattributes as $attributekey=>$allattributename){echo '<option value="'.$attributekey.'">'.$allattributename.'</option>';}?> </select>
         <input type="submit" name="submit" value="Add Attribute"/><br/>
         <input type="text" name="note"/><input type="submit" name="submit" value="Add Note"/>
        </div>
        </form>
       </td>
      </tr>

     <form method="post" action="showApp.php?appid=<?php echo $appid;?>">
      <tr><th bgcolor="<?php echo $appcolor;?>" align="left">Quantity<br/>(on this vehicle)</th><td align="right"><input type="text" name="quantityperapp" size="1" value="<?php echo $app['quantityperapp'];?>"/><input type="submit" name="submit" value="Update Qty"/></td></tr>
      <tr><th bgcolor="<?php echo $appcolor;?>" align="left">Cosmetic</th><td align="right"><?php if($app['cosmetic']){echo 'App is Cosmetic ';}?><input type="submit" name="submit" value="Cosmetic App"/></td></tr>

      <tr><th bgcolor="<?php echo $appcolor;?>" align="left">Category</th><td align="right"><select name="appcategory"> <?php foreach($appcategories as $appcategory){?> <option value="<?php echo $appcategory['id'];?>"<?php if($appcategory['id']==$app['appcategory']){echo ' selected';}?>><?php echo $appcategory['name'];?></option><?php }?></select><input type="submit" name="submit" value="Update Category"/></td></tr>

      <tr><th bgcolor="<?php echo $appcolor;?>" align="left">Fitment<br/>Assets</th><td align="right"><?php if(count($assets)){foreach($assets as $asset) { echo '<div><a title="view this asset in new browser window" href="'.$asset['uri'].'" target="_blank">'.$asset['assetId'].'</a> (representation:'.$asset['representation'].', sequence: '.$asset['assetItemOrder'].') <a title="remove this asset from the application" href="./showAdminApplication.php?id='.intval($_GET['id']).'&removeasset='.$asset['id'].'">x</a><div>'; }}?>
      <div>Asset <select name="assetid"><option value=""></option>
      <?php
      foreach($assets_linked_to_item as $asset)
      {
       $already_applied=false; foreach($assets as $tempasset){if($tempasset['assetId']==$asset['assetId']){$already_applied=true;}} if($already_applied){continue;}
       echo '<option value="'.$asset['assetId'].'">'.$asset['fileType'].' - '.$asset['filename'].' ('.$asset['description'].')</option>';
      }?>
       </select>
       Sequence <input type="text" name="assetorder" size="3" value="1"/>
       <select name="representation"><option value="A">Actual</option><option value="R">Representative</option></select>
      </div></td></tr>
      <tr><th bgcolor="<?php echo $appcolor;?>" align="left">Internal<br/>Notes</th><td><textarea name="comments" cols="50"><?php echo $app['internalnotes'];?></textarea></td><tr>
      <tr><th bgcolor="<?php echo $appcolor;?>" align="left">IDs</th><td>Application ID: <?php echo $app['id'];?><br/>Sandpiper OID: <?php echo $app['oid'];?><br/>BaseVehicle ID: <?php echo $app['basevehicleid'];?></td><tr>
      <tr><th bgcolor="<?php echo $appcolor;?>" align="left">Status</th><td align="right">

<?php switch($app['status']){case 0:?>
App is Active <input type="submit" name="submit" value="Delete"/> <input type="submit" name="submit" value="Hide"/>
<?php break; case 1:?>
App is Deleted <input type="submit" name="submit" value="Undelete"/>
<?php break; case 2:?>
App is Hidden <input type="submit" name="submit" value="Unhide"/> <input type="submit" name="submit" value="Delete"/>
<?php break; default:?>
App status is invalid  <input type="submit" name="submit" value="Undelete"/>
<?php }?></td></tr>
     </form>

    </table>
  </div>
  <div>
  <?php print_r($history);?>
  </div>
  <?php }
  else
  { // no apps found
   echo 'Application not found';
  }?>
 </body>
</html>

