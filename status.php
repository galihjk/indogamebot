<pre style="font-size: xx-large;">

<?php

include_once("helpers/hlp_data.php");
$data = loadData("srvstatus");
$timelag = time()-($data['time'] ?? 0);
$data['TimeDif'] = $timelag;
if($timelag > 3){
    $data['STATUS'] = "OFF";
}
else{
    $data['STATUS'] = "ON";
}
print_r($data);

?>

</pre>
