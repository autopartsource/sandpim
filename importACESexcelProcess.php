<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/XLSXReaderClass.php');
$navCategory = 'import';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importACESexcelProcess.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$logs=new logs();

$originalFilename='';
$validUpload=false;
$inputFileLog=array();

if(isset($_POST['submit']) && $_POST['submit']=='Import')
{
 if($_FILES['fileToUpload']['type']=='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
 {
  $xlsx = new XLSXReader($_FILES['fileToUpload']['tmp_name']);
  $sheetNames = $xlsx->getSheetNames();
  $originalFilename= basename($_FILES['fileToUpload']['name']);

  if(in_array('Applications',$sheetNames))
  {
   $appsSheetRows=$xlsx->getSheetData('Applications');
   $validUpload=true;
   
   if(isset($appsSheetRows[0][0]) && $appsSheetRows[0][0]=='Qty' && isset($appsSheetRows[0][1]) && $appsSheetRows[0][1]=='PartNum' && isset($appsSheetRows[0][2]) && $appsSheetRows[0][2]=='PartTypeID' && isset($appsSheetRows[0][3]) && $appsSheetRows[0][3]=='PositionID' && isset($appsSheetRows[0][4]) && $appsSheetRows[0][4]=='MfrLabel' && isset($appsSheetRows[0][5]) && $appsSheetRows[0][5]=='Note' && isset($appsSheetRows[0][6]) && $appsSheetRows[0][6]=='BaseVehicleID')
   {
       //first 7 colums are named correctly
   }
   else
   {
    $validUpload=false;
    $inputFileLog[]='First row of applications sheet must start with these seven columns:  Qty, PartNum, PartTypeID, PositionID, MfrLabel, Note, BaseVehicleID. VCdb attribute columns after that are optional and the order is arbitrary';
    $logs->logSystemEvent('importACESexcel', 0, 'First row of Items sheet must start with these three columns:  Qty, PartNum, PartTypeID, PositionID, MfrLabel, Note, BaseVehicleID');
   }
  }
  else
  { // Applications sheet not present
   $inputFileLog[]='Uploaded workbook does not contain required worksheets: Applications'; 
   $logs->logSystemEvent('importACESexcel', 0, 'Uploaded workbook does not contain required: Applications');
  }
 }
 else
 {
  $inputFileLog[]='Error uploading file - un-supported file format (must be a valid xlsx file)';
  $logs->logSystemEvent('importACESexcel', 0, 'Error uploading file - un-supported file format');
 }
}

$errors=array(); $warnings=array(); $output='';
$vcdbAttributeColumns=array('EngineBaseID','AspirationID','BedLengthID','BedTypeID','BodyNumDoorsID','BodyTypeID','BrakeABSID','BrakeSystemID','CylinderHeadTypeID','DriveTypeID','EngineDesignationID','EngineMfrID','EngineVersionID','EngineVINID','FrontBrakeTypeID','FrontSpringTypeID','FuelDeliverySubTypeID','FuelDeliveryTypeID','FuelSystemControlTypeID','FuelSystemDesignID','FuelTypeID','IgnitionSystemTypeID','MfrBodyCodeID','PowerOutputID','RearBrakeTypeID','RearSpringTypeID','RegionID','SteeringSystemID','SteeringTypeID','SubModelID','TransmissionControlTypeID','TransmissionElecControlledID','TransmissionMfrID','TransmissionMfrCodeID','TransmissionNumSpeedsID','TransmissionTypeID','ValvesPerEngineID','WheelBaseID');

$columnmap=array();

if($validUpload)
{
 $recordnumber=0;
 foreach($appsSheetRows as $fields)
 {
  if($recordnumber==0)
  {// this is the header row - mine it for attribute column indexes
      
   foreach($vcdbAttributeColumns as $vcdbAttributeColumn)
   {
    for($i=0;$i<=count($fields)-1; $i++)
    {
     if($fields[$i] == $vcdbAttributeColumn){$columnmap[$vcdbAttributeColumn]=$i;}        
    }
   }
   
   $recordnumber++;
   continue;   
  }
  
  // convert all columns containing a numeric value to our native format:
  //name|value|sequence|cosmetic~name|value|sequence|cosmetic~...
  // EngineBase|1250|1|0~Aspiration:5|2|0

  $notechunks=array();
  if(trim($fields[5])!='')
  { // note field contains something
      $rawnotechunks=explode(';',trim($fields[5])); $sequence=0;
      foreach($rawnotechunks as $rawnotechunk)
      {
          $sequence++;
          $notechunks[]=trim($rawnotechunk).'|'.$sequence.'|'.'0';
      }
  }
  
  $vcdbattributechunks=array();
  $sequence=0;
  foreach ($columnmap as $columnname=>$columnindex)
  {
      if(intval($fields[$columnindex])>0)
      {
          $sequence++;
          // strip off the "ID" from the column name
          $columnnameclean=$columnname;
          if(strlen($columnname)>2 && substr($columnname,-2)=='ID')
          {
           $columnnameclean= substr($columnname,0 , strlen($columnname)-2);
          }
          $vcdbattributechunks[]=$columnnameclean.'|'.intval($fields[$columnindex]).'|'.$sequence.'|'.'0';
      }
  }
  $positionid=1; if(intval($fields[3])>0){$positionid=intval($fields[3]);}
  $output.="0\t".intval($fields[6])."\t".trim($fields[1])."\t".intval($fields[2])."\t".$positionid."\t".floatval($fields[0])."\t".implode('~',$vcdbattributechunks)."\t\t".implode('~',$notechunks)."\r\n";
  $recordnumber++;
 }
 
 $inputFileLog[]='processed '.$recordnumber.' records';
 
 $randomstring=$pim->uuidv4();
 $localfilename=__DIR__.'/ACESuploads/'.$randomstring;
 if(file_put_contents($localfilename, $output))
 {
  $token=$pim->createBackgroundjob('ACESflatImport','started',$_SESSION['userid'],$localfilename,'','partcategory:'.$_POST['partcategory'],date('Y-m-d H:i:s'),'text/xml','');
  $logs->logSystemEvent('Import', $_SESSION['userid'], 'ACES excel (fixed VCdb colum format) import setup for houskeeper by:'.$_SERVER['REMOTE_ADDR']);
  $inputFileLog[]='Input data was queued for background process to import ('.$recordnumber.' input lines). Got to <a href="./backgroundJobs.php"> Settings -> Manage background import/export jobs</a> to monitor progress';      
  $inputFileLog[]='Server-side Temp file: '.$localfilename;
 }
 else    
 {// file write was 0 bytes (failure)
  $inputFileLog[]='Failed to write input text to local file';
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
        <?php include('topnav.php');?>

        <!-- Header -->
        
        
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
                        <h3 class="card-header text-start">Import applications from spreadsheet of fixed-column VCdb attributes</h3>

                        <div class="card-body">
                            <div style="padding:10px;">Process Log:</div>
                            <table class="table">
                            <?php
                            foreach($inputFileLog as $result)
                            { // render each element of schema problems into a table
                                echo '<tr><td style="text-align:left;">'.$result.'</td></tr>';
                            }
                            ?>
                            </table>
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
