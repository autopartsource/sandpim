<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/PIES7_1GeneratorClass.php');
$navCategory = 'import';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importAssetTextProcess.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$assetclass=new asset;
$PIESgenerator=new PIESgenerator();

if(isset($_POST['submit']) && $_POST['submit']=='Next') 
{
 $parseerrors=array();
 
 $assetsrecords = explode("\r\n", $_POST['assets']);
 $headerfields=explode("\t",$assetsrecords[0]);
 $items=array();
 
 $PartNumberFieldIndex=-1; $FileNameFieldIndex=-1; $AssetIDFieldIndex=-1; $AssetTypeFieldIndex=-1; $FileTypeFieldIndex=-1; $RepresentationFieldIndex=-1; $FileSizeFieldIndex=-1; $ResolutionFieldIndex=-1; $ColorModeFieldIndex=-1; $BackgroundFieldIndex=-1; $OrientationViewFieldIndex=-1; $AssetHeightFieldIndex=-1; $AssetWidthFieldIndex=-1; $UOMFieldIndex=-1; $FilePathFieldIndex=-1; $URIFieldIndex=-1; $DurationFieldIndex=-1; $DurationUOMFieldIndex=-1; $FrameFieldIndex=-1; $TotalFramesFieldIndex=-1; $PlaneFieldIndex=-1; $HemisphereFieldIndex=-1; $PlungeFieldIndex=-1; $TotalPlanesFieldIndex=-1; $DescriptionFieldIndex=-1; $DescriptionCodeFieldIndex=-1; $DescriptionLanguageCodeFieldIndex=-1; $CreatedDateFieldIndex=-1; $AssetDateTypeFieldIndex=-1; $CountryFieldIndex=-1; $LanguageCodeFieldIndex=-1; $PublicFieldIndex=-1; $AssetLabelFieldIndex=-1; $AssetTagsFieldIndex=-1;
 
 for($i=0; $i<=count($headerfields)-1; $i++)
 { // identify the named columns' IDs
  if($headerfields[$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
  if($headerfields[$i]=='FileName'){$FileNameFieldIndex=$i;}
  if($headerfields[$i]=='AssetID'){$AssetIDFieldIndex=$i;}
  if($headerfields[$i]=='AssetType'){$AssetTypeFieldIndex=$i;}
  if($headerfields[$i]=='FileType'){$FileTypeFieldIndex=$i;}
  if($headerfields[$i]=='Representation'){$RepresentationFieldIndex=$i;}
  if($headerfields[$i]=='FileSize'){$FileSizeFieldIndex=$i;}
  if($headerfields[$i]=='Resolution'){$ResolutionFieldIndex=$i;}
  if($headerfields[$i]=='ColorMode'){$ColorModeFieldIndex=$i;}
  if($headerfields[$i]=='Background'){$BackgroundFieldIndex=$i;}
  if($headerfields[$i]=='OrientationView'){$OrientationViewFieldIndex=$i;}
  if($headerfields[$i]=='AssetHeight'){$AssetHeightFieldIndex=$i;}
  if($headerfields[$i]=='AssetWidth'){$AssetWidthFieldIndex=$i;}
  if($headerfields[$i]=='UOM'){$UOMFieldIndex=$i;}
  if($headerfields[$i]=='FilePath'){$FilePathFieldIndex=$i;}
  if($headerfields[$i]=='URI'){$URIFieldIndex=$i;}
  if($headerfields[$i]=='Duration'){$DurationFieldIndex=$i;}
  if($headerfields[$i]=='DurationUOM'){$DurationUOMFieldIndex=$i;}
  if($headerfields[$i]=='Frame'){$FrameFieldIndex=$i;}
  if($headerfields[$i]=='TotalFrames'){$TotalFramesFieldIndex=$i;}
  if($headerfields[$i]=='Plane'){$PlaneFieldIndex=$i;}
  if($headerfields[$i]=='Hemisphere'){$HemisphereFieldIndex=$i;}
  if($headerfields[$i]=='Plunge'){$PlungeFieldIndex=$i;}
  if($headerfields[$i]=='TotalPlanes'){$TotalPlanesFieldIndex=$i;}
  if($headerfields[$i]=='Description'){$DescriptionFieldIndex=$i;}
  if($headerfields[$i]=='DescriptionCode'){$DescriptionCodeFieldIndex=$i;}
  if($headerfields[$i]=='DescriptionLanguageCode'){$DescriptionLanguageCodeFieldIndex=$i;}
  if($headerfields[$i]=='CreatedDate'){$CreatedDateFieldIndex=$i;}
  if($headerfields[$i]=='AssetDateType'){$AssetDateTypeFieldIndex=$i;}
  if($headerfields[$i]=='Country'){$CountryFieldIndex=$i;}
  if($headerfields[$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
  if($headerfields[$i]=='Public'){$PublicFieldIndex=$i;}
  if($headerfields[$i]=='AssetLabel'){$AssetLabelFieldIndex=$i;}
  if($headerfields[$i]=='AssetTags'){$AssetTagsFieldIndex=$i;}
 } 
  
 $recordnumber=0;
 if($PartNumberFieldIndex>=0)
 {
  foreach($assetsrecords as $record)
  {
   $fields = explode("\t",$record);
   if(count($fields)==1){continue;} // empty row
   if($recordnumber==0){$recordnumber++;continue;}
   $asset=array();
   $PartNumber=trim($fields[$PartNumberFieldIndex]);

   if($FileNameFieldIndex>=0 && trim($fields[$FileNameFieldIndex])!=''){$asset['FileName']=trim($fields[$FileNameFieldIndex]);}
   if($AssetIDFieldIndex>=0 && trim($fields[$AssetIDFieldIndex])!=''){$asset['AssetID']=trim($fields[$AssetIDFieldIndex]);}
   $asset['FileType']='P04'; if($AssetTypeFieldIndex>=0 && trim($fields[$AssetTypeFieldIndex])!=''){$asset['AssetType']=trim($fields[$AssetTypeFieldIndex]);}
   $asset['FileType']='JPG'; if($FileTypeFieldIndex>=0 && trim($fields[$FileTypeFieldIndex])!=''){$asset['FileType']=trim($fields[$FileTypeFieldIndex]);}
   $asset['Representation']='A'; if($RepresentationFieldIndex>=0 && trim($fields[$RepresentationFieldIndex])!=''){$asset['Representation']=trim($fields[$RepresentationFieldIndex]);}
   $asset['FileSize']='1000'; if($FileSizeFieldIndex>=0 && trim($fields[$FileSizeFieldIndex])!=''){$asset['FileSize']=trim($fields[$FileSizeFieldIndex]);}
   $asset['Resolution']=300; if($ResolutionFieldIndex>=0 && trim($fields[$ResolutionFieldIndex])!=''){$asset['Resolution']=trim($fields[$ResolutionFieldIndex]);}
   $asset['ColorMode']='RGB'; if($ColorModeFieldIndex>=0 && trim($fields[$ColorModeFieldIndex])!=''){$asset['ColorMode']=trim($fields[$ColorModeFieldIndex]);}
   $asset['Background']='WHI'; if($BackgroundFieldIndex>=0 && trim($fields[$BackgroundFieldIndex])!=''){$asset['Background']=trim($fields[$BackgroundFieldIndex]);}
   $asset['OrientationView']=''; if($OrientationViewFieldIndex>=0 && trim($fields[$OrientationViewFieldIndex])!=''){$asset['OrientationView']=trim($fields[$OrientationViewFieldIndex]);}
   $asset['AssetHeight']=100; if($AssetHeightFieldIndex>=0 && trim($fields[$AssetHeightFieldIndex])!=''){$asset['AssetHeight']=trim($fields[$AssetHeightFieldIndex]);}
   $asset['AssetWidth']=100; if($AssetWidthFieldIndex>=0 && trim($fields[$AssetWidthFieldIndex])!=''){$asset['AssetWidth']=trim($fields[$AssetWidthFieldIndex]);}
   $asset['UOM']=''; if($UOMFieldIndex>=0 && trim($fields[$UOMFieldIndex])!=''){$asset['UOM']=trim($fields[$UOMFieldIndex]);}
   if($FilePathFieldIndex>=0 && trim($fields[$FilePathFieldIndex])!=''){$asset['FilePath']=trim($fields[$FilePathFieldIndex]);}
   if($URIFieldIndex>=0 && trim($fields[$URIFieldIndex])!=''){$asset['URI']=trim($fields[$URIFieldIndex]);}
   if($DurationFieldIndex>=0 && trim($fields[$DurationFieldIndex])!=''){$asset['Duration']=trim($fields[$DurationFieldIndex]);}
   if($DurationUOMFieldIndex>=0 && trim($fields[$DurationUOMFieldIndex])!=''){$asset['DurationUOM']=trim($fields[$DurationUOMFieldIndex]);}
   $asset['Frame']=0; if($FrameFieldIndex>=0 && trim($fields[$FrameFieldIndex])!=''){$asset['Frame']=trim($fields[$FrameFieldIndex]);}
   $asset['TotalFrames']=0; if($TotalFramesFieldIndex>=0 && trim($fields[$TotalFramesFieldIndex])!=''){$asset['TotalFrames']=trim($fields[$TotalFramesFieldIndex]);}
   $asset['Plane']=0; if($PlaneFieldIndex>=0 && trim($fields[$PlaneFieldIndex])!=''){$asset['Plane']=trim($fields[$PlaneFieldIndex]);}
   $asset['Hemisphere']='N'; if($HemisphereFieldIndex>=0 && trim($fields[$HemisphereFieldIndex])!=''){$asset['Hemisphere']=trim($fields[$HemisphereFieldIndex]);}
   $asset['Plunge']=0; if($PlungeFieldIndex>=0 && trim($fields[$PlungeFieldIndex])!=''){$asset['Plunge']=trim($fields[$PlungeFieldIndex]);}
   $asset['TotalPlanes']=0; if($TotalPlanesFieldIndex>=0 && trim($fields[$TotalPlanesFieldIndex])!=''){$asset['TotalPlanes']=trim($fields[$TotalPlanesFieldIndex]);}
   if($DescriptionFieldIndex>=0 && trim($fields[$DescriptionFieldIndex])!=''){$asset['Description']=trim($fields[$DescriptionFieldIndex]);}
   if($DescriptionCodeFieldIndex>=0 && trim($fields[$DescriptionCodeFieldIndex])!=''){$asset['DescriptionCode']=trim($fields[$DescriptionCodeFieldIndex]);}
   $asset['DescriptionLanguageCode']='EN'; if($DescriptionLanguageCodeFieldIndex>=0 && trim($fields[$DescriptionLanguageCodeFieldIndex])!=''){$asset['DescriptionLanguageCode']=trim($fields[$DescriptionLanguageCodeFieldIndex]); $asset['LanguageCode']=trim($fields[$DescriptionLanguageCodeFieldIndex]);}
   $asset['LanguageCode']='EN'; if($LanguageCodeFieldIndex>=0 && trim($fields[$LanguageCodeFieldIndex])!=''){$asset['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);}
   $asset['CreatedDate']='2000-01-01'; if($CreatedDateFieldIndex>=0 && trim($fields[$CreatedDateFieldIndex])!=''){$asset['CreatedDate']=trim($fields[$CreatedDateFieldIndex]);}
   if($AssetDateTypeFieldIndex>=0 && trim($fields[$AssetDateTypeFieldIndex])!=''){$asset['AssetDateType']=trim($fields[$AssetDateTypeFieldIndex]);}
   if($CountryFieldIndex>=0 && trim($fields[$CountryFieldIndex])!=''){$asset['Country']=trim($fields[$CountryFieldIndex]);}
   if($PublicFieldIndex>=0 && trim($fields[$PublicFieldIndex])!=''){$asset['Public']=trim($fields[$PublicFieldIndex]);}
   if($AssetLabelFieldIndex>=0 && trim($fields[$AssetLabelFieldIndex])!=''){$asset['AssetLabel']=trim($fields[$AssetLabelFieldIndex]);}
   if($AssetTagsFieldIndex>=0 && trim($fields[$AssetTagsFieldIndex])!=''){$asset['AssetTags']=trim($fields[$AssetTagsFieldIndex]);}
   
   if(!array_key_exists('AssetID',$asset) || $asset['AssetID']=='')
   {
    continue;
   }
   
   if($pim->validPart($PartNumber))
   {// valid item
    $existingassets=$assetclass->getAssetRecordsByAssetid($asset['AssetID']);
     $items[$PartNumber]['assets'][]=$asset;
    
    /*
    if(count($existingassets)==0)
    {// this assetID is not already existing
        
     $items[$PartNumber]['assets'][]=$asset;
    }
    else
    {// assetID is already in use
        
     $parseerrors[]='AssetID ['.$asset['AssetID'].'] already exists for partnumber:'.$PartNumber;       
    }
     * 
     * 
     */       
   }
   else
   {// invalid item
    $parseerrors[]='invalid partnumber: '.$PartNumber;
   }
   
  }
 }
 
 $doimport=false; if(isset($_POST['doimport'])){$doimport=true;}
 $importoptions=array();
 if(isset($_POST['removeexisting'])){$importoptions['clearExistingAssetsByPart']=1;}
 
 
 $partcategory=0; $createparts=false;
// print_r($items);
 $importresults=$PIESgenerator->importPIESdata($_SESSION['userid'],$items,$createparts,$partcategory,$doimport,$importoptions);
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
                        <h3 class="card-header text-start">Digital Assets (metadata) import from structured text</h3>

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