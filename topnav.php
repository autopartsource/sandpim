<nav class="navbar can-stick navbar-expand-md navbar-dark bg-dark">
    <a href="index.php" class="navbar-brand">sandPIM</a>
    <button class="navbar-toggler" data-toggle="collapse" data-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>    
    <div id="navbarMenu" class="collapse navbar-collapse">
        <ul class="nav navbar-nav">
            <li<?php if ($navCategory == 'dashboard') {echo ' class="nav-item active"';} else {echo ' class="nav-item"';} ?>><a href="index.php" class="nav-link">Dashboard</a></li>
            <li<?php if ($navCategory == 'parts') {echo ' class="nav-item active"';} else {echo ' class="nav-item"';} ?>><a href="partsIndex.php" class="nav-link">Parts</a></li>
            <li<?php if ($navCategory == 'applications') {echo ' class="nav-item active"';} else {echo ' class="nav-item"';} ?>><a href="appsIndex.php" class="nav-link">Applications</a></li>
            <li<?php if ($navCategory == 'assets') {echo ' class="nav-item active"';} else {echo ' class="nav-item"';} ?>><a href="assetsIndex.php" class="nav-link">Assets</a></li>
            <li<?php if ($navCategory == 'reports') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Reports
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                    <a class="dropdown-item" href="partReferencesReportForm.php">Invalid Part Data</a>
                    <a class="dropdown-item" href="applicationReferencesReportForm.php">Invalid Application Data</a>
                    <a class="dropdown-item" href="missingProductDataReportForm.php">Product Data Holes</a>
                    <a class="dropdown-item" href="applicationHolesReportForm.php">Application Holes</a>
                    <a class="dropdown-item" href="applicationOverlapsReportForm.php">Application Overlaps</a>
                    <a class="dropdown-item" href="applicationNotesReportForm.php">Application Note Usage</a>
                </div>
            </li>
            <li<?php if ($navCategory == 'utilities') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Utilities
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                    <a class="dropdown-item" href="basevidsToMMYinput.php">Convert BaseVehicleIDs to Makes/Models/Years</a>
                    <a class="dropdown-item" href="MMYtoBasevidsInput.php">Convert Makes/Models/Years to BaseVehicleIDs</a>
                </div>
            </li>
            <li<?php if ($navCategory == 'settings') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Settings
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                    <a class="dropdown-item" href="./receiverProfiles.php">Receiver Profiles</a>
                    <a class="dropdown-item" href="./partCategories.php">Part Categories</a>
                    <a class="dropdown-item" href="./priceSheets.php">Price Sheets</a>
                    <a class="dropdown-item" href="./pcdbTypeBrowser.php">Favorite PCdb parttypes</a>
                    <a class="dropdown-item" href="./pcdbPositionBrowser.php">Favorite PCdb positions</a>
                    <a class="dropdown-item" href="./competitiveBrandBrowser.php">Competitive Brands</a>
                    <a class="dropdown-item" href="./noteManager.php">Fitment Note Management</a>
                    <a class="dropdown-item" href="./users.php">Users</a>
                    <a class="dropdown-item" href="./config.php">Configuration</a>
                </div>
            </li>
            <li<?php if ($navCategory == 'import/export') {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Import/Export
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                    <a class="dropdown-item" href="./exportPIESselect.php">Export PIES xml</a>
                    <a class="dropdown-item" href="./exportACESselect.php">Export ACES xml</a>
                    <a class="dropdown-item" href="./exportFlatPartsSelect.php">Export flattened parts file</a>
                    <a class="dropdown-item" href="buyersGuideBuilder.php">Export Buyers Guide</a>
                    <a class="dropdown-item" href="./exportFlatAppsSelect.php">Export flattened applications files</a>
                    <a class="dropdown-item" href="./exportForPrintSelect.php">Export for print publishing</a>
                    <a class="dropdown-item" href="./exportCompetitorInterchangeSelect.php">Export Competitor Interchange</a>
                    <a class="dropdown-item" href="./importACESupload.php">Import ACES xml file</a>
                    <a class="dropdown-item" href="./importACEStext.php">Import applications from structured text</a>
                    <a class="dropdown-item" href="./importPartText.php">Import parts from structured text</a>
                    <a class="dropdown-item" href="./importPricesText.php">Import prices from structured text</a>
                    <a class="dropdown-item" href="./importPartAttributeText.php">Import part attributes from structured text</a>
                    <a class="dropdown-item" href="./importInterchangeText.php">Import Competitor Interchange from structured text</a>
                    <a class="dropdown-item" href="./importBrandTableText.php">Import Brand Table text</a>
                    <a class="dropdown-item" href="./backgroundJobs.php">Manage background import/export jobs</a>
                    <a class="dropdown-item" href="./rhubarb7_1Index.php">Rhubarb 7.1</a>
                </div>
            </li>
        </ul>
        <div class="ml-auto">
        <ul class="nav navbar-nav">
            <a href="logout.php" class="nav-link">Logout (<?php echo $_SESSION['name'];?>)</a>
        </ul>
        </div>
    </div> 
</nav>