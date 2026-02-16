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
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'showPart.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
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

$partnumber = $pim->sanitizePartnumber($_GET['partnumber']);

$part = $pim->getPart($partnumber);
$apps = $pim->getAppsByPartnumber($partnumber);
$appsummarystruct = $pim->getAppSummary($partnumber);
$attributes = $pim->getPartAttributes($partnumber);
$expis=$pim->getPartEXPIs($partnumber);
$validexpis=$pcdb->getAllEXPIcodes();
$validpadbattributes=$padb->getAttributesForParttype($part['parttypeid']);
$assets_linked_to_item = array();
$partcategories = $pim->getPartCategories();
$connectedassets=$asset->getAssetsConnectedToPart($partnumber);
$descriptionswhole=$pim->getPartDescriptions($partnumber);
$descriptions=[];
foreach($descriptionswhole as $descriptionwhole)
{// 'id'=>$row['id'],'description'=>$row['description'],'descriptioncode'=>$row['descriptioncode'],'sequence'=>$row['sequence'],'languagecode'=>$row['languagecode'],'inheritedfrom'=>''
 $descriptiontemp=$descriptionwhole;
 if(strlen($descriptionwhole['description'])>80)
 {
  $descriptiontemp['description']=substr($descriptionwhole['description'],0,100).' ......';
  $descriptiontemp['descriptionfull']=$descriptionwhole['description'];
 }
 $descriptions[]=$descriptiontemp;
}    

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
$history=$logs->getPartEvents($partnumber,100);
$assemblies=$pim->getWhereUsedOfKitComponent($partnumber);
$bomchangescount=$logs->countBOMchangeEvents($partnumber);

$defaultdescriptionlanguagecode=$configGet->getConfigValue('defaultDescriptionLanguageCode','EN');
$defaultdescriptiontypecode=$configGet->getConfigValue('defaultDescriptionTypeCode');
$showAppAttributesInSummary=$configGet->getConfigValue('showAppAttributesInSummary','no');


$balance=$pim->getPartBalance($partnumber);

$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');
$vio=$pim->partVIOtotal($partnumber, $viogeography, $vioyearquarter);
$viomeanyear=$pim->partVIOmeanYear($partnumber, $viogeography, $vioyearquarter);
$viostartyear=$pim->partVIOstartYear($partnumber, $viogeography, $vioyearquarter);
$vioendyear=$pim->partVIOendYear($partnumber, $viogeography, $vioyearquarter);


$primaryphotouri=$asset->primaryPhotoURIofPart($partnumber);

$dependantparts=$pim->getPartnumbersByBasepart($partnumber);
$kitcomponents=$pim->getKitComponents($partnumber);

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
              var response=JSON.parse(xhr.responseText);
              document.getElementById("sandpiperoid").innerHTML=response.oid;
              setStatusColor();
              if(response.success)
              {
               document.getElementById("heading-alert").style.display='none';
               document.getElementById("heading-alert").innerHTML='';
              }
              else
              {
               document.getElementById("heading-alert").style.display='block';
               document.getElementById("heading-alert").innerHTML=response.message;
              }
              
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
               //container.innerHTML+='<div id="descriptionid_'+response.id+'" style="font-size: 80%;">'+response.descriptioncode+': '+descriptiontext+' <button onclick="deleteDescription('+response.id+')">x</button></div>';
               container.innerHTML+='<div style="padding-bottom:3px;" id="descriptionid_'+response.id+'"><div style="float:left;"><button class="btn btn-sm btn-outline-danger" title="Remove this '+response.descriptioncode+' code description from this part" onclick="deleteDescription('+response.id+')">x</button></div><div style="float:left; background-color: #e8e8e8;margin-left:4px; padding:5px;font-size:85%;">'+descriptiontext+'</div><div style="clear:both;"></div></div>';
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
             var PAIDuom='';
             var PAIDvalue=document.getElementById('unappliedattributevalue_'+PAID).value;             
             if(document.getElementById('unappliedattributeuom_'+PAID))
             {
              var e = document.getElementById('unappliedattributeuom_'+PAID);
              PAIDuom = e.options[e.selectedIndex].value;
             }
             
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdateAttributeOfPart.php?partnumber=<?php echo $partnumber;?>&attribute='+PAID+'&value='+encodeURIComponent(PAIDvalue)+'&uom='+encodeURIComponent(PAIDuom));
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              if(response.success)
              { //add attribute to "applied" list
               var container=document.getElementById('appliedattributes');
               container.innerHTML+='<div style="padding:3px;" id="appliedattribute_'+response.id+'"><div style="float:left;"><button class="btn btn-sm btn-outline-danger" onclick="deleteAttribute('+response.id+','+response.PAID+',\''+response.name+'\')">x</button></div><div style="border:1px solid;padding:3px; margin-left:4px; background:#7ad0fe;float:left;">'+response.name+' <span style="background-color:#f8f8f8;padding-left:4px;padding-right:4px;">'+response.value+' '+response.uom+'</span></div><div style="clear:both;"></div></div>';
               //remove PAdb form "unapplied" list
               var unappliedattributediv = document.getElementById('unappliedattribute_'+PAID);
               unappliedattributediv.parentNode.removeChild(unappliedattributediv);
               
               // show new oid
               document.getElementById("sandpiperoid").innerHTML=response.oid;               
              }
             };
             xhr.send();
            }

            function addEXPI(EXPI)
            {
             var EXPIvalue=document.getElementById('unappliedexpivalue_'+EXPI).value;             
             
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdateEXPIofPart.php?partnumber=<?php echo $partnumber;?>&expi='+EXPI+'&value='+encodeURIComponent(EXPIvalue)+'&languagecode=<?php echo $defaultdescriptionlanguagecode;?>');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              if(response.success)
              { //add expi to "applied" list
               var container=document.getElementById('appliedexpis');
               container.innerHTML+='<div style="padding:3px;" id="expiid_'+response.id+'"><div style="float:left;"><button class="btn btn-sm btn-outline-danger" onclick="deleteEXPI('+response.id+')">x</button></div><div style="border:1px solid;padding:3px; margin-left:4px; background:#f49ef7;float:left;">'+response.name+' <span style="background-color:#f8f8f8;padding-left:4px;padding-right:4px;">'+response.value+' </span></div><div style="clear:both;"></div></div>';
               //remove EXPI form "unapplied" list
               var unappliedexpidiv = document.getElementById('unappliedexpi_'+EXPI);
               unappliedexpidiv.parentNode.removeChild(unappliedexpidiv);
               
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
              //var container=document.getElementById('unappliedattributes');
              //container.innerHTML+='<div style="text-align:left;padding:3px;" id="unappliedattribute_'+PAID+'">'+name+' <span><input size="8" id="unappliedattributevalue_'+PAID+'"/> <input size="2" id="unappliedattributeuom_'+PAID+'"/></span><button onclick="addPAdbAttribute('+PAID+')">+</button></div>';

              document.getElementById("sandpiperoid").innerHTML=response.oid;
             };
             xhr.send();
            }

            function addNonPAdbAttribute()
            {
             var name=document.getElementById('nonpadbname').value;
             var value=document.getElementById('nonpadbvalue').value;
             var uom=document.getElementById('nonpadbuom').value;
             
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdateAttributeOfPart.php?partnumber=<?php echo $partnumber;?>&attribute='+encodeURIComponent(name)+'&value='+encodeURIComponent(value)+'&uom='+encodeURIComponent(uom));
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              if(response.success)
              { //add attribute to "applied" list 

               // just in case the user-defined attribute already exists - delete it first (this is possible with user-defined attributes)
               var appliedattributediv = document.getElementById('appliedattribute_'+response.id);
               if(appliedattributediv)
               {
                appliedattributediv.parentNode.removeChild(appliedattributediv);
               }

               var container=document.getElementById('appliedattributes');
               container.innerHTML+='<div style="padding-bottom:3px;" id="appliedattribute_'+response.id+'"><div style="float:left;"><button class="btn btn-sm btn-outline-danger" onclick="deleteAttribute('+response.id+','+response.PAID+',\''+response.name+'\')">x</button></div><div style="border:1px solid;padding:3px; margin-left:4px; background:#dddddd;float:left;">'+response.name+' <span style="background-color:#f8f8f8;padding-left:4px;padding-right:4px;">'+response.value+' '+response.uom+'</span></div><div style="clear:both;"></div></div>';
               // show new oid
               document.getElementById("sandpiperoid").innerHTML=response.oid;
              }
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
                
             var packagebarcodecharacters= document.getElementById("packagebarcodecharacters").value;
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
             var merchandisinglength = document.getElementById("merchandisinglength").value;
             var merchandisingwidth = document.getElementById("merchandisingwidth").value;
             var merchandisingheight = document.getElementById("merchandisingheight").value;
             var dimensionsuom = document.getElementById("dimensionsuom").value;
             var orderable = document.getElementById("orderable").value;
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddPackage.php?packagebarcodecharacters='+packagebarcodecharacters+'&packageuom='+packageuom+'&packagelevelgtin='+packagelevelgtin+'&quantityofeaches='+quantityofeaches+'&innerquantity='+innerquantity+'&innerquantityuom='+innerquantityuom+'&weight='+weight+'&weightsuom='+weightsuom+'&shippinglength='+shippinglength+'&shippingwidth='+shippingwidth+'&shippingheight='+shippingheight+'&merchandisinglength='+merchandisinglength+'&merchandisingwidth='+merchandisingwidth+'&merchandisingheight='+merchandisingheight+'&dimensionsuom='+dimensionsuom+'&orderable='+orderable+'&partnumber=<?php echo $partnumber;?>');
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
               //container.innerHTML+='<div id="priceid_'+response.id+'" style="background-color:#85bb65; font-size: 80%; border:2px solid #808080;margin: 2px;">'+response.niceprice+' <button onclick="deletePrice(' + response.id + ')">D</button></div>';
               container.innerHTML+='<div style="padding-bottom:3px;" id="priceid_'+response.id+'"><div style="float:left;"><button class="btn btn-sm btn-outline-danger" title="Remove this price from this part" onclick="deletePrice('+response.id+')">x</button></div><div style="float:left; background-color:#85bb65;border:2px solid #808080;margin-left:4px;padding:2px">'+response.niceprice+'</div><div style="clear:both;"></div></div>';
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

            function deleteEXPI(expiid)
            {
             var expisdiv = document.getElementById('expiid_'+expiid);
             expisdiv.parentNode.removeChild(expisdiv);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxDeleteExpi.php?id='+expiid+'&partnumber=<?php echo $partnumber;?>');
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

            function showhideUnappliedEXPI()
            {
             var x = document.getElementById("unappliedexpis");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
            }

            function showhideAssetForm()
            {
             var x = document.getElementById("assetform");
             var y = document.getElementById("showAssetFormIcon");
             if (x.style.display === "none") 
             {
              x.style.display = "block";
              y.style.display="none";
             }
             else
             {
              x.style.display = "none";
              y.style.display="block";
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
//             var description='<a href="showApp.php'+ window.location.search +'"><?php echo $partnumber;?></a>';
             var description='<?php echo $partnumber;?>';
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

            function addAppsToClipboard()
            {
                var nodes = document.getElementById('apps').getElementsByTagName("div");
                for(var i=0; i<nodes.length; i++) 
                {
                    //                    console.log(nodes[i].getAttribute('data-appid') + ' - ' + nodes[i].getAttribute('data-description'));
                    var description = nodes[i].getAttribute('data-description-app');
                    var objectdata='';
                    var objectkey=nodes[i].getAttribute('data-appid');
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'ajaxAddToClipboard.php?objecttype=app&description='+description+'&objectkey='+objectkey+'&objectdata='+btoa(objectdata));
                    xhr.onload = function()
                    {
                    };
                    xhr.send();             
                }
                refreshClipboard();
            }


            function addClipboardAppsToPart()
            {
             var appscontainer = document.getElementById('apps');
             
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddClipboardAppsToPart.php?partnumber=<?php echo $partnumber;?>');
             xhr.onload = function()
             {
              var newapps=JSON.parse(xhr.responseText);

              for(var i in newapps)
              {
                 // console.log(newapps[i].id);
               appscontainer.innerHTML+='<a class="btn btn-block btn-secondary" style="margin:5px" href="showApp.php?appid='+newapps[i].id+'">'+newapps[i].niceappdescription+'</a>';
              }
             }
             xhr.send();
            }


            function flagUnsavedGTIN(){document.getElementById("btnUpdateGTIN").className="btn btn-sm btn-danger";}
            function unflagUnsavedGTIN(){document.getElementById("btnUpdateGTIN").className="btn btn-sm btn-outline-secondary";}
            function flagUnsavedReplacedby(){document.getElementById("btnUpdateReplacedby").className="btn btn-sm btn-danger";}
            function unflagUnsavedReplacedby(){document.getElementById("btnUpdateReplacedby").className="btn btn-sm btn-outline-secondary";}
            function flagUnsavedNotes(){document.getElementById("btnUpdateNotes").className="btn btn-sm btn-danger";}
            function unflagUnsavedNotes(){document.getElementById("btnUpdateNotes").className="btn btn-sm btn-outline-secondary";}
            function flagUnsavedBasepart(){document.getElementById("btnUpdateBasepart").className="btn btn-sm btn-danger";}
            function unflagUnsavedBasepart(){document.getElementById("btnUpdateBasepart").className="btn btn-sm btn-outline-secondary";}

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
                    
                <?php 
                
                $foundPrimaries=false; $foundNonprimaries=false;

                foreach($connectedassets as $connectedasset)
                {
                    if($connectedasset['assettypecode']=='P04' && $connectedasset['uri']!='' && ($connectedasset['filetype']=='JPG' || $connectedasset['filetype']=='PNG'))
                    {
                        echo '<div><a href="./showAsset.php?assetid='.$connectedasset['assetid'].'"><img class="img-thumbnail" src="'.$connectedasset['uri'].'" /></a></div>';
                        $foundPrimaries=true;
                    }
                }

                foreach($connectedassets as $connectedasset){if($connectedasset['assettypecode']!='P04' && ($connectedasset['filetype']=='JPG' || $connectedasset['filetype']=='PNG') && $connectedasset['uri']!=''){$foundNonprimaries=true;}}
                
                if($foundPrimaries && $foundNonprimaries){echo '<hr/>';}

                foreach($connectedassets as $connectedasset)
                {
                    if($connectedasset['assettypecode']!='P04' && ($connectedasset['filetype']=='JPG'  || $connectedasset['filetype']=='PNG') && $connectedasset['uri']!='')
                    {
                        echo '<div><a href="./showAsset.php?assetid='.$connectedasset['assetid'].'"><img class="img-thumbnail" src="'.$connectedasset['uri'].'" /></a></div>';
                    }
                }

                
                ?>
                                       
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-7 my-col colMain">
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start">
                            <div>Part Number <span class="text-info"><?php echo $part['partnumber']; ?></span>
                                <?php if(count($dependantparts)){?> <span style="font-size:50%;">Base for <?php echo count($dependantparts); ?> <a href="./partsIndex.php?searchtype=startswith&partnumber=&partcategory=any&parttypeid=any&lifecyclestatus=any&basepart=<?php echo $partnumber;?>&limit=20">parts</a></span> <?php }?>
                                <?php if($pim->validPart($part['basepart'])){?> <span style="font-size:50%;">Based on <a href="./showPart.php?partnumber=<?php echo $part['basepart'];?>"><?php echo $part['basepart'];?></a></span> <?php }?>
                                <div style="float:right;">
                                    <span class="btn btn-info" onclick="addPartToClipboard()">Copy</span>
                                    <?php if(count($history)){echo '<span><a class="btn btn-secondary" href="./partHistory.php?partnumber='.$partnumber.'">History</a></span>';} ?>
                                </div>
                            </div>
                        </h3>
                        <div class="alert alert-danger" role="alert" id="heading-alert" style="display:none;">This is a danger alert—check it out!</div>
                        <div class="card-body">
                            <?php if ($part) {?>
                            <div style="padding:10px;">
                                <table class="table" border="1" cellpadding="5">
                                    <tr><th>Part Type</th><td><div style="float:left;"><select id="parttypeid" onchange="if (this.selectedIndex) updatePart('<?php echo $partnumber;?>','select','parttypeid');"><option value="0">Undefined</option><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"<?php if($parttype['id']==$part['parttypeid']){echo ' selected';}?>><?php echo $parttype['name'];?></option><?php }?></select></div><div style="float:left;padding-left:10px;"><a href="./pcdbTypeBrowser.php?searchtype=selected&searchterm=&submit=Search"><img src="./settings.png" width="18" alt="settings"/></a></div><div style="clear:both;"></div></td></tr>
                                    <tr><th>Category</th><td><div style="float:left;"><select id="partcategory" onchange="if (this.selectedIndex) updatePart('<?php echo $partnumber;?>','select','partcategory');"><option value="0">Undefined</option> <?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"<?php if ($partcategory['id'] == $part['partcategory']) {echo ' selected';} ?>><?php echo $partcategory['name']; ?></option><?php } ?></select></div><div style="float:left;padding-left:10px;"><a href="./partCategories.php"><img src="./settings.png" width="18" alt="settings"/></a></div><div style="clear:both;"></div></td></tr>
                                    <tr>
                                        <th id="label-status" class="partstatus-available">Status</th>
                                        <td id="value-status" class="partstatus-available">
                                            
                                            <?php                                            
                                            $unrestrictedlifecycleedits=$configGet->getConfigValue('unrestrictedLifecycleEdits', 'yes');                                            
                                            if($unrestrictedlifecycleedits=='yes'){?>                                            
                                                <select id="lifecyclestatus" onchange="updatePart('<?php echo $partnumber;?>','select','lifecyclestatus');"><?php foreach($lifecyclestatuses as $lifecyclestatus){?> <option value="<?php echo $lifecyclestatus['code'];?>"<?php if($lifecyclestatus['code']==$part['lifecyclestatus']){echo ' selected';}?>><?php echo $lifecyclestatus['description'];?></option><?php }?></select>
                                            <?php
                                            }
                                            else
                                            {
                                                if($pim->userHasNavelement($_SESSION['userid'], 'PARTS/LIFECYCLE'))
                                                {
                                                    echo '<a href="./partLifecycle.php?partnumber='.$partnumber.'">'.$pcdb->lifeCycleCodeDescription($part['lifecyclestatus']).'</a>';
                                                }
                                                else
                                                {
                                                    echo $pcdb->lifeCycleCodeDescription($part['lifecyclestatus']);
                                                }
                                            }
                                            ?>                                
                                        </td>
                                    <tr/>
                                    <tr>
                                        <th>Descriptions</th>
                                        <td>
                                            <div id="descriptions">
                                            <?php foreach($descriptions as $description)
                                            {
                                                if($description['inheritedfrom']=='')
                                                { // this description is applied directly to this part (not inherited from a basepart) ?>
 
                                                <div style="padding-bottom:3px;" id="descriptionid_<?php echo $description['id'];?>">
                                                    <div style="float:left;"><button class="btn btn-sm btn-outline-danger" title="Remove this <?php echo $description['descriptioncode'];?> code description from this part - ".$description['descriptionfull'] onclick="deleteDescription(<?php echo $description['id'];?>)">x</button></div>
                                                    <div style="float:left; background-color: #e8e8e8;margin-left:4px; padding:5px;font-size:85%;"><?php echo '<b>['.$description['descriptioncode'].']</b> '. $description['description'];?></div>
                                                    <div style="clear:both;"></div>
                                                </div>
                                                
                                                
                                                <?php }
                                                else
                                                {// this description is inherited from the basepart - indicate with an icon and disallow delete

                                                    echo '<div style="float:left;"><img src="./inheritance.png" width="20" title="'.$description['descriptioncode'].' description inherited from '.$description['inheritedfrom'].'"/></div><div style="float:left; background-color: #e8e8e8;margin:4px; padding:5px;font-size:85%;">'.$description['description'].'</div><div style="clear:both;"></div>';
                                                }
                                            }?>
                                            </div>
                                            <div onclick="showhideNewDescription()"><img src="./expandmore.png" title="Expand to show descriptions form"/></div>
                                            <div id="newdescription" style="display:none; padding-top: 10px; text-align: left;">
                                                <div style="padding-bottom:5px;"><input type="text" id="descriptiontext" size="50"/></div>
                                                <div style="padding-bottom:5px;"><select id="descriptioncode"><?php foreach($descriptioncodes as $descriptioncode){$selected=''; if($descriptioncode['code']==$defaultdescriptiontypecode){$selected=' selected';} echo '<option value="'.$descriptioncode['code'].'"'.$selected.'>['.$descriptioncode['code'].'] '.$descriptioncode['description'].' ('.$descriptioncode['format'].')</option>';}?></select> <select id="descriptionlanguagecode"><?php foreach($descriptionlanguagecodes as $descriptionlanguagecode){$selected=''; if($descriptionlanguagecode['code']==$defaultdescriptionlanguagecode){$selected=' selected';} echo '<option value="'.$descriptionlanguagecode['code'].'"'.$selected.'>'.$descriptionlanguagecode['description'].'</option>';}?></select></div>
                                                <div><button class="btn btn-sm btn-success" id="adddescrption" title="Add a description to this part" onclick="addDescription()">Add Description</button></div>
                                            </div>
                                        </td>
                                    <tr>
                                        
                                    <tr><th>Container Type</th><td></td><tr>
                                    <tr><th>Quantities</th><td></td><tr>
                                    <tr><th>Hazardous</th><td></td><tr>
                                        
                                    <tr><th>GTIN</th><td><div style="float:left;"><input type="text" id="gtin" oninput="flagUnsavedGTIN();" value="<?php echo $part['GTIN']?>"/></div><div style="float:left;"><button id="btnUpdateGTIN" class="btn btn-sm btn-outline-secondary" onclick="updatePart('<?php echo $partnumber;?>','text','gtin'); unflagUnsavedGTIN();">Update</button></div><div style="clear:both;"></div></td><tr>
                                    <?php /*    <tr><th>UNSPC</th><td><input type="text" id="unspc" value="<?php echo $part['UNSPC']?>"/><button class="btn btn-sm btn-outline-secondary"  onclick="updatePart('<?php echo $partnumber;?>','text','unspc');">Update</button></td><tr> */ ?>
                                    <tr><th>Replaced By</th><td><div style="float:left;"><input type="text" id="replacedby" oninput="flagUnsavedReplacedby();" value="<?php echo $part['replacedby']?>"/></div><div style="float:left;"><button id="btnUpdateReplacedby" class="btn btn-sm btn-outline-secondary" onclick="updatePart('<?php echo $partnumber;?>','text','replacedby'); unflagUnsavedReplacedby();">Update</button></div><div style="clear:both;"></div></td><tr>
                                    <?php if($balance){?> <tr><th>Balance</th><td>On-Hand: <b><?php echo round($balance['qoh'],0);?></b>, Demand: <b><?php echo $balance['amd'];?></b> units/month</td><tr> <?php }?>
                                    <tr><th>Internal<br/>Notes</th><td><textarea style="width:90%;" id="internalnotes" oninput="flagUnsavedNotes()"><?php echo $part['internalnotes']?></textarea><div><button id="btnUpdateNotes" class="btn btn-sm btn-outline-secondary"  onclick="updatePart('<?php echo $partnumber;?>','text','internalnotes'); unflagUnsavedNotes();">Update</button></div></td><tr>
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
                                                        <th scope="row" id="interchangeid_<?php echo $competitorpart['id'];?>"><?php echo $interchange->brandsubbrandName($competitorpart['brandAAIAID'],$competitorpart['subbrandAAIAID'])?></th>
                                                        <td><?php echo $competitorpart['competitorpartnumber'] ?></td>
                                                        <td><?php echo '<button class="btn btn-sm btn-outline-danger" onclick="deleteInterchange(\''.$competitorpart['id'].'\')">x</button>';?></td>
                                                    </tr>
                                                    <?php }?>
                                                </table>
                                            </div>
                                            <div onclick="showhideNewInterchange()">...</div>
                                            <div id="newinterchange" style="display:none; padding-top: 10px;"><div style="float:left;padding-right: 10px;"><a href="./competitiveBrandBrowser.php?searchtype=selected&searchterm=&submit=Search"><img src="./settings.png" width="18" alt="settings"/></a></div><div style="float:left;"><select id="competitivebrand"><?php foreach($competitivebrands as $competitivebrand){echo '<option value="'.$competitivebrand['brandAAIAID'].'">'.$competitivebrand['description'].'</option>';}?></select> <input type="text" id="competitivepartnumber" size="15"/> <button class="btn btn-sm btn-success" id="addinterchange" onclick="addInterchange()">+</button></div><div style="clear:both;"></div></div>
                                        </td>
                                    <tr>
                                    <tr>
                                        <th>Packages</th>
                                        <td>
                                            <div id="packages"> 
                                            <?php foreach($packages as $package){;?>
                                                <div id="packageid_<?php echo $package['id'];?>">
                                                    <div style="float:left;"><button class="btn btn-sm btn-outline-danger" title="Remove this package from this part" onclick="deletePackage(<?php echo $package['id'];?>)">x</button></div>
                                                    <div style="float:left;background-color:#cd9f61;border:2px solid #808080;margin-left:4px;padding:2px;" style="font-size: 80%;"><?php echo $package['nicepackagehtml'];?></div>
                                                    <div style="clear:both;"></div></div><?php }
                                                ?>
                                            </div>
                                            <div onclick="showhideNewpackage()">...</div>
                                            <div id="newpackage" style="display: none; padding-top: 10px; text-align:left;">
                                                <div style="padding-top:3px;">Package UoM <select id="packageuom"><?php foreach($packageuoms as $packageuom){$selected=''; if($packageuom['code']=='EA'){$selected=' selected';} echo '<option value="'.$packageuom['code'].'"'.$selected.'>'.$packageuom['description'].'</option>';}?></select></div>
                                                <div style="padding-top:3px;">Package-Level GTIN <input type="text" id="packagelevelgtin" size="12"/></div>
                                                <div style="padding-top:3px;">Package Barcode <input type="text" id="packagebarcodecharacters" size="12"/></div>
                                                <div style="padding-top:3px;">Qty of Eaches <input type="text" id="quantityofeaches" size="2" value="1" style="text-align:right;"/></div>
                                                <div style="padding-top:3px;">Inner Qty <input type="text" id="innerquantity" size="2" value="1" style="text-align:right;"/> <select id="innerquantityuom"><?php foreach($innerqtyuoms as $innerqtyuom){$selected=''; if($innerqtyuom['code']=='EA'){$selected=' selected';} echo '<option value="'.$innerqtyuom['code'].'"'.$selected.'>'.$innerqtyuom['description'].'</option>';}?></select></div>
                                                <div style="padding-top:3px;">Weight <input type="text" id="weight" size="2" style="text-align:right;"/> <select id="weightsuom"> <?php foreach($weightsuoms as $weightsuom){echo '<option value="'.$weightsuom['code'].'">'.$weightsuom['description'].'</option>';}?></select></div>
                                                <div style="padding-top:3px;">Shipping LWH <input type="text" id="shippinglength" size="2" style="text-align:right;"/> <input type="text" id="shippingwidth" size="2" style="text-align:right;"/> <input type="text" id="shippingheight" size="2" style="text-align:right;"/>  Merch LWH <input type="text" id="merchandisinglength" size="2" style="text-align:right;"/> <input type="text" id="merchandisingwidth" size="2" style="text-align:right;"/> <input type="text" id="merchandisingheight" size="2" style="text-align:right;"/>  <select id="dimensionsuom"><?php foreach($dimensionsuoms as $dimensionsuom){echo '<option value="'.$dimensionsuom['code'].'">'.$dimensionsuom['description'].'</option>';}?></select></div>
                                                <div style="padding-top:3px;">Orderable <select id="orderable"><option value="Y">Yes</option><option value="N">No</option></select></div>
                                                <div style="padding-top:6px;"><button  class="btn btn-sm btn-success" id="addpackage" title="Add a package to this part" onclick="addPackage()">Add Package</button></div>
                                            </div>
                                        </td>
                                    <tr>
                                    <tr><th>Prices</th>
                                        <td>
                                            <div id="prices">
                                                <?php foreach($prices as $price){;?>
                                                <div style="padding-bottom:3px;" id="priceid_<?php echo $price['id'];?>">
                                                    <div style="float:left;"><button class="btn btn-sm btn-outline-danger" title="Remove this price from this part" onclick="deletePrice(<?php echo $price['id'];?>)">x</button></div>
                                                    <div style="float:left; background-color:#85bb65;border:2px solid #808080;margin-left:4px;padding:2px"><?php echo $price['niceprice'];?></div>
                                                    <div style="clear:both;"></div>
                                                </div>
                                                <?php }?>
                                            </div>
                                            <div onclick="showhideNewPrice()">...</div>
                                            <div id="newprice" style="display:none; text-align: left; padding-top: 10px;">
                                                <div style="padding-top:3px;"><div style="float:left;">Price Sheet Number <select id="pricesheetnumber" name="pricesheet" onchange="showSlectedPricesheetCurrency()"><option value="">select...</option><?php foreach($pricesheets as $pricesheet){echo '<option value="'.$pricesheet['number'].'">'.$pricesheet['description'].'</option>';}?></select></div><div style="float:left;padding-left: 5px;"><a href="./priceSheets.php"><img src="./settings.png" width="18" alt="settings"/></a></div><div style="clear:both;"></div> </div>
                                                <div style="padding-top:3px;">Unit of Measure <select id="priceuom" name="priceuom"><?php foreach($priceuoms as $priceuom){$selected =''; if($priceuom['code']=='PE'){$selected=' selected';} echo '<option value="'.$priceuom['code'].'"'.$selected.'>'.$priceuom['description'].'</option>';}?></select></div>
                                                <div style="padding-top:3px;"><div style="float:left;">Price Type: </div><div id="newpricetype" data-pricetype="" style="float:left;padding-top:1px;padding-right:5px;"></div><div style="clear:both;"></div> </div>
                                                <div style="padding-top:3px;"><div style="float:left;">Amount <input disabled style="text-align:right;" type="text" id="priceamount" size="4"/></div> <div id="newpricecurrency" data-currency="" style="float:left;padding-top:3px;padding-right:5px;"></div> <button class="btn btn-sm btn-success" title="Add a price to this part" id="addprice" disabled onclick="addPrice()">Add Price</button><div style="clear:both;"></div></div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr><th>EXPI Codes</th>
                                        <td>
                                            <div id="appliedexpis">
                                                <?php foreach($expis as $expi){;?>
                                                <div style="padding-bottom:3px;" id="expiid_<?php echo $expi['id'];?>">
                                                    <div style="float:left;"><button class="btn btn-sm btn-outline-danger" title="Remove <?php echo $expi['EXPIcode'];?> EXPI code from this part" onclick="deleteEXPI(<?php echo $expi['id'];?>)">x</button></div>
                                                    <div style="float:left; background-color:#f49ef7;border:2px solid #808080;margin-left:4px;padding:2px"><?php echo $pcdb->EXPIcodeDescription($expi['EXPIcode']).' ('.$expi['EXPIcode'].')';?>: <?php echo $expi['EXPIvalue'];?></div>
                                                    <div style="clear:both;"></div>
                                                </div>
                                                <?php }?>
                                                
                                            </div>
                                            <div onclick="showhideUnappliedEXPI()">...</div>
                                            <div id="unappliedexpis" style="display:none; padding:5px;">

                                                <?php foreach($validexpis as $validexpi){
                                                if($pim->partEXPIvalue($partnumber, $validexpi['code'], $defaultdescriptionlanguagecode, true)){continue;}
                                                echo '<div style="text-align:left;padding:3px;" id="unappliedexpi_'.$validexpi['code'].'">['. $validexpi['code'].'] '. $validexpi['description'] . ' <span><input size="8" id="unappliedexpivalue_'.$validexpi['code'].'"/>';
                                                echo '</span> <button class="btn btn-sm btn-success" title="Add EXPI code '.$validexpi['code'].'" onclick="addEXPI(\''.$validexpi['code'].'\')">+</button></div>';
                                                }?>

                                            </div>
                                        </td>
                                    </tr>


                                    <tr><th>Attributes</th>
                                        <td>
                                            <div id="appliedattributes" style="padding-bottom:5px;">
                                                <?php foreach ($attributes as $attribute) 
                                                {
                                                    if($attribute['PAID']==0)
                                                    { // user-defined attribute
                                                        if($attribute['inheritedfrom']=='')
                                                        { // this attribute is applied directly to this part (not inherited from a basepart)
                                                            echo '<div style="padding:3px;margin-bottom:2px" id="appliedattribute_'.$attribute['id'].'"><div style="float:left;"><button class="btn btn-sm btn-outline-danger" title="Remove this user-defined (non-PAdb) attribute" onclick="deleteAttribute('.$attribute['id'].','.$attribute['PAID'].',\''.$padb->PAIDname($attribute['PAID']).'\')">x</button></div><div style="border:1px solid;padding:3px; margin-left:3px; background:#dddddd;float:left;">'.$attribute['name'].' <span style="background-color:#f8f8f8;padding-left:4px;padding-right:4px;border-radius:2px;">'.$attribute['value'].' '.$attribute['uom'].'</span></div><div style="clear:both;"></div></div>';
                                                        }
                                                        else
                                                        {// this attribute is inherited from the basepart - indicate with an icon and disallow delete
                                                            echo '<div style="float:left;"><img src="./inheritance.png" width="20" title="Non-PAdb attribute inherited from '.$attribute['inheritedfrom'].'"/></div><div style="float:left;border:1px solid;padding:2px;margin-left:12px;;margin-bottom:3px;background:#dddddd;float:left;">'.$attribute['name'].' <span style="background-color:#f8f8f8;padding-left:4px;padding-right:4px;border-radius:2px;">'.$attribute['value'].' '.$attribute['uom'].'</span></div><div style="clear:both;"></div>';
                                                        }
                                                    }
                                                    else
                                                    {// PAdb-coded attribute
                                                        if($attribute['inheritedfrom']=='')
                                                        { // this attribute is applied directly to this part (not inherited from a basepart)
                                                            echo '<div style="padding:3px;margin-bottom:2px" id="appliedattribute_'.$attribute['id'].'"><div style="float:left;"><button class="btn btn-sm btn-outline-danger" title="Remove this PAdb ('.$attribute['PAID'].') attribute" onclick="deleteAttribute('.$attribute['id'].','.$attribute['PAID'].',\''.$padb->PAIDname($attribute['PAID']).'\')">x</button></div><div style="border:1px solid;padding:3px; margin-left:4px; background:#7ad0fe;float:left;">'.$padb->PAIDname($attribute['PAID']).' <span style="background-color:#f8f8f8;padding-left:4px;padding-right:4px;border-radius:2px;">'.$attribute['value'].' '.$attribute['uom'].'</span></div><div style="clear:both;"></div></div>';
                                                        }
                                                        else
                                                        {// this attribute is inherited from the basepart - indicate with an icon and disallow delete
                                                            
                                                            echo '<div style="float:left;"><img src="./inheritance.png" width="20" title="PAdb attribute '.$attribute['PAID'].' inherited from '.$attribute['inheritedfrom'].'"/></div><div style="float:left;border:1px solid;padding:2px; margin-left:12px;margin-bottom:3px;background:#7ad0fe;float:left;">'.$padb->PAIDname($attribute['PAID']).' <span style="background-color:#f8f8f8;padding-left:4px;padding-right:4px;border-radius:2px;">'.$attribute['value'].' '.$attribute['uom'].'</span></div><div style="clear:both;"></div>';
                                                            
                                                        }
                                                    }
                                                } ?>
                                            </div>
                                            <div onclick="showhideUnappliedAttributes()">...</div>
                                            <div id="unappliedattributes" style="display:none; padding:5px;">
                                                <?php foreach ($validpadbattributes as $attribute) { if($pim->getPartAttribute($part['partnumber'], $attribute['PAID'], '')){continue;}
                                                    $validoptions = $padb->getAttributeValidValues($part['parttypeid'], $attribute['PAID']);
                                                    $validoptionslist=''; if(count($validoptions)){$validoptionslist = '. Valid options: '.implode(', ', $validoptions);}
                                                    echo '<div style="text-align:left;padding:3px;" id="unappliedattribute_'.$attribute['PAID'].'">'. $attribute['name'] . ' <span><input size="8" id="unappliedattributevalue_'.$attribute['PAID'].'"/>';
                                                    if(count($attribute['uomlist']))
                                                    {
                                                        echo ' <select id="unappliedattributeuom_'.$attribute['PAID'].'">';
                                                        foreach($attribute['uomlist'] as $uom){echo '<option value="'.$uom.'">'.$uom.'</option>';}
                                                        echo '</select> ';
                                                    }
                                                    echo '</span> <button class="btn btn-sm btn-success" title="Add PAdb attribute '.$attribute['PAID'].$validoptionslist.'" onclick="addPAdbAttribute('.$attribute['PAID'].')">+</button></div>';
                                                }?>
                                                <div style="text-align:left;padding-top:20px;">User-Defined name <input size="5" id="nonpadbname"/> Value <input size="3" id="nonpadbvalue"/> UoM <input size="2" id="nonpadbuom"/> <button class="btn btn-sm btn-success" title="Add non-PAdb attribute" onclick="addNonPAdbAttribute()">+</button></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Assets</th>
                                        <td>
                                            <?php 
                                            foreach($connectedassets as $connectedasset)
                                            {
                                                $assettags=$asset->getAssettagsForAsset($connectedasset['assetid']);
                                                
                                                $assettagdivs='';
                                                
                                                foreach($assettags as $assettag)
                                                {
                                                    $assettagdivs.='<div style="float:left;background-color:#c0c0c0;padding-left:5px;padding-right:5px;margin-left:5px;margin-bottom:5px;border-radius:4px;font-size:.75em;">'.$assettag['tagtext'].'</div>';
                                                }
                                                
                                                if($connectedasset['inheritedfrom']=='')
                                                {// this asset is conneted directly to this part (not inherited from a basepart)
                                                    echo '<div style="padding-bottom:4px;" id="assetconnectionid_'.$connectedasset['connectionid'].'"><div style="float:left;"><button class="btn btn-sm btn-outline-danger" style="height:46px;" title="Disconnect this asset from this part" onclick="disconnectAsset(\''.$connectedasset['connectionid'].'\')">x</button></div><div style="border:1px solid;padding:3px;margin-left:4px;background:#7ad0fe;float:left;"><a class="btn btn-info" role="button" href="showAsset.php?assetid='.$connectedasset['assetid'].'">'.$connectedasset['assetid'].'</a> '.$pcdb->assetTypeCodeDescription($connectedasset['assettypecode']).'</div>'.$assettagdivs.'<div style="clear:both;"></div></div>';
                                                }
                                                else
                                                {// this asset is inherited from the basepart                                                    
                                                    echo '<div style="float:left;"><img src="./inheritance.png" width="20" title="Inherited from '.$connectedasset['inheritedfrom'].'"/></div><div style="border:1px solid;padding:4px;margin-left:4px;background:#7ad0fe;float:left;"><a class="btn btn-info" role="button" href="showAsset.php?assetid='.$connectedasset['assetid'].'">'.$connectedasset['assetid'].'</a> '.$pcdb->assetTypeCodeDescription($connectedasset['assettypecode']).' </div>'.$assettagdivs.'<div style="clear:both;"></div></div>';
                                                }
                                            }
                                            ?>

                                            <div id="showAssetFormIcon" style="display:block;" onclick="showhideAssetForm()"><img src="./expandmore.png" title="Expand to show assets form"/></div>
                                            <div  id="assetform" style="display:none; padding:25px;">
                                                <form action="assetExistingResourceForm.php" method="post">
                                                    <div>Create a new asset (from URI) connected to this part</div>
                                                    <div>URI <input type="text" name="uri" size="50"/></div>
                                                    <div>Filename <input type="text" size="25" name="basename"/><input type="hidden" name="partnumber" value="<?php echo $part['partnumber'];?>"/>
                                                    <input type="submit" name="submit" value="Retrieve"/></div>
                                                </form>
                                            <div id="hideAssetFormIcon" onclick="showhideAssetForm()"><img src="./expandless.png" title="Hide assets form"/></div>
                                            </div>
                                        </td>
                                    </tr>

                                    <?php if(count($kitcomponents)>0 || $bomchangescount>0){?>
                                        <tr><th>Kit Contains</th>
                                            <td>
                                            <?php
                                            foreach($kitcomponents as $kitcomponent)
                                            {
                                                $componentphotohtml='';
                                                if($componentphotouri=$asset->primaryPhotoURIofPart($kitcomponent['partnumber']))
                                                {
                                                    $componentphotohtml='<div style="float:left;padding:4px;"><img src="'.$componentphotouri.'" width="75"/></div>';
                                                }
                                                
                                                echo '<div style="float:left;padding:4px;"><div style="float:left;padding:4px;"><a href="showPart.php?partnumber='.$kitcomponent['partnumber'].'" class="btn btn-secondary">'.$kitcomponent['partnumber'].'</a></div>'.$componentphotohtml.'<div style="float:left;padding:4px;"> x '.number_format($kitcomponent['units']).'</div></div><div style="clear:both;"></div>';
                                            }
                                            
                                            if($bomchangescount>0)
                                            {
                                                echo '<a href="./partRelationHistory.php?partnumber='.$partnumber.'">History</a>';
                                            }
                                            ?>
                                            </td>
                                        <tr>
                                    <?php }?>

                                    <?php if(count($assemblies)>0){?>
                                        <tr><th>Used in kits</th>
                                            <td>
                                            <?php $usecount=0;
                                            foreach($assemblies as $assembly)
                                            {
                                                $usecount++;
                                                $assemblyphotohtml='';
                                                if($assemblyphotouri=$asset->primaryPhotoURIofPart($assembly['partnumber']))
                                                {
                                                    $assemblyphotohtml='<div style="float:left;padding:4px;"><img src="'.$assemblyphotouri.'" height="75"/></div>';
                                                }
                                                
                                                echo '<div style="float:left;padding:4px;"><div style="float:left;padding:4px;"><a href="showPart.php?partnumber='.$assembly['partnumber'].'" class="btn btn-secondary">'.$assembly['partnumber'].'</a></div>'.$assemblyphotohtml.'</div><div style="clear:both;"></div>';
                                                if($usecount>=40)
                                                {
                                                    echo '<div>--- list truncated ---</div>';
                                                    break;
                                                }
                                            }                                            
                                            ?>
                                            </td>
                                        <tr>
                                    <?php }?>

                                    <tr><th>Base Part</th><td><div style="float:left;"><input type="text" id="basepart" oninput="flagUnsavedBasepart();" value="<?php echo $part['basepart']?>"/></div><div style="float:left;"><button id="btnUpdateBasepart" class="btn btn-sm btn-outline-secondary" onclick="updatePart('<?php echo $partnumber;?>','text','basepart'); unflagUnsavedBasepart();">Update</button></div><div style="clear:both;"></div></td><tr>
                                    <?php if($vio){echo '<tr><th>VIO ('.$viogeography.' '.$vioyearquarter.')</th><td>'.number_format($vio,0,'.',',').'<br/>Min,Mean,Max: '.$viostartyear.', '.$viomeanyear.', '.$vioendyear.'</td><tr>';}?>
                                    <tr><th>Dates</th>
                                        <td>
                                            <div>Created in PIM: <?php echo $part['createdDate'];?></div>
                                            <?php 
                                            if($part['firststockedDate']!='0000-00-00'){echo '<div>First Stocked: '.$part['firststockedDate'].'</div>';}
                                            if($part['availableDate']!='0000-00-00' && $part['availableDate']!=''){echo '<div>Available: '.$part['availableDate'].'</div>';}
                                            if($part['supersededDate']!='0000-00-00' && $part['supersededDate']!=''){echo '<div>Superseded: '.$part['supersededDate'].'</div>';}
                                            if($part['discontinuedDate']!='0000-00-00' && $part['discontinuedDate']!=''){echo '<div>Discontinued: '.$part['discontinuedDate'].'</div>';}
                                            if($part['obsoletedDate']!='0000-00-00' && $part['obsoletedDate']!=''){echo '<div>Obsoleted: '.$part['obsoletedDate'].'</div>';}
                                            ?>
                                        </td>
                                    <tr>
                                    <tr><th>Health Score</th><td><div style="float:left;"></div><?php echo $pim->partHealthScore($part['partnumber']);?><div style="clear:both;"></div></td><tr>
                                    
                                    <tr><th>Sandpiper OID</th><td><div id="sandpiperoid"><?php echo $part['oid']; ?></div></td><tr>
                                </table>
                            </div>
                            <?php
                            } else {
                                echo 'Part '.$partnumber.' not found (<a href="./newPart.php?partnumber='.$partnumber.'">add it</a>)';
                            }
                            ?>
                        </div>
                    </div>                    
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-3 my-col colRight">
                    <div class="card shadow-sm">
                        <h4 class="card-header text-start">
                            <?php
                            if(count($apps) && $apps[0]['inheritedfrom']!='')
                            {
                                echo '<img src="./inheritance.png" width="20" title="Inherited from '.$apps[0]['inheritedfrom'].'"/>';
                            }
                            
                            echo 'Applications <span class="badge bg-primary rounded-pill">'. count($apps) .'</span>';

                            echo '<div style="font-size:50%;padding:5px;">'.$appsummarystruct['summary'].'</div>'
                            
                            ?>
                        </h4>

                        <div class="card-body d-flex flex-column scroll">
                            <?php 
                            if(count($apps))
                            {// part has apps
                                echo '<div style="float:right;"><span class="btn btn-info" onclick="addAppsToClipboard()">Copy</span></div><div style="clear:both;"></div>';
                            }
                            else
                            {// part has no apps                                
                                if($pim->clipboardHasAppsFromSinglePart($_SESSION['userid']))
                                {
                                    echo '<div style="float:right;"><span class="btn btn-info" onclick="addClipboardAppsToPart()">Paste</span></div><div style="clear:both;"></div>';
                                }
                            }
                            
                            echo '<div id="apps">';
                            
                            $niceapps=array(); $makesindex=array(); $modelsindex=array(); $yearsindex=array();
                            foreach($apps as $rowid=>$app)
                            {
                             $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
                             $makesindex[$rowid]=$mmy['makename'];
                             $modelsindex[$rowid]=$mmy['modelname'];
                             $yearsindex[$rowid]=$mmy['year'];
                             
                             $niceattributes='';
                             if($showAppAttributesInSummary=='yes'){ $niceattributes=' '.niceAppAttributes($app['attributes']);}
                             $niceappdescription=$vcdb->niceMMYofBasevid($app['basevehicleid']).' '.$niceattributes;
                             
                             $niceapps[$rowid]=array('id'=>$app['id'],'niceappdescription'=>$niceappdescription,'makename'=>$mmy['makename'],'modelname'=>$mmy['modelname'],'year'=>$mmy['year']);
                            }

                            array_multisort($makesindex,SORT_ASC,$modelsindex,SORT_ASC,$yearsindex,SORT_DESC,$niceapps);
                            foreach($niceapps as $app)
                            {
                                echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="showApp.php?appid=' . $app['id'] . '">'.$app['niceappdescription'].'</a>';
                                echo '<div style="display:none;" data-appid="'.$app['id'].'" data-description-app="'. base64_encode($app['niceappdescription']).'">'.$app['id'].'</div>';
                            }
                            
                            echo '</div>';
 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
             <?php //print_r($expis);?>   
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>