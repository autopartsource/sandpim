<?php
include_once('/var/www/html/class/pimClass.php');
$pim= new pim;
if(isset($_GET['appid']))
{
 $pim->toggleAppCosmetic(intval($_GET['appid']));
}?>
