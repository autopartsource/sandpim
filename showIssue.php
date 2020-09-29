<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');


$navCategory = '';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;

$selectedissue=$pim->getIssueById(intval($_GET['id']));

$tree=array();

$issues=$pim->getIssues('%', '%', '%', 9999);

foreach($issues as $issue)
{
 $issuebits = explode('/',$issue['issuetype']);
 
 if(count($issuebits))
 {
  
  for($i=0; $i<count($issuebits); $i++)
  {
   
    switch ($i)
    {
     case 0:
         if(!array_key_exists($issuebits[0], $tree))
         {
          $tree[$issuebits[0]]=array();
         }
         if($i==count($issuebits)-1)
         {// this is the lowest (least-significant) level of the path
          $tree[$issuebits[0]][]=$issue;
         }

         break;
     case 1:
         if(!array_key_exists($issuebits[1], $tree[$issuebits[0]]))
         {
          $tree[$issuebits[0]][$issuebits[1]]=array();
         }
         if($i==count($issuebits)-1)
         {// this is the lowest (least-significant) level of the path
          $tree[$issuebits[0]][$issuebits[1]][]=$issue;
         }
         
         break;
     case 2:
         if(!array_key_exists($issuebits[2], $tree[$issuebits[0]][$issuebits[1]]))
         {
          $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]]=array();
         }
         if($i==count($issuebits)-1)
         {// this is the lowest (least-significant) level of the path
          $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][]=$issue;
         }

         break;
     case 3:
         if(!array_key_exists($issuebits[3], $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]]))
         {
          $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]]=array();
         }
         if($i==count($issuebits)-1)
         {// this is the lowest (least-significant) level of the path
          $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]][]=$issue;
         }

         break;
     case 4:
         if(!array_key_exists($issuebits[4], $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]]))
         {
          $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]][$issuebits[4]]=array();
         }
         if($i==count($issuebits)-1)
         {// this is the lowest (least-significant) level of the path
          $tree[$issuebits[0]][$issuebits[1]][$issuebits[2]][$issuebits[3]][$issuebits[4]][]=$issue;
         }

         break;

     default: break;
    }
  }
 }
}

?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
            <script>
            
        
            
            </script>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft" id="sidebar">
                    
                    <ul class="nav flex-column flex-nowrap overflow-hidden">
                        <li class="nav-item">
                            <a class="nav-link collapsed text-truncate" href="#submenu1" data-toggle="collapse" data-target="#submenu1"><span class="d-none d-sm-inline">Parts</span></a>
                            <div class="collapse" id="submenu1" aria-expanded="false">
                                <ul class="flex-column pl-2 nav">
                                    <li class="nav-item">
                                        <a class="nav-link collapsed text-truncate" href="#submenu1" data-toggle="collapse" data-target="#submenu1sub1"><span class="d-none d-sm-inline">GTIN</span></a>
                                        <div class="collapse" id="submenu1sub1" aria-expanded="false">
                                            <ul class="flex-column nav pl-4">
                                                <li class="nav-item">
                                                    <a class="nav-link p-1" href="./showIssue.php?id=<?php echo $selectedissue['id'] ?>"><?php echo $selectedissue['issuekeyalpha'] ?></a>
                                                </li>
                                        </div>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link collapsed text-truncate" href="#submenu1" data-toggle="collapse" data-target="#submenu1sub2"><span class="d-none d-sm-inline">Package</span></a>
                                        <div class="collapse" id="submenu1sub2" aria-expanded="false">
                                            
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                    
                </div>

                <div>
<?php    

foreach($tree as $key0=>$data0)
{
 echo $key0.'<br/>';
   
 foreach($data0 as $key1=>$data1)
 {
  if(array_key_exists('issuehash', $data1))
  {// this is an end-node
   echo '- ' .$data1['description'].'<br/>';
  }
  else
  {// this node contains children
   echo '- '.$key1.'<br/>';
  }

  foreach($data1 as $key2=>$data2)
  {
   if(array_key_exists('issuehash', $data2))
   {// this is an end-node
    echo '- - ' .$data2['description'].'<br/>';
   }
   else
   {// this node contains children
    echo '- - '.$key2.'<br/>';
   }

   foreach($data2 as $key3=>$data3)
   {
    if(array_key_exists('issuehash', $data3))
    {// this is an end-node
     echo '- - - ' .$data3['description'].'<br/>';
    }
    else
    {// this node contains children
     echo '- - - '.$key3.'<br/>';
    }
   }      
  }     
 }
}
?>
                </div>




                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-10 my-col colMain">
                    <div class="card shadow-sm">
                        <div class="card-header text-left">
                            <?php echo $selectedissue['issuetype']; ?> Breadcrumb Nav
                        </div>
 
                        <div class="card shadow-sm">
                            <!-- Header -->
                            <h5 class="card-header text-left">Issue ID: <span class="text-info"><?php echo $selectedissue['id']; ?></span></h5>

                            <div class="card-body">
                                <div class="row padding my-row">
                                    <div class="col md-4">
                                        <div class="card shadow-sm">
                                            <!-- Header -->
                                            <h6 class="card-header text-left">Item - <?php echo $selectedissue['issuekeyalpha']; ?></h6>
                                        </div>
                                    </div>
                                    <div class="col md-4">
                                        <div class="card shadow-sm">
                                            <!-- Header -->
                                            <h6 class="card-header text-left">Status - <?php echo $selectedissue['status']; ?></h6>
                                        </div>
                                    </div>
                                    <div class="col md-4">
                                        <div class="card shadow-sm">
                                            <!-- Header -->
                                            <h6 class="card-header text-left">Source - <?php echo $selectedissue['source']; ?></h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="row padding my-row">
                                    <div class="col md-6">
                                        <div class="card shadow-sm">
                                            <h6 class="card-header text-left">Description</h6>

                                            <div class="card-body">
                                                <?php echo $selectedissue['description']; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col md-6">
                                        <div class="card shadow-sm">
                                            <h6 class="card-header text-left"> Notes</h6>

                                            <div class="card-body">
                                                <?php echo $selectedissue['notes']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                Created at <?php echo $selectedissue['issuedatetime']; ?>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <!-- End of Main Content -->

            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>