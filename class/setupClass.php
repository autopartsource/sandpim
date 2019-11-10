<?php
include_once("mysqlClass.php");

class setup
{

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

// see what schema version is claimed by pim
    
// determine what permissions we have on the database by creating a random-named db and table and interacting with it

// create pim database and tables with minimal defaults inserted 
    
}
?>