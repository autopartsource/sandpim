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

$selectedissue = $pim->getIssueById(intval($_GET['id']));

$tree = array();

$issues = $pim->getIssues('%', '%', '%', 9999);

foreach ($issues as $issue) {
    $issuebits = explode('/', $issue['issuetype']);

    if (count($issuebits)) {

        for ($i = 0; $i < count($issuebits); $i++) {

            switch ($i) {
                case 0:
                    if (!array_key_exists($issuebits[0], $tree)) {
                        $tree[$issuebits[0]] = array();
                    }
                    if ($i == count($issuebits) - 1) {// this is the lowest (least-significant) level of the path
                        $tree[$issuebits[0]][] = $issue;
                    }

                    break;
                case 1:
                    if (!array_key_exists($issuebits[1], $tree[$issuebits[0]])) {
                        $tree[$issuebits[0]][$issuebits[1]] = array();
                    }
                    if ($i == count($issuebits) - 1) {// this is the lowest (least-significant) level of the path
                        $tree[$issuebits[0]][$issuebits[1]][] = $issue;
                    }

                    break;
                case 2:
                    if (!array_key_exists($issuebits[2], $tree[$issuebits[0]][$issuebits[1]])) {
                        $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]] = array();
                    }
                    if ($i == count($issuebits) - 1) {// this is the lowest (least-significant) level of the path
                        $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][] = $issue;
                    }

                    break;
                case 3:
                    if (!array_key_exists($issuebits[3], $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]])) {
                        $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]] = array();
                    }
                    if ($i == count($issuebits) - 1) {// this is the lowest (least-significant) level of the path
                        $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]][] = $issue;
                    }

                    break;
                case 4:
                    if (!array_key_exists($issuebits[4], $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]])) {
                        $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]][$issuebits[4]] = array();
                    }
                    if ($i == count($issuebits) - 1) {// this is the lowest (least-significant) level of the path
                        $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]][$issuebits[4]][] = $issue;
                    }

                    break;

                default: break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
<?php include('./includes/header.php'); ?>
        <script>
            function renderIssue(id) {
                console.log(id);

                var issueData = atob(document.getElementById('issue_' + id).getAttribute('data-issue'));
                var issueObject = JSON.parse(issueData);

                document.getElementById("issueId").innerHTML = issueObject.id;
                document.getElementById("issueBreadcrumb").innerHTML = issueObject.issuetype;
                document.getElementById("issueDescription").innerHTML = issueObject.description;
                document.getElementById("issueSource").innerHTML = issueObject.source;
                document.getElementById("issueFooter").innerHTML = issueObject.issuedatetime;

                if (issueObject.status == 1) {
                    document.getElementById("issueStatus").innerHTML = "Issue Resolved";
                    document.getElementById("issueStatus").className = "card-header text-white bg-success";
                } else if (issueObject.status == 2) {
                    document.getElementById("issueStatus").innerHTML = "Issue In Review";
                    document.getElementById("issueStatus").className = "card-header text-white bg-warning";

                } else {
                    document.getElementById("issueStatus").innerHTML = "Issue Open";
                    document.getElementById("issueStatus").className = "card-header text-white bg-danger";
                }
                
                document.getElementById("issueId").setAttribute("data-issueid",issueObject.id);

                console.log(issueObject);

            }

            function updateIssueNotes()
            {
                var id = document.getElementById("issueId").getAttribute("data-issueid");
                var value = document.getElementById("issueNotes").value;

                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxUpdateIssue.php?issueid=' + id + '&elementid=notes&value=' + encodeURI(value));
                xhr.onload = function ()
                {
                };
                xhr.send();
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
                <div class="col-xs-12 col-md-2 my-col colLeft scroll" id="sidebar">
                    <!--
                    <ul class="nav flex-column flex-nowrap overflow-hidden">
                        <li class="nav-item">
                            <a class="nav-link collapsed text-truncate side-nav-link" href="#submenu1" data-toggle="collapse" data-target="#submenu1"><span class="d-none d-sm-inline">Parts</span></a>
                            <div class="collapse" id="submenu1" aria-expanded="false">
                                <ul class="flex-column pl-2 nav">
                                    <li class="nav-item">
                                        <a class="nav-link collapsed text-truncate side-nav-link" href="#submenu1sub1" data-toggle="collapse" data-target="#submenu1sub1"><span class="d-none d-sm-inline">GTIN</span></a>
                                        <div class="collapse" id="submenu1sub1" aria-expanded="false">
                                            <ul class="flex-column nav pl-4">
                                                <li class="nav-item">
                                                    <a class="nav-link p-1 side-nav-link" href="./showIssue.php?id=<?php echo $selectedissue['id'] ?>"><?php echo $selectedissue['issuekeyalpha'] ?></a>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link collapsed text-truncate side-nav-link" href="#submenu1" data-toggle="collapse" data-target="#submenu1sub2"><span class="d-none d-sm-inline">Package</span></a>
                                        <div class="collapse" id="submenu1sub2" aria-expanded="false">
                                            
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                    -->

                    <div>
                        <?php
                        echo '<ul class="nav flex-column flex-nowrap overflow-hidden">';
                        foreach ($tree as $key0 => $data0) {

                            echo '<li class="nav-item">';
                            echo '<a class="nav-link collapsed text-truncate side-nav-link" href="#' . $key0 . '" data-toggle="collapse" data-target="#' . $key0 . '"><span class="d-none d-sm-inline">' . $key0 . '</span></a>';
                            echo '<div class="collapse" id="' . $key0 . '" aria-expanded="false">';

                            foreach ($data0 as $key1 => $data1) {
                                echo '<ul class="flex-column pl-2 nav">';
                                echo '<li class="nav-item">';

                                if (array_key_exists('issuehash', $data1)) {// this is an end-node
                                    echo '<div>' . $data1 . '</div>';
                                } else {// this node contains children 
                                    echo '<a class="nav-link collapsed text-truncate side-nav-link" href="#' . $key0 . '-' . $key1 . '" data-toggle="collapse" data-target="#' . $key0 . '-' . $key1 . '"><span class="d-none d-sm-inline">' . $key1 . '</span></a>';
                                    echo '<div class="collapse" id="' . $key0 . '-' . $key1 . '" aria-expanded="false">';
                                    //echo '- ' . $key1 . '<br/>';
                                }

                                foreach ($data1 as $key2 => $data2) {
                                    echo '<ul class="flex-column nav pl-4">';
                                    echo '<li class="nav-item">';

                                    if (array_key_exists('issuehash', $data2)) {// this is an end-node
                                        echo '<div>' . $data2 . '</div>';
                                    } else {// this node contains children 
                                        echo '<a class="nav-link collapsed text-truncate side-nav-link" href="#' . $key0 . '-' . $key1 . '-' . $key2 . '" data-toggle="collapse" data-target="#' . $key0 . '-' . $key1 . '-' . $key2 . '"><span class="d-none d-sm-inline">' . $key2 . '</span></a>';
                                        echo '<div class="collapse" id="' . $key0 . '-' . $key1 . '-' . $key2 . '" aria-expanded="false">';
                                        //echo '- - ' . $key2 . '<br/>'; 
                                    }

                                    foreach ($data2 as $key3 => $data3) {
                                        echo '<ul class="flex-column nav pl-5">';

                                        if (array_key_exists('issuehash', $data3)) {// this is an end-node
                                            echo '<li class="nav-item">';
                                            echo '<div id="issue_' . $data3['id'] . '" data-issue="' . base64_encode(json_encode($data3)) . '" onclick="renderIssue(\'' . intval($data3['id']) . '\');">' . $data3['description'] . '</div>';
                                            echo '</li>';
                                        } else {// this node contains children
                                            echo '<li class="nav-item">';
                                            echo '<div>' . $key3 . '</div>';
                                            //echo '<a class="nav-link p-1 side-nav-link" href="./showIssue.php?id='.$key3.'">'.$key3.'</a>';
                                            //echo $key3;
                                            echo '</li>';
                                        }

                                        echo'</ul>';
                                    }

                                    echo '</li>';
                                    echo'</ul>';
                                }

                                echo '</div>';
                                echo '</li>';
                                echo '</ul>';
                            }

                            echo '</div>';
                            echo '</li>';
                        }
                        echo '</ul>';
                        ?>
                    </div>            
                </div>



                <!-- Main Content -->
                <div class="col-xs-12 col-md-10 my-col colMain">
                    <div class="card shadow-sm">
                        <div id="issueBreadcrumb" class="card-header text-left">
                        </div>

                        <div class="card shadow-sm">
                            <!-- Header -->
                            <h5 class="card-header text-left">Issue ID: <span id="issueId" class="text-info" data-issueid="<?php echo $selectedissue['id']; ?>"><?php echo $selectedissue['id']; ?></span></h5>

                            <div class="card-body">
                                <div class="row padding my-row">
                                    <div class="col md-4">
                                        <div class="card shadow-sm">
                                            <!-- Header -->
                                            <h6 class="card-header text-left">Item - <?php echo $selectedissue['issuekeyalpha']; ?></h6>
                                        </div>
                                    </div>
                                    <div class="col md-4">
                                        <div class="card shadow-sm">
                                            <!-- Header -->
                                            <?php
                                            if ($selectedissue['status'] == 1) {
                                                echo '<h6 id="issueStatus" class="card-header text-white bg-success">Issue Resolved</h6>';
                                            } else if ($selectedissue['status'] == 2) {
                                                echo '<h6 id="issueStatus" class="card-header text-white bg-warning">Issue In Review</h6>';
                                            } else {
                                                echo '<h6 id="issueStatus" class="card-header text-white bg-danger">Issue Open</h6>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col md-4">
                                        <div class="card shadow-sm">
                                            <!-- Header -->
                                            <h6 class="card-header text-left">Reported by: <span id="issueSource" class="text-info"><?php echo $selectedissue['source']; ?></span></h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="row padding my-row">
                                    <div class="col md-6">
                                        <div class="card shadow-sm">
                                            <h6 class="card-header text-left">Description</h6>

                                            <div id="issueDescription" class="card-body">
                                                <?php echo $selectedissue['description']; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col md-6">
                                        <div class="card shadow-sm">
                                            <h6 class="card-header text-left"> Notes</h6>

                                            <div class="card-body">
                                                <textarea id="issueNotes" type=""><?php echo $selectedissue['notes']; ?></textarea>
                                                <button onclick="updateIssueNotes();" >Save</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="issueFooter" class="card-footer">
                                <?php echo $selectedissue['issuedatetime']; ?>
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