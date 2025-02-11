<?php

include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

// login check is intentionally left out so that this page can stand alone as an un-authenticaeted utility
$navCategory = 'utilities';

session_start();

$pim = new pim();
$logs = new logs;

/*
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'UUIDgenerator.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}
*/

 $digits=array(); 
 for($i=0; $i<32; $i++)
 {
   $digits[$i]=array('0'=>0,'1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0,'6'=>0,'7'=>0,'8'=>0,'9'=>0,'a'=>0,'b'=>0,'c'=>0,'d'=>0,'e'=>0,'f'=>0);
 }


$count=1;
if(isset($_GET['count'])){$count=intval($_GET['count']); if($count>1000000){$count=1000000;}}

if($count >=1000)
{// render the UUIDs into a download
  
 $uuids='';
 for($i=0; $i<=$count-1; $i++)
 {
  $uuid=$pim->uuidv4(); $uuids.=$uuid."\r\n";  
  
  // run analysis of generated list
  $strippeduuid= str_replace('-', '', $uuid);
  for($pos=0;$pos<32;$pos++)
  {
   $digit=substr($strippeduuid, $pos,1);
   if(array_key_exists($digit, $digits[$pos])){$digits[$pos][$digit]+=1;}else{$digits[$pos][$digit]=1;}
  }
 }

 
 $uuids.="\r\n--- Symbol distribution statistics by position ---\r\n";
 $uuids.="Position\tSymbol\tOccurrences\tDeviation percentage from expected\r\n";
 
 foreach($digits as $pos=>$digit)
 {
//  $uuids.= "\r\nhex digit distribution in position ".$pos."\r\n"; 
  foreach($digit as $symbol=>$occurrences)
  {
      //$uuids.='   '.$symbol.': '.$occurrences.' ('. number_format((($occurrences/$count)*100), 3).'%)'."\r\n";
      $uuids.=$pos."\t".$symbol."\t".$occurrences."\t". number_format(($occurrences-($count/16))/($count/16)*100, 3)."\r\n";
  }
 }


    
 $filename='SandPIM_UUIDs_'.date('Y-m-d').'.txt';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: text/html');
 header('Content-Length: ' . strlen($uuids));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $uuids;
 exit;
}




?>
<!DOCTYPE html>
<html lang="en" xml:lang="en">
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php if (isset($_SESSION['userid'])){include('topnav.php');} ?>

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">UUID Generator</h3>
                            
                        <div class="card-body">
                            <form>
                                <div style="padding:5px;">
                                <input name="submit" type="submit" value="Generate"/> <select name="count"><option value="1">1</option><option value="10">10</option><option value="100">100</option><option value="1000">1,000</option><option value="10000">10,000</option><option value="100000">100,000</option></select> UUIDs</div>
                            </form>
                            <div class="scroll">
                            <?php for($i=0; $i<=$count-1; $i++)
                            {
                                echo '<ul class="list-group list-group-flush"><li class="list-group-item">'.$pim->uuidv4().'</li></ul>';
                            }
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of Main Content -->
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
   
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->

        <!-- Footer -->
        
        <?php 
if (isset($_SESSION['userid']))
{
 include('./includes/footer.php');
}
else
{
?><div style="font-size: .75em; font-style: italic; color: #808080;"><?php  
 $logs->logSystemEvent('utilities', 0, 'UUID genertor used by:'.$_SERVER['REMOTE_ADDR'].' to generate '.$count.' UUIDs');
 echo 'These UUIDs are generated from the underlying Linux system /dev/urandom. They are version 4 (random) and variant 10. The remaining 122 bits are random. Selecting 1,000 (or more) in the drop-down will cause the result to be downloaded to your browser as a text file that includes a tab-delimited statistical analysis after the block of UUIDs.';
?></div><?php  
}
?>

        
        
        
        
    </body>
</html>