<?php
include_once('./includes/loginCheck.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');
$navCategory = 'parts';

$pim = new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'partLifecycle.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

//validate that user has access to lifecycle management
if(!$pim->userHasNavelement($_SESSION['userid'], 'PARTS/LIFECYCLE'))
{
 $logs->logSystemEvent('accesscontrol',0, 'partLifecycle.php - access denied to user '.$_SESSION['userid'].' at '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;    
}

$pcdb = new pcdb;
$configGet = new configGet;

$partnumber = $pim->sanitizePartnumber($_GET['partnumber']);
$historyevents=$logs->getPartEvents($partnumber,200);

$part = $pim->getPart($partnumber);

$balance=$pim->getPartBalance($partnumber);


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

        </script>
        
    </head>
    <body onload="setStatusColor()">
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-1 my-col colLeft">
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-5 my-col colMain">
                    <div class="card shadow-sm">
                        <h3 class="card-header text-start">
                            <div>Part Number  <a href="./showPart.php?partnumber=<?php echo $partnumber?>"><span class="text-info"><?php echo $partnumber?></span></a>    </div>
                        </h3>
                        <div class="alert alert-danger" role="alert" id="heading-alert" style="display:none;">This is a danger alert—check it out!</div>
                        <div class="card-body">
                            <?php if ($part) { ?>
                            <div style="padding:10px;">
                                <table class="table" border="1" cellpadding="5">
                                    <tr>
                                        <th id="label-status" class="partstatus-available">Current Status</th>
                                        <td><?php echo $pcdb->lifeCycleCodeDescription($part['lifecyclestatus']);?></td>
                                    <tr/>
                                    
                                    <tr>
                                        <th>Actions</th>
                                        <td>
                                            <?php switch($part['lifecyclestatus'])
                                            {
                                                case '0':
                                                    //proposed
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=electronic&partnumber='.$part['partnumber'].'">Electronically Announce</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=available&partnumber='.$part['partnumber'].'">Make Available</a>';
                                                    break;
                                                case '1':
                                                    // released
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=propose&partnumber='.$part['partnumber'].'">Propose</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=electronic&partnumber='.$part['partnumber'].'">Electronically Announce</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=available&partnumber='.$part['partnumber'].'">Make Available</a>';
                                                    break;
                                                case '2':
                                                    // Available to order
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=propose&partnumber='.$part['partnumber'].'">Propose</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=whilesupplieslast&partnumber='.$part['partnumber'].'">While Supplies Last</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=supersede&partnumber='.$part['partnumber'].'">Supersede</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=discontinue&partnumber='.$part['partnumber'].'">Discontinue</a>';
                                                    break;
                                                case '3':
                                                    // Electronically Announced
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=propose&partnumber='.$part['partnumber'].'">Propose</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=available&partnumber='.$part['partnumber'].'">Make Available</a>';
                                                    break;
                                                case '4':
                                                    // Announced
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=propose&partnumber='.$part['partnumber'].'">Propose</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=electronic&partnumber='.$part['partnumber'].'">Electronically Announce</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=available&partnumber='.$part['partnumber'].'">Make Available</a>';
                                                    break;
                                                case '5':
                                                    //Temporarily Unavailable
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=available&partnumber='.$part['partnumber'].'">Make Available</a>';
                                                    break;
                                                case '6':
                                                    //re-numbered
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=supersede&partnumber='.$part['partnumber'].'">Supersede</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=discontinue&partnumber='.$part['partnumber'].'">Discontinue</a>';
                                                    break;
                                                case '7':
                                                    //Superseded
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=available&partnumber='.$part['partnumber'].'">Make Available</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=discontinue&partnumber='.$part['partnumber'].'">Discontinue</a>';
                                                    break;
                                                case '8':
                                                    // Discontinued
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=available&partnumber='.$part['partnumber'].'">Make Available</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=obsolete&partnumber='.$part['partnumber'].'">Obsolete</a>';
                                                    break;
                                                case '9':
                                                    //Obsolete
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=discontinue&partnumber='.$part['partnumber'].'">Discontinue</a>';
                                                    break;
                                                case 'A':
                                                    //Available only while supplies last
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=available&partnumber='.$part['partnumber'].'">Make Available</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=supersede&partnumber='.$part['partnumber'].'">Supersede</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=discontinue&partnumber='.$part['partnumber'].'">Discontinue</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=obsolete&partnumber='.$part['partnumber'].'">Obsolete</a>';
                                                    break;
                                                case 'B':
                                                    //not yet available
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=propose&partnumber='.$part['partnumber'].'">Propose</a>';
                                                    echo '<a class="btn btn-block btn-secondary" style="margin:5px" href="partLifecycleConfirm.php?action=available&partnumber='.$part['partnumber'].'">Make Available</a>';
                                                    break;                                                
                                                default:
                                                    break;
                                            }?>
                                        </td>
                                    <tr/>
                                    
                                    <?php if($balance){?> <tr><th>Balance</th><td>On-Hand: <b><?php echo round($balance['qoh'],0);?></b>, Demand: <b><?php echo $balance['amd'];?></b> units/month</td><tr> <?php }?>
                                    <tr><th>Internal<br/>Notes</th><td><?php echo $part['internalnotes']?></td><tr>
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
                <div class="col-xs-12 col-md-6 my-col colRight">
                    
                    <div class="card shadow-sm">
                        <h4 class="card-header text-start"><a href="./partHistory.php?partnumber=<?php echo $partnumber;?>">History</a></h4>

                        <div class="card-body d-flex flex-column scroll">                            
                            <div id="history">
                                <?php foreach($historyevents as $event){
                                    $eventtext=$event['description'];
                                    if(strstr($event['description'],'fitment grid drag')!==false){$eventtext=substr($event['description'],0,53).'......';}
                                    $eventdatetimebits=explode(' ',$event['eventdatetime']);
                                    $eventdate=$eventdatetimebits[0];
                                    
                                    
                                    ?>

                                <div style="padding:10px; text-align: left;"><?php echo '<a href="./partHistoryEvent.php?id='.$event['id'].'">'.$eventdate.'</a> '.$eventtext;?></div>
                                
                                <?php }?>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>