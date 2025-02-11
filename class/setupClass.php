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

        $result=$this->testDatabaseVerbose($dbname);
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
    
    function testDatabase()
    {
        $db = new mysql; 
        return($db->testConnection());
    }


    function testDatabaseVerbose($dbname)
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
        assetlabel varchar(255) NOT NULL,
        changedDate date not null,
        frame int unsigned not null,
        totalFrames int unsigned not null,
        plane int unsigned not null,
        totalPlanes int unsigned not null,
        PRIMARY KEY (id),
        INDEX idx_assetid (assetid),
        INDEX idx_oid (oid),
        INDEX idx_createdDate(createdDate),
        INDEX idx_fileType(fileType),
        INDEX idx_approved(approved),
        INDEX idx_fileHashMD5(fileHashMD5)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - asset ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - asset ('.$db->conn->error.')';}
        $sql="insert into asset values(30000,'PRC914','PRC914.jpg','https://s3.amazonaws.com/autopartsourceimages/parts/PRC914.jpg','','TOP','RGB',733,1500,'PX',300,'WHI','JPG','2021-10-01',1,1,'Primary photo of PRC914','','',501478,1,'EN','',now(),0,0,0,0)"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into asset values(30001,'PRC914A','PRC914.jpg','https://s3.amazonaws.com/autopartsourceimages/parts/PRC914A.jpg','','TOP','RGB',726,1500,'PX',300,'WHI','JPG','2021-10-02',1,1,'Primary photo of PRC914A','','',202050,1,'EN','',now(),0,0,0,0)"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into asset values(30002,'PRC914B','PRC914.jpg','https://s3.amazonaws.com/autopartsourceimages/parts/PRC914B.jpg','','TOP','RGB',764,1500,'PX',300,'WHI','JPG','2021-10-03',1,1,'Primary photo of PRC914B','','',213594,1,'EN','',now(),0,0,0,0)"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        


        $sql="CREATE TABLE assetlabel (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        labeltext varchar(255) NOT NULL,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - pricesheet ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - assetlabel ('.$db->conn->error.')';}

        $sql="insert into assetlabel values(null,'ASSMGUIDE_PACKING')"; $stmt=$db->conn->prepare($sql); $stmt->execute(); 
        
        
        $sql="CREATE TABLE assettag (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        tagtext varchar(255) NOT NULL,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - assettag ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - assetlabel ('.$db->conn->error.')';}
        $sql="insert into assettag values(null,'MARKETPLACE')"; $stmt=$db->conn->prepare($sql); $stmt->execute(); 
        
        $sql="CREATE TABLE asset_assettag (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        assetid varchar(255) NOT NULL,
        assettagid int UNSIGNED NOT NULL, 
        PRIMARY KEY (id),
        INDEX idx_assetid (assetid),
        INDEX idx_assettagid (assettagid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - asset_assettag ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - assetlabel ('.$db->conn->error.')';}
        
        $sql="CREATE TABLE receiverprofile_assettag (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        receiverprofileid int UNSIGNED NOT NULL,
        assettagid int UNSIGNED NOT NULL, 
        PRIMARY KEY (id),
        index idx_receiverprofileid(receiverprofileid),
        INDEX idx_assettagid (assettagid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - receiverprofile_lifecycleststus ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - receiverprofile_lifecycleststus ('.$db->conn->error.')';}
        
                
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

        $sql="CREATE TABLE vehicle_history (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        basevehicleid int unsigned null default 0,
        userid int unsigned null,
        eventdatetime datetime not null,
        description text not null,
        PRIMARY KEY (id),
        INDEX idx_basevehicleid (basevehicleid),
        INDEX idx_userid (userid),
        INDEX idx_eventdatetime (eventdatetime)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - vehicle_history ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - vehicle_history ('.$db->conn->error.')';}
                
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
        firstyear int unsigned not null,
        lastyear int unsigned not null,
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
        partnumber varchar(255) not null,
        partcategory int unsigned not null,
        parttypeid int unsigned not null,
        replacedby varchar(255) not null,
        lifecyclestatus varchar(255) not null,
        internalnotes text not null,
        description varchar(255) not null,
        GTIN varchar(255) not null,
        UNSPC varchar(255) not null,
        createdDate date not null,
        firststockedDate date not null,
        discontinuedDate date not null,
        oid varchar(255) not null,
        basepart varchar(255) not null,
        PRIMARY KEY (partnumber),
        INDEX idx_partcategory (partcategory),
        INDEX idx_parttypeid (parttypeid),
        INDEX idx_replacedby (replacedby),
        INDEX idx_oid (oid),
        INDEX idx_basepart (basepart))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part ('.$db->conn->error.')';}

        $sql="insert into part values('PRC914',10,1684,'','2','','','841929101122','','2021-09-30','2000-01-01','2000-01-01','','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into part values('PRC914A',10,1684,'','2','','','841929127160','','2021-10-01','2000-01-01','2000-01-01','','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into part values('PRC914B',10,1684,'','1','','','841929127177','','2021-10-02','2000-01-01','2000-01-01','','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
                
        $sql="CREATE TABLE part_attribute (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(255) NOT NULL,
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
        partnumber varchar(255) NOT NULL,
        assetid varchar(255) NOT NULL,
        assettypecode varchar(255) not null,
        sequence int unsigned not null,
        representation varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber),
        INDEX idx_assetid (assetid)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_asset ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_asset ('.$db->conn->error.')';}
        
        $sql="insert into part_asset values(1,'PRC914','PRC914','P04',1,'A')"; $stmt=$db->conn->prepare($sql); $stmt->execute();        
        $sql="insert into part_asset values(2,'PRC914A','PRC914A','P04',1,'A')"; $stmt=$db->conn->prepare($sql); $stmt->execute();        
        $sql="insert into part_asset values(3,'PRC914B','PRC914B','P04',1,'A')"; $stmt=$db->conn->prepare($sql); $stmt->execute();        
        
        
        
        $sql="CREATE TABLE part_description (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(255) NOT NULL,
        description text NOT NULL,
        descriptioncode varchar(255) NOT NULL,
        sequence int unsigned NOT NULL,
        languagecode varchar(255) NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber),
        INDEX idx_descriptioncode_partnumber (descriptioncode, partnumber)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_description ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_description ('.$db->conn->error.')';}

        $sql="insert into part_description values(1,'PRC914','Pad Set W/Hardware','SHO',1,'EN')"; $stmt=$db->conn->prepare($sql); $stmt->execute();        
        $sql="insert into part_description values(2,'PRC914','Ceraamic Pad Set With Hardware','DES',1,'EN')"; $stmt=$db->conn->prepare($sql); $stmt->execute();        
        

        $sql="CREATE TABLE part_expi (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(255) NOT NULL,
        EXPIcode varchar (255) not null,
        EXPIvalue varchar(255) not null,
        languagecode varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber),
        INDEX idx_EXPIcode (EXPIcode),
        INDEX idx_partnumber_EXPIcode (partnumber,EXPIcode))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_expi  ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_expi  ('.$db->conn->error.')';}

        $sql="insert into part_expi values(null,'PRC914','CTO','CA','EN')"; $stmt=$db->conn->prepare($sql); $stmt->execute(); 
        
        // for the non-core part-level attributes. The core stuff (that we care about) is in the part table
        $sql="CREATE TABLE part_PIESitem (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(255) NOT NULL,
        ReferenceFieldNumber varchar (255) not null,
        value varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber),
        INDEX idx_ReferenceFieldNumber (ReferenceFieldNumber))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_PIESitem  ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_PIESitem  ('.$db->conn->error.')';}

        $sql="insert into part_PIESitem values(null,'PRC914','B40','1')"; $stmt=$db->conn->prepare($sql); $stmt->execute(); 
        $sql="insert into part_PIESitem values(null,'PRC914','B41','EA')"; $stmt=$db->conn->prepare($sql); $stmt->execute(); 





        
        $sql="CREATE TABLE part_balance (
        partnumber varchar(255) NOT NULL,
        qoh decimal(10,2) not null,
        amd decimal(10,2) not null,
        cost decimal(10,2) not null,
        updateddate date not null,
        PRIMARY KEY (partnumber),
        INDEX idx_updateddate (updateddate)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_balance ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_balance ('.$db->conn->error.')';}
        


        $sql="CREATE TABLE partrelationship (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        leftpartnumber varchar(255) NOT NULL,
        rightpartnumber varchar(255) NOT NULL,
        relationtype varchar(255) NOT NULL,
        units decimal(10,2) not null,
        sequence int unsigned NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_leftpartnumber (leftpartnumber),
        INDEX idx_rightpartnumber (rightpartnumber)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - partrelationship ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - partrelationship ('.$db->conn->error.')';}
        
        
        $sql="CREATE TABLE price (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(255) NOT NULL,
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

        $sql="insert into price values(1,'PRC914','WDNET2021',12.34,'USD','EA','NET','2021-01-01','2021-12-31')"; $stmt=$db->conn->prepare($sql); $stmt->execute(); 
        
        
        $sql="CREATE TABLE pricesheet (
        pricesheetnumber varchar(255) NOT NULL,
        description varchar(255) NOT NULL,
        pricetype varchar(255) NOT NULL,
        currency varchar(3) NOT NULL,
        effectivedate date not null,
        expirationdate date not null,
        PRIMARY KEY (pricesheetnumber))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - pricesheet ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - pricesheet ('.$db->conn->error.')';}

        $sql="insert into pricesheet values('WDNET2021','WD Net Pricelist for 2021 (USD)','NET','USD','2021-01-01','2021-12-31')"; $stmt=$db->conn->prepare($sql); $stmt->execute(); 
        
                
        $sql="CREATE TABLE package (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(255) NOT NULL,
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
        merchandisingheight decimal(6,2) not null,
        merchandisingwidth decimal(6,2) not null,
        merchandisinglength decimal(6,2) not null,
        dimensionsuom varchar(255) NOT NULL,
        orderable varchar(10) NOT NULL,        
        PRIMARY KEY (id),
        INDEX idx_partnumber (partnumber)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - packages ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - packages ('.$db->conn->error.')';}

        $sql="insert into package values(1,'PRC914','EA',1,1,'EA',4.21,'PG','','',3,8,4,3,8,4,'IN','Y')";  $stmt=$db->conn->prepare($sql); $stmt->execute();

        $sql="CREATE TABLE interchange (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partnumber varchar(255) NOT NULL,
        competitorpartnumber varchar(255) NOT NULL,
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
        marketcopy text not null,
        fab text not null,
        warranty text not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - partcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - partcategory ('.$db->conn->error.')';}

        $sql="insert into partcategory values(10,'AmeriPRO - Ceramic','BKJT','','Ceramic','','Placeholder market copy','Features and benefits placeholder','warranty statement placeholder');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into partcategory values(11,'AmeriPRO - Metalic','BKJT','','Metalic','','Placeholder market copy','Features and benefits placeholder','warranty statement placeholder');"; $stmt=$db->conn->prepare($sql); $stmt->execute();

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
        environment varchar(255),
        PRIMARY KEY (id),
        unique key idx_username(username))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - user ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - user ('.$db->conn->error.')';}

        $sql="CREATE TABLE usertype (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        `typename` varchar(255) not null,
        PRIMARY KEY (id),
        key idx_userid(id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - usertype ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - usertype ('.$db->conn->error.')';}
        
        
        $sql="CREATE TABLE usertype_permission (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        usertypeid int UNSIGNED NOT NULL,
        `permissionname` varchar(255) not null,
        `permissionvalue` tinyint unsigned not null,
        PRIMARY KEY (id),
        key idx_usertypeid(usertypeid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - usertype_permission ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - usertype_permission ('.$db->conn->error.')';}

        $sql="CREATE TABLE config (
        configname varchar(255) not null,
        configvalue varchar(255) not null,
        PRIMARY KEY (configname))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - config ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - config ('.$db->conn->error.')';}
        $sql="insert into config values('AutoCareResourceListURI','https://aps.dev/sandpim/AutoCareTechnology.php');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config values('AutoCareFTPserver','52.168.10.67');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config values('AutoCareDownloadsDirectory','/var/www/html/autocaredownloads');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config values('vcdbProductionDatabase','vcdb');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config values('pcdbProductionDatabase','pcdb');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config values('padbProductionDatabase','padb');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config values('qdbProductionDatabase','qdb');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config values('defaultDescriptionLanguageCode','EN');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config values('defaultDescriptionTypeCode','LAB');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
               
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
        $sql="insert into config_options values('vcdbProductionDatabase','','','vcdb','This is the name of the local MySQL database that will use for lookup of VCdb data. It is assumed to be on the same host as the main pim database');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('pcdbProductionDatabase','','','pcdb','This is the name of the local MySQL database that will use for lookup of PCdb data. It is assumed to be on the same host as the main pim database');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('padbProductionDatabase','','','padb','This is the name of the local MySQL database that will use for lookup of PAdb data. It is assumed to be on the same host as the main pim database');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('qdbProductionDatabase','','','qdb','This is the name of the local MySQL database that will use for lookup of Qdb data. It is assumed to be on the same host as the main pim database');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('vcdbAPIcacheDatabase','','','vcdbcache','This is the name of the local MySQL database that will used caching and using vcdb API data. It is assumed to be on the same host as the main pim database');"; $stmt=$db->conn->prepare($sql); $stmt->execute();       
        $sql="insert into config_options values('assetPushURI','','','','Experimental feature for debugging - URI of peer SandPIM system to push assets to');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('recentPartAdditionsDaysBack','','','7','How many days back from today into the past to consider for the recent-addtions list on the home screen');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('navbarColorHex','AN1/255','','c0c0c0','The UI top-nav background color (6 character hex value)');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('WMclientid','AN36','','','Walmart API client ID (uuid with hyphens)');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('WMconsumerid','AN36','','','Walmart API consumer ID (uuid with hyphens)');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('WMconsumerchanneltype','AN36','','','Walmart API consumer channel type (uuid with hyphens)');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('WMsecret','AN1/255','','','Walmart API secret');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('showAppAttributesInSummary','AN1/255','','','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('partAssetAPIenabled','AN1/255','','no','Controls part asset API');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('AutoCareAPIclientid','AN1/255','','','AutoCare Client ID for API - common to all subscribers');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('AutoCareAPIclientsecret','AN1/255','','','AutoCare Client secret for API - common to all subscribers');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('AutoCareAPIusername','AN1/255','','','AutoCare API username - unique to a subscriber');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into config_options values('AutoCareAPIpassword','AN1/255','','','AutoCare API password - unique to a subscriber');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
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
        $sql="insert into receiverprofile values(1000,0,'Epicor','',30,'2000-01-01','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into receiverprofile values(1001,0,'WHI','',30,'2000-01-01','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into receiverprofile values(1002,0,'PartsTech','',30,'2000-01-01','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into receiverprofile values(1003,0,'Show Me The Parts','',30,'2000-01-01','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into receiverprofile values(1004,0,'OptiCat','',30,'2000-01-01','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into receiverprofile values(1005,0,'Ozark','',30,'2000-01-01','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into receiverprofile values(1006,0,'Sandpiper Demo Receiver','',30,'2000-01-01','');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
        
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

        $sql="insert into receiverprofile_deliverygroup values(1,1000,100)"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
        
        
        $sql="CREATE TABLE receiverprofile_parttranslation (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        receiverprofileid int UNSIGNED NOT NULL,
        internalpart varchar(255) not null,
        externalpart varchar(255) not null,
        PRIMARY KEY (id),
        unique key idx_receiver_internalpart(receiverprofileid,internalpart))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - receiverprofile_parttranslation ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - receiverprofile_parttranslation ('.$db->conn->error.')';}
        
        
        $sql="CREATE TABLE receiverprofile_lifecycleststus (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        receiverprofileid int UNSIGNED NOT NULL,
        lifecyclestatus varchar(255) not null,
        PRIMARY KEY (id),
        index idx_receiverprofileid(receiverprofileid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - receiverprofile_lifecycleststus ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - receiverprofile_lifecycleststus ('.$db->conn->error.')';}

        $sql="insert into receiverprofile_lifecycleststus values(1,1000,'0')"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into receiverprofile_lifecycleststus values(2,1000,'2')"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into receiverprofile_lifecycleststus values(3,1000,'7')"; $stmt=$db->conn->prepare($sql); $stmt->execute();


        $sql="CREATE TABLE receiverprofile_pricesheet (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        receiverprofileid int UNSIGNED NOT NULL,
        pricesheetnumber varchar(255) not null,
        PRIMARY KEY (id),
        index idx_receiverprofileid(receiverprofileid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - receiverprofile_pricesheet ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - receiverprofile_lifecycleststus ('.$db->conn->error.')';}

        $sql="insert into receiverprofile_pricesheet values(1,1000,'WDNET2021')"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
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
        
        
        $sql="CREATE TABLE plan_receiverprofile (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        planid int UNSIGNED NOT NULL,
        receiverprofileid int UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        index idx_planid(planid),
        index idx_receiverprofileid(receiverprofileid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - plan_receiverprofile ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - plan_receiverprofile ('.$db->conn->error.')';}      
        
        $sql="insert into plan_receiverprofile values(1,10500,1000)"; $stmt=$db->conn->prepare($sql); $stmt->execute();
 
        
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
        $sql="insert into plan values(10500,'Demo plan for Sandpiper testing','9a6deb0f-25eb-4d18-83f0-6bf118fb2fad',1006,'data1:value1;data2:value2;','<xml></xm/>','Approved','2021-10-01 14:00:00','2021-10-01 14:01:00','2021-10-01 14:02:00')";  $stmt=$db->conn->prepare($sql); $stmt->execute();

        
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

        $sql="insert into plan_slice values(1,10500,2090,'b9317fa1-e2e1-462e-ba9c-6d6bec9445cd','',1)";  $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into plan_slice values(2,10500,2091,'1ae0b318-7adb-4c37-abfd-084a170c269a','',2)";  $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into plan_slice values(3,10500,2092,'049048f4-04b6-4130-bc7a-e0a46fe50df8','',3)";  $stmt=$db->conn->prepare($sql); $stmt->execute();
        

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
        $sql="insert into slice values(2090,'Slice 1','afd2b3f9-134d-4b68-ac1c-a57bafbdd3bc','aces-file','ACESfile.xml',0,'data1:value1;data2:value2;','')";  $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into slice values(2091,'Slice 1','544dd9cb-13ad-4457-8be5-2a6ab95a8b4a','pies-file','PIESfile1.xml',0,'data1:value1;data2:value2;','')";  $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into slice values(2092,'Slice 1','09c4d2d9-a17b-4a63-bb0c-f3c13cc62acb','pies-file','PIESfile2.xml',0,'data1:value1;data2:value2;','')";  $stmt=$db->conn->prepare($sql); $stmt->execute();

        
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
        $sql="insert into deliverygroup values(100,'AmeriBrakes - Pads');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into deliverygroup values(101,'AmeriBrakes - Rotors');"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into deliverygroup values(102,'AmeriBrakes - Pads, Rotors');"; $stmt=$db->conn->prepare($sql); $stmt->execute();

        
        $sql="CREATE TABLE deliverygroup_partcategory (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        deliverygroupid int unsigned not null,
        partcategory int unsigned not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - deliverygroup_partcategory ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - deliverygroup_partcategory ('.$db->conn->error.')';}
        $sql="insert into deliverygroup_partcategory values(500,100,10);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into deliverygroup_partcategory values(501,100,11);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
                
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

        $sql="CREATE TABLE user_preference (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        userid int UNSIGNED NOT NULL,
        preferencekey varchar(255) not null,
        preferencevalue varchar(255) not null,
        PRIMARY KEY (id), unique key idx_userid_preferencekey(userid,preferencekey))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - user_preference ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - user_preference ('.$db->conn->error.')';}
        
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

        //brand-connected assets (non-part) - marketing materials, non-SKU videos, etc
        $sql="CREATE TABLE brand_asset (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        BrandID varchar(255) NOT NULL,
        assetid varchar(255) NOT NULL,
        assettypecode varchar(255) not null,
        sequence int unsigned not null,
        PRIMARY KEY (id),
        INDEX idx_BrandID (BrandID),
        INDEX idx_assetid (assetid)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - brand_asset ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - brand_asset ('.$db->conn->error.')';}
                
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
        startyear int unsigned null,
        endyear int unsigned null,
        meanyear int unsigned null,    
        growthtrend decimal(4,2) null,
        PRIMARY KEY (id),
        INDEX idx_part_geo_yrQtr (partnumber, geography, yearQuarter),
        INDEX idx_capturedate (capturedate))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - part_VIO ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - part_VIO ('.$db->conn->error.')';}      


        $sql="CREATE TABLE replicationpeer (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        identifier varchar(255) NOT NULL,
        description varchar(255) NOT NULL,
        type varchar(255) NOT NULL,
        role varchar(255) NOT NULL,
        uri varchar(255) NOT NULL,
        objectlimit int unsigned not null,
        sharedsecret varchar(255) NOT NULL,
        enabled int unsigned not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - replicationpeer ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - replicationpeer ('.$db->conn->error.')';}

        // struggling to come up with a good name for this table. We need a data-driven way for regular users to deploy arbitrary html to the dashboard page.
        //  - generally iframe embeds to external resources like AirTable, Jira etc. in order to publish information to other users that is not managed inside the PIM system.
        // the specific use-case diving this inside AutoPartSource is that we manage the queue of requests for digital asset creation in an external web-based service
        // called AirTable. They provide an embed (iframe) capability to tailor a specifc filtered/sorted presentation of a larger dataset.
        $sql="CREATE TABLE dashboardembed (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        description varchar(255) NOT NULL,
        type varchar(255) NOT NULL,
        sequence int unsigned not null,
        data text not null,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - replicationpeer ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - replicationpeer ('.$db->conn->error.')';}
        
        $sql="CREATE TABLE housekeepingrequest (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        requesttype varchar(255) NOT NULL,
        requestdata varchar(255) NOT NULL,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - housekeepingrequest ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - housekeepingrequest ('.$db->conn->error.')';}

        $sql="CREATE TABLE auditrequest (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        requesttype varchar(255) NOT NULL,
        requestdata varchar(255) NOT NULL,
        PRIMARY KEY (id))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - auditrequest ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - auditrequest ('.$db->conn->error.')';}

        $sql="CREATE TABLE auditlog (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        audittype varchar(255) NOT NULL,
        objectkeyalpha varchar(255) NOT NULL,
        objectkeynumeric int UNSIGNED NOT NULL,
        result varchar(255) NOT NULL,
        auditdatetime datetime not null,
        oidataudit varchar(255) NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_type_alpha (audittype,objectkeyalpha,oidataudit),
        INDEX idx_type_numeric (audittype,objectkeynumeric,oidataudit),
        INDEX idx_auditdatetime (auditdatetime),
        INDEX idx_oidataudit (oidataudit))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - auditlog ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - auditlog ('.$db->conn->error.')';}
        
        
        // BOM history 

        $sql="CREATE TABLE partrelationship_history (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        leftpartnumber varchar(255) NOT NULL,
        rightpartnumber varchar(255) NOT NULL,
        relationtype varchar(255) NOT NULL,
        units decimal(10,2) not null,
        sequence int unsigned NOT NULL,
        versiondate datetime not null,
        PRIMARY KEY (id),
        INDEX idx_leftpartnumber (leftpartnumber),
        INDEX idx_rightpartnumber (rightpartnumber),
        INDEX idx_version (versiondate)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - partrelationship_history ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - partrelationship_history ('.$db->conn->error.')';}

        $sql="CREATE TABLE noteqdbtranslation (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        note varchar(255) NOT NULL,
        qdbid int unsigned NOT NULL,
        params text NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_note (note),
        INDEX idx_qdbid (qdbid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - noteqdbtranslation ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - noteqdbtranslation ('.$db->conn->error.')';}

        $sql="CREATE TABLE descriptionrecipe (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        partcategory int unsigned not null,
        parttypeid int unsigned not null,
        descriptioncode varchar(255) NOT NULL,
        languagecode varchar(255) NOT NULL,        
        PRIMARY KEY (id)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - descriptionrecipe ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - descriptionrecipe ('.$db->conn->error.')';}

        $sql="CREATE TABLE descriptionrecipeblock (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        recipeid int UNSIGNED NOT NULL,
        sequence int unsigned NOT NULL,
        blocktype varchar(255) NOT NULL,
        blockparameters text NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_version (recipeid)
        )";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - descriptionrecipeblock ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - descriptionrecipeblock ('.$db->conn->error.')';}
                        
        $sql="CREATE TABLE wmapisession (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        state varchar(255) NOT NULL,
        accesstoken text NOT NULL,
        correlationid varchar(255) NOT NULL,
        startepoch int unsigned not null,
        messages text NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_correlationid (correlationid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - wmapisession ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - wmapisession ('.$db->conn->error.')';}


        $sql="CREATE TABLE wmapifeed (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        feedid varchar(255) NOT NULL,
        type varchar(255) NOT NULL,
        state varchar(255) NOT NULL,
        localfile varchar(255) NOT NULL,
        postfilename varchar(255) NOT NULL,
        receiverprofileid int UNSIGNED NOT NULL,
        messages text NOT NULL,
        epochstart int UNSIGNED NOT NULL,  
        progress decimal(5,2) not null,
        errors int unsigned NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_feedid (feedid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - wmapifeed ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - wmapifeed ('.$db->conn->error.')';}
        
        
        $sql="CREATE TABLE navelement (
        navid varchar(255) not null,
        category varchar(255) not null,
        title varchar(255) not null,
        path varchar(255) not null,
        sequence int not null,
        PRIMARY KEY (navid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - navelement ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - navelement ('.$db->conn->error.')';}
        $sql="insert into navelement values('PARTS','','Parts','',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('APPLICATIONS','','Applications','',2);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('ASSETS','','Assets','',3);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('REPORTS','','Reports','',4);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('UTILITIES','','Utilities','',5);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS','','Settings','',6);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT','','Import','',7);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('EXPORT','','Export','',8);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
        $sql="insert into navelement values('PARTS/OURS','PARTS','Search Our Parts','partsIndex.php',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('PARTS/COMPETITOR','PARTS','Search Competitor Parts','interchangeIndex.php',2);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('PARTS/CREATE','PARTS','Create New Part','newPart.php',3);"; $stmt=$db->conn->prepare($sql); $stmt->execute();

        $sql="insert into navelement values('APPLICATIONS/MMY','APPLICATIONS','Make/Model/Year apps','appsIndex.php',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();

        $sql="insert into navelement values('ASSETS/SEARCH','ASSETS','Search Assets','assetsIndex.php',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();        
        
        $sql="insert into navelement values('REPORTS/ASSETMATRIX','REPORTS','Asset Matrix','assetCoverageReportForm.php',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('REPORTS/ASSETHITLIST','REPORTS','Asset Hitlist','assetHitlistReportForm.php',2);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('REPORTS/ATTRIBUTEMATRIX','REPORTS','Attribute Matrix','partAttributesReportForm.php',3);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('REPORTS/EXPIMATRIX','REPORTS','EXPI Matrix','partExpiReportForm.php',4);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('REPORTS/DESCRIPTIONSMATRIX','REPORTS','Descriptions Matrix','partDescriptionsReportForm.php',5);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('REPORTS/PACKAGESMATRIX','REPORTS','Packages Matrix','partPackagesReportForm.php',6);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('REPORTS/INTERCHANGEMATRIX','REPORTS','Interchange Matrix','interchangeCoverageReportForm.php',7);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('REPORTS/PRICINGMATRIX','REPORTS','Pricing Matrix','pricingCoverageReportForm.php',8);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('REPORTS/COMPETITORCOVERAGE','REPORTS','Competitor Coverage','competitorCoverageReportForm.php',9);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('REPORTS/VIOCOVERAGE','REPORTS','VIO Coverage','vioCoverageReportForm.php',10);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('REPORTS/PARTTYPEVEHICLEHOLES','REPORTS','Vehicle Coverage Holes','parttypeHolesReportForm.php',11);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
        $sql="insert into navelement values('UTILITIES/TWOPARTMATCH','UTILITIES','Two-Part Match-maker','pairPart.php',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('UTILITIES/FULLVEHICLEPARTMATCH','UTILITIES','Full-Vehicle Kit Match-maker','bundlePart.php',2);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('UTILITIES/CLEARDATA','UTILITIES','Clear Data','clearDataSelect.php',3);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('UTILITIES/BUYERSGUIDEBUILDER','UTILITIES','Buyers Guide Builder','buyersGuideBuilder.php',4);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('UTILITIES/BASEVIDSTOMMYS','UTILITIES','Convert BaseVehicleIDs to Makes/Models/Years','basevidsToMMYinput.php',5);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('UTILITIES/MMYSTOBASEVIDS','UTILITIES','Convert Makes/Models/Years to BaseVehicleIDs','MMYtoBasevidsInput.php',6);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('UTILITIES/CODEDVALUSETOACES','UTILITIES','Convert coded-value spreadsheet to ACES','convertAiExcelToACES4_1upload.php',7);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('UTILITIES/RHUBARB71','UTILITIES','Rhubarb 7.1','rhubarb7_1Index.php',8);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('UTILITIES/RHUBARB67','UTILITIES','Rhubarb 6.7','rhubarb6_7Index.php',9);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('UTILITIES/WMUPLOADER','UTILITIES','Walmart Content uploader','wmSessions.php',10);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('UTILITIES/UUID','UTILITIES','UUID Generator','UUIDgenerator.php',11);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
        
        $sql="insert into navelement values('SETTINGS/CATEGORIES','SETTINGS','Part Categories','partCategories.php',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/DELIVERYGROUPS','SETTINGS','Delivery Groups','deliveryGroups.php',2);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/RECEIVERPROFILES','SETTINGS','Receiver Profiles','receiverProfiles.php',3);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/FAVORITEMAKES','SETTINGS','Favorite Makes','vcdbMakeBrowser.php',4);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/FAVORITEPARTTYPES','SETTINGS','Favorite Part Types','pcdbTypeBrowser.php',5);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/FAVORITEAPPLICATIONPOSITIONS','SETTINGS','Favorite Application Positions','pcdbPositionBrowser.php',6);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/FAVORITEBRANDS','SETTINGS','Favorite Brands','competitiveBrandBrowser.php',7);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/PRICESHEETS','SETTINGS','Price Sheets','priceSheets.php',8);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/FITMENTNOTEMANAGEMENT','SETTINGS','Fitment Note Management','noteManager.php',9);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/USERS','SETTINGS','Users','users.php',10);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/CONFIGURATION','SETTINGS','Configuration','config.php',11);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/BACKGROUNDJOBS','SETTINGS','Background import/export jobs','backgroundJobs.php',12);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/SANDPIPER','SETTINGS','Sandpiper','sandpiper.php',13);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('SETTINGS/DESCRIPTIONRECIPES','SETTINGS','Description Recipes','descriptionRecipes.php',14);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
        
        $sql="insert into navelement values('IMPORT/ACESFILEUPLOAD','IMPORT','ACES File Upload','importACESupload.php',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/ACESSNIPPET','IMPORT','ACES xml snippet','importACESxml.php',2);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/APPSFROMTEXT','IMPORT','Applications from text','importACEStext.php',3);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/APPSFROMSPREADSHEET','IMPORT','Applications from Excel spreadsheet','importACESexcelUpload.php',4);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/PARTSFROMTEXT','IMPORT','Parts from text','importPartsText.php',5);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/PARTDESCRIPTIONSFROMTEXT','IMPORT','Part descriptions from text','importPartDescriptionText.php',6);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/PARTDATTRIBUTESFROMTEXT','IMPORT','Part attributes from text','importPartAttributeText.php',7);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/PACKAGINGFROMTEXT','IMPORT','Packaging from text','importPackagingText.php',8);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/PRICESFROMTEXT','IMPORT','Prices from text','importPricesText.php',9);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/COMPETITORINTERCHANGEFROMTEXT','IMPORT','Competitor interchange from text','importInterchangeText.php',10);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/ASSETMETADATAFROMTEXT','IMPORT','Asset metadata from text','importAssetText.php',11);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/ASSETTAGSFROMTEXT','IMPORT','Asset tags from text','importAssetTags.php',12);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/EXPIFROMTEXT','IMPORT','EXPI from text','importPartEXPIText.php',13);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/PARTBALANCEFROMTEXT','IMPORT','Part balance from text','updatePartBalances.php',14);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/KITCOMPONENTSFROMTEXT','IMPORT','Kit components from text','updateKitComponents.php',15);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/AUTOCAREBRANDTABLEFROMTEXT','IMPORT','AutoCare Brand Table from text','importBrandTableText.php',16);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/AUTOCAREREFERENCEDATA','IMPORT','AutoCare Databases (VCdb, PCdb, Qdb, PAdb)','AutoCareDownloads.php',17);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('IMPORT/EXPERIANVIO','IMPORT','Experian VIO','importExperianVIOtext.php',18);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
        $sql="insert into navelement values('EXPORT/PIESXML','EXPORT','PIES xml','exportPIESselect.php',1);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('EXPORT/ACESXML','EXPORT','ACES xml','exportACESselect.php',2);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('EXPORT/BUYERSGUIDE','EXPORT','Buyers Guide','exportApplicationGuideSelect.php',3);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('EXPORT/APAAWDA65SPREADSHEET','EXPORT','APA/AWDA (6.5) Spreadsheet','exportAPA65pricefileSelect.php',4);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('EXPORT/WALMARTPRODUCTSPREADSHEET','EXPORT','Parts in Walmart format spreadsheet','exportWalmartSelect.php',5);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('EXPORT/FLATPARTS','EXPORT','Flattened parts file','exportFlatPartsSelect.php',6);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('EXPORT/FLATAPPS','EXPORT','Flattened applications file','exportFlatAppsSelect.php',7);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('EXPORT/APPGUIDEBASIC','EXPORT','Application Guide PDF (basic)','exportForBasicPrintSelect.php',8);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('EXPORT/APPGUIDEMULTICOLIUMN','EXPORT','Application Guide PDF (multi-column)','exportForMulticolumnPrintSelect.php',9);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        $sql="insert into navelement values('EXPORT/COMPETITORINTERCHANGE','EXPORT','Competitor Interchange','exportCompetitorInterchangeSelect.php',10);"; $stmt=$db->conn->prepare($sql); $stmt->execute();
        
        
        
        
        
        
        $sql="CREATE TABLE user_navelement (
        id int UNSIGNED NOT NULL AUTO_INCREMENT,
        userid int UNSIGNED NOT NULL,
        navid varchar(255) not null,
        PRIMARY KEY (id),
        INDEX idx_navid (navid),
        INDEX idx_userid (userid))";
        if($stmt=$db->conn->prepare($sql)){if(!$stmt->execute()){$returnvalue['log'][]='execute failed - user_navelement ('.$db->conn->error.')';}}else{$returnvalue['log'][]='prepare failed - user_navelement ('.$db->conn->error.')';}

        
        //need a table(s) to store data related to "readiness certification" of what will be exported for a given 
        // receiver profile. Generally, There will be a set of go/no-go criteria for a given receiver profile - for example: 
        // all parts must have an SHO description (yes/no), All asset's URIs must be tested for validity (yes/no), All apps myst be 
        // tested for VCdb reference validity against the current system VCdb version (yes/no). The hash of OIDs in each area of interest (parts, assets, apps)
        // will be stored as a figerprint of the transmit-ready dataset. Any OID change would invalidate total and de-certify the dataset for transmit. Background 
        // auditor woulbd be told to attempt a certification. Failing items would be reported and pass would be comemorated by updating the hash of all in-scope OIDs
        // every lifecyle-available part has descriptions, packages, attributes, assets, prices, apps, valid non-conflicting GTIN, 
        // every app is VCdb-valid against current version with no overlaps
        // every asset's URI is valid
        // every asset's size is within bounds
        
        
        
        
        $db->close();
        return $returnvalue;
    }

    
    
    


// see what schema version is claimed by pim
    
// determine what permissions we have on the database by creating a random-named db and table and interacting with it

// create pim database and tables with minimal defaults inserted 
    
}
?>