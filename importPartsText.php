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
 $logs->logSystemEvent('accesscontrol',0, 'importPartsText.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
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
$importcount=0;
$failedcount=0;
$recordnumber=0;
$partcategory=0;

if (isset($_POST['input']))
{
    $partcategory=intval($_POST['partcategory']);
    $input = $_POST['input'];
    $records = explode("\r\n", $_POST['input']);
    foreach ($records as $record)
    {
        $fields = explode("\t", $record);
        $recordnumber++;
        if(count($fields) == 1 && trim($fields[0])==''){continue;} // ignore blank lines
        
        if(count($fields) == 2)
        { // Partnumber, description text, description code, language code, sequence
            $partnumber = trim(strtoupper($fields[0]));
            if(!$pim->validPart($partnumber))
            { // partnumber does not already exist
                $parttypeid=intval(trim($fields[1]));
                if($pcdb->validPartType($parttypeid))
                {
                    $pim->createPart($partnumber, $partcategory, $parttypeid);
                    $pim->logPartEvent($partnumber, $_SESSION['userid'], 'Part created by mass import', '');
                    $importcount++;
                }
                else
                {// parttype id is not valid
                    $errors[]='Invalid parttype id ('.$parttypeid.') on line '.$recordnumber;                    
                    $failedcount++;
                }
            }
            else
            {// partnumber already exists
                $errors[]='Partnumber ['.$partnumber.'] on line '.$recordnumber.' already exists';
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

$partcategories = $pim->getPartCategories();

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
                        <h3 class="card-header text-start">Import Parts</h3>

                        <div class="card-body">
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste two tab-delimited columns (no header row):</h6>
                                    <p>Partnumber, parttypeid</p>
                                </div>
                                
                                <textarea name="input" style="width:100%;height:200px;"></textarea>
                                <div><select name="partcategory"><?php foreach ($partcategories as $category) { ?> <option value="<?php echo $category['id']; ?>" <?php if($category['id']==$partcategory){echo 'selected';}?>><?php echo $category['name']; ?></option><?php } ?></select></div>
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