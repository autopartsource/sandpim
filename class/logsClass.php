<?php

include_once("mysqlClass.php");

class logs {

    function logSystemEvent($eventtype, $userid, $text) {
        $db = new mysql;
        if(strlen($text)>65534){$text=substr($text,0,65534);}
        $db->connect();
        if ($stmt = $db->conn->prepare('insert into system_history (id,eventdatetime,eventtype,userid,description) values(null,now(),?,?,?)')) {
            $stmt->bind_param('sis', $eventtype, $userid, $text);
            $stmt->execute();
        }
        $db->close();
        return $userid;
    }

    function getSystemEvents($eventtype, $userid, $limit) {
        $db = new mysql;
        //$db->dbname='pim'; 
        $db->connect();
        $events = array();

        if ($userid) {
            $sql = 'select * from system_history where eventtype like ? and userid=? order by eventdatetime desc limit ?';
        } else {
            $sql = 'select * from system_history where eventtype like ? order by eventdatetime desc limit ?';
        }

        if ($stmt = $db->conn->prepare($sql)) {
            if ($userid) {
                $stmt->bind_param('sii', $eventtype, $userid, $limit);
            } else {
                $stmt->bind_param('si', $eventtype, $limit);
            }

            $stmt->execute();
            $db->result = $stmt->get_result();
            while ($row = $db->result->fetch_assoc()) {
                $events[] = array('id' => $row['id'], 'eventdatetime' => $row['eventdatetime'], 'eventtype' => $row['eventtype'], 'userid' => $row['userid'], 'description' => $row['description']);
            }
        }
        $db->close();
        return $events;
    }

    function getSystemEvent($id)
    {
        $db = new mysql; $db->connect();  $event = false;

        if ($stmt = $db->conn->prepare('select * from system_history where id=?')) 
        {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $db->result = $stmt->get_result();
            while ($row = $db->result->fetch_assoc()) 
            {
                $event = array('id' => $row['id'], 'eventdatetime' => $row['eventdatetime'], 'eventtype' => $row['eventtype'], 'userid' => $row['userid'], 'description' => $row['description']);
            }
        }
        $db->close();
        return $event;
    }
    
    function getAppsEvents($limit) {
        $db = new mysql;
        $db->connect();
        $events = array();
        if ($stmt = $db->conn->prepare('select * from application_history order by eventdatetime desc limit ?')) {
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $db->result = $stmt->get_result();
            while ($row = $db->result->fetch_assoc()) {
                $events[] = array('id' => $row['id'], 'applicationid' => $row['applicationid'], 'eventdatetime' => $row['eventdatetime'], 'userid' => $row['userid'], 'description' => $row['description'], 'new_oid' => $row['new_oid']);
            }

            // sort the results ascending
            $sorted = array();
            for ($i = count($events) - 1; $i >= 0; $i--) {
                $sorted[] = $events[$i];
            }
        }
        $db->close();
        return $sorted;
    }

    function getVehicleEvents($basevehicleid,$limit) 
    {
        $db = new mysql; $db->connect();
        $events = array();
        if ($stmt = $db->conn->prepare('select * from vehicle_history where basevehicleid=? order by eventdatetime desc limit ?')) 
        {
            $stmt->bind_param('ii',$basevehicleid, $limit);
            $stmt->execute();
            $db->result = $stmt->get_result();
            while ($row = $db->result->fetch_assoc()) 
            {
                $events[] = array('id' => $row['id'], 'eventdatetime' => $row['eventdatetime'], 'userid' => $row['userid'], 'description' => $row['description']);
            }

            // sort the results ascending
            $sorted = array();
            for ($i = count($events) - 1; $i >= 0; $i--) {
                $sorted[] = $events[$i];
            }
        }
        $db->close();
        return $sorted;
    }
    
    
    
    function getPartsEvents($limit) {
        $db = new mysql; $db->connect(); $events = array();
        if ($stmt = $db->conn->prepare('select * from part_history order by eventdatetime desc limit ?')) {
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $db->result = $stmt->get_result();
            while ($row = $db->result->fetch_assoc()) {
                $events[] = array('id' => $row['id'], 'partnumber' => $row['partnumber'], 'eventdatetime' => $row['eventdatetime'], 'userid' => $row['userid'], 'description' => $row['description'], 'new_oid' => $row['new_oid']);
            }
        }

        // sort the results ascending
/*        $sorted = array();
        for ($i = count($events) - 1; $i >= 0; $i--) {
            $sorted[] = $events[$i];
        }
 */
        $db->close();
        return $events;
    }

    function getPartEvents($partnumber, $limit) {
        $db = new mysql; $db->connect(); $events = array();
        if ($stmt = $db->conn->prepare('select * from part_history where partnumber=? order by eventdatetime desc limit ?')) {
            $stmt->bind_param('si', $partnumber, $limit);
            $stmt->execute();
            $db->result = $stmt->get_result();
            while ($row = $db->result->fetch_assoc()) {
                $events[] = array('id' => $row['id'], 'partnumber' => $row['partnumber'], 'eventdatetime' => $row['eventdatetime'], 'userid' => $row['userid'], 'description' => $row['description'], 'new_oid' => $row['new_oid']);
            }
        }

        // sort the results ascending
 /*       $sorted = array();
        for ($i = count($events) - 1; $i >= 0; $i--) {
            $sorted[] = $events[$i];
        }
  */
        $db->close();
        return $events;
    }

    function getAssetsEvents($limit)
    {
        $db = new mysql; $db->connect(); $events = array();
        if ($stmt = $db->conn->prepare('select * from asset_history order by eventdatetime desc limit ?'))
        {
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $db->result = $stmt->get_result();
            while ($row = $db->result->fetch_assoc())
            {
                $events[] = array('id' => $row['id'], 'assetid' => $row['assetid'], 'eventdatetime' => $row['eventdatetime'], 'userid' => $row['userid'], 'description' => $row['description'], 'new_oid' => $row['new_oid']);
            }
        }

        // sort the results ascending
/*        $sorted = array();
        for ($i = count($events) - 1; $i >= 0; $i--) {
            $sorted[] = $events[$i];
        }
*/

        $db->close();
        return $events;
    }

    function getAssetEvents($assetid,$limit)
    {
        $db = new mysql; $db->connect(); $events = array();
        if($stmt = $db->conn->prepare('select * from asset_history where assetid=? order by id desc limit ?'))
        {
            $stmt->bind_param('si', $assetid, $limit);
            $stmt->execute();
            $db->result = $stmt->get_result();
            while ($row = $db->result->fetch_assoc())
            {
                $events[] = array('id' => $row['id'], 'assetid' => $row['assetid'], 'eventdatetime' => $row['eventdatetime'], 'userid' => $row['userid'], 'description' => $row['description'], 'new_oid' => $row['new_oid']);
            }
        }

        $db->close();
        return $events;
    }

    function getSandpiperEvents($limit)
    {
        $db = new mysql; $db->connect();
        $events = array();
        $sql = 'select * from sandpiperactivity order by id desc limit ?';
 
        if ($stmt = $db->conn->prepare($sql))
        {
            if($stmt->bind_param('i', $limit))
            {
                if($stmt->execute())
                {
                    $db->result = $stmt->get_result();
                    while ($row = $db->result->fetch_assoc()) 
                    {
                        $events[] = array('id' => $row['id'], 'planuuid'=>$row['planuuid'], 'subscriptionuuid'=>$row['subscriptionuuid'], 'grainuuid'=>$row['grainuuid'], 'action'=>$row['action'], 'timestamp' => $row['timestamp']);
                    }
                }
            }
        }
        $db->close();
        return $events;
    }
    
    function countBOMchangeEvents($partnumber)
    {
        $count=0;
        $events=$this->getPartEvents($partnumber, 1000);
        foreach($events as $event)
        {
            if(strpos($event['description'],'BOM change')!==false){$count++;}
        }
        return $count;
    }




    function extractBOMsfromChangeText($input)
    {
        // take a BOM change history record like:
        //BOM change (new != old):BPRC2314~1~10 A2314~1~20 943C~1~80 AMWP1~4~100 HDWBX2~1~115 PRO2~1~120 L25~1~140 != BPRC2314~1~10 A2314~1~20 943C~1~80 AMWP1~4~100 HDWBX2~1~115 PRO2~1~120
        // into an array 
        $returnVal=array('before'=>array(),'after'=>array(),'beforeparts'=>array(),'afterparts'=>array());
        
        if(strpos($input,'BOM change')===false){return $returnVal;}
        $bits=explode(':',$input);
        if(count($bits)==2)
        {
            $oldnewpos=strpos($bits[1],'!=');
            if($oldnewpos!==false)
            {// found the onld/new delimiter (!=)
                
                $oldbomstring=substr($bits[1],$oldnewpos+2);
                $oldbomarray=explode("\t",$oldbomstring);
                $newbomstring=substr($bits[1],0,$oldnewpos);                                      
                $newbomarray=explode("\t",$newbomstring);

                foreach($newbomarray as $newbomline)
                {
                    $bomparts=explode('~',$newbomline);
                    if(count($bomparts)==3)
                    {
                        $returnVal['after'][]=array('partnumber'=>trim($bomparts[0]),'units'=>$bomparts[1]);
                        if(!array_key_exists(trim($bomparts[0]), $returnVal['afterparts']))
                        {
                            $returnVal['afterparts'][trim($bomparts[0])]='';
                        }
                    }
                }
 
                foreach($oldbomarray as $oldbomline)
                {
                    $bomparts=explode('~',$oldbomline);
                    if(count($bomparts)==3)
                    {
                        $returnVal['before'][]=array('partnumber'=>trim($bomparts[0]),'units'=>$bomparts[1]);
                        if(!array_key_exists(trim($bomparts[0]), $returnVal['beforeparts']))
                        {
                            $returnVal['beforeparts'][trim($bomparts[0])]='';
                        }
                    }
                }        
            }
        }
        return $returnVal;
    }
    



    function getUserEvents($userid,$limit)
    {
        // bring these 5 tables into one common result-set and order it by date-time
        // application_history
        // part_history
        // asset_history
        // system_history
        // vehicle_history
        
        $db = new mysql; $db->connect(); $events = array();
        
        if ($stmt = $db->conn->prepare('select * from application_history where userid=? order by eventdatetime desc limit ?'))
        {
            if($stmt->bind_param('ii', $userid, $limit))
            {                   
                if($stmt->execute())
                {
                    $db->result = $stmt->get_result();
                    while ($row = $db->result->fetch_assoc())
                    {
                        $events[] = array('type'=>'application','id' => $row['id'], 'reference' => $row['applicationid'], 'eventdatetime' => $row['eventdatetime'], 'description' => $row['description']);
                    }
                }
            }
        }
        
        if ($stmt = $db->conn->prepare('select * from part_history where userid=? order by eventdatetime desc limit ?'))
        {
            if($stmt->bind_param('ii', $userid, $limit))
            {                   
                if($stmt->execute())
                {
                    $db->result = $stmt->get_result();
                    while ($row = $db->result->fetch_assoc())
                    {
                        $events[] = array('type'=>'part','id' => $row['id'], 'reference' => $row['partnumber'], 'eventdatetime' => $row['eventdatetime'], 'description' => $row['description']);
                    }
                }
            }
        }
        
        if ($stmt = $db->conn->prepare('select * from asset_history where userid=? order by eventdatetime desc limit ?'))
        {
            if($stmt->bind_param('ii', $userid, $limit))
            {                   
                if($stmt->execute())
                {
                    $db->result = $stmt->get_result();
                    while ($row = $db->result->fetch_assoc())
                    {
                        $events[] = array('type'=>'asset','id' => $row['id'], 'reference' => $row['assetid'], 'eventdatetime' => $row['eventdatetime'], 'description' => $row['description']);
                    }
                }
            }
        }
        
        if ($stmt = $db->conn->prepare('select * from system_history where userid=? order by eventdatetime desc limit ?'))
        {
            if($stmt->bind_param('ii', $userid, $limit))
            {                   
                if($stmt->execute())
                {
                    $db->result = $stmt->get_result();
                    while ($row = $db->result->fetch_assoc())
                    {
                        $events[] = array('type'=>'system - '.$row['eventtype'] ,'id' => $row['id'], 'reference' => '', 'eventdatetime' => $row['eventdatetime'], 'description' => $row['description']);
                    }
                }
            }
        }
        
        if ($stmt = $db->conn->prepare('select * from vehicle_history where userid=? order by eventdatetime desc limit ?'))
        {
            if($stmt->bind_param('ii', $userid, $limit))
            {                   
                if($stmt->execute())
                {
                    $db->result = $stmt->get_result();
                    while ($row = $db->result->fetch_assoc())
                    {
                        $events[] = array('type'=>'system','id' => $row['id'], 'reference' => $row['basevehicleid'], 'eventdatetime' => $row['eventdatetime'], 'description' => $row['description']);
                    }
                }
            }
        }
        
        // sort the combined list by date/time and limit to original requested limit
        $datetimeindex=array(); foreach($events as $id=>$event){$datetimeindex[$id]=$event['eventdatetime'];}
        array_multisort($datetimeindex, SORT_DESC, $events);       
        $limitedevents= array_slice($events, 0, $limit);
 
        $db->close();
        return $limitedevents;
    }


    
    
    
}

?>
