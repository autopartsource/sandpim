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

$errors=array();
$results='';
$importcount=0;
$failedcount=0;
$recordnumber=0;


if (isset($_POST['input']))
{
    $input = $_POST['input'];
    $records = explode("\r\n", $_POST['input']);
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
                                $importcount++;
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
                                $importcount++;
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
                            $importcount++;
                            break;

                        case 'replacedby':

                            $replacedby=trim(strtoupper($attributevalue));

                            if($pim->validPart($replacedby))
                            {                                        
                                $pim->setPartReplacedby($partnumber, $replacedby, true);
                                $newoid=$pim->getOIDofPart($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'replacedby updated to ['.$attributevalue.'] by mass import', $newoid);
                                $importcount++;
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
                                $importcount++;
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
                            $importcount++;
                            break;
                            

                        default: 
                            // attribute name is not reserved, and not a PAdb numeric ID
                            $pim->writePartAttribute($partnumber, 0, $attributename, $attributevalue, $uom);
                            $newoid=$pim->getOIDofPart($partnumber);
                            $pim->logPartEvent($partnumber, $_SESSION['userid'], 'Attribute ['.$attributename.'='.$attributevalue.' '.$uom.'] written by mass import', $newoid);
                            break;
                    }
                }
                else
                {// this is a PAdb numeric ID
                    $pim->writePartAttribute($partnumber, $PAID, '', $attributevalue, $uom);
                    $newoid=$pim->getOIDofPart($partnumber);
                    $pim->logPartEvent($partnumber, $_SESSION['userid'], 'PAdb Attribute ['.$PAID.'='.$attributevalue.' '.$uom.'] written by mass import', $newoid);
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
                                
                                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                            </form>
                            
                            <?php if($importcount>0){ echo '<div>Imported '.$importcount.' records.</div>';}?>
                            
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