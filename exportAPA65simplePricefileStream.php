<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pricingClass.php');
include_once('./class/packagingClass.php');
include_once('./class/userClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'exportAPA65simplePricefileStream.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb = new pcdb();
$writer = new XLSXWriter();
$user=new user();
$logs=new logs();
$pcdbVersion=$pcdb->version();
$pricing = new pricing();
$packaging = new packaging();

$receiverprofileid=intval($_GET['receiverprofile']);
$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$blanketeffectivedate='';

if(isset($_GET['blanketeffectivedate']) && trim($_GET['blanketeffectivedate'])!='')
{
 $blanketeffectivedate=$_GET['blanketeffectivedate'];
}




$targetpricesheetnumber='';
$validtargetpricesheetnumber=false;
$pricesheets=$pricing->getPricesheets();
foreach($pricesheets as $pricesheet)
{
    if($_GET['pricesheetnumber']==$pricesheet['number']){$validtargetpricesheetnumber=true; break;}
}

if($validtargetpricesheetnumber){$targetpricesheetnumber=$_GET['pricesheetnumber'];}


$streamXLSX=false;
$xlsxdata='';


$profile=$pim->getReceiverprofileById($receiverprofileid);
$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories);
$brandid='';
$brandownerid='';

if(trim($profile['data'])!='')
{
 $profiledataelements=explode(';',$profile['data']);
 foreach($profiledataelements as $element)
 {
  $bits=explode(':',$element);
  if(count($bits)==2)
  {
   if($bits[0]=='BrandOwnerAAIAID'){$brandownerid=trim($bits[1]);}
   if($bits[0]=='BrandAAIAID'){$brandid=trim($bits[1]);}
   if($bits[0]=='DocumentTitle'){$documenttitle=trim($bits[1]);}   
  }
 }
}




       
$writer->writeSheetHeader('Data', 
        array(
'AAIA Brand Owner ID'=>'string',
'AAIA Brand ID'=>'string',
'Price Sheet Number'=>'string',
'Price Sheet Effective Date'=>'string',
'Currency'=>'string',
'Part Number'=>'string',
'Item Level GTIN'=>'string',
'Item Level GTIN Qualifier'=>'string',
'Part Type ID'=>'number',
'Product Category Code'=>'string',
'Product Description - 20'=>'string',
'Product Description - 80'=>'string',
'Item Quantity Size'=>'number',
'Item Quantity Size UOM'=>'string',
'Life Cycle Status Code'=>'string',
'Primary Country of Origin'=>'string',
'Harmonized Tariff'=>'string',
'Each Height'=>'#,##0.0',
'Each Width'=>'#,##0.0',
'Each Length'=>'#,##0.0',
'Each Weight'=>'#,##0.0',
'Minimum Order Quantity'=>'number',
'Taxable'=>'string',
'MAP Price'=>'price',
'List Price'=>'price',
'WD Price'=>'price',
'Jobber Price'=>'price',
'Dealer Price'=>'price',
'WD Core'=>'price',
'Jobber Core'=>'price',
'Line Item Invoice Cost'=>'price',
'Net Invoice Cost'=>'price',
'Line Item Core Cost'=>'price',
'Net Invoice Core Cost'=>'price',
'Environmental Handling Charge'=>'price',
'Old Part Number'=>'string',
'Part Number Superseded To'=>'string'
            ),
        array('widths'=>
            array(18,23,23,24,25,15,21,11,13,17,16,21,13,21,21,18,22,20,11,19,21,21,21,9,8,11,11,15,5,9,11,18,23,22,20,24,22),'freeze_rows'=>1, 
            [],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[],[]));
       
$latestskudate='0000-00-00';
foreach($partnumbers as $partnumber)
{
 $part=$pim->getPart($partnumber);
 if($part)
 {
  $prices=$pricing->getPricesByPartnumber($partnumber);
  $amount=0; $currency=0; $priceuom=0; $pricetype=0; $effectivedate=0; $expirationdate=0;
  foreach($prices as $price)
  {
   if($price['pricesheetnumber']==$targetpricesheetnumber){$amount=$price['amount']; $currency=$price['currency']; $priceuom=$price['priceuom']; $pricetype=$price['pricetype']; $effectivedate=$price['effectivedate']; $expirationdate=$price['expirationdate'];} 
  }
  
  if($amount==0){continue;}

  $description20=''; $description40=''; $description80='';
  $descriptions=$pim->getPartDescriptions($partnumber);
  foreach($descriptions as $description)
  {
      if($description['descriptioncode']=='SHO'){$description20=$description['description'];}
      if($description['descriptioncode']=='ENV'){$description40=$description['description'];}      
      if($description['descriptioncode']=='DES'){$description80=$description['description'];}
  }
  
  $ctoprimary=''; $tariffcode='';
  $expis=$pim->getPartEXPIs($partnumber); //'id'=>$row['id'],'EXPIcode'=>$row['EXPIcode'],'EXPIvalue'=>$row['EXPIvalue'],'languagecode'=>$row['languagecode'],'inheritedfrom'=>$basepart
  foreach($expis as $expi)
  {
   if($expi['EXPIcode']=='CTO'){$ctoprimary=$expi['EXPIvalue'];}
   if($expi['EXPIcode']=='HTS'){$tariffcode=$expi['EXPIvalue'];}
  }
  
  $eachweight=0; $eachheight=0; $eachwidth=0; $eachlength=0; $weightsuom=''; $dimensionsuom='';
  $packages=$packaging->getPackagesByPartnumber($partnumber); //'id'=>$row['id'],'partnumber'=>$row['partnumber'],'packageuom'=>$row['packageuom'],'quantityofeaches'=>$row['quantityofeaches'],'innerquantity'=>$innerquantity,'innerquantityuom'=>$row['innerquantityuom'],'weight'=>$weight,'weightsuom'=>$row['weightsuom'],'packagelevelGTIN'=>$row['packagelevelGTIN'],'packagebarcodecharacters'=>$row['packagebarcodecharacters'],'shippingheight'=>$shippingheight,'shippingwidth'=>$shippingwidth,'shippinglength'=>$shippinglength,'merchandisingheight'=>$merchandisingheight,'merchandisingwidth'=>$merchandisingwidth,'merchandisinglength'=>$merchandisinglength,'dimensionsuom'=>$row['dimensionsuom'],'orderable'=>$row['orderable'],'nicepackage'=>$nicepackage,'nicepackagehtml'=>$nicepackagehtml
  foreach($packages as $package)
  {
   $eachweight=$package['weight'];
   $eachheight=$package['shippingheight'];
   $eachwidth=$package['shippingwidth'];
   $eachlength=$package['shippinglength'];
   $weightsuom=$package['weightsuom'];
   $dimensionsuom=$package['dimensionsuom'];
  }
  
  
  if($effectivedate>$latestskudate){$latestskudate=$effectivedate;}
  
  $row=array();
  
  /*
  
  $row[0]=$price['pricesheetnumber']; //column 1 (Price Sheet Number) --- optional
  $row[1]=''; //column 2 (Superseded Price Sheet #) --- optional
  $row[2]=$effectivedate; //column 3 (Price Sheet Effective Date) --- required
  if($blanketeffectivedate!=''){$row[2]=$blanketeffectivedate;}
  $row[3]=''; //column 4 (Price Sheet Expiration Date) --- optional
  $row[4]='N'; //column 5 (Hazardous Material Flag Y/N) --- required
  $row[5]='00'.$part['GTIN']; //column 6 (Item Level GTIN) --- required
  $row[6]='UP'; //column 7 (Item Level GTIN Qualifier) --- required
  $row[7]=$partnumber; //column 8 (Part Number) --- required
  $row[8]=$brandid; //column 9 (AAIA Brand ID) --- optional
  $row[9]='Y'; //column 10 (ACES Applications) --- optional
  $row[10]=1; //column 11 (Item Quantity Size) --- required
  $row[11]=$priceuom; //column 12 (Item Quantity Size UOM) --- required
  $row[12]=''; //column 13 (Container Type) --- optional
  $row[13]=$part['typicalqtyperapp']; //column 14 (Quantity Per Application) --- optional
  $row[14]=1; //column 15 (Minimum Order Quantity) --- required
  $row[15]=''; //column 16 (Product Group Code) --- optional
  $row[16]=''; //column 17 (Product Sub-Group Code) --- optional
  $row[17]=''; //column 18 (Product Category Code) --- optional
  $row[18]=$part['parttypeid']; //column 19 (Part Type ID) --- optional
  $row[19]=''; //column 20 (Application Summary) --- optional
  $row[20]=$description80; //column 21 (Product Description - 80) --- optional
  $row[21]=$description20; //column 22 (Product Description - 20) --- required
  $row[22]=$description40; //column 23 (Product Description - 40) --- optional
  $row[23]=$amount; //column 24 (WD Price) --- conditional
  $row[24]=0; //column 25 (WD Core) --- optional
  $row[25]=0; //column 26 (Jobber Price) --- conditional
  $row[26]=0; //column 27 (Dealer Price) --- optional
  $row[27]=0; //column 28 (Non-Stock Dealer) --- optional
  $row[28]=0; //column 29 (User) --- optional
  $row[29]=0; //column 30 (List Price) --- optional
  $row[30]=0; //column 31 (Jobber Core) --- optional
  $row[31]=1; //column 32 (Price Break Quantity) --- optional
  $row[32]=''; //column 33 (Country of Origin (Primary)) --- optional
  $row[33]=''; //column 34 (Harmonized Tariff (Sch B)) --- optional
  $row[34]=$part['lifecyclestatus']; //column 35 (Life Cycle Status Code) --- optional
  $row[35]=''; //column 36 (MSDS Sheet Available Y/N) --- optional
  $row[36]=''; //column 37 (National Popularity Code) --- optional
  $row[37]=''; //column 38 (Part Number - Old) --- conditional
  $row[38]=$part['replacedby']; //column 39 (Part Number Superseded To) --- conditional
  $row[39]=''; //column 40 (Taxable Y/N) --- optional
  $row[40]=''; //column 41 (MSDS Sheet Number) --- optional
  $row[41]='00'.$part['GTIN']; //column 42 (Each Package Level GTIN) --- required
  $row[42]=$part['GTIN']; //column 43 (Each Package Bar Code Characters) --- required
  $row[43]=1; //column 44 (Each Package Inner Quantity) --- required
  $row[44]=$priceuom; //column 45 (Each Package Inner Quantity UOM) --- required
  $row[45]=0; //column 46 (Each Height) --- optional
  $row[46]=0; //column 47 (Each Width) --- optional
  $row[47]=0; //column 48 (Each Length) --- optional
  $row[48]=0; //column 49 (Each Weight) --- optional
  $row[49]=''; //column 50 (Inner Pack Level GTIN) --- optional
  $row[50]=''; //column 51 (Inner Package Bar Code Characters) --- optional
  $row[51]=0; //column 52 (Inner Pack Height (Inches)) --- optional
  $row[52]=0; //column 53 (Inner Pack Width (Inches)) --- optional
  $row[53]=0; //column 54 (Inner Pack Length (Inches)) --- optional
  $row[54]=0; //column 55 (Inner Pack Weight (Gross Pounds)) --- optional
  $row[55]=''; //column 56 (Case Package Level GTIN) --- optional
  $row[56]=''; //column 57 (Case Package Bar Code Characters) --- optional
  $row[57]=''; //column 58 (Case Package Inner Quantity UOM) --- optional
  $row[58]=1; //column 59 (Quantity of Eaches in Case) --- optional
  $row[59]=0; //column 60 (Case Height) --- optional
  $row[60]=0; //column 61 (Case Width) --- optional
  $row[61]=0; //column 62 (Case Length) --- optional
  $row[62]=0; //column 63 (Case Weight) --- optional
  $row[63]=''; //column 64 (Pallet Package GTIN) --- optional
  $row[64]=''; //column 65 (Pallet Bar Code Characters) --- optional
  $row[65]=0; //column 66 (Quantity of Eaches in Pallet) --- optional
  $row[66]=0; //column 67 (Pallet Height) --- optional
  $row[67]=0; //column 68 (Pallet Width) --- optional
  $row[68]=0; //column 69 (Pallet Length) --- optional
  $row[69]=0; //column 70 (Pallet Weight) --- optional
  $row[70]=''; //column 71 (Hazardous Material Description) --- conditional
  $row[71]=''; //column 72 (Hazardous Class Code) --- conditional
  $row[72]=''; //column 73 (Link to Supplier Page (Web)) --- optional
  $row[73]=0; //column 74 (Line Item Invoice Cost*) --- optional
  $row[74]=0; //column 75 (Line Item Invoice Core Cost*) --- optional
  $row[75]=$currency; //column 76 (Currency) --- optional
  
  */
  
  $row[0]=$brandownerid; // column 1 --- AAIA Brand Owner ID (string)
  $row[1]=$brandid; // column 2 --- AAIA Brand ID (string)
  $row[2]=$price['pricesheetnumber']; // column 3 --- Price Sheet Number (string)
  $row[3]=$effectivedate; // column 4 --- Price Sheet Effective Date (string)
  $row[4]=$currency; // column 5 --- Currency (string)
  $row[5]=$partnumber; // column 6 --- Part Number (string)
  $row[6]='00'.$part['GTIN']; // column 7 --- Item Level GTIN (string)
  $row[7]='UP'; // column 8 --- Item Level GTIN Qualifier (string)
  $row[8]=$part['parttypeid']; // column 9 --- Part Type ID (number)
  $row[9]=''; // column 10 --- Product Category Code (string)
  $row[10]=$description20; // column 11 --- Product Description - 20 (string)
  $row[11]=$description80; // column 12 --- Product Description - 80 (string)
  $row[12]=1; // column 13 --- Item Quantity Size (number)
  $row[13]=$priceuom; // column 14 --- Item Quantity Size UOM (string)
  $row[14]=$part['lifecyclestatus']; // column 15 --- Life Cycle Status Code (string)
  $row[15]=$ctoprimary; // column 16 --- Primary Country of Origin (string)
  $row[16]=$tariffcode; // column 17 --- Harmonized Tariff (string)
  $row[17]=$eachheight; // column 18 --- Each Height (number)
  $row[18]=$eachwidth; // column 19 --- Each Width (number)
  $row[19]=$eachlength; // column 20 --- Each Length (number)
  $row[20]=$eachweight; // column 21 --- Each Weight (number)
  $row[21]=1; // column 22 --- Minimum Order Quantity (number)
  $row[22]='Y'; // column 23 --- Taxable (string)
  $row[23]=0; // column 24 --- MAP Price (price)
  $row[24]=0; // column 25 --- List Price (price)
  $row[25]=$amount; // column 26 --- WD Price (price)
  $row[26]=0; // column 27 --- Jobber Price (price)
  $row[27]=0; // column 28 --- Dealer Price (price)
  $row[28]=0; // column 29 --- WD Core (price)
  $row[29]=0; // column 30 --- Jobber Core (price)
  $row[30]=0; // column 31 --- Line Item Invoice Cost (price)
  $row[31]=0; // column 32 --- Net Invoice Cost (price)
  $row[32]=0; // column 33 --- Line Item Core Cost (price)
  $row[33]=0; // column 34 --- Net Invoice Core Cost (price)
  $row[34]=0; // column 35 --- Environmental Handling Charge (price)
  $row[35]=''; // column 36 --- Old Part Number (string)
  $row[36]=$part['replacedby']; // column 37 --- Part Number Superseded To (string)
    
  $writer->writeSheetRow('Data', $row);
 } 
}

$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('export', $_SESSION['userid'], 'APA/AWDA 6.5 exported of '.count($partnumbers).' parts; by:'.$_SERVER['REMOTE_ADDR']);

if($streamXLSX)
{ 
 $filename=$documenttitle.'_'.$latestskudate.'.xlsx';
 if($blanketeffectivedate!='')
 {
  $filename=$documenttitle.'_'.$blanketeffectivedate.'.xlsx';
 }
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>