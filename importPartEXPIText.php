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
 $logs->logSystemEvent('accesscontrol',0, 'importPartEXPIText.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb=new pcdb();
$errors=array();
$results=array();
$importcount=0;
$failedcount=0;
$recordnumber=0;


if (isset($_POST['input']))
{
    $allexpicodes=$pcdb->getAllEXPIcodes(); foreach($allexpicodes as $expi){$keyedValidEXPIs[$expi['code']]=$expi['description'];}
//    print_r($keyedValidEXPIs);
    $input = $_POST['input'];
    $records = explode("\r\n", $_POST['input']);
    
    if($_POST['mode']=='clearadd')
    {
        //compile a list of valid items to clear before importing 
        $validparts=array();
        foreach ($records as $record)
        {
            $fields = explode("\t", $record);
            $partnumber = trim(strtoupper($fields[0]));
            if(count($fields) == 4 && $pim->validPart($partnumber)){$validparts[]=$partnumber;}
        }
        foreach($validparts as $validpart)
        {
            $pim->deletetEXPIsByPartnumber($validpart);
            $pim->logPartEvent($partnumber, $_SESSION['userid'], 'EXPI codes cleared by mass import in clearadd mode', '');
        }
    }
    
    
    foreach ($records as $record)
    {
        $fields = explode("\t", $record);
        $recordnumber++;
        
        if((count($fields) == 1) && trim($fields[0])==''){continue;} // completely ignore empty lines

        if(count($fields) == 4)
        { // partnumber,EXPIcode,value,languagecode
            $partnumber = trim(strtoupper($fields[0]));
            if (strlen($partnumber) > 0 && strlen($partnumber) <= 30)
            { // partnumber is valid

                $EXPIcode = trim(strtoupper($fields[1]));
                $value = trim($fields[2]);
                $languagecode=trim($fields[3]);
                
                switch ($_POST['mode'])
                {
                    case 'test':
                        if($pim->validPart($partnumber))
                        {// good partnumber
                            
                        }
                        else
                        {// bad partnumber
                            $errors[]='Partnumber '.$partnumber.' on line '.$recordnumber.' is not valid';
                            $failedcount++;
                        }
                        
                        if(!array_key_exists($EXPIcode, $keyedValidEXPIs))
                        {// bad expi code
                            $errors[]=$EXPIcode.' on line '.$recordnumber.' is not valid';
                            $failedcount++;
                        }
                        else
                        {// expi code is valid - checck value
                            
                          //  if(!array_key_exists($value, $keyedValidEXPIs[$EXPIcode]))
                          //  {// bad value for given code
                          //      $errors[]='Value '.$value.' is not a valid option for EXPI code '.$EXPIcode.' on line '.$recordnumber;
                          //      $failedcount++;
                          //  }
                        }
                        
                        break;
                    
                    case 'clearadd':
                        if($pim->validPart($partnumber))
                        {// partnumber is valid
                            if(array_key_exists($EXPIcode, $keyedValidEXPIs))
                            {// code is valid 
                                $pim->writePartEXPI($partnumber, $EXPIcode, $value, $languagecode);
                                $newoid=$pim->updatePartOID($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'EXPI ['.$EXPIcode.'='.$value.'] added by mass import in clearadd mode', $newoid);
                                $results[]=$partnumber.':'.$EXPIcode.'='.$value.' ('.$languagecode.')';
                                $importcount++;
                            }
                            else
                            {// bad expi code
                                $errors[]=$EXPIcode.' on line '.$recordnumber.' is not valid';
                                $failedcount++;
                            }
                        }
                        else
                        {// bad partnumber
                            $errors[]='Partnumber '.$partnumber.' on line '.$recordnumber.' is not valid';                            
                            $failedcount++;
                        }
                        
                        
                        break;
                    
                    case 'addupdate':
                        //see if code/language entry exists for this incoming recors

                        if($pim->validPart($partnumber))
                        {// partnumber is valid
                            if(array_key_exists($EXPIcode, $keyedValidEXPIs))
                            {// code is valid 
                                if($existingvalue=$pim->partEXPIvalue($partnumber,$EXPIcode,$languagecode,false))
                                {// exsiting record - update it

                                    if($existingvalue!=$value)
                                    {// updated value is same actually different
                                        $pim->updatePartEXPI($partnumber,$EXPIcode,$languagecode,$value);
                                        $newoid=$pim->updatePartOID($partnumber);
                                        $pim->logPartEvent($partnumber, $_SESSION['userid'], 'EXPI ['.$EXPIcode.'] updated from ['.$existingvalue.'] to ['.$value.'] by mass import in addupdate mode', $newoid);
                                        $importcount++;
                                    }
                                }
                                else
                                {// no existing record - add it
                                    $pim->writePartEXPI($partnumber, $EXPIcode, $value, $languagecode);
                                    $newoid=$pim->updatePartOID($partnumber);
                                    $pim->logPartEvent($partnumber, $_SESSION['userid'], 'EXPI ['.$EXPIcode.'='.$value.'] added by mass import in addupdate mode', $newoid);
                                    $results[]=$partnumber.':'.$EXPIcode.'='.$value.' ('.$languagecode.')';
                                    $importcount++;
                                }
                            }
                            else
                            {// bad expi code
                                $errors[]=$EXPIcode.' on line '.$recordnumber.' is not valid';
                                $failedcount++;
                            }                            
                        }
                        else
                        {// bad partnumber
                            $errors[]='Partnumber '.$partnumber.' on line '.$recordnumber.' is not valid';
                            $failedcount++;
                        }
                        
                        break;

                    case 'add':

                        if($pim->validPart($partnumber))
                        {// partnumber is valid
                            if(array_key_exists($EXPIcode, $keyedValidEXPIs))
                            {// code is valid 
                                $pim->writePartEXPI($partnumber, $EXPIcode, $value, $languagecode);
                                $newoid=$pim->updatePartOID($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'EXPI ['.$EXPIcode.'='.$value.'] added by mass import in add mode', $newoid);
                                $results[]=$partnumber.':'.$EXPIcode.'='.$value.' ('.$languagecode.')';
                                $importcount++;
                            }
                            else
                            {// bad expi code
                                $errors[]=$EXPIcode.' on line '.$recordnumber.' is not valid';
                                $failedcount++;
                            }                            
                        }
                        else
                        {// bad partnumber
                            $errors[]='Partnumber '.$partnumber.' on line '.$recordnumber.' is not valid';
                            $failedcount++;
                        }

                        break;
                    
                    
                    default: break;                    
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
            $errors[]='Wrong field count on line '.$recordnumber.'. This record was ignored. Input data must have exactly 4 tab-delimited columns';
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
                        <h3 class="card-header text-start">EXPI Import</h3>

                        <div class="card-body">
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste 4 tab-delimited columns (no header row):</h6>
                                    <p>Partnumber, EXPIcode, value, language code</p>
                                </div>
                                <textarea name="input" style="width:100%;height:200px;"></textarea>
                                Mode <select name="mode"><option value="add">Add</option><option value="addupdate" selected>Add/Update</option><option value="clearadd">Clear/Add</option><option value="test">Test</option></select>
                                <div style="padding:5px;"><input name="submit" type="submit" value="Import"/></div>
                                <div style="text-align: left;">Operation Modes<br/>
                                    <div style="padding:10px;text-align: left;">
                                        <div><strong>Add</strong> - Records will be added with no consideration of what already exists</div>
                                        <div><strong>Add/Update</strong> - Records will be added and existing values (by EXPIcode and Language) will be updated</div>
                                        <div><strong>Clear Add</strong> - All EXPI Records will be cleared for the Partnumbers in the dataset before import</div>
                                        <div><strong>Test</strong> - Report on validity of EXPI codes (no actual import)</div>
                                    </div>
                                </div>
                            </form>
                            
                            <?php if($importcount>0){ echo '<div  class="alert alert-success" role="alert">Imported '.$importcount.' records.</div>';}?>
                            <?php foreach($errors as $error){echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';}?>
                            <?php foreach($results as $result){echo '<div class="alert alert-success" role="alert">'.$result.'</div>';}?>
                                                        
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