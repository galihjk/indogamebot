<?php
include_once("helpers/hlp_server.php");
server_start();
?>
<h1>Server Started</h1>
<script>
    setTimeout(function () {
        window.close();
    }
    , 2000);
</script>