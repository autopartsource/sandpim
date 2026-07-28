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
$logs = new logs();

$id=intval($_GET['id']);
$peer=$replication->getPeerById($id); //     $peers[]=array('id'=>$row['id'],'identifier'=>$row['identifier'],'description'=>$row['description'],'type'=>$row['type'],'role'=>$row['role'],'uri'=>$row['uri'],'objectlimit'=>$row['objectlimit'],'sharedsecret'=>$row['sharedsecret'],'enabled'=>$row['enabled']);
$events= $logs->getSystemEvents('replication', false, 1000);

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
                        <h3 class="card-header text-start"><a href="./replication.php">Peers </a> > <?php echo $peer['description'];?></h3>
                        <div class="card-body">
                            <table class="table">
                                <tbody>
                                    <tr><th>Identifier</th><td><?php echo $peer['identifier'];?></td></tr>
                                    <tr><th>Description</th><td><?php echo $peer['description'];?></td></tr>
                                    <tr><th>Type</th><td><?php echo $peer['type'];?></td></tr>
                                    <tr><th>Role</th><td><?php echo $peer['role'];?></td></tr>
                                    <tr><th>URI</th><td><?php echo $peer['uri'];?></td></tr>
                                    <tr><th>Object Limit</th><td><?php echo $peer['objectlimit'];?></td></tr>
                                    <tr><th>Signing Secret</th><td><?php echo $peer['sharedsecret'];?></td></tr>
                                    <tr><th>Enabled</th><td><?php echo $peer['enabled'];?></td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-body">
                            <table class="table">
                                <thead><tr><th>Date-time</th><th>Event</th></tr></thead>
                                <tbody>
                                    <?php foreach($events as $event){                                    
                                        if(strstr($event['description'], 'peerid ['.$id.']')===false){continue;} ?>
                                    <tr><td><?php echo $event['eventdatetime'];?></td><td><?php echo $event['description'];?></td></tr>
                                    <?php }?>
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