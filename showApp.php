<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/qdbClass.php');
include_once('./class/assetClass.php');
include_once('./class/configGetClass.php');

$navCategory = 'applications';

$pim=new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'showApp.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;
$pcdb=new pcdb;
$qdb=new qdb;
$asset=new asset();
$configGet=new configGet();
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
   $pim->addVCdbAttributeToApp($appid,$vcdbattributename,$vcdbattributevalue,$topsequence+1,$cosmetic,true);
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

$appcolor='#d0f0c0';
if($app['cosmetic']>0){$appcolor='#e39aea';}
if($app['status']>1){$appcolor='#FFD433';}
if($app['status']==1){$appcolor='#FF5533';}

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

$allattributes=$vcdb->getACESattributesForBasevehicle($app['basevehicleid'],false); //print_r($allattributes);

//print_r($allattributes);


$appassets=$pim->getAppAssets($appid);
$partassets=$asset->getAssetsConnectedToPart($app['partnumber']);
$favoriteparttypes=$pim->getFavoriteParttypes();
$favoritepositions=$pim->getFavoritePositions();
$mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
$makename=$vcdb->makeName($mmy['MakeID']);
$modelname=$vcdb->modelName($mmy['ModelID']);
$year=$mmy['year'];
$pcdbversion=$pcdb->version();
$historylimit=10;
$history=$pim->getAppEvents($appid,$historylimit);

$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');
$vio=$pim->appVIOexperian($appid, $viogeography, $vioyearquarter, $app['attributes']);

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

            function renderFitments()
            {
             var container=document.getElementById('fitment');
             container.innerHTML='';
       
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxGetAppAttributes.php?appid='+<?php echo $appid;?>);
             xhr.onload = function()
             {
              var attributes=JSON.parse(xhr.responseText);
              var i;
              for (i = 0; i < attributes.length; i++) 
              {
               var id = attributes[i].id;
               var nicetext = attributes[i].nicetext;
               
               var decoration='';
               if(attributes[i].cosmetic==1){decoration='text-decoration:line-through;';}

               var color='ffffff';
               if(attributes[i].type=='vcdb'){color='52BE80';}
               if(attributes[i].type=='qdb'){color='6060F0';}
               if(attributes[i].type=='note'){color='C0C0C0';}
               
               container.innerHTML+='<div id="attribute_'+id+'" style="border:solid 1px;margin:5px;padding:2px;'+decoration+'background-color:#'+color+';">'+nicetext+'<div style="float:right;"><button onclick="toggleAttributeCosmetic('+id+')" title="Flag this qualifier as cosmetic or non-cosmetic"/><img src="./cosmetic.png" width="25"></button><button onclick="sequenceAttributeUp('+id+')" title="Move this qualifier down in the sequence"><img src="./down.png" width="25"/></button><button onclick="removeFitmentAttribute('+id+')" title="Remove this qualifier from the app"><img src="./delete.png" width="25"/></button></div><div style="clear:both;"></div></div></div>';
              }              

             };
             xhr.send();
            }
    
    
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


            function addVCdb()
            {
             var vcdbSelect = document.getElementById("vcdbattribute");
             var selectedValue=vcdbSelect.options[vcdbSelect.selectedIndex].value;
             var chunks = selectedValue.split('_');
                             
             var name=chunks[0];
             var value=chunks[1];
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddAppAttribute.php?type=vcdb&name='+name+'&value='+value+'&cosmetic=0&appid='+<?php echo $appid;?>);
             xhr.onload = function()
             {
              var result=JSON.parse(xhr.responseText);
              
              var container=document.getElementById('fitment');
              container.innerHTML+='<div id="attribute_'+result.id+'" style="border:solid 1px;margin:5px;padding:2px;background-color:#52BE80;">'+result.nicetext+'<div style="float:right;"><button onclick="toggleAttributeCosmetic('+result.id+')" title="Flag this qualifier as cosmetic or non-cosmetic"/><img src="./cosmetic.png" width="25"></button><button onclick="sequenceAttributeUp('+result.id+')" title="Move this qualifier down in the sequence"><img src="./down.png" width="25"/></button><button onclick="removeFitmentAttribute('+result.id+')" title="Remove this qualifier from the app"><img src="./delete.png" width="25"/></button></div><div style="clear:both;"></div></div></div>';
              document.getElementById("sandpiperoid").innerHTML=result.oid;
             };
             xhr.send();
             document.getElementById("newvcdbattributeform").style.display='none';
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
              
              var container=document.getElementById('fitment');
              container.innerHTML+='<div id="attribute_'+result.id+'" style="border:solid 1px;margin:5px;padding:2px;background-color:#6060F0;">'+result.nicetext+'<div style="float:right;"><button onclick="toggleAttributeCosmetic('+result.id+')" title="Flag this qualifier as cosmetic or non-cosmetic"/><img src="./cosmetic.png" width="25"></button><button onclick="sequenceAttributeUp('+result.id+')" title="Move this qualifier down in the sequence"><img src="./down.png" width="25"/></button><button onclick="removeFitmentAttribute('+result.id+')" title="Remove this qualifier from the app"><img src="./delete.png" width="25"/></button></div><div style="clear:both;"></div></div></div>';
              document.getElementById("sandpiperoid").innerHTML=result.oid;

             };
             xhr.send();
             document.getElementById('qdbresultscount').style.display='none';
             document.getElementById('qdbresults').style.display='none';
             document.getElementById('qdbpreview').style.display='none';             
             document.getElementById('addqdbqualifierbutton').style.display='none';
             document.getElementById('newqdbattributeform').style.display='none';
              
             resetQdbParms();

            }

            function resetQdbParms()
            {
             var p;
             for(p=1; p<=8; p++)
             {
              document.getElementById('qdbparm'+p+'block').style.display='none';
              document.getElementById('qdbparm'+p+'uomblock').style.display='none';
              document.getElementById('qdbparm'+p+'value').value='';
              document.getElementById('qdbparm'+p+'uom').value='';
             }
            }
            



            function addNote()
            {
             var noteText = document.getElementById("fitmentnote").value;
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddAppAttribute.php?type=note&name=note&value='+encodeURIComponent(noteText)+'&cosmetic=0&appid='+<?php echo $appid;?>);
             xhr.onload = function()
             {
              var result=JSON.parse(xhr.responseText);
              
              var container=document.getElementById('fitment');
              container.innerHTML+='<div id="attribute_'+result.id+'" style="border:solid 1px;margin:5px;padding:2px;background-color:#c0c0c0;">'+result.nicetext+'<div style="float:right;"><button onclick="convertNote('+result.id+')" title="Convert this note to a Qdb qualifier"><img src="./toqdb.png" width="25"/></button><button onclick="toggleAttributeCosmetic('+result.id+')" title="Flag this qualifier as cosmetic or non-cosmetic"><img src="./cosmetic.png" width="25"/></button><button onclick="sequenceAttributeUp('+result.id+')" title="Move this qualifier down in the sequence"><img src="./down.png" width="25"/></button><button onclick="removeFitmentAttribute('+result.id+')" title="Remove this qualifier from the app"><img src="./delete.png" width="25"/></button></div><div style="clear:both;"></div></div></div>';
              document.getElementById("sandpiperoid").innerHTML=result.oid;
              
             };
             xhr.send();
             document.getElementById("newnoteform").style.display='none';
            }


            function searchQdb()
            {
             document.getElementById("qdbresultscount").innerHTML="Searching Qdb...";
             document.getElementById("qdbresults").innerHTML = "";
             var searchterm=document.getElementById("qdbsearchterm").value;
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxSearchQdb.php?type=any&searchterm='+encodeURIComponent(searchterm));
             xhr.onload = function()
             {
              var results=JSON.parse(xhr.responseText);
              document.getElementById("qdbresultscount").innerHTML= results.length+" results found";
              for(var k in results) 
              {
                var newOption = new Option(results[k].qualifiertext,results[k].qualifierid);
                document.getElementById("qdbresults").add(newOption,undefined);
              }
              
              if(results.length>0)
              {
               document.getElementById("qdbresults").style.display='block';
              }
              else
              {
               document.getElementById("qdbresults").style.display='none';
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
             document.getElementById('qdbpreview').style.display='block';
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

            function sequenceAttributeUp(id)
            {
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxMoveAppAttribute.php?id='+id+'&appid=<?php echo $appid;?>');
             xhr.onload = function()
             {
// re-render all attributes from the database now that the ajax call has completed
    
              renderFitments();
    
             };
             xhr.send();
            }

            function toggleAttributeCosmetic(id)
            {
             var property=document.getElementById('attribute_'+id).style.getPropertyValue('text-decoration');
             if(property=='line-through')
             {
              document.getElementById('attribute_'+id).style.setProperty('text-decoration','none');
             }
             else
             {
              document.getElementById('attribute_'+id).style.setProperty('text-decoration','line-through');
             }
              
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxToggleAppAttributeCosmetic.php?id='+id+'&appid=<?php echo $appid;?>');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              document.getElementById("sandpiperoid").innerHTML=response.oid;
             };
             xhr.send();
            }


            function showhideForm(elementid)
            {
             var element = document.getElementById(elementid);
             if (element.style.display === "none") 
             {
              element.style.display = "block";
             }
             else
             {
              element.style.display = "none";
             }
            }
            
            function removeFitmentAttribute(id)
            {
             var div = document.getElementById('attribute_'+id);
             div.parentNode.removeChild(div);
                
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxDeleteAppAttribute.php?id='+id+'&appid=<?php echo $appid;?>');
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              document.getElementById("sandpiperoid").innerHTML=response.oid;
             };
             xhr.send();
            }
            
            function convertNote(id)
            {
             location.href='convertNoteToQdb.php?attributeid='+id;
            }

            function addAsset()
            {
             var assetid=document.getElementById('assetid').value;
             var assetrepresentation=document.getElementById('assetrepresentation').value;
             var assetsequence=document.getElementById('assetsequence').value;
             
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddAppAsset.php?appid=<?php echo $appid;?>&assetid='+assetid+'&representation='+assetrepresentation+'&sequence='+assetsequence);
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              document.getElementById("sandpiperoid").innerHTML=response.oid;
              var container = document.getElementById('assets');
              container.innerHTML+='<div id="assetconnection_'+response.id+'"><span>'+assetid+' ('+assetrepresentation+', '+assetsequence+')</span><span onclick="removeAsset(\''+response.id+'\');">x</span></div>';
             };
             xhr.send();
            }
            
            function removeAsset(id)
            {
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxDeleteAppAsset.php?appid=<?php echo $appid;?>&id='+id);
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
              document.getElementById("sandpiperoid").innerHTML=response.oid;
             };
             xhr.send();

             var div = document.getElementById('assetconnection_'+id);
             div.parentNode.removeChild(div);
            }
            
            function populateDoc() {
                var docs = document.getElementsByClassName("btn-docs");
                for (var i=0; i<docs.length; i++) {
                    getDocText(docs[i].getAttribute('id'),docs[i].getAttribute('data-doc'));
                }
            }
                        
            function getDocText(divid,path)
            {
                var docdiv=document.getElementById(divid);
                
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajaxGetDocumentation.php?path='+btoa(path));
                xhr.onload = function()
                {
                    docdiv.setAttribute("title",xhr.responseText);
                };
                console.log(xhr.responseText);
                xhr.send();
             
            }

            function addAppToClipboard()
            {
             var description = '<a href="showApp.php'+ window.location.search +'"><?php echo $makename.' '.$modelname.', '.$year.' ('.$app['partnumber'].')';?></a>';
             console.log(window.location.search);
             var objectdata='';
             var objectkey='<?php echo $appid;?>';
             var xhr = new XMLHttpRequest();
             xhr.open('GET', 'ajaxAddToClipboard.php?objecttype=app&description='+btoa(description)+'&objectkey='+objectkey+'&objectdata='+btoa(objectdata));
             xhr.onload = function()
             {
              var response=JSON.parse(xhr.responseText);
             };
             xhr.send();
            }



        </script>
    </head>
    <body onload="setStatusColor();populateDoc();">
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    <?php $issues=$pim->getIssues('APP/%', '', $appid, array(1,2),10);
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
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
                        <h6 class="card-header text-start">
                            <div style="float:right">
                                <span class="btn btn-info" onclick="addAppToClipboard(),refreshClipboard()">Copy</span>
                                <?php if(count($history)){echo '<a class="btn btn-secondary" href="./appHistory.php?appid='.$appid.'">History</a>';}?>
                            </div>
                            <div style="clear:both;"></div>
                        </h6>
                        
                        

                        
                        <div class="card-body">
                            <?php if($app) {;?>
                            <div style="padding:10px;">
                             <?php
                              if($pcdb->parttypeName($app['parttypeid'])=='not found'){echo '<div style="color:red;">Part Type id '.$app['parttypeid'].' is not found in the loaded ('.$pcdbversion.') PCdb</div>';}
                              if($pcdb->positionName($app['positionid'])=='not found'){echo '<div style="color:red;">Position id '.$app['positionid'].' is not found in the loaded ('.$pcdbversion.') PCdb</div>';}
                             ?>
                                
                            <table class="table" border="1" cellpadding="5">
                                    <tr>
                                        <th>
                                            <a href="./showAppsByBasevehicle.php?<?php echo implode('&', $selectedcategoriesurlvars); ?>&makeid=<?php echo $mmy['MakeID']; ?>&modelid=<?php echo $mmy['ModelID']; ?>&yearid=<?php echo $year; ?>&submit=Show+Applications" class="btn btn-secondary">Base Vehicle</a>
                                        </th>
                                        <td align="left">
                                            <div style="background-color:<?php echo $appcolor;?>;padding:5px;">
                                                <?php echo '<a href="appsIndex.php"  class="btn btn-secondary">'.$makename.'</a>  <a href="mmySelectModel.php?makeid=' . $mmy['MakeID'] . '" class="btn btn-secondary">'.$modelname.'</a> <a href="mmySelectYear.php?makeid=' . $mmy['MakeID'] . '&modelid=' . $mmy['ModelID'] . '" class="btn btn-secondary">'.$year.'</a>'; ?>
                                            </div>
                                        </td>
                                    </tr>                                    
                                    <tr>                                        
                                        <th id="label-status">Status</th>
                                        <td id="value-status" align="right">                                            
                                                <select id="status" onchange="updateApp(<?php echo $appid; ?>,'select','status');"><option value="0">Active</option><option value="1"<?php if ($app['status'] == 1) {
                                    echo ' selected';
                                    } ?>>Deleted</option><option value="2"<?php if ($app['status'] == 2) {
                                    echo ' selected';
                                    } ?>>Hidden</option></select>                                            
                                        </td>
                                    </tr>                                    
                                    <tr><th>Part</th><td align="left"><a href="showPart.php?partnumber=<?php echo $app['partnumber']; ?>" class="btn btn-secondary"><?php echo $app['partnumber']; ?></a></td></tr>
                                    <tr><th>Application<br/>Part Type</th><td align="right"><select id="parttypeid" onchange="if (this.selectedIndex) updateApp(<?php echo $appid; ?>,'select','parttypeid');"><option value="0">Undefined</option><?php foreach ($favoriteparttypes as $parttype) { ?> <option value="<?php echo $parttype['id']; ?>"<?php if ($parttype['id'] == $app['parttypeid']) {
                                    echo ' selected';
                                    } ?>><?php echo $parttype['name']; ?></option><?php } ?></select></td></tr>
                                    <tr><th>Position</th><td align="right"><select id="positionid"  onchange="if (this.selectedIndex) updateApp(<?php echo $appid; ?>,'select','positionid');"><option value="0">Undefined</option><?php foreach ($favoritepositions as $position) { ?> <option value="<?php echo $position['id']; ?>"<?php if ($position['id'] == $app['positionid']) {
                                    echo ' selected';
                                    } ?>><?php echo $position['name']; ?></option><?php } ?></select></td></tr>
                                    <tr><th>Fitment<br/>Qualifiers</th>
                                        <td align="left">
                                            <div id="fitment">
                                                <?php
                                                foreach ($niceattributes as $niceattribute) {
                                                    $text_decoration = '';
                                                    if ($niceattribute['cosmetic'] == 1) {
                                                        $text_decoration = 'text-decoration: line-through;';
                                                    }
                                                    ?>
                                                    <div id="attribute_<?php echo $niceattribute['id']; ?>" style="border:solid 1px;margin:5px;<?php echo $text_decoration; ?> padding:2px;background-color:#<?php echo $attributecolors[$niceattribute['type']]; ?>;">
                                                            <?php echo $niceattribute['text']; ?><div style="float:right;">

                                                <?php 
                                                    if ($niceattribute['type'] == 'note') {
                                                    echo "<button onclick=\"convertNote('" . $niceattribute['id'] . "')\" title=\"Convert this note to a Qdb qualifier\"><img src=\"./toqdb.png\" width=\"25\"/></button>";
                                                } ?>
                                                    <button onclick="toggleAttributeCosmetic('<?php echo $niceattribute['id']; ?>')" title="Flag this qualifier as cosmetic or non-cosmetic"/><img src="./cosmetic.png" width="25"></button><button onclick="sequenceAttributeUp('<?php echo $niceattribute['id']; ?>')" title="Move this qualifier down in the sequence"><img src="./down.png" width="25"/></button><button onclick="removeFitmentAttribute('<?php echo $niceattribute['id']; ?>')" title="Remove this qualifier from the app"><img src="./delete.png" width="25"/></button></div><div style="clear:both;"></div></div><?php } ?>
                                            </div>
                                            <div onclick="showhideForm('newvcdbattributeform'); getElementById('vcdbattribute').focus();">Add VCdb Attribute ...</div>

                                            <div id="newvcdbattributeform" style="display:none;padding: 30px;">
                                                <select id="vcdbattribute"> 
                                                    <?php
                                                    
                                                    $acestags = array(); $taghashes=array();
                                                    
                                                    foreach ($allattributes as $allattribute)
                                                    {       
                                                        $acestags[$allattribute['name']][]=$allattribute;
                                                    }

                                                    foreach ($acestags as $acestagname=>$options) 
                                                    {
                                                        echo '<optgroup label="' . $acestagname . '">';
                                                        foreach($options as $option)
                                                        {
                                                            if(array_key_exists($option['name'].'_'.$option['value'], $taghashes)){continue;}
                                                            $taghashes[$option['name'].'_'.$option['value']]='';
                                                            echo '<option value="'.$option['name'].'_'.$option['value']. '">'.$option['display'].'</option>';
                                                        }
                                                        echo '</optgroup>';
                                                    }
                                                    
                                                    ?>
                                                </select>
                                                <button onclick="addVCdb()">Add VCdb Attribute</button>
                                            </div>

                                            <div onclick="showhideForm('newnoteform'); getElementById('fitmentnote').focus();">Add free-form fitment note ...</div>

                                            <div id="newnoteform" style="display:none;padding: 30px;">
                                                <input type="text" id="fitmentnote"/> <button onclick="addNote()">Add Fitment Note</button>
                                            </div>


                                            <div onclick="showhideForm('newqdbattributeform'); getElementById('qdbsearchterm').focus();">Add Qdb Qualifier ...</div>
                                            <div id="newqdbattributeform" style="display:none;padding:30px;">
                                                <div style="padding:3px;">
                                                    <div style="float:left;">
                                                        <input type="text" id="qdbsearchterm"/>
                                                        <button id="qdbsearch" onclick="searchQdb()">Search</button>
                                                    </div>
                                                    <div style="clear:both;"></div>
                                                </div>
                                                <div style="padding:3px;font-size: 75%; color: #aaaaaa;" id="qdbresultscount"></div>
                                                <select id="qdbresults" style="display:none;" size="10" multiple onchange="selectQdb(); getElementById('addqdbqualifierbutton').style.display='block';"></select>

                                                <?php for ($i = 1; $i <= 8; $i++) { ?>
                                                    <div id="qdbparm<?php echo $i; ?>block" style="padding:3px; display:none;">
                                                        <div id="qdbparm<?php echo $i; ?>title" style="float:left;"></div>
                                                        <div style="float:left;">
                                                            <input size="8" type="text" id="qdbparm<?php echo $i; ?>value" onkeyup="showQdbPreview()"/>
                                                        </div>
                                                        <div id="qdbparm<?php echo $i; ?>uomblock" style="float:left;padding-left:10px; display:none;">uom
                                                            <input type="text" id="qdbparm<?php echo $i; ?>uom" size="2" onkeyup="showQdbPreview();"/>
                                                        </div>
                                                        <div style="clear:both;"></div>
                                                    </div>
                                                <?php } ?>

                                                <div id="qdbpreview" data-qdbid="" data-qdbparmstring="" style="display:none; background-color: #6060F0; border: solid 1px black; padding:3px;margin: 20px;"></div>
                                                <div id="addqdbqualifierbutton" style="padding: 5px;display:none;"><button id="addQdb" onclick="addQdb();">Add Qdb Qualifier</button></div>
                                            </div>

                                        </td>
                                    </tr>
                                    <tr><th>Quantity<br/>(on this vehicle)</th>
                                        <td align="right">
                                            <input id="quantityperapp" type="text" name="quantityperapp" size="1" value="<?php echo $app['quantityperapp']; ?>"/><button onclick='updateApp(<?php echo $appid; ?>,"text","quantityperapp");'>Update Qty</button>
                                            <button id="doc-qty" type="button" class="btn btn-sm btn-docs" data-doc="Apps/Show App/Fitment Quantity" data-bs-toggle="tooltip" data-bs-placement="top">?</button>
                                        </td></tr>
                                    <tr><th>Fitment<br/>Assets</th>
                                        <td align="right">
                                            <div id="assets">
                                            <?php if (count($appassets)) {
                                                foreach ($appassets as $appasset) {
                                                    echo '<div id="assetconnection_'.$appasset['id'].'"><span>'.$appasset['assetid'].' ('.$appasset['representation'].', ' . $appasset['assetItemOrder']. ')</span><span onclick="removeAsset(\''.$appasset['id'].'\');">x</span></div>';
                                                }
                                            } ?>
                                            </div>
                                            <?php if(count($partassets)){?>
                                            <div>
                                                Asset <select id="assetid">
                                                <?php foreach ($partassets as $partasset) {echo '<option value="' . $partasset['assetid'] . '">'.$partasset['filename'].'</option>';}?>
                                                </select>
                                                Sequence <input type="text" id="assetsequence" size="3" value="1"/>
                                                <select id="assetrepresentation"><option value="A">Actual</option><option value="R">Representative</option></select>
                                                <button onclick="addAsset();">+</button>
                                            </div>
                                            <?php }?>
                                            <button id="doc-asset" type="button" class="btn btn-sm btn-docs" data-doc="Apps/Show App/Fitment Assets/Representation" data-bs-toggle="tooltip" data-bs-placement="top">?</button>
                                        </td>
                                    </tr>
                                    <tr><th>Internal<br/>Notes</th>
                                        <td>
                                            <textarea id="internalnotes" cols="60" rows="5"><?php echo $app['internalnotes']; ?></textarea><div><button onclick='updateApp(<?php echo $appid; ?>,"text","internalnotes");'>Save</button></div>
                                            <button id="doc-internalnotes" type="button" class="btn btn-sm btn-docs" data-doc="Apps/Show App/Internal Notes" data-bs-toggle="tooltip" data-bs-placement="top">?</button>
                                        </td>
                                    <tr>
                                <tr><th>IDs</th><td><div style="float:left;">Application ID:</div><div style="float:left;"><?php echo $app['id']; ?></div><div style="clear:both;"></div><div style="float:left;">Sandpiper OID:</div><div style="float:left;" id="sandpiperoid"><?php echo $app['oid']; ?></div><div style="clear:both;"></div><div style="float:left;">BaseVehicle ID:</div><div style="float:left;"><?php echo $app['basevehicleid']; ?></div><div style="clear:both;"></div></td><tr>
                                <tr><th>VIO</th><td><div style="float:left;"><?php echo $viogeography.' '.$vioyearquarter.': <a href="./ExperianVIOsnippetStream.php?basevehicleid='.$app['basevehicleid'].'">'.number_format($vio,0,'.',',').'</a>';?></div></td></tr>
                                
                                <tr>
                                    <th id="label-cosmetic" class="appcosmetic-noncosmetic">Cosmetic</th>
                                    <td id="value-cosmetic" class="appcosmetic-noncosmetic" align="right">
                                        <div id="cosmetic-text"></div>
                                        <button onclick='updateApp(<?php echo $appid; ?>,"button","cosmetic");'>Cosmetic</button>
                                        <button id="doc-cosmetic" type="button" class="btn btn-sm btn-docs" data-doc="Apps/Show App/Cosmetic" data-bs-toggle="tooltip" data-bs-placement="top">?</button>
                                    </td>
                                </tr>
                               </table>
                              </div>
                              <?php }
                              else
                              { // no apps found
                               echo 'Application not found';
                              }?>
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
        <?php include('./includes/footer.php'); ?>
    </body>
</html>