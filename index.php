<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pimClass.php');

$v=new vcdb;
$pim= new pim;

/* $snippet='<?xml version="1.0" encoding="UTF-8"?><App action="A" id="46" ref="13167460"><BaseVehicle id="119657"/><SubModel id="912"/><EngineBase id="2118"/><Qty>1</Qty><PartType id="6192"/><Position id="1"/><Part>AF5203</Part><AssetName>c531ce9eeda34f15b89eb4427d33d24a</AssetName><AssetItemOrder>1</AssetItemOrder></App>';  */
/* $snippet='<?xml version="1.0" encoding="UTF-8"?><App action="A" id="680" ref="12991983"><BaseVehicle id="7481"/><EngineBase id="983"/><Note>288mm Front Rotor</Note><Note>Some other note</Note><Qty>2</Qty><PartType id="1896"/><MfrLabel>Performance Plus Brake Rotor</MfrLabel><Position id="30"/><Part>R11280</Part></App>'; */
// $id=$pim->createAppFromACESsnippet($snippet,15,$pim->makeoid());
// echo 'App '.$id.' created<br/>';

?>
<!DOCTYPE html>
<html>
 <head>
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <h1>Dashboard</h1>
 </div>
 </body>
</html>
