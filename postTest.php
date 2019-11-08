<!DOCTYPE html>
<html>
 <head>
     <link rel="stylesheet" type="text/css" href="styles.css" />
 </head>
 <body>
 <?php include('topnav.php');?>
  <h1>POST test for sandpiperGetObjectList.php</h1>
  <form action="sandpiperGetObjectList.php" method="post">
   <div>Sliceid <input type="text" name="sliceid"/></div>
   <div>Branch oid list</div>
   <div><textarea name="oids" style="width:99%;height:400px;"></textarea></div>
   <div><input type="submit" name="submit" value="post"/><div>
  </form>
 </body>
</html>

