<?php
include_once("mysqlClass.php");
//GRANT create ON *.* TO webservice@'localhost';
class setup
{

    function createDatebase($dbname)
    {
        $db = new mysql; 
        $db->dbname=$dbname; 
        $db->connect_nodb(); // will return empty string if successful
        
        if($stmt=$db->conn->prepare('create database '.$dbname))
        {
            if(!$stmt->execute())
            {
                $result='execute failed ('.$db->conn->error.')';
            }
        }
        else
        {
                $result='prepare failed ('.$db->conn->error.')';
        }
        $db->close();
        return $result;
    }
    
    
    function verifyDatabasePermissions($dbname)
    {
        $returnvalue=array('success'=>false,'connect'=>false,'create'=>false,'insert'=>false,'update'=>false,'delete'=>false,'drop'=>false,'log'=>array());

        $result=$this->testDatabaseConnection($dbname);
        if($result=='')
        {
            $returnvalue['log'][]='successful connection to database: '.$dbname;
            $returnvalue['connect']=true;

            $tablename='testtable'.rand(10000,99999);
            $result=$this->createTestTable($dbname,$tablename);
            if($result=='')
            {
                $returnvalue['log'][]='successful creation of test table: '.$tablename;
                $returnvalue['create']=true;

                $testvalue= 'test'.rand(10000,99999);
                $result=$this->insertIntoTestTable($dbname,$tablename,$testvalue);
                if($result=='')
                {
                    $returnvalue['log'][]='successful insert of test record '.$testvalue; 
                    $returnvalue['insert']=true;

                    $newvalue= 'updated'.rand(10000,99999);

                    $result=$this->updateTestTable($dbname, $tablename, $testvalue, $newvalue);
                    if($result=='')
                    {
                        $returnvalue['log'][]='successful update of test record to new value:'.$newvalue;
                        $returnvalue['update']=true;

                        $result=$this->dropTestTable($dbname, $tablename);
                        if($result=='')
                        {
                            $returnvalue['log'][]='successful drop of test table:'.$tablename;
                            $returnvalue['drop']=true;
                            $returnvalue['success']=true;
                        }
                        else
                        {
                            $returnvalue['log'][]='failed to drop test table ('.$result.')';
                            $returnvalue['success']=true; // its actually ok to not have drop permission - its not needed
                        }
                    }
                    else
                    {
                        $returnvalue['log'][]='failed to update record in test table ('.$result.'), but it does not really matter';
                    }
                }
                else 
                {
                    $returnvalue['log'][]='failed to insert record into test table ('.$result.')';
                }
            }
            else
            {
                $returnvalue['log'][]='failed to create test table ('.$result.')';
            }
        }
        else
        {
            $returnvalue['log'][]='failed to connect ('.$result.')';
        }
        return $returnvalue;
    }
    
    function testDatabaseConnection($dbname)
    {
        $db = new mysql; 
        $db->dbname=$dbname; 
        $connectresult=$db->connect(); // will return empty string if successful
        $db->close();
        return $connectresult;
    }
    
    function createTestTable($dbname,$tablename)
    {
        $result='';
        $db = new mysql;
        $db->dbname=$dbname;
        $db->connect(); // will return empty string if successful
        
        if($stmt=$db->conn->prepare('create table '.$tablename.' (id varchar(50))'))
        {
            if(!$stmt->execute())
            {
                $result='execute failed ('.$db->conn->error.')';
            }
        }
        else
        {
                $result='prepare failed ('.$db->conn->error.')';
        }
        $db->close();
        return $result;
    }

    
    function dropTestTable($dbname,$tablename)
    {
        $result='';
        $db = new mysql;
        $db->dbname=$dbname;
        $db->connect(); // will return empty string if successful
        
        if($stmt=$db->conn->prepare('drop table '.$tablename))
        {
            if(!$stmt->execute())
            {
                $result='execute failed ('.$db->conn->error.')';
            }
        }
        else
        {
                $result='prepare failed ('.$db->conn->error.')';
        }
        $db->close();
        return $result;
    }

    
    
    function insertIntoTestTable($dbname,$tablename,$value)
    {
        $result=''; 
        $db = new mysql;
        $db->dbname=$dbname;
        $db->connect(); // will return empty string if successful
        
        if($stmt=$db->conn->prepare('insert into '.$tablename.' values(?)'))
        {
            if($stmt->bind_param('s', $value)) 
            {
                if(!$stmt->execute())
                {
                    $result='execute failed ('.$db->conn->error.')';
                }
            }
            else
            {
                $result='bind failed ('.$db->conn->error.')';
            }
        }
        else
        {
                $result='prepare failed ('.$db->conn->error.')';
        }
        $db->close();
        return $result;
    }

    function deleteFromTestTable($dbname,$tablename,$value)
    {
        $result=''; 
        $db = new mysql;
        $db->dbname=$dbname;
        $db->connect(); // will return empty string if successful
        
        if($stmt=$db->conn->prepare('delete from '.$tablename.' where id=?'))
        {
            if($stmt->bind_param('s', $value)) 
            {
                if(!$stmt->execute())
                {
                    $result='execute failed ('.$db->conn->error.')';
                }
            }
            else
            {
                $result='bind failed ('.$db->conn->error.')';
            }
        }
        else
        {
                $result='prepare failed ('.$db->conn->error.')';
        }
        $db->close();
        return $result;
    }


    function updateTestTable($dbname,$tablename,$value,$newvalue)
    {
        $result=''; 
        $db = new mysql;
        $db->dbname=$dbname;
        $db->connect(); // will return empty string if successful
        
        if($stmt=$db->conn->prepare('update '.$tablename.' set id=? where id=?'))
        {
            if($stmt->bind_param('ss', $newvalue ,$value)) 
            {
                if(!$stmt->execute())
                {
                    $result='execute failed ('.$db->conn->error.')';
                }
            }
            else
            {
                $result='bind failed ('.$db->conn->error.')';
            }
        }
        else
        {
                $result='prepare failed ('.$db->conn->error.')';
        }
        $db->close();
        return $result;
    }
    
    function createTables($dbname)
    {
        $returnvalue=array('success'=>true,'log'=>array());
        $db = new mysql;
        $db->dbname=$dbname;
        $db->connect(); // will return empty string if successful

        $sql="CREATE TABLE application (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        oid varchar(255) not null default '',
        basevehicleid int unsigned null default 0,
        makeid int unsigned null default 0,
        equipmentid int unsigned null default 0,
        parttypeid int unsigned not null default 0,
        positionid int unsigned not null default 0,
        quantityperapp int unsigned not null default 1,
        partnumber varchar(255) not null default '',
        internalnotes text not null default '',
        status tinyint unsigned not null default 0,
        cosmetic tinyint unsigned not null default 0,
        appcategory int unsigned not null default 0,
        PRIMARY KEY (id),
        INDEX idx_oid (oid),
        INDEX idx_partnumber (partnumber),
        INDEX idx_basevehicleid (basevehicleid),
        INDEX idx_appcategory (appcategory)
        )";

        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - appcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed ('.$db->conn->error.')';}

        $sql="CREATE TABLE application_attribute (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        applicationid int unsigned null,
        `name` varchar(255) not null,
        `value` varchar(255) not null,
        `type` varchar(255) not null,
        sequence tinyint unsigned not null,
        cosmetic tinyint unsigned not null,
        PRIMARY KEY (id),
        INDEX idx_applicationid (applicationid)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - application_attribute ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - application_attribute ('.$db->conn->error.')';}
        
        
        $sql="CREATE TABLE application_asset (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        applicationid int unsigned null,
        assetid varchar(255) not null,
        representation varchar(255) not null,
        assetItemOrder tinyint unsigned not null,
        cosmetic tinyint unsigned not null,
        PRIMARY KEY (id),
        INDEX idx_applicationid (applicationid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - application_asset ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - application_asset ('.$db->conn->error.')';}

        $sql="CREATE TABLE asset (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        assetid varchar(255) NOT NULL,
        filename varchar(255) NOT NULL,
        uri text not null,
        localpath varchar(255) NOT NULL,
        orientationViewCode varchar(255) not null,
        colorModeCode varchar(255) not null,
        assetHeight int unsigned not null,
        assetWidth int unsigned not null,
        dimensionUOM varchar(255) not null,
        resolution int unsigned not null,
        background varchar(255) not null,
        fileType varchar(255) not null,
        createdDate date not null,
        public tinyint unsigned not null,
        approved tinyint unsigned not null,
        description text not null,
        oid varchar(255) not null,
        fileHashMD5 varchar(255) not null,
        filesize int unsigned not null,
	uripublic tinyint unsigned not null,
        PRIMARY KEY (id),
        INDEX idx_assetid (assetid),
        INDEX idx_oid (oid),
        INDEX idx_createdDate(createdDate),
        INDEX idx_fileType(fileType),
        INDEX idx_approved(approved),
        INDEX idx_fileHashMD5(fileHashMD5)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - asset ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - asset ('.$db->conn->error.')';}

        $sql="CREATE TABLE application_history (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        applicationid int unsigned null,
        eventdatetime datetime not null,
        userid int unsigned null,
        description text not null,
        new_oid varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_applicationid (applicationid),
        INDEX idx_eventdatetime (eventdatetime),
        INDEX idx_userid (userid),
        INDEX idx_new_oid (new_oid)       
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - application_history ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - application_history ('.$db->conn->error.')';}

        $sql="CREATE TABLE asset_history (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        assetid varchar(255) not null,
        eventdatetime datetime not null,
        userid int unsigned null,
        description text not null,
        new_oid varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_assetid (assetid),
        INDEX idx_eventdatetime (eventdatetime),
        INDEX idx_userid (userid),
        INDEX idx_new_oid (new_oid)       
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - asset_history ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - asset_history ('.$db->conn->error.')';}

        $sql="CREATE TABLE part_history (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(255) not null,
        eventdatetime datetime not null,
        userid int unsigned null,
        description text not null,
        new_oid varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber),
        INDEX idx_eventdatetime (eventdatetime),
        INDEX idx_userid (userid),
        INDEX idx_new_oid (new_oid)       
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_history ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_history ('.$db->conn->error.')';}

        $sql="CREATE TABLE oidmaster (
        oid varchar(255) not null,
        objecttype varchar(255) not null,
        status varchar(255) not null,
        tablekey int unsigned null,
        description text not null,
        datetimechanged datetime not null,
        userid tinyint unsigned not null,
        PRIMARY KEY (oid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - oidmaster ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - oidmaster ('.$db->conn->error.')';}
        
        $sql="CREATE TABLE backgroundjob (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        jobtype varchar(255) not null,
        status varchar(255) not null,
        userid tinyint unsigned not null,
        inputfile text not null,
        outputfile text not null,
        parameters text not null,
        datetimecreated datetime not null,
        datetimetostart datetime not null,
        datetimestarted datetime not null,
        datetimeended datetime not null,
        percentage decimal(4,1) not null,
        clientfilename varchar(255) not null,
        token varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_status (status))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - backgroundjob ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - backgroundjob ('.$db->conn->error.')';}
        
        $sql="CREATE TABLE backgroundjob_log (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        jobid int unsigned not null,
        eventtext text not null,
        timestamp datetime not null,
        PRIMARY KEY (id),
        INDEX idx_jobid (jobid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - backgroundjob_log ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - backgroundjob_log ('.$db->conn->error.')';}

        $sql="CREATE TABLE part (
        partnumber varchar(20) not null,
        partcategory int unsigned not null,
        parttypeid int unsigned not null,
        replacedby varchar(20) not null,
        lifecyclestatus varchar(255) not null,
        internalnotes text not null,
        description varchar(255) not null,
        GTIN varchar(255) not null,
        UNSPC varchar(255) not null,
        createdDate date not null,
        firststockedDate date not null,
        discontinuedDate date not null,
        oid varchar(255) not null,
        PRIMARY KEY (partnumber),
        INDEX idx_partcategory (partcategory),
        INDEX idx_parttypeid (parttypeid),
        INDEX idx_replacedby (replacedby),
        INDEX idx_oid (oid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part ('.$db->conn->error.')';}

        $sql="CREATE TABLE part_attribute (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(20) NOT NULL,
        PAID int unsigned not null,
        userDefinedAttributeName varchar(255) not null,
        `value` varchar(255) not null,
        uom  varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber),
        INDEX idx_PAID (PAID),
        INDEX idx_PAID_value (PAID,`value`),
        INDEX idx_userDefinedAttributeName_value(userDefinedAttributeName,`value`))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_attribute ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_attribute ('.$db->conn->error.')';}

        $sql="CREATE TABLE part_asset (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(20) NOT NULL,
        assetid varchar(255) NOT NULL,
        assettypecode varchar(255) not null,
        sequence int unsigned not null,
        representation varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber),
        INDEX idx_assetid (assetid)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_asset ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_asset ('.$db->conn->error.')';}
        
        $sql="CREATE TABLE part_description (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(20) NOT NULL,
        description varchar(255) NOT NULL,
        descriptioncode varchar(255) NOT NULL,
        sequence int unsigned NOT NULL,
        languagecode varchar(255) NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber),
        INDEX idx_descriptioncode_partnumber (descriptioncode, partnumber)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_description ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_description ('.$db->conn->error.')';}
                
        $sql="CREATE TABLE price (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(20) NOT NULL,
        pricesheetnumber varchar(255) NOT NULL,
        amount decimal(10,4) not null,
        currency varchar(3) NOT NULL,
        priceuom varchar(3) NOT NULL,	
        pricetype varchar(3) NOT NULL, 
        effectivedate date not null,
        expirationdate date not null,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber),
        INDEX idx_pricesheetnumber_partnumber (pricesheetnumber, partnumber),
        INDEX idx_effectivedate (effectivedate),
        INDEX idx_expirationdate (expirationdate)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - price ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - price ('.$db->conn->error.')';}
        
        $sql="CREATE TABLE package (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(20) NOT NULL,
        packageuom varchar(255) NOT NULL,
        quantityofeaches decimal(8,3) not null,
        innerquantity decimal(8,3) not null,
        innerquantityuom varchar(255) NOT NULL,
        weight decimal(8,3) not null,
        weightsuom varchar(255) NOT NULL,
        packagelevelGTIN varchar(255) NOT NULL,
        packagebarcodecharacters varchar(255) NOT NULL,
        shippingheight decimal(6,2) not null,
        shippingwidth decimal(6,2) not null,
        shippinglength decimal(6,2) not null,
        dimensionsuom varchar(255) NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - packages ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - packages ('.$db->conn->error.')';}


        $sql="CREATE TABLE interchange (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(20) NOT NULL,
        competitorpartnumber varchar(20) NOT NULL,
        brandAAIAID varchar(255) NOT NULL,
        interchangequantity decimal(8,3) not null,
        uom varchar(255) NOT NULL,
        interchangenotes text not null,
        internalnotes text not null,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber),
        INDEX idx_brandAAIAID_partnumber (brandAAIAID, partnumber),
        INDEX idx_competitorpartnumber (competitorpartnumber)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - interchange ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - interchange ('.$db->conn->error.')';}

        
        $sql="CREATE TABLE slice (
        sliceid varchar(255) NOT NULL,
        slicename varchar(255) NOT NULL,
        peerid int unsigned not null,
        expectedsyncperiod int unsigned not null,
        minsyncperiod int unsigned not null,
        lastsyncepochtime int unsigned not null,
        peeruri varchar(255) not null,
        peerusername varchar(255) not null,
        peerpassword varchar(255) not null,
        PRIMARY KEY (sliceid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - slice ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - slice ('.$db->conn->error.')';}

        $sql="CREATE TABLE slice_parttype (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        sliceid varchar(255) NOT NULL,
        parttypeid int unsigned not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - slice_parttype ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - slice_parttype ('.$db->conn->error.')';}

        $sql="CREATE TABLE slice_appcategory (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        sliceid varchar(255) NOT NULL,
        appcategoryid int unsigned not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - slice_appcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - slice_appcategory ('.$db->conn->error.')';}

        $sql="CREATE TABLE slice_partalias (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        sliceid varchar(255) NOT NULL,
        partnumber varchar(255) not null,
        peerpartnumber varchar(255) not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - slice_partalias ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - slice_partalias ('.$db->conn->error.')';}

        $sql="CREATE TABLE appcategory (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(255) not null,
        `logouri` varchar(255) not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - appcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - appcategory ('.$db->conn->error.')';}

        $sql="CREATE TABLE partcategory (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(255) not null,
        brandID varchar(255) not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - partcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - partcategory ('.$db->conn->error.')';}

        $sql="CREATE TABLE peer (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(255) not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - peer ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - peer ('.$db->conn->error.')';}

        $sql="CREATE TABLE Make (
        MakeID int UNSIGNED NOT NULL,
        MakeName varchar(255) not null,
        PRIMARY KEY (MakeID),
        INDEX idx_MakeName (MakeName))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - Make ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - Make ('.$db->conn->error.')';}

        $sql="CREATE TABLE parttype (
        id int UNSIGNED NOT NULL,
        `name` varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_name (`name`))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - parttype ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - parttype ('.$db->conn->error.')';}

        $sql="CREATE TABLE position (
        id int UNSIGNED NOT NULL,
        `name` varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_name (`name`))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - position ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - position ('.$db->conn->error.')';}

        $sql="CREATE TABLE user (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        status tinyint unsigned not null,
        failedcount tinyint unsigned not null,
        `name` varchar(255) not null,
        username varchar(255) not null,
        hash varchar(255) not null,
        PRIMARY KEY (id),
        unique key idx_username(username))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - user ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - user ('.$db->conn->error.')';}

        $sql="CREATE TABLE user_appcategory (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        userid int UNSIGNED NOT NULL,
        appcategory int unsigned not null,
        `permissionname` varchar(255) not null,
        `permissionvalue` tinyint unsigned not null,
        PRIMARY KEY (id),
        key idx_userid(userid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - user_appcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - user_appcategory ('.$db->conn->error.')';}

        $sql="CREATE TABLE user_selected_appcategory (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        userid int UNSIGNED NOT NULL,
        appcategory int unsigned not null,
        PRIMARY KEY (id),
        key idx_userid(userid),
        key idx_appcategory(appcategory))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - user_selected_appcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - user_selected_appcategory ('.$db->conn->error.')';}
        
        $sql="CREATE TABLE user_permission (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        userid int UNSIGNED NOT NULL,
        `permissionname` varchar(255) not null,
        `permissionvalue` tinyint unsigned not null,
        PRIMARY KEY (id),
        key idx_userid(userid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - user_permission ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - user_permission ('.$db->conn->error.')';}

        $sql="CREATE TABLE config (
        configname varchar(255) not null,
        configvalue varchar(255) not null,
        PRIMARY KEY (configname))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - config ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - config ('.$db->conn->error.')';}

        $sql="CREATE TABLE system_history (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        eventdatetime datetime not null,
        eventtype varchar(255) not null,
        userid int unsigned null,
        description text not null,
        PRIMARY KEY (id),
        INDEX idx_eventdatetime (eventdatetime),
        INDEX idx_eventtype (eventtype),
        INDEX idx_userid (userid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - system_history ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - system_history ('.$db->conn->error.')';}

        
        $sql="CREATE TABLE alert (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        alertdatetime datetime not null,
        alerttype varchar(255) not null,
        description text not null,
        PRIMARY KEY (id),
        INDEX idx_alertdatetime (alertdatetime),
        INDEX idx_alerttype (alerttype))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - alert ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - alert ('.$db->conn->error.')';}
        
        
        $sql="CREATE TABLE alert_application (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        applicationid int unsigned not null,
        PRIMARY KEY (id),
        INDEX idx_applicationid (applicationid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - alert_application ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - alert_application ('.$db->conn->error.')';}

        $sql="CREATE TABLE alert_part (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - alert_part ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - alert_part ('.$db->conn->error.')';}
        
        $sql="CREATE TABLE alert_asset (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        assetid varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_assetid (assetid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - alert_asset ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - alert_asset ('.$db->conn->error.')';}
        
        $sql="CREATE TABLE fitmentnote (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        note varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_note (note))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - fitmentnote ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - fitmentnote ('.$db->conn->error.')';}
        
   
        $sql="CREATE TABLE receiverprofile (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        status tinyint unsigned not null,
        `name` varchar(255) not null,
        `data` text not null,
        intervaldays int unsigned not null,
        lastexport date not null,
        notes text not null,
        PRIMARY KEY (id),
        INDEX idx_name (`name`))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - receiverprofile ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - receiverprofile ('.$db->conn->error.')';}

        
        $sql="CREATE TABLE receiverprofile_marketingcopy (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        receiverprofileid int UNSIGNED NOT NULL,
        marketcopycontent text not null,
        marketcopycode varchar(255) not null,
        marketcopyreference varchar(255) not null,
        marketcopytype varchar(255) not null,
        recordsequence tinyint unsigned not null,
        languagecode varchar(255) not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - receiverprofile_marketingcopy ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - receiverprofile_marketingcopy ('.$db->conn->error.')';}


        $sql="CREATE TABLE receiverprofile_appcategory (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        receiverprofileid int UNSIGNED NOT NULL,
        appcategory int unsigned not null default 0,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - receiverprofile_appcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - receiverprofile_appcategory ('.$db->conn->error.')';}

        $sql="CREATE TABLE receiverprofile_parttype (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        receiverprofileid int UNSIGNED NOT NULL,
        parttypeid int unsigned not null default 0,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - receiverprofile_parttype ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - receiverprofile_parttype ('.$db->conn->error.')';}

        $sql="CREATE TABLE autocare_databases (
        databasename varchar(255) not null,
        databasetype varchar(255) not null,
        versiondate date not null,
        PRIMARY KEY (databasename))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - autocare_databases ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - autocare_databases ('.$db->conn->error.')';}
        
        
        $db->close();
        return $returnvalue;
    }



// see what schema version is claimed by pim
    
// determine what permissions we have on the database by creating a random-named db and table and interacting with it

// create pim database and tables with minimal defaults inserted 
    
}
?>