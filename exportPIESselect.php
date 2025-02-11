<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
$navCategory = 'export';

$pim = new pim;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'exportPIESselect.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
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
$receiverprofiles=$pim->getReceiverprofiles();
$preferedreceiverprofileid = $user->getUserPreference($_SESSION['userid'], 'last receiverprofileid used');
?>

<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        <script>
            function updateSelectedID() {
                var selectedBox = document.getElementById("selectBox");
                var selectedValue = selectBox.options[selectedBox.selectedIndex].value;
                var asset = document.getElementById("assetFilesDownload");
                var assethash = document.getElementById("assethashFilesDownload");
                var part = document.getElementById("partsListDownload");
                asset.setAttribute("href",'exportAssetfilesListStream.php?receiverprofile='+selectedValue);
                assethash.setAttribute("href",'exportAssetfilesListStream.php?format=hashlist&receiverprofile='+selectedValue);
                part.setAttribute("href",'exportPartListStream.php?receiverprofile='+selectedValue);
                asset.className = "btn btn-secondary btn-sm";
                assethash.className = "btn btn-secondary btn-sm";
                part.className = "btn btn-secondary btn-sm";
            }
        </script>
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
                        <h3 class="card-header text-start">Export PIES xml</h3>

                        <div class="card-body">
                            <form action="exportPIESstream.php" method="get">
                                <div style="border:solid #808080 1px;margin:20px;padding:10px;background-color: #f0f0f0">
                                    <div>
                                        Receiver Profile <select id="selectBox" name="receiverprofile" onclick="updateSelectedID();"><?php foreach ($receiverprofiles as $receiverprofile) { ?><option value="<?php echo $receiverprofile['id']; ?>" <?php if($receiverprofile['id']==$preferedreceiverprofileid){echo ' selected';} ?>><?php echo $receiverprofile['name']; ?></option><?php } ?></select>
                                        <a id="assetFilesDownload" href="" role="button" class="btn btn-secondary btn-sm disabled" aria-disabled="true" data-bs-toggle="tooltip" data-bs-placement="top" title="Generate Asset File List for Export">Assets</a>
                                        <a id="assethashFilesDownload" href="" role="button" class="btn btn-secondary btn-sm disabled" aria-disabled="true" data-bs-toggle="tooltip" data-bs-placement="top" title="Generate asset File hashes list for export">Asset Hashes</a>
                                        <a id="partsListDownload" href="" role="button" class="btn btn-secondary btn-sm disabled" aria-disabled="true" data-bs-toggle="tooltip" data-bs-placement="top" title="Generate Parts List">Parts</a>
                                    </div>
                                    <div><input type="checkbox" id="ignorelogic" name="ignorelogic"/><label for="ignorelogic">Ignore logic flaws</label></div>
                                    
                                    <div><input type="checkbox" id="showxml" name="showxml"/><label for="showxml">Display XML in a text area</label></div>

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