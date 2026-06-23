<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/assetClass.php');
include_once('./class/configGetClass.php');

$navCategory = 'search';
session_start();

$pim=new pim();
$vcdb=new vcdb();
$pcdb=new pcdb();
$qdb=new qdb();
$asset=new asset();
$configGet = new configGet();

$partcategories= array();
$categoriesstrings=explode(',',$configGet->getConfigValue('publicCatalogCategories'));
foreach($categoriesstrings as $categoriesstring){$partcategories[]=intval($categoriesstring);}


$parttypelist=array(1684);
$lifecyclestatuses=array('2','3','4','7','8');


if(isset($_GET['makeid']) && isset($_GET['modelid']) && isset($_GET['yearid']))
{
 $makeid=intval($_GET['makeid']);
 $makename=$vcdb->makeName($makeid);
 $modelid=intval($_GET['modelid']);
 $modelname=$vcdb->modelName($modelid);
 $yearid = intval($_GET['yearid']);
 $basevehicleid=$vcdb->getBasevehicleidForMidMidYid($makeid, $modelid, $yearid);
 $apps=$pim->getAppsByBasevehicleid($basevehicleid, $partcategories);
 
 
 $fitmentrowkeys = array();
 $fitmentcolumnkeys = array();
 $appmatrix = array();
 $applist = array();
 
 $prevyearexists = $vcdb->getBasevehicleidForMidMidYid($makeid, $modelid, ($yearid - 1));
 $nextyearexists = $vcdb->getBasevehicleidForMidMidYid($makeid, $modelid, ($yearid + 1));

 if (count($apps)) 
 {
  foreach ($apps as $app)
  {
   $niceattributes = array();
   foreach ($app['attributes'] as $appattribute)
   {
    if ($appattribute['type'] == 'vcdb')
    {
     $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']);
    }

    if ($appattribute['type'] == 'qdb')
    {
     $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $qdb->qualifierText($appattribute['name'], explode('~', str_replace('|','',$appattribute['value']))), 'cosmetic' => $appattribute['cosmetic']);
    }

    if ($appattribute['type'] == 'note')
    {
     $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']);
    }
   }
   
   $nicefitmentarray = array();
   foreach ($niceattributes as $niceattribute)
   {
    $nicefitmentarray[] = $niceattribute['text'];
   }
   
   $noncosmeticfitmentarray = array();
   foreach ($niceattributes as $niceattribute)
   {
    if($niceattribute['cosmetic']==1){continue;}
    $noncosmeticfitmentarray[] = $niceattribute['text'];
   }
   $noncosmeticfitment = implode('; ', $noncosmeticfitmentarray);

   
   // build the distinct row keys for the grid presentation (desktop mode UI)
   $rowkey = implode('; ', $nicefitmentarray);
   $fitmentrowkeys[$rowkey] = urlencode(base64_encode(serialize($app['attributes'])));

   // build the distinct column keys for the grid presentation (desktop mode UI)
   $positionname=$pcdb->positionName($app['positionid']);
   $parttypename=$pcdb->parttypeName($app['parttypeid']);
   $columnkey = $positionname . '<br/>' . $parttypename;
   $fitmentcolumnkeys[$columnkey] = urlencode(base64_encode(serialize(array('positionid' => $app['positionid'], 'parttypeid' => $app['parttypeid']))));

   $appmatrix[$rowkey][$columnkey][] = $app;
   
   // applist is for mobile UI presentation
   $part=$pim->getPart($app['partnumber']);
   
   $applist[]=array('partnumber'=>$app['partnumber'],'nicefitment'=>$noncosmeticfitment,'positionname'=>$positionname,'parttypename'=>$parttypename,'quantityperapp'=>$app['quantityperapp'],'cosmetic'=>$app['cosmetic'],'partcategoryname'=>$part['partcategoryname']);
   // build index for sorting applist
   $positionsortkey=[];$fitmentsortkey=[];
   foreach($applist as $i=>$a)
   {
    $positionsortkey[$i]=$a['positionname'];
    $fitmentsortkey[$i]=$a['nicefitment'];       
   } 
   
   array_multisort($positionsortkey,SORT_ASC,SORT_STRING,$fitmentsortkey,SORT_ASC,SORT_STRING,$applist);
       
  }
 }

 ksort($fitmentrowkeys);
 ksort($fitmentcolumnkeys);
 
 
}

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <div class="row">
            
            
            <!-- mobile content (display in sm) -->
            <div class="col-12 d-block d-md-none">
                <h2 class="card-header text-start"><a href="./publicCatalog.php">Home</a> >> <a href="./publicCatalogMakes.php"><?php echo $makename;?></a> >> <a href="./publicCatalogModels.php?makeid=<?php echo $makeid;?>"><?php echo $modelname;?></a> >> <a href="./publicCatalogYears.php?makeid=<?php echo $makeid;?>&modelid=<?php echo $modelid;?>"><?php echo $yearid;?></a></h2>
                <?php foreach($applist as $app){
                    if($app['cosmetic']==1){continue;}
                    $primaryphotouri=$asset->primaryPhotoURIofPart($app['partnumber']);
                    ?>
                <div style="padding:5px;">
                
                    <div style="float:left;width:45%;padding:5px;">
                        <a href="./publicCatalogPart.php?basevid=<?php echo $basevehicleid;?>&partnumber=<?php echo $app['partnumber'];?>"><img class="img-thumbnail" src="<?php echo $primaryphotouri;?>" /></a>
                    </div>

                    <div style="float:left;text-align: left;width:50%;padding:5px;">
                        <div style="padding:2px;"><strong><?php echo $app['partnumber'];?></strong></div>
                        <div style="padding-left:4px;"><?php echo $app['partcategoryname'];?></div>
                        <div style="padding-left:4px;"><?php echo $app['positionname'].' '.$app['parttypename'];?></div>
                        <div style="padding-left:4px;"><?php echo $app['nicefitment'];?></div>
                    </div>
                    

                    <div style="clear:both;"></div>
                </div>
                <hr/>
                <?php }?>
            
            </div>


            <!-- desktop content (display in md and lg ) -->
            <div class="d-none d-md-block col-1 col-lg-2"></div>
            <div class="d-none d-md-block col-10 col-lg-8">
                <div class="card shadow-sm">
                    <h3 class="card-header text-start"><a href="./publicCatalog.php">Home</a> > <a href="./publicCatalogMakes.php"><?php echo $makename;?></a> > <a href="./publicCatalogModels.php?makeid=<?php echo $makeid;?>"><?php echo $modelname;?></a> > <a href="./publicCatalogYears.php?makeid=<?php echo $makeid;?>&modelid=<?php echo $modelid;?>"><?php echo $yearid;?></a></h3>
                    <div class="card-body">
                        <table class="table table-bordered"><tr><td></td>
                        <?php

                        foreach ($fitmentcolumnkeys as $fitmentcolumnkey => $trash)
                        {
                         echo '<td>'.$fitmentcolumnkey.'</td>';
                        }
                        echo '</tr>';

                        foreach ($fitmentrowkeys as $fitmentrowkey => $rowfitmentattributes)
                        {
                         echo '<tr><td><div style="padding:5px;">' . $fitmentrowkey . '</div></td>';
                         foreach ($fitmentcolumnkeys as $fitmentcolumnkey => $positionandparttype)
                         {
                          echo '<td>';


                          if(isset($appmatrix[$fitmentrowkey][$fitmentcolumnkey]))
                          {
                           foreach ($appmatrix[$fitmentrowkey][$fitmentcolumnkey] as $app)
                           {
                            echo '<div style="padding:5px;"><a href="publicCatalogPart.php?basevid='.$basevehicleid.'&partnumber='.$app['partnumber'].'" class="btn btn-secondary">'.$app['partnumber'].'</a></div>';
                           }
                          }

                          echo '</td>';                             
                         }
                         echo '</tr>';
                        }


                        ?>
                        </table>
                    </div>
                </div>
            </div>
            <div class="d-none d-md-block col-1 col-lg-2"></div>
            <!-- end of desktop -->

            
            
            
            
        </div>
    </body>
</html>