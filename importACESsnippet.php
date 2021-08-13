<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'import';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$v = new vcdb;
$pim = new pim;
/*
  <?xml version="1.0" encoding="UTF-8"?>
  <ACES>
  <App action="A" id="680" ref="12991983">
  <BaseVehicle id="7481"/><EngineBase id="983"/>
  <Qual id="240">
  <param value="1"/>
  <text>1 Piece Driveshaft</text>
  </Qual>
  <Qual id="260">
  <param value="2"/>
  <text>2 Piece Driveshaft</text>
  </Qual>
  <Qual id="4967">
  <text>Greaseable</text>
  </Qual>
  <Qual id="12073">
  <param value="1.75" uom="in"/>
  <text>with 1.75in Driveshaft Diameter</text>
  </Qual>
  <Note>288mm Front Rotor</Note>
  <Note>Some other note</Note>
  <Qty>2</Qty>
  <PartType id="1896"/>
  <MfrLabel>Performance Plus Brake Rotor</MfrLabel>
  <Position id="30"/>
  <Part>R11280</Part>
  </App>
  </ACES>
 */

if (isset($_POST['input'])) {
    $xml = simplexml_load_string($_POST['input']);
    $app_count = $pim->createAppFromACESsnippet($xml);
    echo $app_count . ' apps created';
}?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
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
                        <h3 class="card-header text-start">Import small ACES xml</h3>

                        <div class="card-body">
                            <h5 class="card-subtitle mb-2 text-muted">Paste ACES XML text to import</h5>
                            <form method="post">
                                <textarea name="input" rows="20" cols="100"></textarea>
                                <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
                            </form>
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