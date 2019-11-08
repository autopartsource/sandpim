<ul class="topnav">
  <li><a <?php if ($navCategory == 'dashboard') {echo 'class="navActive"';} ?>href="index.php">Dashboard</a></li>
  <li><a <?php if ($navCategory == 'parts') {echo 'class="navActive"';} ?>href="partsIndex.php">Parts</a></li>
  <li><a <?php if ($navCategory == 'applications') {echo 'class="navActive"';} ?>href="appsIndex.php">Applications</a></li>
  <li><a <?php if ($navCategory == 'assets') {echo 'class="navActive"';} ?>href="assetsIndex.php">Assets</a></li>
  <li><a <?php if ($navCategory == 'reports') {echo 'class="navActive"';} ?>href="reportsIndex.php">Reports</a></li>
  <li><a <?php if ($navCategory == 'utilities') {echo 'class="navActive"';} ?>href="utilitiesIndex.php">Utilities</a></li>
  <li><a <?php if ($navCategory == 'settings') {echo 'class="navActive"';} ?>href="settings.php">Settings</a></li>
  <li><a <?php if ($navCategory == 'import/export') {echo 'class="navActive"';} ?>href="ioIndex.php">Import/Export</a></li>
  <li class="right"><a href="logout.php">Logout (<?php echo $_SESSION['name'];?>)</a></li>
 </ul>