<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
$navCategory = 'utilities';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'backupRestoreSettings.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])) {echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}
if(!$pim->userHasNavelement($_SESSION['userid'], 'UTILITIES/BACKUPRESTORE')){echo 'access denied'; $logs->logSystemEvent('accesscontrol', $_SESSION['userid'], 'denied:UTILITIES/BACKUPRESTORE'); exit;}

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
                        <h3 class="card-header text-start">Export System Settings</h3>

                        <div class="card-body">
                            This will export the following system settings to an XML download.
                            Parts, Apps and Assets (or history) are not included in this export.
                            <div style="text-align:left;padding:40px;">
                                <ul>
                                    <li>Configuration</li>
                                    <li>Users</li>
                                    <li>Receiver Profiles</li>
                                    <li>Part Categories</li>
                                    <li>Delivery Groups</li>
                                    <li>Price Sheets</li>
                                    <li>Favorite Makes</li>
                                    <li>Favorite Part Types</li>
                                    <li>Favorite App Positions</li>
                                    <li>Favorite Brands</li>
                                    <li>Description Recipes</li>
                                    <li>Replication Peers</li>
                                    <li>Asset Tags</li>
                                    <li>Allowed Hosts</li>
                                </ul>
                            </div>

                            <form action="exportSystemStream.php" method="get">
                               <input type="submit" name="submit" value="Export"/>
                            </form>
                        </div>
                    </div>
                    
                    
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Import System Settings</h3>

                        <div class="card-body">
                            <div style="padding-bottom: 20px;">Paste XML settings file content in the text area below. The XML content will replace the current settings. If any problems are detected 
                            with the data, no changes will be made. If you want to only import specific settings and leave the other untouched, you can 
                            delete sections of the XML file. For example, if you only wanted to replace replicationpeers, the XML files should only contain the replicationpeers section.</div>
                            
                            <form method="post" action="importSystem.php">
                                <textarea name="input" style="width:95%;height: 200px;"></textarea>
                                <div style="padding:10px;"><label><input type="checkbox" name="test"/>Test data without importing</label></div>

                                <div style="padding:5px;"><input name="submit" type="submit" value="Import"/></div>
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