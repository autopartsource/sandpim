<?php
include_once('/var/www/html/class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pim= new pim;

if(isset($_POST['input']))
{
 $input =  $_POST['input'];
 $records=explode("\r\n", $_POST['input']);
 $PAID=0;
 foreach($records as $record)
 {
  $fields=explode("\t",$record);
  if(count($fields)==3 || count($fields)==4)
  { // partnumber,attributename,attributevalue[,unitOfMeasure]
   $partnumber=trim(strtoupper($fields[0]));
   if(strlen($partnumber)<=20 && strlen($partnumber)>0)
   { // partnumber is within valid length
    if($pim->validPart($partnumber))
    {
     $attributename=trim($fields[1]); $attributevalue=trim($fields[2]); $uom=''; if(count($fields)==4){$uom=trim($fields[3]);}
     $pim->writePartAttribute($partnumber,$PAID,$attributename,$attributevalue,$uom);
    }
   }
  }
 }
}

?>
<!DOCTYPE html>
<html>
 <head>
 </head>
 <body>
 <?php include('topnav.php');?>
  <h1>Import Part Attribute Data (non-PAdb)</h1>
  <div>
   <form method="post">
    <div style="padding:10px;"><div>Paste three or four columns (tab delimited) data: part, name, value [, UoM])</div>
     <textarea name="input" rows="20" cols="100"></textarea>
    </div>
    <div style="padding:10px;"><select name="attributetype"><option value="weight">Weight</option></select></div>
    <div style="padding:10px;"><input name="submit" type="submit" value="Import"/></div>
   </form>
  </div>
 </body>
</html>
