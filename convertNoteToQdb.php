<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'settings';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'convertNoteToQdb.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$attributeid=intval($_GET['attributeid']);
$attribute=$pim->getAppAttribute($attributeid);
$usecount=count($pim->getAppAttributesByValue('note','note',$attribute['value']));
$applicationid=$attribute['applicationid'];
$redirectto='showApp';
if(isset($_GET['source']) && $_GET['source']=='noteManager')
{
 $redirectto='noteManager';
}


?>
<!DOCTYPE html>
<html>
    <head>
     <?php include('./includes/header.php'); ?>
     <script>        
      function searchQdb()
      {
       document.getElementById("qdbresultscount").innerHTML="Searching Qdb...";
       document.getElementById("qdbresults").innerHTML = "";
       var searchterm=document.getElementById("qdbsearchterm").value;
       var xhr = new XMLHttpRequest();
       xhr.open('GET', 'ajaxSearchQdb.php?type=any&searchterm='+encodeURIComponent(searchterm));
       xhr.onload = function()
       {
        var results=JSON.parse(xhr.responseText);
        document.getElementById("qdbresultscount").innerHTML= results.length+" results found";
        for(var k in results) 
        {
         var newOption = new Option(results[k].qualifiertext,results[k].qualifierid);
         document.getElementById("qdbresults").add(newOption,undefined);
        }

        if(results.length>0)
        {
         document.getElementById("qdbresults").style.display='block';
        }
        else
        {
         document.getElementById("qdbresults").style.display='none';
        }
       };
       xhr.send();
      }

      function selectQdb()
      {
       var p;
       for(p=1; p<=8; p++)
       {
        document.getElementById('qdbparm'+p+'block').style.display='none';
        document.getElementById('qdbparm'+p+'uomblock').style.display='none';
        document.getElementById('qdbparm'+p+'value').value='';
        document.getElementById('qdbparm'+p+'uom').value='';
       }

       document.getElementById('qdbpreview').innerHTML='';
 
       var resultSelect = document.getElementById("qdbresults");
       var selectedQdbText=resultSelect.options[resultSelect.selectedIndex].text;
       var selectedQdbID=resultSelect.options[resultSelect.selectedIndex].value;

     // identify embeded parameters in the Qdb String
       var n = -1;
       var offset=0;
       var parmType='';
       var p;
       for(p=1; p<=8; p++)
       {
        n=selectedQdbText.indexOf(' type="',offset);

        if(n > -1)
        {// found a parm
         parmTypeEnd=selectedQdbText.indexOf('"',n+8);

         if(parmTypeEnd > -1)
         {// found an ending "
          parmType=selectedQdbText.substring(n+7,parmTypeEnd);
          document.getElementById('qdbparm'+p+'title').innerHTML='Parameter '+p+' ('+parmType+') ';
          document.getElementById('qdbparm'+p+'block').style.display='block';

          if(parmType=='size' || parmType=='weight')
          {
           document.getElementById('qdbparm'+p+'uomblock').style.display='block';
          }
         }
         offset=n+1;
        }
        else
        { // no more parms found
         break;   
        }
       }
//  var str = '<p1 type="size"/> Bolt, <p2 type="size"/> Thick x <p3 type="size"/> Long x <p4 type="size"/> Wide'; 
       showQdbPreview();
      }

      function showQdbPreview()
      {
       var i;
       var resultSelect = document.getElementById("qdbresults");
       var selectedQdbText=resultSelect.options[resultSelect.selectedIndex].text;
       var selectedQdbID=resultSelect.options[resultSelect.selectedIndex].value;
       var parmsString='';

       var parms=['-']; // element 0 is filled with trash to allow the element numbers to align with the "p" mumbers

       for(i=1; i<=8; i++)
       {
        parms.push(document.getElementById('qdbparm'+i+'value').value + document.getElementById('qdbparm'+i+'uom').value);
                           //value like "123, ABC, XYZ|~12|mm~4|mm"
        if(document.getElementById('qdbparm'+i+'value').value !='')
        {
         parmsString+=document.getElementById('qdbparm'+i+'value').value+'|'+document.getElementById('qdbparm'+i+'uom').value+'~';
        }
       }

       document.getElementById("qdbpreview").setAttribute("data-qdbid",selectedQdbID);
       document.getElementById("qdbpreview").setAttribute("data-qdbparmstring",parmsString);

       var previewText=applyQdbParmsToString(selectedQdbText,parms);
       document.getElementById('qdbpreview').innerHTML=previewText;
       document.getElementById('qdbpreview').style.display='block';
      }

      
      function applyQdbParmsToString(text,parms)
      {
       var result=text;
       var startpos=-1;
       var parmType='';
       var i=0;

       for(i=1; i<=8; i++)
       {
        startpos=result.indexOf('<p'+i+' type="');
        if(startpos > -1)
        {
         parmTypeEnd=result.indexOf('"',startpos+10);
         if(parmTypeEnd > -1)
         {// found an ending "
          parmType=result.substring(startpos+10,parmTypeEnd);
          if(parms[i]!='')
          { // 
           result=result.replace('<p'+i+' type="'+parmType+'"/>',parms[i]);
          }
         }                  
        }
       }
       return result;
      }
      
      function convertNote(redirectto)
      {
       var qdbid=document.getElementById("qdbpreview").getAttribute("data-qdbid");
       var qdbparms=document.getElementById("qdbpreview").getAttribute("data-qdbparmstring");
       document.getElementById('loadinggif').style.display='block';

       var xhr = new XMLHttpRequest();
       xhr.open('GET', 'ajaxConvertNoteToQdb.php?&note=<?php echo urlencode($attribute['value']);?>&qdbid='+qdbid+'&qdbparms='+encodeURIComponent(qdbparms));
       xhr.onload = function()
       {
        var results=JSON.parse(xhr.responseText);
        document.getElementById('loadinggif').style.display='none';
        if(redirectto=='noteManager')
        {
         location.href="./noteManager.php";
        }
        else
        {
         location.href="./showApp.php?appid=<?php echo $applicationid;?>";
        }
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
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Migrate a fitment note to Qdb</h3>
                        
                        <div class="card-body">
                            <!-- Note to Migrate Card -->
                            <div class="card">
                                <!-- Header -->
                                <h6 class="card-header text-start">
                                    Note to Migrate
                                    <div style="float:right">Total Uses: <?php echo $usecount; ?></div>
                                </h6>

                                <div class="card-body">
                                    <div style="background-color:#c0c0c0; padding:5px;float:left;"><?php echo $attribute['value']; ?></div>

                                </div>
                            </div>
                            
                            <!-- Search Card -->
                            <div class="card">
                                <!-- Header -->
                                <h6 class="card-header text-start">
                                    <div style="float:left;">
                                        <input type="text" id="qdbsearchterm"/>
                                        <button id="qdbsearch" onclick="searchQdb()">Search</button>
                                    </div>
                                </h6>

                                <div class="card-body">
                                    <div id="newqdbattributeform">
                                    <div style="padding:3px;font-size: 75%; color: #aaaaaa;" id="qdbresultscount"></div>
                                    <select id="qdbresults" style="display:none;" size="10" multiple onchange="selectQdb(); getElementById('convertNotesButton').style.display='block';"></select>

                                    <?php for ($i = 1; $i <= 8; $i++) { ?>
                                        <div id="qdbparm<?php echo $i; ?>block" style="padding:3px; display:none;">
                                            <div id="qdbparm<?php echo $i; ?>title" style="float:left;"></div>
                                            <div style="float:left;">
                                                <input size="8" type="text" id="qdbparm<?php echo $i; ?>value" onkeyup="showQdbPreview()"/>
                                            </div>
                                            <div id="qdbparm<?php echo $i; ?>uomblock" style="float:left;padding-left:10px; display:none;">uom
                                                <input type="text" id="qdbparm<?php echo $i; ?>uom" size="2" onkeyup="showQdbPreview();"/>
                                            </div>
                                            <div style="clear:both;"></div>
                                        </div>
                                    <?php } ?>

                                    <div id="qdbpreview" data-qdbid="" data-qdbparmstring="" style="display:none; background-color: #6060F0; border: solid 1px black; padding:3px;margin: 20px;"></div>

                                    <div id="convertNotesButton" style="padding: 5px;display:none;"><button id="convert" onclick="convertNote('<?php echo $redirectto; ?>');">Convert</button></div>
                                    <div id="loadinggif" style="padding: 5px;display:none;"><img src="./loading.gif" width="50"/><div>Converting <?php echo $usecount ?> instances of this note (in all applications) to a Qdb qualifiers</div></div>
                                    </div>
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