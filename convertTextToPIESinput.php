<?php
include_once('./class/pimClass.php');
$navCategory = 'import/export';
session_start();

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php if (isset($_SESSION['userid'])){include('topnav.php');} ?>

        <!-- Header -->
        <h1>Build PIES xml from spreadsheet template</h1>
        <h2>Step 1: copy/paste data from each tab in the <a href="./PIES_7-1_flat_template_2020-05-12.xlsx">spreadsheet</a></h2>

        <div class="wrapper">
            <div class="contentLeft"></div>

            
            <!-- Main Content -->
            <div class="contentMain">
                <form method="post" action="convertTextToPIESprocess.php">
                    <div style="padding:10px;"><div>Header</div>
                        <textarea name="header" rows="6" cols="130"></textarea>
                    </div>

                    <div style="padding:10px;"><div>MarketingCopy</div>
                        <textarea name="marketingcopy" rows="6" cols="130"></textarea>
                    </div>

                    <div style="padding:10px;"><div>Items</div>
                        <textarea name="items" rows="6" cols="130"></textarea>
                    </div>

                    <div style="padding:10px;"><div>Descriptions</div>
                        <textarea name="descriptions" rows="6" cols="130"></textarea>
                    </div>

                    <div style="padding:10px;"><div>Prices</div>
                        <textarea name="prices" rows="6" cols="130"></textarea>
                    </div>

                    <div style="padding:10px;"><div>EXPI</div>
                        <textarea name="expis" rows="6" cols="130"></textarea>
                    </div>

                    <div style="padding:10px;"><div>Attributes</div>
                        <textarea name="attributes" rows="6" cols="130"></textarea>
                    </div>
                    
                    <div style="padding:10px;"><div>Packages</div>
                        <textarea name="packages" rows="6" cols="130"></textarea>
                    </div>

                    <div style="padding:10px;"><div>Interchanges</div>
                        <textarea name="interchanges" rows="6" cols="130"></textarea>
                    </div>

                    <div style="padding:10px;"><div>Digital Assets</div>
                        <textarea name="assets" rows="6" cols="130"></textarea>
                    </div>
                    <input type="submit" name="submit" value="Next"/>
                    <input type="checkbox" name="showtext"/>
                    
                   </form>
               
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('./includes/footer.php'); ?>
    </body>
</html>