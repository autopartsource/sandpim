<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');

function buildModelYearLink($makeid,$modelid,$yearid,$appcategories,$displaytext)
{
 $catsstring='';
 if(count($appcategories))
 {
  foreach($appcategories as $appcategory){$catsstring.='&appcategory_'.$appcategory.'=on';}
 }
 return '<a href="showAppsByBasevehicle.php?makeid='.$makeid.'&modelid='.$modelid.'&yearid='.$yearid.$catsstring.'">'.$displaytext.'</a>';
}


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

$prevyearexists=$vcdb->getBasevehicleidForMidMidYid($makeid,$modelid,($yearid-1));
$nextyearexists=$vcdb->getBasevehicleidForMidMidYid($makeid,$modelid,($yearid+1));



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
   $nicefitmentarray[]=$niceattribute['text'];
  }

  // build the distinct row keys
  $rowkey=implode('; ',$nicefitmentarray);
  $fitmentrowkeys[$rowkey]=base64_encode(serialize($app['attributes']));

  // build the distinct column keys
  $columnkey=$pcdb->positionName($app['positionid'])."<br/>".$pcdb->parttypeName($app['parttypeid']);
  $fitmentcolumnkeys[$columnkey]=base64_encode(serialize(array('positionid'=>$app['positionid'],'parttypeid'=>$app['parttypeid'])));

  $appmatrix[$rowkey][$columnkey][]=$app;
 }
}

ksort($fitmentrowkeys);
ksort($fitmentcolumnkeys);

?>
<!DOCTYPE HTML>
<html>
<style>
.apppart {
  padding: 1px;
  border: 1px solid #808080;
  margin: 0px;
  background-color:#d0f0c0;
}

.apppart-cosmetic {
  padding: 1px;
  border: 1px solid #aaaaaa;
  margin:0px;
  background-color:#33FFD7;
}
.apppart-hidden {
  padding: 1px;
  border: 1px solid #aaaaaa;
  margin:0px;
  background-color:#FFD433;
}
.apppart-deleted {
  padding: 1px;
  border: 1px solid #aaaaaa;
  margin:0px;
  background-color:#FF5533;
}


/* unvisited link */
a:link {
 color: blue;
 text-decoration: none;
}

/* visited link */
a:visited {
 color: blue;
 text-decoration: none;
}

/* mouse over link */
a:hover {
 color: gray;
 text-decoration: none;
}

/* selected link */
a:active {
 color: blue;
 text-decoration: none;
}


table {
  border-collapse: collapse;
}

table, th, td {
  border: 1px solid black;
}



  </style>
 <head>
  <script>


function showPartEntryBox(ev)
{
 console.log('data-row:'+ev.target.getAttribute('data-row'));

}

function allowDrop(ev) {
  ev.preventDefault();
}

function drag(ev)
{
  ev.dataTransfer.setData("t", ev.target.id);
  ev.dataTransfer.setData("sourceapp", ev.target.getAttribute('data-sourceapp'));
  ev.dataTransfer.setData("sourcerow", ev.target.getAttribute('data-row'));
  ev.dataTransfer.setData("sourcecolumn", ev.target.getAttribute('data-column'));
  ev.dataTransfer.setData("basevehicleid", ev.target.getAttribute('data-basevehicleid'));
  ev.dataTransfer.setData("sourcepartnumber", ev.target.getAttribute('data-partnumber'));
  ev.dataTransfer.setData("sourcecosmetic", ev.target.getAttribute('data-cosmetic'));
  ev.dataTransfer.setData("sourcequantityperapp", ev.target.getAttribute('data-quantityperapp'));
  ev.dataTransfer.setData("sourceappcategory", ev.target.getAttribute('data-appcategory'));

}

function drop(ev)
{
 ev.preventDefault();
 var data = ev.dataTransfer.getData("t");
 var childapp="";
 var sourceapp = ev.dataTransfer.getData("sourceapp");
 var sourcerow = ev.dataTransfer.getData("sourcerow");
 var sourcecolumn = ev.dataTransfer.getData("sourcecolumn");

 var basevehicleid = ev.dataTransfer.getData("basevehicleid");
 var sourcepartnumber = ev.dataTransfer.getData("sourcepartnumber");
 var sourcecosmetic = ev.dataTransfer.getData("sourcecosmetic");
 var sourcequantityperapp = ev.dataTransfer.getData("sourcequantityperapp");
 var sourceappcategory = ev.dataTransfer.getData("sourceappcategory");

/*
 console.log('sourceapp:'+sourceapp);
 console.log('sourcerow:'+sourcerow);
 console.log('sourcecolumn:'+sourcecolumn);

 console.log('data-row:'+ev.target.getAttribute('data-row'));
 console.log('data-column:'+ev.target.getAttribute('data-column'));
 console.log('data-type:'+ev.target.getAttribute('data-type'));
*/

 if(ev.target.getAttribute('data-type')!='dropzone'){return;}
 if(ev.target.getAttribute('data-row')==sourcerow && ev.target.getAttribute('data-column')==sourcecolumn)
 {
  //app was dragged to its own cell - toggle cosmetic and reload the page
  var xhr = new XMLHttpRequest();
  xhr.open('GET', 'ajaxToggleAppCosmetic.php?appid='+sourceapp);
  xhr.send();
//  location.reload(true)

//  document.getElementById(data).setAttribute("class","apppart-cosmetic");
  return;
 }

 var copymove="";
 if(document.querySelector('#copymove').checked)
 {
  var movingapp = document.getElementById(data).cloneNode(true);
  childapp=movingapp;
  copymove="copy";

  console.log('movingapp:'+movingapp);

 }
 else
 {
  childapp=document.getElementById(data);
  copymove="move";
 }

 if(ev.target.getAttribute('id')=='trash' || ev.target.getAttribute('id')=='hide')
 { // app was dragged to trash or hide dropzone - set status accordingly and remove them from the document 
  childapp.remove();
  var xhr = new XMLHttpRequest();
  xhr.open('GET', 'ajaxUpdateAppStatus.php?appid='+sourceapp+'&status='+ev.target.getAttribute('id'));
  xhr.send();
 }
 else
 { // app was dragged to a cell other than its own (handled elsewhere). Move the app object in the document visually  and make ajax call 
    // to apply the attributes of the destination row/column to the dragged app
  ev.target.appendChild(childapp);

  if(copymove=="copy")
  { // new app is created at the row/column intersection using the part number, qty, appcategory

   var xhr = new XMLHttpRequest();
   xhr.open('GET', 'ajaxNewApp.php?basevehicleid='+basevehicleid+'&partnumber='+sourcepartnumber+'&appcategory='+sourceappcategory+'&cosmetic='+sourcecosmetic+'&quantityperapp='+sourcequantityperapp+'&fitment='+ev.target.getAttribute('data-row')+'&positionandparttype='+ev.target.getAttribute('data-column'));
   xhr.send();
  }
  else
  { //app was moved (no new app created by the drag)
   var xhr = new XMLHttpRequest();
   xhr.open('GET', 'ajaxConformApp.php?appid='+sourceapp+'&fitment='+ev.target.getAttribute('data-row')+'&positionandparttype='+ev.target.getAttribute('data-column'));
   xhr.send();
  }
 }





}
  </script>
 </head>
 <body>
<?php include('topnav.inc');?>
 <div>
  <div style="padding:10px;font-size:25px;"><?php echo $vcdb->makeName($makeid); ?>, <?php echo $vcdb->modelName($modelid); ?> <?php echo $yearid;?>
 <?php  if($prevyearexists){echo buildModelYearLink($makeid,$modelid,($yearid-1),$appcategories,'<<');}else{echo '--';} echo ' ';  if($nextyearexists){echo buildModelYearLink($makeid,$modelid,($yearid+1),$appcategories,'>>');}else{echo '--';}?></div>
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

  echo '<label><input type="checkbox" id="copymove" name="copymove"/>Copy mode</label>';

  echo '<table><tr><td></td>'; foreach($fitmentcolumnkeys as $fitmentcolumnkey=>$trash){echo '<td><div style="padding:5px;">'.$fitmentcolumnkey.'</div></td>';} echo '</tr>';

   foreach($fitmentrowkeys as $fitmentrowkey=>$rowfitmentattributes)
   {
    echo '<tr><td><div style="padding:5px;">'.$fitmentrowkey.'</div></td>';
    foreach($fitmentcolumnkeys as $fitmentcolumnkey=>$positionandparttype)
    {
     echo '<td style="vertical-align:top">';
     echo '<div ondrop="drop(event)" ondragover="allowDrop(event)" onclick="showPartEntryBox(event)" data-type="dropzone" data-row="'.$rowfitmentattributes.'" data-column="'.$positionandparttype.'" style="background-color:#c0c0c0;padding-top:2px;padding-bottom:25px;padding-left:2px;padding-right:2px;">';
      if(isset($appmatrix[$fitmentrowkey][$fitmentcolumnkey]))
      {
       foreach($appmatrix[$fitmentrowkey][$fitmentcolumnkey] as $app)
       {
        $appstyle='apppart'; if($app['cosmetic']>0){$appstyle='apppart-cosmetic';} if($app['status']>1){$appstyle='apppart-hidden';} if($app['status']==1){$appstyle='apppart-deleted';}
        echo '<div id="apppart_'.$app['id'].'" class="'.$appstyle.'" draggable="true" ondragstart="drag(event)" data-type="app" data-row="'.$rowfitmentattributes.'" data-column="'.$positionandparttype.'" data-sourceapp="'.$app['id'].'" data-basevehicleid="'.$app['basevehicleid'].'" data-partnumber="'.$app['partnumber'].'" data-quantityperapp="'.$app['quantityperapp'].'" data-cosmetic="'.$app['cosmetic'].'" data-appcategory="'.$app['appcategory'].'" style="padding-left:3px;padding-top:3px;padding-bottom:3px;padding-right:30px;"><a href="showApp.php?appid='.$app['id'].'">'.$app['partnumber'].'</a></div>';
       }
      }
      echo '</div>';
     echo '</td>';
    }
    echo '</tr>';
   }
   echo '</table>';

   echo '<div id="trash" ondrop="drop(event)" ondragover="allowDrop(event)" data-type="dropzone" data-row="trash" data-column="trash" style="float:left;padding:10px;margin:10px;border:2px solid #f5f5f5;background-color:#FF5533;">Drag apps here to delete them</div>';
   echo '<div id="hide" ondrop="drop(event)" ondragover="allowDrop(event)" data-type="dropzone" data-row="hide" data-column="hide" style="float:left;padding:10px;margin:10px;border:2px solid #f5f5f5;background-color:#FFD433;">Drag apps here to de-activate them</div>';
   echo '<div style="clear:both;"></div>';
  }
  else
  { // no apps found
   echo 'No applications found for this make/model/year';
  }?>
   </div>
  </div>
 </body>
</html>

