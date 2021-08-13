<?php
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
$navCategory = 'import';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$pcdb=new pcdb();

if (isset($_POST['input'])) {
    $input = $_POST['input'];
    $records = explode("\r\n", $_POST['input']);
    foreach ($records as $record) {
        $fields = explode("\t", $record);
        if (count($fields) == 3 || count($fields) == 4) { // partnumber,attributename,attributevalue[,unitOfMeasure]
            $partnumber = trim(strtoupper($fields[0]));
            if (strlen($partnumber) <= 20 && strlen($partnumber) > 0) { // partnumber is within valid length
                if ($pim->validPart($partnumber)) {

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
                                // maybe should validate the checkdigit and lengthere?
                                $pim->setPartGTIN($partnumber, $attributevalue, true);
                                $newoid=$pim->getOIDofPart($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'GTIN updated to ['.$attributevalue.'] by mass import', $newoid);
                                break;

                            case 'parttypeid':
                                $parttypename=$pcdb->parttypeName(intval($attributevalue));
                                if($parttypename!='not found')
                                {
                                    $pim->setPartParttype($partnumber, intval($attributevalue), true);
                                    $newoid=$pim->getOIDofPart($partnumber);
                                    $pim->logPartEvent($partnumber, $_SESSION['userid'], 'part type updated to ['.$parttypename.'] by mass import', $newoid);
                                }
                                break;

                            case 'lifecyclestatus':
                                $pim->setPartLifecyclestatus($partnumber, $attributevalue, true);
                                $newoid=$pim->getOIDofPart($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'lifecycle status updated to ['.$attributevalue.'] by mass import', $newoid);
                                break;
     
                            case 'replacedby':
                                
                                $replacedby=trim(strtoupper($attributevalue));
                                
                                if($pim->validPart($replacedby))
                                {                                        
                                    $pim->setPartReplacedby($partnumber, $replacedby, true);
                                    $newoid=$pim->getOIDofPart($partnumber);
                                    $pim->logPartEvent($partnumber, $_SESSION['userid'], 'replacedby updated to ['.$attributevalue.'] by mass import', $newoid);
                                }
                                break;
                            
                            
                            default: 
                                // attribute name is not reserved, and not a PAdb numeric ID
                                $pim->writePartAttribute($partnumber, 0, $attributename, $attributevalue, $uom);
                                $newoid=$pim->getOIDofPart($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'Attribute ['.$attributename.'='.$attributevalue.' '.$uom.'] writted by mass import', $newoid);
                                break;
                        }
                    }
                    else
                    {// this is a PAdb numeric ID
                        $pim->writePartAttribute($partnumber, $PAID, '', $attributevalue, $uom);
                        $newoid=$pim->getOIDofPart($partnumber);
                        $pim->logPartEvent($partnumber, $_SESSION['userid'], 'PAdb Attribute ['.$PAID.'='.$attributevalue.' '.$uom.'] writted by mass import', $newoid);
                    }
                }
            }
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
                                    <h6 class="alert-heading">Paste three or four tab delimited columns:</h6>
                                    <p>Partnumber, attributename or PAdbID, value [, UoM]</p>
                                </div>
                                    <hr>
                                    <p>Part numbers are validated. If the second column is a number, it is assumed to be a PAdb ID.
                                    <br>Non-numeric values are assumed to be user-defined attribute names.
                                    <br>Attribute names GTIN, parttypeid, lifecyclestatus, replacedby are special cases that will apply to the part if used.</p>
                                
                                
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