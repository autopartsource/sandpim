<?php
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/padbClass.php');
include_once('./class/logsClass.php');

$navCategory = 'import';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importPartAttributeText.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb=new pcdb();
$padb=new padb();

$errors=array();
$results=array();
$writtencount=0;
$updatedcount=0;
$deletedcount=0;
$testedcount=0;
$failedcount=0;
$recordnumber=0;


if (isset($_POST['input']))
{
    $input = $_POST['input'];
    $records = explode("\r\n", $_POST['input']);
    
    if($_POST['mode']=='clearadd')
    { //compile a list of valid items to clear before importing 
        $validparts=array();
        foreach ($records as $record)
        {
            $fields = explode("\t", $record);
            $partnumber = trim(strtoupper($fields[0]));
            if((count($fields) == 3 || count($fields) == 4) && $pim->validPart($partnumber)){$validparts[]=$partnumber;}
        }
        foreach($validparts as $validpart)
        {
            $pim->deletePartAttributesByPartnumber($validpart);
            $pim->logPartEvent($partnumber, $_SESSION['userid'], 'PAdb (and user-defined) attributes cleared by mass import in clearadd mode', '');
        }
    }        

        
    foreach ($records as $record)
    {
        $fields = explode("\t", $record);
        $recordnumber++;
        
        if(count($fields) == 3 || count($fields) == 4)
        { // partnumber,attributename,attributevalue[,unitOfMeasure]
            $partnumber = trim(strtoupper($fields[0]));
            if (strlen($partnumber) <= 20 && strlen($partnumber) > 0 && $pim->validPart($partnumber))
            { // partnumber is valid

                $attributename = trim($fields[1]);
                $attributevalue = trim($fields[2]);
                $PAID=intval(trim($fields[1]));
                $uom = '';
                if (count($fields) == 4 && trim($fields[3])!='') {
                    $uom = trim($fields[3]);
                }

                if($PAID==0)
                {// this is a user-defined name string

                    // look for reserved attribute names (GTIN,parttypeid,lifecyclestatus)
                    // these will update the part table (not the part_attribute table)

                    switch(trim($fields[1]))
                    {
                        case '':
                            // blank name. Do nothing
                            break;

                        case 'GTIN':
                            if($pim->isValidBarcode($attributevalue))
                            {// valid check digit
                                $pim->setPartGTIN($partnumber, $attributevalue, true);
                                $newoid=$pim->getOIDofPart($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'GTIN updated to ['.$attributevalue.'] by mass import', $newoid);
                                $updatedcount++;
                            }
                            else
                            {
                                $errors[]= 'Invalid GTIN ['.$attributevalue.'] for partnumber ['.$partnumber.'] on line '.$recordnumber;
                                $failedcount++;
                            }
                            break;

                        case 'parttypeid':
                            $parttypename=$pcdb->parttypeName(intval($attributevalue));
                            if($parttypename!='not found')
                            {
                                $pim->setPartParttype($partnumber, intval($attributevalue), true);
                                $newoid=$pim->getOIDofPart($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'part type updated to ['.$parttypename.'] by mass import', $newoid);
                                $updatedcount++;
                            }
                            else
                            {// parttype was not valid
                                $errors[]= 'Invalid parttypeid ['.$attributevalue.'] for partnumber ['.$partnumber.'] on line '.$recordnumber;
                                $failedcount++;
                            }
                            break;

                        case 'lifecyclestatus':
                            $pim->setPartLifecyclestatus($partnumber, $attributevalue, true);
                            $newoid=$pim->getOIDofPart($partnumber);
                            $pim->logPartEvent($partnumber, $_SESSION['userid'], 'lifecycle status updated to ['.$attributevalue.'] by mass import', $newoid);
                            $updatedcount++;
                            break;

                        case 'replacedby':

                            $replacedby=trim(strtoupper($attributevalue));

                            if($pim->validPart($replacedby))
                            {                                        
                                $pim->setPartReplacedby($partnumber, $replacedby, true);
                                $newoid=$pim->getOIDofPart($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'replacedby updated to ['.$attributevalue.'] by mass import', $newoid);
                                $updatedcount++;
                            }
                            else
                            {// replacement part is not valid
                                $errors[]= $partnumber.': replacedby ['.$replacedby.'] is not a valid partnumber on line '.$recordnumber;
                                $failedcount++;
                            }
                            break;

                        case 'basepart':

                            $basepart=trim(strtoupper($attributevalue));

                            if($pim->validPart($basepart))
                            {                                        
                                //$pim->setPartReplacedby($partnumber, $replacedby, true);
                                $pim->setPartBasepart($partnumber, $basepart, true);
                                $newoid=$pim->getOIDofPart($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'basepart updated to ['.$attributevalue.'] by mass import', $newoid);
                                $updatedcount++;
                            }
                            else
                            {// replacement part is not valid
                                $errors[]= $partnumber.': basepart ['.$basepart.'] is not a valid partnumber on line '.$recordnumber;
                                $failedcount++;
                            }
                            break;

                        case 'firststockeddate':
                            $pim->setPartFirststockedDate($partnumber, $attributevalue,true);
                            $newoid=$pim->getOIDofPart($partnumber);
                            $pim->logPartEvent($partnumber, $_SESSION['userid'], 'first stock date set to ['.$attributevalue.'] by mass import', $newoid);
                            $updatedcount++;
                            break;
                            

                        default: 
                            // attribute name is not reserved, and not a PAdb numeric ID
                            // it is assumed to be a user-defined attribute (non-padb)
                            switch ($_POST['mode'])
                            {                                            
                                case 'test':
                                    //not really much to test here
                                    $testedcount++;
                                    break;

                                case 'add':
                                    //Records will be added with no consideration of what already exists
                                    $pim->writePartAttribute($partnumber, 0, $attributename, $attributevalue, $uom);
                                    $newoid=$pim->getOIDofPart($partnumber);
                                    $pim->logPartEvent($partnumber, $_SESSION['userid'], 'User-defined attribute ['.$attributename.'='.$attributevalue.' '.$uom.'] written by mass import in add mode', $newoid);
                                    $writtencount++;
                                    break;

                                case 'addupdate':
                                    //Existing values (by PAdbID and UoM) will be updated, non-existent records will be added.

                                    if($pim->getPartAttribute($partnumber, 0, $attributename, $uom))
                                    {// update existing                                
                                        $pim->updatePartAttribute($partnumber, 0, $attributename, $attributevalue, $uom);
                                        $newoid=$pim->getOIDofPart($partnumber);
                                        $pim->logPartEvent($partnumber, $_SESSION['userid'], 'User-defined attribute ['.$attributename.'='.$attributevalue.' '.$uom.'] updated by mass import in addupdate mode', $newoid);
                                        $writtencount++;
                                    }
                                    else
                                    {// write new
                                        $pim->writePartAttribute($partnumber, 0, $attributename, $attributevalue, $uom);
                                        $newoid=$pim->getOIDofPart($partnumber);
                                        $pim->logPartEvent($partnumber, $_SESSION['userid'], 'User-defined attribute ['.$attributename.'='.$attributevalue.' '.$uom.'] written by mass import in addupdate mode', $newoid);                            
                                        $writtencount++;
                                    }

                                    break;

                                case 'clearadd':
                                    //All PAdb and user-defined attributes were cleared (above) for the Partnumbers in the dataset. 
                                    //Existing reserved attributes (GTIN, parttypeid, lifecyclestatus, replacedby, basepart and firststockeddate) are not affected.
                                    $pim->writePartAttribute($partnumber, 0, $attributename, $attributevalue, $uom);
                                    $newoid=$pim->getOIDofPart($partnumber);
                                    $pim->logPartEvent($partnumber, $_SESSION['userid'], 'User-defined attribute ['.$attributename.'='.$attributevalue.' '.$uom.'] written by mass import in clearadd mode', $newoid);      
                                    $writtencount++;
                                    break;

                                default: break;
                            }
                            
                            break;
                    }
                }
                else
                {// this is a PAdb numeric ID
                                        
                    switch ($_POST['mode'])
                    {                                            
                        case 'test':
                            //Report on validity of PAdb codes (no actual import)
                            
                            $part=$pim->getPart($partnumber);
                            $parttypeid=$part['parttypeid'];
                            $validattributes=$padb->getAttributesForParttype($parttypeid);
                            
                            $found=false;
                            foreach($validattributes as $validattribute)
                            {
                                if($PAID==$validattribute['PAID']){$found=true; break;}    
                            }
                            if($found)
                            {// valid PAdb for part type  
                                $results[]='PAdbID '.$PAID.' ('.$padb->PAIDname($PAID).') it valid for parttype '.$parttypeid.' on partnumber '.$partnumber;
                            }
                            else
                            {// not a valid PAdb id for parttype of given part
                                $errors[]='PAdbID '.$PAID.' ('.$padb->PAIDname($PAID).') it not valid for parttype '.$parttypeid.' on partnumber '.$partnumber;
                            }
                            $testedcount++;
                            break;

                        case 'add':
                            //Records will be added with no consideration of what already exists
                            $pim->writePartAttribute($partnumber, $PAID, '', $attributevalue, $uom);
                            $newoid=$pim->getOIDofPart($partnumber);
                            $pim->logPartEvent($partnumber, $_SESSION['userid'], 'PAdb Attribute ['.$PAID.'='.$attributevalue.' '.$uom.'] written by mass import in add mode', $newoid);
                            $writtencount++;
                            break;

                        case 'addupdate':
                            //Existing values (by PAdbID and UoM) will be updated, non-existent records will be added.

                            if($pim->getPartAttribute($partnumber, $PAID, '', $uom))
                            {// update existing (or deleted it)
                                if($attributevalue=='DELETE')
                                {
                                    $pim->deletePartAttributesByPartnumberPAIDuom($partnumber, $PAID, '', $uom);
                                    $newoid=$pim->getOIDofPart($partnumber);
                                    $pim->logPartEvent($partnumber, $_SESSION['userid'], 'PAdb Attribute ['.$PAID.' ('.$uom.')] deleted by mass import in addupdate mode', $newoid);
                                    $deletedcount++;
                                }
                                else
                                {// a real value was given to update to
                                    $pim->updatePartAttribute($partnumber, $PAID, '', $attributevalue, $uom);
                                    $newoid=$pim->getOIDofPart($partnumber);
                                    $pim->logPartEvent($partnumber, $_SESSION['userid'], 'PAdb Attribute ['.$PAID.'='.$attributevalue.' '.$uom.'] updated by mass import in addupdate mode', $newoid);
                                    $updatedcount++;
                                }
                            }
                            else
                            {// write new
                                $pim->writePartAttribute($partnumber, $PAID, '', $attributevalue, $uom);
                                $newoid=$pim->getOIDofPart($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'PAdb Attribute ['.$PAID.'='.$attributevalue.' '.$uom.'] written by mass import in addupdate mode', $newoid);                            
                                $writtencount++;
                            }
                            
                            break;
                        
                        case 'clearadd':
                            //All PAdb and user-defined attributes were cleared (above) for the Partnumbers in the dataset. 
                            //Existing reserved attributes (GTIN, parttypeid, lifecyclestatus, replacedby, basepart and firststockeddate) are not affected.
                            $pim->writePartAttribute($partnumber, $PAID, '', $attributevalue, $uom);
                            $newoid=$pim->getOIDofPart($partnumber);
                            $pim->logPartEvent($partnumber, $_SESSION['userid'], 'PAdb Attribute ['.$PAID.'='.$attributevalue.' '.$uom.'] written by mass import in clearadd mode', $newoid);                            
                            $writtencount++;
                            break;
                                                
                        default: break;
                    }
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
            $errors[]='Wrong field count on line '.$recordnumber.'. Input data must have 3 or 4 tab-delimited data';
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
                        <h3 class="card-header text-start">Import Part Attributes</h3>

                        <div class="card-body">
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste three or four tab-delimited columns:</h6>
                                    <p>Partnumber, attributename or PAdbID, value [, UoM]</p>
                                </div>
                                    <hr>
                                    <p>Part numbers are validated. If the second column is a number, it is assumed to be a PAdb ID.
                                    <br>Non-numeric values are assumed to be user-defined attribute names.
                                    <br>Attribute names GTIN, parttypeid, lifecyclestatus, replacedby, basepart, firststockeddate are special cases that will apply to the part if used.</p>
                                
                                
                                <textarea name="input" style="width:100%;height:200px;"></textarea>
                                
                               
                                <div style="text-align: left;">Mode <select name="mode"><option value="add">Add</option><option value="addupdate">Add/Update</option><option value="clearadd">Clear/Add</option><option value="test" selected>Test</option></select> <input name="submit" type="submit" value="Import"/>
                                    <div style="padding:10px;text-align: left;">
                                        <div><strong>Add</strong> - Records will be added with no consideration of what already exists</div>
                                        <div><strong>Add/Update</strong> - Existing values (by PAdbID and UoM) will be updated, non-existent records will be added..</div>
                                        <div><strong>Clear Add</strong> - All PAdb and user-defined attributes will be cleared for the Partnumbers in the dataset before import.</div>
                                        <div><strong>Test</strong> - Report on validity of PAdb codes (no actual import)</div>
                                    </div>
                                </div>
                            </form>
                            
                            <?php if(isset($_POST['input'])){ echo '<div class="alert alert-info" role="alert">Wrote: '.$writtencount.'<br/>Updated: '.$updatedcount.'<br/>Deleted: '.$deletedcount.'<br/>Tested: '.$testedcount.'</div>';}?>
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