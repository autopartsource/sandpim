<script>    
    function refreshClipboard() {
        document.getElementById("clipboardBody").innerHTML = "<p></p>";

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajaxGetClipboard.php');
        xhr.onload = function ()
        {
            var response = JSON.parse(xhr.responseText);
            for (var i = 0; i < response.length; i++) {
                document.getElementById("clipboardBody").innerHTML += '<p id=clipboardObject_' + response[i].id + '>' + response[i].description + '<a type="button" class="btn btn-light" onclick="deleteClipboardObject(\'clipboardObject_' + response[i].id + '\')"><i class="bi bi-x"></a></p>';
            }
            console.log(response);
            
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
    }

</script>

<nav class="navbar can-stick navbar-expand-md navbar-dark bg-dark">
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>    
    <div id="navbarMenu" class="collapse navbar-collapse">
        <ul class="nav navbar-nav">
            <li<?php if ($navCategory == 'dashboard') {echo ' class="nav-item active"';} else {echo ' class="nav-item"';} ?>><a href="index.php" class="nav-link">Dashboard</a></li>
            <li<?php if ($navCategory == 'parts') {echo ' class="nav-item active"';} else {echo ' class="nav-item"';} ?>><a href="partsIndex.php" class="nav-link">Parts</a></li>
            <li<?php if ($navCategory == 'applications') {echo ' class="nav-item active"';} else {echo ' class="nav-item"';} ?>><a href="appsIndex.php" class="nav-link">Applications</a></li>
            <li<?php if ($navCategory == 'assets') {echo ' class="nav-item active"';} else {echo ' class="nav-item"';} ?>><a href="assetsIndex.php" class="nav-link">Assets</a></li>
            <li<?php if ($navCategory == 'reports') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownReports" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Reports
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownReports">
                    <a class="dropdown-item" href="missingProductDataReportForm.php">Missing/Invalid Part Data</a>
                    <a class="dropdown-item" href="invalidApplicationsReportForm.php">Invalid Applications</a>
                    <a class="dropdown-item" href="applicationHolesReportForm.php">Application Holes</a>
                    <a class="dropdown-item" href="applicationOverlapsReportForm.php">Application Overlaps</a>
                    <a class="dropdown-item" href="applicationNotesReportForm.php">Application Note Usage</a>
                </div>
            </li>
            <li<?php if ($navCategory == 'utilities') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUtilities" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Utilities
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownUtilities">
                    <a class="dropdown-item" href="basevidsToMMYinput.php">Convert BaseVehicleIDs to Makes/Models/Years</a>
                    <a class="dropdown-item" href="MMYtoBasevidsInput.php">Convert Makes/Models/Years to BaseVehicleIDs</a>
                    <a class="dropdown-item" href="convertAiExcelToACES4_1upload.php">Convert coded-value spreadsheet to ACES</a>
                    <a class="dropdown-item" href="./rhubarb7_1Index.php">Rhubarb 7.1</a>
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
                    <a class="dropdown-item" href="./pcdbTypeBrowser.php">Favorite PCdb parttypes</a>
                    <a class="dropdown-item" href="./pcdbPositionBrowser.php">Favorite PCdb positions</a>
                    <a class="dropdown-item" href="./competitiveBrandBrowser.php">Competitive Brands</a>
                    <a class="dropdown-item" href="./priceSheets.php">Price Sheets</a>
                    <a class="dropdown-item" href="./noteManager.php">Fitment Note Management</a>
                    <hr>
                    <a class="dropdown-item" href="./users.php">Users</a>
                    <a class="dropdown-item" href="./config.php">Configuration</a>
                </div>
            </li>
            <li<?php if ($navCategory == 'import/export') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownIO" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Import/Export
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownIO">
                    <a class="dropdown-item" href="./exportPIESselect.php">Export PIES xml</a>
                    <a class="dropdown-item" href="./exportACESselect.php">Export ACES xml</a>
                    <a class="dropdown-item" href="./exportFlatPartsSelect.php">Export flattened parts file</a>
                    <a class="dropdown-item" href="buyersGuideBuilder.php">Export Buyers Guide</a>
                    <a class="dropdown-item" href="./exportFlatAppsSelect.php">Export flattened applications files</a>
                    <a class="dropdown-item" href="./exportForPrintSelect.php">Export for print publishing</a>
                    <a class="dropdown-item" href="./exportCompetitorInterchangeSelect.php">Export Competitor Interchange</a>
                    <hr>
                    <a class="dropdown-item" href="./importACESupload.php">Import ACES xml file</a>
                    <a class="dropdown-item" href="./importACEStext.php">Import applications from text</a>
                    <a class="dropdown-item" href="./importPartText.php">Import parts from text</a>
                    <a class="dropdown-item" href="./importPackagingText.php">Import packaging from text</a>
                    <a class="dropdown-item" href="./importPricesText.php">Import prices from text</a>
                    <a class="dropdown-item" href="./importPartAttributeText.php">Import part attributes from text</a>
                    <a class="dropdown-item" href="./importInterchangeText.php">Import Competitor Interchange from text</a>
                    <a class="dropdown-item" href="./importBrandTableText.php">Import Brand Table text</a>
                    <a class="dropdown-item" href="./importAssetText.php">Import Asset metadata from text</a>
                    <hr>
                    <a class="dropdown-item" href="./backgroundJobs.php">Manage background import/export jobs</a>
                    <a class="dropdown-item" href="./AutoCareDownloads.php">AutoCare Downloads</a>
                    <a class="dropdown-item" href="./sandpiper.php">Sandpiper</a>
                </div>
            </li>
        </ul>
        <div class="ms-auto">
        
        </div>
        <div class="ms-auto">
        <ul class="nav navbar-nav">
            <a id="clipboardButton" class="btn btn-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#clipboard" aria-controls="clipboard" onclick="refreshClipboard()"><i class="bi bi-clipboard"></i></a>
            <a href="logout.php" class="nav-link">Logout (<?php echo $_SESSION['name'];?>)</a>
        </ul>
            
        </div>
    </div> 
</nav>

<div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="clipboard" aria-labelledby="clipboard">
  <div class="offcanvas-header">
    <span class="btn btn-danger" id="clearClipboard" onclick="clearClipboard()">CLEAR</span>
    <h5 class="offcanvas-title" id="clipboardLabel">Clipboard</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div id="clipboardBody" class="offcanvas-body">
  </div>
  
</div>