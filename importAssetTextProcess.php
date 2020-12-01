<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/PIES7_1GeneratorClass.php');
$navCategory = 'import/export';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$assetclass=new asset;
$PIESgenerator=new PIESgenerator();

if(isset($_POST['submit']) && $_POST['submit']=='Next') 
{
 $parseerrors=array();
 
 $assetsrecords = explode("\r\n", $_POST['assets']);
 $headerfields=explode("\t",$assetsrecords[0]);
 $items=array();
 
 $PartNumberFieldIndex=-1; $FileNameFieldIndex=-1; $AssetIDFieldIndex=-1; $AssetTypeFieldIndex=-1; $FileTypeFieldIndex=-1; $RepresentationFieldIndex=-1; $FileSizeFieldIndex=-1; $ResolutionFieldIndex=-1; $ColorModeFieldIndex=-1; $BackgroundFieldIndex=-1; $OrientationViewFieldIndex=-1; $AssetHeightFieldIndex=-1; $AssetWidthFieldIndex=-1; $UOMFieldIndex=-1; $FilePathFieldIndex=-1; $URIFieldIndex=-1; $DurationFieldIndex=-1; $DurationUOMFieldIndex=-1; $FrameFieldIndex=-1; $TotalFramesFieldIndex=-1; $PlaneFieldIndex=-1; $HemisphereFieldIndex=-1; $PlungeFieldIndex=-1; $TotalPlanesFieldIndex=-1; $DescriptionFieldIndex=-1; $DescriptionCodeFieldIndex=-1; $DescriptionLanguageCodeFieldIndex=-1; $AssetDateFieldIndex=-1; $AssetDateTypeFieldIndex=-1; $CountryFieldIndex=-1; $LanguageCodeFieldIndex=-1;

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
  if($headerfields[$i]=='AssetDate'){$AssetDateFieldIndex=$i;}
  if($headerfields[$i]=='AssetDateType'){$AssetDateTypeFieldIndex=$i;}
  if($headerfields[$i]=='Country'){$CountryFieldIndex=$i;}
  if($headerfields[$i]=='LanguageCode'){$LanguageCodeFieldIndex=$i;}
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
   $PartNumber=trim($fields[0]);

   if($FileNameFieldIndex>=0 && trim($fields[$FileNameFieldIndex])!=''){$asset['FileName']=trim($fields[$FileNameFieldIndex]);}
   if($AssetIDFieldIndex>=0 && trim($fields[$AssetIDFieldIndex])!=''){$asset['AssetID']=trim($fields[$AssetIDFieldIndex]);}
   if($AssetTypeFieldIndex>=0 && trim($fields[$AssetTypeFieldIndex])!=''){$asset['AssetType']=trim($fields[$AssetTypeFieldIndex]);}
   if($FileTypeFieldIndex>=0 && trim($fields[$FileTypeFieldIndex])!=''){$asset['FileType']=trim($fields[$FileTypeFieldIndex]);}
   if($RepresentationFieldIndex>=0 && trim($fields[$RepresentationFieldIndex])!=''){$asset['Representation']=trim($fields[$RepresentationFieldIndex]);}
   if($FileSizeFieldIndex>=0 && trim($fields[$FileSizeFieldIndex])!=''){$asset['FileSize']=trim($fields[$FileSizeFieldIndex]);}
   if($ResolutionFieldIndex>=0 && trim($fields[$ResolutionFieldIndex])!=''){$asset['Resolution']=trim($fields[$ResolutionFieldIndex]);}
   if($ColorModeFieldIndex>=0 && trim($fields[$ColorModeFieldIndex])!=''){$asset['ColorMode']=trim($fields[$ColorModeFieldIndex]);}
   if($BackgroundFieldIndex>=0 && trim($fields[$BackgroundFieldIndex])!=''){$asset['Background']=trim($fields[$BackgroundFieldIndex]);}
   if($OrientationViewFieldIndex>=0 && trim($fields[$OrientationViewFieldIndex])!=''){$asset['OrientationView']=trim($fields[$OrientationViewFieldIndex]);}
   if($AssetHeightFieldIndex>=0 && trim($fields[$AssetHeightFieldIndex])!=''){$asset['AssetHeight']=trim($fields[$AssetHeightFieldIndex]);}
   if($AssetWidthFieldIndex>=0 && trim($fields[$AssetWidthFieldIndex])!=''){$asset['AssetWidth']=trim($fields[$AssetWidthFieldIndex]);}
   if($UOMFieldIndex>=0 && trim($fields[$UOMFieldIndex])!=''){$asset['UOM']=trim($fields[$UOMFieldIndex]);}
   if($FilePathFieldIndex>=0 && trim($fields[$FilePathFieldIndex])!=''){$asset['FilePath']=trim($fields[$FilePathFieldIndex]);}
   if($URIFieldIndex>=0 && trim($fields[$URIFieldIndex])!=''){$asset['URI']=trim($fields[$URIFieldIndex]);}
   if($DurationFieldIndex>=0 && trim($fields[$DurationFieldIndex])!=''){$asset['Duration']=trim($fields[$DurationFieldIndex]);}
   if($DurationUOMFieldIndex>=0 && trim($fields[$DurationUOMFieldIndex])!=''){$asset['DurationUOM']=trim($fields[$DurationUOMFieldIndex]);}
   if($FrameFieldIndex>=0 && trim($fields[$FrameFieldIndex])!=''){$asset['Frame']=trim($fields[$FrameFieldIndex]);}
   if($TotalFramesFieldIndex>=0 && trim($fields[$TotalFramesFieldIndex])!=''){$asset['TotalFrames']=trim($fields[$TotalFramesFieldIndex]);}
   if($PlaneFieldIndex>=0 && trim($fields[$PlaneFieldIndex])!=''){$asset['Plane']=trim($fields[$PlaneFieldIndex]);}
   if($HemisphereFieldIndex>=0 && trim($fields[$HemisphereFieldIndex])!=''){$asset['Hemisphere']=trim($fields[$HemisphereFieldIndex]);}
   if($PlungeFieldIndex>=0 && trim($fields[$PlungeFieldIndex])!=''){$asset['Plunge']=trim($fields[$PlungeFieldIndex]);}
   if($TotalPlanesFieldIndex>=0 && trim($fields[$TotalPlanesFieldIndex])!=''){$asset['TotalPlanes']=trim($fields[$TotalPlanesFieldIndex]);}
   if($DescriptionFieldIndex>=0 && trim($fields[$DescriptionFieldIndex])!=''){$asset['Description']=trim($fields[$DescriptionFieldIndex]);}
   if($DescriptionCodeFieldIndex>=0 && trim($fields[$DescriptionCodeFieldIndex])!=''){$asset['DescriptionCode']=trim($fields[$DescriptionCodeFieldIndex]);}
   if($DescriptionLanguageCodeFieldIndex>=0 && trim($fields[$DescriptionLanguageCodeFieldIndex])!=''){$asset['DescriptionLanguageCode']=trim($fields[$DescriptionLanguageCodeFieldIndex]);}
   if($AssetDateFieldIndex>=0 && trim($fields[$AssetDateFieldIndex])!=''){$asset['AssetDate']=trim($fields[$AssetDateFieldIndex]);}
   if($AssetDateTypeFieldIndex>=0 && trim($fields[$AssetDateTypeFieldIndex])!=''){$asset['AssetDateType']=trim($fields[$AssetDateTypeFieldIndex]);}
   if($CountryFieldIndex>=0 && trim($fields[$CountryFieldIndex])!=''){$asset['Country']=trim($fields[$CountryFieldIndex]);}
   if($LanguageCodeFieldIndex>=0 && trim($fields[$LanguageCodeFieldIndex])!=''){$asset['LanguageCode']=trim($fields[$LanguageCodeFieldIndex]);}
   
   
   if(!array_key_exists('AssetID',$asset) || $asset['AssetID']=='')
   {
    continue;
   }
   
   if($pim->validPart($PartNumber))
   {// valid item
    $existingassets=$assetclass->getAssetRecordsByAssetid($asset['AssetID']);
    if(count($existingassets)==0)
    {// this assetID is not already existing
        
     $items[$PartNumber]['assets'][]=$asset;
    }
    else
    {// assetID is already in use
        
     $parseerrors[]='AssetID ['.$asset['AssetID'].'] already exists for partnumber:'.$PartNumber;       
    }       
   }
   else
   {// invalid item
    $parseerrors[]='invalid partnumber: '.$PartNumber;
   }
   
  }
 }
 
 $doimport=false; if(isset($_POST['doimport'])){$doimport=true;}
 $partcategory=0; $createparts=false;
// print_r($items);
 $importresults=$PIESgenerator->importPIESdata($items,$createparts,$partcategory,$doimport);
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

        <!-- Header -->
        <h1>Import part data from spreadsheet template</h1>
        <h2>Step 2: Results</h2>

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                
                    <?php if(count($parseerrors)>0){?>
                    <div style="padding:10px;">Logic Problems</div>
                    <table><?php
                    foreach($parseerrors as $error)
                    {
                        echo '<tr><td style="text-align:left;">'.$error.'</td></tr>';
                    }
                    ?>
                    </table>
                    <?php }?>



                    <?php if(count($importresults)>0){?>
                    <div style="padding:10px;">Actions</div>
                    <table><?php
                    foreach($importresults as $importresult)
                    {
                        echo '<tr><td style="text-align:left;">'.$importresult.'</td></tr>';
                    }
                    ?>
                    </table>
                    <?php }?>

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