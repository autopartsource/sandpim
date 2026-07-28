<?php
include_once('./class/pimClass.php');
include_once('./class/replicationClass.php');
include_once('./class/logsClass.php');
$navCategory = 'settings';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'replication.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$replication = new replication();

$peers=$replication->getAllPeers(); //     $peers[]=array('id'=>$row['id'],'identifier'=>$row['identifier'],'description'=>$row['description'],'type'=>$row['type'],'role'=>$row['role'],'uri'=>$row['uri'],'objectlimit'=>$row['objectlimit'],'sharedsecret'=>$row['sharedsecret'],'enabled'=>$row['enabled']);

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
                
                <div class="col-xs-12">
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start">Upstream Peers</h3>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr><th>Identifier</th><th>Description</th><th>Type</th><th>URI</th><th>Object Limit</th><th>Signing Secret</th><th>Enabled</th></tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($peers as $peer) 
                                {
                                    if($peer['role']=='secondary'){continue;}
                                    echo '<tr>';
                                    echo '<td><a href="./replicationPeer.php?id='.$peer['id'].'">'.$peer['identifier'].'</a></td>';
                                    echo '<td>'.$peer['description'].'</td>';
                                    echo '<td>'.$peer['type'].'</td>';
                                    echo '<td>'.$peer['uri'].'</td>';
                                    echo '<td>'.$peer['objectlimit'].'</td>';
                                    echo '<td>'.$peer['sharedsecret'].'</td>';
                                    echo '<td>'.$peer['enabled'].'</td>';
                                    echo '<td></td>';
                                    echo '</tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start">Downstream Peers</h3>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr><th>Identifier</th><th>Description</th><th>Type</th><th>URI</th><th>Object Limit</th><th>Signing Secret</th><th>Enabled</th></tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($peers as $peer) 
                                {
                                    if($peer['role']=='primary'){continue;}
                                    echo '<tr>';
                                    echo '<td><a href="./replicationPeer.php?id='.$peer['id'].'">'.$peer['identifier'].'</a></td>';
                                    echo '<td>'.$peer['description'].'</td>';
                                    echo '<td>'.$peer['type'].'</td>';
                                    echo '<td>'.$peer['uri'].'</td>';
                                    echo '<td>'.$peer['objectlimit'].'</td>';
                                    echo '<td>'.$peer['sharedsecret'].'</td>';
                                    echo '<td>'.$peer['enabled'].'</td>';
                                    echo '<td></td>';
                                    echo '</tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    
                    
                    
                    
                    
                </div>
                <!-- End of Main Content -->
                
            </div>

        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>