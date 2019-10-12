<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');

$vcdb=new vcdb;
$pcdb=new pcdb;
$pim= new pim;


function niceAppAttributes($appattributes)
{
 $vcdb=new vcdb;
 $niceattributes=array();
 foreach($appattributes as $appattribute)
 {
  if($appattribute['type']=='vcdb'){$niceattributes[]=array('sequence'=>$appattribute['sequence'],'text'=>$vcdb->niceVCdbAttributePair($appattribute),'cosmetic'=>$appattribute['cosmetic']);}
  if($appattribute['type']=='note'){$niceattributes[]=array('sequence'=>$appattribute['sequence'],'text'=>$appattribute['value'],'cosmetic'=>$appattribute['cosmetic']);}
 }
 $nicefitmentstring=''; $nicefitmentarray=array();
 foreach($niceattributes as $niceattribute)
 {
  // exclude cosmetic elements from the compiled list
  $nicefitmentarray[]=$niceattribute['text'];
 }
 return implode('; ',$nicefitmentarray);
}


$partnumber=strtoupper($_GET['partnumber']); if(strlen($partnumber)>20){$partnumber=substr($partnumber,0,20);}

if(isset($_POST['submit']) && $_POST['submit']=='Save'){$pim->updatePartOID($partnumber);}

$part=$pim->getPart($partnumber);
$apps=$pim->getAppsByPartnumber($partnumber);
$attributes=$pim->getPartAttributes($partnumber);
$assets_linked_to_item=array();
$partcategories=$pim->getPartCategories();

?>
<html>
 <head>
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <?php if($part)
  {;?>
   <div style="padding:10px;">
   <form method="post" action="showPart.php?partnumber=<?php echo $partnumber;?>">
    <table border="1" cellpadding="5">
     <tr><th bgcolor="#c0c0c0" align="left">Partnumber</th><td align="left"><?php echo $part['partnumber'];?></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">Part Type</th><td align="left"><?php echo $pcdb->parttypeName($part['parttypeid']);?></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">Part Category</th><td align="left"><?php echo $pim->partCategoryName($part['partcategory']);?></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">Applications</th><td align="left"><div><?php foreach($apps as $app){echo '<div><a href="showApp.php?appid='.$app['id'].'">'.$vcdb->niceMMYofBasevid($app['basevehicleid']).' '.niceAppAttributes($app['attributes']).'<a></div>';;}?></div></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">Category</th><td align="right"><select name="partcategory"> <?php foreach($partcategories as $partcategory){?> <option value="<?php echo $partcategory['id'];?>"<?php if($partcategory['id']==$part['partcategory']){echo ' selected';}?>><?php echo $partcategory['name'];?></option><?php }?></select></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">Internal<br/>Notes</th><td><textarea name="comments" cols="50"></textarea></td><tr>
     <tr><th bgcolor="#c0c0c0" align="left">Attributes</th><td><table><?php foreach($attributes as $attribute){echo '<tr><td>'.$attribute['name'].'</td><td align="right">'.$attribute['value'].'</td><td>'.$attribute['uom'].'</td></tr>';}?></table></td></tr>
     <tr><th bgcolor="#c0c0c0" align="left">IDs</th><td>SandpiperOID: <?php echo $part['oid'];?></td><tr>
     <tr><th bgcolor="#c0c0c0" align="left">Status</th><td><?php echo $part['lifecyclestatus'];?></td><tr/>
     <tr><th></th><td align="right"><input type="submit" name="submit" value="Save"/></td></tr>
    </table>
   </form>
  </div>
  <?php }
  else
  { // 
   echo 'Part not found';
  }?>
 </div>
 </body>
</html>

