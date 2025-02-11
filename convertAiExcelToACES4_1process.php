<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/ACES4_1GeneratorClass.php');
include_once('./class/XLSXReaderClass.php');

$navCategory = 'utilities';

session_start();

$pim = new pim;
$ACESgenerator=new ACESgenerator();
$logs=new logs();

// ACA database vserion of null implies "dont validate"
// a valid date is required for XSD pass on the output XML, so 1900-01-01 
// is what we actually put in the xml if "dont validate" was the user's 
// intention
$pcdbVersion='1900-01-01';
$vcdbVersion='1900-01-01';
$qdbVersion='1900-01-01';
$basevehicles=array();
$qdbs=array();
$positions=array();
$parttypeids=array();

if($pim->validAutoCareLocalDatabaseName($_POST['vcdbname']))
{
 $vcdb=new vcdb($_POST['vcdbname']);
 $vcdbVersion=$vcdb->version();
 $basevehicles=$vcdb->getAllBaseVehicles();
}

if($pim->validAutoCareLocalDatabaseName($_POST['pcdbname']))
{
 $pcdb=new pcdb($_POST['pcdbname']);
 $pcdbVersion=$pcdb->version();
 $positions=$pcdb->getAllPositions();
 $parttypeids=$pcdb->getAllParttypes();
}

if($pim->validAutoCareLocalDatabaseName($_POST['qdbname']))
{
 $qdb=new qdb($_POST['qdbname']);
 $qdbVersion=$qdb->version();
 $qdbs=$qdb->getAllQdbs(); 
}

$acesxmlstring='';
$streamXML=true;

$originalFilename='';
$validUpload=false;
$inputFileLog=array();
$apps=array();
$header=array();//'Company'=>'ACME Anvils','SenderName'=>'Luke Smith','SenderPhone'=>'804-329-3000','TransferDate'=>'2020-10-17','DocumentTitle'=>'stuff','EffectiveDate'=>'2020-10-17','SubmissionType'=>'FULL','VcdbVersionDate'=>'2020-08-28','QdbVersionDate'=>'2020-08-28','PcdbVersionDate'=>'2020-08-28');
$header=array('VcdbVersionDate'=>$vcdbVersion,'QdbVersionDate'=>$qdbVersion,'PcdbVersionDate'=>$pcdbVersion);

$assets=array();
$options=array();
        
$validcolumns=array('Application id','Reference','BaseVehicleid','Part','BrandAAIAID','PartTypeid','Positionid','Quantity','VCdb-coded Attributes','Qdb-coded Qualifiers','Notes','Mfr Label','AssetName','Asset Item Order');

$RefColumnId=-1; $BaseVehicleIDColumnId=-1; $PartNumberColumnId=-1; $PartTypeIDColumnId=-1; $BrandAAIAIDColumnId=-1; $PositionIDColumnId=-1; $QtyColumnId=-1; $VCdbAttributesColumnId=-1; $QdbQualifiersColumnId=-1; $NotesColumnId=-1; $MfrLabelColumnId=-1; $AssetNameColumnId=-1; $assetitemordercolumnid=-1;
$columnids=array();

        
if(isset($_POST['submit']) && $_POST['submit']=='Generate ACES xml')
{
 if($_FILES['fileToUpload']['type']=='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
 {
  if($_FILES['fileToUpload']['size']<5000000 || isset($_SESSION['userid']))   
  {     
   
   $xlsx = new XLSXReader($_FILES['fileToUpload']['tmp_name']);
   $sheetNames = $xlsx->getSheetNames();
   $originalFilename= basename($_FILES['fileToUpload']['name']);

   if(in_array('Applications',$sheetNames) && in_array('Header',$sheetNames))
   {
    $appsSheet=$xlsx->getSheetData('Applications');
   
    $validUpload=true;
    
     if(isset($appsSheet[0][3]) && $appsSheet[0][3]=='Part' )
     {
         
         
     }
     else
     {
      $validUpload=false;
      $inputFileLog[]='First row must contain specific named columns:Application id,Reference,BaseVehicleid,Part,PartTypeid,Positionid,Quantity,VCdb-coded Attributes,Qdb-coded Qualifiers,Notes,Mfr Label,AssetName,Asset Item Order';
      $logs->logSystemEvent('flatACEStoXML', 0, 'First row must contain specific named columns:Application id,Reference,BaseVehicleid,Part,PartTypeid,Positionid,Quantity,VCdb-coded Attributes,Qdb-coded Qualifiers,Notes,Mfr Label,AssetName,Asset Item Order');
     }
   }
   else
   { // Header or Items sheets are not present
    $inputFileLog[]='Uploaded workbook does not contain required worksheets named Applications and Header'; 
    $logs->logSystemEvent('flatACEStoXML', 0, 'Uploaded workbook does not contain required worksheets named Applications and Header');
   }
  }
  else
  {
   $inputFileLog[]='Input file was too big (5M limit for anonymous users)';
   $logs->logSystemEvent('flatACEStoXML', 0, 'Input file was too big (5M limit for anonymous users');
  }
 }
 else
 {
  $inputFileLog[]='Error uploading file - un-supported file format (must be a valid xlsx file)';
  $logs->logSystemEvent('flatACEStoXML', 0, 'Error uploading file - un-supported file format (must be a valid xlsx file');
 }
}

$errors=array(); $warnings=array(); $schemaresults=array();

if($validUpload)
{
    
 //extract xml header elements from the Header worksheet
 $headerSheet=$xlsx->getSheetData('Header');
 for($i=0;$i<20;$i++)
 {
  if(isset($headerSheet[$i]) && isset($headerSheet[$i][0]) && $headerSheet[$i][0]!='' && isset($headerSheet[$i][1]) && $headerSheet[$i][1]!='')
  {
   $header[$headerSheet[$i][0]]=$headerSheet[$i][1];
  }
 }
 
 // Establish a map of column names (identified by the first row - which is element 0) and their relative column number.
 // this way, there does not have to be a specific order of columns in the input data - we just need to 
 // verify that all of the required elements are present.
 foreach ($appsSheet[0] as $columnnumber=>$columnname)
 {
  if(in_array($columnname, $validcolumns)){$columnids[$columnname]=$columnnumber;}
 }     

$refcolumnid=$columnids['Reference'];
$basevehicleidcolumnid=$columnids['BaseVehicleid'];
$partnumbercolumnid=$columnids['Part'];
$brandcolumnid=$columnids['BrandAAIAID'];
$parttypeidcolumnid=$columnids['PartTypeid'];
$positionidcolumnid=$columnids['Positionid'];
$qtycolumnid=$columnids['Quantity'];
$vcdbattributescolumnid=$columnids['VCdb-coded Attributes'];
$qdbqualifierscolumnid=$columnids['Qdb-coded Qualifiers'];
$notescolumnid=$columnids['Notes'];
$mfrlabelcolumnid=$columnids['Mfr Label'];
$assetnamecolumnid=$columnids['AssetName'];
$assetitemordercolumnid=$columnids['Asset Item Order'];


 foreach ($appsSheet as $rownumber=>$row)
 {
  if($rownumber==0){continue;}  // skip the header row
    
  $goodrecord=true;
  $basevehicleid=intval($row[$basevehicleidcolumnid]);
  $partnumber=trim($row[$partnumbercolumnid]);
  $brand=trim($row[$brandcolumnid]);
  $ref=trim($row[$refcolumnid]);
  $parttypeid=intval($row[$parttypeidcolumnid]);
  $positionid=intval($row[$positionidcolumnid]);
  $qty=$row[$qtycolumnid];
  $vcdbattributesstring=trim($row[$vcdbattributescolumnid]);
  $qdbqualifiersstring=trim($row[$qdbqualifierscolumnid]); 
  $notes=trim($row[$notescolumnid]);
  $mfrlabel=$row[$mfrlabelcolumnid];
  $assetname=$row[$assetnamecolumnid];
  
  if(count($basevehicles) && !array_key_exists($basevehicleid, $basevehicles))
  {
   $errors[]='row number '.($rownumber+1).' contains an unknown BaseVehicleID ('.$basevehicleid.')';
   //continue;
  }

  if($positionid>0 && count($positions) && !array_key_exists($positionid, $positions))
  {
   $errors[]='row number '.($rownumber+1).' contains an unknown PositionID ('.$positionid.')';
   //continue;
  }
  
  if(count($parttypeids) && !array_key_exists($parttypeid, $parttypeids))
  {
   $errors[]='row number '.($rownumber+1).' contains an unknown PartTypeID ('.$parttypeid.')';
   //continue;
  }
  
  
  $attributes=array();
   
  if($vcdbattributesstring!='')
  {// EngineBase:2006;BodyType:5
   $rawattributestrings=explode(';',$vcdbattributesstring);
   foreach($rawattributestrings as $rawattributestring)
   {
    $attributebits=explode(':',$rawattributestring);
    if(count($attributebits)==2)
    {
     $attributes[]=array('id'=>0,'name'=>$attributebits[0],'value'=>intval($attributebits[1]),'type'=>'vcdb','sequence'=>1,'cosmetic'=>0);
    }
   }
  }

  if($qdbqualifiersstring!='')
  { // 10866:G16B;
    // 2431;
    // 4615:07/07/1994;
    // 2431;793:7/32 in;

   $rawqdbstrings=explode(';',$qdbqualifiersstring);
   foreach($rawqdbstrings as $rawqdbstring)
   {
    if(trim($rawqdbstring)==''){continue;}
    if(strpos($rawqdbstring, ':'))
    {// parms are present
     $qdbbits=explode(':',$rawqdbstring);
     $qdbid=intval($qdbbits[0]);
     unset($qdbbits[0]); // the 0th element is the Qdbid the rest of the elements are parms
     
     if(count($qdbs)==0 || array_key_exists($qdbid, $qdbs))
     {
      $attributes[]=array('id'=>0,'name'=>$qdbid,'value'=>implode('~', $qdbbits),'type'=>'qdb','sequence'=>1,'cosmetic'=>0);
     }
     else
     {// invalid qdbid -log the error and prevent this app from being added to the output
      $goodrecord=false;
      $errors[]='row number '.($rownumber+1).' contains an unknown QdbID ('.$qdbid.')';
     }
    }
    else
    {// no parms are present. like "13120" 
     $qdbid=intval($rawqdbstring);
     if(count($qdbs)==0 || array_key_exists($qdbid, $qdbs))
     {
      $attributes[]=array('id'=>0,'name'=>$qdbid,'value'=>'','type'=>'qdb','sequence'=>1,'cosmetic'=>0);   
     }
     else     
     {// qdb lookup failed (qdbid=0). log the error and prevent this app from being added to the output
      $goodrecord=false;
      $errors[]='row number '.($rownumber+1).' contains an unknown QdbID ('.$qdbid.')';
     }
    }   
   }
  }

  if($notes!='')
  {
   $attributes[]=array('id'=>0,'name'=>'note','value'=>$notes,'type'=>'note','sequence'=>1,'cosmetic'=>0);
  }
  
  
  if($goodrecord)
  {
   $apps[]=array('partnumber'=>$partnumber,'brand'=>$brand,'id'=>$ref,'basevehicleid'=>$basevehicleid,'parttypeid'=>$parttypeid,'positionid'=>$positionid,'quantityperapp'=>$qty,'attributes'=>$attributes,'cosmetic'=>0,'mfrlabel'=>$mfrlabel,'assetname'=>$assetname);
  }          
            
 }
   

 
 //-----------------------------------------------------
 $parttranslations=array(); // translations are not relavant here
 $partdescriptions=array();
 $doc=$ACESgenerator->createACESdoc($header, $apps, $assets, $parttranslations, $partdescriptions, $partdescriptions, $options);
 $doc->formatOutput=true;
 $acesxmlstring=$doc->saveXML();    

 $newdoc=new DOMDocument();
 $newdoc->loadXML($acesxmlstring); 
 // I do realize that this extra step seems redundant. Running the schema validation 
 // directly on the original object failed because of namespace problems that 
 // I could not resolve (or understand). Exporting the original object's xml 
 // to a text string and then re-importing it to a new DOM object was the
 // work-around that I found.
 
 $schemavalidated=true;   
 libxml_use_internal_errors(true);
 if(!$newdoc->schemaValidate('ACES_4_1_XSDSchema_Rev1.xsd'))
 {
  $schemavalidated=false;
  $schemaerrors = libxml_get_errors();
  foreach ($schemaerrors as $schemaerror)
  {
   $errormessage='';
   switch ($schemaerror->level) 
   {
    case LIBXML_ERR_WARNING:
     //$errormessage .= 'Warning code '. $schemaerror->code;
     break;
    case LIBXML_ERR_ERROR:
     //$errormessage .= 'Error code '.$schemaerror->code;
     break;
    case LIBXML_ERR_FATAL:
     //$errormessage .= 'Fatal Error code '.$schemaerror->code;
     break;
   }
   $errormessage.= trim($schemaerror->message);
   $schemaresults[]=$errormessage;   
  }
  libxml_clear_errors();
 }
}
else
{ // not a valid upload (for many possible reasons). Log it
    
    
}

if(isset($_POST['showtext']) || count($errors)>0 || count($schemaresults)>0 || !$validUpload)
{
 $streamXML=false; ?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php if (isset($_SESSION['userid'])){include('topnav.php');} ?>
        
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
                        <h3 class="card-header text-start">Build ACES xml from spreadsheet</h3>

                        <div class="card-body">
                            <h5 class="alert alert-secondary">Step 2: Analyze results and download XML</h5>
                            <div class="alert alert-info"><em>Validation done against VCdb: <?php echo $pcdbVersion;?> and PCdb version: <?php echo $vcdbVersion;?></em></div>
                            
                            <?php
                            if(!$validUpload){?>
                            <div style="padding:10px;background-color:#FF0000;font-size:1.5em;">Your input file has problems:</div>
                            <table class="table"><?php
                            foreach($inputFileLog as $result)
                            { // render each element of schema problems into a table
                                echo '<tr><td style="text-align:left;background-color:#FF0000;">'.$result.'</td></tr>';
                            }
                            ?>
                            </table>
                            <?php }?>

                            <?php if(count($schemaresults)>0){?>
                            <div style="padding:10px;background-color:#FF8800;font-size:1.5em;">Your input data causes schema (XSD) problems. Here they are:</div>
                            <table class="table"><?php
                            foreach($schemaresults as $result)
                            { // render each element of schema problems into a table
                             echo '<tr><td style="text-align:left;background-color:#FF8800;">'.$result.'</td></tr>';
                            } ?>
                            </table>
                            <?php }else{if(strlen($acesxmlstring)>0){?>
                            <div style="padding:10px;"><textarea rows="20" cols="150"><?php echo $acesxmlstring;?></textarea></div>
                            <?php }}?>

                            <?php if(count($errors)>0){?>
                            <div style="padding:10px;background-color:yellow;font-size:1.5em;margin: 10px;"><?php if(count($schemaresults)==0){echo 'XSD-validated output was produced and rendered to the text box above. You could copy/paste it into a text editor and save that as a .xml file. However, ';} ?>your input data contains invalid references (included in the xml above) to the AutoCare database versions selected. Here are the ones we detected:</div>
                            <table class="table"><?php
                            foreach($errors as $error)
                            {
                                echo '<tr><td style="text-align:left;background-color:yellow;">'.$error.'</td></tr>';
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
       <?php if (isset($_SESSION['userid'])){include('./includes/footer.php');} ?>
    </body>
</html>
<?php }
$logs->logSystemEvent('flatACEStoXML', 0, 'file:'.$originalFilename.';apps:'.count($apps).';xsd:'.count($schemaresults).';logic:'.count($errors).';by:'.$_SERVER['REMOTE_ADDR']);

//echo '<textarea>'.$acesxmlstring.'</textarea>';

if($streamXML && $validUpload)
{
$filename='ACES_4_1_FULL_'.date('Y-m-d').'.xml';
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Type: application/octet-stream');
header('Content-Length: ' . strlen($acesxmlstring));
header('Connection: close');    
echo $acesxmlstring;
}?>