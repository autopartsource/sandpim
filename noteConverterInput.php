<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$navCategory = 'utilities';

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'noteConverterInput.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$logs = new logs;

$output='';

/*
 * Submit CRLF-delimited data in one textarea, and conversion dictionary in a second 
 * textarea. The conversion dictionary is 2 columns (tab-delimited) of input<tab>output
 * Split input data by character ('|') and run each section through the conversion
 * Each input row will produce an output row (even if the output row is 0-length)
 * 
 * 
 * 
 * 
 * 
 */




if (isset($_POST['submit']) && strlen($_POST['input'])>0) 
{ 
 $inputrecords = explode("\r\n", $_POST['input']);
 $translationrecords = explode("\r\n", $_POST['dictionary']);
 $translations=array();
 
 foreach($translationrecords as $translationrecord) 
 {     
  $fields = explode("\t", $translationrecord);
  if(count($fields)==2){$translations[$fields[0]]=$fields[1];}
 }
 
 $output='';
 
 foreach($inputrecords as $inputrecord) 
 {
  $outputfields=array();    
  $fields = explode(';', $inputrecord);
  
  foreach($fields as $field)
  {
   if(array_key_exists(trim($field), $translations))
   {// translation exists - blank is special case      
    if($translations[trim($field)]!='')
    {
     $outputfields[]=$translations[trim($field)];
    }
   }
   else
   {// no translation exists for input value - pass it through
    $outputfields[]=$field;
   }  
  }
  
  $output.=implode('; ',$outputfields)."\r\n";
 }
 
 $logs->logSystemEvent('UTILITIES', $_SESSION['userid'], 'note converter '.count($inputrecords).' records');
 
 $filename='conversion_'.date('Y-m-d').'_'. random_int(10000, 90000).'.txt';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: text/plain');
 header('Content-Length: ' . strlen($output));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $output;
 exit;
}

?>

<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Content Container -->
        <form method="post">
            <div class="container-fluid padding my-container">
                <div class="row padding my-row">


                    <div class="col-xs-12 col-md-6 my-col colMain">
                        <div class="card shadow-sm">
                            <h4 class="card-header text-start">Input</h4>
                            <div class="card-body">
                               <div><textarea name="input" class="form-control" rows="15"></textarea></div>
                            </div>
                        </div>                    
                    </div>

                    <div class="col-xs-12 col-md-5 my-col colMain">
                        <div class="card shadow-sm">
                            <h4 class="card-header text-start">Translation Dictionary</h4>
                            <div class="card-body">
                                <div><textarea name="dictionary" class="form-control" rows="15"></textarea></div>
                            </div>
                        </div>                    
                    </div>

                    <div class="col-xs-12 col-md-1 my-col colMain">
                        <div style="padding:10px;">
                            <input class="btn btn-primary" type="submit" name="submit" value="Convert"/>
                        </div>
                    </div>

                </div>
            </div>
        </form>
        
        <div>Input rows (CRLF-delimited) will be split on semicolon, translated if a 
            dictionary entry exists and then re-combined with a semicolon as the glue.
            The resulting records are written to a text file that is streamed as a download.
            Every input row will produce an output row - even when the input row is null.
            The dictionary format is two columns of tab-delimited text. The first column is
            the input, the second column is the output. A translation record with null
            in the second column will cause that input value to be suppressed from the output.
            
        </div>
        
        <!-- End of Content Container -->

        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>