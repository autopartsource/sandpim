<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
$navCategory = 'import/export';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$pim = new pim;

$importcount=0; $invalidcount=0; $recordnumber=0;

if (isset($_POST['input'])) 
{
 $input = $_POST['input'];
 $records = explode("\r\n", $_POST['input']);

 $PartNumberFieldIndex=-1; $PackageUOMFieldIndex=-1; $QuantityofEachesFieldIndex=-1; $WeightFieldIndex=-1; $WeightsUOMFieldIndex=-1; $InnerQuantityFieldIndex=-1; $InnerQuantityUOMFieldIndex=-1; $MerchandisingHeightFieldIndex=-1; $MerchandisingWidthFieldIndex=-1;  $MerchandisingLengthFieldIndex=-1; $DimensionsUOMFieldIndex=-1;

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
  if($headerfields[$i]=='MerchandisingHeight'){$MerchandisingHeightFieldIndex=$i;}
  if($headerfields[$i]=='MerchandisingWidth'){$MerchandisingWidthFieldIndex=$i;}
  if($headerfields[$i]=='MerchandisingLength'){$MerchandisingLengthFieldIndex=$i;}
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
   
      
      
      
      
      
   $pim->logPartEvent($partnumber,$_SESSION['userid'],'packaging record imported: ','');
   $importcount++;
  }
  else
  {// invalid part - make a note of it
   $errors[]='invalid partnumber ['.$partnumber.'] in row '.$recordnumber;
   $invalidcount++;
  }
    
 }
 $finalresultmessage='Imported '.$importcount.' interchange records';
 if($invalidcount>0){$finalresultmessage.='. '.$invalidcount.' records were ignored because of invalid data.';};
 $errors[]=$finalresultmessage;
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
                            <?php foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}?>
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste tab-delimited data (including header row):</h6>
                                    <p>PartNumber, PackageUOM,	QuantityofEaches, [Weight], [WeightsUOM], [InnerQuantity], [InnerQuantityUOM], [MerchandisingHeight], [MerchandisingWidth], [MerchandisingLength], [DimensionsUOM]</p>
                                </div>
                                    
                                <textarea name="input" rows="20" cols="100"></textarea>
                                
                                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                            </form>
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