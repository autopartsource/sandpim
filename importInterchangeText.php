<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
$navCategory = 'import';


$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importINterchangeText.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$interchange = new interchange;
$pricing = new interchange;
$errors=array();
$importresults= array();
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
    
  if (count($fields) == 7) 
  {
   $partnumber = trim(strtoupper($fields[0]));

   if(strlen($partnumber) <= 20 && strlen($partnumber) > 0 && $pim->validPart($partnumber)) 
   { // partnumber is valid
    $partnumber=strtoupper(trim($fields[0]));
    $brandAAIAID=strtoupper(trim($fields[1]));
    $competitorpartnumber= strtoupper(trim($fields[2]));
    // look for a coma in the part and slpit accordingly
    
    $competitorparts=explode(',',$competitorpartnumber);
    
    foreach($competitorparts as $competitorpart)
    {
     $interchangequantity= floatval(trim($fields[3]));
     $uom=trim($fields[4]);
     $interchangenotes=$fields[5];
     $internalnotes=$fields[6];
     
     $interchange->addInterchange($partnumber,trim($competitorpart),$brandAAIAID,$interchangequantity,$uom,$interchangenotes,$internalnotes);
     $newoid=$pim->updatePartOID($partnumber);
     $importresults[]='partnumber '.$partnumber.' interchange to '.trim($competitorpart).' imported';
     $pim->logPartEvent($partnumber,$_SESSION['userid'],'competitor interchange to:'.$brandAAIAID.'/'.trim($competitorpart).' imported',$newoid);
     $importcount++;
    }
   }
   else
   {// invalid part - make a note of it
     $errors[]='invalid partnumber ['.$partnumber.'] in row '.$recordnumber;
     $invalidcount++;
   }
  }
  else
  {// field count is wrong
   $errors[]='Field count was wrong ('.count($fields).') in row '.$recordnumber.' Expected exactly 7 tab-delimited columns.';
  }
 }
 $finalresultmessage='Imported '.$importcount.' interchange records';
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
                        <h3 class="card-header text-start">Import Competitor Interchange</h3>
                        <div class="card-body">
                            
                            <?php echo '<div>'.$finalresultmessage.'</div>'; ?>

                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste 7 columns of tab-delimited data (no header row):</h6>
                                    <p>Partnumber, <a href="./competitiveBrandBrowser.php">Competitor BrandID</a>, Competitor partnumber, Competitor Quantity, UoM, Public Notes, Internal notes</p>
                                    <p>Note: if Competitor partnumber contains comas, it will be split by coma into multiple records on the fly</p>
                                </div>
                                    
                                <textarea style="width:100%;height:200px;" name="input"></textarea>
                                
                                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                            </form>
                            
                            <?php
                                foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}
                                foreach($importresults as $importresult){echo '<div class="alert alert-success" role="alert">'.$importresult.'</div>';}
                            ?>

                            
                            
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