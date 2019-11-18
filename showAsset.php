<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');

$navCategory = 'assets';




session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
$asset = new asset;

if (isset($_POST['submit']) && $_POST['submit'] == 'Save') {
    $pim->updatePartOID($partnumber);
}


$assetid = $_GET['assetid'];
$assetrecords=$asset->getAssetRecordsByAssetid($assetid);

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('/var/www/html/includes/header.php'); ?>
        
        <script>
            function updateAsset(assetid,elementtype,elementid)
            {
             var value='';
             if(elementtype=='text'){value=document.getElementById(elementid).value;}
             if(elementtype=='button'){value='toggle';}
             if(elementtype=='select'){var e=document.getElementById(elementid);value=e.options[e.selectedIndex].value;}

            console.log('asset:'+assetid+'; elementtype:'+elementtype+'; elementid:'+elementid+'; value:'+value);

//             document.getElementById("sandpiperoid").innerHTML='';

             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdateAsset.php?assetid='+assetid+'&elementid='+elementid+'&value='+encodeURI(value));
             xhr.onload = function()
             {
              var response=xhr.responseText;
//              document.getElementById("sandpiperoid").innerHTML=response;
             };
             xhr.send();
            }

        
            function updateAssetRecord(id,elementtype,elementid)
            {
             var value='';
             if(elementtype=='text'){value=document.getElementById(elementid).value;}
             if(elementtype=='button'){value='toggle';}
             if(elementtype=='select'){var e=document.getElementById(elementid);value=e.options[e.selectedIndex].value;}

            console.log('id:'+id+'; elementtype:'+elementtype+'; elementid:'+elementid+'; value:'+value);

//             document.getElementById("sandpiperoid").innerHTML='';

             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdateAssetRecord.php?id='+id+'&elementid='+elementid+'&value='+encodeURI(value));
             xhr.onload = function()
             {
              var response=xhr.responseText;
              document.getElementById('assetrecordpublic_'+id).innerHTML=response;
             };
             xhr.send();
            }

        
        
        </script>

    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1></h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain button">
                Asset ID <?php echo $assetid;?>
                <div>
                    <?php
                    foreach($assetrecords as $assetrecord)
                    {
                        echo '<div style="border:1px solid;margin:5px;padding:5px;">';
                        echo ' <div style="float:left;padding:10px;">'.$assetrecord['description'];
                        echo ' <hr/>'.$asset->niceExifTypeName($assetrecord['fileType']);
                        echo ' <hr/>'.$assetrecord['assetHeight'].'x'.$assetrecord['assetWidth'];
                        echo ' <hr/><button id="assetrecordpublic_'.$assetrecord['id'].'" onclick="updateAssetRecord(\''.$assetrecord['id'].'\',\'button\',\'public\')">'.$asset->niceBoolText($assetrecord['public'],'Public','Private').'</button>';
                        echo ' </div>';
                        echo ' <div style="float:left;">';
                        echo '  <a href="showAssetRecord.php?id='.$assetrecord['id'].'"><img width="100" src="'.$assetrecord['uri'].'"/></a>';
                        echo ' </div>';
                        echo ' <div style="clear:both;"></div>';
                        echo '</div>';
                    }?>
                </div>
            </div>

            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>