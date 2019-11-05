<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;
$pim=new pim;

$partcategories=$pim->getPartCategories();



?>
<!DOCTYPE html>
<html>
 <head>
  <style>
   .apppart {padding: 1px; border: 1px solid #808080; margin: 0px; background-color:#d0f0c0;}
   .apppart-cosmetic {padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#33FFD7;}
   .apppart-hidden {padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#FFD433;}
   .apppart-deleted { padding: 1px; border: 1px solid #aaaaaa; margin:0px; background-color:#FF5533;}

   a:link {color: blue; text-decoration: none;}
   a:visited {color: blue; text-decoration: none;}
   a:hover {color: gray; text-decoration: none;}
   a:active {color: blue; text-decoration: none;}

   table {border-collapse: collapse;}
   table, th, td {border: 1px solid black;}
  </style>
 </head>
 <body>
  <?php include('topnav.php');?>
  <h1>Export PIES xml</h1>
  <form action="exportPIESstream.php" method="get">

   <div style="padding:20px;border:1px solid blue;width:650px;">
    <h3>Header Elements</h3>
    <div style="float:left;padding:5px;"><input type="checkbox" name="testFile" value="yes"/></div><div style="float:left;padding:5px;">Flag This file as &quot;Testing&quot;</div><div style="clear:both;"></div>
    <div style="float:left;padding:5px;">File Title</div><div style="float:left;padding:5px;"><input type="text" name="title" size="30"/></div>
    <div style="float:left;padding:5px;">Effective Date</div><div style="float:left;padding:5px;"><input type="text" name="blanketEffectiveDate" value="<?=date("Y-m-d");?>" size="10"/></div><div style="clear:both;"></div>
    <div style="float:left;padding:5px;">Brand Owner ID</div><div style="float:left;padding:5px;"><input type="text" name="brandOwnerAAIAID" value="" size="6"/></div><div style="float:left;padding:5px;"><a href="http://parentsupplierbrand.pricedex.com/Default.aspx" target="_blank" title="visit AutoCare.org for brand listing in new window">?</a></div><div style="clear:both;"></div>
    <div style="float:left;padding:5px;">Technical Contact</div><div style="float:left;padding:5px;"><input type="text" name="technicalContact" value="" size="15"/></div>
    <div style="float:left;padding:5px;">Contact Email</div><div style="float:left;padding:5px;"><input type="text" name="contactEmail" value="" size="30"/></div><div style="clear:both;"></div>
   </div>

   <hr/>

   <div style="padding:20px;border:1px solid blue;width:350px;">
    <h3>Part Categories to include</h3>
    <?php foreach($partcategories as $partcategory){echo '<div><input type="checkbox" id="partcategory_'.$partcategory['id'].'" name="partcategory_'.$partcategory['id'].'" checked><label for="partcategory_'.$partcategory['id'].'">'.$partcategory['name'].'</label></div>';}?>
   </div>

   <hr/>

   <div style="padding:20px;border:1px solid blue;width:650px;">
    <h3>Item-Level options</h3>
    <div style="padding:15px;">
    <div style="float:left;padding:5px;">AAIA BrandID</div><div style="float:left;padding:5px;"><input type="text" name="AAIABrandID" value="" size="6"/></div><div style="float:left;padding:5px;"><a href="http://parentsupplierbrand.pricedex.com/Default.aspx" target="_blank" title="visit AutoCare.org for brand listing in new window">?</a></div><div style="clear:both;"></div>
    <div style="float:left;padding:3px;"><input type="checkbox" name="includeDescription" value="yes"/></div><div style="float:left;padding:3px;">Description (S2K DESC1)</div><div style="clear:both;"></div>
    <div style="float:left;padding:3px;"><input type="checkbox" name="includePackageWeight" value="yes"/></div><div style="float:left;padding:3px;">Packaged Weight</div><div style="clear:both;"></div>
    <div style="float:left;padding:3px;"><input type="checkbox" name="includeCountryOfOrigin" value="yes"/></div><div style="float:left;padding:3px;">Country Of Origin</div>
    <div style="float:left;padding:3px;"><select name="countryOfOrigin"><option value="US">United States</option><option value="CA">Canada</option><option value="CN">China</option><option value="BR">Brazil</option></select></div><div styl$
    <div style="float:left;padding:3px;"><input type="checkbox" name="includePackageDims" value="yes"/></div><div style="float:left;padding:3px;">Packaged Diminsions</div><div style="clear:both;"></div>
    <div style="float:left;padding:3px;"><input type="checkbox" name="includeAttributes" value="yes"/></div><div style="float:left;padding:3px;">Product Attributes</div><div style="clear:both;"></div>
    <div style="float:left;padding:3px;"><input type="checkbox" name="includeAssets" value="yes"/></div><div style="float:left;padding:3px;">Digital Assets</div>  <div style="float:left;padding:3px;">
    <input type="checkbox" name="includePrivateAssets" value="yes"/></div><div style="float:left;padding:3px;">Include Private Assets</div><div style="clear:both;"></div>
    <div style="float:left;padding:3px;"><input type="checkbox" name="includeJobberPrice" value="yes"/></div><div style="float:left;padding:3px;">Jobber Price</div>
    <div style="float:left;padding-left:15px;padding-top:3px;padding-bottom:3px;">Pricesheet Number</div> <div style="float:left;padding:3px;"><input type="text" name="priceSheetNumberJobber" value="" size="10"/></div>
    <div style="float:left;padding-left:15px;padding-top:3px;padding-bottom:3px;">Effective Date</div>
    <div style="float:left;padding:3px;">

    <input type="text" name="priceSheetJobberEffectiveDate" value="<?=date('Y-m-d');?>" size="10"/><div style="clear:both;"></div>
    <div style="float:left;padding:3px;"><input type="checkbox" name="includeNetPrice" value="yes"/></div>
    <div style="float:left;padding:3px;">Net Price</div><div style="float:left;padding-left:15px;padding-top:3px;padding-bottom:3px;">Pricesheet Number</div>
    <div style="float:left;padding:3px;"><input type="text" name="priceSheetNumberNet" value="" size="10"/></div><div style="float:left;padding-left:15px;padding-top:3px;padding-bottom:3px;">Effective Date</div>
    <div style="float:left;padding:3px;"><input type="text" name="priceSheetNetEffectiveDate" value="<?=date('Y-m-d');?>" size="10"/></div><div style="clear:both;"></div>
    <div style="float:left;padding:3px;"><input type="checkbox" name="supersession" value="yes"/></div><div style="float:left;padding:3px;">Supersessions</div><div style="clear:both;"></div>
    <div style="float:left;padding:3px;"><input type="checkbox" name="pop" value="yes"/></div><div style="float:left;padding:3px;">POP code</div><div style="clear:both;"></div>
    <div style="float:left;padding:3px;"><input type="checkbox" name="lifecycle" value="yes"/></div><div style="float:left;padding:3px;">Lifecycle code</div><div style="clear:both;"></div>
   </div>
   <input type="submit" name="submit" value="Download"/>
  </form>
 </body>
</html>
