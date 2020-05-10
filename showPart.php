<?php
include_once('./class/vcdbClass.php');
include_once('./class/padbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/pricingClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/packagingClass.php');
include_once('./class/logsClass.php');
$navCategory = 'parts';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;
$padb = new padb;
$pcdb = new pcdb;
$pim = new pim;
$asset = new asset;
$pricing = new pricing;
$interchange = new interchange;
$packaging = new packaging;
$logs=new logs;

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
$prices=$pricing->getPricesByPartnumber($partnumber);
$competitors=$interchange->getInterchangeByPartnumber($partnumber);
$packages=$packaging->getPackagesByPartnumber($partnumber);
$favoriteparttypes=$pim->getFavoriteParttypes();
$lifecyclestatuses=$pcdb->getLifeCycleCodes();
$history=$logs->getPartsEvents(50);

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
             xhr.open('GET', 'ajaxUpdatePart.php?partnumber='+partnumber+'&elementid='+elementid+'&value='+encodeURI(value));
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
            
            function addPAdbAttribute(PAID)
            {
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdateAttributeOfPart.php?partnumber=<?php echo $partnumber;?>&attribute='+PAID+'&value=&uom=');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              if(response.success)
              { //add attribute to "applied" list
               var container=document.getElementById('appliedattributes');
               container.innerHTML+='<div id="appliedattribute_'+response.id+'"><div style="width:2em;float:left;"><button onclick="deleteAttribute('+response.id+','+response.PAID+',\''+response.name+'\')">-</button></div><div style="border:1px solid;padding:1px; margin-bottom:1px; background:#dddddd;float:left;">'+response.name+'<input type="text"/></div><div style="clear:both;"></div></div>';
               
               //remove PAdb form "unapplied" list
               var unappliedattributediv = document.getElementById('unappliedattribute_'+PAID);
               unappliedattributediv.parentNode.removeChild(unappliedattributediv);
               
               // show new oid
               document.getElementById("sandpiperoid").innerHTML=response.oid;
              }
             };
             xhr.send();
            }

            function updatePAdbAttribute(PAID)
            {
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxUpdateAttributeOfPart.php?partnumber=<?php echo $partnumber;?>&attribute='+PAID+'&value=&uom=');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              if(response.success)
              {
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
              container.innerHTML+='<div id="unappliedattribute_'+PAID+'"><div style="width:2em;float:left;"><button onclick="addPAdbAttribute('+PAID+')">+</button></div><div style="float:left;">'+name+'</div><div style="clear:both;"></div></div>';

              document.getElementById("sandpiperoid").innerHTML=response.oid;
             };
             xhr.send();
            }


        </script>
        
    </head>
    <body onload="setStatusColor()">
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1></h1>
        
        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <?php if ($part) {; ?>
                <div style="padding:10px;">
                    <table border="1" cellpadding="5">
                        <tr><th>Partnumber</th><td><?php echo $part['partnumber']; ?></td></tr>
                        <tr><th>Part Type</th><td><select id="parttypeid" onchange="if (this.selectedIndex) updatePart('<?php echo $partnumber;?>','select','parttypeid');"><option value="0">Undefined</option><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"<?php if($parttype['id']==$part['parttypeid']){echo ' selected';}?>><?php echo $parttype['name'];?></option><?php }?></select></td></tr>
                        <tr><th>Category</th><td><select id="partcategory" onchange="if (this.selectedIndex) updatePart('<?php echo $partnumber;?>','select','partcategory');"><option value="0">Undefined</option> <?php foreach ($partcategories as $partcategory) { ?> <option value="<?php echo $partcategory['id']; ?>"<?php if ($partcategory['id'] == $part['partcategory']) {echo ' selected';} ?>><?php echo $partcategory['name']; ?></option><?php } ?></select></td></tr>
                        <tr><th>Descriptions</th><td><input type="text" id="description" value="<?php echo $part['description']?>"/><div><button onclick="updatePart('<?php echo $partnumber;?>','text','description');">Update</button></div></td><tr>
                        <tr><th>GTIN</th><td><input type="text" id="gtin" value="<?php echo $part['GTIN']?>"/><div><button onclick="updatePart('<?php echo $partnumber;?>','text','gtin');">Update</button></div></td><tr>
                        <tr><th>UNSPC</th><td><input type="text" id="unspc" value="<?php echo $part['UNSPC']?>"/><div><button onclick="updatePart('<?php echo $partnumber;?>','text','unspc');">Update</button></div></td><tr>
                        <tr><th>Internal<br/>Notes</th><td><textarea  id="internalnotes"  cols="50"><?php echo $part['internalnotes']?></textarea><div><button onclick="updatePart('<?php echo $partnumber;?>','text','internalnotes');">Update</button></div></td><tr>
                        <tr><th>Competitor<br/>Interchange</th></th><td><?php foreach($competitors as $competitor){;?><div><?php echo $competitor['brandAAIAID'].': '.$competitor['competitorpartnumber'];?></div><?php }?></td><tr>
                        <tr><th>Packages</th><td><?php foreach($packages as $package){;?><div><?php echo $package['weight'].' '.$package['weightsuom'];?></div><?php }?></td><tr>
                        <tr><th>Prices</th><td><?php foreach($prices as $price){;?><div><?php echo $price['pricetype'].': '.$price['amount'];?></div><?php }?></td><tr>
                        <tr><th>Attributes</th>
                            <td>
                                <div id="appliedattributes" style="padding:5px;">
                                    <?php foreach ($attributes as $attribute) 
                                    {
                                        if($attribute['PAID']==0)
                                        {
                                            //echo '<tr><td>' . $attribute['name'] . '</td><td>' . $attribute['value'] . '</td><td>' . $attribute['id'] . '</td></tr>';
                                        }
                                        else
                                        {
                                            echo '<div id="appliedattribute_'.$attribute['id'].'"><div style="width:2em;float:left;"><button onclick="deleteAttribute('.$attribute['id'].','.$attribute['PAID'].',\''.$padb->PAIDname($attribute['PAID']).'\')">-</button></div><div style="border:1px solid;padding:1px; margin-bottom:1px; background:#dddddd;float:left;">'.$padb->PAIDname($attribute['PAID']).'<input type="text"/></div><div style="clear:both;"></div></div>';
                                        }
                                    } ?>
                                </div>

                                <div id="unappliedattributes" style="padding:5px;">
                                        <?php foreach ($validpadbattributes as $attribute) { if($pim->getPartAttribute($part['partnumber'], $attribute['PAID'], '')){continue;}
                                            echo '<div id="unappliedattribute_'.$attribute['PAID'].'"><div style="width:2em;    float:left;"> <button onclick="addPAdbAttribute('.$attribute['PAID'].')">+</button></div><div style="float:left;">' . $attribute['name'] . '</div><div style="clear:both;"></div></div>';
                                        }?>
                                </div>
                            </td>
                        </tr>
                        <tr><th>Connected Assets</th><td><?php if($connectedassets){foreach($connectedassets as $connectedasset){echo '<a class="button" href="showAsset.php?assetid='.$connectedasset['assetid'].'">'.$connectedasset['assetid'].'</a> ';}};?></td><tr>
                        <tr><th>Sandpiper OID</th><td><div id="sandpiperoid"><?php echo $part['oid']; ?></div></td><tr>
                        <tr><th id="label-status" class="partstatus-available">Status</th><td id="value-status" class="partstatus-available"><select id="lifecyclestatus" onchange="updatePart('<?php echo $partnumber;?>','select','lifecyclestatus');"><?php foreach($lifecyclestatuses as $lifecyclestatus){?> <option value="<?php echo $lifecyclestatus['code'];?>"<?php if($lifecyclestatus['code']==$part['lifecyclestatus']){echo ' selected';}?>><?php echo $lifecyclestatus['description'];?></option><?php }?></select></td><tr/>
                    </table>
                </div>
                <?php if(count($history)){echo '<div><a href="./partHistory.php?partnumber='.$partnumber.'">History</a></div>';}?>
                <?php
                } else {
                    echo 'Part ('.$partnumber.') not found';
                }
                ?>
            </div>
            <div class="contentRight">
                <h3 class="mobile">Applications</h3>
                <div class="scrolling-wrapper-flexbox">
                <?php foreach ($apps as $app) {
                    echo '<div style="padding:.2em;" class="button card"><a href="showApp.php?appid=' . $app['id'] . '">' . $vcdb->niceMMYofBasevid($app['basevehicleid']) . ' ' . niceAppAttributes($app['attributes']) . '</a></div>';} 
                ?>
                </div>
            </div>
        </div>
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>