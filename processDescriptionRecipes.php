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




include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
$pim = new pim();


include_once(__DIR__.'/class/pcdbClass.php');
include_once(__DIR__.'/class/padbClass.php');
include_once(__DIR__.'/class/logsClass.php');

$pcdb=new pcdb();
$padb=new padb();

$logs=new logs();

// description recipie records are keyed by parttype/cattegory
/*
 * a recipe is made of blocks that are concatinated together left to right in sequence order
 * block types are: 
 *  literal string
 *  coponent touter
 *  padb (id and transform list)
 *  buyers guide
 * 
 * id
 * parttypeid
 * categoryid
 * blocktype (LITERAL,COMPONENTTOUTER,ATTRIBUTE,BUYERSGUIDE)
 * blockparameters (
 *  for literals: "Permium brake pads"
 *  for COMPONENTTOUTER: "parts~A2340,A567,A3325,A1203~Drop-in hardware kit included"
 *  for ATTRIBUTE: "4536~prefixtext~suffixtext"
 * sequence
 * language
 * descriptioncode
 * 
 */






?>
