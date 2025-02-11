<?php
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
$navCategory = 'parts';

$pim = new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'newPart.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb = new pcdb;
$user=new user;

$partcategories = $pim->getPartCategories();
$favoriteparttypes=$pim->getFavoriteParttypes();
$partnumber=''; if(isset($_GET['partnumber'])){$partnumber=trim(strtoupper($_GET['partnumber']));}


if(isset($_POST['partnumber'])  && trim($_POST['partnumber'])!='' && isset($_POST['parttypeid']) && isset($_POST['partcategory']))
{
 $partnumber=trim(strtoupper($_POST['partnumber']));
 $parttypeid=intval($_POST['parttypeid']);
 $partcategory=intval($_POST['partcategory']);
    // record user's preference for category and type
 $user->setUserPreference($_SESSION['userid'], 'last part create category',$partcategory);
 $user->setUserPreference($_SESSION['userid'], 'last part create part type',$parttypeid);
    
 if($pim->validPart($partnumber))
 {// part already esists - re-direct to showPart.php without making a fuss
  echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./showPart.php?partnumber=".$partnumber."'\" /></head><body></body></html>";
  exit;  
 }
 else
 {// part does not already exist. create it
  if($pim->createPart($partnumber, $partcategory, $parttypeid))
  {
   $oid=$pim->getOIDofPart($partnumber);
   $pim->logPartEvent($partnumber,$_SESSION['userid'],'part created by form input',$oid);
   echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./showPart.php?partnumber=".$partnumber."'\" /></head><body></body></html>";
   exit;  
  }
  else
  {// failuer creating part
   $errormessage='part was not created';
  }
 }
 
}

// get user's preference for category and type
$preferedpartcategory=$user->getUserPreference($_SESSION['userid'], 'last part create category');
$preferedparttype=$user->getUserPreference($_SESSION['userid'], 'last part create part type');

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        
        <script>
            function populatePasteDiv() {
                var pasteDiv = document.getElementById("paste");

                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxGetClipboard.php?objecttype=part');
                xhr.onload = function ()
                {
                    var response = JSON.parse(xhr.responseText);
                    if (parseInt(response.length) > 0) {
                        for (var i = 0; i < response.length; i++) {
                            pasteDiv.innerHTML += '<p id=pasteObject_' + response[i].id + '>' + response[i].description + '</p>';
                        }
                    }
                    else {
                        
                    } 
                };
                xhr.send();
            }
        </script>
    </head>
    <body onload="populatePasteDiv()">
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
                        <h3 class="card-header text-start">Create Part</h3>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="create-tab" data-bs-toggle="tab" href="#create" role="tab" aria-controls="create" aria-selected="true">New Part Form</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="paste-tab" data-bs-toggle="tab" href="#paste" role="tab" aria-controls="create" aria-selected="false">Create from Clipboard</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="create" role="tabpanel" aria-labelledby="create-tab">
                                    <form method="post">
                                        <table border="1" cellpadding="5">
                                            <tr><th>Partnumber</th><td><input type="text" name="partnumber" value="<?php echo $partnumber;?>"/></td></tr>
                                            <tr><th>Part Type</th><td><select name="parttypeid"><?php foreach ($favoriteparttypes as $parttype) { ?> <option value="<?php echo $parttype['id']; ?>"   <?php if($parttype['id']==$preferedparttype){echo ' selected';}?>    ><?php echo $parttype['name']; ?></option><?php } ?></select></td></tr>
                                            <tr><th>Part Category</th><td><select name="partcategory"><?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>" <?php if($partcategory['id']==$preferedpartcategory){echo ' selected';}?>><?php echo $partcategory['name']; ?></option><?php }?></select></td></tr>
                                        </table>
                                        <div style="padding-top:15px;"><input type="submit" name="submit" value="Next"/></div>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="paste" role="tabpanel" aria-labelledby="paste-tab">
                                    
                                </div>
                            </div>
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
