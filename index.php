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
include_once('./class/kpiClass.php');

$navCategory = 'dashboard';


$pim = new pim;
$logs = new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'index.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

$user = new user;
$asset=new asset;

$pcdb = new pcdb();
$vcdb = new vcdb();
$padb = new padb();
$qdb = new qdb();
$kpi=new kpi();


function acaVersionAgeColor($date)
{
 $returnval='#ffffff';
 if(strlen($date)==10)
 {
  $year=intval(substr($date,0,4)); $month=intval(substr($date,5,2)); $day=intval(substr($date,8,2));
  if($year>=1900 && $month>0 & $month <13 && $day>0 && $day<32)
  {
   $returnval='#ff0000';
   $diffseconds=time()-mktime(0, 0, 0, $month, $day, $year);
   if($diffseconds<=(60*60*24*8)){$returnval='#ff8800';}
   if($diffseconds<=(60*60*24*6)){$returnval='#ffd433';}
   if($diffseconds<=(60*60*24*4)){$returnval='#33ffd7';}
   if($diffseconds<=(60*60*24*2)){$returnval='#66FF99';}   
   if($diffseconds<=(60*60*24)){$returnval='#00FF00';}   
  }
 }
 return  $returnval;    
}



$configGet = new configGet;
$appshistory = $logs->getAppsEvents(100);
$assetshistory = $logs->getAssetsEvents(100);
$partshistory = $logs->getPartsEvents(100);
$systemhistory = $logs->getSystemEvents('%', false, 100);
$sandpiperhistory= $logs->getSandpiperEvents(100);



//$partissues=$pim->getIssues('PART/%','%',0,array(1,2),20);
$partissues=$pim->getPartIssuesPrioritized(20);
$appissues=$pim->getIssues('APP/%','%','%',array(1,2),20);
$assetissues=$pim->getIssues('ASSET/%','%','%',array(1,2),20);
$systemissues=$pim->getIssues('SYSTEM/%','%','%',array(1,2),20);
$sandpiperissues=$pim->getIssues('SANDPIPER/%','%','%',array(1,2),20);
$issuescount=count($partissues)+count($appissues)+count($assetissues)+count($systemissues)+count($sandpiperissues);
$metricsACTIVEPARTCOUNT=$kpi->getMetric('ACTIVE PART COUNT', date('Y-m-d', strtotime('-3 day')), date('Y-m-d',strtotime('+1 day')));
$metricsPUBLICASSETCOUNT=$kpi->getMetric('PUBLIC ASSET COUNT', date('Y-m-d', strtotime('-3 day')), date('Y-m-d',strtotime('+1 day')));
$metricsACTIVEAPPLICATIONCOUNT=$kpi->getMetric('ACTIVE APPLICATION COUNT', date('Y-m-d', strtotime('-3 day')), date('Y-m-d',strtotime('+1 day')));
$embeds=$pim->getDashboardEmbeds();

$jobstemp=$pim->getBackgroundjobs('%', '%');
$myjobs=array(); foreach($jobstemp as $job){if($job['userid']==$_SESSION['userid']){$myjobs[]=$job;}}

$logpreviewlength = intval($configGet->getConfigValue('logPreviewDescriptionLength', 80));
$firststockeddaysback = intval($configGet->getConfigValue('recentPartAdditionsDaysBack', 7));

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

                            <?php if(count($myjobs)){?>
                            <div class="card">
                                <h5 class="card-header text-start">My Background Jobs</h5>
                                <div class="card-body">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Type</th>
                                                <th scope="col">Filename</th>
                                                <th scope="col">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($myjobs as $job){?>
                                            <tr>
                                                <td><?php echo $job['jobtype'];?></td>
                                                <td><?php if($job['status']=='complete'){echo '<a href="./downloadBackgroundExport.php?token='.$job['token'].'">'.$job['clientfilename'].'</a>';}else{echo $job['clientfilename'];}?></td>
                                                <td><?php echo '<a href="./backgroundJob.php?id='.$job['id'].'">'.$job['status'].'</a>';?></td>
                                            </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php }?>

                            <?php 
                            $sincefirststockeddate=date('Y-m-d', time()-(24*3600*$firststockeddaysback));
                            $recentparts=$pim->getPartsSinceFirststockedDate($sincefirststockeddate);
                            if(count($recentparts)){ ?>
                            <div class="card">
                                <h5 class="card-header text-start">Recent Part Additions (First-stocked-date since <?php echo $sincefirststockeddate;?>)</h5>
                                <div class="card-body">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Partnumber</th>
                                                <th scope="col">Health Score</th>
                                                <th scope="col">Type</th>
                                                <th scope="col">Category</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">First Stocked</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($recentparts as $recentpart){
                                                $healthscore=$pim->partHealthScore($recentpart['partnumber']);
                                                $healthcolor='#FF8A65'; 
                                                if($healthscore>=10){$healthcolor='#FFB74D';} 
                                                if($healthscore>=20){$healthcolor='#FFD54F';} 
                                                if($healthscore>=30){$healthcolor='#FFF176';} 
                                                if($healthscore>=40){$healthcolor='#DCE775';} 
                                                if($healthscore>=50){$healthcolor='#AED581';} 
                                                if($healthscore>=60){$healthcolor='#81C784';} 
                                                if($healthscore>=70){$healthcolor='#4DB6AC';} 
                                                if($healthscore>=80){$healthcolor='#4DD0E1';} 
                                                if($healthscore>=90){$healthcolor='#4FC3F7';} 
                                                
                                                ?>
                                            <tr>
                                                <td><a href="showPart.php?partnumber=<?php echo $recentpart['partnumber'];?>" class="btn btn-secondary"><?php echo $recentpart['partnumber'];?></a></td>
                                                <td><div style="width:40px; border-radius: 8px; background-color: <?php echo $healthcolor;?>;"><?php echo $healthscore;?></div></td>
                                                <td><?php echo $pcdb->parttypeName($recentpart['parttypeid']);?></td>
                                                <td><?php echo $pim->partCategoryName($recentpart['partcategory']);?></td>
                                                <td><?php echo $pcdb->lifeCycleCodeDescription($recentpart['lifecyclestatus']);?></td>
                                                <td><?php echo $recentpart['firststockedDate'];?></td>
                                            </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php }?>

                            <div class="card">
                                <h5 class="card-header text-start">Metrics</h5>
                                <div class="card-body">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Name</th>
                                                <th scope="col">Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Active parts</td>
                                                <td><?php if(count($metricsACTIVEPARTCOUNT)>0){echo number_format($metricsACTIVEPARTCOUNT[count($metricsACTIVEPARTCOUNT)-1]['value'],0,'.',',');} ?></td>
                                            </tr>
                                            <tr>
                                                <td>Active Applications</td>
                                                <td><?php if(count($metricsACTIVEAPPLICATIONCOUNT)){echo number_format($metricsACTIVEAPPLICATIONCOUNT[count($metricsACTIVEAPPLICATIONCOUNT)-1]['value'],0,'.',',');} ?></td>
                                            </tr>
                                            <tr>
                                                <td>Public assets</td>
                                                <td><?php if(count($metricsPUBLICASSETCOUNT)){echo number_format($metricsPUBLICASSETCOUNT[count($metricsPUBLICASSETCOUNT)-1]['value'],0,'.',',');} ?></td>
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
                                                <td style="background-color: <?php echo acaVersionAgeColor($vcdb->version());?>;"><?php echo $vcdb->version();?></td>
                                                <td style="background-color: <?php echo acaVersionAgeColor($pcdb->version());?>;"><?php echo $pcdb->version();?></td>
                                                <td style="background-color: <?php echo acaVersionAgeColor($padb->version());?>;"><?php echo $padb->version();?></td>
                                                <td style="background-color: <?php echo acaVersionAgeColor($qdb->version());?>;"><?php echo $qdb->version();?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            


                            
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
 
                                    if(count($sandpiperhistory)) {
                                    echo '<li class="nav-item">
                                        <a class="nav-link" id="sandpiper-tab" data-bs-toggle="tab" href="#sandpiper" role="tab" aria-controls="sandpiper" aria-selected="false">Sandpiper</a>
                                    </li>';
                                    }
                                        
                                echo '</ul>';
                                      
                                echo '<div class="tab-content" id="myTabContent">';
                                  echo '<div class="tab-pane fade show active mt-3" id="main" role="tabpanel" aria-labelledby="main-tab">
                                            Logged Events
                                        </div>';
                                        if(count($appshistory)) {
                                            echo '<div class="tab-pane fade mt-3" id="applications" role="tabpanel" aria-labelledby="applications-tab">'
                                            . '<table class="table"><tr><th>Date/Time</th><th>User</th><th>AppID</th><th>Event Description</th></tr>';
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
                                            . '<table class="table"><tr><th>Date/Time</th><th>User</th><th>AssetID</th><th>Event Description</th></tr>';
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
                                            . '<table class="table"><tr><th>Date/Time</th><th>User</th><th>Partnumber</th><th>Event Description</th></tr>';
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
                                            . '<table class="table"><tr><th>Date/Time</th><th>User</th><th>Eventtype</th><th>Description</th></tr>';
                                            foreach ($systemhistory as $record) {
                                                $nicedescription = $record['description'];
                                                if (strlen  ($nicedescription) > $logpreviewlength) {
                                                    $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                                                }
                                                echo '<tr><td><a href="./showSystemLogEvent.php?id='.$record['id'].'">' . $record['eventdatetime'] . '</a></td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td>'.$record['eventtype'].'</td><td style="max-width:400px;"><div class="text-truncate">' . $nicedescription . '</div></td></tr>';
                                            }
                                            echo '</table></div>';
                                        }      

                                        if(count($sandpiperhistory))
                                        {
                                            echo '<div class="tab-pane fade mt-3" id="sandpiper" role="tabpanel" aria-labelledby="sandpiper-tab">'
                                            . '<table class="table"><tr><th>Date/Time</th><th>Event Description</th></tr>';
                                            foreach ($sandpiperhistory as $record) {
                                                $nicedescription = $record['action'];
                                                if (strlen  ($nicedescription) > $logpreviewlength) {
                                                    $nicedescription = substr($nicedescription, 0, $logpreviewlength) . '...';
                                                }
                                                echo '<tr><td><a href="./showSandpiperLogEvent.php?id='.$record['id'].'">' . $record['timestamp'] . '</a></td><td><code>' . $nicedescription . '</code></td></tr>';
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
                        <?php foreach($embeds as $embed){?>
                        <div class="card">
                            <h5 class="card-header text-start"><?php echo $embed['description'];?></h5>
                            <div class="card-body"><?php echo $embed['data'];?></div>
                        </div>
                        <?php }?>

                </div>
            </div>
        </div>
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                    
                    <?php //print_r($orphans);?>
                    
                    
    </div>
        
        <!-- Footer -->
        
        <?php include('./includes/footer.php'); ?>
    </body>
</html>
