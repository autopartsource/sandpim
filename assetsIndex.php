<?php
include_once('./class/pimClass.php');
$navCategory = 'assets';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;

include('/var/www/html/includes/header.php');
?>

<div class="wrapper">
    <h1>Assets</h1>
</div>
<?php include('/var/www/html/includes/footer.php'); ?>