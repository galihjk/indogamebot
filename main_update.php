<?php 

include_once("helpers/hlp_config.php");
include_once("helpers/hlp_data.php");
include_once("helpers/hlp_bot.php");

$message_data = $update["message"];
$chat_id = (string) $message_data["chat"]["id"];
$message_text = $message_data["text"];
if($message_text == "/srvstatus"){
    $srvstatus = loadData("srvstatus");
    KirimPerintah('sendMessage',[
        'chat_id' => $chat_id,
        'text' => 'srvstatus: '.print_r($srvstatus, true)."\nLag ".time()-($srvstatus['time'] ?? 0),
        'disable_web_page_preview' => true,
    ],$token);
}