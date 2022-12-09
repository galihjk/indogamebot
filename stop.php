<?php
include_once("helpers/hlp_server.php");
include_once("helpers/hlp_data.php");
?>
<h1>The Server will be Terminated</h1>
<pre>
<?php 
print_r(loadData("srvstatus"));
server_stop();
?>
</pre>
<script>
    setTimeout(function () {
        window.close();
    }
    , 10000);
</script>