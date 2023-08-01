<script>
    function refreshClipboard() {
        document.getElementById("clipboardBody").innerHTML = "<p></p>";

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajaxGetClipboard.php');
        xhr.onload = function ()
        {
            var response = JSON.parse(xhr.responseText);
            if (parseInt(response.length) > 0) {
                document.getElementById("clipboardButton").removeAttribute("hidden");
                document.getElementById("clipboardButton").setAttribute("class", "btn btn-success position-relative");
                for (var i = 0; i < response.length; i++) {
                    document.getElementById("clipboardBody").innerHTML += '<p id=clipboardObject_' + response[i].id + '>' + response[i].description + ' <a type="button" class="btn btn-sm btn-outline-danger" onclick="deleteClipboardObject(\'clipboardObject_' + response[i].id + '\')"><i class="bi bi-x"></a></p>';
                }
                document.getElementById("clipboardBadge").innerHTML=response.length;
            }
            else {
                document.getElementById("clipboardButton").setAttribute("hidden", "");
            } 
        };
        xhr.send();
    }
    

    function deleteClipboardObject(id) {
        var clipboardObject = document.getElementById(id);
        var chunks = id.split("_");
        clipboardObject.parentNode.removeChild(clipboardObject);

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajaxDeleteClipboard.php?id=' + chunks[1]);
        xhr.onload = function ()
        {
            refreshClipboard();
        };
        xhr.send();
    }

    function clearClipboard() {
        document.getElementById("clipboardBody").innerHTML = "<p></p>";
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajaxDeleteClipboard.php');
        xhr.onload = function ()
        {
        };
        xhr.send();
        document.getElementById("clipboardButton").setAttribute("hidden", "");
    }


</script>

</script>

<style>
.navbar-custom {background-color: #<?php if(isset($pim)){echo $pim->navbarColor();}else{echo '404040';}?>;}
.navbar-custom .navbar-brand,
.navbar-custom .navbar-text {color: #ffcc00;}
.navbar-custom .navbar-nav .nav-link {color: #ffbb00;}
.navbar-custom .nav-item.active .nav-link,
.navbar-custom .nav-item:focus .nav-link,
.navbar-custom .nav-item:hover .nav-link {color: #ffffff;}
.navbar-custom .navbar-nav .dropdown-menu {background-color: #ddaa11;}
.navbar-custom .navbar-nav .dropdown-item {color: #000000;}
.navbar-custom .navbar-nav .dropdown-item:hover,.navbar-custom .navbar-nav .dropdown-item:focus {color: #404040; background-color: #ffffff;}
</style>

<nav class="navbar can-stick navbar-expand-md navbar-custom">
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>    
    <div id="navbarMenu" class="collapse navbar-collapse">
        <ul class="nav navbar-nav">
            <li<?php if ($navCategory == 'dashboard') {echo ' class="nav-item active"';} else {echo ' class="nav-item"';} ?>><a href="index.php" class="nav-link">Home</a></li>
            <li<?php if ($navCategory == 'parts') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownParts" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Parts
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownParts">
                    <a class="dropdown-item" href="partsIndex.php">Search Our Parts</a>
                    <a class="dropdown-item" href="interchangeIndex.php">Search Competitor Parts</a>
                    <a class="dropdown-item" href="newPart.php">Create New Part</a>
                </div>
            </li>
                        
            <li<?php if ($navCategory == 'applications') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownApps" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Applications
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownApps">
                    <a class="dropdown-item" href="appsIndex.php">Make/Model/Year apps</a>
 <?php /*                   <a class="dropdown-item" href="equipmentAppsIndex.php">Make/Equipment apps</a> */?>
                </div>
            </li>
            <li<?php if ($navCategory == 'assets') {echo ' class="nav-item active"';} else {echo ' class="nav-item"';} ?>><a href="assetsIndex.php" class="nav-link">Assets</a></li>
            <li<?php if ($navCategory == 'reports') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownReports" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Reports
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownReports">
 <?php /*                   <a class="dropdown-item" href="applicationGuideReportForm.php">Application Guide</a>
                    <a class="dropdown-item" href="missingProductDataReportForm.php">Missing/Invalid Part Data</a>
                    <a class="dropdown-item" href="invalidApplicationsReportForm.php">Invalid Applications</a>
                    <a class="dropdown-item" href="applicationHolesReportForm.php">Application Holes</a>
                    <a class="dropdown-item" href="applicationOverlapsReportForm.php">Application Overlaps</a>
                    <a class="dropdown-item" href="applicationNotesReportForm.php">Application Note Usage</a>
 */?>
                    <a class="dropdown-item" href="assetCoverageReportForm.php">Asset Matrix</a>
                    <a class="dropdown-item" href="assetHitlistReportForm.php">Asset Hitlist</a>
                    <a class="dropdown-item" href="partAttributesReportForm.php">Attribute Matrix</a>
                    <a class="dropdown-item" href="partExpiReportForm.php">EXPI Matrix</a>
                    <a class="dropdown-item" href="partDescriptionsReportForm.php">Descriptions Matrix</a>
                    <a class="dropdown-item" href="partPackagesReportForm.php">Packages Matrix</a>
                    <a class="dropdown-item" href="interchangeCoverageReportForm.php">Interchange Matrix</a>
                    <a class="dropdown-item" href="pricingCoverageReportForm.php">Pricing Matrix</a>                    
                    <a class="dropdown-item" href="competitorCoverageReportForm.php">Competitor Coverage</a>
                    <a class="dropdown-item" href="vioCoverageReportForm.php">VIO Coverage</a>
                </div>
            </li>
            <li<?php if ($navCategory == 'utilities') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUtilities" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Utilities
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownUtilities">
                    <a class="dropdown-item" href="pairPart.php">Two-Part Match-maker</a>
                    <a class="dropdown-item" href="bundlePart.php">Full-Vehicle Kit Match-maker</a>
                    <a class="dropdown-item" href="clearDataSelect.php">Clear Data</a>
                    <a class="dropdown-item" href="buyersGuideBuilder.php">Buyers Guide Builder</a>
                    <a class="dropdown-item" href="basevidsToMMYinput.php">Convert BaseVehicleIDs to Makes/Models/Years</a>
                    <a class="dropdown-item" href="MMYtoBasevidsInput.php">Convert Makes/Models/Years to BaseVehicleIDs</a>
                    <a class="dropdown-item" href="convertAiExcelToACES4_1upload.php">Convert coded-value spreadsheet to ACES</a>
                    <a class="dropdown-item" href="./rhubarb7_1Index.php">Rhubarb 7.1</a>
                    <a class="dropdown-item" href="./rhubarb6_7Index.php">Rhubarb 6.7</a>
                    <a class="dropdown-item" href="./wmSessions.php">Walmart Content uploader</a>
                    
                    <a class="dropdown-item" href="UUIDgenerator.php">UUID Generator</a>
                </div>
            </li>
            <li<?php if ($navCategory == 'settings') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownSettings" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Settings
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownSettings">
                    <a class="dropdown-item" href="./partCategories.php">Part Categories</a>
                    <a class="dropdown-item" href="./deliveryGroups.php">Delivery Groups</a>
                    <a class="dropdown-item" href="./receiverProfiles.php">Receiver Profiles</a>
                    <a class="dropdown-item" href="./vcdbMakeBrowser.php">Favorite Makes</a>
                    <a class="dropdown-item" href="./pcdbTypeBrowser.php">Favorite Part Types</a>
                    <a class="dropdown-item" href="./pcdbPositionBrowser.php">Favorite Application Positions</a>
                    <a class="dropdown-item" href="./competitiveBrandBrowser.php">Favorite Brands</a>
                    <a class="dropdown-item" href="./priceSheets.php">Price Sheets</a>
                    <a class="dropdown-item" href="./noteManager.php">Fitment Note Management</a>
                    <hr>
                    <a class="dropdown-item" href="./users.php">Users</a>
                    <a class="dropdown-item" href="./config.php">Configuration</a>
                    <a class="dropdown-item" href="./backgroundJobs.php">Manage background import/export jobs</a>
                    <a class="dropdown-item" href="./sandpiper.php">Sandpiper</a>
                </div>
            </li>
            <li<?php if ($navCategory == 'import') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownIO" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Import
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownIO">
                    <a class="dropdown-item" href="./importACESupload.php">ACES file upload</a>
                    <a class="dropdown-item" href="./importACESxml.php">ACES xml</a>
                    <a class="dropdown-item" href="./importACEStext.php">Applications from text</a>
                    <a class="dropdown-item" href="./importACESexcelUpload.php">Applications from spreadsheet</a>
                    <a class="dropdown-item" href="./importPartsText.php">Parts from text</a>
                    <a class="dropdown-item" href="./importPartDescriptionText.php">Part descriptions from text</a>
                    <a class="dropdown-item" href="./importPartAttributeText.php">Part attributes from text</a>
                    <a class="dropdown-item" href="./importPackagingText.php">Packaging from text</a>
                    <a class="dropdown-item" href="./importPricesText.php">Prices from text</a>
                    <a class="dropdown-item" href="./importInterchangeText.php">Competitor Interchange from text</a>
                    <a class="dropdown-item" href="./importAssetText.php">Asset metadata from text</a>
                    <a class="dropdown-item" href="./importPartEXPIText.php">EXPI from text</a>
                    <a class="dropdown-item" href="./updatePartBalances.php">Part balance data from text</a>
                    <a class="dropdown-item" href="./updateKitComponents.php">Kit components from text</a>
                    <a class="dropdown-item" href="./importBrandTableText.php">AutoCare Brand Table text</a>
                    <a class="dropdown-item" href="./AutoCareDownloads.php">AutoCare Downloads (VCdb, PCdb, PAdb, Qdb)</a>
                    <a class="dropdown-item" href="./importExperianVIOtext.php">Experian VIO</a>
                </div>
            </li>
            <li<?php if ($navCategory == 'export') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownIO" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Export
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownIO">
                    <a class="dropdown-item" href="./exportPIESselect.php">PIES xml</a>
                    <a class="dropdown-item" href="./exportACESselect.php">ACES xml</a>
                    <a class="dropdown-item" href="./exportAPA65pricefileSelect.php">APA/AWDA (6.5) pricesheet</a>
                    <a class="dropdown-item" href="./exportWalmartSelect.php">Parts in Walmart format spreadsheet</a>
                    <a class="dropdown-item" href="./exportFlatPartsSelect.php">Flattened parts file</a>
                    <a class="dropdown-item" href="./exportFlatAppsSelect.php">Flattened applications file</a>
                    <a class="dropdown-item" href="./exportForBasicPrintSelect.php">Application guide PDF (basic)</a>
                    <a class="dropdown-item" href="./exportForMulticolumnPrintSelect.php">Application guide PDF (multi-Column)</a>
                    <a class="dropdown-item" href="./exportCompetitorInterchangeSelect.php">Competitor Interchange</a>
                </div>
            </li>
        </ul>
        <div class="ms-auto">
            <form action="./showPart.php">
                <input name="partnumber" type="text" id="partsearch" size="10"/><input type="submit" name="submit" value="Go"/>
            </form>
        </div>
        
        <div class="ms-auto">
        <ul class="nav navbar-nav">
            <button id="clipboardButton" type="button" class="btn btn-primary position-relative" type="button" data-bs-toggle="offcanvas" data-bs-target="#clipboard" aria-controls="clipboard" onclick="refreshClipboard()" hidden>
                <i class="bi bi-clipboard"></i> <span id="clipboardBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"><span class="visually-hidden"> </span></span>
            </button>
            <a href="logout.php" class="nav-link">Logout (<?php echo $_SESSION['name'];?>)</a>
        </ul>
            
        </div>
    </div> 
</nav>

<div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="clipboard" aria-labelledby="clipboard">
  <div class="offcanvas-header">
    <span class="btn btn-sm btn-outline-danger" id="clearClipboard" onclick="clearClipboard()">CLEAR</span>
    <h5 class="offcanvas-title" id="clipboardLabel" style="margin-left:10px;">Clipboard</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div id="clipboardBody" class="offcanvas-body">
  </div>
  
</div>
<script>
    var el = document.getElementById("clipboardButton");
    el.addEventListener("onload", refreshClipboard());
</script>