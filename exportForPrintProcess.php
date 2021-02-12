<?php
include_once('./class/bookClass.php');
include_once('./class/pimClass.php');


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

//set_time_limit(300);
//ini_set('memory_limit','1000M');

$book = new book;
$pim = new pim;
$vcdb = new vcdb;

$categories=array();

$categories[]=16;

$content=$book->getContent($categories);

//print_r($content);

// extract column keys
$columnkeys=array();
foreach($content as $make => $models)
{
 foreach($models as $model=>$blocks)
 {
  foreach($blocks as $block)
  {
   foreach($block['columns'] as $key=>$column)
   {
    $columnkeys[$key]='x';
   }
  }
 }
}


foreach($content as $make => $models)
{
    if($make !='Ford'){continue;}
    echo '<br/><hr/><div style="padding-left:100px;">'.$make.'</div><br/>';

    foreach($models as $model=>$blocks)
    {
        echo '<br/>'.$model.'<br/>';
        echo '<table class="table" border="1">';
        echo '<tr><th>Years</th><th>Notes</th>';
        foreach($columnkeys as $key=>$trash)
        {
         echo '<th>'.$key.'</th>';   
        }        
        echo '</tr>';
        
        foreach($blocks as $block)
        {
            echo '<tr>';
            if($block['startyear']==$block['endyear']){echo '<td>'.$block['startyear'].'</td>';}else{echo '<td>'.$block['startyear'].' - '.$block['endyear'].'</td>';}
            echo '<td>'.$block['qualifiers'].'</td>';

            foreach($columnkeys as $key=>$trash)
            {
             echo '<td>';
             if(array_key_exists($key,$block['columns']))
             {
              foreach($block['columns'][$key] as $item)
              {
               echo '<div>'.$item['part'].'</div>';
              }
             }
             echo '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
        
    }
}


?>