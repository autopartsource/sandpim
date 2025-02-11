<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');

$navCategory = 'export';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exportBuyersGuideStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if(!isset($_SESSION['userid']))
{
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
 exit;
}


$user=new user();
$logs=new logs();

$streamXLSX=false;
        
$filename='';
$xlsxdata='';

$receiverprofileid=intval($_POST['receiverprofile']);
$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$profile=$pim->getReceiverprofileById($receiverprofileid);
$profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';
$profilename=$profile['name'];

$profileelements=explode(';',$profiledata);
$keyedprofile=array();
foreach($profileelements as $profileelement)
{
 $bits=explode(':',$profileelement);
 if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}
}

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$appscount=$pim->countAppsByPartcategories($partcategories);

$pricesheetnumber=''; if(isset($_POST['pricesheetnumber'])){$pricesheetnumber=$_POST['pricesheetnumber'];}

$lifecyclestatuslist=array();
$lifecyclestatusestemp=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
foreach ($lifecyclestatusestemp as $status){$lifecyclestatuslist[]=$status['lifecyclestatus'];}


if($appscount>=0)
{ // dataset is too big - this export will be handled by the housekeeper (cron) and written to a temp directory for download
 $streamXML=false;
 
 $randomstring=$pim->newoid();
 $clientfilename='buyersguide_'.$keyedprofile['DocumentTitle'].'_'.$randomstring.'_'.date('Y-m-d').'.xlsx';
 $localfilename=__DIR__.'/ACESexports/'.$randomstring;
 $token=$pim->createBackgroundjob('BuyersGuideExport','started',$_SESSION['userid'],'',$localfilename,'receiverprofile:'.$receiverprofileid.';DocumentTitle:'.$keyedprofile['DocumentTitle'].';pricesheetnumber:'.$pricesheetnumber,date('Y-m-d H:i:s'),'text/xml',$clientfilename);
 $logs->logSystemEvent('Export', $_SESSION['userid'], 'Buyers guide file ['.$clientfilename.'] export setup for houskeeper; apps:'.$appscount.' by:'.$_SERVER['REMOTE_ADDR']);
 echo 'This export will contain '.$appscount.' apps. It will be processed by the houskeeper and be available in a few minutes at <a href="./downloadBackgroundExport.php?token='.$token.'">this link</a>';
 echo '<br/><br/><a href="./backgroundJobs.php">Go to background export jobs list</a>';
}
else
{// dataset is small enough to stream it on-the-fly without kicking-off background processing
 

}

if($streamXLSX)
{   
 $logs->logSystemEvent('Export', 0, 'Buyers Guide file ['.$filename.'] exported by:'.$_SERVER['REMOTE_ADDR']);

 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}
?>
