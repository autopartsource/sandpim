<?php
include_once("mysqlClass.php");
//GRANT create ON *.* TO webservice@'localhost';
class setup
{

    
    function databaseNameExists($dbname)
    {
        $result=false;
        $db = new mysql;
        $connectresult=$db->connect_nodb(); // will return empty string if successful
        if($connectresult=='')
        {
            if($stmt=$db->conn->prepare('select schema_name from information_schema.schemata where schema_name =?'))
            {
                if($stmt->bind_param('s', $dbname))
                {
                    if($stmt->execute())
                    {
                        $db->result = $stmt->get_result();
                        if($row = $db->result->fetch_assoc())
                        {
                            $result=true;
                        }
                    }
                }
            }
            $db->close();
        }
        return $result;
    }
 
 
    function databaseTableCount($dbname)
    {
        $result=-1;
        $db = new mysql;
        $db->dbname=$dbname;
 
        $connectresult=$db->connect(); // will return empty string if successful
        if($connectresult=='')
        {
            if($stmt=$db->conn->prepare('select table_name from information_schema.tables where TABLE_SCHEMA=?'))
            {
                if($stmt->bind_param('s', $dbname))
                {
                    if($stmt->execute())
                    {
                        $result=0;
                        $db->result = $stmt->get_result();
                        while($row = $db->result->fetch_assoc())
                        {
                            $result++;
                        }
                    }
                }
            }
            $db->close();
        }
        return $result;
    }
    
    
    
    
    function createDatebase($dbname)
    {
        $db = new mysql; 
        $db->dbname=$dbname; 
        $db->connect_nodb(); // will return empty string if successful
        $result='';
        
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

        
        
        $sql="CREATE TABLE allowedhosts (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        address varchar(255) not null,
        description varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_address(address))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - allowedhosts ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - allowedhosts ('.$db->conn->error.')';}
        $sql="insert into allowedhosts values(null,'*.*.*.*','Allow All (delete this rule to restrict clients by address)');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into allowedhosts values(null,'127.0.0.1','Allow localhost');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into allowedhosts values(null,'10.*.*.*','Allow any address in Bogon Class A Networks');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into allowedhosts values(null,'172.12.*.*','Allow any address in Bogon /12 Networks');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into allowedhosts values(null,'192.168.*.*','Allow any address in Bogon Class B Networks');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into allowedhosts values(null,'192.168.1.*','Allow any address in Bogon Class C Networks');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into allowedhosts values(null,'192.168.1.100','Allow a specific address');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
        
        
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
        PRIMARY KEY (id),
        INDEX idx_oid (oid),
        INDEX idx_partnumber (partnumber),
        INDEX idx_basevehicleid (basevehicleid)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - application ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed ('.$db->conn->error.')';}

        $sql="CREATE TABLE application_attribute (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        applicationid int unsigned null,
        `name` varchar(255) not null,
        `value` varchar(255) not null,
        `type` varchar(255) not null,
        sequence tinyint unsigned not null,
        cosmetic tinyint unsigned not null,
        PRIMARY KEY (id),
        INDEX idx_type_value (`type`,`value`),
        INDEX idx_name_value (`name`,`value`),
        INDEX idx_type_name_value (`type`,`name`,`value`),
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
        languagecode varchar(255) NOT NULL,
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

        
        
        $sql="CREATE TABLE part_application_summary (
        partnumber varchar(255) not null,
        summary text not null,
        capturedatetime datetime not null,
        PRIMARY KEY (partnumber),
        INDEX idx_capturedatetime (capturedatetime)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_application_summary ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_application_summary ('.$db->conn->error.')';}

        
        
        
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
        contenttype varchar(255) not null,
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
               
        $sql="CREATE TABLE part_balance (
        partnumber varchar(20) NOT NULL,
        qoh decimal(10,2) not null,
        amd decimal(10,2) not null,
        updateddate date not null,
        PRIMARY KEY (partnumber),
        INDEX idx_updateddate (updateddate)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_balance ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_balance ('.$db->conn->error.')';}
        
        
        
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

        $sql="CREATE TABLE pricesheet (
        pricesheetnumber varchar(255) NOT NULL,
        description varchar(255) NOT NULL,
        pricetype varchar(255) NOT NULL,
        currency varchar(3) NOT NULL,
        effectivedate date not null,
        expirationdate date not null,
        PRIMARY KEY (pricesheetnumber))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - pricesheet ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - pricesheet ('.$db->conn->error.')';}


        
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


        $sql="CREATE TABLE partcategory (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(255) not null,
        brandID varchar(255) not null,
        subbrandID varchar(255) not null,
        mfrlabel varchar(255) not null,
        logouri varchar(255) not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - partcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - partcategory ('.$db->conn->error.')';}

/* 11/7/2020 - probably won't need this 

        $sql="CREATE TABLE peer (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(255) not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - peer ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - peer ('.$db->conn->error.')';}
*/
        $sql="CREATE TABLE Make (
        MakeID int UNSIGNED NOT NULL,
        MakeName varchar(255) not null,
        PRIMARY KEY (MakeID),
        INDEX idx_MakeName (MakeName))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - Make ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - Make ('.$db->conn->error.')';}
        $sql="insert into Make values(54,'Ford');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into Make values(47,'Chevrolet');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into Make values(39,'Chrysler');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
                
        $sql="CREATE TABLE parttype (
        id int UNSIGNED NOT NULL,
        `name` varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_name (`name`))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - parttype ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - parttype ('.$db->conn->error.')';}
        $sql="insert into parttype values(1684,'Disc Brake Pad Set');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into parttype values(1896,'Disc Brake Rotor');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into parttype values(1688,'Drum Brake Shoe');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into parttype values(1744,'Brake Drum');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
        
        $sql="CREATE TABLE position (
        id int UNSIGNED NOT NULL,
        `name` varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_name (`name`))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - position ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - position ('.$db->conn->error.')';}
        $sql="insert into position values(22,'Front');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into position values(30,'Rear');"; $stmt=$db->conn->prepare($sql); $stmt->execute();

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

        $sql="CREATE TABLE config_options (
        configname varchar(255) not null,
        format varchar(255) not null,
        validvalues text not null,
        defaultvalue varchar(255) not null,
        description text not null,
        PRIMARY KEY (configname))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - config_options ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - config_options ('.$db->conn->error.')';}
   
        $sql="insert into config_options values('AutoCareResourceListURI','A1/255','','https://aps.dev/sandpim/AutoCareTechnology.php','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('AutoCareFTPserver','A1/255','','52.168.10.67','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('AutoCareFTPusername','A1/255','','','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('AutoCareFTPpassword','A1/255','','','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('AutoCareDownloadsDirectory','A1/255','','/var/www/html/autocaredownloads','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('defaultDescriptionLanguageCode','A1/255','','EN','This code will be auto-selected on the parts page for creating a new description');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('defaultDescriptionTypeCode','A1/255','','LAB','This code will be auto-selected on the parts page for creating a new description');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('localImageStorePath','A1/255','','/var/www/html/images','absolute path for storing image assets');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('logPreviewDescriptionLength','','','80','how many characters are displayed from log entry in a preview list');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('photoAssetHostURI','','','https://s3.amazonaws.com/autopartsourceimages/parts/','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('storeImageAssetsLocally','','','1','0 or 1 indicating local saving of image assets');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('systemDocRootRUL','','','/var/www/html','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('requireCredentialsForBalanceUpdate','','','/var/www/html','Controls how updatePartBalancesAutomated API operates. Is set to yes, a valid username/password (any) are required along in the POST of balance data.');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('VIOdefaultGeography','','','US','If Experian VIO is used, this is the geography used for determining application vehicle counts and PIO');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('VIOdefaultYearQuarter','','','2021Q2','If Experian VIO is used, this is the year+quarter used for determining application vehicle counts and PIO');"; $stmt=$db->conn->prepare($sql); $stmt->execute();

        
        $sql="CREATE TABLE issue (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        status int UNSIGNED NOT NULL,
        issuedatetime datetime not null,
        issuetype varchar(255) not null,
        issuekeyalpha varchar(255) not null,
        issuekeynumeric int UNSIGNED NOT NULL,
        description text not null,
        notes text,
        source varchar(255) not null,
        issuehash varchar(255) not null,
        PRIMARY KEY (id),INDEX idx_issuehash (issuehash),INDEX idx_type_keyalpha (issuetype,issuekeyalpha), INDEX idx_type_keynumeric (issuetype,issuekeynumeric) )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - issues table ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - issues table ('.$db->conn->error.')';}
        
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
        
        $sql="CREATE TABLE receiverprofile_deliverygroup (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        receiverprofileid int UNSIGNED NOT NULL,
        deliverygroupid int unsigned not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - receiverprofile_deliverygroup ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - receiverprofile_deliverygroup ('.$db->conn->error.')';}

        $sql="CREATE TABLE receiverprofile_parttranslation (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        receiverprofileid int UNSIGNED NOT NULL,
        internalpart varchar(255) not null,
        externalpart varchar(255) not null,
        PRIMARY KEY (id),
        unique key idx_receiver_internalpart(receiverprofileid,internalpart))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - receiverprofile_parttranslation ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - receiverprofile_parttranslation ('.$db->conn->error.')';}
        
        // contemplating schema options to address plan-user connections
        // this table may not be the final answer
        $sql="CREATE TABLE plan_user (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        planid int UNSIGNED NOT NULL,
        userid int UNSIGNED NOT NULL,
        role varchar(255) not null,
        PRIMARY KEY (id),
        index idx_planid(planid),
        index idx_userid(userid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - plan_user ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - plan_user ('.$db->conn->error.')';}      
        
        
        $sql="CREATE TABLE plan (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        description varchar(255) not null,      
        planuuid varchar(255) not null,
        receiverprofileid int UNSIGNED NOT NULL,
        planmetadata text not null,
        plandocument text not null,
        status varchar(255) not null,
        planstatuson datetime,
        primaryapprovedon datetime,
        secondaryapprovedon datetime,
        PRIMARY KEY (id),
        unique key idx_planuuid(planuuid),
        index idx_receiverprofileid(receiverprofileid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - plan ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - plan ('.$db->conn->error.')';}      
        
        //each record here is a subscription
        $sql="CREATE TABLE plan_slice (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        planid int UNSIGNED NOT NULL,
        sliceid int unsigned not null,
        subscriptionuuid varchar(255) not null,
        subscriptionmetadata text not null,
        sliceorder int UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        unique key idx_subscriptionuuid(subscriptionuuid),
        index idx_planid(planid,sliceorder))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - plan_partcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - plan_partcategory ('.$db->conn->error.')';}


        $sql="CREATE TABLE slice (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        description varchar(255) not null,
        sliceuuid varchar(255) not null,
        slicetype varchar(255) not null,
        filename varchar(255) not null,
        partcategory int unsigned not null,
        slicemetadata text not null,
        slicehash varchar(255) not null,        
        PRIMARY KEY (id),
        unique key idx_sliceuuid(sliceuuid),
        index idx_partcategory(partcategory))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - plan_partcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - plan_partcategory ('.$db->conn->error.')';}

        
        $sql="CREATE TABLE slice_filegrain (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        sliceid int unsigned not null,
        grainid int unsigned not null,
        grainorder int unsigned not null,        
        PRIMARY KEY (id),
        index idx_sliceid(sliceid,grainorder),
        index idx_grain(grainid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - slice_filegrain ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - slice_filegrain ('.$db->conn->error.')';}
        
        // grains that are full files
        // Only level-1 grains (files) are literally stored - and this table is what they stored as base64 text.
        // Level2 grains are synthesized on the fly from PIM content and not store in a tabel.
        $sql="CREATE TABLE filegrain (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        grainuuid varchar(255) not null,
        grainkey  varchar(255) not null,
        source  varchar(255) not null,
        encoding  varchar(255) not null,
        payload longtext not null,
        timestamp datetime not null,
        PRIMARY KEY (id),
        index idx_grainkey(grainkey),
        index idx_grainuuid(grainuuid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - sandpiperlevel1grains ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - sandpiperlevel1grains ('.$db->conn->error.')';}


        
        $sql="CREATE TABLE sandpiperactivity (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        planuuid varchar(255) not null,
        subscriptionuuid varchar(255) not null,
        grainuuid varchar(255) not null,
        action varchar(255) not null,
        timestamp datetime not null,
        PRIMARY KEY (id),
        index idx_planuuid(planuuid),
        index idx_subscriptionuuid(subscriptionuuid),
        index idx_grainuuid(grainuuid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - sandpiperactivity ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - sandpiperactivity ('.$db->conn->error.')';}
        
        
        
        // a collections of partcategories that go together 
        // "send me your brand X data" would likely require multiple (maybe dozens) of part categories
        // putting them in a collection (deliverygroup) allows repeatability over
        // many receivers without having to manually maintain lists of part categories for each.
        $sql="CREATE TABLE deliverygroup (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        description varchar(255) not null,      
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - deliverygroup ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - deliverygroup ('.$db->conn->error.')';}

        $sql="CREATE TABLE deliverygroup_partcategory (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        deliverygroupid int unsigned not null,
        partcategory int unsigned not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - deliverygroup_partcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - deliverygroup_partcategory ('.$db->conn->error.')';}
        
        
        
        
        $sql="CREATE TABLE user_partcategory (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        userid int UNSIGNED NOT NULL,
        partcategory int UNSIGNED NOT NULL,
        `permissionname` varchar(255) not null,
        `permissionvalue` tinyint unsigned not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - user_partcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - user_partcategory ('.$db->conn->error.')';}

        $sql="CREATE TABLE user_selected_partcategory (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        userid int UNSIGNED NOT NULL,
        partcategory int UNSIGNED NOT NULL,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - user_selected_partcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - user_selected_partcategory ('.$db->conn->error.')';}

        
        $sql="CREATE TABLE brand (
        BrandID varchar(255) not null,
        BrandName varchar(255) not null,
        BrandOwnerID varchar(255) not null,
        BrandOwner varchar(255) not null,
        ParentID varchar(255) not null,
        ParentCompany varchar(255) not null,
        PRIMARY KEY (BrandID),
        INDEX idx_BrandName (BrandName))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - brand ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - brand ('.$db->conn->error.')';}

        $sql="CREATE TABLE competitivebrand (
        brandAAIAID varchar(255) not null,
        description varchar(255) not null,
        PRIMARY KEY (brandAAIAID))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - competitivebrand ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - competitivebrand ('.$db->conn->error.')';}

        $sql="CREATE TABLE autocare_databases (
        databasename varchar(255) not null,
        databasetype varchar(255) not null,
        versiondate date not null,
        PRIMARY KEY (databasename))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - autocare_databases ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - autocare_databases ('.$db->conn->error.')';}
  

        $sql="CREATE TABLE clipboard (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        userid int unsigned null,
        description varchar(255) not null,
        objecttype varchar(255) not null,
        objectkey varchar(255) not null,
        objectdata text not null,
        capturedate date not null,
        PRIMARY KEY (id),
        INDEX idx_userid_objecttype (userid,objecttype))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - clipboard ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - clipboard ('.$db->conn->error.')';}


        
        $sql="CREATE TABLE documentation (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        language varchar(255) not null,
        path varchar(255) not null,
        doctext text not null,
        sequence int unsigned not null,
        PRIMARY KEY (id),
        unique key idx_language_path(language,path))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - documentation ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - documentation ('.$db->conn->error.')';}

        $sql="insert into documentation values(null,'EN','Apps/Show App/Fitment Quantity','The quantity of this part used on the particular position on this vehicle. For example, a six cylinder engine would have a quantity of 6 spark plugs. A front brake rotor would have a quantity of 2. A Front-Right brake rotor would have a quantity of 1.',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into documentation values(null,'EN','Apps/Show App/Fitment Assets/Representation','Assets can be used to explain (or sometime qualify) an application of a part to a vehicle. A video, photo or diagram could depict the specific part and vehilce described by this app, or it could be more generic and intended to represent many similar situations. This is the distinction between Actual and Representitive. This element is part of data exported to an ACES file.',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into documentation values(null,'EN','Apps/Show App/Fitment Assets/Sequence','Assets can be used to explain (or sometime qualify) an application of a part to a vehicle. An example of this is an exhaust diagram. The diagram could show a dozen different parts in a system and only one of them is the one in this application. That one part among the dozen is differentiated by its unique sequence number',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into documentation values(null,'EN','Apps/Show App/Internal Notes','These notes are internal and for the use of the product/catalog manager. They will not be exported to a reciever. Free-form notes that are intended to qualify or describe the application can be created in the Fitment Qualifiers area.',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into documentation values(null,'EN','Apps/Show App/Cosmetic','Marking an app as Cosmetic will prevent it from being exported in an ACES file. Cosmetic apps fill-in holes implied in a matrix (printed page) presentation of the content.',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        

        $sql="CREATE TABLE kpi (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        description varchar(255) NOT NULL,
        type varchar(255) NOT NULL,
        timeunits varchar(255) NOT NULL,
        horizonunits int unsigned not null,
        rangemin decimal(10,2) not null,
        rangemax decimal(10,2) not null,
        target decimal(10,2) not null,
        datakey varchar(255) NOT NULL,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - kpi ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - kpi ('.$db->conn->error.')';}
             
        

        $sql="CREATE TABLE metrics (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        datakey varchar(255) NOT NULL,
        capturedate date not null,
        metric decimal(10,2) not null,
        PRIMARY KEY (id),
        INDEX idx_datakey_capturedate (datakey,capturedate))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - metrics ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - metrics ('.$db->conn->error.')';}
             
        $sql="CREATE TABLE experianVIO (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        yearQuarter varchar(255) NOT NULL,
        geography varchar(255) NOT NULL,
        vehicleID int unsigned null,
        baseVehicleID int unsigned null,
        yearID int unsigned null,
        makeID int unsigned null,
        modelID int unsigned null,
        subModelID int unsigned null,
        bodyTypeID int unsigned null,
        bodyNumDoorsID int unsigned null,
        driveTypeID int unsigned null,
        fuelTypeID int unsigned null,
        engineBaseID int unsigned null,
        engineVINID int unsigned null,
        fuelDeliverySubTypeID int unsigned null,
        transControlTypeID int unsigned null,
        transNumSpeedID int unsigned null,
        aspirationID int unsigned null,
        vehicleTypeID int unsigned null,
        vehicleCount int unsigned null,
        PRIMARY KEY (id),
        INDEX idx_yearQuarter_geography_baseVehicleID (yearQuarter,geography,baseVehicleID))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - experianVIO ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - experianVIO ('.$db->conn->error.')';}

        $sql="CREATE TABLE part_VIO (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(255) NOT NULL,
        yearQuarter varchar(255) NOT NULL,
        geography varchar(255) NOT NULL,
        capturedate date not null,
        vehicleCount int unsigned null,        
        PRIMARY KEY (id),
        INDEX idx_part_geo_yrQtr (partnumber, geography, yearQuarter),
        INDEX idx_capturedate (capturedate))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_VIO ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_VIO ('.$db->conn->error.')';}      
        
        $db->close();
        return $returnvalue;
    }

    
    
    


// see what schema version is claimed by pim
    
// determine what permissions we have on the database by creating a random-named db and table and interacting with it

// create pim database and tables with minimal defaults inserted 
    
}
?>