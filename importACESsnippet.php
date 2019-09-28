<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pimClass.php');

$v=new vcdb;
$pim= new pim;
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

if(isset($_POST['input']))
{
 $xml = simplexml_load_string($_POST['input']);
 $app_count=$pim->createAppFromACESsnippet($xml,intval($_POST['appcategory']));
 echo $app_count.' apps created';
}

$appcategories=$pim->getAppCategories();

?>
<!DOCTYPE html>
<html>
 <head>
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <h1>Import small ACES xml</h1>
  <div>
   <form method="post">
    <div style="padding:10px;"><div>Paste ACES XML text to import</div>
     <textarea name="input" rows="20" cols="100"></textarea>
    </div>
    <div style="padding:10px;">App Category <select name="appcategory"><?php foreach($appcategories as $appcategory){?> <option value="<?php echo $appcategory['id'];?>"><?php echo $appcategory['name'];?></option><?php }?></select></div>
    <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
   </form>
  </div>
 </div>
 </body>
</html>
