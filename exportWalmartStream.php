<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/assetClass.php');
include_once('./class/packagingClass.php');
include_once('./class/pricingClass.php');
include_once('./class/userClass.php');
include_once('./class/XLSXWriterClass.php');

/*
 * some data elements are pulled from the receiver profile 
 *  MarketplaceBrandName
 *  MarketplaceManufacturerName
 * 
 * 
 * 
 * 
 */


$pim = new pim();
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'exportWalmartStream.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pricesheetnumber='WD NET';


$pcdb = new pcdb();
$asset = new asset;
$packaging = new packaging;
$pricing = new pricing;
$user = new user();
$writer = new XLSXWriter();
$logs=new logs();
$pcdbVersion=$pcdb->version();

$receiverprofileid=intval($_GET['receiverprofile']);
$receiverprofile=$pim->getReceiverprofileById($receiverprofileid);
$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuses);

$brandname='not set in receiver profile (MarketplaceBrandName)'; if(isset($receiverprofile['keyeddata']['MarketplaceBrandName'])){$brandname=$receiverprofile['keyeddata']['MarketplaceBrandName'];}
$manufacturername='not set in receiver profile (MarketplaceManufacturerName)'; if(isset($receiverprofile['keyeddata']['MarketplaceManufacturerName'])){$manufacturername=$receiverprofile['keyeddata']['MarketplaceManufacturerName'];}

        
$columnslist=array(); $columnwidthlist=array(); 
for($i=0;$i<=154;$i++)
{
    $columnslist['Column '.$i]='string';
    $columnwidthlist[]=12;
    if($i==10){$columnslist['Column '.$i]='number';}// PPU Quantity of Units
    if($i==12){$columnslist['Column '.$i]='number';}// Multipack
    if($i==42){$columnslist['Column '.$i]='number';}// Part Terminology ID
    if($i==26){$columnslist['Column '.$i]='#0.00';}// price column
    if($i==34){$columnslist['Column '.$i]='#0.00';}// weight column
    if($i==118){$columnslist['Column '.$i]='#0.00';}// weight column
    
}


//$writer->writeSheetHeader('Sheet1', $columnslist, array('widths'=>$columnwidthlist,'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
$writer->writeSheetHeader('Sheet1', $columnslist, array('widths'=>$columnwidthlist,'freeze_rows'=>1));
       
foreach($partnumbers as $partnumber)
{
 $part=$pim->getPart($partnumber);
 if($part)
 {
  $row=array();
  for($i=0;$i<=154;$i++){$row[]='';}
  
  
  $partcategory=$pim->getPartCategory($part['partcategory']);
  $connectedassets=$asset->getAssetsConnectedToPart($partnumber,true);
  $descriptions=$pim->getPartDescriptions($partnumber);
  $appsummarystruct = $pim->getAppSummary($partnumber);
  $packages=$packaging->getPackagesByPartnumber($partnumber);
  $prices=$pricing->getPricesByPartnumber($partnumber,$pricesheetnumber);

  
  
  
  
  $packagelengthvalue=0;$packagelengthunits=''; $packagewidthvalue=0;$packagewidthunits=''; $packageheightvalue=0; $packageheightunits=''; $packageweightvalue=0; $packageweightunits=''; $packageuom=''; $packagequantityofeaches=0;
  foreach($packages as $package)
  {//array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'packageuom'=>$row['packageuom'],'quantityofeaches'=>$row['quantityofeaches'],'innerquantity'=>$innerquantity,'innerquantityuom'=>$row['innerquantityuom'],'weight'=>$weight,'weightsuom'=>$row['weightsuom'],'packagelevelGTIN'=>$row['packagelevelGTIN'],'packagebarcodecharacters'=>$row['packagebarcodecharacters'],'shippingheight'=>$shippingheight,'shippingwidth'=>$shippingwidth,'shippinglength'=>$shippinglength,'dimensionsuom'=>$row['dimensionsuom'],'nicepackage'=>$nicepackage);
   if($package['packageuom']=='EA')
   {
    $packagelengthvalue=$package['shippinglength']; $packagelengthunits=$package['dimensionsuom']; $packagewidthvalue=$package['shippingwidth']; $packagewidthunits=$package['dimensionsuom']; $packageheightvalue=$package['shippingheight']; 
    $packageheightunits=$package['dimensionsuom']; $packageweightvalue=floatval($package['weight']); $packageweightunits=$package['weightsuom']; $packagequantityofeaches=$package['quantityofeaches'];    $packageuom=$package['packageuom'];
    break;
   }
  }
  if($packageweightunits=='PG'){$packageweightunits='lb';}
  
  
  $descriptiontext='';
  foreach($descriptions as $description)
  {//array('id'=>$row['id'],'description'=>$row['description'],'descriptioncode'=>$row['descriptioncode'],'sequence'=>$row['sequence'],'languagecode'=>$row['languagecode'],'inheritedfrom'=>'');       
   if($description['descriptioncode']=='DES' && $description['languagecode']=='EN')
   {
       $descriptiontext=$description['description']; break;
   }
  }
  
  $sellprice=0;
  foreach($prices as $price)
  {// array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'pricesheetnumber'=>$row['pricesheetnumber'],'amount'=>$row['amount'],'currency'=>$row['currency'],'priceuom'=>$row['priceuom'],'pricetype'=>$row['pricetype'],'effectivedate'=>$row['effectivedate'],'expirationdate'=>$row['expirationdate'],'niceprice'=>$niceprice);
   $sellprice=round($price['amount'],2);
   break;
  }
  
  

  
  
//  $row=array($partnumber,$pcdb->parttypeName($part['parttypeid']),$pcdb->lifeCycleCodeDescription($part['lifecyclestatus']), $part['GTIN'],$part['replacedby'],$pim->partCategoryName($part['partcategory']),$part['createdDate'],$part['firststockedDate'],$part['discontinuedDate']);
 
  $row[3]=$partnumber;
  $row[4]='GTIN';
  $row[5]='00'.$part['GTIN'];  
  $row[8]=$descriptiontext.' - '.$partnumber;
  $row[9]=$brandname; //brand  
  $row[10]=intval($packagequantityofeaches); //PPU Quantity of Units
  $row[11]='Each';//PPU Unit of Measure	
  $row[12]=intval($packagequantityofeaches);//Multipack Quantity
  $row[13]=$partcategory['marketcopy'];//Site Description
  $row[14]=$partcategory['fab'];//Key Features (+)	
  $row[15]='';//Key Features 1 (+)	
  $row[16]='';//Key Features 2 (+)	
  
  $foundPrimaries=false;
  $additionalimagelist=array();
  foreach($connectedassets as $connectedasset)
  {
   if($connectedasset['assettypecode']=='P04' && $connectedasset['uri']!='' && ($connectedasset['filetype']=='JPG' || $connectedasset['filetype']=='PNG'))
   {
    $row[17]=$connectedasset['uri']; //Main Image URL
    $foundPrimaries=true;
   }
   
   if($connectedasset['assettypecode']!='P04' && $connectedasset['uri']!='' &&  $connectedasset['assetlabel']=='MARKETPLACE' && ($connectedasset['filetype']=='JPG' || $connectedasset['filetype']=='PNG'))
   {
    $additionalimagelist[]=$connectedasset['uri'];
   }
  }  
  if(isset($additionalimagelist[0])){$row[18]=$additionalimagelist[0];} //Additional Image URL (+)
  if(isset($additionalimagelist[1])){$row[19]=$additionalimagelist[1];} //Additional Image URL (+)
  if(isset($additionalimagelist[2])){$row[20]=$additionalimagelist[2];} //Additional Image URL (+)
  if(isset($additionalimagelist[3])){$row[21]=$additionalimagelist[3];} //Additional Image URL (+)
  $row[22]='No';//Contains Electronic Component
  $row[23]='Does Not Contain a Battery';//Contained Battery Type
  $row[24]='No';//Contains Chemical, Aerosol or Pesticide?
  $row[26]=$sellprice;//Selling Price
  $row[27]='2021-05-27';//Site Start Date
  $row[28]='2049-12-31';//Site End Date
  $row[32]='Yes';//Ships in Original Packaging
  $row[34]=round($packageweightvalue,2);//Shipping Weight
  $row[38]='Specific';//Fit Type
  $row[39]='Car;Truck;';//Vehilce Category
  $row[40]=$part['brandid'];//AAIA Brand ID
  $row[42]=$part['parttypeid'];//Part Terminology ID
  $row[44]=$manufacturername;//Manufacturer name
  $row[45]=$partnumber;//Manufacturer Part Number
  $row[46]=$partnumber;//Model Number
  $row[47]='1 - Does not contain composite wood';//
  $row[48]=intval($packagequantityofeaches);//Package Count
  $row[54]=$appsummarystruct['summary'];
  $row[112]=$packagelengthvalue;//Assembled Product Length - Measure
  $row[113]=strtolower($packagelengthunits);//Assembled Product Length - Unit
  $row[114]=$packagewidthvalue;//Assembled Product Width - Measure
  $row[115]=strtolower($packagewidthunits);//Assembled Product Width - Unit
  $row[116]=$packageheightvalue;//Assembled Product Height - Measure
  $row[117]=strtolower($packageheightunits);//Assembled Product Height - Unit
  $row[118]=$packageweightvalue;//Assembled Product Weight - Measure
  $row[119]=strtolower($packageweightunits);//Assembled Product Weight - Unit
  $row[123]='oem replacement';//
  $row[142]=$partcategory['warranty'];//Warranty Text

 }
 else
 { // part is not in the part master list
  $row=array($partnumber,'');
 }
 
 $writer->writeSheetRow('Sheet1', $row);
}

$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('export', 0, 'Exported '.count($partnumbers).' parts; by:'.$_SERVER['REMOTE_ADDR']);

if($streamXLSX)
{   
 $filename='parts-walmart_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}
/*
 * Walmart template layout (version 4.6)
Column Index	Column Description	Sample Value
0	Version	Version=4.6,mpmaintenance,vehicle_parts_and_accessories,en,external,Vehicle Parts & Accessories
1	Metadata	{"additionalInfo":{"fileId":"416EC9C80454422595CEF529A68F2DDF@AUIBAgA"},"metaTask":{}}
2		
3	SKU	ASD1611AP
4	Product ID Type	GTIN
5	Product ID	00841929132232
6		
7		
8	Product Name	AmeriBRAKES Severe Duty Disc Brake Pads with included lubricant and hardware, AmeriPLATINUM ASD1611AP - For Taurus Flex Police Interceptor Utility Polic
9	Brand	AmeriBRAKES
10	PPU Quantity of Units	1
11	PPU Unit of Measure	Each
12	Multipack Quantity	1
13	Site Description	AmeriPLATINUM Severe Duty Semi-Metallic Brake Pads by AmeriBRAKES are 100% manufactured in USA and Canada, entrepreneurially owned by an American family for 76 years.  They take performance, quiet and clean braking to the next level through our innovative and advanced formulations. Their design is OE matched with slots, chamfers, and optimum noise abating insulators. Additionally, each set comes with industry best high-quality hardware which assists with a complete brake repair and brings the brake system to a "like-new" state. To assist with a proper job, high-temperature lubricant is included. Coverage light and medium duty truck, heavy duty, and air disc applications.
14	Key Features (+)	<ul><li>100% North American Manufactured, and American owned for 76+ years</li><li>Resonant Dampening Shim Technology dampens noise and eliminates vibration</li><li>Designed with noise abatement slots, chamfers, and shims for quiet and optimum brake “tip in” performance</li><li>Includes brake lubricant</li><li>Includes hardware</li></ul>
15	Key Features 1 (+)	
16	Key Features 2 (+)	
17	Main Image URL	https://s3.amazonaws.com/autopartsourceimages/parts/ameriplatinum-series-severe-duty-brake-pads_1.jpg
18	Additional Image URL (+)	https://s3.amazonaws.com/autopartsourceimages/parts/ameriplatinum-series-severe-duty-brake-pads_2.jpg
19	Additional Image URL 1 (+)	https://s3.amazonaws.com/autopartsourceimages/parts/ASD1611AP.jpg
20	Additional Image URL 2 (+)	
21	Additional Image URL 3 (+)	
22		No
23	Contained Battery Type	Does Not Contain a Battery
24	Contains Chemical, Aerosol or Pesticide?	No
25		
26	Selling Price	101.05
27	Site Start Date	2021-05-27
28	Site End Date	2049-12-31
29		
30		
31		
32	Ships in Original Packaging	Yes
33		
34	Shipping Weight	8.45
35		
36		
37		
38	Fit Type	Specific
39	Vehilce Category	Car;Truck;
40	AAIA Brand ID	GQBF
41		
42	Part Terminology ID	1684
43		
44	Manufacturer name	Momentum USA
45	Manufacturer Part Number	ASD1611AP
46	Model Number	AmeriPlatinum ASD1611AP
47		1 - Does not contain composite wood
48	Package Count	1
49		
50		
51		
52		
53		
54		
55	Compatible Cars	2013 Ford Flex;2013 Ford Police Interceptor Sedan;2013 Ford Police Interceptor Utility;2013 Ford Taurus;2014 Ford Flex;2014 Ford Police Interceptor Sedan;2014 Ford Police Interceptor Utility;2014 Ford Special Service Police Sedan;2014 Ford Taurus;2015 Ford Flex;2015 Ford Police Interceptor Sedan;2015 Ford Police Interceptor Utility;2015 Ford Special Service Police Sedan;2015 Ford Taurus;2016 Ford Flex;2016 Ford Police Interceptor Sedan;2016 Ford Police Interceptor Utility;2016 Ford Special Service Police Sedan;2016 Ford Taurus;2017 Ford Flex;2017 Ford Police Interceptor Sedan;2017 Ford Police Interceptor Utility;2017 Ford Taurus;2018 Ford Flex;2018 Ford Police Interceptor Sedan;2018 Ford Police Interceptor Utility;2018 Ford Taurus;2019 Ford Flex;2019 Ford Police Interceptor Sedan;2019 Ford Police Interceptor Utility;2019 Ford Taurus;
56		
57		
58		
59		
60		
61		
62		
63		
64		
65		
66		
67		
68		
69		
70		
71		
72		
73		
74		
75		
76		
77		
78		
79		
80		
81		
82		
83		
84		
85		
86		
87		
88		
89		
90		
91		
92		
93		
94		
95		
96		
97		
98		
99		
100		
101		
102		
103		
104		
105		
106		
107		
108		
109		
110		
111		
112		
113		
114	Assembled Product Length - Measure	11
115	Assembled Product Length - Unit	in
116	Assembled Product Width - Measure	7
117	Assembled Product Width - Unit	in
118	Assembled Product Height - Measure	4
119	Assembled Product Height - Unit	in
120	Assembled Product Weight - Measure	7.45
121	Assembled Product Weight - Unit	lb
122		
123		
124		
125		oem replacement
126		
127		
128		
129		
130		
131		
132		
133		
134		
135		
136		
137		
138		
139		
140		
141		
142		
143		
144	Warranty Text	Momentum USA Inc. warrants this disc brake pad set to be free of defects in material and workmanship. Customer satisfaction guaranteed.
145		
146		
147		
148		
149		
150		
151		
152		
153		
154		

 * 
 * 
 * 
 * 
 * 
 * 
 * 
 */












?>