<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
$navCategory = 'import';

$pim=new pim;
$logs = new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'importExperianVIOtext.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$errors=array();
$results=array();
$vios=array();
$recordnumber=0;

if(isset($_POST['input'])) 
{
 $clearfirst=false; if(isset($_POST['clearfirst'])){$clearfirst=true;}
 $input = $_POST['input'];
 $records = explode("\r\n", $_POST['input']);
 foreach ($records as $record) 
 {
  $recordnumber++;
  $fields = explode("\t", $record);
  
  if(count($fields)==1 && $fields[0]==''){continue;}
  
  if (count($fields) == 20) 
  {
   $yearquarter=trim($fields[0]);
   $geography=trim($fields[1]);
   $vehicleid=intval(trim($fields[2]));
   $basevehicleid=intval(trim($fields[3]));
   $yearid=intval(trim($fields[4]));
   $makeid=intval(trim($fields[5]));
   $modelid=intval(trim($fields[6]));
   $submodelid=intval(trim($fields[7]));
   $bodytypeid=intval(trim($fields[8]));
   $bodynumdoorsid=intval(trim($fields[9]));
   $drivetypeid=intval(trim($fields[10]));
   $fueltypeid=intval(trim($fields[11]));
   $enginebaseid=intval(trim($fields[12]));
   $enginevinid=intval(trim($fields[13]));
   $fueldeliverysubtypeid=intval(trim($fields[14]));
   $transcontroltypeid=intval(trim($fields[15]));
   $transnumspeedid=intval(trim($fields[16]));
   $aspirationid=intval(trim($fields[17]));
   $vehicletypeid=intval(trim($fields[18]));
   $vehiclecount=intval(trim($fields[19]));   
   $vios[]=array('yearquarter'=>$yearquarter,'geography'=>$geography,'vehicleid'=>$vehicleid,'basevehicleid'=>$basevehicleid,'yearid'=>$yearid,'makeid'=>$makeid,'modelid'=>$modelid,'submodelid'=>$submodelid,'bodytypeid'=>$bodytypeid,'bodynumdoorsid'=>$bodynumdoorsid,'drivetypeid'=>$drivetypeid,'fueltypeid'=>$fueltypeid,'enginebaseid'=>$enginebaseid,'enginevinid'=>$enginevinid,'fueldeliverysubtypeid'=>$fueldeliverysubtypeid,'transcontroltypeid'=>$transcontroltypeid,'transnumspeedid'=>$transnumspeedid,'aspirationid'=>$aspirationid,'vehicletypeid'=>$vehicletypeid,'vehiclecount'=>$vehiclecount);
  }
  else
  {// field count is wrong
   $errors[]='Field count on line '.$recordnumber.' was wrong (expected exactly 20 tab-delimited columns)';
  }
 }
 
 $pim->addExperianVIOrecords($vios);
 $results[]='Imported '.count($vios).' VIO records on top of existing data';     
 $logs->logSystemEvent('import',$_SESSION['userid'], 'Imported '.count($vios).' VIO records on top of existing data');
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
                        <h3 class="card-header text-start">Import ACES-mapped Experian VIO data</h3>

                        <div class="card-body">
                            <?php foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}?>
                            <?php foreach($results as $result){echo '<div class="alert alert-success" role="alert">'.$result.'</div>';}?>
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste tab-delimited data (no header row):</h6>
                                    <p>yearquarter, geography, vehicleid, basevehicleid, yearid, makeid, modelid, submodelid, bodytypeid, bodynumdoorsid, drivetypeid, fueltypeid, enginebaseid, enginevinid, fueldeliverysubtypeid, transcontroltypeid, transnumspeedid, aspirationid, vehicletypeid, vehiclecount</p>
                                    <p>Example yearquarter: <strong>2022Q4</strong></p>
                                    <p>Example geography: <strong>US</strong></p>
                                </div>
                                <textarea name="input" style="width:100%;" rows="15"></textarea>
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