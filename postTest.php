<!DOCTYPE html>
<html>
    <head>
        <?php include('/var/www/html/includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>POST test for sandpiperGetObjectList.php</h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <form action="sandpiperGetObjectList.php" method="post">
                    <div>Sliceid <input type="text" name="sliceid"/></div>
                    <div>Branch oid list</div>
                    <div><textarea name="oids" style="width:99%;height:400px;"></textarea></div>
                    <div><input type="submit" name="submit" value="post"/></div>
                </form>
            </div>

            <div class="contentRight"></div>
        </div>
                
        <!-- Footer -->
        <?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>