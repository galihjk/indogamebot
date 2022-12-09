<pre style="font-size: xx-large;">

<?php

include_once("helpers/hlp_data.php");
$data = loadData("srvstatus");
$timelag = time()-($srvstatus['time'] ?? 0);
if($timelag > 2){
    $data['STATUS'] = "OFF";
}
else{
    $data['STATUS'] = "ON";
}
print_r($data);

?>

</pre>
