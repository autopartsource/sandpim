<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
$navCategory = 'applications';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$userid=$_SESSION['userid'];

$vcdb=new vcdb;
$pim = new pim;
$user=new user;

$makeid=intval($_GET['makeid']);
if(isset($_GET['modelid'])){$modelid=intval($_GET['modelid']);}
if(isset($_GET['yearid'])){$yearid=intval($_GET['yearid']);}
if(isset($_GET['equipmentid'])){$equipmentid=intval($_GET['equipmentid']);}

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
   if($appid=$pim->newApp($basevehicleid, $parttypeid, $positionid, $quantityperapp, $partnumber, $cosmetic, $attributes))
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
              xhr.open('GET', 'ajaxSelectUnselectUserPartcategory.php?userid='+userid+'&partcategory='+partcategory+'&action=select');
              xhr.send();
             }
             else
             { // category has been clocked off
              var xhr = new XMLHttpRequest();
              console.log(partcategory);

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
            
        </script>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>Applications (<?php echo $vcdb->makeName($makeid).', '.$vcdb->modelName($modelid).', '.$yearid;?>)</h1>
        
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div style="padding:20px;">
                        <form action="showAppsByBasevehicle.php">
                         <?php
                            foreach ($partcategories as $partcategory) {
                                $checked = '';
                                if ($partcategory['selected']) {
                                    $checked = ' checked';
                                }
                                echo '<div style="padding:5px"><input type="checkbox" id="partcategory_' . $partcategory['id'] . '" onclick="selectUnselectPartcategory(\'' . $userid . '\',\'' . $partcategory['id'] . '\')" name="partcategory_' . $partcategory['id'] . '"' . $checked . '><label style="padding:5px;border: 1px solid;margin:3px; border-radius:5px"for="partcategory_' . $partcategory['id'] . '">' . $partcategory['name'] . '<img style="padding:0px 5px 0px" height="17px" src="' . $partcategory['logouri'] . '"></label></div>';
                            }
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
                         <div style="padding-top:10px;"><input type="submit" name="submit" value="Show Applications"/></div>
                        </form>
                    </div>
                    <div onclick="showhideNewApp()">...</div>

                    <form action="appsSelectCategory.php">
                        <div id="newapp" style="display:none; padding:5px;">
                            <div style="text-align: left;padding:3px;font-weight: bold;">Create new app to this vehicle</div>
                            <div style="text-align: left;padding:5px;">Part Number <input type="text" id="partnumber" name="partnumber" size="15" onkeyup="validateCreate('partnumber')"/></div>
                            <div style="text-align: left;padding:5px;">Position <select id="positionid" name="positionid" onchange="validateCreate('positionid')"><option value="0">--- Select ---</option><?php foreach($favoritepositions as $position){?><option value="<?php echo $position['id'];?>"><?php echo $position['name'];?></option><?php }?></select></div>
                            <div style="float:left; text-align: left; padding:5px;">Part Type <select id="parttypeid" name="parttypeid" onchange="validateCreate('parttypeid')"><option value="0">--- Select ---</option><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"><?php echo $parttype['name'];?></option><?php }?></select></div><div style="float:left;padding-left:10px;"><a href="./pcdbTypeBrowser.php?searchtype=selected&searchterm=&submit=Search"><img src="./settings.png" width="18" alt="settings"/></a></div><div style="clear:both;"></div> 
                            <div style="text-align: left;padding:5px;">Quantity <input style="text-align: right;" type="text" id="quantityperapp" name="quantityperapp" size="2"/> <input type="submit" name="submit" value="Create" id="createapp" /></div>
                            <input type="hidden" name="makeid" value="<?php echo $makeid;?>"/><input type="hidden" name="modelid" value="<?php echo $modelid;?>"/><input type="hidden" name="yearid" value="<?php echo $yearid;?>"/>
                        </div>
                    </form>
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
