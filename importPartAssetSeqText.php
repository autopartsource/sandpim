<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$navCategory = 'import';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'importPartAssetSeqText.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$asset=new asset();

$errors=array();
$results='';
$importcount=0; $updatecount=0; $deletecount=0;
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
        
        if(count($fields) == 3)
        { // partnumber, assetid, sequence
            $partnumber = trim(strtoupper($fields[0]));
            $assetid=trim(strtoupper($fields[1]));
            if (strlen($partnumber) <= 20 && strlen($partnumber) > 0 && $pim->validPart($partnumber))
            { // partnumber is valid
                if($asset->validAsset($assetid))
                { // asset id is valid
                    $sequence = intval(trim($fields[2]));
                    
                    // check for existing record(s) before adding
                    $existingconnections=$asset->getPartAssetConnections($partnumber, $assetid);                    
                    if(count($existingconnections))
                    {
                        foreach($existingconnections as $existingconnection)
                        {
                            if($sequence>=0)
                            {// new sequence is non-negative
                                if($sequence!=$existingconnection['sequence'])
                                {
                                    $asset->updatePartAssetSequence($partnumber, $existingconnection['id'], $sequence);
                                    $newoid=$pim->updatePartOID($partnumber);
                                    $pim->logPartEvent($partnumber, $_SESSION['userid'], 'Asset ['.$assetid.'] sequence changed from ['.$existingconnection['sequence'].'] to ['.$sequence.'] by mass import', $newoid);
                                    $updatecount++;
                                }
                            }
                            else
                            {// sequence is -1 (delete connection)
                                $asset->disconnectPartFromAsset($partnumber, $existingconnection['id']);
                                $newoid=$pim->updatePartOID($partnumber);
                                $pim->logPartEvent($partnumber, $_SESSION['userid'], 'Asset ['.$assetid.'] removed by mass import (sequence = -1)', $newoid);
                                $deletecount++;
                            }                        
                        }
                    }
                    else
                    {// there are no existing records for this part/assetid
                        $errors[]='Invalid part/assetid ['.$assetid.'/'.$assetid.'] on line '.$recordnumber;
                        $failedcount++;                        
                    }                    
                }
                else
                {// assetid is not valid
                    
                    $errors[]='Invalid assetid ['.$assetid.'] on line '.$recordnumber;
                    $failedcount++;
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
                        <h3 class="card-header text-start">Import Part-asset sequences</h3>

                        <div class="card-body">
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">
                                    <h6 class="alert-heading">Paste three tab-delimited columns (no header row):</h6>
                                    <p>Partnumber, assetid, sequence</p>
                                    <p>If the part-asset connection exists, its sequence will be updated. To delete an existing part-asset connection, import -1 as the sequence value.</p>
                                </div>
                                    <hr>                               
                                
                                <textarea name="input" style="width:100%;height:200px;"></textarea>
                                
                                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                            </form>
                            
                            <?php if($importcount>0){ echo '<div>Imported '.$importcount.' records.</div>';}?>
                            <?php if($updatecount>0){ echo '<div>Updated '.$updatecount.' records.</div>';}?>
                            <?php if($deletecount>0){ echo '<div>Deleted '.$deletecount.' records.</div>';}?>
                            
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