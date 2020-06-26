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

        <!-- Header -->
        <h1>Import Part Prices</h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                
                <?php foreach($errors as $error){echo '<div style="color:red;">'.$error.'</div>';}?>
                <form method="post">
                    <div style="padding:10px;"><div>Paste tab-delimited data:<br/>Partnumber, Pricesheet Number, Amount, Currency Code, UoM, Price Type,  Effective Date, Expiration Date</div>
                        <textarea name="input" rows="20" cols="100"></textarea>
                    </div>
                    <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                </form>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('./includes/footer.php'); ?>
    </body>
</html>