<?php
include_once('/var/www/html/class/pimClass.php');

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('/var/www/html/includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
<?php include('topnav.php'); ?>

        <!-- Header -->
        <h1>Build PIES xml from structured text</h1>
        <h2>Step 1: copy/paste data from spreadsheet of product data</h2>

        <div class="wrapper">
            <div class="contentLeft"></div>

            
            <!-- Main Content -->
            <div class="contentMain">
                <form method="post" action="importPartTextProcess.php">
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
                        <textarea name="expi" rows="6" cols="130"></textarea>
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
                    
                    
                    
                    <div style="padding:10px;"><input name="submit" type="submit" value="Next"/></div>
                </form>
                
                <?php if($piesxml !=''){ ?><div style="padding:10px;"><textarea rows="30" cols="100"><?php echo $piesxml;?></textarea></div><?php }?>

                
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
<?php include('/var/www/html/includes/footer.php'); ?>
    </body>
</html>