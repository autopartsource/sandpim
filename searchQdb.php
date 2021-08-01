<?php
include_once('./class/pimClass.php');
include_once('./class/qdbClass.php');
$navCategory = 'applications';

$pim=new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'searchQdb.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    


session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$qdb=new qdb;
$userid=$_SESSION['userid'];

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        <script>
            var searchterm="";
            var termTimers = setInterval(lookForSearchtermChange, 100);
            
            function searchQdb(term)
            {
                //console.log(encodeURI(term));
                //var searchterm=document.getElementById("searchterm").value
                //var searchterm=term;
                if(term.length>2)
                {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'ajaxSearchQdb.php?searchterm='+encodeURI(term));
                    xhr.onload = function()
                    {
                        var response= JSON.parse(xhr.responseText);
                        document.getElementById("searchresults").innerHTML="";
                        response.forEach(renderResultRow);
                        //console.log(response);
                    };
                    xhr.send();
                }
            }
            
            function renderResultRow(row,index)
            {
              document.getElementById("searchresults").innerHTML+='<div id="qualifierid_'+row.qualifierid+'">'+row.htmlsafequalifiertext+'</div>';
            }
            
            function lookForSearchtermChange()
            {
                var newsearchterm = document.getElementById("searchterm").value;
                if (newsearchterm != searchterm) 
                {
                    searchterm = newsearchterm;
                    searchQdb(searchterm);     // do whatever you need to do
                }
            }
            
            
            
            
        </script>
    </head>
    <body>
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
                    <div><input type="text" id="searchterm" onchange="searchQdb(this.value)"/></div>
                    <div id="searchresults"></div>
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