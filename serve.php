<?php
date_default_timezone_set('Asia/Jakarta');

include_once("helpers/hlp_config.php");
include_once("helpers/hlp_data.php");
include_once("helpers/hlp_bot.php");

if(empty($_GET['runserver']) or $_GET['runserver'] !== getConfig('servercode', "")){
    die("wrong access");
}

sleep(rand(1,2));

$srvstatus = loadData("srvstatus");
if(!empty($srvstatus['run_code'])){
    $run_code = $srvstatus['run_code'];
    if($_GET['code'] == $run_code){
        $srvstatus['time'] = time();
        saveData("srvstatus",$srvstatus);
        // file_get_contents(getConfig('host', "")."/run.php");
        include("run.php");
        $runserver = getConfig('servercode', "");
        $serverurl = getConfig('host', "")."/serve.php?runserver=$runserver&code=$run_code";
        // get_without_wait($serverurl);
        echo "<a href='$serverurl'>$serverurl</a>";
    }
    else{
        //run code has been changed
    }
}
else{
    $bot_config = getConfig('bot', []);
    foreach($bot_config as $bot=>$config){
        $token = $config['token'];
        $id_developer = $config['developer'];
        KirimPerintah('sendMessage',[
            'chat_id' => $config['admingroup'],
            'text' => "Server Stopped. \nSTART: ".getConfig('host', "")."/start.php",
            'disable_web_page_preview' => true,
        ]);
    }
}