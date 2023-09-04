<script>
    function refreshClipboard() {
        document.getElementById("clipboardBody").innerHTML = "<p></p>";

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajaxGetClipboard.php');
        xhr.onload = function ()
        {
            var response = JSON.parse(xhr.responseText);
            if (parseInt(response.length) > 0) {
                document.getElementById("clipboardButton").removeAttribute("hidden");
                document.getElementById("clipboardButton").setAttribute("class", "btn btn-success position-relative");
                for (var i = 0; i < response.length; i++) {
                    document.getElementById("clipboardBody").innerHTML += '<p id=clipboardObject_' + response[i].id + '>' + response[i].description + ' <a type="button" class="btn btn-sm btn-outline-danger" onclick="deleteClipboardObject(\'clipboardObject_' + response[i].id + '\')"><i class="bi bi-x"></a></p>';
                }
                document.getElementById("clipboardBadge").innerHTML=response.length;
            }
            else {
                document.getElementById("clipboardButton").setAttribute("hidden", "");
            } 
        };
        xhr.send();
    }
    

    function deleteClipboardObject(id) {
        var clipboardObject = document.getElementById(id);
        var chunks = id.split("_");
        clipboardObject.parentNode.removeChild(clipboardObject);

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajaxDeleteClipboard.php?id=' + chunks[1]);
        xhr.onload = function ()
        {
            refreshClipboard();
        };
        xhr.send();
    }

    function clearClipboard() {
        document.getElementById("clipboardBody").innerHTML = "<p></p>";
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajaxDeleteClipboard.php');
        xhr.onload = function ()
        {
        };
        xhr.send();
        document.getElementById("clipboardButton").setAttribute("hidden", "");
    }


</script>

</script>

<style>
.navbar-custom {background-color: #<?php if(isset($pim)){echo $pim->navbarColor();}else{echo '404040';}?>;}
.navbar-custom .navbar-brand,
.navbar-custom .navbar-text {color: #ffcc00;}
.navbar-custom .navbar-nav .nav-link {color: #ffbb00;}
.navbar-custom .nav-item.active .nav-link,
.navbar-custom .nav-item:focus .nav-link,
.navbar-custom .nav-item:hover .nav-link {color: #ffffff;}
.navbar-custom .navbar-nav .dropdown-menu {background-color: #ddaa11;}
.navbar-custom .navbar-nav .dropdown-item {color: #000000;}
.navbar-custom .navbar-nav .dropdown-item:hover,.navbar-custom .navbar-nav .dropdown-item:focus {color: #404040; background-color: #ffffff;}
</style>

<nav class="navbar can-stick navbar-expand-md navbar-custom">
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>    
    <div id="navbarMenu" class="collapse navbar-collapse">
        <ul class="nav navbar-nav">
            <li<?php if ($navCategory == 'dashboard') {echo ' class="nav-item active"';} else {echo ' class="nav-item"';} ?>><a href="index.php" class="nav-link">Home</a></li>
            

            <?php $navcategories=$pim->getNavelements('');
            foreach($navcategories as $navcategory)
            { ?>
            
            <li<?php if ($navCategory == strtolower($navcategory['navid'])) {echo ' class="nav-item dropdown active"';} else {echo ' class="nav-item dropdown"';} ?>>
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown<?php echo $navcategory['category'];?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo $navcategory['title'];?>
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown<?php echo $navcategory['category'];?>">
                    <?php
                    $navelements=$pim->getNavelements($navcategory['navid']);
                    foreach($navelements as $navelement)
                    {
                     if($pim->userHasNavelement($_SESSION['userid'],$navelement['navid']))
                     {
                      echo '<a class="dropdown-item" href="'.$navelement['path'].'">'.$navelement['title'].'</a>';
                     }
                     else
                     {
                      echo '<a class="dropdown-item disabled">'.$navelement['title'].'</a>';
                     }
                    }?>
                </div>
            </li>

            <?php }?>

            
        </ul>
        <div class="ms-auto">
            <form action="./showPart.php">
                <input name="partnumber" type="text" id="partsearch" size="10"/><input type="submit" name="submit" value="Go"/>
            </form>
        </div>
        
        <div class="ms-auto">
        <ul class="nav navbar-nav">
            <button id="clipboardButton" type="button" class="btn btn-primary position-relative" type="button" data-bs-toggle="offcanvas" data-bs-target="#clipboard" aria-controls="clipboard" onclick="refreshClipboard()" hidden>
                <i class="bi bi-clipboard"></i> <span id="clipboardBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"><span class="visually-hidden"> </span></span>
            </button>
            <a href="logout.php" class="nav-link">Logout (<?php echo $_SESSION['name'];?>)</a>
        </ul>
            
        </div>
    </div> 
</nav>

<div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="clipboard" aria-labelledby="clipboard">
  <div class="offcanvas-header">
    <span class="btn btn-sm btn-outline-danger" id="clearClipboard" onclick="clearClipboard()">CLEAR</span>
    <h5 class="offcanvas-title" id="clipboardLabel" style="margin-left:10px;">Clipboard</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div id="clipboardBody" class="offcanvas-body">
  </div>
  
</div>
<script>
    var el = document.getElementById("clipboardButton");
    el.addEventListener("onload", refreshClipboard());
</script>