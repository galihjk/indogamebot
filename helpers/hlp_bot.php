<?php

//Fungsi untuk Penyederhanaan kirim perintah dari URI API Telegram
function BotKirim($bot_token,$perintah){
    return 'https://api.telegram.org/bot'.$bot_token.'/'.$perintah;
}
  
//Fungsi untuk mengirim "perintah" ke Telegram
function KirimPerintahStream($bot_token,$perintah,$data){
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );
    $context  = stream_context_create($options);
    $result = file_get_contents(BotKirim($bot_token,$perintah), false, $context);
    return $result;
}

function KirimPerintahCurl($bot_token,$perintah,$data){
	if(!is_countable($data)){
		file_put_contents("ERRORCOUNTABLE.txt",print_r([$bot_token,$perintah,$data],1));
	}
	if(empty($data)) $data = [];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,BotKirim($bot_token,$perintah));
    curl_setopt($ch, CURLOPT_POST, count($data));
    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $kembali = curl_exec ($ch);
    curl_close ($ch);

    return $kembali;
}

// Perintah untuk mendapatkan Update dari Api Telegram.
function DapatkanUpdate($offset,$bot_token = "default") 
{
    global $config;

    if($bot_token == "default"){
        $bot_token = $config['bot_token'];
    }
    
    $url = BotKirim($bot_token,"getUpdates")."?offset=".$offset;
    
    $hasil =  KirimPerintah("getUpdates",['offset'=>$offset],$bot_token);
    
    if (!empty($hasil["ok"])){
        return $hasil["result"];
    }else{
        return array();
    }
}

function KirimPerintah($perintah,$data,$bot_token = "default"){
    global $config;
    
	if(empty($data)){
		$data = [];
	}

    if($bot_token == "default"){
        $bot_token = $config['bot_token'];
    }

	//set data yang berupa array menjadi json agar sesuai dengan api bot telegram 
	foreach($data as $key => $val){
		if(is_array($val)){
			$data[$key] = json_encode($val);
		}
	}

    // Detek otomatis metode curl atau stream 
    if(is_callable('curl_init')) {
        $hasil = KirimPerintahCurl($bot_token,$perintah,$data);
        //cek kembali, terkadang di XAMPP Curl sudah aktif
        //namun pesan tetap tidak terikirm, maka kita tetap gunakan Stream
        if (empty($hasil)){
            $hasil = KirimPerintahStream($bot_token,$perintah,$data);
        }   
    } else {
        $hasil = KirimPerintahStream($bot_token,$perintah,$data);
    }	

    //santuy
    if (!empty($hasil) and ($perintah == "sendMessage" or $perintah == "editMessageText")) usleep(30000);
    
    //debug
    $debug = json_decode($hasil,true);

	//kalau gagal kirim ke suatu chat id, non aktifkan
	if(empty($debug['ok']) and !empty($data['chat_id'])){
		//under
	}
	
    //laporan error
    if(!$debug['ok']){
		file_put_contents("last_error.txt", print_r([$perintah,$data,$bot_token],true)."\n\n".print_r($debug,true));
        print_r($debug);
    }
    
    return $debug;  
}

// function checkPrivateChatOnly($chat_id, $command, $message_id){
// 	global $config;
// 	if(isDiawali($chat_id,"-")){
// 		$bot_username = $config['bot_username'];
// 		KirimPerintah('sendMessage',[
// 			'chat_id' => $chat_id,
// 			'text'=> "<a href='t.me/$bot_username?start=cmd_$command'>Gunakan command ini di private chat</a>",
// 			'parse_mode'=>'HTML',
// 			'reply_to_message_id' => $message_id
// 		]);
// 		return false;
// 	}
// 	return true;
// }

function genKeyBoard($array, $max_col = 1){
	if(!is_array($array)){
		$array = [$array];
	}
	$curcol = 0;
	$cols = [];
	$rows = [];
	foreach($array as $data=>$text){
		$curcol ++;
		$cols[] = [
			'text' => $text,
			'callback_data' => $data
		];
		if($curcol == $max_col){
			$rows[] = $cols;
			$curcol = 0;
			$cols = [];
		}
	}
	if($curcol < $max_col and !empty($cols)){
		$rows[] = $cols;
	}
	// print_r($rows);
	$keyboard = [
		'inline_keyboard' => $rows
	];
	$encodedKeyboard = json_encode($keyboard);
	return $encodedKeyboard;
}

function inlineKeyBoard($array, $max_col = 0){
	if(empty($max_col)){
		$automaxcol = true;
	}
	else{
		$automaxcol = false;
	}
	if(!is_array($array)){
		$array = [$array];
	}
	$curcol = 0;
	$cols = [];
	$rows = [];
	foreach($array as $btnindex=>$button){
		if(!is_array($button)){
			$button = [$button];
		}
		if(count($button) == 1 and isset($button[0])){
			//default button for 1 non associative parameter
			$button = [
				'text'=>$button[0],
				'callback_data'=>$button[0]
			];
			unset($button[0]);
		}
		
		foreach($button as $key=>$val){
			if($key === 0 or $key === 1 or $key === 2){
				if(!isset($button['text'])){
					//non associative parameter: 1st = text
					$button['text'] = $button[$key];
				}
				elseif(!isset($button['url']) and !isset($button['callback_data'])){
					//non associative parameter: 2nd = callback_data or url
					if(strtolower(substr($button[$key],0,7)) == "http://" or strtolower(substr($button[$key],0,8)) == "https://"){
						$button['url'] = $button[$key];
					}
					else{
						$button['callback_data'] = $button[$key];
					}
				}
				elseif(!isset($button['width'])){
					//non associative parameter: 3rd = width
					$button['width'] = $button[$key];
				}
			}
		}
		if(empty($button['width'])){
			//default width
			$button['width'] = 1;
		}
		if($automaxcol and $button['width'] > $max_col){
			$max_col = $button['width'];
		}		
		if($button['width'] > $max_col){
			//max width
			$button['width'] = $max_col;
		}
		unset($button[0]);
		unset($button[1]);
		unset($button[2]);

		$nextbuttonwidth = 1;
		if(isset($array[$btnindex+1])){
			$nextbutton = $array[$btnindex+1];
			if(!is_array($nextbutton)){
				$nextbutton = [$nextbutton];
			}
			foreach($nextbutton as $key=>$val){
				if($key === 0 or $key === 1 or $key === 2){
					if(!isset($nextbutton['text'])){
						$nextbutton['text'] = "(next)";
					}
					elseif(!isset($nextbutton['url']) and !isset($nextbutton['callback_data'])){
						$nextbutton['url'] = "(next)";
					}
					elseif(!isset($nextbutton['width'])){
						$nextbutton['width'] = $nextbutton[$key];
					}
				}
			}
			if(empty($nextbutton['width'])){
				$nextbutton['width'] = 1;
			}
			$nextbuttonwidth = $nextbutton['width'];
		}

		$curcol += $button['width'];
		$cols[] = $button;
		if($curcol + $nextbuttonwidth > $max_col){
			//new line
			$rows[] = $cols;
			$curcol = 0;
			$cols = [];
		}
	}
	if($curcol < $max_col and !empty($cols)){
		$rows[] = $cols;
	}
	$keyboard = [
		'inline_keyboard' => $rows
	];
	// print_r($keyboard);
	$encodedKeyboard = json_encode($keyboard);
	return $encodedKeyboard;
}