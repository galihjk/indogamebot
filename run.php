<?php

include_once("helpers/hlp_config.php");
include_once("helpers/hlp_data.php");
include_once("helpers/hlp_bot.php");

$bot_config = getConfig('bot', []);
foreach($bot_config as $bot=>$config){
    $token = $config['token'];
    $update_id = loadData("updid_$bot",0);
    $updates = DapatkanUpdate($update_id, $token);
    foreach($updates as $update){
        include("main_update.php");
        $update_id = 1+$update["update_id"];
    }
    saveData("updid_$bot",$update_id);
}

