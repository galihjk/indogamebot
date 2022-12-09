<?php 

include_once("helpers/hlp_config.php");
include_once("helpers/hlp_data.php");
include_once("helpers/hlp_bot.php");

function server_stop(){
    $srvstatus['run_code'] = false;
    saveData("srvstatus",$srvstatus);
}

function server_start($check_already_running=true, $drop_pending = true){
    global $id_developer;

    $bot_config = getConfig('bot', []);
    if($drop_pending){
        foreach($bot_config as $bot=>$config){
            $token = $config['token'];
            $update_id = 0;
            $updates = DapatkanUpdate($update_id, $token);
            $maxloop = 1000;
            while(count($updates) >= 50){
                $maxloop --;
                $update_id = 1+end($updates)["update_id"];
                $updates = DapatkanUpdate($update_id, $token);
                if($maxloop < 1) break;
            }
        }
    }

    $srvstatus = loadData("srvstatus");
    $starting = true;
    if(!empty($srvstatus['run_code'])){
        if(!empty($srvstatus['time'])){
            if(abs(time() - $srvstatus['time']) <= 5){
                $starting = false;
                if($check_already_running){
                    //jika sudah aktif dalam 5 detik yang lalu, tidak perlu start ulang
                    return false;
                }
            }
        }
    }

    $run_code = md5(date("YmdHis").rand(0,99));
    $srvstatus['run_code'] = $run_code;

    if($starting){
        foreach($bot_config as $bot=>$config){
            $token = $config['token'];
            KirimPerintah('sendMessage',[
                'chat_id' => $config['admingroup'],
                'text' => 'Server Started: '.$run_code . " \nSTOP: ".getConfig('host', "")."/stop.php",
                'disable_web_page_preview' => true,
            ],$token);
        }
    }
    
    saveData("srvstatus",$srvstatus);
    $runserver = getConfig('servercode', "");
    $srvurl = getConfig('host', "")."/serve.php?runserver=$runserver&code=$run_code";
    // get_without_wait($srvurl);
    return $srvurl;
}

function get_without_wait($url)
{
	$ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function create_job($php, $time){
    file_put_contents("jobs/$time.php", "<?php $php");
}