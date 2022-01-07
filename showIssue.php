<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
$navCategory = 'issues';


$pim = new pim;
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// ip-based ACL enforcement - bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'showIssue.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$selectedissue = $pim->getIssueById(intval($_GET['id']));

$tree = array();

if(isset($_GET['showclosed'])) {
    $statuses=array(0,1,2,3);
} else {
    $statuses=array(1,2);
}

$issues = $pim->getIssues('%', '%', '%', $statuses ,9999);

foreach ($issues as $issue) {
    $issuebits = explode('/', $issue['issuetype']);

    if (count($issuebits)) {

        for ($i = 0; $i < count($issuebits); $i++) {

            switch ($i) {
                case 0:
                    // highest (root) level 
                    if (!array_key_exists($issuebits[0], $tree)) {
                        $tree[$issuebits[0]] = array();
                    }
                    if ($i == count($issuebits) - 1) {// this is the lowest (least-significant) level of the path
                        $tree[$issuebits[0]][] = $issue;
                    }

                    break;
                case 1:
                    // sub-level
                    if (!array_key_exists($issuebits[1], $tree[$issuebits[0]])) {
                        $tree[$issuebits[0]][$issuebits[1]] = array();
                    }
                    if ($i == count($issuebits) - 1) {// this is the lowest (least-significant) level of the path
                        $tree[$issuebits[0]][$issuebits[1]][] = $issue;
                    }

                    break;
                case 2:
                    // sub-sub-level
                    if (!array_key_exists($issuebits[2], $tree[$issuebits[0]][$issuebits[1]])) {
                        $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]] = array();
                    }
                    if ($i == count($issuebits) - 1) {// this is the lowest (least-significant) level of the path
                        $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][] = $issue;
                    }

                    break;
                case 3:
                    // sub-sub-sub-level
                    if (!array_key_exists($issuebits[3], $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]])) {
                        $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]] = array();
                    }
                    if ($i == count($issuebits) - 1) {// this is the lowest (least-significant) level of the path
                        $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]][] = $issue;
                    }

                    break;
                case 4:
                    // sub-sub-sub-sub-level
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
                
                if(id) {

                    var issueData = atob(document.getElementById('issue_' + id).getAttribute('data-issue'));
                    var issueObject = JSON.parse(issueData);
                    
                    document.getElementById("IssueContent").setAttribute("style","");

                    document.getElementById("issueId").innerHTML = issueObject.id;
                    document.getElementById("issueBreadcrumb").innerHTML = issueObject.issuetype;
                    document.getElementById("issueDescription").innerHTML = issueObject.description;
                    document.getElementById("issueSource").innerHTML = issueObject.source;
                    document.getElementById("issueDatetime").innerHTML = issueObject.issuedatetime;
                    document.getElementById("issueNotes").innerHTML = issueObject.notes;
                    
                    renderStatusButtons(issueObject.status); // sets current status state

                    if(issueObject.issuetype.substring(0, 5)=='PART/')
                    {
                     document.getElementById("issuekeydisplay").innerHTML='Partnumber: <a href="./showPart.php?partnumber='+issueObject.issuekeyalpha+'">'+issueObject.issuekeyalpha+'</a>';
                    }
                    else
                    {
                     if(issueObject.issuetype.substring(0, 4)=='APP/')
                     {
                      document.getElementById("issuekeydisplay").innerHTML='App: <a href="./showApp.php?appid='+issueObject.issuekeynumeric+'">'+issueObject.issuekeynumeric+'</a>';
                     }
                     else
                     {
                      if(issueObject.issuetype.substring(0, 6)=='ASSET/')
                      {
                       document.getElementById("issuekeydisplay").innerHTML='Asset: <a href="./showAsset.php?assetid='+issueObject.issuekeyalpha+'">'+issueObject.issuekeyalpha+'</a>';
                      }
                      else
                      {// unknown type
                       document.getElementById("issuekeydisplay").innerHTML=issueObject.issuekeyalpha+','+issueObject.issuekeynumeric;
                      }
                     }
                    }

                    document.getElementById("issueId").setAttribute("data-issueid",issueObject.id);
                    document.getElementById('issue_' + id).classList.add("nodeSelected");
                    
                    removePreviousSelection();
                    renderSelectedTree(issueObject);
                    
                    var url = window.location.href;
                    if(url.toString().includes('&showclosed')) {
                        document.getElementById("toggleClosed").className = "btn btn-success";
                    }
                    else {
                        document.getElementById("toggleClosed").className = "btn btn-secondary";
                    }
                    
                } else {
                    document.getElementById("IssueContent").setAttribute("style","display:none;");
                }
                
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
            
            function updateIssueStatus(value) {
                var id = document.getElementById("issueId").getAttribute("data-issueid");

                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxUpdateIssue.php?issueid=' + id + '&elementid=status&value=' + value);
                xhr.onload = function ()
                {
                };
                xhr.send();
                
                renderStatusButtons(value);
            }
            
            function snoozeIssue(value) {
                var id = document.getElementById("issueId").getAttribute("data-issueid");

                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxUpdateIssue.php?issueid=' + id + '&elementid=snoozedays&value=' + value);
                xhr.onload = function ()
                {
                };
                xhr.send();
                
                renderStatusButtons(3);
            }
            
            function renderStatusButtons(value) {
                if (value == 1) {
                    document.getElementById("label_status_closed").className = "btn btn-danger";
                    document.getElementById("label_status_review").className = "btn btn-danger";
                    document.getElementById("label_status_open").className = "btn btn-danger active";
                    document.getElementById("label_status_snooze").className = "btn btn-danger";
                    document.getElementById("input_open").checked = true;

                } else if (value == 2) {
                    document.getElementById("label_status_closed").className = "btn btn-warning";
                    document.getElementById("label_status_review").className = "btn btn-warning active";
                    document.getElementById("label_status_open").className = "btn btn-warning";
                    document.getElementById("label_status_snooze").className = "btn btn-warning";
                    document.getElementById("input_review").checked = true;

                } else if (value == 3) {
                    document.getElementById("label_status_closed").className = "btn btn-info";
                    document.getElementById("label_status_review").className = "btn btn-info";
                    document.getElementById("label_status_open").className = "btn btn-info";
                    document.getElementById("label_status_snooze").className = "btn btn-info active";
//                    document.getElementById("input_snooze").checked = true;
                } else {
                    document.getElementById("label_status_closed").className = "btn btn-success active";
                    document.getElementById("label_status_review").className = "btn btn-success";
                    document.getElementById("label_status_open").className = "btn btn-success";
                    document.getElementById("label_status_snooze").className = "btn btn-success";
                    document.getElementById("input_closed").checked = true;
                }
            }

            function renderSelectedTree(issueObject) {
//                console.log(issueObject.id);

                issueTypeArray = issueObject.issuetype.split("/");

                var totalPath = issueTypeArray[0];
                document.getElementById(totalPath).setAttribute("style", "display: block;");
                document.getElementById('nav_' + totalPath).setAttribute("class", "dropdown-btn sidenavActive");

                for (var i = 1; i < issueTypeArray.length; i++) {
                    totalPath += "-" + issueTypeArray[i];
                    document.getElementById(totalPath).setAttribute("style", "display: block;");
                    document.getElementById('nav_' + totalPath).setAttribute("class", "dropdown-btn sidenavActive");
                }

                document.getElementById('issue_' + issueObject.id).classList.add("nodeSelected");

            }

            function removePreviousSelection() {
                var selectedArray = document.getElementsByClassName("sidenavActive");
                var selectedEndNode = document.getElementsByClassName("nodeSelected");
                
                for (var i = 0; i < selectedArray.length; i++) {
                    var testing = selectedArray[i].getAttribute('data-target');
                    var nohash = testing.replace("#","")
                    console.log(nohash);
                    document.getElementById(nohash).setAttribute("style", "display: none");
//                    console.log(str_replace("#","",selectedArray[i].getAttribute('data-target')));
                }

                for (var i = 0; i < selectedEndNode.length; i++) {
//                    console.log(selectedEndNode[i].getAttribute('id'));
                    document.getElementById(selectedEndNode[i].getAttribute('id')).setAttribute("class", "navbarEndNode ps-5");
                }
                for (var i = 0; i < selectedEndNode.length; i++) {
//                    console.log(selectedEndNode[i].getAttribute('id'));
                    document.getElementById(selectedEndNode[i].getAttribute('id')).setAttribute("class", "navbarEndNode ps-5");
                }

                for (var i = 0; i < selectedArray.length; i++) {
//                    console.log(selectedArray[i].getAttribute('id'));
                    document.getElementById(selectedArray[i].getAttribute('id')).setAttribute("class", "dropdown-btn");
                }
                for (var i = 0; i < selectedArray.length; i++) {
//                    console.log(selectedArray[i].getAttribute('id'));
                    document.getElementById(selectedArray[i].getAttribute('id')).setAttribute("class", "dropdown-btn");
                }
            }
            
            function toggleClosed() {
                var url = window.location.href;
                if(url.toString().includes('showclosed')) {
                    if(url.toString().includes('&showclosed')) {
                        url = url.toString().replace('&showclosed','');
                    } else {
                        url = url.toString().replace('showclosed','');
                    }
                }
                else {
                    if (url.indexOf('?') > -1){
                       url += '&showclosed'
                    }else{
                       url += '?showclosed'
                    } 
                }
                window.location.href = url;
            }

        </script>
    </head>
    <body onload="renderIssue(<?php echo $selectedissue['id']?>)">
        <!-- Navigation Bar -->
<?php include('topnav.php'); ?>

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 colLeft" style="background-color: #343a40; min-height: 90vh;">
                    <div id="sidebar" class="sidenav scroll" role="group">
                    <?php
                    foreach ($tree as $key0 => $data0) {
                        if (is_array($data0) && array_key_exists('issuehash', $data0)) {
                            echo '<a id="issue_' . $data0['id'] . '" data-issue="' . base64_encode(json_encode($data0)) . '" class="navbarEndNode ps-2" onclick="renderIssue(\'' . intval($data0['id']) . '\');">' . $data0['description'] . '</a>';
                        } else {
                            echo '<button id="nav_'.$key0.'" class="dropdown-btn ps-2" data-toggle="collapse" data-target="#' . $key0 . '">' . $key0 . '<i class="fa fa-caret-down"></i></button>';
                            echo '<div class="collapse" id="' . $key0 . '" aria-expanded="false">';

                            foreach ($data0 as $key1 => $data1) {
                                if (is_array($data1) && array_key_exists('issuehash', $data1)) {// this is an end-node
                                    echo '<a id="issue_' . $data1['id'] . '" data-issue="' . base64_encode(json_encode($data1)) . '" class="navbarEndNode ps-3" onclick="renderIssue(\'' . intval($data1['id']) . '\');">' . $data1['description'] . '</a>';
                                } else {
                                    echo '<button id="nav_' . $key0 . '-' . $key1 . '" class="dropdown-btn ps-3" data-toggle="collapse" data-target="#' . $key0 . '-' . $key1 . '">' . $key1 . '<i class="fa fa-caret-down"></i></button>';
                                    echo '<div class="collapse" id="' . $key0 . '-' . $key1 . '" aria-expanded="false">';

                                    foreach ($data1 as $key2 => $data2) {
                                        if (is_array($data2) && array_key_exists('issuehash', $data2)) {// this is an end-node
                                            echo '<a id="issue_' . $data2['id'] . '" data-issue="' . base64_encode(json_encode($data2)) . '" class="navbarEndNode ps-4" onclick="renderIssue(\'' . intval($data2['id']) . '\');">' . $data2['description'] . '</a>';
                                        } else {
                                            echo '<button id="nav_' . $key0 . '-' . $key1 . '-' . $key2 . '" class="dropdown-btn ps-4" data-toggle="collapse" data-target="#' . $key0 . '-' . $key1 . '-' . $key2 . '">' . $key2 . '<i class="fa fa-caret-down"></i></button>';
                                            echo '<div class="collapse" id="' . $key0 . '-' . $key1 . '-' . $key2 . '" aria-expanded="false">';

                                            foreach ($data2 as $key3 => $data3) {
                                                if (is_array($data3) && array_key_exists('issuehash', $data3)) {// this is an end-node
                                                    echo '<a id="issue_' . $data3['id'] . '" data-issue="' . base64_encode(json_encode($data3)) . '" class="navbarEndNode ps-5" onclick="renderIssue(\'' . intval($data3['id']) . '\');">' . $data3['description'] . '</a>';
                                                } else {
                                                    echo '<button id="nav_' . $key0 . '-' . $key1 . '-' . $key2 . '-' . $key3 . '" class="dropdown-btn ps-5" data-toggle="collapse" data-target="#' . $key0 . '-' . $key1 . '-' . $key2 . '-' . $key3 . '">' . $key3 . '<i class="fa fa-caret-down"></i></button>';
                                                    echo '<div class="collapse" id="' . $key0 . '-' . $key1 . '-' . $key2 . '-' . $key3 . '" aria-expanded="false">';

                                                    foreach ($data3 as $key4 => $data4) {
                                                        if (is_array($data4) && array_key_exists('issuehash', $data4)) {// this is an end-node
                                                            echo '<a id="issue_' . $data4['id'] . '" data-issue="' . base64_encode(json_encode($data4)) . '" class="navbarEndNode ps-6" onclick="renderIssue(\'' . intval($data4['id']) . '\');">' . $data4['description'] . '</a>';
                                                        } 
                                                    }//end of 4th inner loop
                                                    echo '</div>';
                                                }
                                            }//end of 3rd inner loop
                                            echo '</div>';
                                        }
                                    }//end of 2nd inner loop
                                    echo '</div>';
                                }
                            }//end of 1st inner loop
                            echo '</div>';
                        }
                    }//end of Master Loop
                        ?>
                    </div>
                    <?php
//                        echo '<ul class="nav flex-column flex-nowrap overflow-hidden btn-group-vertical" role="group">';
//                        foreach ($tree as $key0 => $data0) {
//                            
//                            if (is_array($data0) && array_key_exists('issuehash', $data0)) {
//                                echo '<li class="nav-item">';
//                                echo '<div id="issue_' . $data0['id'] . '" data-issue="' . base64_encode(json_encode($data0)) . '" class="btn btn-secondary" onclick="renderIssue(\'' . intval($data0['id']) . '\');">' . $data0['description'] . '</div>';
//                                echo '</li>';
//                            } else {
//                                echo '<li class="nav-item">';
//                                echo '<a id="nav_'.$key0.'" class="nav-link collapsed text-truncate side-nav-link" href="#' . $key0 . '" data-toggle="collapse" data-target="#' . $key0 . '"><span class="d-none d-sm-inline">' . $key0 . '</span></a>';
//                                echo '<div class="collapse" id="' . $key0 . '" aria-expanded="false">';
//                            
//                                foreach ($data0 as $key1 => $data1) {
//                                    echo '<ul class="flex-column ps-1 nav">';
//
//                                    if (is_array($data1) && array_key_exists('issuehash', $data1)) {// this is an end-node
//                                        echo '<li class="nav-item">';
//                                        echo '<div id="issue_' . $data1['id'] . '" data-issue="' . base64_encode(json_encode($data1)) . '" class="btn btn-secondary" onclick="renderIssue(\'' . intval($data1['id']) . '\');">' . $data1['description'] . '</div>';
//                                        echo '</li>';
//                                    } else {// this node contains children 
//                                        echo '<li class="nav-item">';
//                                        echo '<a class="nav-link collapsed text-truncate side-nav-link" href="#' . $key0 . '-' . $key1 . '" data-toggle="collapse" data-target="#' . $key0 . '-' . $key1 . '"><span class="d-none d-sm-inline">' . $key1 . '</span></a>';
//                                        echo '<div class="collapse" id="' . $key0 . '-' . $key1 . '" aria-expanded="false">';
//
//                                        foreach ($data1 as $key2 => $data2) {
//                                            echo '<ul class="flex-column nav ps-2">';
//
//                                            if (is_array($data2) && array_key_exists('issuehash', $data2)) {// this is an end-node
//                                                echo '<li class="nav-item">';
//                                                echo '<div id="issue_' . $data2['id'] . '" data-issue="' . base64_encode(json_encode($data2)) . '" class="btn btn-secondary" onclick="renderIssue(\'' . intval($data2['id']) . '\');">' . $data2['description'] . '</div>';
//                                                echo '</li>';
//
//                                            } else {// this node contains children 
//                                                echo '<li class="nav-item">';
//                                                echo '<a class="nav-link collapsed text-truncate side-nav-link" href="#' . $key0 . '-' . $key1 . '-' . $key2 . '" data-toggle="collapse" data-target="#' . $key0 . '-' . $key1 . '-' . $key2 . '"><span class="d-none d-sm-inline">' . $key2 . '</span></a>';
//                                                echo '<div class="collapse" id="' . $key0 . '-' . $key1 . '-' . $key2 . '" aria-expanded="false">';
//
//                                                foreach ($data2 as $key3 => $data3) {
//                                                    echo '<ul class="flex-column nav ps-3">';
//
//                                                    if (is_array($data3) && array_key_exists('issuehash', $data3)) {// this is an end-node
//                                                        echo '<li class="nav-item">';
//                                                        echo '<div id="issue_' . $data3['id'] . '" data-issue="' . base64_encode(json_encode($data3)) . '" class="btn btn-secondary" onclick="renderIssue(\'' . intval($data3['id']) . '\');">' . $data3['description'] . '</div>';
//                                                        echo '</li>';
//                                                    } else {// this node contains children
//                                                        echo '<li class="nav-item">';
//                                                        echo '<a class="nav-link collapsed text-truncate side-nav-link" href="#' . $key0 . '-' . $key1 . '-' . $key2 . '-' . $key3 . '" data-toggle="collapse" data-target="#' . $key0 . '-' . $key1 . '-' . $key2 . '-' . $key3 . '"><span class="d-none d-sm-inline">' . $key3 . '</span></a>';
//                                                        echo '<div class="collapse" id="' . $key0 . '-' . $key1 . '-' . $key2 . '-' . $key3 . '" aria-expanded="false">';
//
//                                                        foreach ($data3 as $key4 => $data4) {
//                                                            echo '<ul class="flex-column nav ps-4">';
//
//                                                            if (is_array($data4) && array_key_exists('issuehash', $data4)) {// this is an end-node
//                                                                echo '<li class="nav-item">';
//                                                                echo '<div id="issue_' . $data4['id'] . '" data-issue="' . base64_encode(json_encode($data4)) . '" class="btn btn-secondary" onclick="renderIssue(\'' . intval($data4['id']) . '\');">' . $data4['description'] . '</div>';
//                                                                echo '</li>';
//                                                            }
//
//                                                            echo '</ul>';
//
//                                                        } //end of loop 5
//                                                        
//                                                        echo '</div>';
//                                                        echo '</li>';
//                                                    }//end of loop 4 ELSE
//                                                    
//                                                    echo '</ul>';
//                                                } //end of loop 4
//                                                
//                                                echo '</div>';
//                                                echo '</li>';
//                                            }//end of loop 3 ELSE
//                                            
//                                            echo'</ul>';
//                                        } //end of loop 3
//
//                                        echo '</div>';
//                                        echo '</li>';
//                                    }//end of loop 2 ELSE
//                                    
//                                    echo '</ul>';
//                                } //end of loop 2
//
//                                echo '</div>';
//                                echo '</li>';
//                            } //end of loop 1 ELSE
//                            
//                        } // end of loop 1
//                        echo '</ul>';
                    ?>   
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-10 my-col colMain">
                    <div id="IssueContent" class="card shadow-sm my-col" style="">
                        <div class="card-header text-start">
                            <span id="issueBreadcrumb"></span>
                            <div class="btn-group" role="group" style="float:right;">
                                <label id="toggleClosed" class="btn btn-secondary">
                                    <input type="radio" class="btn-check" autocomplete="off" onclick="toggleClosed()"> Show Closed
                                </label>
                            </div>
                        </div>

                        <div class="card shadow-sm">
                            <!-- Header -->
                            <h5 class="card-header text-start">
                                Issue ID: <span id="issueId" class="text-info" data-issueid="<?php echo $selectedissue['id']; ?>"><?php echo $selectedissue['id']; ?></span>
                                <form id="IssueSetStatus" style="float:right;" >
                                    <div class="btn-group" role="group">
                                        <label id="label_status_open" class="btn">
                                            <input type="radio" class="btn-check"  name="status" id="input_open" value="1" onclick="updateIssueStatus(1)"> Open
                                        </label>
                                        <label id="label_status_review" class="btn">
                                            <input type="radio" class="btn-check"  name="status" id="input_review" value="2" onclick="updateIssueStatus(2)"> In Review
                                        </label>
                                        <label id="label_status_closed" class="btn">
                                            <input type="radio" class="btn-check"  name="status" id="input_closed" value="0" onclick="updateIssueStatus(0)"> Closed
                                        </label>
                                    </div>
                                    <div class="btn-group" role="group">
                                        <button id="label_status_snooze" type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Snooze
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" value="1" onclick="snoozeIssue(1)">Until Tomorrow</a>
                                            <a class="dropdown-item" value="7" onclick="snoozeIssue(7)">Until Next Week</a>
                                            <a class="dropdown-item" value="30" onclick="snoozeIssue(30)">Until Next Month</a>
                                        </div>
                                    </div>
                                </form>
                             </h5>

                            <div class="card-body">
                                <div class="row padding my-row">
                                    <div class="col md-6">
                                        <div class="card shadow-sm">
                                            <!-- Header -->
                                            <h6 class="card-header text-start"><div id="issuekeydisplay"></div></h6>
                                        </div>
                                    </div>
                                    <div class="col md-6">
                                        <div class="card shadow-sm">
                                            <!-- Header -->
                                            <h6 class="card-header text-start">Reported by: <span id="issueSource" class="text-info"></span>
                                                <div>on <span id="issueDatetime" class="text-info"></span><div>
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="row padding my-row">
                                    <div class="col md-6">
                                        <div class="card shadow-sm">
                                            <h6 class="card-header text-start">Description</h6>
                                            <div id="issueDescription" class="card-body"></div>
                                        </div>
                                    </div>
                                    <div class="col md-6">
                                        <div class="card shadow-sm">
                                            <h6 class="card-header text-start">Notes</h6>

                                            <div class="card-body">
                                                <textarea id="issueNotes"></textarea>
                                                <button onclick="updateIssueNotes();" >Save</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
        <script>
            /* Loop through all dropdown buttons to toggle between hiding and showing its dropdown content - This allows the user to have multiple dropdowns without any conflict */
            var dropdown = document.getElementsByClassName("dropdown-btn");
            var i;

            for (i = 0; i < dropdown.length; i++) {
              dropdown[i].addEventListener("click", function() {
                this.classList.toggle("sidenavActive");
                var dropdownContent = this.nextElementSibling;
                if (dropdownContent.style.display === "block") {
                  dropdownContent.style.display = "none";
                } else {
                  dropdownContent.style.display = "block";
                }
              });
            }
        </script>
    </body>
</html>