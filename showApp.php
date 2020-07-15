<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/qdbClass.php');
$navCategory = 'applications';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;
$pcdb=new pcdb;
$pim=new pim;
$qdb=new qdb;
$userid=$_SESSION['userid'];

$appid=intval($_GET['appid']);

if(isset($_POST))
{
 if(isset($_POST['submit']) && $_POST['submit']=='Undelete')
 {
  $pim->setAppStatus($appid,0);
  $pim->logAppEvent($appid,$userid,'app was un-deleted','');
 }
 if(isset($_POST['submit']) && $_POST['submit']=='Unhide')
 {
  $pim->setAppStatus($appid,0);
  $pim->logAppEvent($appid,$userid,'app was un-hidden','');
 }
 if(isset($_POST['submit']) && $_POST['submit']=='Delete')
 {
  $pim->setAppStatus($appid,1);
  $pim->logAppEvent($appid,$userid,'app was deleted','');
 }
 if(isset($_POST['submit']) && $_POST['submit']=='Hide')
 {
  $pim->setAppStatus($appid,2);
  $pim->logAppEvent($appid,$userid,'app was hidden','');
 }


 if(isset($_POST['submit']) && $_POST['submit']=='Add Attribute')
 {
  $bits=explode('_',$_POST['vcdbattribute']);
  if(count($bits)==2 && intval($bits[1])>0)
  {
   $vcdbattributename=$bits[0]; $vcdbattributevalue=intval($bits[1]); $cosmetic=0;
   $topsequence=$pim->highestAppAttributeSequence($appid);
   $pim->addVCdbAttributeToApp($appid,$vcdbattributename,$vcdbattributevalue,$topsequence+1,$cosmetic);
   $pim->cleansequenceAppAttributes($appid);
   $pim->logAppEvent($appid,$userid,'VCdb attribute added '.$vcdbattributename.'='.$vcdbattributevalue,'');
  }
 }

 if(isset($_POST['submit']) && $_POST['submit']=='Add Note')
 {
  if(strlen(trim($_POST['note']))>0)
  {
   $cosmetic=0;
   $topsequence=$pim->highestAppAttributeSequence($appid);
   $pim->addNoteAttributeToApp($appid,trim($_POST['note']),$topsequence,$cosmetic);
   $pim->cleansequenceAppAttributes($appid);
   $pim->logAppEvent($appid,$userid,'Fitment note added: '.trim($_POST['note']),'');
  }
 }

 foreach($_POST as $post_key=>$post_value)
 {
  if(strstr($post_key,'cosmetic_'))
  { // toggle cosmeticness for a specific app attribute
   $bits=explode('_',$post_key);
   $pim->toggleAppAttributeCosmetic($appid,$bits[1]);
   $pim->cleansequenceAppAttributes($appid);
  }

  if(strstr($post_key,'sequenceup_'))
  { // increase the sequence value for a specific app attribute to change its position relative to its peers
   $bits=explode('_',$post_key);
   $pim->incAppAttributeSequence($appid,$bits[1]);
  }

  if(strstr($post_key,'remove_'))
  { // delete a specific app attribute to change its position relative to its peers
   $bits=explode('_',$post_key);
   $pim->deleteAppAttribute($appid,$bits[1]);
   $pim->cleansequenceAppAttributes($appid);
  }
 }

}


$app=$pim->getApp($appid);
//print_r($app);

$appcolor='#d0f0c0'; if($app['cosmetic']>0){$appcolor='#33FFD7';} if($app['status']>1){$appcolor='#FFD433';} if($app['status']==1){$appcolor='#FF5533';}

$attributecolors=array('vcdb'=>'52BE80','qdb'=>'6060F0','note'=>'C0C0C0');
$niceattributes=array();
foreach($app['attributes'] as $appattribute)
{
 if($appattribute['type']=='vcdb'){$niceattributes[]=array('sequence'=>$appattribute['sequence'],'text'=>$vcdb->niceVCdbAttributePair($appattribute),'cosmetic'=>$appattribute['cosmetic'],'type'=>$appattribute['type'],'id'=>$appattribute['id']);}
 if($appattribute['type']=='qdb'){$niceattributes[]=array('sequence'=>$appattribute['sequence'],'text'=>$qdb->qualifierText(intval($appattribute['name']), explode('~', str_replace('|', '', $appattribute['value']))),'cosmetic'=>$appattribute['cosmetic'],'type'=>$appattribute['type'],'id'=>$appattribute['id']);}
 if($appattribute['type']=='note'){$niceattributes[]=array('sequence'=>$appattribute['sequence'],'text'=>$appattribute['value'],'cosmetic'=>$appattribute['cosmetic'],'type'=>$appattribute['type'],'id'=>$appattribute['id']);}
 
}

$nicefitmentarray=array(); foreach($niceattributes as $niceattribute){$nicefitmentarray[]=$niceattribute['text'];}
$nicefitmentstring=implode('; ',$nicefitmentarray);

$allattributes=$vcdb->getACESattributesForBasevehicle($app['basevehicleid']); //print_r($allattributes);

$assets=array();
$assets_linked_to_item=array();
$favoriteparttypes=$pim->getFavoriteParttypes();
$favoritepositions=$pim->getFavoritePositions();
$mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
$pcdbversion=$pcdb->version();
$historylimit=10;
$history=$pim->getAppEvents($appid,$historylimit);


$selectedcategories=array(); $selectedcategoriesurlvars=array();
if(isset($_GET['categories']))
{
    $categoryparts=explode(',',urldecode($_GET['categories']));
    foreach($categoryparts as $categorypart)
    {
        if(intval($categorypart)>0)
        {
            $selectedcategories[]=intval($categorypart);
            $selectedcategoriesurlvars[]='partcategory_'.intval($categorypart).'=on';
        }
    }
}


?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
        <script>
            function updateApp(appid,elementtype,elementid)
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
             xhr.open('GET', 'ajaxUpdateApp.php?appid='+appid+'&elementid='+elementid+'&value='+encodeURI(value));
             xhr.onload = function()
             {
              var response=xhr.responseText;
              document.getElementById("sandpiperoid").innerHTML=response;
              setStatusColor(); // get app's color based on status and cosmetic
             };
             xhr.send();
            }
            
            function setStatusColor()
            {
               
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxGetApp.php?appid='+<?php echo $appid;?>);
             xhr.onload = function()
             {
              var app=JSON.parse(xhr.responseText);
              var statusClassName="appstatus-active";
              
              if(app.status==1){statusClassName="appstatus-deleted";}
              if(app.status==2){statusClassName="appstatus-hidden";}
              
              document.getElementById("label-status").className=statusClassName;
              document.getElementById("value-status").className=statusClassName;

              var cosmeticClassName="appcosmetic-noncosmetic";
              var cosmeticText="";
              if(app.cosmetic==1){cosmeticClassName="appcosmetic-cosmetic"; cosmeticText="App is cosmetic";}
              
              document.getElementById("label-cosmetic").className=cosmeticClassName;
              document.getElementById("value-cosmetic").className=cosmeticClassName;
              document.getElementById("cosmetic-text").innerHTML=cosmeticText;

             };
             xhr.send();
            }


            function addVCdbAttribute()
            {
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddAppAttribute.php?type=vcdb&name=SubModel&value=2&cosmetic=1&appid='+<?php echo $appid;?>);
             xhr.onload = function()
             {
              var result=JSON.parse(xhr.responseText);
              //console.log(result);
             };
             xhr.send();
            }
            
            function addQdb()
            {
             var qdbid=document.getElementById("qdbpreview").getAttribute("data-qdbid");
             var qdbparmsString=document.getElementById("qdbpreview").getAttribute("data-qdbparmstring");
             var xhr = new XMLHttpRequest();
             
             xhr.open('GET', 'ajaxAddAppAttribute.php?type=qdb&name='+qdbid+'&value='+encodeURIComponent(qdbparmsString)+'&cosmetic=0&appid='+<?php echo $appid;?>);
             xhr.onload = function()
             {
              var result=JSON.parse(xhr.responseText);
              //console.log(result);
             };
             xhr.send();
            }
            
            
            

            function searchQdb()
            {
             document.getElementById("qdbresultscount").innerHTML="Searching Qdb...";
             document.getElementById("qdbresults").innerHTML = "";
             var searchterm=document.getElementById("qdbsearchterm").value;
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxSearchQdb.php?type=1&searchterm='+encodeURIComponent(searchterm));
             xhr.onload = function()
             {
              var results=JSON.parse(xhr.responseText);
              document.getElementById("qdbresultscount").innerHTML= results.length+" results found";
              for(var k in results) 
              {
                var newOption = new Option(results[k].qualifiertext,results[k].qualifierid);
                document.getElementById("qdbresults").add(newOption,undefined);
              }
              
             };
             xhr.send();
            }
            
            function selectQdb()
            {
             var p;
             for(p=1; p<=8; p++)
             {
              document.getElementById('qdbparm'+p+'block').style.display='none';
              document.getElementById('qdbparm'+p+'uomblock').style.display='none';
              document.getElementById('qdbparm'+p+'value').value='';
              document.getElementById('qdbparm'+p+'uom').value='';

             }
             
             document.getElementById('qdbpreview').innerHTML='';

             var resultSelect = document.getElementById("qdbresults");
             var selectedQdbText=resultSelect.options[resultSelect.selectedIndex].text;
             var selectedQdbID=resultSelect.options[resultSelect.selectedIndex].value;
             
             // identify embeded parameters in the Qdb String
             var n = -1;
             var offset=0;
             var parmType='';
             var p;
             for(p=1; p<=8; p++)
             {
              n=selectedQdbText.indexOf(' type="',offset);

              if(n > -1)
              {// found a parm
               parmTypeEnd=selectedQdbText.indexOf('"',n+8);
               
               if(parmTypeEnd > -1)
               {// found an ending "
                parmType=selectedQdbText.substring(n+7,parmTypeEnd);
                document.getElementById('qdbparm'+p+'title').innerHTML='Parameter '+p+' ('+parmType+') ';
                document.getElementById('qdbparm'+p+'block').style.display='block';
                
                if(parmType=='size' || parmType=='weight')
                {
                 document.getElementById('qdbparm'+p+'uomblock').style.display='block';
                }
               }
               offset=n+1;
              }
              else
              {// no more parms found
               break;   
              }
             }

//  var str = '<p1 type="size"/> Bolt, <p2 type="size"/> Thick x <p3 type="size"/> Long x <p4 type="size"/> Wide'; 

             showQdbPreview();
            }

            function showQdbPreview()
            {
             var i;
             var resultSelect = document.getElementById("qdbresults");
             var selectedQdbText=resultSelect.options[resultSelect.selectedIndex].text;
             var selectedQdbID=resultSelect.options[resultSelect.selectedIndex].value;
             var parmsString='';

             var parms=['-']; // element 0 is filled with trash to allow the element numbers to align with the "p" mumbers



             for(i=1; i<=8; i++)
             {
              parms.push(document.getElementById('qdbparm'+i+'value').value + document.getElementById('qdbparm'+i+'uom').value);

                                 //value like "123, ABC, XYZ|~12|mm~4|mm"
              if(document.getElementById('qdbparm'+i+'value').value !='')
              {
               parmsString+=document.getElementById('qdbparm'+i+'value').value+'|'+document.getElementById('qdbparm'+i+'uom').value+'~';
              }
             }

             document.getElementById("qdbpreview").setAttribute("data-qdbid",selectedQdbID);
             document.getElementById("qdbpreview").setAttribute("data-qdbparmstring",parmsString);


             var previewText=applyQdbParmsToString(selectedQdbText,parms);
             document.getElementById('qdbpreview').innerHTML=previewText;
            }

            function applyQdbParmsToString(text,parms)
            {
             var result=text;
             var startpos=-1;
             var parmType='';
             var i=0;
              
             for(i=1; i<=8; i++)
             {
              startpos=result.indexOf('<p'+i+' type="');
              if(startpos > -1)
              {
               parmTypeEnd=result.indexOf('"',startpos+10);
               if(parmTypeEnd > -1)
               {// found an ending "
                parmType=result.substring(startpos+10,parmTypeEnd);
                if(parms[i]!='')
                { // 
                 result=result.replace('<p'+i+' type="'+parmType+'"/>',parms[i]);
                }
               }                  
              }
             }
             return result;
            }


            function showhideForm(e)
            {
             var x = document.getElementById(e);
             if (x.style.display === "none") 
             {
              x.style.display = "block";
             }
             else
             {
              x.style.display = "none";
             }
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
                <?php if($app) {;?>
                <div style="padding:10px;">

                 <?php
                  if($pcdb->parttypeName($app['parttypeid'])=='not found'){echo '<div style="color:red;">Part Type id '.$app['parttypeid'].' is not found in the loaded ('.$pcdbversion.') PCdb</div>';}
                  if($pcdb->positionName($app['positionid'])=='not found'){echo '<div style="color:red;">Position id '.$app['positionid'].' is not found in the loaded ('.$pcdbversion.') PCdb</div>';}
                 ?>

                 <table border="1" cellpadding="5">
                  <tr><th><a href="./showAppsByBasevehicle.php?<?php echo implode('&',$selectedcategoriesurlvars);?>&makeid=<?php echo $mmy['MakeID'];?>&modelid=<?php echo $mmy['ModelID'];?>&yearid=<?php echo $mmy['year'];?>&submit=Show+Applications">Base Vehicle</a></th><td align="left"><?php echo '<a href="appsIndex.php">'.$vcdb->makeName($mmy['MakeID']).'</a>  <a href="mmySelectModel.php?makeid='.$mmy['MakeID'].'">'.$vcdb->modelName($mmy['ModelID']).'</a> <a href="mmySelectYear.php?makeid='.$mmy['MakeID'].'&modelid='.$mmy['ModelID'].'">'.$mmy['year'].'</a>';?></td></tr>
                  <tr><th>Part</th><td align="left"><a href="showPart.php?partnumber=<?php echo $app['partnumber'];?>"><?php echo $app['partnumber'];?></a></td></tr>
                  <tr><th>Application<br/>Part Type</th><td align="right"><select id="parttypeid" onchange="if (this.selectedIndex) updateApp(<?php echo $appid;?>,'select','parttypeid');"><option value="0">Undefined</option><?php foreach($favoriteparttypes as $parttype){?> <option value="<?php echo $parttype['id'];?>"<?php if($parttype['id']==$app['parttypeid']){echo ' selected';}?>><?php echo $parttype['name'];?></option><?php }?></select></td></tr>
                  <tr><th>Position</th><td align="right"><select id="positionid"  onchange="if (this.selectedIndex) updateApp(<?php echo $appid;?>,'select','positionid');"><option value="0">Undefined</option><?php foreach($favoritepositions as $position){?> <option value="<?php echo $position['id'];?>"<?php if($position['id']==$app['positionid']){echo ' selected';}?>><?php echo $position['name'];?></option><?php }?></select></td></tr>
                  <tr><th>Fitment<br/>Qualifiers</th>
                   <td align="left">
                    <form method="post" action="showApp.php?appid=<?php echo $appid;?>">
                     <?php foreach($niceattributes as $niceattribute){$text_decoration=''; if($niceattribute['cosmetic']==1){$text_decoration='text-decoration: line-through;';} echo '<div style="border:solid 1px;margin:5px;'.$text_decoration.'padding:2px;background-color:#'.$attributecolors[$niceattribute['type']].';">'.$nicefitmentarray[]=$niceattribute['text'].' <input type="submit" name="cosmetic_'.$niceattribute['id'].'" value="Cosmetic"/><input type="submit" name="sequenceup_'.$niceattribute['id'].'" value="Down"/><input type="submit" name="remove_'.$niceattribute['id'].'" value="Remove"/></div>';} ?>
                    </form>

                     <div onclick="showhideForm('newvcdbattributeform')">VCdb Attribute ...</div>
                       
                     <div id="newvcdbattributeform" style="display:none;padding: 40px;">
                      <select id="vcdbattribute" name="vcdbattribute"><option value="">-- Select a VCdb Attribute --</option> <?php foreach($allattributes as $attributekey=>$allattributename){echo '<option value="'.$attributekey.'">'.$allattributename.'</option>';}?> </select>
                      <button onclick="addVCdb()">Add VCdb Attribute</button>
                     </div>

                     <div onclick="showhideForm('newnoteform')">Free-form fitment note ...</div>
                       
                     <div id="newnoteform" style="display:none;padding: 40px;">
                      <input type="text" id="fitmentnote"/><button onclick="addNote()">Add Fitment Note</button>
                     </div>

                       
                     <div onclick="showhideForm('newqdbattributeform')">Qdb Qualifier ...</div>

                     <div id="newqdbattributeform" style="display:none;padding:40px;">
                      <div style="padding:3px;">
                       <div style="float:left;"><input type="text" id="qdbsearchterm"/></div>
                       <div style="float:left;"><button id="mybutton" onclick="searchQdb()">Search</button></div>
                       <div style="clear:both;"></div>
                      </div>
                      <div style="padding:3px;font-size: 75%; color: #aaaaaa;" id="qdbresultscount"></div>
                      <select id="qdbresults" multiple onchange="selectQdb()"></select>

                      <?php for($i=1; $i<=8;$i++){?>
                      <div id="qdbparm<?php echo $i;?>block" style="padding:3px; display:none;">
                       <div id="qdbparm<?php echo $i;?>title" style="float:left;"></div>
                       <div style="float:left;">
                        <input size="8" type="text" id="qdbparm<?php echo $i;?>value" onkeyup="showQdbPreview()"/>
                       </div>
                       <div id="qdbparm<?php echo $i;?>uomblock" style="float:left;padding-left:10px; display:none;">UoM
                        <input type="text" id="qdbparm<?php echo $i;?>uom" size="2" onkeyup="showQdbPreview()"/>
                       </div>
                       <div style="clear:both;"></div>
                      </div>
                      <?php }?>

                      <div id="qdbpreview" data-qdbid="" data-qdbparmstring="" style="background-color: #f0f0f0; border: solid 1px black; padding:3px;margin: 20px;"></div>
                      <div style="padding: 5px;"><button id="addQdb" onclick="addQdb()">Add Qdb Qualifier</button></div>
                     </div>
                       
                    </td>
                   </tr>
                   <tr><th>Quantity<br/>(on this vehicle)</th><td align="right"><input id="quantityperapp" type="text" name="quantityperapp" size="1" value="<?php echo $app['quantityperapp'];?>"/><button onclick='updateApp(<?php echo $appid;?>,"text","quantityperapp");'>Update Qty</button></td></tr>
                   <tr><th>Fitment<br/>Assets</th><td align="right"><?php if(count($assets)){foreach($assets as $asset) { echo '<div><a title="view this asset in new browser window" href="'.$asset['uri'].'" target="_blank">'.$asset['assetId'].'</a> (representation:'.$asset['representation'].', sequence: '.$asset['assetItemOrder'].') <a title="remove this asset from the application" href="./showAdminApplication.php?id='.intval($_GET['id']).'&removeasset='.$asset['id'].'">x</a><div>'; }}?>
                   <div>Asset <select name="assetid"><option value=""></option>
                   <?php
                   foreach($assets_linked_to_item as $asset)
                   {
                    $already_applied=false; foreach($assets as $tempasset){if($tempasset['assetId']==$asset['assetId']){$already_applied=true;}} if($already_applied){continue;}
                    echo '<option value="'.$asset['assetId'].'">'.$asset['fileType'].' - '.$asset['filename'].' ('.$asset['description'].')</option>';
                   }?>
                    </select>
                    Sequence <input type="text" name="assetorder" size="3" value="1"/>
                    <select name="representation"><option value="A">Actual</option><option value="R">Representative</option></select>
                   </div></td></tr>
                   <tr><th>Internal<br/>Notes</th><td><textarea id="internalnotes" cols="60" rows="5"><?php echo $app['internalnotes'];?></textarea><div><button onclick='updateApp(<?php echo $appid;?>,"text","internalnotes");'>Save</button></div></td><tr>
                   <tr><th>IDs</th><td><div style="float:left;">Application ID:</div><div style="float:left;"><?php echo $app['id'];?></div><div style="clear:both;"></div><div style="float:left;">Sandpiper OID:</div><div style="float:left;" id="sandpiperoid"><?php echo $app['oid'];?></div><div style="clear:both;"></div><div style="float:left;">BaseVehicle ID:</div><div style="float:left;"><?php echo $app['basevehicleid'];?></div><div style="clear:both;"></div></td><tr>
                   <tr><th id="label-cosmetic" class="appcosmetic-noncosmetic">Cosmetic</th><td id="value-cosmetic" class="appcosmetic-noncosmetic" align="right"><div id="cosmetic-text"></div> <button onclick='updateApp(<?php echo $appid;?>,"button","cosmetic");'>Cosmetic</button></td></tr>
                   <tr><th id="label-status" class="apppart-active">Status</th><td id="value-status" class="apppart-active" align="right"><select id="status" onchange="updateApp(<?php echo $appid;?>,'select','status');"><option value="0">Active</option><option value="1"<?php if($app['status']==1){echo ' selected';}?>>Deleted</option><option value="2"<?php if($app['status']==2){echo ' selected';}?>>Hidden</option></select></td></tr>
                 </table>
                </div>
                <?php if(count($history)){echo '<div><a href="./appHistory.php?appid='.$appid.'">History</a></div>';}?>
                <?php }
                else
                { // no apps found
                 echo 'Application not found';
                }?>
            </div> <!-- End Main Content -->

            <div class="contentRight">

                <button onclick="myFunction()"><img src="./down.png" width="20"></button>

            </div>
        </div> <!-- End Wrapper -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>