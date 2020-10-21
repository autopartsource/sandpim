<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/ACES4_1GeneratorClass.php');
include_once('./class/XLSXReaderClass.php');
$navCategory = 'import/export';

session_start();

$pim = new pim;
$ACESgenerator=new ACESgenerator();
$pcdb=new pcdb($_POST['pcdbversion']);
$vcdb=new vcdb($_POST['vcdbversion']);
$qdb=new qdb($_POST['qdbversion']);
$pcdbVersion=$pcdb->version();
$vcdbVersion=$vcdb->version();
$qdbVersion=$qdb->version();
$logs=new logs();

$acesxmlstring='';
$streamXML=true;

$originalFilename='';
$validUpload=false;
$inputFileLog=array();
$apps=array();
$header=array('Company'=>'ACME Anvils','SenderName'=>'Luke Smith','SenderPhone'=>'804-329-3000','TransferDate'=>'2020-10-17','DocumentTitle'=>'stuff','EffectiveDate'=>'2020-10-17','SubmissionType'=>'FULL','VcdbVersionDate'=>'2020-08-28','QdbVersionDate'=>'2020-08-28','PcdbVersionDate'=>'2020-08-28');
$assets=array();
$options=array();
        
$validcolumns=array('Ref','BaseVehicleID','PartNumber','PartTypeID','PositionID','Qty','VCdb Attributes','Qdb Qualifiers','Free-Form Fitments Notes','MfrLabel','AssetName');
$RefColumnId=-1; $BaseVehicleIDColumnId=-1; $PartNumberColumnId=-1; $PartTypeIDColumnId=-1; $PositionIDColumnId=-1; $QtyColumnId=-1; $VCdbAttributesColumnId=-1; $QdbQualifiersColumnId=-1; $NotesColumnId=-1; $MfrLabelColumnId=-1; $AssetNameColumnId=-1;
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

   if(in_array('Applications',$sheetNames))
   {
    $appsSheet=$xlsx->getSheetData('Applications');
   
    $validUpload=true;
    
     if(isset($appsSheet[0][2]) && $appsSheet[0][2]=='PartNumber' )
     {
         
         
     }
     else
     {
      $validUpload=false;
      $inputFileLog[]='First row must contain these three columns: BaseVehicleID, PartNumber, PartTypeID, PositionID, Qty,';
      $logs->logSystemEvent('rhubarb', 0, 'First row must contain these three columns: PartNumber, PartType, Make, Model, YearFrom, YearTo');
     }
   }
   else
   { // Header or Items sheets are not present
    $inputFileLog[]='Uploaded workbook does not contain required worksheet named Applications'; 
    $logs->logSystemEvent('rhubarb', 0, 'Uploaded workbook does not contain required worksheet named Applications');
   }
  }
  else
  {
   $inputFileLog[]='Input file was too big (5M limit for anonymous users)';
   $logs->logSystemEvent('rhubarb', 0, 'Input file was too big (1M limit for anonymous users');
  }
 }
 else
 {
  $inputFileLog[]='Error uploading file - un-supported file format (must be a valid xlsx file)';
  $logs->logSystemEvent('rhubarb', 0, 'Error uploading file - un-supported file format');
 }
}

$errors=array(); $warnings=array(); $schemaresults=array();

if($validUpload)
{
     
 // Establish a map of column names (identified by the first row - which is element 0) and their relative column number.
 // this way, there does not have to be a specific order of columns in the input data - we just need to 
 // verify that all of the required elements are present.
 foreach ($appsSheet[0] as $columnnumber=>$columnname)
 {
  if(in_array($columnname, $validcolumns)){$columnids[$columnname]=$columnnumber;}
 }     

$refcolumnid=$columnids['Ref'];
$basevehicleidcolumnid=$columnids['BaseVehicleID'];
$partnumbercolumnid=$columnids['PartNumber'];
$parttypeidcolumnid=$columnids['PartTypeID'];
$positionidcolumnid=$columnids['PositionID'];
$qtycolumnid=$columnids['Qty'];
$vcdbattributescolumnid=$columnids['VCdb Attributes'];
$qdbqualifierscolumnid=$columnids['Qdb Qualifiers'];
$notescolumnid=$columnids['Free-Form Fitments Notes'];
$mfrlabelcolumnid=$columnids['MfrLabel'];
$assetnamecolumnid=$columnids['AssetName'];


 foreach ($appsSheet as $rownumber=>$row)
 {
  if($rownumber==0){continue;}  // skip the header row
    
  $goodrecord=true;
  $basevehicleid=intval($row[$basevehicleidcolumnid]);
  $partnumber=trim($row[$partnumbercolumnid]);
  $ref=trim($row[$refcolumnid]);
  $parttypeid=intval($row[$parttypeidcolumnid]);
  $positionid=intval($row[$positionidcolumnid]);
  $qty=$row[$qtycolumnid];
  $vcdbattributesstring=trim($row[$vcdbattributescolumnid]);
  $qdbqualifiersstring=trim($row[$qdbqualifierscolumnid]); 
  $notes=trim($row[$notescolumnid]);
  $mfrlabel=$row[$mfrlabelcolumnid];
  $assetname=$row[$assetnamecolumnid];
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
  {// 13120;4623^10/05/2009|~12/08/2010|
    //in this example, 13120 = "with Automatic Temperature Control" and has no parms
    //4623 is "From <date> to <date>" -- 2 parms and no units of measure
     
   $rawqdbstrings=explode(';',$rawqdbstrings);
   foreach($rawqdbstrings as $rawqdbstring)
   {
    if(strpos($rawqdbstring, '^'))
    {// parms are present
     $qdbbits=explode('^',$rawqdbstring);
     $attributes[]=array('id'=>0,'name'=>intval($qdbbits[0]),'value'=>$qdbbits[1],'type'=>'qdb','sequence'=>1,'cosmetic'=>0);
    }
    else
    {// no parms are present. like "13120"   
     $attributes[]=array('id'=>0,'name'=>intval($rawqdbstring),'value'=>'','type'=>'qdb','sequence'=>1,'cosmetic'=>0);
    }   
   }
  }

  if($notes!='')
  {
   $attributes[]=array('id'=>0,'name'=>'note','value'=>$notes,'type'=>'note','sequence'=>1,'cosmetic'=>0);
  }
  
  if($goodrecord)
  {
   $apps[]=array('partnumber'=>$partnumber,'id'=>$ref,'basevehicleid'=>$basevehicleid,'parttypeid'=>$parttypeid,'positionid'=>$positionid,'quantityperapp'=>$qty,'attributes'=>$attributes);
  }
            
            
 }
   

 
 //-----------------------------------------------------
 $doc=$ACESgenerator->createACESdoc($header, $apps, $assets, $options);
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
{ // not a valid upload (for many possible reasons)
    
    
}

if(isset($_POST['showtext']) || (count($errors)>0 && !isset($_POST['ignorelookupfails']) ) || count($schemaresults)>0 || !$validUpload)
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

        <!-- Header -->
        <h1>Build ACES xml from spreadsheet</h1>
        <h2>Step 2: Analyze results and download XML</h2>
        <div style="font-style: italic;">Validation done against VCdb: <?php echo $pcdbVersion;?> and PCdb version: <?php echo $vcdbVersion;?></div>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <?php
                    if(!$validUpload){?>
                    <div style="padding:10px;background-color:#FF0000;font-size:1.5em;">Your input file has problems:</div>
                    <table><?php
                    foreach($inputFileLog as $result)
                    { // render each element of schema problems into a table
                        echo '<tr><td style="text-align:left;background-color:#FF0000;">'.$result.'</td></tr>';
                    }
                    ?>
                    </table>
                    <?php }?>

                    <?php if(count($schemaresults)>0){?>
                    <div style="padding:10px;background-color:#FF8800;font-size:1.5em;">Your input data causes schema (XSD) problems. Here they are:</div>
                    <table><?php
                    foreach($schemaresults as $result)
                    { // render each element of schema problems into a table
                     echo '<tr><td style="text-align:left;background-color:#FF8800;">'.$result.'</td></tr>';
                    } ?>
                    </table>
                    <?php }else{if(strlen($acesxmlstring)>0){?>
                    <div style="padding:10px;"><textarea rows="20" cols="150"><?php echo $acesxmlstring;?></textarea></div>
                    <?php }}?>

                    <?php if(count($errors)>0 && !isset($_POST['ignorelookupfails'])){?>
                    <div style="padding:10px;background-color:yellow;font-size:1.5em;"><?php if(count($schemaresults)==0){echo 'XSD-validated output was (or could be) produced. However, ';} ?>your input data contains logic problems. Here are the ones we detected:</div>
                    <table><?php
                    foreach($errors as $error)
                    {
                        echo '<tr><td style="text-align:left;background-color:yellow;">'.$error.'</td></tr>';
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
       <?php if (isset($_SESSION['userid'])){include('./includes/footer.php');} ?>
    </body>
</html>
<?php }
$logs->logSystemEvent('rhubarb', 0, 'file:'.$originalFilename.';apps:'.count($apps).';xsd:'.count($schemaresults).';logic:'.count($errors).';by:'.$_SERVER['REMOTE_ADDR']);

echo '<textarea>'.$acesxmlstring.'</textarea>';

if($streamXML && $validUpload)
{
$filename='ACES_4_1_FULL_'.date('Y-m-d').'.xml';
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Type: application/octet-stream');
header('Content-Length: ' . strlen($acesxmlstring));
header('Connection: close');    
echo $acesxmlstring;
}?>