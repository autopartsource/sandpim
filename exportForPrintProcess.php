<?php
include_once('./class/bookClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

//set_time_limit(300);
//ini_set('memory_limit','1000M');


$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exportForPrintProcess.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$book = new book;
$vcdb = new vcdb;

$categories=array(114,115);

$content=$book->getContent($categories);


echo '<table style="border-collapse: collapse;table-layout:fixed;width:650px;"><tr style="background-color:#c0c0c0;border-collapse: collapse;"><th style="border:1px solid black; padding: 3px;width:20%;">Years</th><th style="border:1px solid black; padding: 3px;width:65%;">Notes</th><th style="border:1px solid black; padding: 3px;width:15%;">Parts</th></tr></table>';




foreach($content as $make => $models)
{
 //if($make !='Honda'){continue;}
 echo '<div style="font-size:200%;">'.$make.'</div>';  

 foreach($models as $model=>$blocks)
 {
  echo '<div style="padding:10px 0px 1px 1px;margin:2px;font-size:150%;">'.$model.'</div>';

//  echo '<table style="border-collapse: collapse;table-layout:fixed;width:600px;"><tr style="background-color:#c0c0c0;border-collapse: collapse;"><th style="border:1px solid black; padding: 5px;width:15%;">Years</th><th style="border:1px solid black; padding: 5px;width:65%;">Notes</th><th style="border:1px solid black; padding: 5px;width:20%;">Parts</th></tr>';
  
  echo '<table style="border-collapse: collapse;table-layout:fixed;width:650px;">';

  foreach($blocks as $block)
  {
   $rownumber=0;
   foreach($block['qualifierblocks'] as $qualifiers=>$parts)
   {
    echo '<tr style="border-collapse: collapse;">';
    if($rownumber==0)
    {
        $niceyears=$block['startyear'].' - '.$block['endyear'];
        if($block['startyear']==$block['endyear']){$niceyears=$block['startyear'];}
        echo '<td rowspan="'.count($block['qualifierblocks']).'" style="border:1px solid black; padding: 3px;text-align:center;width:20%">'.$niceyears.'</td>';
    }
    echo '<td style="border:1px solid black; padding: 3px;width:65%;">'.$qualifiers.'</td>';
    echo '<td style="border:1px solid black; padding: 3px;text-align:center;width:15%;">';    
         
    foreach($parts as $part)
    {
     echo '<div>'.$part.'</div>';
    }
    echo '</td>';
    echo '</tr>';
    $rownumber++;
   }
  }
    
  echo '</table>';
 }
}


?>