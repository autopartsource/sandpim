<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');

$vcdb=new vcdb;
$pcdb=new pcdb;
$pim=new pim;

$appid=intval($_GET['appid']);

if(isset($_POST['submit']) && $_POST['submit']=='Save'){$pim->updateAppOID($appid);}

$app=$pim->getApp($appid);

$attributecolors=array('vcdb'=>'52BE80','qdb'=>'6060F0','note'=>'C0C0C0');
$niceattributes=array();
foreach($app['attributes'] as $appattribute)
{
 if($appattribute['type']=='vcdb'){$niceattributes[]=array('sequence'=>$appattribute['sequence'],'text'=>$vcdb->niceVCdbAttributePair($appattribute),'cosmetic'=>$appattribute['cosmetic'],'type'=>$appattribute['type'],'id'=>$appattribute['id']);}
 if($appattribute['type']=='note'){$niceattributes[]=array('sequence'=>$appattribute['sequence'],'text'=>$appattribute['value'],'cosmetic'=>$appattribute['cosmetic'],'type'=>$appattribute['type'],'id'=>$appattribute['id']);}
}

$nicefitmentarray=array(); foreach($niceattributes as $niceattribute){$nicefitmentarray[]=$niceattribute['text'];}
$nicefitmentstring=implode('; ',$nicefitmentarray);

$assets=array();
$assets_linked_to_item=array();
$appcategories=$pim->getAppCategories();
$mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
?>
<html>
 <head>
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <?php if($app)
  {;?>
   <div style="padding:10px;">
   <form method="post" action="showApp.php?appid=<?php echo $appid;?>">
    <table border="1" cellpadding="5">
     <tr><th bgcolor="#c0c0c0" align="left">Vehicle</th><td align="left"><?php echo '<a href="appsIndex.php">'.$vcdb->makeName($mmy['MakeID']).'</a>  <a href="mmySelectModel.php?makeid='.$mmy['MakeID'].'">'.$vcdb->modelName($mmy['ModelID']).'</a> <a href="mmySelectYear.php?makeid='.$mmy['MakeID'].'&modelid='.$mmy['ModelID'].'">'.$mmy['year'].'</a>';?></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">Part</th><td align="left"><a href="showPart.php?partnumber=<?php echo $app['partnumber'];?>"><?php echo $app['partnumber'];?></a></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">Part Type</th><td align="left"><?php echo $pcdb->parttypeName($app['parttypeid']);?></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">Position</th><td align="left"><?php echo $pcdb->positionName($app['positionid']);?></td></tr>

     <tr><th bgcolor="#c0c0c0" align="left">Fitment<br/>Qualifiers</th><td align="left">
     <?php

     foreach($niceattributes as $niceattribute){echo '<div style="border:solid 1px;margin:5px;padding:2px;background-color:#'.$attributecolors[$niceattribute['type']].';">'.$nicefitmentarray[]=$niceattribute['text'].'</div>';}


     ?></td></tr>


     <tr><th bgcolor="#c0c0c0" align="left">Quantity<br/>(on this vehicle)</th><td align="right"><input type="text" name="quantity" size="1" value="<?php echo $app['quantityperapp'];?>"/></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">Cosmetic</th><td align="right"><input type="text" name="cosmetic" size="1" value="<?php echo $app['cosmetic'];?>"/></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">Category</th><td align="right"><select name="appcategory"> <?php foreach($appcategories as $appcategory){?> <option value="<?php echo $appcategory['id'];?>"<?php if($appcategory['id']==$app['appcategory']){echo ' selected';}?>><?php echo $appcategory['name'];?></option><?php }?></select></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">Fitment<br/>Assets</th><td align="right"><?php if(count($assets)){foreach($assets as $asset) { echo '<div><a title="view this asset in new browser window" href="'.$asset['uri'].'" target="_blank">'.$asset['assetId'].'</a> (representation:'.$asset['representation'].', sequence: '.$asset['assetItemOrder'].') <a title="remove this asset from the application" href="./showAdminApplication.php?id='.intval($_GET['id']).'&removeasset='.$asset['id'].'">x</a><div>'; }}?>
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
     <tr><th bgcolor="#c0c0c0" align="left">Internal<br/>Notes</th><td><textarea name="comments" cols="50"><?php echo $app['internalnotes'];?></textarea></td><tr>
     <tr><th bgcolor="#c0c0c0" align="left">IDs</th><td>AppID: <?php echo $app['id'];?>, SandpiperOID: <?php echo $app['oid'];?></td><tr>
     <tr><th bgcolor="#c0c0c0" align="left">Status</th><td align="right" <?php if($app['status']==1){echo ' bgcolor="red"';}?> <?php if($app['status']==2){echo ' bgcolor="yellow"';}?>><select name="status"><option value="0"<?php if($app['status']==0){echo ' selected';}?>>Active</option><option value="2"<?php if($app['status']==2){echo ' selected';}?>>Hidden</option><option value="1"<?php if($app['status']==1){echo ' selected';}?>>Deleted</option><option value=""></option></select></td><tr/>
     <tr><th></th><td align="right"><input type="submit" name="submit" value="Save"/></td></tr>
    </table>
   </form>
  </div>
  <?php }
  else
  { // no apps found
   echo 'Application not found';
  }?>
 </div>
 </body>
</html>

