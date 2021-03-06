<?php
include_once('./class/pimClass.php');
include_once('./class/pricingClass.php');
$navCategory = 'import/export';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$pricing = new pricing;
$errors=array();

$importcount=0; $invalidcount=0; $recordnumber=0;

if (isset($_POST['input'])) 
{
 $input = $_POST['input'];
 $records = explode("\r\n", $_POST['input']);
 foreach ($records as $record) 
 {
  $recordnumber++;
  $fields = explode("\t", $record);
  
  if(count($fields)==1 && $fields[0]==''){continue;}
    
  if (count($fields) == 8) 
  {
   $partnumber = trim(strtoupper($fields[0]));
   $pricesheetnumber=trim(strtoupper($fields[1]));
   $amount=floatval($fields[2]);
   $currencycode=trim(strtoupper($fields[3]));
   $uom=trim(strtoupper($fields[4]));
   $pricetype=trim(strtoupper($fields[5]));
   $effectivedate=trim($fields[6]);
   $expirationdate=trim($fields[7]);

   if (strlen($partnumber) <= 20 && strlen($partnumber) > 0) 
   { // partnumber is within valid length
    if($pim->validPart($partnumber)) 
    {
     $pricing->addPrice($partnumber, $pricesheetnumber, $amount, $currencycode, $uom, $pricetype, $effectivedate, $expirationdate);
     $pim->logPartEvent($partnumber,$_SESSION['userid'],'price imported '.$pricetype.' into pricesheet '.$pricesheetnumber.', '.$amount.' '.$currencycode.'  effective from '.$effectivedate.' to '.$expirationdate,'');
     $importcount++;
    }
    else
    {// invalid part - make a note of it
     $errors[]='invalid partnumber ['.$partnumber.'] in row '.$recordnumber;
     $invalidcount++;
    }
   }
  }
  else
  {// field count is wrong
   $errors[]='Field count was wrong (expected exactly 8 tab-delimited columns)';
  }
 }
 $finalresultmessage='Imported '.$importcount.' price records';
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
                        <h3 class="card-header text-start">Import Part Prices</h3>

                        <div class="card-body">
                            <?php foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}?>
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste tab-delimited data:</h6>
                                    <p>Partnumber, <a href="./priceSheets.php">Pricesheet Number</a>, Amount, Currency Code, UoM, Price Type,  Effective Date, Expiration Date</p>
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