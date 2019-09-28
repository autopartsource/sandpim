<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');

$vcdb=new vcdb;
$pcdb=new pcdb;
$pim=new pim;

$makeid=intval($_GET['makeid']);
if(isset($_GET['modelid'])){$modelid=intval($_GET['modelid']);}
if(isset($_GET['yearid'])){$yearid=intval($_GET['yearid']);}
if(isset($_GET['equipmentid'])){$equipmentid=intval($_GET['equipmentid']);}


$appcategories=array(); foreach($_GET as $getname=>$getval){if(strpos($getname,'appcategory_')===0){$bits=explode('_',$getname); $appcategories[]=$bits[1];}}

$basevehicleid=$vcdb->getBasevehicleidForMidMidYid($makeid,$modelid,$yearid);
$apps=$pim->getAppsByBasevehicleid($basevehicleid,$appcategories);
$fitmentrowkeys=array();
$fitmentcolumnkeys=array();
$appmatrix=array();


if(count($apps))
{
 foreach($apps as $app)
 {
  $niceattributes=array();
  foreach($app['attributes'] as $appattribute)
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
  // sort the array by it's sequence member

  // build the distinct row keys
  $rowkey=implode('; ',$nicefitmentarray);
  $fitmentrowkeys[$rowkey]='';

  // build the distinct column keys
  $columnkey=$pcdb->positionName($app['positionid'])."<br/>".$pcdb->parttypeName($app['parttypeid']);
  $fitmentcolumnkeys[$columnkey]='';

  $appmatrix[$rowkey][$columnkey][]=$app;
 }
}

ksort($fitmentrowkeys);
ksort($fitmentcolumnkeys);

?>
<html>
 <head>
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <div style="padding:10px;font-size:25px;">Apps by Make/Model/Year - <?php echo $vcdb->makeName($makeid); ?>, <?php echo $vcdb->modelName($modelid); ?> <?php echo $yearid.' ('.$basevehicleid.')';?></div>
  <div style="padding:10px;">
  <?php if(count($apps))
  {
   foreach($apps as $app)
   {
    $niceattributes=array();
    foreach($app['attributes'] as $appattribute)
    {
     if($appattribute['type']=='vcdb'){$niceattributes[]=array('sequence'=>$appattribute['sequence'],'text'=>$vcdb->niceVCdbAttributePair($appattribute),'cosmetic'=>$appattribute['cosmetic']);}
     if($appattribute['type']=='note'){$niceattributes[]=array('sequence'=>$appattribute['sequence'],'text'=>$appattribute['value'],'cosmetic'=>$appattribute['cosmetic']);}
    }
    $nicefitmentstring=''; $nicefitmentarray=array();
    foreach($niceattributes as $niceattribute)
    {
     $nicefitmentarray[]=$niceattribute['text'];
    }
   }

   echo '<table border="1"><tr><th></th>'; foreach($fitmentcolumnkeys as $fitmentcolumnkey=>$trash){echo '<th><div style="padding:5px;">'.$fitmentcolumnkey.'</div></th>';} echo '</tr>';

   foreach($fitmentrowkeys as $fitmentrowkey=>$trash)
   {
    echo '<tr><td><div style="padding:5px;">'.$fitmentrowkey.'</div></td>';
    foreach($fitmentcolumnkeys as $fitmentcolumnkey=>$trash)
    {
     echo '<td>';
     if(isset($appmatrix[$fitmentrowkey][$fitmentcolumnkey]))
     {
      foreach($appmatrix[$fitmentrowkey][$fitmentcolumnkey] as $app)
      {
       echo '<div style="padding:2px 5px 2px 5px;"><a href="showApp.php?appid='.$app['id'].'">'.$app['partnumber'].'</a></div>';
      }
     }
     echo '</td>';
    }
    echo '</tr>';
   }
   echo '</table>';

  }
  else
  { // no apps found
   echo 'No applications found for this make/model/year';
  }?>
   </div>
  </div>
 </body>
</html>

