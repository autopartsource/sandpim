<?php
include_once('./class/pimClass.php');
include_once('./class/pricingClass.php');
include_once('./class/logsClass.php');
$navCategory = 'import';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importPricesText.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
}



$pricing = new pricing;
$errors=array();
$importresults=array();
$finalresultmessage='';
        
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

   if(validateDate($expirationdate) && validateDate($effectivedate))
   {   
    if($pricing->getPricesheet($pricesheetnumber))
    {
     if (strlen($partnumber) <= 20 && strlen($partnumber) > 0) 
     { // partnumber is within valid length
      if($pim->validPart($partnumber)) 
      {

       $existingprices=$pricing->getPrices($partnumber, $pricesheetnumber,$currencycode, $uom, $pricetype);
       foreach($existingprices as $existingprice)
       {
        $pricing->deletePriceById($existingprice['id']);
        $pim->logPartEvent($partnumber,$_SESSION['userid'],'price ('.$existingprice['amount'].' '.$existingprice['currency'].') removed during import','');
       }

       $newoid=$pim->updatePartOID($partnumber);
       $pricing->addPrice($partnumber, $pricesheetnumber, $amount, $currencycode, $uom, $pricetype, $effectivedate, $expirationdate);
       $pim->logPartEvent($partnumber,$_SESSION['userid'],'price imported '.$pricetype.' into pricesheet '.$pricesheetnumber.', '.$amount.' '.$currencycode.'  effective from '.$effectivedate.' to '.$expirationdate,$newoid);
       $importresults[]=$pricetype.' price of '.$amount.' '.$currencycode.' for partnumber '.$partnumber.' imported to pricesheet '.$pricesheetnumber;
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
    {// pricesheet number was not valid
     $errors[]='invalid pricesheet number ['.$pricesheetnumber.'] in row '.$recordnumber;
     $invalidcount++;        
    }
   }
   else
   {// date(s) were not valid format
     $errors[]='invalid date format in row '.$recordnumber.'. Dates must be in format YYYY-MM-DD';
     $invalidcount++;               
   }
  }
  else
  {// field count is wrong
   $errors[]='Field count was wrong (expected exactly 8 tab-delimited columns)';
  }
 }
 $finalresultmessage='Imported '.$importcount.' price records';
 if($invalidcount>0){$finalresultmessage.='. '.$invalidcount.' records were ignored because of invalid data.';};
 $finalresultmessage.='. See details at the bottom of this page.';
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
                            <?php echo '<div>'.$finalresultmessage.'</div>'; ?>
                            
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste 8 tab-delimited columns of data (no header row):</h6>
                                    <p>Partnumber, <a href="./priceSheets.php">Pricesheet Number</a>, Amount, Currency Code, UoM, Price Type,  Effective Date, Expiration Date</p>
                                    <p>Date format is YYYY-MM-DD. Existing records with the same partnumber, pricesheet number, currency, uom and price type will be replaced.</p>
                                </div>
                                    
                                <textarea name="input" style="width:100%;height:200px;"></textarea>                         
                                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                            </form>
                        </div>
                            <?php
                                foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}
                                foreach($importresults as $importresult){echo '<div class="alert alert-success" role="alert">'.$importresult.'</div>';}
                            ?>

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