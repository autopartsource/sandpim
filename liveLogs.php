<?php
$navCategory = 'dashboard';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

        <script>
         var appsTimer = setInterval(updateAppsLogView, 1000);
         var partsTimer = setInterval(updatePartsLogView, 1000);
         var assetsTimer = setInterval(updateAssetsLogView, 1000);
         
         var displayedAppRecords=[];
         var displayedPartRecords=[];
         var displayedAssetRecords=[];
            
         function updateAppsLogView()
         {
            var isDisplayed=false; 
            var isInResults=false;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'ajaxGetLogs.php?logname=application&requesttype=latest&size=20');
            xhr.onload = function()
            {
                var records=JSON.parse(xhr.responseText);

                for(var i in records)
                {
                    isDisplayed=false;
                    for(var j in displayedAppRecords)
                    {
                        if(displayedAppRecords[j].id==records[i].id)
                        {
                            isDisplayed=true;
                            break;
                        }
                    }
                    if(!isDisplayed)
                    {
                        displayedAppRecords.push(records[i]);
                        var p = document.createElement('p');
                        p.id = 'appevent_'+records[i].id; p.textContent=records[i].eventdatetime+' '+records[i].description; p.class="appevent";
                        document.getElementById("appevents").appendChild(p);
                    }
                }

                for(var i in displayedAppRecords)
                {
                    isInResults=false;
                    for(var j in records)
                    {
                        if(displayedAppRecords[i].id==records[j].id)
                        {
                            isInResults=true;
                            break;
                        }
                    }
                    if(!isInResults)
                    {   // remove from DOM and displayedRecords array
                        var elem = document.getElementById('appevent_'+displayedAppRecords[i].id);
                        elem.parentNode.removeChild(elem);                      
                        displayedAppRecords.splice(i,1);
                    }
                }
                
             };
             xhr.send();
         }

         function updatePartsLogView()
         {
            var isDisplayed=false; 
            var isInResults=false;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'ajaxGetLogs.php?logname=part&requesttype=latest&size=20');
            xhr.onload = function()
            {
                var records=JSON.parse(xhr.responseText);

                for(var i in records)
                {
                    isDisplayed=false;
                    for(var j in displayedPartRecords)
                    {
                        if(displayedPartRecords[j].id==records[i].id)
                        {
                            isDisplayed=true;
                            break;
                        }
                    }
                    if(!isDisplayed)
                    {
                        displayedPartRecords.push(records[i]);
                        var p = document.createElement('p');
                        p.id = 'partevent_'+records[i].id; p.textContent=records[i].eventdatetime+' '+records[i].description; p.class="partevent";
                        document.getElementById("partevents").appendChild(p);
                    }
                }

                for(var i in displayedPartRecords)
                {
                    isInResults=false;
                    for(var j in records)
                    {
                        if(displayedPartRecords[i].id==records[j].id)
                        {
                            isInResults=true;
                            break;
                        }
                    }
                    if(!isInResults)
                    {   // remove from DOM and displayedRecords array
                        var elem = document.getElementById('partevent_'+displayedPartRecords[i].id);
                        elem.parentNode.removeChild(elem);                      
                        displayedPartRecords.splice(i,1);
                    }
                }
                
             };
             xhr.send();
         }

         function updateAssetsLogView()
         {
            var isDisplayed=false; 
            var isInResults=false;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'ajaxGetLogs.php?logname=asset&requesttype=latest&size=20');
            xhr.onload = function()
            {
                var records=JSON.parse(xhr.responseText);

                for(var i in records)
                {
                    isDisplayed=false;
                    for(var j in displayedAssetRecords)
                    {
                        if(displayedAssetRecords[j].id==records[i].id)
                        {
                            isDisplayed=true;
                            break;
                        }
                    }
                    if(!isDisplayed)
                    {
                        displayedAssetRecords.push(records[i]);
                        var p = document.createElement('p');
                        p.id = 'assetevent_'+records[i].id; p.textContent=records[i].eventdatetime+' '+records[i].description; p.class="assetevent";
                        document.getElementById("assetevents").appendChild(p);
                    }
                }

                for(var i in displayedAssetRecords)
                {
                    isInResults=false;
                    for(var j in records)
                    {
                        if(displayedAssetRecords[i].id==records[j].id)
                        {
                            isInResults=true;
                            break;
                        }
                    }
                    if(!isInResults)
                    {   // remove from DOM and displayedRecords array
                        var elem = document.getElementById('assetevent_'+displayedAssetRecords[i].id);
                        elem.parentNode.removeChild(elem);                      
                        displayedAssetRecords.splice(i,1);
                    }
                }
                
             };
             xhr.send();
         }

        function openLink(evt, animName) {
          var i, x, tablinks;
          x = document.getElementsByClassName("city");
          for (i = 0; i < x.length; i++) {
            x[i].style.display = "none";
          }
          tablinks = document.getElementsByClassName("tablink");
          for (i = 0; i < x.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" w3-red", "");
          }
          document.getElementById(animName).style.display = "block";
          evt.currentTarget.className += " w3-red";
        }

        </script>
    </head>
    <body onload="openLink(event, 'apps')">
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1></h1>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="w3-sidebar w3-bar-block w3-black w3-card" style="width:150px">
                        <button class="w3-bar-item w3-button tablink" onclick="openLink(event, 'apps')">Application Log</button>
                        <button class="w3-bar-item w3-button tablink" onclick="openLink(event, 'parts')">Parts Log</button>
                        <button class="w3-bar-item w3-button tablink" onclick="openLink(event, 'assets')">Assets Log</button>
                    </div>
                    <div>
                        <div id="apps" class="w3-container city w3-animate-opacity" style="display:none">
                            <div id="appevents"></div>
                        </div>
                        <div id="parts" class="w3-container city w3-animate-opacity" style="display:none">
                            <div id="partevents"></div>
                        </div>
                        <div id="assets" class="w3-container city w3-animate-opacity" style="display:none">
                            <div id="assetevents"></div>
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
