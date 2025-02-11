<?php
/*
 * intended to be executed from the command-line be a cron call ("php processACESexport.php")
 * on a cycle (likely every 5 or 10 minutes). It will query the db for the oldest job that 
 * is status "started" and execute it. The job will be 
 * 
 * On my fedora 31 box, I had to apply a read/write SELinux policy to the 
 * directory where apache can write the exported files (/var/www/html/ACESexports
 * semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/ACESexports(/.*)?"
 * restorecon -Rv /var/www/html/ACESexports/
 * 
 * 
 */

function compareApps($app1,$app2,$respectpart,$respectposition,$respectqty,$respectparttype)
{
    if($app1['basevehicleid']!=$app2['basevehicleid']){return false;}
    if($respectpart && $app1['partnumber']!=$app2['partnumber']){return false;}
    if($respectposition && $app1['positionid']!=$app2['positionid']){return false;}
    if($respectqty && $app1['quantityperapp']!=$app2['quantityperapp']){return false;}
    if($respectparttype && $app1['parttypeid']!=$app2['parttypeid']){return false;}
    if(count($app1['attributes']) != count($app2['attributes'])){return false;}
    if(count($app1['attributes'])>0)
    {
        foreach($app1['attributes'] as $i=>$attr)
        {
            if($app2['attributes'][$i]['name']!=$attr['name']){return false;}
            if($app2['attributes'][$i]['value']!=$attr['value']){return false;}
            if($app2['attributes'][$i]['type']!=$attr['type']){return false;}            
        }
    }    
    return true;
}



include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
$pim = new pim();


include_once(__DIR__.'/class/vcdbClass.php');
include_once(__DIR__.'/class/pcdbClass.php');
include_once(__DIR__.'/class/qdbClass.php');
include_once(__DIR__.'/class/logsClass.php');

$vcdb=new vcdb();
$pcdb=new pcdb();
$qdb=new qdb();

$logs=new logs();

$kits=$pim->getKits();
$assemblies=array_keys($kits);

$assemblies=array('CAB80152','CAB80346','CAB80348','CAB80364','CAB80740','CAB80904','CAB81069','CAB81082','CAB81710','CAB81721','CAB82168','CAB82308','CAB82460',
'CAB82701','CAB83037','CAB83136','CAB83327','CAB83371','CAB83673','CAB84383','CAB84852','CAB85101','CAB85117','CAB85466','CAB85499','CAB85573',
'CAB85745','CAB86021','CAB86247','CAB86258','CAB86276','CAB86511','CAB86550','CAB86584','CAB87046','CAB87072','CAB87180','CAB87247','CAB87273',
'CAB87390','CAB87564','CAB87820','CAB88011','CAB88383','CAB88820','CAB89073','CAB89180','CAB89193','CAB89413','CAB89639','CAB89692');



foreach($assemblies as $assembly)
{
    $components = $pim->getKitComponents($assembly);
    $itemkeyedapplists=array();
    $allapps=array();
    $commonapps=array();
     
    foreach($components as $component)
    {
        $apps=$pim->getAppsByPartnumber($component['partnumber']);
        $itemkeyedapplists[$component['partnumber']]=$apps;
        foreach($apps as $app){$allapps[]=$app;}
    }
    
    foreach($allapps as $allapp)
    {
        $componentmatchcount=0;
                
        foreach($itemkeyedapplists as $componentpartnumber=>$componentapps)
        {
            $found=false;
            
            foreach($componentapps as $componentapp)
            {
                if(compareApps($componentapp,$allapp,false,true,false,false))
                {
                    $found=true; break;
                }                
            }

            if($found){$componentmatchcount++;}                        
        }
        
        if(count($itemkeyedapplists)==$componentmatchcount)
        {
            $exists=false;
            foreach($commonapps as $commonapp)
            {
                if(compareApps($commonapp, $allapp, false, true, false, false))
                {
                    $exists=true;
                    break;
                }                
            }
            
            if(!$exists){$commonapps[]=$allapp;}          
        }
    }
    
    
    // common apps now contains a list of "donor" apps to contribute 
    // a vehicle (basevid+qualifiers)    
    // look at existing app for the assembly an see if we need to change anything
    $existingassemblyapps = $pim->getAppsByPartnumber($assembly);
    
    $identicallists=true;
    if(count($existingassemblyapps)>0 && count($existingassemblyapps)==count($commonapps))
    {// existing apps list on the assembly is the same count as the common list we just compiled -
        // search each one to verify identical lists

        foreach($existingassemblyapps as $existingassemblyapp)
        {
            $found=false;
            foreach($commonapps as $commonapp)
            {
                if(compareApps($commonapp, $existingassemblyapp, false, true, false, false))
                {
                    $found=true; break;
                }   
            }
            if(!$found){$identicallists=false; break;}
        }        
    }
    
    if(!$identicallists || count($existingassemblyapps)!=count($commonapps))
    {// existing app list for this assembly needs to be flushed and re-created
        //echo $assembly.' needs refresh of '.count($commonapps)." apps\r\n";
        $appids=array(); foreach($commonapps as $commonapp){$appids[]=$commonapp['id'];}
        $pim->deleteAppsByPartnumber($assembly);
        $pim->cloneAppsToPart($assembly, $appids);
        $logs->logSystemEvent('housekeeping',0, 'kit application updater found need to write '.count($commonapps).' for kit:'.$assembly);
    }
    
}
        
        






?>
