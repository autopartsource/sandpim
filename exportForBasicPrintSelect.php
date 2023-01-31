<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'export';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exportForBasicPrintSelect.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;
$user = new user;


$partcategories = $pim->getPartCategories();
$allreceiverprofiles=$pim->getReceiverprofiles();
$receiverprofiles=array();
foreach($allreceiverprofiles as $receiverprofile)
{
    $data=$receiverprofile['data'];
    $pairs=explode(";",$data);
    foreach($pairs as $pair)
    {
        $pairparts=explode(':',$pair);
        if(count($pairparts)==2 && $pairparts[0]=='IsPrintable' && $pairparts[1]=='yes')
        {
         $receiverprofiles[]=$receiverprofile;
        }        
    }
}


$preferedreceiverprofileid = $user->getUserPreference($_SESSION['userid'], 'last receiverprofileid used');


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
                        <h3 class="card-header text-start">Export PDF application guide (single parts column)</h3>

                        <div class="card-body">
                            <form action="exportForBasicPrintProcess.php" method="get">
                                
                                <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f0f0f0">
                                    Printable Receiver Profile <select name="receiverprofile"><?php foreach ($receiverprofiles as $receiverprofile) { ?><option value="<?php echo $receiverprofile['id']; ?>" <?php if($receiverprofile['id']==$preferedreceiverprofileid){echo ' selected';} ?>><?php echo $receiverprofile['name']; ?></option><?php } ?></select>
                                   <input type="submit" name="submit" value="Export"/>
                                </div>
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