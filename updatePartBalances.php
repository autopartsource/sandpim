<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
$navCategory = 'import/export';

$pim = new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol', 0, 'updatePartBalances - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


  
if (isset($_POST['input'])) 
{
 $input = $_POST['input'];
 $records = explode("\r\n", $_POST['input']);
 foreach ($records as $record) 
 {
  $recordnumber++;
  $fields = explode("\t", $record);
  
  if(count($fields)==1 && $fields[0]==''){continue;}
    
  if (count($fields) == 3) 
  {
   $partnumber = trim(strtoupper($fields[0]));
   $qoh= floatval(trim(strtoupper($fields[1])));
   $amd= floatval(trim(strtoupper($fields[2])));
   
   
   if (strlen($partnumber) <= 20 && strlen($partnumber) > 0) 
   { // partnumber is within valid length
    if($pim->validPart($partnumber)) 
    {
     $pim->updatePartBalance($partnumber, $qoh, $amd);

     $pim->logPartEvent($partnumber,$_SESSION['userid'],'balance updated (qoh:'.$qoh.', amd:'.$amd.') via manual import','');
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
   $errors[]='Field count was wrong (expected exactly 3 tab-delimited columns)';
  }
 }
 $finalresultmessage='Imported '.$importcount.' balance records';
 if($invalidcount>0){$finalresultmessage.='. '.$invalidcount.' records were ignored because of invalid partnumbers.';};
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
                        <h3 class="card-header text-start">Import Part Balance Data</h3>
                        <div class="card-body">
                            <?php foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}?>
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste tab-delimited data:</h6>
                                    <p>Partnumber, QoH, AMD</p>
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