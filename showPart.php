<?php
include_once('./includes/loginCheck.php');
include_once('./class/vcdbClass.php');
include_once('./class/padbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/pricingClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/packagingClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');
$navCategory = 'parts';

$pim = new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'showPart.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$vcdb = new vcdb;
$padb = new padb;
$pcdb = new pcdb;
$asset = new asset;
$pricing = new pricing;
$interchange = new interchange;
$packaging = new packaging;
$configGet = new configGet;

function niceAppAttributes($appattributes) {
    $vcdb = new vcdb;
    $niceattributes = array();
    foreach ($appattributes as $appattribute) {
        if ($appattribute['type'] == 'vcdb') {
            $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']);
        }
        if ($appattribute['type'] == 'note') {
            $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']);
        }
    }
    $nicefitmentstring = '';
    $nicefitmentarray = array();
    foreach ($niceattributes as $niceattribute) {
        // exclude cosmetic elements from the compiled list
        $nicefitmentarray[] = $niceattribute['text'];
    }
    return implode('; ', $nicefitmentarray);
}

$partnumber = strtoupper($_GET['partnumber']);
if (strlen($partnumber) > 20) {
    $partnumber = substr($partnumber, 0, 20);
}

$part = $pim->getPart($partnumber);
$apps = $pim->getAppsByPartnumber($partnumber);
$attributes = $pim->getPartAttributes($partnumber);
$validpadbattributes=$padb->getAttributesForParttype($part['parttypeid']);
$assets_linked_to_item = array();
$partcategories = $pim->getPartCategories();
$connectedassets=$asset->getAssetsConnectedToPart($partnumber);
$descriptions=$pim->getPartDescriptions($partnumber);
$prices=$pricing->getPricesByPartnumber($partnumber);
$competitorparts=$interchange->getInterchangeByPartnumber($partnumber);
$competitivebrands=$interchange->getCompetitivebrands();
$packages=$packaging->getPackagesByPartnumber($partnumber);
$innerqtyuoms=$pcdb->getUoMsForPackaging('Inner Quantity');
$orderablepackageuoms=$pcdb->getUoMsForPackaging('Orderable Package');
$dimensionsuoms=$pcdb->getUoMsForPackaging('UOM for Dimensions');
$weightsuoms=$pcdb->getUoMsForPackaging('UOM for Weight');
$packageuoms=$pcdb->getUoMsForPackaging('Package UOM');
$priceuoms=$pcdb->getUoMsForPrice();
$pricetypes=$pcdb->getPriceTypeCodes();
$favoriteparttypes=$pim->getFavoriteParttypes();
$lifecyclestatuses=$pcdb->getLifeCycleCodes();
$descriptioncodes=$pcdb->getPartDescriptionTypeCodes();
$descriptionlanguagecodes=$pcdb->getPartDescriptionLanguageCodes();
$pricesheets=$pricing->getPricesheets();
$history=$logs->getPartEvents($partnumber,50);

$defaultdescriptionlanguagecode=$configGet->getConfigValue('defaultDescriptionLanguageCode','EN');
$defaultdescriptiontypecode=$configGet->getConfigValue('defaultDescriptionTypeCode');

$balance=$pim->getPartBalance($partnumber);

$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');
$vio=$pim->partVIOexperian($partnumber, 'US', '2021Q2');

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        
        <script>
            function updatePart(partnumber,elementtype,elementid)
            {
             var value='';
             if(elementtype=='text'){value=document.getElementById(elementid).value;}
             if(elementtype=='select')
             {
              var e=document.getElementById(elementid);
              value=e.options[e.selectedIndex].value;
             }
             document.getElementById("sandpiperoid").innerHTML='';

             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdatePart.php?partnumber='+partnumber+'&elementid='+elementid+'&value='+encodeURIComponent(value));
             xhr.onload = function()
             {
              var response=xhr.responseText;
              document.getElementById("sandpiperoid").innerHTML=response;
              setStatusColor();
             };
             xhr.send();
            }
            
            function setStatusColor()
            {
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxGetPart.php?partnumber=<?php echo $partnumber;?>');
             xhr.onload = function()
             {
              var part=JSON.parse(xhr.responseText);
              var statusClassName="partstatus-available";
              if(part.lifecyclestatus==0){statusClassName="partstatus-proposed";}
              if(part.lifecyclestatus==1){statusClassName="partstatus-released";}
              if(part.lifecyclestatus==4){statusClassName="partstatus-announced";}
              if(part.lifecyclestatus==7){statusClassName="partstatus-superseded";}
              if(part.lifecyclestatus==8){statusClassName="partstatus-discontinued";}
              if(part.lifecyclestatus==9){statusClassName="partstatus-obsolete";}
              
              document.getElementById("label-status").className=statusClassName;
              document.getElementById("value-status").className=statusClassName;
             };
             xhr.send();
            }
            

            function addDescription()
            {
             var descriptiontext = document.getElementById("descriptiontext").value;
             var descriptioncode = document.getElementById("descriptioncode").value;
             var languagecode = document.getElementById("descriptionlanguagecode").value;
             if(descriptiontext.trim().length>0)
             {
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddDescription.php?descriptiontext='+btoa(descriptiontext)+'&descriptioncode='+descriptioncode+'&languagecode='+languagecode+'&partnumber=<?php echo $partnumber;?>');
              
              xhr.onload = function()
              {
               var response=JSON.parse(xhr.responseText);
               document.getElementById("sandpiperoid").innerHTML=response.oid;

               var container=document.getElementById('descriptions');
               container.innerHTML+='<div id="descriptionid_'+response.id+'" style="font-size: 80%;">'+response.descriptioncode+': '+descriptiontext+' <button onclick="deleteDescription('+response.id+')">x</button></div>';
              };
              xhr.send();
             }
            }

            function deleteDescription(descriptionid)
            {
             var descriptionsdiv = document.getElementById('descriptionid_'+descriptionid);
             descriptionsdiv.parentNode.removeChild(descriptionsdiv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxDeleteDescription.php?id='+descriptionid+'&partnumber=<?php echo $partnumber;?>');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              document.getElementById("sandpiperoid").innerHTML=response.oid;
             };
             xhr.send();
            }


            function addPAdbAttribute(PAID)
            {
                
             var PAIDvalue=document.getElementById('unappliedattributevalue_'+PAID).value;
             var PAIDuom=document.getElementById('unappliedattributeuom_'+PAID).value;
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdateAttributeOfPart.php?partnumber=<?php echo $partnumber;?>&attribute='+PAID+'&value='+encodeURIComponent(PAIDvalue)+'&uom='+encodeURIComponent(PAIDuom));
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              if(response.success)
              { //add attribute to "applied" list
               var container=document.getElementById('appliedattributes');
               container.innerHTML+='<div id="appliedattribute_'+response.id+'"><div style="width:2em;float:left;"><button onclick="deleteAttribute('+response.id+','+response.PAID+',\''+response.name+'\')">x</button></div><div style="border:1px solid;padding:1px; margin-bottom:1px; background:#dddddd;float:left;">'+response.name+':<span style="background-color:#f8f8f8;padding-left:4px;padding-right:4px;">'+response.value+' '+response.uom+'</span></div><div style="clear:both;"></div></div>';
               //remove PAdb form "unapplied" list
               var unappliedattributediv = document.getElementById('unappliedattribute_'+PAID);
               unappliedattributediv.parentNode.removeChild(unappliedattributediv);
               
               // show new oid
               document.getElementById("sandpiperoid").innerHTML=response.oid;
              }
             };
             xhr.send();
            }

            function deleteAttribute(attributeid,PAID,name)
            {
             var appliedattributediv = document.getElementById('appliedattribute_'+attributeid);
             appliedattributediv.parentNode.removeChild(appliedattributediv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxDeletePartAttribute.php?attributeid='+attributeid+'&partnumber=<?php echo $partnumber;?>');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              
              // add it back to the "unnapplied" list
              var container=document.getElementById('unappliedattributes');
              //container.innerHTML+='<div id="unappliedattribute_'+PAID+'"><div style="width:2em;float:left;"><button onclick="addPAdbAttribute('+PAID+')">+</button></div><div style="float:left;">'+name+'</div><div style="clear:both;"></div></div>';
              container.innerHTML+='<div style="text-align:left;padding:3px;" id="unappliedattribute_'+PAID+'">'+name+' <span><input size="8" id="unappliedattributevalue_'+PAID+'"/> <input size="2" id="unappliedattributeuom_'+PAID+'"/></span><button onclick="addPAdbAttribute('+PAID+')">+</button></div>';

              document.getElementById("sandpiperoid").innerHTML=response.oid;
             };
             xhr.send();
            }

            function deleteInterchange(interchangeid)
            {
             var interchangediv = document.getElementById('interchangeid_'+interchangeid);
             interchangediv.parentNode.removeChild(interchangediv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxDeleteInterchange.php?id='+interchangeid+'&partnumber=<?php echo $partnumber;?>');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              document.getElementById("sandpiperoid").innerHTML=response.oid;
             };
             xhr.send();
            }
            
            
            function addInterchange()
            {
             var competitivepartnumber = document.getElementById("competitivepartnumber").value;
             var brand = document.getElementById("competitivebrand").value;
             if(competitivepartnumber.trim().length>0)
             {
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddInterchange.php?brand='+brand+'&competitivepartnumber='+competitivepartnumber+'&partnumber=<?php echo $partnumber;?>');
              xhr.onload = function()
              {
               var response=JSON.parse(xhr.responseText);
               document.getElementById("sandpiperoid").innerHTML=response.oid;

               var container=document.getElementById('interchanges');
               container.innerHTML+='<div id="interchangeid_'+response.id+'" style="font-size: 80%;">'+response.brandname+':'+competitivepartnumber+' <button onclick="deleteInterchange('+response.id+')">x</button></div>';
              };
              xhr.send();
             }
            }

            function addPackage()
            {
                
             var packagebarcodecharacters='';
             var packageuom = document.getElementById("packageuom").value;
             var packagelevelgtin = document.getElementById("packagelevelgtin").value;
             var quantityofeaches = document.getElementById("quantityofeaches").value;
             var innerquantity = document.getElementById("innerquantity").value;
             var innerquantityuom = document.getElementById("innerquantityuom").value;
             var weight = document.getElementById("weight").value;
             var weightsuom = document.getElementById("weightsuom").value;
             var shippinglength = document.getElementById("shippinglength").value;
             var shippingwidth = document.getElementById("shippingwidth").value;
             var shippingheight = document.getElementById("shippingheight").value;
             var dimensionsuom = document.getElementById("dimensionsuom").value;
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddPackage.php?packagebarcodecharacters='+packagebarcodecharacters+'&packageuom='+packageuom+'&packagelevelgtin='+packagelevelgtin+'&quantityofeaches='+quantityofeaches+'&innerquantity='+innerquantity+'&innerquantityuom='+innerquantityuom+'&weight='+weight+'&weightsuom='+weightsuom+'&shippinglength='+shippinglength+'&shippingwidth='+shippingwidth+'&shippingheight='+shippingheight+'&dimensionsuom='+dimensionsuom+'&partnumber=<?php echo $partnumber;?>');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              document.getElementById("sandpiperoid").innerHTML=response.oid;

              var container=document.getElementById('packages');
              container.innerHTML+='<div id="packageid_'+response.id+'" style="background-color:#cd9f61; font-size: 80%; border:2px solid #808080;margin: 2px;">'+response.nicepackage+' <button onclick="deletePackage('+response.id+')">x</button></div>';
             };
             xhr.send();
            }

            function deletePackage(packageid)
            {
             var packagesdiv = document.getElementById('packageid_'+packageid);
             packagesdiv.parentNode.removeChild(packagesdiv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxDeletePackage.php?id='+packageid+'&partnumber=<?php echo $partnumber;?>');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              document.getElementById("sandpiperoid").innerHTML=response.oid;
             };
             xhr.send();
            }

            function addPrice()
            {
             var amount = document.getElementById("priceamount").value;
             var pricetype = document.getElementById("newpricetype").getAttribute("data-pricetype");
             var pricesheetnumber = document.getElementById("pricesheetnumber").value;
             var currency = document.getElementById("newpricecurrency").getAttribute("data-currency");
             var priceuom = document.getElementById("priceuom").value;
             
             if(amount>0)
             {
              var xhr = new XMLHttpRequest();
              xhr.open('GET', 'ajaxAddPrice.php?pricesheetnumber='+pricesheetnumber+'&amount='+amount+'&currency='+currency+'&priceuom='+priceuom+'&pricetype='+pricetype+'&partnumber=<?php echo $partnumber;?>');
              
              xhr.onload = function()
              {
               var response=JSON.parse(xhr.responseText);
               document.getElementById("sandpiperoid").innerHTML=response.oid;

               var container=document.getElementById('prices');
               container.innerHTML+='<div id="priceid_'+response.id+'" style="background-color:#85bb65; font-size: 80%; border:2px solid #808080;margin: 2px;">'+response.niceprice+' <button onclick="deletePrice('+response.id+')">x</button></div>';
              };
              xhr.send();
             }
            }

            function deletePrice(priceid)
            {
             var pricesdiv = document.getElementById('priceid_'+priceid);
             pricesdiv.parentNode.removeChild(pricesdiv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxDeletePrice.php?id='+priceid+'&partnumber=<?php echo $partnumber;?>');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              document.getElementById("sandpiperoid").innerHTML=response.oid;
             };
             xhr.send();
            }

            function showSlectedPricesheetCurrency()
            {
             var e = document.getElementById("pricesheetnumber");
             var selectedpricesheetnumber = e.options[e.selectedIndex].value;
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxPricesheetCurrency.php?pricesheetnumber='+selectedpricesheetnumber);
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              document.getElementById("newpricecurrency").innerHTML=response.currencycode
              document.getElementById("newpricecurrency").setAttribute("data-currency",response.currencycode);
              document.getElementById("newpricetype").innerHTML=response.pricetypename;
              document.getElementById("newpricetype").setAttribute("data-pricetype",response.pricetype);
              document.getElementById("priceamount").disabled=false;
              document.getElementById("addprice").disabled=false;
             };
             xhr.send();
            }


            function showhideNewDescription()
            {
             var x = document.getElementById("newdescription");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
            }

            function showhideNewInterchange()
            {
             var x = document.getElementById("newinterchange");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
            }

            function showhideNewpackage()
            {
             var x = document.getElementById("newpackage");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
            }

            function showhideNewPrice()
            {
             var x = document.getElementById("newprice");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
            }


            function showhideUnappliedAttributes()
            {
             var x = document.getElementById("unappliedattributes");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
            }


            function disconnectAsset(connectionid)
            {
                var assetdiv = document.getElementById('assetconnectionid_'+connectionid);
                assetdiv.parentNode.removeChild(assetdiv);

                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxDisconnectPartAsset.php?connectionid='+connectionid+'&partnumber=<?php echo $partnumber;?>');
                xhr.onload = function()
                {
                 var response=JSON.parse(xhr.responseText);
                 document.getElementById("sandpiperoid").innerHTML=response.partoid;
                };
                xhr.send();
            }



            function addPartToClipboard()
            {
             var description='<a href="showApp.php'+ window.location.search +'"><?php echo $partnumber;?></a>';
             var objectkey='<?php echo $partnumber;?>';
             var objectdata='';
             
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddToClipboard.php?objecttype=part&description='+btoa(description)+'&objectkey='+objectkey+'&objectdata='+btoa(objectdata)+'&submit');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
             };
             xhr.send();
             refreshClipboard();
            }



        </script>
        
    </head>
    <body onload="setStatusColor()">
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">

                <?php $issues=$pim->getIssues('PART/%', $partnumber, 0, array(1,2) ,10);
                if(count($issues)>0){?>
                <div class="card shadow-sm">
                    <h5 class="card-header">
                        Issues
                    </h5>
                    <div class="card-body">
                        <?php
                        foreach($issues as $issue)
                        {
                            echo '<div><a href="showIssue.php?id='.$issue['id'].'">'.$issue['description'].'</a></div>';
                        }?>
                    </div>
                </div>
                <?php }?>
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-7 my-col colMain">
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start">
                            Part Number <span class="text-info"><?php echo $part['partnumber']; ?></span>
                            <div style="float:right;">
                                    
                                <span class="btn btn-info" onclick="addPartToClipboard(),refreshClipboard()">Copy</span>
                                
                                <?php if(count($history)){echo '<span><a class="btn btn-secondary" href="./partHistory.php?partnumber='.$partnumber.'">History</a></span>';} ?>
                            </div>
                        </h3>
                        <div class="card-body">
                            <?php if ($part) {; ?>
                            <div style="padding:10px;">
                                <table class="table" border="1" cellpadding="5">
                                    <tr><th>Part Type</th><td><div style="float:left;"><select id="parttypeid" onchange="if (this.selectedIndex) updatePart('<?php echo $partnumber;?>','select','parttypeid');"><option value="0">Undefined</option><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"<?php if($parttype['id']==$part['parttypeid']){echo ' selected';}?>><?php echo $parttype['name'];?></option><?php }?></select></div><div style="float:left;padding-left:10px;"><a href="./pcdbTypeBrowser.php?searchtype=selected&searchterm=&submit=Search"><img src="./settings.png" width="18" alt="settings"/></a></div><div style="clear:both;"></div></td></tr>
                                    <tr><th>Category</th><td><div style="float:left;"><select id="partcategory" onchange="if (this.selectedIndex) updatePart('<?php echo $partnumber;?>','select','partcategory');"><option value="0">Undefined</option> <?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"<?php if ($partcategory['id'] == $part['partcategory']) {echo ' selected';} ?>><?php echo $partcategory['name']; ?></option><?php } ?></select></div><div style="float:left;padding-left:10px;"><a href="./partCategories.php"><img src="./settings.png" width="18" alt="settings"/></a></div><div style="clear:both;"></div></td></tr>
                                    <tr><th id="label-status" class="partstatus-available">Status</th><td id="value-status" class="partstatus-available"><select id="lifecyclestatus" onchange="updatePart('<?php echo $partnumber;?>','select','lifecyclestatus');"><?php foreach($lifecyclestatuses as $lifecyclestatus){?> <option value="<?php echo $lifecyclestatus['code'];?>"<?php if($lifecyclestatus['code']==$part['lifecyclestatus']){echo ' selected';}?>><?php echo $lifecyclestatus['description'];?></option><?php }?></select></td><tr/>
                                    <tr>
                                        <th>Descriptions</th>
                                        <td>
                                            <div id="descriptions">
                                            <?php foreach($descriptions as $description){;?><div id="descriptionid_<?php echo $description['id'];?>" style="font-size: 80%;"><?php echo $description['descriptioncode'].': '.$description['description'].' <button onclick="deleteDescription(\''.$description['id'].'\')">x</button>';?></div><?php }?>
                                            </div>
                                            <div onclick="showhideNewDescription()">...</div>
                                            <div id="newdescription" style="display:none; padding-top: 10px;">
                                                <div style="padding:5px;"><input type="text" id="descriptiontext" size="40"/><button id="adddescrption" onclick="addDescription()">Add</button></div>
                                                <div><select id="descriptioncode"><?php foreach($descriptioncodes as $descriptioncode){$selected=''; if($descriptioncode['code']==$defaultdescriptiontypecode){$selected=' selected';} echo '<option value="'.$descriptioncode['code'].'"'.$selected.'>'.$descriptioncode['description'].'</option>';}?></select> <select id="descriptionlanguagecode"><?php foreach($descriptionlanguagecodes as $descriptionlanguagecode){$selected=''; if($descriptionlanguagecode['code']==$defaultdescriptionlanguagecode){$selected=' selected';} echo '<option value="'.$descriptionlanguagecode['code'].'"'.$selected.'>'.$descriptionlanguagecode['description'].'</option>';}?></select></div>
                                            </div>
                                        </td>
                                    <tr>
                                    <tr><th>GTIN (Item Level)</th><td><input type="text" id="gtin" value="<?php echo $part['GTIN']?>"/><button class="btn btn-sm btn-outline-secondary" onclick="updatePart('<?php echo $partnumber;?>','text','gtin');">Update</button></td><tr>
                                    <?php /*    <tr><th>UNSPC</th><td><input type="text" id="unspc" value="<?php echo $part['UNSPC']?>"/><button class="btn btn-sm btn-outline-secondary"  onclick="updatePart('<?php echo $partnumber;?>','text','unspc');">Update</button></td><tr> */ ?>
                                    <tr><th>Replaced By</th><td><input type="text" id="replacedby" value="<?php echo $part['replacedby']?>"/><button class="btn btn-sm btn-outline-secondary"  onclick="updatePart('<?php echo $partnumber;?>','text','replacedby');">Update</button></td><tr>
                                    <?php if($balance){?> <tr><th>Balance</th><td>On-Hand: <b><?php echo round($balance['qoh'],0);?></b>, Demand: <b><?php echo $balance['amd'];?></b> units/month</td><tr> <?php }?>
                                    <tr><th>Internal<br/>Notes</th><td><textarea  id="internalnotes"  cols="50"><?php echo $part['internalnotes']?></textarea><div><button class="btn btn-sm btn-outline-secondary"  onclick="updatePart('<?php echo $partnumber;?>','text','internalnotes');">Update</button></div></td><tr>
                                    <tr>
                                        <th>Interchange</th>
                                        <td>
                                            <div id="interchanges">
                                            
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                          <th scope="col">Competitor</th>
                                                          <th scope="col">Part Number</th>
                                                          <th scope="col">Delete</th>
                                                        </tr>
                                                    </thead>
                                                    <?php foreach($competitorparts as $competitorpart){;?>
                                                    <tr style="font-size: 80%;">
                                                        <th scope="row" id="interchangeid_<?php echo $competitorpart['id'];?>"><?php echo $interchange->brandName($competitorpart['brandAAIAID'])?></td>
                                                        <td><?php echo $competitorpart['competitorpartnumber'] ?></td>
                                                        <td><?php echo '<button class="btn btn-sm btn-outline-danger" onclick="deleteInterchange(\''.$competitorpart['id'].'\')">x</button>';?></td>
                                                    </tr>
                                                    <?php }?>
                                                </table>
                                            </div>
                                            <div onclick="showhideNewInterchange()">...</div>
                                            <div id="newinterchange" style="display:none; padding-top: 10px;"><div style="float:left;padding-right: 10px;"><a href="./competitiveBrandBrowser.php?searchtype=selected&searchterm=&submit=Search"><img src="./settings.png" width="18" alt="settings"/></a></div><div style="float:left;"><select id="competitivebrand"><?php foreach($competitivebrands as $competitivebrand){echo '<option value="'.$competitivebrand['brandAAIAID'].'">'.$competitivebrand['description'].'</option>';}?></select><input type="text" id="competitivepartnumber" size="10"/><button id="addinterchange" onclick="addInterchange()">+</button></div><div style="clear:both;"></div></div>
                                        </td>
                                    <tr>
                                    <tr>
                                        <th>Packages</th>
                                        <td>
                                            <div id="packages"> 
                                            <?php foreach($packages as $package){;?><div style="background-color:#cd9f61; font-size: 80%; border:2px solid #808080;margin: 2px;" id="packageid_<?php echo $package['id'];?>" style="font-size: 80%;"><?php echo $package['nicepackage'];?>  <button onclick="deletePackage(<?php echo $package['id'];?>)">x</button></div><?php }?>
                                            </div>
                                            <div onclick="showhideNewpackage()">...</div>
                                            <div id="newpackage" style="display: none; padding-top: 10px; text-align:left;">
                                                <div style="padding-top:3px;">Package UoM <select id="packageuom"><?php foreach($packageuoms as $packageuom){$selected=''; if($packageuom['code']=='EA'){$selected=' selected';} echo '<option value="'.$packageuom['code'].'"'.$selected.'>'.$packageuom['description'].'</option>';}?></select></div>
                                                <div style="padding-top:3px;">Package-Level GTIN <input type="text" id="packagelevelgtin" size="12"/></div>
                                                <div style="padding-top:3px;">Qty of Eaches <input type="text" id="quantityofeaches" size="2" value="1" style="text-align:right;"/></div>
                                                <div style="padding-top:3px;">Inner Qty <input type="text" id="innerquantity" size="2" value="1" style="text-align:right;"/><select id="innerquantityuom"><?php foreach($innerqtyuoms as $innerqtyuom){$selected=''; if($innerqtyuom['code']=='EA'){$selected=' selected';} echo '<option value="'.$innerqtyuom['code'].'"'.$selected.'>'.$innerqtyuom['description'].'</option>';}?></select></div>
                                                <div style="padding-top:3px;">Weight <input type="text" id="weight" size="2" style="text-align:right;"/><select id="weightsuom"><?php foreach($weightsuoms as $weightsuom){echo '<option value="'.$weightsuom['code'].'">'.$weightsuom['description'].'</option>';}?></select></div>
                                                <div style="padding-top:3px;">L / W / H <input type="text" id="shippinglength" size="2" style="text-align:right;"/><input type="text" id="shippingwidth" size="2" style="text-align:right;"/><input type="text" id="shippingheight" size="2" style="text-align:right;"/><select id="dimensionsuom"><?php foreach($dimensionsuoms as $dimensionsuom){echo '<option value="'.$dimensionsuom['code'].'">'.$dimensionsuom['description'].'</option>';}?></select></div>
                                                <div><button id="addpackage" onclick="addPackage()">Create</button></div>
                                            </div>
                                        </td>
                                    <tr>
                                    <tr><th>Prices</th>
                                        <td>
                                            <div id="prices">
                                            <?php foreach($prices as $price){;?><div id="priceid_<?php echo $price['id'];?>" style="background-color:#85bb65; font-size: 80%; border:2px solid #808080;margin: 2px;"><?php echo $price['niceprice'];?> <button onclick="deletePrice(<?php echo $price['id'];?>)">x</button></div><?php }?>
                                            </div>
                                            <div onclick="showhideNewPrice()">...</div>
                                            <div id="newprice" style="display:none; text-align: left; padding-top: 10px;">
                                                <div style="padding-top:3px;"><div style="float:left;">Price Sheet Number <select id="pricesheetnumber" name="pricesheet" onchange="showSlectedPricesheetCurrency()"><option value="">select...</option><?php foreach($pricesheets as $pricesheet){echo '<option value="'.$pricesheet['number'].'">'.$pricesheet['description'].'</option>';}?></select></div><div style="float:left;padding-left: 5px;"><a href="./priceSheets.php"><img src="./settings.png" width="18" alt="settings"/></a></div><div style="clear:both;"></div> </div>
                                                <div style="padding-top:3px;">Unit of Measure <select id="priceuom" name="priceuom"><?php foreach($priceuoms as $priceuom){$selected =''; if($priceuom['code']=='PE'){$selected=' selected';} echo '<option value="'.$priceuom['code'].'"'.$selected.'>'.$priceuom['description'].'</option>';}?></select></div>
                                                <div style="padding-top:3px;"><div style="float:left;">Price Type: </div><div id="newpricetype" data-pricetype="" style="float:left;padding-top:1px;padding-right:5px;"></div><div style="clear:both;"></div> </div>
                                                <div style="padding-top:3px;"><div style="float:left;">Amount <input disabled style="text-align:right;" type="text" id="priceamount" size="4"/></div> <div id="newpricecurrency" data-currency="" style="float:left;padding-top:3px;padding-right:5px;"></div> <button id="addprice" disabled onclick="addPrice()">+</button><div style="clear:both;"></div></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr><th>Attributes</th>
                                        <td>
                                            <div id="appliedattributes" style="padding:5px;">
                                                <?php foreach ($attributes as $attribute) 
                                                {
                                                    if($attribute['PAID']==0)
                                                    {
                                                        echo '<div id="appliedattribute_'.$attribute['id'].'"><div style="width:2em;float:left;"><button class="btn btn-sm btn-outline-danger" onclick="deleteAttribute('.$attribute['id'].','.$attribute['PAID'].',\''.$padb->PAIDname($attribute['PAID']).'\')">x</button></div><div style="border:1px solid;padding:1px; margin-bottom:1px; background:#dddddd;float:left;">'.$attribute['name'].':<span style="background-color:#f8f8f8;padding-left:4px;padding-right:4px;">'.$attribute['value'].' '.$attribute['uom'].'</span></div><div style="clear:both;"></div></div>';
                                                    }
                                                    else
                                                    {
                                                        echo '<div id="appliedattribute_'.$attribute['id'].'"><div style="width:2em;float:left;"><button class="btn btn-sm btn-outline-danger" onclick="deleteAttribute('.$attribute['id'].','.$attribute['PAID'].',\''.$padb->PAIDname($attribute['PAID']).'\')">x</button></div><div style="border:1px solid;padding:1px; margin-bottom:1px; background:#dddddd;float:left;">'.$padb->PAIDname($attribute['PAID']).':<span style="background-color:#f8f8f8;padding-left:4px;padding-right:4px;">'.$attribute['value'].' '.$attribute['uom'].'</span></div><div style="clear:both;"></div></div>';
                                                    }
                                                } ?>
                                            </div>
                                            <div onclick="showhideUnappliedAttributes()">...</div>
                                            <div id="unappliedattributes" style="display:none; padding:5px;">
                                                    <?php foreach ($validpadbattributes as $attribute) { if($pim->getPartAttribute($part['partnumber'], $attribute['PAID'], '')){continue;}
                                                        echo '<div style="text-align:left;padding:3px;" id="unappliedattribute_'.$attribute['PAID'].'">'. $attribute['name'] . ' <span><input size="8" id="unappliedattributevalue_'.$attribute['PAID'].'"/> <input size="2" id="unappliedattributeuom_'.$attribute['PAID'].'"/></span><button onclick="addPAdbAttribute('.$attribute['PAID'].')">+</button></div>';
                                                    }?>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Assets</th>
                                        <td>
                                            <?php 
                                            foreach($connectedassets as $connectedasset)
                                            {
                                                echo '<div>';
                                                if($connectedasset['assettypecode']=='P04' && $connectedasset['uri']!='')
                                                {
                                                    echo '<div><img class="img-thumbnail" src="'.$connectedasset['uri'].'" width="400px"/></div>';
                                                }
                                                echo '<div id="assetconnectionid_'.$connectedasset['connectionid'].'" style="padding:2px;"><a class="btn btn-info" role="button" href="showAsset.php?assetid='.$connectedasset['assetid'].'">'.$connectedasset['assetid'].'</a> <button class="btn btn-sm btn-outline-danger" onclick="disconnectAsset(\''.$connectedasset['connectionid'].'\')"><span aria-hidden="true">&times;</span></button></div>';
                                                echo '</div>';

                                            };
                                            ?>
                                        </td>
                                    <tr>
                                    <tr><th>VIO (<?php echo $viogeography.' '.$vioyearquarter;?>)</th><td><?php echo number_format($vio,0,'.',',');?></td><tr>

                                    <tr><th>Sandpiper OID</th><td><div id="sandpiperoid"><?php echo $part['oid']; ?></div></td><tr>
                                </table>
                            </div>
                            <?php
                            } else {
                                echo 'Part ('.$partnumber.') not found';
                            }
                            ?>
                        </div>
                    </div>
                                       
                    
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-3 my-col colRight">
                    <div class="card shadow-sm">
                        <h4 class="card-header text-start">Applications <?php echo '<span class="badge bg-primary rounded-pill">'.count($apps).'</span>'; ?></h4>
                        <div class="card-body d-flex flex-column scroll">
                            <?php foreach ($apps as $app) {
                                echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="showApp.php?appid=' . $app['id'] . '">' . $vcdb->niceMMYofBasevid($app['basevehicleid']) . ' ' . niceAppAttributes($app['attributes']) . '</a>';} 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>