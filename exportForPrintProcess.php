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

print_r($content);

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
    if($make !='Honda'){continue;}
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
        
        // compress blocks of same years
        $compressed=array();
        foreach($blocks as $block)
        {
            $niceyearrange = $block['startyear'].' - '.$block['endyear']; if($block['startyear']==$block['endyear']){$niceyearrange = $block['startyear'];}
            $compressed[$niceyearrange][]=$block;
        }        

        foreach($compressed as $niceyearrange=>$blocks)
        {
            echo '<tr>';
            echo '<td>'.$niceyearrange.'</td>';
            echo '<td>';

            
            echo '<table class="table" border="1">';
            foreach($blocks as $block)
            {
             echo '<tr>';
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
            
            echo '</td>';
            echo '</tr>';
            
            
        }


        
        
        /*
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
         * 
         * 
         * 
         */
        
        
        echo '</table>';
        
    }
}


?>