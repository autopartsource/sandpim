<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/padbClass.php');
include_once('./class/qdbClass.php');

$navCategory = 'dashboard';

$pim = new pim;
$logs = new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'index.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$user = new user;
$asset=new asset;

$pcdb = new pcdb();
$vcdb = new vcdb();
$padb = new padb();
$qdb = new qdb();


$configGet = new configGet;
$appshistory = $logs->getAppsEvents(20);
$assetshistory = $logs->getAssetsEvents(20);
$partshistory = $logs->getPartsEvents(20);
$systemhistory = $logs->getSystemEvents('%', false, 20);

$partissues=$pim->getIssues('PART/%','%',0,array(1,2),20);
$appissues=$pim->getIssues('APP/%','%','%',array(1,2),20);
$assetissues=$pim->getIssues('ASSET/%','%','%',array(1,2),20);
$systemissues=$pim->getIssues('SYSTEM/%','%','%',array(1,2),20);
$sandpiperissues=$pim->getIssues('SANDPIPER/%','%','%',array(1,2),20);
$issuescount=count($partissues)+count($appissues)+count($assetissues)+count($systemissues)+count($sandpiperissues);

$logpreviewlength = intval($configGet->getConfigValue('logPreviewDescriptionLength', 80));
?>
<!DOCTYPE html>
<html>
    <head>
        <script>
            function deleteIssue(id)
            {
             var issuediv = document.getElementById('issue_'+id);
             issuediv.parentNode.removeChild(issuediv);
             
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxDeleteIssue.php?id='+id);
             xhr.onload = function()
             {
             };
             xhr.send();
            }

        </script>
            
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft"></div>
                
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
                        
                        <!-- Header -->
                        <h3 class="card-header text-start">Dashboard</h3>
                        
                        <!-- Main Content -->
                        <div class="card-body">
                            
                            <div class="card">
                                <h5 class="card-header text-start">Active AutoCare Reference Databases</h5>
                                <div class="card-body">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">VCdb</th>
                                                <th scope="col">PCdb</th>
                                                <th scope="col">PAdb</th>
                                                <th scope="col">Qdb</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><?php echo $vcdb->version();?></td>
                                                <td><?php echo $pcdb->version();?></td>
                                                <td><?php echo $padb->version();?></td>
                                                <td><?php echo $qdb->version();?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <?php if ($issuescount>0) {
                            echo '<div class="card">
                                <h5 class="card-header text-start">Issues <span class="badge rounded-pill bg-danger">'.$issuescount.'</span></h5>
                                <div class="card-body">
                                    <ul class="nav nav-tabs" id="myTab" role="tablist">';
                                        if (count($partissues) > 0) {
                                            echo '<li class="nav-item" role="presentation">
                                                <a class="nav-link" id="partissues-tab" data-bs-toggle="tab" href="#partissues" role="tab" aria-controls="partissues" aria-selected="true">Parts <span class="badge rounded-pill bg-danger">'.count($partissues).'</span></a>
                                            </li>';
                                        }
                                        if (count($appissues) > 0) {
                                            echo '<li class="nav-item" role="presentation">
                                            <a class="nav-link" id="appissues-tab" data-bs-toggle="tab" href="#appissues" role="tab" aria-controls="appissues" aria-selected="true">Apps <span class="badge rounded-pill bg-danger">'.count($appissues).'</span></a>
                                        </li>';
                                        }
                                        if (count($assetissues) > 0) {
                                            echo '<li class="nav-item" role="presentation">
                                            <a class="nav-link" id="assetissues-tab" data-bs-toggle="tab" href="#assetissues" role="tab" aria-controls="assetissues" aria-selected="true">Assets <span class="badge rounded-pill bg-danger">'.count($assetissues).'</span></a>
                                        </li>';
                                        }
                                        if (count($systemissues) > 0) {
                                            echo '<li class="nav-item" role="presentation">
                                            <a class="nav-link" id="systemissues-tab" data-bs-toggle="tab" href="#systemissues" role="tab" aria-controls="systemissues" aria-selected="true">System <span class="badge rounded-pill bg-danger">'.count($systemissues).'</span></a>
                                        </li>';
                                        }
                                        if (count($sandpiperissues) > 0) {
                                            echo '<li class="nav-item" role="presentation">
                                            <a class="nav-link" id="sandpiperissues-tab" data-bs-toggle="tab" href="#sandpiperissues" role="tab" aria-controls="sandpiperissues" aria-selected="true">Sandpiper <span class="badge rounded-pill bg-danger">'.count($sandpiperissues).'</span></a>
                                        </li>';
                                        }
                                        
                                    echo'</ul>
                                    <div class="tab-content" id="myTabContent">
                                        <div class="tab-pane fade show active mt-3" id="main" role="tabpanel" aria-labelledby="main-tab">
                                            Open Issues (from all sources)
                                        </div>';

                                        if (count($partissues) > 0) {
                                            echo '<div class="tab-pane fade mt-3" id="partissues" role="tabpanel" aria-labelledby="partissues-tab">';
                                            foreach($partissues as $partissue)
                                            {
                                             echo '<div style="padding:2px;" id="issue_'.$partissue['id'].'"><a href="./showPart.php?partnumber='.$partissue['issuekeyalpha'].'">'.$partissue['issuekeyalpha'].'</a>: '.$partissue['description'].' <button class="btn btn-sm btn-outline-danger" onclick="deleteIssue(\''.$partissue['id'].'\')"><i class="bi bi-x"></i></button></div>';
                                            }
                                            echo '</div>';
                                        }
                                        if (count($appissues) > 0) {
                                            echo '<div class="tab-pane fade mt-3" id="appissues" role="tabpanel" aria-labelledby="appissues-tab">';
                                            foreach($appissues as $appissue)
                                            {
                                             echo '<div style="padding:2px;" id="issue_'.$appissue['id'].'"><a href="./showApp.php?appid='.$appissue['issuekeynumeric'].'">App ID '.$appissue['issuekeynumeric'].'</a>: '.$appissue['description'].' <button class="btn btn-sm btn-outline-danger" onclick="deleteIssue(\''.$appissue['id'].'\')"><i class="bi bi-x"></i></button></div>';
                                            }
                                            echo '</div>';
                                        }
                                        if (count($assetissues) > 0) {
                                            echo '<div class="tab-pane fade mt-3" id="assetissues" role="tabpanel" aria-labelledby="assetissues-tab">';
                                            foreach($assetissues as $assetissue)
                                            {
                                             echo '<div style="padding:2px;" id="issue_'.$assetissue['id'].'"><a href="./showAsset.php?assetid='.$assetissue['issuekeyalpha'].'">'.$assetissue['issuekeyalpha'].'</a>: '.$assetissue['description'].' <button class="btn btn-sm btn-outline-danger" onclick="deleteIssue(\''.$assetissue['id'].'\')"><i class="bi bi-x"></i></button></div>';
                                            }
                                            echo '</div>';
                                        }
                                        if (count($systemissues) > 0) {
                                            echo '<div class="tab-pane fade mt-3" id="systemissues" role="tabpanel" aria-labelledby="systemissues-tab">';
                                            foreach($systemissues as $systemissue)
                                            {
                                             echo '<div style="padding:2px;" id="issue_'.$systemissue['id'].'">'.$systemissue['description'].' <button class="btn btn-sm btn-outline-danger" onclick="deleteIssue(\''.$systemissue['id'].'\')"><i class="bi bi-x"></i></button></div>';
                                            }
                                            echo '</div>';
                                        }
                                        if (count($sandpiperissues) > 0) {
                                            echo '<div class="tab-pane fade mt-3" id="sandpiperissues" role="tabpanel" aria-labelledby="sandpiperissues-tab">';
                                            foreach($sandpiperissues as $sandpiperissue)
                                            {
                                             echo '<div style="padding:2px;" id="issue_'.$sandpiperissue['id'].'">'.$sandpiperissue['description'].' <button class="btn btn-sm btn-outline-danger" onclick="deleteIssue(\''.$sandpiperissue['id'].'\')"><i class="bi bi-x"></i></button></div>';
                                            }
                                            echo '</div>';
                                        }
                                        
                                echo '</div>
                                </div>
                            </div>';
                            }?>
                            
                            <?php
                            if(count($appshistory) || count($assetshistory) || count($partshistory) || count($systemhistory)) {
                            echo '<div class="card">
                                <h5 class="card-header text-start">History</h5>
                                <div class="card-body">';
                                echo '<ul class="nav nav-tabs" id="myTab" role="tablist">';
                                    if(count($appshistory)) {
                                    echo '<li class="nav-item">
                                        <a class="nav-link" id="applications-tab" data-bs-toggle="tab" href="#applications" role="tab" aria-controls="applications" aria-selected="true">Applications</a>
                                    </li>';
                                    }

                                    if(count($assetshistory)) {
                                    echo '<li class="nav-item">
                                        <a class="nav-link" id="assets-tab" data-bs-toggle="tab" href="#assets" role="tab" aria-controls="assets" aria-selected="false">Assets</a>
                                    </li>';
                                    }

                                    if(count($partshistory)) {
                                    echo '<li class="nav-item">
                                        <a class="nav-link" id="parts-tab" data-bs-toggle="tab" href="#parts" role="tab" aria-controls="parts" aria-selected="false">Parts</a>
                                    </li>';
                                    }

                                    if(count($systemhistory)) {
                                    echo '<li class="nav-item">
                                        <a class="nav-link" id="system-tab" data-bs-toggle="tab" href="#system" role="tab" aria-controls="system" aria-selected="false">System</a>
                                    </li>';
                                    }
                                        
                                echo '</ul>';
                                      
                                echo '<div class="tab-content" id="myTabContent">';
                                  echo '<div class="tab-pane fade show active mt-3" id="main" role="tabpanel" aria-labelledby="main-tab">
                                            Logged Events
                                        </div>';
                                        if(count($appshistory)) {
                                            echo '<div class="tab-pane fade mt-3" id="applications" role="tabpanel" aria-labelledby="applications-tab">'
                                            . '<table class="table"><tr><th>Date/Time</th><th>User</th><th>AppID</th><th>Change Description</th></tr>';
                                                foreach ($appshistory as $record) {
                                                    $nicedescription = $record['description'];
                                                    if (strlen($nicedescription) > $logpreviewlength) {
                                                        $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                                                    }
                                                    echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td><a href="showApp.php?appid='.$record['applicationid'].'">'.$record['applicationid'].'</a></td><td>' . $nicedescription . '</td></tr>';
                                                }
                                                echo '</table>'
                                            . '</div>';
                                        }
                                        
                                        if (count($assetshistory)) 
                                        {
                                            echo '<div class="tab-pane fade mt-3" id="assets" role="tabpanel" aria-labelledby="assets-tab">'
                                            . '<table class="table"><tr><th>Date/Time</th><th>User</th><th>AssetID</th><th>Change Description</th></tr>';
                                                foreach ($assetshistory as $record) {
                                                    $nicedescription = $record['description'];
                                                    if (strlen($nicedescription) > $logpreviewlength) {
                                                        $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                                                    }
                                                    echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td><a href="showAsset.php?assetid='.$record['assetid'].'">'.$record['assetid'].'</a></td><td>' . $nicedescription . '</td></tr>';
                                                }
                                            echo '</table>'
                                            . '</div>';
                                        }
                                        
                                        if (count($partshistory)) 
                                        {
                                            echo '<div class="tab-pane fade mt-3" id="parts" role="tabpanel" aria-labelledby="parts-tab">'
                                            . '<table class="table"><tr><th>Date/Time</th><th>User</th><th>Partnumber</th><th>Change Description</th></tr>';
                                            foreach ($partshistory as $record) {
                                                $nicedescription = $record['description'];
                                                if (strlen  ($nicedescription) > $logpreviewlength) {
                                                    $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                                                }
                                                echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td><a href="showPart.php?partnumber='.$record['partnumber'].'">'.$record['partnumber'].'</a></td><td>' . $nicedescription . '</td></tr>';
                                            }
                                            echo '</table></div>';
                                        }
                                        
                                        if(count($systemhistory))
                                        {
                                            echo '<div class="tab-pane fade mt-3" id="system" role="tabpanel" aria-labelledby="system-tab">'
                                            . '<table class="table"><tr><th>Date/Time</th><th>User</th><th>Eventtype</th><th>Change Description</th></tr>';
                                            foreach ($systemhistory as $record) {
                                                $nicedescription = $record['description'];
                                                if (strlen  ($nicedescription) > $logpreviewlength) {
                                                    $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                                                }
                                                echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td>'.$record['eventtype'].'</td><td>' . $nicedescription . '</td></tr>';
                                            }
                                            echo '</table></div>';
                                        }      
                            echo '</div>';
                            } else {
                                echo '<div></div>';
                            }
                            ?>
                        </div>
                        <!-- End of Main Content -->
                    </div>
                </div>
            </div>
        </div>
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight"></div>
    </div>
</div>
</div>
        
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>
