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
$interchange = new interchange;
$pricing = new interchange;
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
    
  if (count($fields) == 7) 
  {
   $partnumber = trim(strtoupper($fields[0]));

   if (strlen($partnumber) <= 20 && strlen($partnumber) > 0) 
   { // partnumber is within valid length
    if($pim->validPart($partnumber)) 
    {
     $partnumber=strtoupper(trim($fields[0]));
     $brandAAIAID=strtoupper(trim($fields[1]));
     $competitorpartnumber= strtoupper(trim($fields[2]));
     $interchangequantity= floatval(trim($fields[3]));
     $uom=trim($fields[4]);
     $interchangenotes=$fields[5];
     $internalnotes=$fields[6];
     
     $interchange->addInterchange($partnumber,$competitorpartnumber,$brandAAIAID,$interchangequantity,$uom,$interchangenotes,$internalnotes);
     $pim->logPartEvent($partnumber,$_SESSION['userid'],'competitor interchange to:'.$brandAAIAID.'/'.$competitorpartnumber.' imported','');
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
   $errors[]='Field count was wrong (expected exactly 7 tab-delimited columns)';
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

        <!-- Header -->
        <h1>Import Competitor Interchange</h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                
                <?php foreach($errors as $error){echo '<div style="color:red;">'.$error.'</div>';}?>
                <form method="post">
                    <div style="padding:10px;"><div>Paste tab-delimited data:<br/>Partnumber, Competitor BrandID, Competitor partnumber, Competitor Quantity, UoM, Public Notes, Internal notes</div>
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