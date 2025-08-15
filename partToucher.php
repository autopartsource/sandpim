<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'utilities';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'partToucher.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$errors=array();
$importcount=0;
$userid=intval($_SESSION['userid']);


if (isset($_POST['input']))
{
    $input = $_POST['input'];
    $records = explode("\r\n", $_POST['input']);
    foreach ($records as $record)
    {
        $fields = explode("\t", $record);
        $recordnumber++;
        if(count($fields) == 1 && trim($fields[0])==''){continue;} // ignore blank lines
        
        if(count($fields) == 2)
        { // partnumber, note
            $partnumber=trim(strtoupper($fields[0]));
            $description=trim($fields[1]);
            if(strlen($description)>0)
            {
                $part=$pim->getPart($partnumber);
                if($part)
                {
                    $newoid=$pim->updatePartOID($partnumber);
                    $pim->logPartEvent($partnumber, $userid, $description, $newoid);
                    $importcount++;
                }
                else
                {// bad partnumber
                    $errors[]='Invalid partnumber on line '.$recordnumber;
                    $failedcount++;  
                }
            }
            else
            {// note is blank
                $errors[]='Note field cannot be blank. Line '.$recordnumber;
                $failedcount++;
            }
        }
        else
        {// wrong field count
            $errors[]='Wrong field count on line '.$recordnumber.'. Input data must have 2 tab-delimited columns';
            $failedcount++;
        }
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
                        <h3 class="card-header text-start">Touch parts by list</h3>

                        <div class="card-body">
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste two tab-delimited columns (no header row):</h6>
                                    <p>partnumber, historical note</p>
                                </div>
                                
                                <textarea name="input" style="width:100%;height:200px;"></textarea>
                                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                            </form>
                            
                            <?php if($importcount>0){ echo '<div>Touched '.$importcount.' parts.</div>';}?>
                            
                            <?php foreach($errors as $error){ echo '<div>'.$error.'</div>'; }?>
                                         
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