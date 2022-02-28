<?php
include_once('./class/pimClass.php');
include_once('./class/packagingClass.php');
include_once('./class/logsClass.php');

$navCategory = 'import';

$pim = new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'importPackagingText.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}



session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$packaging=new packaging;
$importcount=0; $invalidcount=0; $recordnumber=0; $errors=array(); $results=array(); 

if (isset($_POST['input'])) 
{
 $input = $_POST['input'];
 $records = explode("\r\n", $_POST['input']);

 $PartNumberFieldIndex=-1; $PackageUOMFieldIndex=-1; $QuantityofEachesFieldIndex=-1; $WeightFieldIndex=-1; $WeightsUOMFieldIndex=-1; $InnerQuantityFieldIndex=-1; $InnerQuantityUOMFieldIndex=-1; $ShippingHeightFieldIndex=-1; $ShippingWidthFieldIndex=-1;  $ShippingLengthFieldIndex=-1; $DimensionsUOMFieldIndex=-1;

 $headerfields=explode("\t",$records[0]);

 
 for($i=0; $i<=count($headerfields)-1; $i++)
 { // identify the named columns' IDs
  if($headerfields[$i]=='PartNumber'){$PartNumberFieldIndex=$i;}
  if($headerfields[$i]=='PackageUOM'){$PackageUOMFieldIndex=$i;}
  if($headerfields[$i]=='QuantityofEaches'){$QuantityofEachesFieldIndex=$i;}
  if($headerfields[$i]=='Weight'){$WeightFieldIndex=$i;}
  if($headerfields[$i]=='WeightsUOM'){$WeightsUOMFieldIndex=$i;}
  if($headerfields[$i]=='InnerQuantity'){$InnerQuantityFieldIndex=$i;}
  if($headerfields[$i]=='InnerQuantityUOM'){$InnerQuantityUOMFieldIndex=$i;}
  if($headerfields[$i]=='ShippingHeight'){$ShippingHeightFieldIndex=$i;}
  if($headerfields[$i]=='ShippingWidth'){$ShippingWidthFieldIndex=$i;}
  if($headerfields[$i]=='ShippingLength'){$ShippingLengthFieldIndex=$i;}
  if($headerfields[$i]=='DimensionsUOM'){$DimensionsUOMFieldIndex=$i;}   
 } 


 
 foreach ($records as $record) 
 {
  $recordnumber++;
  $fields = explode("\t", $record);
  
  if(count($fields)==1 && $fields[0]==''){continue;}
    
  if($recordnumber==1)
  {// this is the header row
      
   if($PartNumberFieldIndex!=0 || $PackageUOMFieldIndex!=1 || $QuantityofEachesFieldIndex!=2)
   {
    $errors[]='Header row must start with: PartNumber (tab) PackageUOM (tab) QuantityofEaches';
    break;
   }
   continue;;
  }
  
  $partnumber = trim(strtoupper($fields[$PartNumberFieldIndex]));

  if ($pim->validPart($partnumber)) 
  { // partnumber is valid
   
   $packageuom=trim($fields[$PackageUOMFieldIndex]);
   $quantityofeaches=trim($fields[$QuantityofEachesFieldIndex]);
   $innerquantity=trim($fields[$InnerQuantityFieldIndex]);
   $innerquantityuom=trim($fields[$InnerQuantityUOMFieldIndex]);
   $weight=0; if(isset($fields[$WeightFieldIndex]) && is_numeric(trim($fields[$WeightFieldIndex]))){$weight=trim($fields[$WeightFieldIndex]);}
   $weightsuom='PG'; if(isset($fields[$WeightsUOMFieldIndex]) && trim($fields[$WeightsUOMFieldIndex])!=''){$weightsuom=trim($fields[$WeightsUOMFieldIndex]);}
   $packagelevelGTIN='';
   $packagebarcodecharacters='';
   $shippingheight=0; if(isset($fields[$ShippingHeightFieldIndex]) && is_numeric(trim($fields[$ShippingHeightFieldIndex]))){$shippingheight=trim($fields[$ShippingHeightFieldIndex]);}
   $shippingwidth=0; if(isset($fields[$ShippingWidthFieldIndex]) && is_numeric(trim($fields[$ShippingWidthFieldIndex]))){$shippingwidth=trim($fields[$ShippingWidthFieldIndex]);}
   $shippinglength=0; if(isset($fields[$ShippingLengthFieldIndex]) && is_numeric(trim($fields[$ShippingLengthFieldIndex]))){$shippinglength=trim($fields[$ShippingLengthFieldIndex]);}
   $dimensionsuom='IN'; if(isset($fields[$DimensionsUOMFieldIndex]) && trim($fields[$DimensionsUOMFieldIndex])!=''){$dimensionsuom=trim($fields[$DimensionsUOMFieldIndex]);}      
   $packaging->addPackage($partnumber, $packageuom, $quantityofeaches, $innerquantity, $innerquantityuom, $weight, $weightsuom, $packagelevelGTIN, $packagebarcodecharacters, $shippingheight, $shippingwidth, $shippinglength, $dimensionsuom);
   $oid=$pim->updatePartOID($partnumber);
   $pim->logPartEvent($partnumber,$_SESSION['userid'],'packaging record imported ('.$innerquantity.' '.$packageuom.'; '.$weight.' '.$weightsuom.'; ' .$shippinglength.'x'.$shippingwidth.'x'.$shippingheight.'x '.$dimensionsuom.')',$oid);
   $importcount++;
   $results[]='['.$innerquantity.' '.$packageuom.'; '.$weight.' '.$weightsuom.'; ' .$shippinglength.'x'.$shippingwidth.'x'.$shippingheight.'x '.$dimensionsuom.'] -> '.$partnumber;
  }
  else
  {// invalid part - make a note of it
   $errors[]='invalid partnumber ['.$partnumber.'] in row '.$recordnumber;
   $invalidcount++;
  }
    
 }
 
 $finalresultmessage='Imported '.$importcount.' package records'; if($invalidcount>0){$finalresultmessage.='. '.$invalidcount.' records were ignored because of invalid data.';};
 $logs->logSystemEvent('import', $_SESSION['userid'], $finalresultmessage);
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
                        <h3 class="card-header text-start">Import Packaging</h3>
                        <div class="card-body">
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste tab-delimited data (including header row):</h6>
                                    <p>PartNumber, PackageUOM,	QuantityofEaches, [Weight], [WeightsUOM], [InnerQuantity], [InnerQuantityUOM], [ShippingHeight], [ShippingWidth], [ShippingLength], [DimensionsUOM]</p>
                                </div>                                    
                                <textarea style="width:100%;" name="input" rows="15"></textarea>
                                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                            </form>
                            <?php foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}?>
                            <?php foreach($results as $result){echo '<div class="alert alert-success" role="alert">'.$result.'</div>';}?>
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