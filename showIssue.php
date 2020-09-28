<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');


$navCategory = '';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;

$issue=$pim->getIssueById(intval($_GET['id']));

$issueTypes = explode('/',$issue['issuetype']);


?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
            <script>
            
        
            
            </script>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft" id="sidebar">
                    
                    <ul class="nav flex-column flex-nowrap overflow-hidden">
                        <li class="nav-item">
                            <a class="nav-link collapsed text-truncate" href="#submenu1" data-toggle="collapse" data-target="#submenu1"><span class="d-none d-sm-inline">Parts</span></a>
                            <div class="collapse" id="submenu1" aria-expanded="false">
                                <ul class="flex-column pl-2 nav">
                                    <li class="nav-item">
                                        <a class="nav-link collapsed text-truncate" href="#submenu1" data-toggle="collapse" data-target="#submenu1sub1"><span class="d-none d-sm-inline">GTIN</span></a>
                                        <div class="collapse" id="submenu1sub1" aria-expanded="false">
                                            <ul class="flex-column nav pl-4">
                                                <li class="nav-item">
                                                    <a class="nav-link p-1" href="./showIssue.php?id=<?php echo $issue['id'] ?>"><?php echo $issue['issuekeyalpha'] ?></a>
                                                </li>
                                        </div>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link collapsed text-truncate" href="#submenu1" data-toggle="collapse" data-target="#submenu1sub2"><span class="d-none d-sm-inline">Package</span></a>
                                        <div class="collapse" id="submenu1sub2" aria-expanded="false">
                                            
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-10 my-col colMain">
                    <div class="card shadow-sm">
                        <div class="card-header text-left">
                            <?php echo $issue['issuetype']; ?> Breadcrumb Nav
                        </div>
 
                        <?php print_r($issue);?>

                        <div class="card shadow-sm">
                            <!-- Header -->
                            <h5 class="card-header text-left">Issue ID: <span class="text-info"><?php echo $issue['id']; ?></span></h5>

                            <div class="card-body">
                                <div class="row padding my-row">
                                    <div class="col md-4">
                                        <div class="card shadow-sm">
                                            <!-- Header -->
                                            <h6 class="card-header text-left">Item - <?php echo $issue['issuekeyalpha']; ?></h6>
                                        </div>
                                    </div>
                                    <div class="col md-4">
                                        <div class="card shadow-sm">
                                            <!-- Header -->
                                            <h6 class="card-header text-left">Status - <?php echo $issue['status']; ?></h6>
                                        </div>
                                    </div>
                                    <div class="col md-4">
                                        <div class="card shadow-sm">
                                            <!-- Header -->
                                            <h6 class="card-header text-left">Source - <?php echo $issue['source']; ?></h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="row padding my-row">
                                    <div class="col md-6">
                                        <div class="card shadow-sm">
                                            <h6 class="card-header text-left">Description</h6>

                                            <div class="card-body">
                                                <?php echo $issue['description']; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col md-6">
                                        <div class="card shadow-sm">
                                            <h6 class="card-header text-left"> Notes</h6>

                                            <div class="card-body">
                                                <?php echo $issue['notes']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                Created at <?php echo $issue['issuedatetime']; ?>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <!-- End of Main Content -->

            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>