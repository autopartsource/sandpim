<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
$navCategory = 'applications';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'appsSelectCategory.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$userid=$_SESSION['userid'];

$vcdb=new vcdb;
$user=new user;

$makeid=intval($_GET['makeid']);
if(isset($_GET['modelid'])){$modelid=intval($_GET['modelid']);}
if(isset($_GET['yearid'])){$yearid=intval($_GET['yearid']);}
if(isset($_GET['equipmentid'])){$equipmentid=intval($_GET['equipmentid']);}

$clipboardapps=$pim->getClipboard($userid, 'app');

if(isset($_GET['submit']) && $_GET['submit']=='Create' )
{
 $makeid=intval($_GET['makeid']);
 $modelid=intval($_GET['modelid']);
 $yearid=intval($_GET['yearid']);
 $partnumber=trim(strtoupper($_GET['partnumber']));
 $parttypeid=intval($_GET['parttypeid']);
 $positionid=intval($_GET['positionid']);
 $quantityperapp=intval($_GET['quantityperapp']);
 
 $cosmetic=0;
 $attributes=array();
 
 if($pim->validPart($partnumber))
 {
  if($basevehicleid=$vcdb->getBasevehicleidForMidMidYid($makeid, $modelid, $yearid))
  {
   if($appid=$pim->newApp($basevehicleid, $parttypeid, $positionid, $quantityperapp, $partnumber, $cosmetic, $attributes,''))
   {
     $appoid=$pim->getOIDofApp($appid);
     $pim->logAppEvent($appid, $userid, 'app created with appsSelectCategory.php form', $appoid);
     
     $partoid=$pim->getOIDofPart($partnumber);
     $pim->logPartEvent($partnumber, $userid, 'app id '.$appid.' was created connecting ['.$vcdb->niceMMYofBasevid($basevehicleid).'] to part', $partoid);
     echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./showApp.php?appid=".$appid."'\" /></head><body></body></html>"; exit;
   }
  }
 }
}

if(isset($_GET['submit']) && $_GET['submit']=='Create from clipboard' )
{
 if($basevehicleid=$vcdb->getBasevehicleidForMidMidYid($makeid, $modelid, $yearid))
 {
  $appids=array();
  foreach($clipboardapps as $clipboardapp)
  {
   $appids[]=$clipboardapp['objectkey'];
  }
  
  if(count($appids)>0)
  {
   $newappids=$pim->cloneAppsToNewBasevehicle($basevehicleid, $appids);
   if(count($newappids)>0)
   {
    foreach($newappids as $newappid)
    {
     $appoid=$pim->getOIDofApp($newappid);
     $pim->logAppEvent($newappid, $userid, 'app cloned with appsSelectCategory.php form clipboard', $appoid);        
    }
   }
//   echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./showApp.php?appid=".$appid."'\" /></head><body></body></html>"; exit;
  }
 }
}

$partcategories=$user->getUserVisiblePartcategories($userid);
$favoritepositions=$pim->getFavoritePositions();
$favoriteparttypes=$pim->getFavoriteParttypes();

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        <script>
            function selectUnselectPartcategory(userid,partcategory)
            {
             if(document.getElementById('partcategory_'+partcategory).checked) 
             { // category has been clocked on 
              console.log(partcategory);
              var xhr = new XMLHttpRequest();
              
              document.getElementById('categorySelectButton_'+partcategory).className = "btn btn-success";
              console.log(document.getElementById('categorySelectButton_'+partcategory).className);
              
              xhr.open('GET', 'ajaxSelectUnselectUserPartcategory.php?userid='+userid+'&partcategory='+partcategory+'&action=select');
              xhr.send();
             }
             else
             { // category has been clocked off
              var xhr = new XMLHttpRequest();
              console.log(partcategory);
              
              document.getElementById('categorySelectButton_'+partcategory).className = "btn btn-outline-secondary";
              console.log(document.getElementById('categorySelectButton_'+partcategory).className);

              xhr.open('GET', 'ajaxSelectUnselectUserPartcategory.php?userid='+userid+'&partcategory='+partcategory+'&action=unselect');
              xhr.send();
             }
            }
            
            
            function showhideNewApp()
            {
             var x = document.getElementById("newapp");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
            }
            
            function validateCreate(source)
            {
             var partnumber = document.getElementById("partnumber").value;
             var position = document.getElementById("positionid").value;
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxGetPart.php?partnumber='+partnumber);
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);

        
              if(response!==false)
              {// valid part number
               // use the part's typeid to set the parttypeid selector

               if(source=='partnumber')
               {// event that got us here was keying activity in partnumber box
                document.getElementById('parttypeid').value=response.parttypeid;
                document.getElementById('positionid').value=response.typicalposition;
                            position = response.typicalposition;
                            document.getElementById('quantityperapp').value = response.typicalqtyperapp;
                        }

                        if (position != 0)
                        {
                            document.getElementById("createapp").disabled = false;
                        } else
                        {
                            document.getElementById("createapp").disabled = true;
                        }
                    } else
                    {// the content of the partnumber input is not a valid item

                        if (source == 'partnumber')
                        {// event that got us here was keying activity in partnumber box
                            document.getElementById("createapp").disabled = true;
                            document.getElementById('parttypeid').value = 0;
                            document.getElementById('positionid').value = 0;
                            document.getElementById('quantityperapp').value = '';
                        }
                    }
                };
                xhr.send();
            }
            
            function populatePasteDiv() {
                var pasteDiv = document.getElementById("paste");

                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxGetClipboard.php?objecttype=app');
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
        
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Apps > <?php echo '<a href="appsIndex.php">'.$vcdb->makeName($makeid).'</a> > <a href="mmySelectModel.php?makeid='.$makeid.'">'.$vcdb->modelName($modelid).'</a> > <a href="mmySelectYear.php?makeid='.$makeid.'&modelid='.$modelid.'">'.$yearid;?></a></h3>

                        <div class="card-body">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="existing-tab" data-bs-toggle="tab" href="#existing" role="tab" aria-controls="existing" aria-selected="true">Existing Apps</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="create-tab" data-bs-toggle="tab" href="#create" role="tab" aria-controls="create" aria-selected="false">Create Application</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="paste-tab" data-bs-toggle="tab" href="#paste" role="tab" aria-controls="create" aria-selected="false">Create from Clipboard</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="existing" role="tabpanel" aria-labelledby="existing-tab">
                                    <form action="showAppsByBasevehicle.php">
                                        <div style="padding:20px;"><input type="submit" name="submit" value="Show Applications"/></div>
                                        <?php
                                            echo '<div class="btn-group-toggle">';
                                            foreach ($partcategories as $partcategory) {
                                                $checked = '';
                                                if ($partcategory['selected']) {
                                                    $checked = ' checked';
                                                    $buttonClass = 'btn btn-success';
                                                }
                                                else {
                                                    $buttonClass = 'btn btn-outline-secondary';
                                                }
                                                echo '<div style="padding:5px"><label id="categorySelectButton_' . $partcategory['id'] . '" class="'. $buttonClass .'" style="padding:5px;border: 1px solid;margin:3px; border-radius:5px"for="partcategory_' . $partcategory['id'] . '">' . $partcategory['name'] . '<img style="padding:0px 5px 0px" height="17px" src="' . $partcategory['logouri'] . '"><input type="checkbox" id="partcategory_' . $partcategory['id'] . '" onclick="selectUnselectPartcategory(\'' . $userid . '\',\'' . $partcategory['id'] . '\')" name="partcategory_' . $partcategory['id'] . '"' . $checked . ' style="display:none"></label></div>';
                                            }
                                            echo '</div>';
                                        ?>
                                           <input type="hidden" name="makeid" value="<?php echo $makeid; ?>"/>
                                        <?php
                                            if (isset($modelid)) {
                                                echo '<input type="hidden" name="modelid" value="' . $modelid . '"/>';
                                            }
                                            if (isset($yearid)) {
                                                echo '<input type="hidden" name="yearid" value="' . $yearid . '"/>';
                                            }
                                            if (isset($equipmentid)) {
                                                echo '<input type="hidden" name="equipmentid" value="' . $equipmentid . '"/>';
                                            }
                                        ?>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="create" role="tabpanel" aria-labelledby="create-tab">
                                    <form action="appsSelectCategory.php">
                                        <div id="newapp" style="padding:5px;">
                                            <div style="text-align: left;padding:3px;font-weight: bold;">Create new app to this vehicle</div>
                                            <div style="text-align: left;padding:5px;">Part Number <input type="text" id="partnumber" name="partnumber" size="15" onkeyup="validateCreate('partnumber')"/></div>
                                            <div style="text-align: left;padding:5px;">Position <select id="positionid" name="positionid" onchange="validateCreate('positionid')"><option value="0">--- Select ---</option><?php foreach($favoritepositions as $position){?><option value="<?php echo $position['id'];?>"><?php echo $position['name'];?></option><?php }?></select></div>
                                            <div style="float:left; text-align: left; padding:5px;">Part Type <select id="parttypeid" name="parttypeid" onchange="validateCreate('parttypeid')"><option value="0">--- Select ---</option><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"><?php echo $parttype['name'];?></option><?php }?></select></div><div style="float:left;padding-left:10px;"><a href="./pcdbTypeBrowser.php?searchtype=selected&searchterm=&submit=Search"><img src="./settings.png" width="18" alt="settings"/></a></div><div style="clear:both;"></div> 
                                            <div style="text-align: left;padding:5px;">Quantity <input style="text-align: right;" type="text" id="quantityperapp" name="quantityperapp" size="2"/> <input type="submit" name="submit" value="Create" id="createapp" /></div>
                                            <input type="hidden" name="makeid" value="<?php echo $makeid;?>"/><input type="hidden" name="modelid" value="<?php echo $modelid;?>"/><input type="hidden" name="yearid" value="<?php echo $yearid;?>"/>
                                        </div>
                                    </form>
                                </div>
                                
                                <div class="tab-pane fade" id="paste" role="tabpanel" aria-labelledby="paste-tab">
                                    <?php if(count($clipboardapps)>0){?>
                                    <form>
                                        <input type="hidden" name="makeid" value="<?php echo $makeid;?>"/>
                                        <input type="hidden" name="modelid" value="<?php echo $modelid;?>"/>
                                        <input type="hidden" name="yearid" value="<?php echo $yearid;?>"/>
                                        <div style="padding:20px;"><input type="submit" name="submit" value="Create from clipboard"/></div>
                                    </form>
                                    <?php }else{?>
                                    <div style="padding:20px;">There are no apps on the clipboard.</div>
                                    
                                    <?php }?>
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
        </div>
        <!-- End of Content Container -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>