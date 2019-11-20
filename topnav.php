<div class="topnav" id="myTopnav">
  <a<?php if ($navCategory == 'dashboard') {echo ' class="navActive"';} ?> href="index.php">Dashboard</a>
  <a<?php if ($navCategory == 'parts') {echo ' class="navActive"';} ?> href="partsIndex.php">Parts</a>
  <a<?php if ($navCategory == 'applications') {echo ' class="navActive"';} ?> href="appsIndex.php">Applications</a>
  <a<?php if ($navCategory == 'assets') {echo ' class="navActive"';} ?> href="assetsIndex.php">Assets</a>
  <a<?php if ($navCategory == 'reports') {echo ' class="navActive"';} ?> href="reportsIndex.php">Reports</a>
  <a<?php if ($navCategory == 'utilities') {echo ' class="navActive"';} ?> href="utilitiesIndex.php">Utilities</a>
  <a<?php if ($navCategory == 'settings') {echo ' class="navActive"';} ?> href="settings.php">Settings</a>
  <a<?php if ($navCategory == 'import/export') {echo ' class="navActive"';} ?> href="ioIndex.php">Import/Export</a>
  <a href="logout.php">Logout (<?php echo $_SESSION['name'];?>)</a>
  <a href="javascript:void(0);" class="icon" onclick="myFunction()">
    <i class="fa fa-bars"></i>
  </a>
</div>

<script>
function myFunction() {
  var x = document.getElementById("myTopnav");
  if (x.className === "topnav") {
    x.className += " responsive";
  } else {
    x.className = "topnav";
  }
}
</script>