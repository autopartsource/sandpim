<!DOCTYPE html>
<html>
 <head>
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <h1>POST test for sandpiperGetObjectList.php</h1>
  <form action="sandpiperGetObjectList.php" method="post">
   <div>Sliceid <input type="text" name="sliceid"/></div>
   <div>Branch oid list</div>
   <div><textarea name="oids" style="width:99%;height:400px;"></textarea></div>
   <div><input type="submit" name="submit" value="post"/><div>
  </form>
 </div>
 </body>
</html>

