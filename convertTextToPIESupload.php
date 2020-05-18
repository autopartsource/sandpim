<?php
require('XLSXReader.php');
$xlsx = new XLSXReader('PIES_7-1_flat_template_2020-05-13.xlsx');
$sheetNames = $xlsx->getSheetNames();

if(in_array('Items',$sheetNames) && in_array('Header',$sheetNames))
{
    echo 'Found header and item sheets';
    $headerSheet=$xlsx->getSheetData('Header');
    $itemsSheet=$xlsx->getSheetData('Items');
    
    print_r($headerSheet[4]);
    
}



?>
