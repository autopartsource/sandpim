<?php
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/logsClass.php');

$navCategory = 'import';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importPartDescriptionText.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb=new pcdb();

$errors=array();
$results='';
$importcount=0;
$failedcount=0;
$recordnumber=0;
$showvalidcodes=false;

if (isset($_POST['input']))
{
    $input = $_POST['input'];
    $records = explode("\r\n", $_POST['input']);
    foreach ($records as $record)
    {
        $fields = explode("\t", $record);
        $recordnumber++;
        
        if(count($fields) == 5)
        { // Partnumber, description text, description code, language code, sequence
            $partnumber = trim(strtoupper($fields[0]));
            if (strlen($partnumber) <= 20 && strlen($partnumber) > 0 && $pim->validPart($partnumber))
            { // partnumber is valid

                $descriptiontext = trim($fields[1]);
                $descriptioncode = trim($fields[2]);
                if($pcdb->validPartDecriptionCode($descriptioncode))
                {
                    $languagecode = trim($fields[3]);
                    $sequence = intval(trim($fields[4]));
                    $pim->addPartDescription($partnumber, $descriptiontext, $descriptioncode, $sequence, $languagecode);
                    $newoid=$pim->updatePartOID($partnumber);
                    $pim->logPartEvent($partnumber, $_SESSION['userid'], 'Description ['.$descriptiontext.'] written by mass import', $newoid);
                    $importcount++;
                }
                else
                {// description code is not valid (according the the currently loaded PCdb)
                    
                    $errors[]='Invalid description code ['.$descriptioncode.'] on line '.$recordnumber;
                    $failedcount++;
                    $showvalidcodes=true;
                }
            }
            else
            {// partnumber is not valid
                $errors[]='Invalid partnumber ['.$partnumber.'] on line '.$recordnumber;
                $failedcount++;
            }
        }
        else
        {// wrong field count
            $errors[]='Wrong field count on line '.$recordnumber.'. Input data must have 5 tab-delimited data';
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
                        <h3 class="card-header text-start">Import Part Descriptions</h3>

                        <div class="card-body">
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste five tab-delimited columns (no header row):</h6>
                                    <p>Partnumber, description text, description code (SHO, DES, etc.), language code (EN, ES, etc.), sequence</p>
                                </div>
                                    <hr>                               
                                
                                <textarea name="input" style="width:100%;height:200px;"></textarea>
                                
                                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                            </form>
                            
                            <?php if($importcount>0){ echo '<div>Imported '.$importcount.' records.</div>';}?>
                            
                            <?php foreach($errors as $error){ echo '<div>'.$error.'</div>'; }?>
                                         
                            <?php
                            if($showvalidcodes)
                            {
                                echo '<div>Valid Desctiption codes: <table><tr><th>Code</th><th>Code Meaning</th><th>Format/Max length</th></tr>';
                                $validdescriptioncodes=$pcdb->getPartDescriptionTypeCodes(); //    $codes[]=array('code'=>$row['CodeValue'],'description'=>$row['CodeDescription']);
                                foreach($validdescriptioncodes as $validdescriptioncode)
                                {
                                    echo '<tr><td>'.$validdescriptioncode['code'].'</td><td>'.$validdescriptioncode['description'].'</td><td>'.$validdescriptioncode['format'].'</td></tr>';
                                }
                            }
                            echo '</table></div>'
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