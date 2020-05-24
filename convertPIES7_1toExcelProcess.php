<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/XLSXWriterClass.php');
$navCategory = 'import/export';

session_start();

$pim = new pim();
$logs=new logs();
$pcdb = new pcdb();
$pcdbVersion=$pcdb->version();



$validAssetTypes=array(); $assetTypeCodes=$pcdb->getAssetTypeCodes(); foreach($assetTypeCodes as $assetTypeCode){$validAssetTypes[$assetTypeCode['code']]=$assetTypeCode['description'];}
$validDescriptionCodes=array(); $descriptionCodes=$pcdb->getItemDescriptionCodes(); foreach($descriptionCodes as $descriptionCode){$validDescriptionCodes[$descriptionCode['code']]=$descriptionCode['description'];}
$validEXPIcodes=$pcdb->getAllEXPIcodes();
$validPartTypes=array(); $partTypes=$pcdb->getPartTypes('%'); foreach($partTypes as $partType){$validPartTypes[$partType['id']]=$partType['name'];}

$streamXLSX=false;
$xlsxdata='';

$originalFilename='';
$validUpload=false;
$schemaresults=array();
$inputFileLog=array();
$errors=array(); 
$warnings=array();

if(isset($_POST['submit']) && $_POST['submit']=='Generate Excel file')
{
 if($_FILES['fileToUpload']['type']=='text/xml')
 {
  if($_FILES['fileToUpload']['size']<500000 || isset($_SESSION['userid']))   
  {     
   $originalFilename= basename($_FILES['fileToUpload']['name']);
   $doc = new DOMDocument('1.0', 'UTF-8');
   $doc->load($_FILES['fileToUpload']['tmp_name']);
   
   libxml_use_internal_errors(true);
   if(!$doc->schemaValidate('PIES_7_1_r4_XSD.xsd'))
   {
    $schemavalidated=false;
    $schemaerrors = libxml_get_errors();
    foreach ($schemaerrors as $schemaerror)
    {
     $errormessage='';
     switch ($schemaerror->level) 
     {
      case LIBXML_ERR_WARNING:
       //$errormessage .= 'Warning code '. $schemaerror->code;
       break;
      case LIBXML_ERR_ERROR:
       //$errormessage .= 'Error code '.$schemaerror->code;
       break;
      case LIBXML_ERR_FATAL:
       //$errormessage .= 'Fatal Error code '.$schemaerror->code;
       break;
     }
     $errormessage.= trim($schemaerror->message);
     $schemaresults[]=$errormessage;   
    }
    libxml_clear_errors();
   }
   
   if(count($schemaresults)==0)
   {
       $validUpload=true;
   }
  }
  else
  {
   $inputFileLog[]='Input file was too big (500K limit for anonymous users)';
   //$logs->logSystemEvent('rhubarb', 0, 'Input file was too big (500K limit for anonymous users');
  }
 }
 else
 {
  echo 'Error uploading file - un-supported file format ('.$_FILES['fileToUpload']['type'].'). Must be a valid xml file';
  //$logs->logSystemEvent('rhubarb', 0, 'Error uploading file - un-supported file format');
 }
}

if($validUpload)
{
 $items=array();
 $itemElements=$doc->getElementsByTagName('Item');
 foreach ($itemElements AS $itemElement) 
 {
  $partnumber=$itemElement->getElementsByTagName('PartNumber')[0]->nodeValue;
  $partterminologyid=$itemElement->getElementsByTagName('PartTerminologyID')[0]->nodeValue;
  $brandaaiaid=$itemElement->getElementsByTagName('BrandAAIAID')[0]->nodeValue;
  
  $itemlevelgtin=''; $gtinqualifier='';
  $itemlevelgtinElement=$itemElement->getElementsByTagName('ItemLevelGTIN');
  if(count($itemlevelgtinElement))
  {
   $itemlevelgtin = $itemlevelgtinElement[0]->nodeValue;
   $gtinqualifier=$itemElement->getElementsByTagName('ItemLevelGTIN')[0]->getAttribute('GTINQualifier');//$itemElement->getElementsByTagName('GTINQualifier')->item(0)->nodeValue;
  }
  
  $minimumorderquantity=''; $minimumorderquantityuom='';
  $minimumorderquantityElement=$itemElement->getElementsByTagName('MinimumOrderQuantity');
  if(count($minimumorderquantityElement))
  {
   $minimumorderquantity = $minimumorderquantityElement[0]->nodeValue;
   $minimumorderquantityuom=$itemElement->getElementsByTagName('MinimumOrderQuantity')[0]->getAttribute('UOM');
  }

  $hazardousmaterialcode=''; $hazardousmaterialcodeElement=$itemElement->getElementsByTagName('HazardousMaterialCode');
  if(count($hazardousmaterialcodeElement)){$hazardousmaterialcode = $hazardousmaterialcodeElement[0]->nodeValue;}

  $baseitemid=''; $baseitemidElement=$itemElement->getElementsByTagName('BaseItemID');
  if(count($baseitemidElement)){$baseitemid = $baseitemidElement[0]->nodeValue;}
  
  $itemeffectivedate=''; $itemeffectivedateElement=$itemElement->getElementsByTagName('ItemEffectiveDate');
  if(count($itemeffectivedateElement)){$itemeffectivedate = $itemeffectivedateElement[0]->nodeValue;}

  $availabledate=''; $availabledateElement=$itemElement->getElementsByTagName('AvailableDate');
  if(count($availabledateElement)){$availabledate = $availabledateElement[0]->nodeValue;}
  
  $ACESapplications=''; $ACESapplicationsElement=$itemElement->getElementsByTagName('ACESApplications');
  if(count($ACESapplicationsElement)){$ACESapplications = $ACESapplicationsElement[0]->nodeValue;}
  
  $itemquantitysize=''; $itemquantitysizeuom='';
  $itemquantitysizeElement=$itemElement->getElementsByTagName('ItemQuantitySize');
  if(count($itemquantitysizeElement))
  {
   $itemquantitysize = $itemquantitysizeElement[0]->nodeValue;
   $itemquantitysizeuom=$itemElement->getElementsByTagName('ItemQuantitySize')[0]->getAttribute('UOM');
  }

  $containertype=''; $containertypeElement=$itemElement->getElementsByTagName('ContainerType');
  if(count($containertypeElement)){$containertype = $containertypeElement[0]->nodeValue;}

  $quantityperapplication=''; $quantityperapplicationuom='';
  $quantityperapplicationElement=$itemElement->getElementsByTagName('QuantityPerApplication');
  if(count($quantityperapplicationElement))
  {
   $quantityperapplication = $quantityperapplicationElement[0]->nodeValue;
   $quantityperapplicationuom=$itemElement->getElementsByTagName('QuantityPerApplication')[0]->getAttribute('UOM');
  }

  $brandlabel=''; $brandlabelElement=$itemElement->getElementsByTagName('BrandLabel');
  if(count($brandlabelElement)){$brandlabel = $brandlabelElement[0]->nodeValue;}
  
  $VMRSbrandid=''; $VMRSbrandidElement=$itemElement->getElementsByTagName('VMRSBrandID');
  if(count($VMRSbrandidElement)){$VMRSbrandid = $VMRSbrandidElement[0]->nodeValue;}
  
  $UNSPSC=''; $UNSPSCElement=$itemElement->getElementsByTagName('UNSPSC');
  if(count($UNSPSCElement)){$UNSPSC = $UNSPSCElement[0]->nodeValue;}
  
  
  //UNSPC

  
  $items[$partnumber]=array('PartTerminologyID'=>$partterminologyid,'BrandAAIAID'=>$brandaaiaid,'ItemLevelGTIN'=>$itemlevelgtin,'GTINQualifier'=>$gtinqualifier,'MinimumOrderQuantity'=>$minimumorderquantity,'MinimumOrderQuantityUOM'=>$minimumorderquantityuom,'HazardousMaterialCode'=>$hazardousmaterialcode,'BaseItemID'=>$baseitemid,'ItemEffectiveDate'=>$itemeffectivedate,'AvailableDate'=>$availabledate,'ACESApplications'=>$ACESapplications,'ItemQuantitySize'=>$itemquantitysize,'ItemQuantitySizeUOM'=>$itemquantitysizeuom,'ContainerType'=>$containertype,'QuantityPerApplication'=>$quantityperapplication,'QuantityPerApplicationUOM'=>$quantityperapplicationuom,'BrandLabel'=>$brandlabel,'VMRSBrandID'=>$VMRSbrandid,'UNSPSC'=>$UNSPSC,'descriptions'=>array());

  $writer = new XLSXWriter();
  $writer->setAuthor('SandPIM'); 
  $writer->writeSheetHeader('Items', array('PartNumber'=>'string','PartTerminologyID'=>'integer','BrandAAIAID'=>'string','ItemLevelGTIN'=>'string','GTINQualifier'=>'string','MinimumOrderQuantity'=>'integer','MinimumOrderQuantityUOM'=>'string','HazardousMaterialCode'=>'string','BaseItemID'=>'string','ItemEffectiveDate'=>'date','AvailableDate'=>'date','ACESApplications'=>'string','ItemQuantitySize'=>'integer','ItemQuantitySizeUOM'=>'string','ContainerType'=>'string','QuantityPerApplication'=>'integer','QuantityPerApplicationUOM'=>'string','BrandLabel'=>'string','VMRSBrandID'=>'string','UNSPC'=>'string'),        array('freeze_rows'=>1, 'freeze_columns'=>1,['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],            ['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#ffff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00'],['fill'=>'#00ff00']));
  foreach($items as $partnumber=>$item)
  {
   $row=array($partnumber,$item['PartTerminologyID'],$item['BrandAAIAID'],$item['ItemLevelGTIN'],$item['GTINQualifier'],$item['MinimumOrderQuantity'],$item['MinimumOrderQuantityUOM'],$item['HazardousMaterialCode'],$item['BaseItemID'],$item['ItemEffectiveDate'],$item['AvailableDate'],$item['ACESApplications'],$item['ItemQuantitySize'],$item['ItemQuantitySizeUOM'],$item['ContainerType'],$item['QuantityPerApplication'],$item['QuantityPerApplicationUOM'],$item['BrandLabel'],$item['VMRSBrandID'],$item['UNSPSC']);
   $writer->writeSheetRow('Items', $row);
  }

  $writer->writeSheetHeader('Descriptions', array('PartNumber'=>'string','Description'=>'string','DescriptionCode'=>'string','Sequence'=>'integer','LanguageCode'=>'string'), array('freeze_rows'=>1, 'freeze_columns'=>1,['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ff0000'],['fill'=>'#ffff00'],['fill'=>'#ffff00']));
  foreach($items as $partnumber=>$item)
  {
   foreach($item['descriptions'] as $description)
   {
    $row=array($partnumber,$description['Description'],$description['DescriptionCode'],$description['Sequence'],$description['LanguageCode']);
    $writer->writeSheetRow('Descriptions', $row);
   }
  }
  
  $xlsxdata=$writer->writeToString();
  $streamXLSX=true;
  }
}



/*                    echo '*';
                    foreach($itemElements as $itemElement)
                    {
                        $partnumber=$itemElement->getElementsByTagName('PartNumber')->nodeValue.'<br/>';
                        echo '*'.$partnumber.'*';
                    }
                    echo '*';
*/

if(count($errors)>0 || count($schemaresults)>0 || !$validUpload)
{
 $streamXLSX=false; ?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php if (isset($_SESSION['userid'])){include('topnav.php');} ?>

        <!-- Header -->
        <h1>Convert PIES (7.1) xml to Excel spreadsheet</h1>
        <h2>Step 2: Analyze results and download spreadsheet</h2>
        <div style="font-style: italic;">Validation done against PCdb version: <?php echo $pcdbVersion;?></div>
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
    

                <?php
                if(!$validUpload){?>
                <div style="padding:10px;background-color:#FF0000;font-size:1.5em;">Your input file has problems:</div>
                <table><?php
                foreach($inputFileLog as $result)
                { // render each element of schema problems into a table
                    echo '<tr><td style="text-align:left;background-color:#FF0000;">'.$result.'</td></tr>';
                }
                ?>
                </table>
                
                <?php }?>


                
                <?php if(count($schemaresults)>0){?>
                <div style="padding:10px;background-color:#FF8800;font-size:1.5em;">Your input data causes schema (XSD) problems. Here they are:</div>
                <table><?php
                foreach($schemaresults as $result)
                { // render each element of schema problems into a table
                 echo '<tr><td style="text-align:left;background-color:#FF8800;">'.$result.'</td></tr>';
                }
                ?>
                </table>
                <?php }else
                {
                }
                
                if(count($errors)>0 && !isset($_POST['ignorelogic'])){?>
                <div style="padding:10px;background-color:yellow;font-size:1.5em;"><?php if(count($schemaresults)==0){echo 'XSD-validated output was produced. However, ';} ?>your input data contains logic problems. Here are the ones we detected:</div>
                <table><?php
                foreach($errors as $error)
                {
                    echo '<tr><td style="text-align:left;background-color:yellow;">'.$error.'</td></tr>';
                }
                ?>
                </table>
                <?php }
                
                $logs->logSystemEvent('rhubarb', 0, 'file:'.$originalFilename.';items:'.count($items).';xsd:'.count($schemaresults).';logic:'.count($errors).';by:'.$_SERVER['REMOTE_ADDR']);
                ?>
                 
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
       <?php if (isset($_SESSION['userid'])){include('./includes/footer.php');} ?>
    </body>
</html>
<?php }

if($streamXLSX)
{   
 $filename='Rhubarb_7_1_A_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>