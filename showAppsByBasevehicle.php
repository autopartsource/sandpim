<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/qdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');


function selfURL($makeid, $modelid, $yearid, $partcategories)
{
    $catsstring = '';
    if (count($partcategories)) {
        foreach ($partcategories as $partcategory) {
            $catsstring .= '&partcategory_' . $partcategory . '=on';
        }
    }
    return 'showAppsByBasevehicle.php?makeid=' . $makeid . '&modelid=' . $modelid . '&yearid=' . $yearid . $catsstring;
}


function selfLink($makeid, $modelid, $yearid, $partcategories, $class, $displaytext)
{
    $url=selfURL($makeid, $modelid, $yearid, $partcategories);
    $classparm='class=""'; if($class!=''){$classparm='class="'.$class.'"';}
    return '<a '.$classparm.' href="'.$url.'">' . $displaytext . '</a>';
}



$navCategory = 'applications';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'showAppsByBasevehicle.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$userid = $_SESSION['userid'];

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

$vcdb = new vcdb;
$pcdb = new pcdb;
$qdb = new qdb;
$configGet=new configGet;
$logs = new logs;

$makeid = intval($_REQUEST['makeid']);
if (isset($_REQUEST['modelid'])) {
    $modelid = intval($_REQUEST['modelid']);
}
if (isset($_REQUEST['yearid'])) {
    $yearid = intval($_REQUEST['yearid']);
}
if (isset($_REQUEST['equipmentid'])) {
    $equipmentid = intval($_REQUEST['equipmentid']);
}


$partcategories = array();
foreach ($_REQUEST as $getname => $getval) {
    if (strpos($getname, 'partcategory_') === 0) {
        $bits = explode('_', $getname);
        $partcategories[] = $bits[1];
    }
}

$basevehicleid = $vcdb->getBasevehicleidForMidMidYid($makeid, $modelid, $yearid);
$vehcilehistory=$logs->getVehicleEvents($basevehicleid, 25);
$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');
$viototal=$pim->experianVehicleCount($viogeography, $vioyearquarter, $basevehicleid, array());


$makename = $vcdb->makeName($makeid);
$modelname = $vcdb->modelName($modelid);

$clipboardapps=$pim->getClipboard($userid, 'app');

if(isset($_REQUEST['submit']) && $_REQUEST['submit']=='Paste' )
{
 $clipboardappids=array();
 foreach($clipboardapps as $clipboardapp){$clipboardappids[]=$clipboardapp['objectkey'];}
  
 if(count($clipboardappids)>0)
 {
  $newappids=$pim->cloneAppsToNewBasevehicle($basevehicleid, $clipboardappids);
  if(count($newappids)>0)
  {
   foreach($newappids as $newappid)
   {
    $appoid=$pim->getOIDofApp($newappid);
    $pim->logAppEvent($newappid, $userid, 'app cloned with showAppsByBasevehicle.php from clipboard', $appoid);        
   }
  }
 }
}



$apps = $pim->getAppsByBasevehicleid($basevehicleid,$partcategories);
$fitmentrowkeys = array();
$fitmentcolumnkeys = array();
$appmatrix = array();
$dropzonenumber = 0;

$prevyearexists = $vcdb->getBasevehicleidForMidMidYid($makeid, $modelid, ($yearid - 1));
$nextyearexists = $vcdb->getBasevehicleidForMidMidYid($makeid, $modelid, ($yearid + 1));

if (count($apps)) 
{
    foreach ($apps as $app) 
    {
        $niceattributes = array();
        foreach ($app['attributes'] as $appattribute) {
            if ($appattribute['type'] == 'vcdb') {
                $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']);
            }

            if ($appattribute['type'] == 'qdb') {
                $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $qdb->qualifierText($appattribute['name'], explode('~', str_replace('|','',$appattribute['value']))), 'cosmetic' => $appattribute['cosmetic']);
            }
            
            if ($appattribute['type'] == 'note') {
                $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']);
            }
        }
        $nicefitmentstring = '';
        $nicefitmentarray = array();
        foreach ($niceattributes as $niceattribute) {
            $nicefitmentarray[] = $niceattribute['text'];
        }

        // build the distinct row keys
        $rowkey = implode('; ', $nicefitmentarray);
        $fitmentrowkeys[$rowkey] = urlencode(base64_encode(serialize($app['attributes'])));

        // build the distinct column keys
        $columnkey = $pcdb->positionName($app['positionid']) . "<br/>" . $pcdb->parttypeName($app['parttypeid']);
        $fitmentcolumnkeys[$columnkey] = urlencode(base64_encode(serialize(array('positionid' => $app['positionid'], 'parttypeid' => $app['parttypeid']))));

        $appmatrix[$rowkey][$columnkey][] = $app;
    }
}

ksort($fitmentrowkeys);
ksort($fitmentcolumnkeys);

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        <script>

            function allowDrop(ev)
            {
                ev.preventDefault();
            }

            function drag(ev)
            {
                ev.dataTransfer.setData("t", ev.target.id);
                ev.dataTransfer.setData("sourceapp", ev.target.getAttribute('data-sourceapp'));
                ev.dataTransfer.setData("sourcerow", ev.target.getAttribute('data-row'));
                ev.dataTransfer.setData("sourcecolumn", ev.target.getAttribute('data-column'));
                ev.dataTransfer.setData("basevehicleid", ev.target.getAttribute('data-basevehicleid'));
                ev.dataTransfer.setData("sourcepartnumber", ev.target.getAttribute('data-partnumber'));
                ev.dataTransfer.setData("sourcecosmetic", ev.target.getAttribute('data-cosmetic'));
                ev.dataTransfer.setData("sourcequantityperapp", ev.target.getAttribute('data-quantityperapp'));
            }

            function drop(ev)
            {
                ev.preventDefault();
                var data = ev.dataTransfer.getData("t");
                var childapp = "";
                var sourceapp = ev.dataTransfer.getData("sourceapp");
                var sourcerow = ev.dataTransfer.getData("sourcerow");
                var sourcecolumn = ev.dataTransfer.getData("sourcecolumn");
                var basevehicleid = ev.dataTransfer.getData("basevehicleid");
                var sourcepartnumber = ev.dataTransfer.getData("sourcepartnumber");
                var sourcecosmetic = ev.dataTransfer.getData("sourcecosmetic");
                var sourcequantityperapp = ev.dataTransfer.getData("sourcequantityperapp");

                if (ev.target.getAttribute('data-type') != 'dropzone') {
                    return;
                }
                if (ev.target.getAttribute('data-row') == sourcerow && ev.target.getAttribute('data-column') == sourcecolumn)
                {
                    //app was dragged to its own cell - toggle cosmetic and reload the page
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'ajaxToggleAppCosmetic.php?appid=' + sourceapp);
                    xhr.send();

                    if (sourcecosmetic == 0)
                    {
                        document.getElementById(data).setAttribute('class', 'apppart-cosmetic');
                        document.getElementById(data).setAttribute('data-cosmetic', '1');
                    } else
                    {
                        document.getElementById(data).setAttribute('class', 'apppart');
                        document.getElementById(data).setAttribute('data-cosmetic', '0');
                    }
                    return;
                }

                var copymove = "";
                if (document.querySelector('#copymove').checked)
                {
                    var movingapp = document.getElementById(data).cloneNode(true);
                    childapp = movingapp;
                    copymove = "copy";
                } else
                {
                    childapp = document.getElementById(data);
                    copymove = "move";
                }

                if (ev.target.getAttribute('id') == 'trash' || ev.target.getAttribute('id') == 'hide')
                { // app was dragged to trash or hide dropzone - set status accordingly and remove them from the document 
                    childapp.remove();
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'ajaxUpdateAppStatus.php?appid=' + sourceapp + '&status=' + ev.target.getAttribute('id'));
                    xhr.send();
                } else
                { // app was dragged to a cell other than its own (handled elsewhere). Move the app object in the document visually  and make ajax call 
                    // to apply the attributes of the destination row/column to the dragged app

                    if (copymove == "copy")
                    { // new app is created at the row/column intersection using the part number, qty
                        var xhr = new XMLHttpRequest();
                        xhr.open('GET', 'ajaxNewApp.php?basevehicleid=' + basevehicleid + '&partnumber=' + sourcepartnumber + '&cosmetic=' + sourcecosmetic + '&quantityperapp=' + sourcequantityperapp + '&fitment=' + ev.target.getAttribute('data-row') + '&positionandparttype=' + ev.target.getAttribute('data-column') + '&movetype=drag-copy');
                        xhr.onload = function ()
                        {
                            var response=JSON.parse(xhr.responseText);
                            if(response.success)
                            {
                                document.getElementById("heading-alert").style.display='none';
                                document.getElementById("heading-alert").innerHTML='';
                                ev.target.innerHTML += '<div id="apppart_' + response.newappid + '" class="apppart" draggable="true" ondragstart="drag(event)" data-type="app" data-row="' + ev.target.getAttribute('data-row') + '" data-column="' + ev.target.getAttribute('data-column') + '" data-sourceapp="' + response.newappid + '" data-basevehicleid="' + basevehicleid + '" data-partnumber="' + sourcepartnumber + '" data-quantityperapp="' + sourcequantityperapp + '" data-cosmetic="' + sourcecosmetic + '" style="padding-left:3px;padding-top:3px;padding-bottom:3px;padding-right:30px;"><a href="showApp.php?appid=' + response.newappid + '">' + sourcepartnumber + '</a></div>';
                            }
                            else
                            {
                                document.getElementById("heading-alert").style.display='block';
                                document.getElementById("heading-alert").innerHTML=response.message;
                            }
                        };
                        xhr.send();
                    } else
                    { //app was moved (no new app created by the drag)
                        var xhr = new XMLHttpRequest();
                        xhr.open('GET', 'ajaxConformApp.php?appid=' + sourceapp + '&fitment=' + ev.target.getAttribute('data-row') + '&positionandparttype=' + ev.target.getAttribute('data-column'));
                        xhr.send();
                        ev.target.appendChild(childapp);

                        // update the document element to have the new grid coordinates (over-write its origials)
                        document.getElementById(data).setAttribute('data-row', ev.target.getAttribute('data-row'));
                        document.getElementById(data).setAttribute('data-column', ev.target.getAttribute('data-column'));
                    }
                }
            }


            function showAddPartArea(source, rowdata, columndata, basevehicleid, dropzone)
            {
                source.outerHTML += "<div style='position:absolute;width:200px;height:100px;border:1px solid;margin-top:-1em;background-color:#ffffff;padding:1em;'>" +
                        " <p><input id='addpart_" + dropzone.toString() + "' type='text' name='partnumber' style='width:95%;'/></p>" +
                        "<button class='addpartbutton' onclick='submitAddPart(this,\"" + rowdata.toString() + "\",\"" + columndata.toString() + "\",\"" + basevehicleid.toString() + "\",\"" + dropzone.toString() + "\")'>Add</button> <button onclick='closeAddPartArea(this);'>Cancel</button></div>";
                document.getElementById('addpart_' + dropzone).focus();
            }

            function submitAddPart(source, rowdata, columndata, basevehicleid, dropzone)
            {
                var partnumber = source.parentNode.querySelector('input[name="partnumber"]').value;
                if (partnumber === "") {
                    return;
                } else
                { // valid looking part number

                    // ajax call to add new app
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'ajaxNewApp.php?basevehicleid=' + basevehicleid + '&partnumber=' + partnumber + '&cosmetic=0&quantityperapp=1&fitment=' + rowdata + '&positionandparttype=' + columndata + '&movetype=entry');
                    xhr.onload = function ()
                    {
                        // insert new draggable part block                        
                        var response=JSON.parse(xhr.responseText);
                        if(response.success)
                        {
                            document.getElementById("heading-alert").style.display='none';
                            document.getElementById("heading-alert").innerHTML='';
                            document.getElementById(dropzone).innerHTML += '<div id="apppart_' + response.newappid + '" class="apppart" draggable="true" ondragstart="drag(event)" data-type="app" data-row="' + rowdata + '" data-column="' + columndata + '" data-sourceapp="' + response.newappid + '" data-basevehicleid="' + basevehicleid + '" data-partnumber="' + partnumber + '" data-quantityperapp="1" data-cosmetic="0" style="padding-left:3px;padding-top:3px;padding-bottom:3px;padding-right:30px;"><a href="showApp.php?appid=' + response.newappid + '">' + partnumber + '</a></div>';
                        }
                        else
                        {
                            document.getElementById("heading-alert").style.display='block';
                            document.getElementById("heading-alert").innerHTML=response.message;
                        }
                    };
                    xhr.send();
                    // destroy popup area input form
                    source.parentNode.parentNode.removeChild(source.parentNode);
                }
            }

            function closeAddPartArea(source)
            {
                source.parentNode.parentNode.removeChild(source.parentNode);
            }

            document.addEventListener("keyup", function (event)
            {
                if (document.activeElement.name == 'partnumber')
                {
                    if (event.keyCode == 13)
                    {
                        document.activeElement.parentNode.parentNode.querySelector('.addpartbutton').click();
                    }

                    if (event.keyCode == 27)
                    {
                        document.activeElement.parentNode.parentNode.parentNode.removeChild(document.activeElement.parentNode.parentNode);
                    }
                }
            })



            function addAppsToClipboard()
            {
                var nodes = document.getElementById('appids').getElementsByTagName("div");
                for(var i=0; i<nodes.length; i++) 
                {
                    //                    console.log(nodes[i].getAttribute('data-appid') + ' - ' + nodes[i].getAttribute('data-description'));
                    var description = nodes[i].getAttribute('data-description');
                    var objectdata='';
                    var objectkey=nodes[i].getAttribute('data-appid');
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'ajaxAddToClipboard.php?objecttype=app&description='+btoa(description)+'&objectkey='+objectkey+'&objectdata='+btoa(objectdata));
                    xhr.onload = function()
                    {
                    };
                    xhr.send();             
                }
                refreshClipboard();
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
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <div class="alert alert-danger" role="alert" id="heading-alert" style="display:none;">This is a danger alert—check it out!</div>

                        <h3 class="card-header">

                            <div style="padding:5px;float:left">
                                <a class="btn btn-secondary" href="appsSelectCategory.php?makeid=<?php echo $makeid;?>&modelid=<?php echo $modelid;?>&yearid=<?php echo $yearid;?>">Part Categories</a>
                            </div>
                            
                            <div style="padding:5px;float:left">
                                <?php echo '<a href="appsIndex.php">'.$makename.'</a>, <a href="mmySelectModel.php?makeid='.$makeid.'">'.$modelname.'</a> ';
                                if ($prevyearexists) {echo selfLink($makeid, $modelid, ($yearid - 1), $partcategories, '', '<i class="bi bi-chevron-double-left"></i>');}
                                echo $yearid;
                                if ($nextyearexists) {echo selfLink($makeid, $modelid, ($yearid + 1), $partcategories, '', '<i class="bi bi-chevron-double-right"></i>');}?>
                                
                                <div style="font-size:45%;"><?php echo 'VIO ('.$viogeography.' '.$vioyearquarter.'): <a href="./ExperianVIOsnippetStream.php?basevehicleid='.$basevehicleid.'">'.number_format($viototal,0,'.',',').'</a>'; ?></div>
                                                                
                            </div>
                            
                            <div style="padding:5px;float:right">
                                
                                <?php if(count($clipboardapps)>0)
                                {
                                    echo '<form method="post" action="showAppsByBasevehicle.php">';
                                    echo '<input type="hidden" name="makeid" value="'.$makeid.'"/>'; 
                                    echo '<input type="hidden" name="modelid" value="'.$modelid.'"/>'; 
                                    echo '<input type="hidden" name="yearid" value="'.$yearid.'"/>';
                                    foreach($partcategories as $partcategory){echo '<input type="hidden" name="partcategory_'.$partcategory.'" value="on"/>';}
                                    echo '<input type="hidden" name="paste" value=""/>'; 
                                    echo '<input class="btn btn-secondary" type="submit" name="submit" value="Paste"/>'; 
                                    echo '</form>';
                                } ?>
                            </div>


                            <div style="padding:5px;float:right">
                                
                                <?php if(count($apps))
                                {
                                    echo '<div style="display:none;" id="appids">';
                                    foreach ($apps as $app)
                                    {
                                        echo '<div data-appid="'.$app['id'].'" data-description="'.$makename.', '.  str_replace('&', ' AND ',$modelname).', '.$yearid.' ('.  $app['partnumber'].')">'.$app['id'].'</div>';
                                    }
                                    echo '</div>';                        
                                    echo '<span class="btn btn-info" onclick="addAppsToClipboard()">Copy</span>';
                                    
                                    if(count($vehcilehistory)){echo '<span> <a class="btn btn-secondary" href="./vehicleHistory.php?basevehicleid='.$basevehicleid.'">History</a></span>';}
                                    
                                    echo '</div>';
                                }?>

                            <div style="clear:both;"></div>
                        </h3>

                        <div class="card-body">
                            <?php
                            if (count($apps)) {
                                foreach ($apps as $app) {
                                    $niceattributes = array();
                                    foreach ($app['attributes'] as $appattribute) {
                                        if ($appattribute['type'] == 'vcdb') {
                                            $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']);
                                        }
                                        if ($appattribute['type'] == 'note') {
                                            $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']);
                                        }
                                    }
                                    $nicefitmentstring = '';
                                    $nicefitmentarray = array();
                                    foreach ($niceattributes as $niceattribute) {
                                        $nicefitmentarray[] = $niceattribute['text'];
                                    }
                                }

                                echo '<label><input type="checkbox" id="copymove" name="copymove"/>Replicate Dragged Parts</label>';

                                echo '<table class="table"><tr><td></td>';
                                foreach ($fitmentcolumnkeys as $fitmentcolumnkey => $trash) {
                                    echo '<td><div style="padding:5px;">' . $fitmentcolumnkey . '</div></td>';
                                } echo '</tr>';

                                foreach ($fitmentrowkeys as $fitmentrowkey => $rowfitmentattributes) {
                                    echo '<tr><td><div style="padding:5px;">' . $fitmentrowkey . '</div></td>';
                                    foreach ($fitmentcolumnkeys as $fitmentcolumnkey => $positionandparttype) {
                                        echo '<td style="vertical-align:top">';
                                        $dropzonenumber++;
                                        echo '<div id="dropzone_' . $dropzonenumber . '" ondrop="drop(event)" ondragover="allowDrop(event)" data-type="dropzone" data-row="' . $rowfitmentattributes . '" data-column="' . $positionandparttype . '" style="background-color:#c0c0c0;padding-top:2px;padding-bottom:25px;padding-left:2px;padding-right:2px;">';
                                        if (isset($appmatrix[$fitmentrowkey][$fitmentcolumnkey])) {
                                            foreach ($appmatrix[$fitmentrowkey][$fitmentcolumnkey] as $app) {
                                                $appstyle = 'apppart';
                                                if ($app['cosmetic'] > 0) {
                                                    $appstyle = 'apppart-cosmetic';
                                                } if ($app['status'] > 1) {
                                                    $appstyle = 'apppart-hidden';
                                                } if ($app['status'] == 1) {
                                                    $appstyle = 'apppart-deleted';
                                                }
                                                echo '<div id="apppart_' . $app['id'] . '" class="' . $appstyle . '" draggable="true" ondragstart="drag(event)" data-type="app" data-row="' . $rowfitmentattributes . '" data-column="' . $positionandparttype . '" data-sourceapp="' . $app['id'] . '" data-basevehicleid="' . $app['basevehicleid'] . '" data-partnumber="' . $app['partnumber'] . '" data-quantityperapp="' . $app['quantityperapp'] . '" data-cosmetic="' . $app['cosmetic'] . '" style="padding-left:3px;padding-top:3px;padding-bottom:3px;padding-right:30px;"><a href="showApp.php?appid=' . $app['id'] .'&categories='. urlencode(implode(',',$partcategories)).'">' . $app['partnumber'] . '</a></div>';
                                            }
                                        }
                                        echo '</div>';

                                        echo '<div onclick="showAddPartArea(this,\'' . $rowfitmentattributes . '\',\'' . $positionandparttype . '\',\'' . $basevehicleid . '\',\'dropzone_' . $dropzonenumber . '\')" data-type="addpart">...</div>';

                                        echo '</td>';
                                    }
                                    echo '</tr>';
                                }
                                echo '</table>';
                                
                                echo '<div class="card-footer bg-transparent"><div class="row padding my-row">';
                                echo '<div class="col-6"><div id="trash" ondrop="drop(event)" ondragover="allowDrop(event)" data-type="dropzone" data-row="trash" data-column="trash" style="padding:10px;margin:10px;border:2px solid #f5f5f5;background-color:#FF5533;">Drag apps here to delete them</div></div>';
                                echo '<div class="col-6"><div id="hide" ondrop="drop(event)" ondragover="allowDrop(event)" data-type="dropzone" data-row="hide" data-column="hide" style="padding:10px;margin:10px;border:2px solid #f5f5f5;background-color:#FFD433;">Drag apps here to de-activate them</div></div>';
                                echo '</div>';           
                                      
                            } else { // no apps found
                                echo 'No applications found for this make/model/year';
                            }
                            ?>
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