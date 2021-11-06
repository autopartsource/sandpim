<?php
include_once("mysqlClass.php");

class replication
{
/* securing the replication:
 * 1) client primary and server secondary have mutal PSK
 * 2) we can't send secret in the clear
 * 3) we must be resistant to a play-back attack
 * 4) encryption is not needed
 * 5) client must be authenticated. Server's identity is assumed to be trustworthy
 * 
 * client's initial grain hash request and grainlist request are un-authenticated
 * subsequent push of content (or pulls of content) from client signed with the PSK.
 * If the signature fails the server will not accept content or divulge content.
 */
 
 function getPeers($identifier,$type,$role)
 {
  $db=new mysql; $db->connect(); $peers=array();
  if($stmt=$db->conn->prepare('select * from replicationpeer where identifier like ? and `type`=? and role=?'))
  {
   if($stmt->bind_param('sss',$identifier,$type,$role))
   {
    if($stmt->execute())
    {
     $db->result = $stmt->get_result();
     while($row = $db->result->fetch_assoc())
     {
       $peers[]=array('id'=>$row['id'],'identifier'=>$row['identifier'],'description'=>$row['description'],'type'=>$row['type'],'role'=>$row['role'],'uri'=>$row['uri'],'objectlimit'=>$row['objectlimit'],'sharedsecret'=>$row['sharedsecret'],'enabled'=>$row['enabled']);
     }
    }
   }
  }
  $db->close();
  return $peers;
 }


 
}?>
