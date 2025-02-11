<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
$navCategory = 'import';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importAssetTagsProcess.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$assetclass=new asset;

if(isset($_POST['submit']) && $_POST['submit']=='Next') 
{
 $parseerrors=array();
 
 $records = explode("\r\n", $_POST['assets']);
 $doimport=false; if(isset($_POST['doimport'])){$doimport=true;}
 $removeexisting=false; if(isset($_POST['removeexisting'])){$removeexisting=true;}
 
 foreach($records as $record)
 {
  $fields = explode("\t", $record);
  if(count($fields) == 2)
  {
   $assetid = trim(strtoupper($fields[0]));   
   $existingassetrecords=$assetclass->getAssetRecordsByAssetid($assetid);   //$records[]=array('id'=>$row['id'],'assetid'=>$row['assetid'],'filename'=>$row['filename'],'localpath'=>$row['localpath'],'uri'=>$row['uri'],'orientationViewCode'=>$row['orientationViewCode'],'colorModeCode'=>$row['colorModeCode'],'assetHeight'=>$row['assetHeight'],'assetWidth'=>$row['assetWidth'],'dimensionUOM'=>$row['dimensionUOM'],'background'=>$row['background'],'fileType'=>$row['fileType'],'createdDate'=>$row['createdDate'],'public'=>$row['public'],'approved'=>$row['approved'],'description'=>$row['description'],'oid'=>$row['oid'],'fileHashMD5'=>$row['fileHashMD5'],'filesize'=>$row['filesize'],'resolution'=>$row['resolution'],'languagecode'=>$row['languagecode'],'assetlabel'=>$row['assetlabel'],'changedDate'=>$row['changedDate'],'frame'=>$row['frame'],'totalFrames'=>$row['totalFrames'],'plane'=>$row['plane'],'totalPlanes'=>$row['totalPlanes']);          
   if(count($existingassetrecords))
   { // assetid referes to an existing asset(s)
       
    if($removeexisting)
    {// delete all tags for given asset befor adding any new ones
        $assetclass->removeTagsFromAsset($assetid, false);        
    }
       
    $tagsstring = trim(strtoupper($fields[1]));
    $tagstemp = explode(',', $tagsstring);
    $tags=array();
    foreach($tagstemp as $tagtemp)
    {
     if($tagid=$assetclass->assetTagid(trim($tagtemp)))
     {// valid tag
      $tags[]=trim($tagtemp);
      $importresults[]='Asset ['.$assetid.'] valid tag ['.$tagtemp.']';
      $assetclass->addAssetTagidToAsset($assetid, $tagid);
     }
     else
     {// invalid tag
      $importresults[]='Asset ['.$assetid.'] invalid tag ['.$tagtemp.']';
     }
       
    } 
   }
   else
   {//no assets found 
    $importresults[]='Asset ['.$assetid.'] not found';
   }
  }   
 }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
<?php include('topnav.php'); ?>

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Import digital asset tags from text</h3>

                        <div class="card-body">
                            <div class="alert alert-secondary">Results</div>
                            <?php if(count($parseerrors)>0){?>
                            <div class="alert alert-danger">Logic Problems</div>
                            <table class="table"><?php
                            foreach($parseerrors as $error)
                            {
                                echo '<tr><td style="text-align:left;">'.$error.'</td></tr>';
                            }
                            ?>
                            </table>
                            <?php }?>

                            <?php if(count($importresults)>0){?>
                            <div class="alert alert-success">Actions</div>
                            <table class="table"><?php
                            foreach($importresults as $importresult)
                            {
                                echo '<tr><td style="text-align:left;">'.$importresult.'</td></tr>';
                            }
                            ?>
                            </table>
                            <?php }?>
                        </div>
                    </div>

                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->

        <!-- Footer -->
<?php include('./includes/footer.php'); ?>
    </body>
</html>