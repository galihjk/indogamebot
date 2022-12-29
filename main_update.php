<?php 
/*
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
            'text' => 'srvstatus: '.print_r($srvstatus, true)."\nLag ".(time()-($srvstatus['time'] ?? 0)),
            'disable_web_page_preview' => true,
        ],$token);
    }
*/

//set variables

$message_data = $update["message"];
$dari = $message_data["from"]["id"];

//skip if blocked
if(in_array($dari,$blocked)){
    goto skip_blocked;
}

$chatid = (string) $message_data["chat"]["id"];
$message_id = $message_data["message_id"];

$dari_user = "@" . $message_data["from"]["username"];
if(isset($message_data["from"]["last_name"])){
    $lastname = " " . $message_data["from"]["last_name"];
}else{
    $lastname = "";
}
$nama = str_ireplace("'","''",$message_data["from"]["first_name"] . "$lastname");
$nama = str_replace("<","",str_replace(">","",$nama));
// $nama = $message_data["from"]["first_name"];
$jenis = $message_data["chat"]["type"]; //hasilnya "private" atau "group" atau "supergroup"		

$text = $message_data["text"];

//jika ada command menggunakan @
$isi = strtolower(trim($text));
$command = "";
if(substr($isi,0,1) == "/"){
    $isi = str_ireplace("@$botname","",$isi);
    $command = str_ireplace("@$botname","",$text);
}

//jika ada di supergroup, anggap group
if($jenis == "supergroup"){
    $jenis = "group";
}

//debug mode----------------
if($debugmode and $dari == $developer){
    $data = array(
        'chat_id' => $developer,
        'text'=> "idg_message:\n" . print_r($update,true),
        'parse_mode'=>'HTML'
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
if($dari == $developer and $isi == "/debugmode" and $debugmode == false){
    $debugmode = true;
    $output="Debug Mode ON";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'reply_to_message_id'=>$message_id
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}elseif($dari == $developer and $isi == "/debugmode"){
    $debugmode = false;
    $output="Debug Mode OFF";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'reply_to_message_id'=>$message_id
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
//------------------------------

//user checking
if(array_key_exists($dari,$activeusers)){
    if($chatid == $admingroup){
        $updateuser['admin'][$dari]['user_name'] = $dari_user;
        $updateuser['admin'][$dari]['name'] = $nama;
    }elseif(in_array($chatid,$groups)){
        if($activeusers[$dari]['user_name'] != $dari_user or $activeusers[$dari]['name'] != $nama){
            $output="ID $dari: ".$activeusers[$dari]['user_name']." ".$activeusers[$dari]['name']." $emoji_kanan $dari_user $nama";
            $data = array(
                'chat_id' => $admingroup,
                'text'=> $output,
                'parse_mode'=>'HTML'
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
            $activeusers[$dari]['user_name'] = $dari_user;
            $activeusers[$dari]['name'] = $nama;
        }
        $updateuser['group'][$dari]['user_name'] = $dari_user;
        $updateuser['group'][$dari]['name'] = $nama;
    }
    if($jenis == "private" and $activeusers[$dari]['private_active'] == '0'){
        $updateuser['private'][$dari]['user_name'] = $dari_user;
        $updateuser['private'][$dari]['name'] = $nama;
    }
}
else{
    //get user
    // $st = $app["pdo.DB_IDG"]->prepare("select user_name, name, warn, block, pingme, group_active, 
    // private_active, admin_active, first_active, admin_first_active from idg_users where user_id = '$dari'");
    // $st->execute();
    $sdata = sdata_find($db_alias."_USERS", ['user_id'=>$dari], 1, [
        'user_name', 'name', 'warn', 'block', 'pingme', 'group_active', 
        'private_active', 'admin_active', 'first_active', 'admin_first_active',
    ]);
    $ada = false;
    $first_active = "";
    $admin_first_active = "";
    // while($row = $st->fetch(PDO::FETCH_ASSOC)){
    foreach($sdata as $row){
        $first_active = $row['first_active'];
        $admin_first_active = $row['admin_first_active'];
        $ada = true;
        $activeusers[$dari]['user_name'] = $row['user_name'];
        $activeusers[$dari]['name'] = $row['name'];
        $activeusers[$dari]['warn'] = $row['warn'];
        $activeusers[$dari]['block'] = $row['block'];
        $activeusers[$dari]['pingme'] = $row['pingme'];
        $activeusers[$dari]['group_active'] = $row['group_active'];
        $activeusers[$dari]['private_active'] = $row['private_active'];
        $activeusers[$dari]['admin_active'] = $row['admin_active'];
    }
    if($ada){
        if($chatid == $admingroup and empty($admin_first_active)){

            // $st = $app["pdo.DB_IDG"]->prepare("update idg_users set
            // admin_first_active = now(), admin_active = '1' where user_id = '$dari'");
            // $st->execute();
            sdata_update_filtered($db_alias."_USERS",[
                'user_id' => $dari,
            ], [
                'admin_first_active' => time(),
                'admin_active' => 1,
            ],1);
        }elseif(in_array($chatid,$groups) and empty($first_active)){
            // $st = $app["pdo.DB_IDG"]->prepare("update idg_users set
            // first_active = now(), last_active = now(), group_active = '1' where user_id = '$dari'");
            // $st->execute();
            sdata_update_filtered($db_alias."_USERS",[
                'user_id' => $dari,
            ], [
                'first_active' => time(),
                'last_active' => time(),
                'group_active' => 1,
            ],1);
        }elseif($jenis == "private" and $activeusers[$dari]['private_active'] != '1'){
            // $st = $app["pdo.DB_IDG"]->prepare("update idg_users set
            // private_active = '1' where user_id = '$dari'");
            // $st->execute();
            sdata_update_filtered($db_alias."_USERS",[
                'user_id' => $dari,
            ], [
                'private_active' => 1,
            ],1);
            $activeusers[$dari]['private_active'] == '1';
        }
    }else{
        //insert user
        if($chatid == $admingroup){
            // $st = $app["pdo.DB_IDG"]->prepare("insert into idg_users
            // (user_id, user_name, name, first_active, last_active, warn, block, pingme, admin_active, admin_first_active)
            // values
            // ('$dari', '$dari_user', '$nama', now(), now(), '0', 0, '0', '1', now())");
            // $st->execute();
            sdata_insert($db_alias."_USERS",[
                'user_id' => $dari,
                'user_name' => $dari_user,
                'name' => $nama,
                'first_active' => time(),
                'last_active' => time(),
                'warn' => 0,
                'pingme' => 0,
                'admin_active' => 1,
                'admin_first_active' => time(),
            ]);
            $activeusers[$dari]['admin_active'] = 1;
        }elseif(in_array($chatid,$groups)){
            // $st = $app["pdo.DB_IDG"]->prepare("insert into idg_users
            // (user_id, user_name, name, first_active, last_active, warn, block, pingme, group_active)
            // values
            // ('$dari', '$dari_user', '$nama', now(), now(), '0', 0, '0', '1')");
            // $st->execute();
            sdata_insert($db_alias."_USERS",[
                'user_id' => $dari,
                'user_name' => $dari_user,
                'name' => $nama,
                'first_active' => time(),
                'last_active' => time(),
                'warn' => 0,
                'pingme' => 0,
                'group_active' => 1,
            ]);
        }
        elseif($jenis == "private"){
            // $st = $app["pdo.DB_IDG"]->prepare("insert into idg_users
            // (user_id, user_name, name, first_active, last_active, warn, block, pingme, private_active)
            // values
            // ('$dari', '$dari_user', '$nama', now(), now(), '0', 0, '0', '1')");
            // $st->execute();
            sdata_insert($db_alias."_USERS",[
                'user_id' => $dari,
                'user_name' => $dari_user,
                'name' => $nama,
                'first_active' => time(),
                'last_active' => time(),
                'warn' => 0,
                'pingme' => 0,
                'private_active' => 1,
            ]);
        }
        if($chatid == $admingroup or in_array($chatid,$groups) or $jenis == "private"){
            $activeusers[$dari]['user_name'] = $dari_user;
            $activeusers[$dari]['name'] = $nama;
            $activeusers[$dari]['warn'] = 0;
            $activeusers[$dari]['block'] = 0;
            $activeusers[$dari]['pingme'] = 0;
            if(in_array($chatid,$groups)){
                $activeusers[$dari]['group_active'] = 1;
            }else{
                $activeusers[$dari]['group_active'] = 0;
            }
            if($jenis == "private"){
                $activeusers[$dari]['private_active'] = 1;
            }else{
                $activeusers[$dari]['private_active'] = 0;
            }
            if($chatid == $admingroup){
                $activeusers[$dari]['admin_active'] = 1;
            }else{
                $activeusers[$dari]['admin_active'] = 0;
            }
        }
    }
}

//sticker collection
/*
    if(isset($message_data['sticker'])){
        $sticker_file_id = $message_data['sticker']['file_id'];
        $sticker_set_name = $message_data['sticker']['set_name'];
        if(!in_array($sticker_set_name,$stickerset)){
            $sticker_set = json_decode(file_get_contents("https://api.telegram.org/bot$token/getStickerSet?name=$sticker_set_name"),true);
            $stickerset[] = $sticker_set_name;
            if(!empty($sticker_set['result']['stickers']) and count($sticker_set['result']['stickers']) > 3){
                $st = $app["pdo.DB_IDG"]->prepare("update idg_settings set
                value='".serialize($stickerset)."' where key='stc'");
                $st->execute();
                
                $data = array(
                    'chat_id' => '@indogamesticker',
                    'sticker'=> $sticker_file_id,
                );	
                $hasil = KirimPerintahX($token,'sendSticker',$data);	
                
                $message_out = json_decode($hasil,true);
                
                $data = array(
                    'chat_id' => '@indogamesticker',
                    'text'=> "-- <b>".$sticker_set['result']['title']."</b> -- \n<a href='https://t.me/addstickers/$sticker_set_name'>$sticker_set_name</a> \nFirst Detected: <a href='https://t.me/".$message_data["chat"]["username"]."/$message_id'>~".$message_data["from"]["first_name"]."</a>",
                    'parse_mode'=>'HTML',
                    'reply_to_message_id' => $message_out['result']['message_id'],
                    );
                $hasil = KirimPerintahX($token,'sendMessage',$data);					
            }

        }
    }
*/

//chat members 
if (isset($message_data['new_chat_members'])) {
    foreach($message_data['new_chat_members'] as $new_chat_member){
        if($new_chat_member['username'] == $botname){
            $output="Hai, aku adalah bot spesial untuk group @indogame :D";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'reply_to_message_id' => $message_id
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
            if($chatid != $admingroup and !in_array($chatid,$groups) and !$debugmode){
                if(!empty($activeusers[$dari]['admin_active'])){
                    //kalau yang memasukkan adalah admin
                    $groups[] = $chatid;
                    // $st = $app["pdo.DB_IDG"]->prepare("update idg_settings set
                    // value='".implode(", ",$groups)."' where key='allowed_groups'");
                    // $st->execute();
                    sdata_update_filtered($db_alias."_SETTINGS",[
                        'key'=>'allowed_groups',
                    ],[
                        'value'=>implode(", ",$groups),
                    ],1);
                    $data = array(
                        'chat_id' => $admingroup,
                        'text'=> "Group ".$message_data["chat"]["title"]." (ID: $chatid) telah ditambahkan",
                        'parse_mode'=>'HTML'
                        );
                    $hasil = KirimPerintahX($token,'sendMessage',$data);
                }
                else{
                    $output="Tempatku bukan di sini, aku left yaa thanks :D";
                    $data = array(
                        'chat_id' => $chatid,
                        'text'=> $output,
                        'parse_mode'=>'HTML'
                        );
                    $hasil = KirimPerintahX($token,'sendMessage',$data);
                    $data = array(
                        'chat_id' => $chatid
                        );
                    $hasil = KirimPerintahX($token,'leaveChat',$data);
                    $data = array(
                        'chat_id' => $admingroup,
                        'text'=> "Ada yang mencoba memasukkanku ke groupnya: \nID: $chatid \nGrup: ".$message_data["chat"]["title"]." \nOleh: $nama $dari_user ($dari)",
                        'parse_mode'=>'HTML'
                        );
                    $hasil = KirimPerintahX($token,'sendMessage',$data);	
                }
            }
        }
        elseif($chatid == $admingroup){
            // $st = $app["pdo.DB_IDG"]->prepare("update idg_users set
            // admin_joined_date = now(), admin_active = '1' where user_id = '$dari'");
            // $st->execute();
            sdata_update_filtered($db_alias."_USERS",[
                'user_id'=>$dari,
            ],[
                'admin_joined_date'=>time(),
                'admin_active'=>1,
            ],1);
            $output="Selamat datang @" . $new_chat_member['username'] . " " . $new_chat_member['first_name'] . " " . $new_chat_member['last_name'] . " di group admin bot @$botname :D";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'reply_to_message_id' => $message_id
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
        elseif(in_array($chatid,$groups)){
            // $st = $app["pdo.DB_IDG"]->prepare("update idg_users set
            // joined_date = now(), group_active = '1' where user_id = '$dari'");
            // $st->execute();
            sdata_update_filtered($db_alias."_USERS",[
                'user_id'=>$dari,
            ],[
                'joined_date'=>time(),
                'group_active'=>1,
            ],1);
            if(empty($new_chat_member['is_bot'])){
                $temp = "member";
            }else{
                $temp = "<b>BOT</b>";
            }
            if ($chatid == $maingroup){
                $output="$chatid~ Ada $temp yang baru join: @" . $new_chat_member['username'] . " " . $new_chat_member['first_name'] . " " . $new_chat_member['last_name'] . "\nID: " . $new_chat_member['id'] . " \n<i>*Pesan ini bisa dibalas. </i><a href='t.me/groupindogame/$message_id'>$message_id</a>";
            }
            elseif($chatid == '-1001149199985'){
                $output="$chatid~ Ada $temp yang baru join: @" . $new_chat_member['username'] . " " . $new_chat_member['first_name'] . " " . $new_chat_member['last_name'] . "\nID: " . $new_chat_member['id'] . " \n<i>*Pesan ini bisa dibalas. </i><a href='t.me/werewolfindogame/$message_id'>$message_id</a>";
            }
            else{
                $output="$chatid~ Ada $temp yang baru join: @" . $new_chat_member['username'] . " " . $new_chat_member['first_name'] . " " . $new_chat_member['last_name'] . "\nID: " . $new_chat_member['id'] . " \nGroup: " . $message_data["chat"]["title"];
            }
            $data = array(
                'chat_id' => $admingroup,
                'text'=> $output,
                'parse_mode'=>'HTML'
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
            if($temp = "member"){
                if(!empty($welcome_message)){
                    $output = $welcome_message;
                    $reply_markup = json_encode([]);
                    $diapit_start = strpos($output, '(btn=');
                    $diapit_end = strpos($output, '(/btn)');
                    while($diapit_start !== false and $diapit_end !== false){
                        $diapit_start += strlen('(btn=');
                        $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                        $output = str_ireplace("(btn=$diapit(/btn)","",$output);
                        $explode = explode(")",$diapit);
                        $btnurl = $explode[0];
                        $btncapt = str_replace("$btnurl)","",$diapit);
                        if(!empty($btnurl) and !empty($btncapt)){
                            $reply_markup = addUrlButton($btnurl,$btncapt,$reply_markup);
                        }
                        $diapit_start = strpos($output, '(btn=');
                        $diapit_end = strpos($output, '(/btn)');
                    }
                    
                    
                    $output = str_ireplace('(sbj_dpn)',$new_chat_member["first_name"],$output);
                    $output = str_ireplace('(sbj_blk)',$new_chat_member["last_name"],$output);
                    $output = str_ireplace('(sbj_un)',$new_chat_member["username"],$output);
                    $output = str_ireplace('(sbj_id)',$new_chat_member["id"],$output);
                    $output = str_ireplace('(sbj)',$nama,$output);
                    $obj_first_name = $new_chat_member["first_name"];		
                    $obj_last_name = $new_chat_member["last_name"];
                    if(empty($obj_last_name)){
                        $obj_nama = $obj_first_name;
                    }else{
                        $obj_nama = $obj_first_name . " " . $obj_last_name;
                    }
                    $output = str_ireplace('(obj_dpn)',$obj_first_name,$output);
                    $output = str_ireplace('(obj_blk)',$obj_last_name,$output);
                    $output = str_ireplace('(obj_un)',$new_chat_member["username"],$output);
                    $output = str_ireplace('(obj_id)',$new_chat_member["id"],$output);
                    $output = str_ireplace('(obj)',$obj_nama,$output);
                    $output = str_ireplace('@sbj(',"<a href='tg://user?id=".$new_chat_member["id"]."'>",$output);
                    $output = str_ireplace('@obj(',"<a href='tg://user?id=".$new_chat_member["id"]."'>",$output);
                    $output = str_ireplace(')@','</a>',$output);
                    
                    $data = array(
                        'chat_id' => $chatid,
                        'text'=> $output,
                        'parse_mode'=>'HTML',
                        'disable_web_page_preview'=>true
                    );
                        
                    if(!empty(json_decode($reply_markup,1))){
                        $data['reply_markup'] = $reply_markup;
                    }
                    $hasil = KirimPerintahX($token,'sendMessage',$data);
                }
            }
        }
    }
}


//chat left
/*
    if (isset($message_data['left_chat_member'])) {
        if($chatid == $admingroup){
            $st = $app["pdo.DB_IDG"]->prepare("update idg_users set
            admin_left_date = now(), out_count = coalesce(out_count,0) + 1, admin_active = '0' where user_id = '$dari'");
            $st->execute();
            $temp = "<b>ADMIN</b>";
        }elseif(in_array($chatid,$groups)){
            $st = $app["pdo.DB_IDG"]->prepare("update idg_users set
            left_date = now(), out_count = coalesce(out_count,0) + 1, group_active = '0' where user_id = '$dari'");
            $st->execute();
            if(empty($message_data['left_chat_member']['is_bot'])){
                $temp = "member";
            }else{
                $temp = "<b>BOT</b>";
            }
        }
        $output="$chatid~ Ada $temp yang left: @" . $message_data['left_chat_member']['username'] . " " . $message_data['left_chat_member']['first_name'] . " " . $message_data['left_chat_member']['last_name'] . "\nID: " . $message_data['left_chat_member']['id'] . " \n<i>*Pesan ini bisa dibalas. </i><a href='t.me/groupindogame/$message_id'>$message_id</a>";
        $data = array(
            'chat_id' => $admingroup,
            'text'=> $output,
            'parse_mode'=>'HTML'
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
*/

//balas chat join / left
elseif($chatid == $admingroup and !empty($message_data['reply_to_message'])
and $message_data['reply_to_message']['from']['username'] == $botname
and strpos($message_data['reply_to_message']['text'],"*Pesan ini bisa dibalas. ") !== false ){
    $explode = explode("*Pesan ini bisa dibalas. ",$message_data['reply_to_message']['text']);
    $message_idnya = $explode[1];
    $chatidnya = explode("~",$message_data['reply_to_message']['text'])[0];
    if(!in_array($chatidnya,$groups)){
        $chatidnya = $maingroup;
        $data = array(
            'chat_id' => $admingroup,
            'text'=> "ERROR $chatidnya",
            'parse_mode'=>'HTML'
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    // echo "idg_message_idnya$message_idnya idg_chatidnya$chatidnya\n";
    $textnya = $message_data['text'];
    include('convert_message.php');
    $output = $textnya;
    if(substr($output,0,7) == "*photo "){
        $explode = explode("|",$output);
        $file_id = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'photo'=> $file_id,
            'caption' => str_replace("*photo ","",$explode[0]),
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendPhoto',$data);	
    }elseif(substr($output,0,9) == "*sticker "){
        $explode = explode("|",$output);
        $stickernya = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'sticker'=> $stickernya,
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendSticker',$data);	
    }elseif(substr($output,0,10) == "*document "){
        $explode = explode("|",$output);
        $file_id = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'document'=> $file_id,
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendDocument',$data);	
    }elseif(substr($output,0,7) == "*voice "){
        $explode = explode("|",$output);
        $file_id = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'voice'=> $file_id,
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendVoice',$data);	
    }elseif(substr($output,0,7) == "*location "){
        $explode = explode("|",$output);
        $explode2 = explode(",",$explode[1]);
        $data = array(
            'chat_id' => $chatidnya,
            'latitude'=> $explode2[0],
            'longitude'=> $explode2[1],
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendLocation',$data);	
    }else{
        $data = array(
            'chat_id' => $chatidnya,
            'text'=> $output,
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_idnya
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}

//developer
/*
    if(($chatid == $admingroup or $dari == $developer) 
    and substr($isi,0,3) == '/q '){
        // $q = str_ireplace('/q ','',$isi);
        // $st = $app["pdo.DB_IDG"]->prepare($q);
        // $st->execute();
        $output_arr = array();
        while($row = $st->fetch(PDO::FETCH_ASSOC)){
            $output_arr[] = $row;
        };
        if(empty($output_arr)){
            $data = array(
                'chat_id' => $chatid,
                'text'=> "OK\n" . print_r($st->errorInfo(),true),
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id' => $message_id
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);							
        }
        else{
            $columns = array_keys($output_arr[0]);
            $first = true;
            foreach($columns as $column){
                $output = "<b>$column</b>\n";
                $rows = array_column($output_arr, $column);
                foreach($rows as $key=>$val){
                    $output .= $key+1 . ". $val\n";
                }
                if($first){
                    $first = false;
                    $message_idnya = $message_id;
                }else{
                    $message_idnya = "";
                }
                $data = array(
                    'chat_id' => $chatid,
                    'text'=> $output,
                    'parse_mode'=>'HTML',
                    'disable_web_page_preview'=>true,
                    'reply_to_message_id' => $message_idnya
                    );
                $hasil = KirimPerintahX($token,'sendMessage',$data);						
            }
        }
    }
*/

if(($chatid == $admingroup or $dari == $developer) 
and $isi == '/debug'){
    $output = "idg_updateuser" . print_r ($updateuser,true);
    $output .= "\nidg_update_interval$update_interval";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id' => $message_id
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
if(($chatid == $admingroup or $dari == $developer) 
and $isi == '/activeuser'){
    print_r($activeusers);
    $output = "idg_activeusers" . print_r ($activeusers,true);
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id' => $message_id
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}elseif(isset($update["callback_query"])){
    $callback_query = $update["callback_query"];
    $callback_chatid = $callback_query['message']['chat']['id'];
    $callback_msgid = $callback_query['message']['message_id'];
    $callback_data = $callback_query['data'];
    $callback_fromid = $callback_query['from']['id'];
    if(strpos($callback_query['message']['text'],"etor angka") !== false
    and !empty($gamedata['lncn'][$callback_chatid]['started'])
    and !empty($gamedata['lncn'][$callback_chatid]['players'][$callback_fromid])
    and $gamedata['lncn'][$callback_chatid]['step'] == "sedangsetor"){
        //game lncn
        if($callback_data == "luck1"){
            $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['ln_stored'] = 1;
        }elseif($callback_data == "luck2"){
            $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['ln_stored'] = 2;
        }elseif($callback_data == "luck3"){
            $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['ln_stored'] = 3;
        }elseif($callback_data == "cursed1"){
            $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['cn_stored'] = 1;
        }elseif($callback_data == "cursed2"){
            $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['cn_stored'] = 2;
        }elseif($callback_data == "cursed3"){
            $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['cn_stored'] = 3;
        }
        $output = "";
        $count = 0;
        if(substr($callback_data,0,4) == "luck"){					
            foreach($gamedata['lncn'][$callback_chatid]['players'] as $playerid=>$player){
                if(empty($player['ln_stored'])){
                    $output .= $player['nama']."\n";
                    $count++;
                }
            }
            $output .= "Setor angka untuk $emoji_check <b>LUCKY NUMBER</b>";
            $button = array();
            $button['luck1'] = '1';
            $button['luck2'] = '2';
            $button['luck3'] = '3';
        }
        else{
            foreach($gamedata['lncn'][$callback_chatid]['players'] as $playerid=>$player){
                if(empty($player['ln_stored'])){
                    $output .= $player['nama']."\n";
                    $count++;
                }
            }
            $output .= "Setor angka untuk $emoji_skull <b>CURSED NUMBER</b>";
            $button = array();
            $button['cursed1'] = '1';
            $button['cursed2'] = '2';
            $button['cursed3'] = '3';
        }
        if($count == 0){
            $data = array(
                'chat_id' => $callback_chatid,
                'message_id'=> $callback_msgid
                );
            $hasil = KirimPerintahX($token,'deleteMessage',$data);
        }
        else{
            $data = array(
                'chat_id' => $callback_chatid,
                'message_id' => $callback_msgid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_markup' => makebutton($button)
            );
            $hasil = KirimPerintahX($token,'editMessageText',$data);
        }
    }
    elseif(substr($callback_data,0,4) == "pick"
    and !empty($gamedata['lncn'][$callback_chatid]['started'])
    and !empty($gamedata['lncn'][$callback_chatid]['players'][$callback_fromid])
    and !empty($gamedata['lncn'][$callback_chatid]['unpicked'][str_ireplace('pick','',$callback_data)])){
        $pickednumber = str_ireplace('pick','',$callback_data);
        $playercount = count($gamedata['lncn'][$callback_chatid]['players']);
        if(empty($gamedata['lncn'][$callback_chatid]['lucky']) and empty($gamedata['lncn'][$callback_chatid]['cursed'])){
            $sekian = floor($gamedata['lncn'][$callback_chatid]['numcount']/$playercount);
        }
        else{
            $sekian = floor($gamedata['lncn'][$callback_chatid]['numcount']/($playercount-1));
        }
        
        if(!empty($gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['got_lc'])
        or count($gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['picked']) >= $sekian){
            $data = array(
                'callback_query_id' => $callback_query['id'],
                'text'=> "Kamu tidak bisa ambil angka lagi.",
                'show_alert'=> true
            );
            $hasil = KirimPerintahX($token,'answerCallbackQuery',$data);
        }
        else{
            $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['picked'][] = $pickednumber;
            unset($gamedata['lncn'][$callback_chatid]['unpicked'][$pickednumber]);
            if($pickednumber == $gamedata['lncn'][$callback_chatid]['ln']){
                $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['got_lc'] = "ln";
                $gamedata['lncn'][$callback_chatid]['lucky'] = $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['nama'];
                $gamedata['lncn'][$callback_chatid]['numcount'] -= count($gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['picked']);
                $data = array(
                    'chat_id' => $callback_chatid,
                    'text'=> $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['nama'] . " mendapat $emoji_check <b>LUCKY NUMBER</b> !!",
                    'parse_mode'=>'HTML'
                    );
                $hasil = KirimPerintahX($token,'sendMessage',$data);	
            }elseif($pickednumber == $gamedata['lncn'][$callback_chatid]['cn']){
                $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['got_lc'] = "cn";
                $gamedata['lncn'][$callback_chatid]['cursed'] = strip_tags($gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['nama']);
                $gamedata['lncn'][$callback_chatid]['numcount'] -= count($gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['picked']);
                $data = array(
                    'chat_id' => $callback_chatid,
                    'text'=> $gamedata['lncn'][$callback_chatid]['players'][$callback_fromid]['nama'] . " mendapat $emoji_skull <b>CURSED NUMBER</b> !!",
                    'parse_mode'=>'HTML'
                    );
                $hasil = KirimPerintahX($token,'sendMessage',$data);	
            }
            $playercount = count($gamedata['lncn'][$callback_chatid]['players']);
            if(empty($gamedata['lncn'][$callback_chatid]['lucky']) and empty($gamedata['lncn'][$callback_chatid]['cursed'])){
                $sekian = floor($gamedata['lncn'][$callback_chatid]['numcount']/$playercount);
            }
            else{
                $sekian = floor($gamedata['lncn'][$callback_chatid]['numcount']/($playercount-1));
            }	
            $output = "";
            foreach($gamedata['lncn'][$callback_chatid]['players'] as $playerid=>$player){
                if($player['got_lc'] == "ln"
                or $player['got_lc'] == "cn"
                or count($player['picked'])>=$sekian){
                    $output .= strip_tags($player['nama']).": ";
                }else{
                    $output .= $player['nama'].": ";
                }
                $output .= implode(", ",$player['picked']);
                if($player['got_lc'] == "ln"){
                    $output .= $emoji_check;
                }
                elseif($player['got_lc'] == "cn"){
                    $output .= $emoji_skull;
                }elseif(count($player['picked'])>=$sekian){
                    $output .= " $emoji_no";
                }else{
                    $output .= " $emoji_kanan";
                }
                $output .= "\n\n";
            }
            $output .= "Pilih $sekian angka!";
            $button = array();
            foreach($gamedata['lncn'][$callback_chatid]['unpicked'] as $val){
                $button["pick$val"] = $val;
            }
            $data = array(
                'chat_id' => $callback_chatid,
                'message_id' => $callback_msgid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_markup' => makebutton($button)
            );
            $hasil = KirimPerintahX($token,'editMessageText',$data);
        }
    }
    elseif($callback_chatid == $admingroup){
        //tombol "beres!" di bot admin grup
        $explode = explode("Balas di sini. ID: ",$callback_query['message']['text']);
        $output = "$emoji_check ".$callback_query['from']['first_name']." ".$callback_query['from']['last_name']." \n";
        $output .= "Balas di sini. ID: " . $explode[1];
        $button1['text'] = "$emoji_pesan KE PESAN";
        if(explode("l",$explode[1])[0] == "0"){
            $button1['url'] = "t.me/groupindogame/$callback_data";					
        }
        elseif($groups[explode("l",$explode[1])[0]] == '-1001149199985'){
            $button1['url'] = "t.me/werewolfindogame/$callback_data";					
        }
        else{
            $button1['text'] = "(TIDAK ADA LINK KE PESAN)";
            $button1['url'] = "t.me/indogame";	
        }
        $keyboard['inline_keyboard'] = array(array($button1));
        $encodedKeyboard = json_encode($keyboard);						
        $data = array(
            'chat_id' => $callback_chatid,
            'message_id' => $callback_msgid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_markup' => $encodedKeyboard
        );
        $hasil = KirimPerintahX($token,'editMessageText',$data);			
        $data = array(
            'chat_id' => $callback_chatid,
            'message_id' => $admin_mentions[$explode[1]],
        );
        $hasil = KirimPerintahX($token,'deleteMessage',$data);			
        unset($admin_mentions[$explode[1]]);
    }
    else{
        $data = array(
            'callback_query_id' => $callback_query['id'],
            'text'=> "Tombol sudah tidak bisa dipakai.",
            // 'show_alert'=> true
        );
        $hasil = KirimPerintahX($token,'answerCallbackQuery',$data); 
    }
}
elseif($chatid == $admingroup and !empty($message_data['reply_to_message'])
and $message_data['reply_to_message']['from']['username'] == $botname
and strpos($message_data['reply_to_message']['text'],"alas di sini. ID: ") !== false ){
    $explode = explode("Balas di sini. ID: ",$message_data['reply_to_message']['text']);
    $message_idnya = $explode[1];
    $chatidnya = explode("l",$message_idnya)[0];
    $message_idnya = str_ireplace("$chatidnya"."l","",$message_idnya);
    $chatidnya = $groups[$chatidnya];
    // echo "idg_message_idnya$message_idnya idg_chatidnya$chatidnya\n";
    $textnya = $message_data['text'];
    include('convert_message.php');
    $output = $textnya;
    if(substr($output,0,7) == "*photo "){
        $explode = explode("|",$output);
        $file_id = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'photo'=> $file_id,
            'caption' => str_replace("*photo ","",$explode[0]),
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendPhoto',$data);	
    }elseif(substr($output,0,9) == "*sticker "){
        $explode = explode("|",$output);
        $stickernya = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'sticker'=> $stickernya,
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendSticker',$data);	
    }elseif(substr($output,0,10) == "*document "){
        $explode = explode("|",$output);
        $file_id = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'document'=> $file_id,
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendDocument',$data);	
    }elseif(substr($output,0,7) == "*voice "){
        $explode = explode("|",$output);
        $file_id = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'voice'=> $file_id,
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendVoice',$data);	
    }elseif(substr($output,0,7) == "*location "){
        $explode = explode("|",$output);
        $explode2 = explode(",",$explode[1]);
        $data = array(
            'chat_id' => $chatidnya,
            'latitude'=> $explode2[0],
            'longitude'=> $explode2[1],
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendLocation',$data);	
    }else{
        $data = array(
            'chat_id' => $chatidnya,
            'text'=> $output,
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_idnya
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    $explode = explode("Balas di sini. ID: ",$message_data['reply_to_message']['text']);
    $output = "$emoji_check $nama\n";
    
    $output .= "Balas di sini. ID: " . $explode[1];
    $button1['text'] = "$emoji_pesan KE PESAN";
    if(explode("l",$explode[1])[0] == "0"){
        $button1['url'] = "t.me/groupindogame/".$message_data['reply_to_message']['message_id'];					
    }
    elseif($groups[explode("l",$explode[1])[0]] == '-1001149199985'){
        $button1['url'] = "t.me/werewolfindogame/".$message_data['reply_to_message']['message_id'];					
    }
    else{
        $button1['text'] = "(TIDAK ADA LINK KE PESAN)";
        $button1['url'] = "t.me/indogame";	
    }
    
    $keyboard['inline_keyboard'] = array(array($button1));
    $encodedKeyboard = json_encode($keyboard);						
    $data = array(
        'chat_id' => $chatid,
        'message_id' => $message_data['reply_to_message']['message_id'],
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_markup' => $encodedKeyboard
    );
    $hasil = KirimPerintahX($token,'editMessageText',$data);				
    $data = array(
        'chat_id' => $chatid,
        'message_id' => $admin_mentions[$explode[1]],
    );
    $hasil = KirimPerintahX($token,'deleteMessage',$data);			
    unset($admin_mentions[$explode[1]]);
}
elseif($chatid == $pmgroup and !empty($message_data['reply_to_message'])
and $message_data['reply_to_message']['from']['username'] == $botname){
    $cancel = false;
    if(strpos($message_data['reply_to_message']['text'],"eply mode: ") === false 
    and isset($message_data['reply_to_message']['forward_from'])){
        $chatidnya = $message_data['reply_to_message']['forward_from']['id'];
        $message_idnya = "";
    }
    elseif(strpos($message_data['reply_to_message']['text'],"eply mode: ") !== false){
        $explode = explode("eply mode: ",$message_data['reply_to_message']['text']);
        $message_idnya = $explode[1];
        $chatidnya = explode("l",$message_idnya)[0];
        $message_idnya = str_ireplace("$chatidnya"."l","",$message_idnya);	
    }
    else{
        $cancel = true;
    }
    if(!$cancel){
        $textnya = $message_data['text'];
        include('convert_message.php');
        $output = $textnya;
        if(substr($output,0,7) == "*photo "){
            $explode = explode("|",$output);
            $file_id = $explode[1];
            $data = array(
                'chat_id' => $chatidnya,
                'photo'=> $file_id,
                'caption' => str_replace("*photo ","",$explode[0]),
                'reply_to_message_id' => $message_idnya
            );	
            $hasil = KirimPerintahX($token,'sendPhoto',$data);	
        }elseif(substr($output,0,9) == "*sticker "){
            $explode = explode("|",$output);
            $stickernya = $explode[1];
            $data = array(
                'chat_id' => $chatidnya,
                'sticker'=> $stickernya,
                'reply_to_message_id' => $message_idnya
            );	
            $hasil = KirimPerintahX($token,'sendSticker',$data);	
        }elseif(substr($output,0,10) == "*document "){
            $explode = explode("|",$output);
            $file_id = $explode[1];
            $data = array(
                'chat_id' => $chatidnya,
                'document'=> $file_id,
                'reply_to_message_id' => $message_idnya
            );	
            $hasil = KirimPerintahX($token,'sendDocument',$data);	
        }elseif(substr($output,0,7) == "*voice "){
            $explode = explode("|",$output);
            $file_id = $explode[1];
            $data = array(
                'chat_id' => $chatidnya,
                'voice'=> $file_id,
                'reply_to_message_id' => $message_idnya
            );	
            $hasil = KirimPerintahX($token,'sendVoice',$data);	
        }elseif(substr($output,0,7) == "*location "){
            $explode = explode("|",$output);
            $explode2 = explode(",",$explode[1]);
            $data = array(
                'chat_id' => $chatidnya,
                'latitude'=> $explode2[0],
                'longitude'=> $explode2[1],
                'reply_to_message_id' => $message_idnya
            );	
            $hasil = KirimPerintahX($token,'sendLocation',$data);	
        }else{
            $data = array(
                'chat_id' => $chatidnya,
                'text'=> $output,
                'disable_web_page_preview'=>true,
                'reply_to_message_id' => $message_idnya
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
        $output = "$emoji_check $nama\n";
        $output .= "Reply mode: ".$chatidnya."l" . $message_idnya;
        $data = array(
            'chat_id' => $chatid,
            'message_id' => $message_data['reply_to_message']['message_id'],
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true
        );
        $hasil = KirimPerintahX($token,'editMessageText',$data);	
    }
}
//admin group
elseif($chatid == $admingroup and substr($isi,0,4) == "/pm_"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "jalanin command ini di grup PM yaa!",
        'reply_to_message_id' => $message_id
    ]);
}
elseif($chatid == $admingroup and substr($isi,0,8) == "/testbtn"){
    $reply_markup = json_encode([]);
    if(!isset($testbtns)){
        $testbtns = [];
    }
    $newbtn = str_ireplace("/testbtn","",$isi);
    if(!empty($newbtn)){
        $testbtns[] = $newbtn;
    }
    foreach($testbtns as $btnnya){
        $reply_markup = addUrlButton($btnnya,$btnnya,$reply_markup);
    }
    $data = array(
        'chat_id' => $chatid,
        'text'=> "Test Button",
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        );
    if(!empty(json_decode($reply_markup,1))){
        $data['reply_markup'] = $reply_markup;
    }
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif($chatid == $pmgroup and substr($isi,0,4) == "/pm_"){
    $output = "Reply mode: " . str_ireplace("/pm_","",$isi) . "l";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'reply_markup' => $forcereply,
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif($chatid == $admingroup and substr($isi,0,4) == "/id_"){
    // $st = $app["pdo.DB_IDG"]->prepare("
    // select user_name, user_id, name, block, pingme, group_active, private_active, admin_active, first_active, last_active, admin_first_active, telp from idg_users 
    // where user_id = '".str_ireplace("/id_","",$isi)."'");
    // $st->execute();
    // while($row = $st->fetch(PDO::FETCH_ASSOC)){
    $sdata = sdata_find($db_alias."_USERS",[
        'user_id'=>str_ireplace("/id_","",$isi),
    ],1,[
        'user_name', 'user_id', 'name', 'block', 'pingme', 'group_active',
        'private_active', 'admin_active', 'first_active', 'last_active', 'admin_first_active', 'telp',
    ]);
    foreach($sdata as $row){
        if($row['private_active']){
            $pmnya = "/pm_" . $row['user_id'];
        }
        else{
            $pmnya = "OFF";
        }
        $output = "user detail:\n
user_name = ".$row['user_name'].",
name = ".$row['name'].",
telp = ".$row['telp'].",
block = ".$row['block'].",
private_active = ".$pmnya.", 
first_active = ".substr($row['first_active'],0,11)."
last_active = ".substr($row['last_active'],0,11)."
admin_active = ".substr($row['admin_first_active'],0,11).", 
--
        ";
        
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id'=>$message_id
        );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    

}
elseif($chatid == $admingroup and $isi == "/block"){
    $data = array(
        'chat_id' => $chatid,
        'text'=> "format:\n/block(spasi)id",
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);	
}
elseif($chatid == $admingroup and substr($isi,0,strlen("/block "))  == "/block "){
    $idnya = str_ireplace("/block ","",$isi);
    $blocked[] = $idnya;
    // $st = $app["pdo.DB_IDG"]->prepare("update idg_users set block = '1' where user_id='$idnya'");
    // $st->execute();
    $data = array(
        'chat_id' => $chatid,
        'text'=> "OK",
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);	
}
elseif($chatid == $admingroup and $isi == "/unblock"){
    $data = array(
        'chat_id' => $chatid,
        'text'=> "format:\n/unblock(spasi)id",
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);	
}
elseif($chatid == $admingroup and substr($isi,0,strlen("/unblock "))  == "/unblock "){
    $idnya = str_ireplace("/unblock ","",$isi);
    $keynya = array_search($idnya,$blocked);
    unset($blocked[$keynya]);
    // $st = $app["pdo.DB_IDG"]->prepare("update idg_users set block = '0' where user_id='$idnya'");
    // $st->execute();
    $data = array(
        'chat_id' => $chatid,
        'text'=> "OK",
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);	
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/cmd"){
    // $output = "/msgcmd - <i>message command</i>: otomatis kirim pesan untuk <i>keyword</i> tertentu\n";
    $output = "/msgcmd - <i>message command</i>: otomatis kirim pesan untuk <i>keyword</i> tertentu\n";
    $output .= "/settings - pengaturan\n";
    $output .= "/kirim - mengirim pesan\n";
    $output .= "/pinglast - mention sekian member @groupindogame terkini\n";
    $output .= "/pingww - mention sekian member @werewolfindogame terkini\n";
    $output .= "/users atau tambahkan (spasi)(keyword cari) - daftar user (berdasarkan last_active) \n";
    $output .= "/restartscore - mulai menghitung skor\n";
    $output .= "\n/restart - restartbot\n";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);			
}
elseif($chatid == $admingroup and substr($isi,0,strlen("/users")) == "/users"){
    $output = "users (by last_active):\n";
    $wherecommand = strtolower(trim(str_ireplace("/users","",$isi)));
    // $st = $app["pdo.DB_IDG"]->prepare("select user_id, name from idg_users 
    // where LOWER(name) like '%$wherecommand%'
    // or LOWER(user_name) like '%$wherecommand%'
    // order by last_active desc nulls last limit 250");
    // $st->execute();
    // while($row = $st->fetch(PDO::FETCH_ASSOC)){
    $sdata = sdata_find($db_alias."_USERS",[
        'name'=>[$wherecommand,"contains_insensitive"],
    ],100,[
        'user_name', 'user_id', 'name',
    ]);
    foreach($sdata as $row){        
        $output .= "/id_".$row['user_id']." ".substr($row['name'],0,6);
        if(strlen($row['name'])>6){
            $output .= "..\n";
        }
        else{
            $output .= "\n";
        }
    }
    $sdata = sdata_find($db_alias."_USERS",[
        'user_name'=>[$wherecommand,"contains_insensitive"],
    ],100,[
        'user_name', 'user_id', 'name',
    ]);
    foreach($sdata as $row){        
        $output .= "/id_".$row['user_id']." ".substr($row['name'],0,6);
        if(strlen($row['name'])>6){
            $output .= "..\n";
        }
        else{
            $output .= "\n";
        }
    }

    $output .= "\n<i>gunakan /users(spasi)(keyword) untuk mencari</i>\n";

    if(strlen($output)>3900){
        $output = substr($output,0,3900) . "... (kepanjangan)";
    }
    $data = array(
        'chat_id' => $admingroup,
        'text'=> utf8_encode($output),
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);			
}
elseif($chatid == $admingroup and $isi == "/kabur"){
    $output = "$nama melarikan diri dari @indogame dan meninggalkan tugasnya sebagai admin untuk sementara, hingga ia mengatakan sesuatu.";
    unset($activeusers[$dari]);
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);			
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/settings"){
    $output = "<b>(UNDER CONSTRUCTION)</b>\n\n";
    $output .= "welcome message: /welcome_setting\n\n";
    $output .= "forward yg reply ke bot: <b>ON</b>\n<b>/set*</b>\n\n";
    $output .= "forward yg mention @admin: <b>YG MENGANDUNG</b>\n<b>/set_di_depan_saja\n/set_off</b>\n\n";
    $output .= "msgcmd mute setting: <b>NO RULE</b>\n<b>/set*</b>\n\n";
    $output .= "warn setting: <b>NO RULE</b>\n<b>/set*</b>\n\n";
    $output .= "max msg length: <b>NO RULE</b>\n<b>/set*</b>\n\n";
    $output .= "max name length: <b>NO RULE</b>\n<b>/set*</b>\n\n";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/welcome_setting"){
    $data = array(
        'chat_id' => $chatid,
        'text'=> $welcome_message,
        'disable_web_page_preview'=>true,
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
    $output = "Balas pesan ini dengan pesan welcome (format seperti msgcmd), atau balas \"OFF\" untuk menonaktifkan.";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id'=>$message_id,
        'reply_markup' => $forcereply,
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer)
and strpos($message_data['reply_to_message']["text"],"alas pesan ini dengan pesan welcome (format seperti msgcmd), atau balas \"OFF\" untuk menonaktifkan") !== false){
    if($isi == "off"){
        $welcome_msgcmd = "";
        $welcome_message = "";
    }
    else{
        $welcome_msgcmd = str_replace("'","''",$text);
        $welcome_message = $text;
    }
    // $st = $app["pdo.DB_IDG"]->prepare("update idg_settings set
    // value='$welcome_msgcmd' where key='welcome_msgcmd'");
    // $st->execute();
    sdata_update_filtered($db_alias."_SETTINGS",[
        'key'=>'welcome_msgcmd',
    ],[
        'value'=>$welcome_msgcmd,
    ],1);
    $output = "OK";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/pinglast"){
    $output = "gunakan format:\n\n";
    $output .= "/pinglast(<b>jml</b>)(<i>spasi</i>)(<b>pesan</b>)\n\n";
    $output .= "jml = jumlah member terkini yang dimention\n";
    $output .= "pesan = teks yang ditampilkan sebelum mention (opsional / boleh kosong)\n\n";
    $output .= "contoh:\n<b>/pinglast10 kuy join</b>\n\n";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,9) == "/pinglast"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);

    $param = str_ireplace("/pinglast","",$isi);
    $jml = explode(" ",$param)[0];
    if($jml > 0){
        $pesan = str_ireplace("/pinglast$jml ","",$text);
        $pesan = str_ireplace("/pinglast$jml","",$pesan);
        $output = "$pesan ";
        // if(strpos(" ",$text) === false){
            // $pesan = "";
        // }
        // $st = $app["pdo.DB_IDG"]->prepare("select user_id, user_name, name from idg_users where group_active = '1' order by last_active desc nulls last limit $jml");
        // $st->execute();
        // while($row = $st->fetch(PDO::FETCH_ASSOC)){
        /*
            $sdata = sdata_find($db_alias."_USERS",['group_active'=>1],1,[
                'user_id', 'user_name', 'name',
            ]);
            foreach($sdata as $row){
                if($row['user_name'] == '@'){
                    $output .= "<a href='tg://user?id=".$row['user_id']."'>".$row['name']."</a> ";
                }else{
                    $output .= $row['user_name'] . " ";
                }
            }
            $data = array(
                'chat_id' => $maingroup,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true
            );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        */
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/pingww"){
    $output = "gunakan format:\n\n";
    $output .= "/pingww(<b>jml</b>)(<i>spasi</i>)(<b>pesan</b>)\n\n";
    $output .= "jml = jumlah member terkini yang dimention\n";
    $output .= "pesan = teks yang ditampilkan sebelum mention (opsional / boleh kosong)\n\n";
    $output .= "contoh:\n<b>/pingww10 kuy join</b>\n\n";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
// elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,strlen("/pingww")) == "/pingww"){
// underconstruction
//     $param = str_ireplace("/pingww","",$isi);
//     $jml = explode(" ",$param)[0];
//     if($jml > 0){
//         $pesan = str_ireplace("/pingww$jml ","",$text);
//         $pesan = str_ireplace("/pingww$jml","",$pesan);
//         // if(strpos(" ",$text) === false){
//             // $pesan = "";
//         // }
//         $st = $app["pdo.DB_IDG"]->prepare("select user_id, user_name, name from idg_users where group_active = '1' order by last_active desc nulls last limit $jml");
//         $st->execute();
//         $output = "$pesan ";
//         while($row = $st->fetch(PDO::FETCH_ASSOC)){
//             if($row['user_name'] == '@'){
//                 $output .= "<a href='tg://user?id=".$row['user_id']."'>".$row['name']."</a> ";
//             }else{
//                 $output .= $row['user_name'] . " ";
//             }
//         }
//         $data = array(
//             'chat_id' => '-1001149199985',
//             'text'=> $output,
//             'parse_mode'=>'HTML',
//             'disable_web_page_preview'=>true
//         );
//         $hasil = KirimPerintahX($token,'sendMessage',$data);
//     }
// }
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/kirim"){
    $output = "Balas di sini untuk mengirim pesan ke @groupindogame";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_markup' => $forcereply,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/kirimww"){
    $output = "Balas di sini untuk mengirim pesan ke @werewolfindogame";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_markup' => $forcereply,
        'reply_to_message_id'=>$message_id
    );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif($chatid == $admingroup and !empty($message_data['reply_to_message'])
and $message_data['reply_to_message']['from']['username'] == $botname
and substr($message_data['reply_to_message']['text'],0,strlen("Balas di sini untuk mengirim pesan ke ")) == "Balas di sini untuk mengirim pesan ke "){
    if($message_data['reply_to_message']['text'] == "Balas di sini untuk mengirim pesan ke @werewolfindogame"){
        $chatidnya = "-1001149199985";
    }
    else{
        $chatidnya = $maingroup;
    }			
    $textnya = $message_data['text'];
    $message_idnya = "";
    include('convert_message.php');
    $output = $textnya;
    if(substr($output,0,7) == "*photo "){
        $explode = explode("|",$output);
        $file_id = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'photo'=> $file_id,
            'caption' => str_replace("*photo ","",$explode[0]),
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendPhoto',$data);	
    }elseif(substr($output,0,9) == "*sticker "){
        $explode = explode("|",$output);
        $stickernya = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'sticker'=> $stickernya,
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendSticker',$data);	
    }elseif(substr($output,0,10) == "*document "){
        $explode = explode("|",$output);
        $file_id = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'document'=> $file_id,
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendDocument',$data);	
    }elseif(substr($output,0,7) == "*voice "){
        $explode = explode("|",$output);
        $file_id = $explode[1];
        $data = array(
            'chat_id' => $chatidnya,
            'voice'=> $file_id,
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendVoice',$data);	
    }elseif(substr($output,0,7) == "*location "){
        $explode = explode("|",$output);
        $explode2 = explode(",",$explode[1]);
        $data = array(
            'chat_id' => $chatidnya,
            'latitude'=> $explode2[0],
            'longitude'=> $explode2[1],
            'reply_to_message_id' => $message_idnya
        );	
        $hasil = KirimPerintahX($token,'sendLocation',$data);	
    }else{
        $data = array(
            'chat_id' => $chatidnya,
            'text'=> $output,
            'disable_web_page_preview'=>true,
            'parse_mode'=>'HTML',
            'reply_to_message_id' => $message_idnya
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) 
and substr($isi,0,strlen("/msgcmd")) == "/msgcmd"
and strpos($isi,"msgcmd_") === false ){
    $wherecommand = strtolower(trim(str_ireplace("/msgcmd","",$isi)));
    // $st = $app["pdo.DB_IDG"]->prepare("select id, command, active from idg_msg_cmd where LOWER(command) like '%$wherecommand%'");
    // $st->execute();
    if($wherecommand){
        $sdata = sdata_find($db_alias."_MSG_CMD", [
            'command'=>[$wherecommand, "contains_insensitive"],
        ], 200, [
            'id', 'command', 'active',
        ]);
    
        $output = "";
        foreach($sdata as $val){
            if($val['active'] == '0'){
                $output .= "$emoji_no ";
            }
            $output .= "<b>" . $val['command'] . "</b> /msgcmd_edit_" . $val['id'] . " \n\n";
        }
        /*
            $msgcmdlist = array();
            $msgcmdlist_pg = array();
            // while($row = $st->fetch(PDO::FETCH_ASSOC)){
            foreach($sdata as $row){
                $msgcmdlist[] = $row;
            };
            foreach ($msgcmdlist as $key => $val) {
                $col_command[$key]  = $val['command'];
                $col_id[$key] = $val['id'];
            }
            array_multisort($col_command, SORT_ASC, $col_id, SORT_ASC, $msgcmdlist);
            $page = 1;
            $count = 0;
            foreach($msgcmdlist as $val){
                $msgcmdlist_pg[$page][] = $val;
                $count++;
                if($count >= 20){
                    $page++;
                    $count = 0;
                }
            }
            // print_r($msgcmdlist_pg);
            foreach($msgcmdlist_pg as $key=>$val){
                $output .= "/msgcmd_p$key" . " - page $key\n( <b>" . substr($val[0]['command'],0,2) . "</b>.. $emoji_kanan <b>" .  substr($val[count($val)-1]['command'],0,2) ."</b>.. )\n\n";
            }
        */
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    else{
        $data = array(
            'chat_id' => $chatid,
            'text'=> "/msgcmd(spasi)(keyword cari) - cari msgcmd\n/msgcmd_new - buat baru",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
/*
    elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,9) == "/msgcmd_p" and strlen($isi)>9){
        $page = str_ireplace("/msgcmd_p","",$isi);
        if(array_key_exists($page,$msgcmdlist_pg)){
            $output = "";
            foreach($msgcmdlist_pg[$page] as $val){
                if($val['active'] == '0'){
                    $output .= "$emoji_no ";
                }
                $output .= "<b>" . $val['command'] . "</b> /msgcmd_edit_" . $val['id'] . " \n\n";
            }
            $output .= "\n\n/msgcmd - refresh";
        }
        else{
            $output = "ERROR page $page \n/msgcmd - refresh";
        }
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
*/
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/msgcmd_new"){
    $output = "Gunakan format:\n\n/msgcmd_new(spasi)(command)\n\natau reply sebuah pesan dengan format tsb untuk manjadikan pesan yg direply itu sebagai respon.\n\n<i>command boleh mengandung spasi dan tidak perlu diawali garis miring</i>";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id' => $message_id
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,12) == "/msgcmd_new " and strpos($isi, "'") === false){
    $commandnya = str_ireplace("@$botname","",str_ireplace("/msgcmd_new ","",$text));
    if(empty($message_data['reply_to_message'])){
        $output = "Balas pesan ini untuk membuat respon baru pada command: $commandnya";
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_markup' => $forcereply,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    else{
        $isinya = $message_data['reply_to_message']['text'];
        include('convert_message_reply.php');
        // $st = $app["pdo.DB_IDG"]->prepare("insert into idg_msg_cmd 
        // (created_by, created_time, command, case_sensitive, whole_word, message, reply_mode, active)
        // values
        // ('$dari', now(), '$commandnya', '0', '1', '$isinya', '0', '1')
        // returning id");
        // $st->execute();
        $sdata_insert = sdata_insert($db_alias."_MSG_CMD",[
            'created_by' => $dari,
            'created_time' => time(),
            'command' => $commandnya,
            'case_sensitive' => 0,
            'whole_word' => 1,
            'message' => $isinya,
            'reply_mode' => 0,
            'active' => 1,
        ]);
        // while($row = $st->fetch(PDO::FETCH_ASSOC)){
        if($sdata_insert){
            // $output = "command $commandnya berhasil dibuat.\n/msgcmd_edit_".$row['id']." - command settings";
            $output = "command $commandnya berhasil dibuat.\n/msgcmd_edit_".$sdata_insert." - command settings";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id' => $message_id
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and !empty($message_data['reply_to_message']['from']['is_bot'])
and strpos($message_data['reply_to_message']["text"],"alas pesan ini untuk membuat respon baru pada command: ") !== false
){
    $commandnya = str_ireplace("Balas pesan ini untuk membuat respon baru pada command: ","",$message_data['reply_to_message']["text"]);
    $textnya = str_replace("'","''",$text);
    include('convert_message.php');			
    if(!empty($commandnya) and !empty($textnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("insert into idg_msg_cmd 
        // (created_by, created_time, command, case_sensitive, whole_word, message, reply_mode, active)
        // values
        // ('$dari', now(), '$commandnya', '0', '1', '$textnya', '0', '1')
        // returning id");
        // $st->execute();
        // while($row = $st->fetch(PDO::FETCH_ASSOC)){
        $sdata_insert = sdata_insert($db_alias."_MSG_CMD",[
            'created_by' => $dari,
            'created_time' => time(),
            'command' => $commandnya,
            'case_sensitive' => 0,
            'whole_word' => 1,
            'message' => $textnya,
            'reply_mode' => 0,
            'active' => 1,
        ]);
        if($sdata_insert){
            $output = "command $commandnya berhasil dibuat.\n/msgcmd_edit_".$row['id']." - command settings";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id' => $message_id
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,13) == "/msgcmd_edit_"){
    $idnya = str_ireplace('/msgcmd_edit_','',$isi);
    // $st = $app["pdo.DB_IDG"]->prepare("select command, case_sensitive, whole_word, message, reply_mode, active from idg_msg_cmd where id='$idnya'");
    // $st->execute();
    $sdata = sdata_get_one($db_alias."_MSG_CMD",$idnya,[
        'command', 'case_sensitive', 'whole_word', 'message', 'reply_mode', 'active'
    ]);
    $output = "[EDIT]\n";
    // while($row = $st->fetch(PDO::FETCH_ASSOC)){
    foreach([$sdata] as $row){
        if((string)$row['command'] === "") continue;
        $output .= "Command: <b>" . $row['command'] . "</b>\n /msgcmd_EditCmd_$idnya \n\n";
        if($row['active'] == '1'){
            $output .= "Active: <b>ON</b>\n /msgcmd_Active_off_$idnya \n\n";
        }else{
            $output .= "Active: <b>OFF</b>\n /msgcmd_Active_on_$idnya \n\n";
        }
        if($row['case_sensitive'] == '1'){
            $output .= "Case sensitive: <b>ON</b>\n /msgcmd_CaSe_off_$idnya \n\n";
        }else{
            $output .= "Case sensitive: <b>OFF</b>\n /msgcmd_CaSe_on_$idnya \n\n";
        }
        if($row['whole_word'] == '1'){
            $output .= "Boleh mengandung: <b>OFF</b>\n /msgcmd_Containable_on_$idnya\n /msgcmd_Containable_first_$idnya\n\n";
        }elseif($row['whole_word'] == '2'){
            $output .= "Boleh mengandung: <b>DI DEPAN</b>\n /msgcmd_Containable_on_$idnya\n /msgcmd_Containable_off_$idnya\n\n";
        }else{
            $output .= "Boleh mengandung: <b>ON</b>\n /msgcmd_Containable_first_$idnya\n /msgcmd_Containable_off_$idnya\n\n";
        }
        if($row['reply_mode'] == '1'){
            $output .= "Reply mode: <b>TO SENDER</b>\n /msgcmd_ReplyMode_toObj_$idnya\n /msgcmd_ReplyMode_off_$idnya\n\n";
        }elseif($row['reply_mode'] == '2'){
            $output .= "Reply mode: <b>TO RECEIVER</b>\n /msgcmd_ReplyMode_toSbj_$idnya\n /msgcmd_ReplyMode_off_$idnya\n\n";
        }else{
            $output .= "Reply mode: <b>OFF</b>\n /msgcmd_ReplyMode_toSbj_$idnya\n /msgcmd_ReplyMode_toObj_$idnya\n\n";
        }
        $output .= "<b>Message:</b> /msgcmd_EditMsg_$idnya\n----------------\n";
        $messagenya = $row['message'];
        if(strlen($messagenya)>45){
            $messagenya = substr($messagenya,0,45) . "...";
        }
        $output .= $messagenya;
        $output .= "\n/msgcmd_lihat_$idnya\n----------------\n\nHapus: /msgcmd_hapus_$idnya";
    };
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id' => $message_id
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,16) == "/msgcmd_editcmd_" and strlen($isi)>16){
    $idnya = str_ireplace("/msgcmd_editcmd_","",$isi);
    $output = "Balas pesan ini untuk mengubah command dengan ID: $idnya";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_markup' => $forcereply,
        'reply_to_message_id' => $message_id
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and !empty($message_data['reply_to_message']['from']['is_bot'])
and strpos($message_data['reply_to_message']["text"],"alas pesan ini untuk mengubah command dengan ID:") !== false
){
    $idnya = str_ireplace("Balas pesan ini untuk mengubah command dengan ID: ","",$message_data['reply_to_message']["text"]);
    $textnya = str_replace("'","''",$text);
    if(!empty($idnya) and !empty($textnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set
        // command = '$textnya', edited_by = '$dari', edited_time = now() where id = '$idnya' returning command");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD", $idnya, [
            'command' => $textnya,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        
        // while($row = $st->fetch(PDO::FETCH_ASSOC)){
            // $output = "command telah berhasil diubah menjadi ".$row['command'].".\n/msgcmd_edit_$idnya";
            $output = "command telah berhasil diubah menjadi ".$textnya.".\n/msgcmd_edit_$idnya";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id' => $message_id
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        // }
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,16) == "/msgcmd_editmsg_" and strlen($isi)>16){
    $idnya = str_ireplace("@$botname","",str_ireplace("/msgcmd_editmsg_","",$text));
    if(empty($message_data['reply_to_message'])){
        $output = "Balas pesan ini untuk meng-update-nya (command ini bisa juga dijalankan dengan me-reply suatu pesan). id command: $idnya";
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_markup' => $forcereply,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    else{
        $isinya = $message_data['reply_to_message']['text'];
        include('convert_message_reply.php');
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set
        // message = '$isinya', edited_by = '$dari', edited_time = now() where id = '$idnya' returning command");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'message' => $isinya,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        // while($row = $st->fetch(PDO::FETCH_ASSOC)){
            // $output = "command ".$row['command']." berhasil diupdate.\n/msgcmd_edit_$idnya";
            $output = "berhasil diupdate.\n/msgcmd_edit_$idnya";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id' => $message_id
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        // }
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and !empty($message_data['reply_to_message']['from']['is_bot'])
and strpos($message_data['reply_to_message']["text"],"alas pesan ini untuk meng-update-nya (command ini bisa juga dijalankan dengan me-reply suatu pesan). id command:") !== false
){
    $idnya = str_ireplace("Balas pesan ini untuk meng-update-nya (command ini bisa juga dijalankan dengan me-reply suatu pesan). id command: ","",$message_data['reply_to_message']["text"]);
    // echo "$idnya\n";
    $textnya = str_replace("'","''",$text);
    include('convert_message.php');		
    echo "$textnya\n";
    if(!empty($idnya) and !empty($textnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set
        // message = '$textnya', edited_by = '$dari', edited_time = now() where id = '$idnya' returning command");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'message' => $textnya,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        // while($row = $st->fetch(PDO::FETCH_ASSOC)){
            // $output = "command ".$row['command']." berhasil diupdate.\n/msgcmd_edit_$idnya";
            $output = "berhasil diupdate.\n/msgcmd_edit_$idnya";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id' => $message_id
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        // }
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,19) == "/msgcmd_active_off_" and strlen($isi)>19){
    $idnya = str_replace('/msgcmd_active_off_','',$isi);
    if(!empty($idnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set active = '0', edited_by = '$dari', edited_time = now() where id = '$idnya'");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'active' => 0,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "OK /msgcmd_edit_$idnya",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
        );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,18) == "/msgcmd_active_on_" and strlen($isi)>18){
    $idnya = str_replace('/msgcmd_active_on_','',$isi);
    if(!empty($idnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set active = '1', edited_by = '$dari', edited_time = now() where id = '$idnya'");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'active' => 1,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "OK /msgcmd_edit_$idnya",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,17) == "/msgcmd_case_off_" and strlen($isi)>17){
    $idnya = str_replace('/msgcmd_case_off_','',$isi);
    if(!empty($idnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set case_sensitive = '0', edited_by = '$dari', edited_time = now() where id = '$idnya'");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'case_sensitive' => 0,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "OK /msgcmd_edit_$idnya",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,16) == "/msgcmd_case_on_" and strlen($isi)>16){
    $idnya = str_replace('/msgcmd_case_on_','',$isi);
    if(!empty($idnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set case_sensitive = '1', edited_by = '$dari', edited_time = now() where id = '$idnya'");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'case_sensitive' => 1,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "OK /msgcmd_edit_$idnya",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,24) == "/msgcmd_containable_off_" and strlen($isi)>24){
    $idnya = str_replace('/msgcmd_containable_off_','',$isi);
    if(!empty($idnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set whole_word = '1', edited_by = '$dari', edited_time = now() where id = '$idnya'");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'whole_word' => 1,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "OK /msgcmd_edit_$idnya",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,26) == "/msgcmd_containable_first_" and strlen($isi)>26){
    $idnya = str_replace('/msgcmd_containable_first_','',$isi);
    if(!empty($idnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set whole_word = '2', edited_by = '$dari', edited_time = now() where id = '$idnya'");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'whole_word' => 2,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "OK /msgcmd_edit_$idnya",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,23) == "/msgcmd_containable_on_" and strlen($isi)>23){
    $idnya = str_replace('/msgcmd_containable_on_','',$isi);
    if(!empty($idnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set whole_word = '0', edited_by = '$dari', edited_time = now() where id = '$idnya'");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'whole_word' => 0,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "OK /msgcmd_edit_$idnya",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,22) == "/msgcmd_replymode_off_" and strlen($isi)>22){
    $idnya = str_replace('/msgcmd_replymode_off_','',$isi);
    if(!empty($idnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set reply_mode = '0', edited_by = '$dari', edited_time = now() where id = '$idnya'");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'reply_mode' => 0,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "OK /msgcmd_edit_$idnya",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,24) == "/msgcmd_replymode_tosbj_" and strlen($isi)>24){
    $idnya = str_replace('/msgcmd_replymode_tosbj_','',$isi);
    if(!empty($idnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set reply_mode = '1', edited_by = '$dari', edited_time = now() where id = '$idnya'");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'reply_mode' => 1,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "OK /msgcmd_edit_$idnya",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,24) == "/msgcmd_replymode_toobj_" and strlen($isi)>24){
    $idnya = str_replace('/msgcmd_replymode_toobj_','',$isi);
    if(!empty($idnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set reply_mode = '2', edited_by = '$dari', edited_time = now() where id = '$idnya'");
        // $st->execute();
        sdata_update($db_alias."_MSG_CMD",$idnya,[
            'reply_mode' => 2,
            'edited_by' => $dari,
            'edited_time' => time(),
        ]);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "OK /msgcmd_edit_$idnya",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,14) == "/msgcmd_lihat_" and strlen($isi)>14){
    $idnya = str_replace('/msgcmd_lihat_','',$isi);
    if(!empty($idnya)){
        $sdata = sdata_get_one($db_alias."_MSG_CMD",$idnya,['message']);
        // $st = $app["pdo.DB_IDG"]->prepare("select message from idg_msg_cmd where id = '$idnya'");
        // $st->execute();
        // while($row = $st->fetch(PDO::FETCH_ASSOC)){
        $output = $sdata['message'];
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
        if(substr($output,0,7) == "*photo "){
            $explode = explode("|",$output);
            $file_id = $explode[1];
            $data = array(
                'chat_id' => $chatid,
                'photo'=> $file_id,
                'caption' => str_replace("*photo ","",$explode[0]),
                'reply_to_message_id' => $message_id
            );	
            $hasil = KirimPerintahX($token,'sendPhoto',$data);	
        }elseif(substr($output,0,9) == "*sticker "){
            $explode = explode("|",$output);
            $stickernya = $explode[1];
            $data = array(
                'chat_id' => $chatid,
                'sticker'=> $stickernya,
                'reply_to_message_id' => $message_id
            );	
            $hasil = KirimPerintahX($token,'sendSticker',$data);	
        }elseif(substr($output,0,10) == "*document "){
            $explode = explode("|",$output);
            $file_id = $explode[1];
            $data = array(
                'chat_id' => $chatid,
                'document'=> $file_id,
                'reply_to_message_id' => $message_id
            );	
            $hasil = KirimPerintahX($token,'sendDocument',$data);	
        }elseif(substr($output,0,7) == "*voice "){
            $explode = explode("|",$output);
            $file_id = $explode[1];
            $data = array(
                'chat_id' => $chatid,
                'voice'=> $file_id,
                'reply_to_message_id' => $message_id
            );	
            $hasil = KirimPerintahX($token,'sendVoice',$data);	
        }elseif(substr($output,0,7) == "*location "){
            $explode = explode("|",$output);
            $explode2 = explode(",",$explode[1]);
            $data = array(
                'chat_id' => $chatid,
                'latitude'=> $explode2[0],
                'longitude'=> $explode2[1],
                'reply_to_message_id' => $message_id
            );	
            $hasil = KirimPerintahX($token,'sendLocation',$data);	
        }
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,strlen("/msgcmd_hapus_yakin_")) == "/msgcmd_hapus_yakin_" and strlen($isi)>14){
    $idnya = str_replace('/msgcmd_hapus_yakin_','',$isi);
    if(!empty($idnya)){
        // $st = $app["pdo.DB_IDG"]->prepare("delete from idg_msg_cmd where id = '$idnya'");
        // $st->execute();
        sdata_delete($db_alias."_MSG_CMD",$idnya);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "OK",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,14) == "/msgcmd_hapus_" and strlen($isi)>14){
    $idnya = str_replace('/msgcmd_hapus_','',$isi);
    if(!empty($idnya)){
        $data = array(
            'chat_id' => $chatid,
            'text'=> "Yakin? /msgcmd_hapus_yakin_$idnya",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);        
    }

}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/ekstra"){
    $data = array(
        'chat_id' => $chatid,
        'text'=> "Gunakan format:\n\n/ekstra(spasi)#(command)(spasi)(isi)\n\natau reply sebuah pesan dengan\n/ekstra(spasi)#(command)",
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id' => $message_id
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and substr($isi,0,9) == "/ekstra #" and strlen($isi)>9){
    $explode = explode(" ",str_replace('/ekstra #','',$isi));
    $commandnya = $explode[0];
    if(!empty($explode[1])){
        $isinya = str_ireplace("/ekstra #$commandnya ",'',$text);
    }
    elseif(!empty($message_data['reply_to_message'])){
        $isinya = $message_data['reply_to_message']['text'];
        include('convert_message_reply.php');
    }
    if(!empty($isinya)){
        // $st = $app["pdo.DB_IDG"]->prepare("select id from idg_msg_cmd where command = '#$commandnya'");
        // $st->execute();
        // $idnya="";
        // while($row = $st->fetch(PDO::FETCH_ASSOC)){
        //     $idnya = $row['id'];
        // }
        $idnya = sdata_find_one($db_alias."_MSG_CMD",['command'=>"#$commandnya"])['id'] ?? "";
        if(empty($idnya)){
            // $st = $app["pdo.DB_IDG"]->prepare("insert into idg_msg_cmd 
            // (created_by, created_time, command, case_sensitive, whole_word, message, reply_mode, active)
            // values
            // ('$dari', now(), '#$commandnya', '1', '1', '', '1', '1')");
            // $st->execute();		
            sdata_insert($db_alias."_MSG_CMD",[
                'created_by'=>$dari, 
                'created_time'=>time(), 
                'command'=>"#$commandnya",
                'case_sensitive'=>1,
                'whole_word'=>1,
                'message'=>$isinya, 
                'reply_mode'=>1, 
                'active'=>1,
            ]);
            $output = "command #$commandnya berhasil dibuat";
        }
        else{
            // $st = $app["pdo.DB_IDG"]->prepare("update idg_msg_cmd set
            // edited_by = '$dari',
            // edited_time = now(),
            // case_sensitive = '1',
            // whole_word = '1',
            // message = '$isinya',
            // reply_mode = '1',
            // active = '1'
            // where command = '#$commandnya'
            // ");
            // $st->execute();	
            sdata_update($db_alias."_MSG_CMD",$idnya,[
                'edited_by'=>$dari, 
                'edited_time'=>time(), 
                'case_sensitive'=>1,
                'whole_word'=>1,
                'message'=>$isinya, 
                'reply_mode'=>1, 
                'active'=>1,
            ]);
            $output = "command #$commandnya berhasil <b>diupdate</b>";
        }
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/restart"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);
    // $output = "OK.\n\n<i>Warning: Dilarang menjalankan command ini lagi dalam waktu dekat</i>";
    // $output = "COMMAND ini sudah tidak berfungsi";
    // $data = array(
    //     'chat_id' => $chatid,
    //     'text'=> $output,
    //     'parse_mode'=>'HTML',
    //     'disable_web_page_preview'=>true,
    //     'reply_to_message_id'=>$message_id
    // );
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/restartscore"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);
    // $score = array();
    // $gameround = array();
    // $output = "Pengitung skor telah direset. Silakan mulai hitung skor dengan memforwardnya ke https://t.me/joinchat/DYgdIA-yzCAgr_xkyBEsAQ\n\n";
    // $output .= "/info_sixnimmt\n";
    // $output .= "/info_thirtyone\n";
    // $output .= "/info_komunikata\n";
    // $output .= "/info_kataberbintang\n";
    // $output .= "/info_kuismayofam\n";
    // $output .= "/info_rnt\n";
    // $output .= "/info_werewolf\n";
    // $output .= "/info_CriminalDance\n";
    // $output .= "/info_jokebig2bot\n"; 
    // $data = array(
    //     'chat_id' => $chatid,
    //     'text'=> $output,
    //     'parse_mode'=>'HTML',
    //     'disable_web_page_preview'=>true,
    //     'reply_to_message_id'=>$message_id
    // );
    // $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/info_jokebig2bot"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);
//     $output = "Forward yang modelnya kayak gini:
// Player1 Won!

// Player1 - xx [+xx]
// Player2 - xx [-xx]
// Player3 - xx [-xx]
// Player4 - xx [-xx]";
//     $data = array(
//         'chat_id' => $chatid,
//         'text'=> $output,
//         'parse_mode'=>'HTML',
//         'disable_web_page_preview'=>true,
//         'reply_to_message_id'=>$message_id
//     );
//     $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/info_sixnimmt"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);
    // $output = "Forward dari hasil akhir perolehan bulls sampai <b>won the game</b>";
    // $data = array(
    //     'chat_id' => $chatid,
    //     'text'=> $output,
    //     'parse_mode'=>'HTML',
    //     'disable_web_page_preview'=>true,
    //     'reply_to_message_id'=>$message_id
    // );
    // $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/info_thirtyone"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);
    // $output = "Forward semua yang mengandung <b>Daftar Pemain</b> secara berurutan (dari awal sampai akhir). @$botname akan otomatis mendeteksi game baru saat tidak ada yang mati dalam daftar pemain yg diforward.";
    // $data = array(
    //     'chat_id' => $chatid,
    //     'text'=> $output,
    //     'parse_mode'=>'HTML',
    //     'disable_web_page_preview'=>true,
    //     'reply_to_message_id'=>$message_id
    // );
    // $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/info_werewolf"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);
    // $output = "Forward setiap daftar pemain di akhir permainan (setelah ada yang menang). (Bot tidak menghitung FB/FL)";
    // $data = array(
    //     'chat_id' => $chatid,
    //     'text'=> $output,
    //     'parse_mode'=>'HTML',
    //     'disable_web_page_preview'=>true,
    //     'reply_to_message_id'=>$message_id
    // );
    // $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/info_komunikata"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);
    // $output = "Forward daftar skor pemain";
    // $data = array(
    //     'chat_id' => $chatid,
    //     'text'=> $output,
    //     'parse_mode'=>'HTML',
    //     'disable_web_page_preview'=>true,
    //     'reply_to_message_id'=>$message_id
    // );
    // $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/info_kataberbintang"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);
    // $output = "Forward daftar skor pemain";
    // $data = array(
    //     'chat_id' => $chatid,
    //     'text'=> $output,
    //     'parse_mode'=>'HTML',
    //     'disable_web_page_preview'=>true,
    //     'reply_to_message_id'=>$message_id
    // );
    // $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/info_kuismayofam"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);
    // $output = "Forward daftar skor pemain";
    // $data = array(
    //     'chat_id' => $chatid,
    //     'text'=> $output,
    //     'parse_mode'=>'HTML',
    //     'disable_web_page_preview'=>true,
    //     'reply_to_message_id'=>$message_id
    // );
    // $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/info_rnt"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);
    // $output = 'Forward pesan yang mengandung "PEMENANGNYA ADALAH SPY / TIM MERAH / BIRU"';
    // $data = array(
    //     'chat_id' => $chatid,
    //     'text'=> $output,
    //     'parse_mode'=>'HTML',
    //     'disable_web_page_preview'=>true,
    //     'reply_to_message_id'=>$message_id
    // );
    // $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or $dari == $developer) and $isi == "/info_criminaldance"){
    KirimPerintahX($token,'sendMessage',[
        'chat_id' => $chatid,
        'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
        'reply_to_message_id'=>$message_id
    ]);
    // $output = 'Forward pesan yang mengandung "<b>Won</b> atau <b>Lost</b>" (sebelum pesan "Game ended!")';
    // $data = array(
    //     'chat_id' => $chatid,
    //     'text'=> $output,
    //     'parse_mode'=>'HTML',
    //     'disable_web_page_preview'=>true,
    //     'reply_to_message_id'=>$message_id
    // );
    // $hasil = KirimPerintahX($token,'sendMessage',$data);
}
//scorecounter group:
elseif($chatid == $scoregroup){
    /*
    if(isset($message_data['forward_from']) and $message_data['forward_from']['username'] == "sixnimmtbot"){
        $output = "$emoji_check";
        if(!isset($gameround['sixnimmtbot'])){
            $gameround['sixnimmtbot'] = 1;
            $scorebase_sapi = 0;
            $output = "game baru dimulai.";
        }
        $gameroundnya = $gameround['sixnimmtbot'];
        if(strpos($isi,"won the") !== false){
            $scorebase_sapi = 0;
            //menang
            $playernya = explode(" won",$text)[0];
            if(strpos($playernya,", ")!== false){
                $explode = explode(", ",$playernya);
                foreach($explode as $val){
                    $score['sixnimmtbot'][$gameroundnya][$val]['won'] = 1;
                }
            }
            else{
                $score['sixnimmtbot'][$gameroundnya][$playernya]['won'] = 1;
            }
            $gameround['sixnimmtbot'] ++;
            $output = "game $gameroundnya selesai. Selanjutnya game " . ($gameroundnya+1);
        }else{
            // $players = array();
            // $bulls_card = array();
            // $bulls_afk = array();
            // $bulls_total = array();
            // $score = array();
            // $array = array();
            $explode = explode("\n",$text);
            foreach($explode as $key=>$val){
                if(strpos($val,"▇") !== false){
                    $scorebase_sapi++;
                }elseif(strpos($val,"has got") !== false){
                    $explode2 = explode(" has got ",$val);
                    $namanya = $explode2[0];
                    $got = $explode2[1];
                    
                    if(strpos($got,"AFK")!==false){
                        $bulls_totalnya = 0;
                        $explode3 = explode(" AFK Bulls and ",$got);
                        $bulls_afk = $explode3[0];
                        $bulls_totalnya += $explode3[0];
                        $explode3 = explode(" Bulls",$explode3[1]);
                        $bulls_card = $explode3[0];
                        $bulls_totalnya += $explode3[0];
                        $bulls_total = $bulls_totalnya;
                    }
                    else{
                        $explode3 = explode(" Bulls",$got);
                        $bulls_card = $explode3[0];
                        $bulls_afk = 0;
                        $bulls_total = $explode3[0];
                    }
                    
                    $score['sixnimmtbot'][$gameroundnya][$namanya]['played'] = 1;
                    $score['sixnimmtbot'][$gameroundnya][$namanya]['rankscore'] = $scorebase_sapi;
                    // $score['sixnimmtbot'][$gameroundnya][$namanya]['bulls_card'] = $bulls_card;
                    $score['sixnimmtbot'][$gameroundnya][$namanya]['afkbulls'] = $bulls_afk;
                    $score['sixnimmtbot'][$gameroundnya][$namanya]['bulls'] = $bulls_total;
                    if(empty($score['sixnimmtbot'][$gameroundnya][$namanya]['won'])){
                        $score['sixnimmtbot'][$gameroundnya][$namanya]['won'] = 0;
                    }
                }
            }
            // $text = implode("\n",$explode);
            // foreach($array2 as $key=>$val){
                // if($key % 2 == 0){
                    // $players[] = $val;
                // }
                // elseif($key % 2 == 1){
                    // if(strpos($val,"AFK")!==false){
                        // $bulls_totalnya = 0;
                        // $explode = explode(" AFK Bulls and ",$val);
                        // $bulls_afk[] = $explode[0];
                        // $bulls_totalnya += $explode[0];
                        // $explode = explode(" Bulls",$explode[1]);
                        // $bulls_card[] = $explode[0];
                        // $bulls_totalnya += $explode[0];
                        // $bulls_total[] = $bulls_totalnya;
                    // }
                    // else{
                        // $explode = explode(" Bulls",$val);
                        // $bulls_card[] = $explode[0];
                        // $bulls_afk[] = 0;
                        // $bulls_total[] = $explode[0];
                    // }
                // }
            // }
            // foreach($players as $key=>$val){
                // $score['sixnimmtbot'][$gameroundnya][$val]['bulls_card'] = $bulls_card[$key];
                // $score['sixnimmtbot'][$gameroundnya][$val]['bulls_afk'] = $bulls_afk[$key];
                // $score['sixnimmtbot'][$gameroundnya][$val]['bulls_total'] = $bulls_total[$key];
                // if(empty($score['sixnimmtbot'][$gameroundnya][$val]['won'])){
                    // $score['sixnimmtbot'][$gameroundnya][$val]['won'] = 0;
                // }
            // }
        }
        $output .= "\n/scoresixnimmtbot";
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id'=>$message_id
        );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    elseif(isset($message_data['forward_from']) and $message_data['forward_from']['username'] == "blackwerewolfbot"){
        if(empty($gameround['blackwerewolfbot'])){
            $gameround['blackwerewolfbot'] = 1;
            $output = "game baru dimulai.";
        }else{
            $output = "$emoji_check round " . $gameround['blackwerewolfbot'];
        }
        $gameroundnya = $gameround['blackwerewolfbot'];
        $gameround['blackwerewolfbot'] ++;
        $explode = explode("\n",$text);
        foreach($explode as $key=>$val){
            if(strpos($val,"Kalah") !== false or strpos($val,"Menang") !== false){
                $explode2 = explode(": ",$val);
                $namanya = $explode2[0];
                if(strpos($explode2[1],"Menang")!== false){
                    $menang = 1;
                }elseif(strpos($explode2[1],"Kalah")!== false){
                    $menang = 0;
                }else{
                    $menang = "ERROR";
                }
                if(strpos($explode2[1],"Masih Hidup")!== false){
                    $hidup = 1;
                }elseif(strpos($explode2[1],"Tewas")!== false){
                    $hidup = 0;
                }else{
                    $hidup = "ERROR";
                }
                $score['blackwerewolfbot'][$gameroundnya][$namanya]['hidup'] = $hidup;
                $score['blackwerewolfbot'][$gameroundnya][$namanya]['menang'] = $menang;
                if(empty($hidup) and empty($menang)){
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['HidupMenang'] = 0;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['HidupKalah'] = 0;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['MatiMenang'] = 0;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['MatiKalah'] = 1;
                }elseif(empty($hidup)){
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['HidupMenang'] = 0;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['HidupKalah'] = 0;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['MatiMenang'] = 1;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['MatiKalah'] = 0;
                }elseif(empty($menang)){
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['HidupMenang'] = 0;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['HidupKalah'] = 1;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['MatiMenang'] = 0;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['MatiKalah'] = 0;
                }else{
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['HidupMenang'] = 1;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['HidupKalah'] = 0;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['MatiMenang'] = 0;
                    $score['blackwerewolfbot'][$gameroundnya][$namanya]['MatiKalah'] = 0;
                }
                
            }
        }
        $output .= "\n/scoreblackwerewolfbot";
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id'=>$message_id
        );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    elseif(isset($message_data['forward_from']) and $message_data['forward_from']['username'] == "werewolfbot"){
        if(empty($gameround['werewolfbot'])){
            $gameround['werewolfbot'] = 1;
            $output = "game baru dimulai.";
        }else{
            $output = "$emoji_check round " . $gameround['werewolfbot'];
        }
        $gameroundnya = $gameround['werewolfbot'];
        $gameround['werewolfbot'] ++;
        $explode = explode("\n",$text);
        foreach($explode as $key=>$val){
            if(strpos($val,"Kalah") !== false or strpos($val,"Menang") !== false){
                $explode2 = explode(": ",$val);
                $namanya = $explode2[0];
                if(strpos($explode2[1],"Menang")!== false){
                    $menang = 1;
                }elseif(strpos($explode2[1],"Kalah")!== false){
                    $menang = 0;
                }else{
                    $menang = "ERROR";
                }
                if(strpos($explode2[1],"Masih Hidup")!== false){
                    $hidup = 1;
                }elseif(strpos($explode2[1],"Tewas")!== false){
                    $hidup = 0;
                }else{
                    $hidup = "ERROR";
                }
                $score['werewolfbot'][$gameroundnya][$namanya]['hidup'] = $hidup;
                $score['werewolfbot'][$gameroundnya][$namanya]['menang'] = $menang;
                if(empty($hidup) and empty($menang)){
                    $score['werewolfbot'][$gameroundnya][$namanya]['HidupMenang'] = 0;
                    $score['werewolfbot'][$gameroundnya][$namanya]['HidupKalah'] = 0;
                    $score['werewolfbot'][$gameroundnya][$namanya]['MatiMenang'] = 0;
                    $score['werewolfbot'][$gameroundnya][$namanya]['MatiKalah'] = 1;
                }elseif(empty($hidup)){
                    $score['werewolfbot'][$gameroundnya][$namanya]['HidupMenang'] = 0;
                    $score['werewolfbot'][$gameroundnya][$namanya]['HidupKalah'] = 0;
                    $score['werewolfbot'][$gameroundnya][$namanya]['MatiMenang'] = 1;
                    $score['werewolfbot'][$gameroundnya][$namanya]['MatiKalah'] = 0;
                }elseif(empty($menang)){
                    $score['werewolfbot'][$gameroundnya][$namanya]['HidupMenang'] = 0;
                    $score['werewolfbot'][$gameroundnya][$namanya]['HidupKalah'] = 1;
                    $score['werewolfbot'][$gameroundnya][$namanya]['MatiMenang'] = 0;
                    $score['werewolfbot'][$gameroundnya][$namanya]['MatiKalah'] = 0;
                }else{
                    $score['werewolfbot'][$gameroundnya][$namanya]['HidupMenang'] = 1;
                    $score['werewolfbot'][$gameroundnya][$namanya]['HidupKalah'] = 0;
                    $score['werewolfbot'][$gameroundnya][$namanya]['MatiMenang'] = 0;
                    $score['werewolfbot'][$gameroundnya][$namanya]['MatiKalah'] = 0;
                }
                
            }
        }
        $output .= "\n/scorewerewolfbot";
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id'=>$message_id
        );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    elseif(isset($message_data['forward_from']) and $message_data['forward_from']['username'] == "ThirtyOneBot"){
        $output = "$emoji_check";
        if(empty($gameround['thirtyonebot'])){
            $gameround['thirtyonebot'] = 1;
            $output = "game baru dimulai.";
        }
        $gameroundnya = $gameround['thirtyonebot'];
        if(strpos($isi,$emoji_skull) === false){
            if(empty($score['thirtyonebot'][$gameroundnya])){
                $output = "Tidak ada yg mati. (game baru)";
                $scorebase = 0;
            }else{
                $ada_yg_mati = false;
                foreach($score['thirtyonebot'][$gameroundnya] as $key=>$val){
                    if(!empty($val['dead'])){
                        $ada_yg_mati = true;
                        break;
                    }
                }
                if($ada_yg_mati){
                    $output = "Tidak ada yg mati. Artinya game $gameroundnya selesai. Selanjutnya game " . ($gameroundnya+1);
                    $gameround['thirtyonebot'] ++;
                    $gameroundnya = $gameround['thirtyonebot'];	
                    $scorebase = 0;							
                }else{
                    $output = "Tidak ada yg mati.";														
                }

            }
        }
        $explode = explode("\n",$text);
        $playercount = 0;
        $yg_mati = array();
        $hidup = array();
        foreach($explode as $val){
            if(strpos($val,$emoji_heart) !== false or strpos($val,$emoji_skull) !== false){
                $playercount++;
                $explode2 = explode(" ",$val);
                array_pop($explode2);
                $playernya = implode(" ",$explode2);
                if(!isset($score['thirtyonebot'][$gameroundnya][$playernya]['dead'])){
                    $score['thirtyonebot'][$gameroundnya][$playernya]['dead'] = 0;
                    $score['thirtyonebot'][$gameroundnya][$playernya]['score'] = 0;
                }
                if(strpos($val,$emoji_skull) !== false){
                    if(empty($score['thirtyonebot'][$gameroundnya][$playernya]['dead'])){
                        $score['thirtyonebot'][$gameroundnya][$playernya]['dead'] = 1;
                        $yg_mati[] = $playernya;
                    }
                }
                if(strpos($val,$emoji_heart) !== false){
                    $hidup[] = $playernya;
                }
            }
        };
        
        if(!empty($yg_mati)){
            $scorebase ++;
            foreach($yg_mati as $val){
                $score['thirtyonebot'][$gameroundnya][$val]['score'] = $scorebase;
            }
            foreach($hidup as $val){
                $score['thirtyonebot'][$gameroundnya][$val]['score'] = $scorebase+1;
            }
        }

        $output .= "\n/scorethirtyonebot";
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id'=>$message_id
        );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    elseif(isset($message_data['forward_from']) and strtolower($message_data['forward_from']['username']) == "komunikatabot"){
        $output = "$emoji_check";
        if(!isset($gameround['komunikatabot'])){
            $gameround['komunikatabot'] = 1;
            $scorebase_kk = 0;
            $output = "game baru dimulai.";
        }
        $gameroundnya = $gameround['komunikatabot'];
        $explode = explode("\n",$text);
        foreach($explode as $key=>$val){
            if(strpos($val,": ") === false and strpos($val,". ") === false){
                unset($explode[$key]);
            }
        }
        if(empty($explode)){
            $output = "ERROR";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id'=>$message_id
            );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
        else{
            $scorebase_kk = count($explode);
            foreach($explode as $val){
                $explode2 = explode(". ",$val);
                $val2 = str_replace($explode2[0].". ","",$val);
                $explode3 = explode(": ",$val2);
                $skor = $explode3[count($explode3)-1];
                $namanya = str_replace(": ".$skor,"",$val2);
                $rankskor = $scorebase_kk;
                $score['komunikatabot'][$gameroundnya][$namanya]['score'] = $skor;
                $score['komunikatabot'][$gameroundnya][$namanya]['rankscore'] = $rankskor;
                $scorebase_kk--;
            }					
            $gameround['komunikatabot'] ++;
            $output .= "\n/scorekomunikatabot";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id'=>$message_id
            );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
        
    }
    elseif(isset($message_data['forward_from']) and strtolower($message_data['forward_from']['username']) == "kataberbintangbot"){
        $output = "$emoji_check";
        if(!isset($gameround['kataberbintangbot'])){
            $gameround['kataberbintangbot'] = 1;
            $scorebase_kk = 0;
            $output = "game baru dimulai.";
        }
        $gameroundnya = $gameround['kataberbintangbot'];
        $explode = explode("\n",$text);
        foreach($explode as $key=>$val){
            if(strpos($val,"Berikut hasil") === false){
                unset($explode[$key]);
            }
            else{
                break;
            }
        }
        foreach($explode as $key=>$val){
            if(strpos($val,": ") === false 
                and strpos($val,". ") === false 
                and !is_numeric(substr($val,-1))
            ){
                unset($explode[$key]);
            }
        }
        if(empty($explode)){
            $output = "ERROR";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id'=>$message_id
            );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
        else{
            $scorebase_kk = count($explode);
            foreach($explode as $val){
                $explode2 = explode(". ",$val);
                $val2 = str_replace($explode2[0].". ","",$val);
                $explode3 = explode(": ",$val2);
                $skor = $explode3[count($explode3)-1];
                $namanya = str_replace(": ".$skor,"",$val2);
                $rankskor = $scorebase_kk;
                $score['kataberbintangbot'][$gameroundnya][$namanya]['score'] = $skor;
                $score['kataberbintangbot'][$gameroundnya][$namanya]['rankscore'] = $rankskor;
                $scorebase_kk--;
            }					
            $gameround['kataberbintangbot'] ++;
            $output .= "\n/scorekataberbintangbot";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id'=>$message_id
            );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
        
    }
    elseif(isset($message_data['forward_from']) and strtolower($message_data['forward_from']['username']) == "kuismayofambot"){
        $output = "$emoji_check";
        if(!isset($gameround['kuismayofambot'])){
            $gameround['kuismayofambot'] = 1;
            $scorebase_kk = 0;
            $output = "game baru dimulai.";
        }
        $gameroundnya = $gameround['kuismayofambot'];
        $explode = explode("\n",$text);
        foreach($explode as $key=>$val){
            if(strpos($val,": ") === false and strpos($val,". ") === false and !is_numeric(substr($val,-1)) ){
                unset($explode[$key]);
            }
        }
        if(empty($explode)){
            $output = "ERROR";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id'=>$message_id
            );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
        else{
            $scorebase_kk = count($explode);
            foreach($explode as $val){
                $explode2 = explode(". ",$val);
                $val2 = str_replace($explode2[0].". ","",$val);
                $explode3 = explode(": ",$val2);
                $skor = $explode3[count($explode3)-1];
                $namanya = str_replace(": ".$skor,"",$val2);
                $rankskor = $scorebase_kk;
                $score['kuismayofambot'][$gameroundnya][$namanya]['score'] = $skor;
                $score['kuismayofambot'][$gameroundnya][$namanya]['rankscore'] = $rankskor;
                $scorebase_kk--;
            }					
            $gameround['kuismayofambot'] ++;
            $output .= "\n/scorekuismayofambot";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id'=>$message_id
            );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
        
    }
    elseif(isset($message_data['forward_from']) and strtolower($message_data['forward_from']['username']) == "rnt_game_bot"){
        $output = "$emoji_check";
        if(!isset($gameround['rnt_game_bot'])){
            $gameround['rnt_game_bot'] = 1;
            $output = "game baru dimulai.";
        }
        $gameroundnya = $gameround['rnt_game_bot'];
        $explode = explode("\n",$text);
        foreach($explode as $key=>$val){
            if(strpos($val,"(") === false and strpos(strtolower($val),"kalah") === false){
                unset($explode[$key]);
            }
        }
        if(empty($explode)){
            $output = "ERROR";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id'=>$message_id
            );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
        else{
            $rnt_win = 1;
            foreach($explode as $val){
                if(strpos(strtolower($val),"kalah") !== false){
                    $rnt_win = 0;
                }
                else{
                    $explode2 = explode(" (",$val);
                    $namanya = $explode2[0];
                    $win_as_team_survive = 0;
                    $win_as_team_out = 0;
                    $win_as_spy = 0;
                    $win_as_traitor = 0;
                    $lose = 0;
                    if($rnt_win == 1){
                        if(strpos($explode2[1],"PENGKHIANAT") !== false){
                            $win_as_traitor = 1;
                        }elseif(strpos($explode2[1],"SPY") !== false){
                            $win_as_spy = 1;
                        }elseif(strpos($explode2[1],$emoji_flexed_biceps) !== false){
                            $win_as_team_survive = 1;
                        }else{
                            $win_as_team_out= 1;
                        }
                    }else{
                        $lose = 1;
                    }
                    $score['rnt_game_bot'][$gameroundnya][$namanya]['teamWinSurv'] = $win_as_team_survive;
                    $score['rnt_game_bot'][$gameroundnya][$namanya]['teamWinOut'] = $win_as_team_out;
                    $score['rnt_game_bot'][$gameroundnya][$namanya]['spyWin'] = $win_as_spy;
                    $score['rnt_game_bot'][$gameroundnya][$namanya]['traitor'] = $win_as_traitor;
                    $score['rnt_game_bot'][$gameroundnya][$namanya]['lose'] = $lose;
                    $scorebase_kk--;	
                }
            }					
            $gameround['rnt_game_bot'] ++;
            $output .= "\n/scorernt_game_bot";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id'=>$message_id
            );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
        
    }
    elseif(isset($message_data['forward_from']) and $message_data['forward_from']['username'] == "CriminalDanceBot"){
         sample format:
        // Evan @bubble02 - Won
        // Yasir (Accomplice) (Accomplice :pouting_cat:) - Won
        // Dono (Accomplice) (Accomplice :pouting_cat:) - Won
        // Lebah Cantieq - Lost
        // Wisemkyo - Lost
        // :dragon_face: - Lost

        if(empty($gameround['CriminalDanceBot'])){
            $gameround['CriminalDanceBot'] = 1;
            $output = "game baru dimulai.";
        }else{
            $output = "$emoji_check round " . $gameround['CriminalDanceBot'];
        }
        $gameroundnya = $gameround['CriminalDanceBot'];
        $gameround['CriminalDanceBot'] ++;
        $explode = explode("\n",$text);
        foreach($explode as $key=>$val){
            if(strpos($val," - Won") !== false or strpos($val," - Lost") !== false){
                $explode2 = explode(" - ",$val);
                $namanya = explode(" (",$explode2[0])[0];
                if(strpos($explode2[count($explode2)-1],"Won")!== false){
                    if(strpos($val," (Dog ") !== false){
                        $menang = 0;
                        $kalah = 0;
                        $dog = 1;								
                    }
                    else{
                        $menang = 1;
                        $kalah = 0;
                        $dog = 0;
                    }
                }elseif(strpos($explode2[count($explode2)-1],"Lost")!== false){
                    $menang = 0;
                    $kalah = 1;
                    $dog = 0;
                }else{
                    $menang = "ERROR";
                }
                $score['criminaldancebot'][$gameroundnya][$namanya]['menangBiasa'] = $menang;
                $score['criminaldancebot'][$gameroundnya][$namanya]['menangAnjing'] = $dog;
                $score['criminaldancebot'][$gameroundnya][$namanya]['kalah'] = $kalah;						
            }
        }
        $output .= "\n/scoreCriminalDanceBot";
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id'=>$message_id
        );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    elseif(isset($message_data['forward_from']) and $message_data['forward_from']['username'] == "jokebig2bot"){
        if(empty($gameround['jokebig2bot'])){
            $gameround['jokebig2bot'] = 1;
            $output = "game baru dimulai.";
        }else{
            $output = "$emoji_check round " . $gameround['jokebig2bot'];
        }
        $gameroundnya = $gameround['jokebig2bot'];
        $gameround['jokebig2bot'] ++;
        $explode = explode("\n",$text);
        foreach($explode as $key=>$val){
            $namanya = explode(" [",$val)[0];
            $x = strrpos($namanya, " - ");
            $namanya = substr($namanya,0,$x);
            if(strpos($val," [+") !== false or strpos($val," [-") !== false){
                $scorenya = 0;
                if(strpos($val," [+") !== false){
                    $daptescorenya = preg_replace('/\D/', '', explode(" [+",$val)[1]);
                    $plusnya = $daptescorenya;
                    $minusnya = 0;
                    $scorenya += $daptescorenya;
                }
                else{
                    $daptescorenya = preg_replace('/\D/', '', explode(" [-",$val)[1]);
                    $plusnya = 0;
                    $minusnya = $daptescorenya;
                    $scorenya -= $daptescorenya;
                }
                $score['jokebig2bot'][$gameroundnya][$namanya]['plus'] = $plusnya;
                $score['jokebig2bot'][$gameroundnya][$namanya]['minus'] = $minusnya;
                $score['jokebig2bot'][$gameroundnya][$namanya]['score'] = $scorenya;						
            }
        }
        $output .= "\n/scorejokebig2bot";
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id'=>$message_id
        );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    elseif(isset($message_data['forward_from'])){
        $output = "Forwarded Data:\n\n";
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output . print_r($message_data,1),
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id'=>$message_id
        );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }			
    elseif(substr($isi,0,6) == "/score"){
        $gamenya = str_ireplace("/score","",$isi);
        if(empty($score[$gamenya])){
            $output = "ERROR\n" . print_r($score,true);
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id'=>$message_id
            );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
        }
        else{
            $data = array(
                'chat_id' => $chatid,
                'text'=> "tips: gunakan <b>text to column</b> dengan delimiter tanda <b>~</b>\nhttp://www.google.com/search?q=cara+excel+text+to+column \n\nRestart Skor: /restartscore_$gamenya",
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id'=>$message_id
            );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
            reset($score[$gamenya]);
            $first_key = key($score[$gamenya]['1']);
            $keys = array_keys($score[$gamenya]['1'][$first_key]);
            $playerscores = array();
            foreach($score[$gamenya] as $key=>$val){
                foreach($val as $key2=>$val2){
                    foreach($keys as $val3){
                        $playerscores[$key2][$key][$val3] = $val2[$val3];
                    }
                }
            }
            foreach($playerscores as $key=>$val){
                foreach($val as $key2=>$val2){
                    foreach($keys as $val3){
                        $playerscores[$key]['total'][$val3] += $val2[$val3];
                    }
                }
            }
            $rounds = array_keys($score[$gamenya]);
            $rounds[] = "total";
            foreach($rounds as $val){
                $output = "<b>Game $val\n";
                // $output .= print_r($val,true);
                $output .= "Name~".implode("~",$keys);
                $output .= "</b>\n\n";
                foreach($playerscores as $key2=>$val2){
                    $array_data = array();
                    $array_data[] = $key2;
                    foreach($keys as $val3){
                        $array_data[] = $val2[$val][$val3];
                    }
                    $output .= implode("~",$array_data) . "\n";
                }
                $data = array(
                    'chat_id' => $chatid,
                    'text'=> $output,
                    'parse_mode'=>'HTML',
                    'disable_web_page_preview'=>true
                );
                $hasil = KirimPerintahX($token,'sendMessage',$data);
            }	
        }
    }
    elseif(substr($isi,0,14) == "/restartscore_"){
        $gamenya = str_ireplace("/restartscore_","",$isi);
        if(empty($score[$gamenya])){
            $output = "ERROR\n" . print_r($score,true);
        }
        else{
            unset($score[$gamenya]);
            $output = "SKOR $gamenya direset";
        }
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id'=>$message_id
        );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
    */
}

//main group:
elseif(($chatid == $admingroup or in_array($chatid,$groups))
and $isi == "/testbtn"){
    $button = array();
    $button[] = '1';
    $button[] = '2';
    $button[] = '3';
    $button[] = '4';
    $button[] = '5';
    $button[] = '6';
    $button[] = '7';
    $data = array(
        'chat_id' => $chatid,
        'text'=> "Test Button",
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_markup' => makebutton($button)
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or in_array($chatid,$groups))
and $isi == "/debug_lncn"){
    print_r($gamedata['lncn']);
    $data = array(
        'chat_id' => $chatid,
        'text'=> print_r($gamedata['lncn'],1)
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
}
elseif(($chatid == $admingroup or in_array($chatid,$groups))
and empty($gamedata['lncn'][$chatid]['started'])
and ($isi == "/join_lncn" or $isi == "/flee_lncn")){
    if(empty($gamedata['lncn'][$chatid])){
        $gamedata['lncn'][$chatid]['started'] = 0;
        $gamedata['lncn'][$chatid]['players'] = array();
        $gamedata['lncn'][$chatid]['ln'] = 0;
        $gamedata['lncn'][$chatid]['cn'] = 0;
        $gamedata['lncn'][$chatid]['unpicked'] = array();
        $gamedata['lncn'][$chatid]['starting_time'] = 0;
        $gamedata['lncn'][$chatid]['step'] = 0;
        $gamedata['lncn'][$chatid]['steptime'] = 0;
    }
    if($isi == "/join_lncn"){
        if(empty($gamedata['lncn'][$chatid]['players'][$dari])){
            $gamedata['lncn'][$chatid]['players'][$dari]['nama'] = "<a href='tg://user?id=$dari'>$nama</a>";
            $gamedata['lncn'][$chatid]['players'][$dari]['ln_stored'] = 0;
            $gamedata['lncn'][$chatid]['players'][$dari]['cn_stored'] = 0;
            $gamedata['lncn'][$chatid]['players'][$dari]['picked'] = array();
            $gamedata['lncn'][$chatid]['players'][$dari]['got_lc'] = "";
        }
    }elseif($isi == "/flee_lncn"){
        if(!empty($gamedata['lncn'][$chatid]['players'][$dari])){
            unset($gamedata['lncn'][$chatid]['players'][$dari]);
        }
    }
    
    $output = "LUCKY NUMBER CURSED NUMBER\n\n";
    $output .= "Pemain:\n";
    foreach($gamedata['lncn'][$chatid]['players'] as $key=>$val){
        $output .= $val['nama'] . "\n";
    }
    $output .= "/flee_lncn - batal ikut\n";
    $output .= "\nJumlah Pemain: ".count($gamedata['lncn'][$chatid]['players'])."\n";
    $output .= "Minimal Pemain: 3\n";
    if(count($gamedata['lncn'][$chatid]['players']) >= 3){
        $output .= "\n/start_lncn - mulai\n\n";
    }else{
        $output .= "Jumlah pemain belum cukup\n";
    }
    $output .= "Yang lain ayo /join_lncn juga\n";
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id' => $message_id
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);	
}
elseif(($chatid == $admingroup or in_array($chatid,$groups))
and empty($gamedata['lncn'][$chatid]['started'])
and $isi == "/start_lncn"){
    if(empty($gamedata['lncn'][$chatid]['players'][$dari])){
        $data = array(
            'chat_id' => $chatid,
            'text'=> "Kamu belum join. /join_lncn",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);		
    }
    elseif(count($gamedata['lncn'][$chatid]['players'])<3){
        $data = array(
            'chat_id' => $chatid,
            'text'=> "Jumlah pemain kurang, yang lain ayo /join_lncn juga!",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);		
    }else{
        $gamedata['lncn'][$chatid]['started'] = 1;
        
        $gamedata['lncn'][$chatid]['ln'] = 0;
        $gamedata['lncn'][$chatid]['cn'] = 0;
        $gamedata['lncn'][$chatid]['unpicked'] = array();
        $gamedata['lncn'][$chatid]['starting_time'] = 0;
        $gamedata['lncn'][$chatid]['steptime'] = 0;
        $gamedata['lncn'][$chatid]['lucky'] = "";
        $gamedata['lncn'][$chatid]['cursed'] = "";
        $gamedata['lncn'][$chatid]['darer'] = array();
        $gamedata['lncn'][$chatid]['dared'] = array();
        $gamedata['lncn'][$chatid]['step'] = "setor";
        $data = array(
            'chat_id' => $chatid,
            'text'=> "Permainan akan segera dimulai",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(($chatid == $admingroup or in_array($chatid,$groups))
and $isi == "/stop_lncn"){
    if(empty($activeusers[$dari]['admin_active'])){
        $data = array(
            'chat_id' => $chatid,
            'text'=> "Command ini hanya untuk admin",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }else{
        unset($gamedata['lncn'][$chatid]);
        $data = array(
            'chat_id' => $chatid,
            'text'=> "Permainan LUCKY NUMBER CURSED NUMBER dihentikan.",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_to_message_id' => $message_id
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif(!empty($message_data['reply_to_message'])
and $message_data['reply_to_message']['from']['username'] == $botname
and strpos($message_data['reply_to_message']['text'],"yo berikan TRUTH OR DARE kepada") !== false 
and !empty($gamedata['lncn'][$chatid]['started'])
and $gamedata['lncn'][$chatid]['step'] == "sedangkasihdare" 
and in_array($dari,$gamedata['lncn'][$chatid]['darer'])){
    unset($gamedata['lncn'][$chatid]['darer'][$dari]);
    $output = "#TRUTHORDARE ";
    foreach($gamedata['lncn'][$chatid]['dared'] as $dared){
        $output .= $gamedata['lncn'][$chatid]['players'][$dared]['nama'] . " ";
    }
    $data = array(
        'chat_id' => $chatid,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id' => $message_id
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
    if(empty($gamedata['lncn'][$chatid]['darer'])){
        $gamedata['lncn'][$chatid]['step'] = "selesai";
        $gamedata['lncn'][$chatid]['steptime'] = 0;				
    }
}

elseif(
// in_array($chatid,$groups) 
($chatid == $maingroup or $chatid == '-1001149199985')
and 
(strpos($isi,"@admin") !== false 
or (!empty($message_data['reply_to_message']) and $message_data['reply_to_message']['from']['username'] == $botname)
or strpos($isi,"@indogamebot") !== false
)){
    $data = array(
        'chat_id' => $admingroup,
        'from_chat_id'=> $chatid,
        'message_id'=>$message_id
        );
    $hasil = KirimPerintahX($token,'forwardMessage',$data);
    // $message_out = json_decode($hasil,true);
    $output = "";
    $keynya = array_search($chatid,$groups);
    $output .= "Balas di sini. ID: $keynya"."l".$message_id;
    $button1['text'] = "$emoji_pesan KE PESAN";
    if($chatid == $maingroup){
        $button1['url'] = "t.me/groupindogame/$message_id";				
    }
    else{
        $button1['url'] = "t.me/werewolfindogame/$message_id";
    }
    $button2['text'] = "$emoji_check BERES! ";
    $button2['callback_data'] = $message_id;
    $keyboard['inline_keyboard'] = array(array($button1,$button2));
    $encodedKeyboard = json_encode($keyboard);						
    $data = array(
        'chat_id' => $admingroup,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        'reply_to_message_id' => "",//$message_out['result']['message_id'],
        'reply_markup' => $encodedKeyboard
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
    $output = "";
    foreach($activeusers as $key=>$val){
        if($val['admin_active'] == '1'){
            $output .= $val['user_name'] . "\n";
        }
    }
    $data = array(
        'chat_id' => $admingroup,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true,
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
    // $message_out = json_decode($hasil,true);
    $message_out = $hasil;
    $keymessaggeid = $keynya."l".$message_id;
    $admin_mentions[$keymessaggeid]=$message_out['result']['message_id'];
}

elseif(($chatid == $admingroup or in_array($chatid,$groups) or $jenis == "private")
and !empty($isi) and strpos($text,"'") === false){
    // $st = $app["pdo.DB_IDG"]->prepare("select command, message, reply_mode from idg_msg_cmd where 
    // active = '1' and message is not null and
    // (
    // (lower(command) = '$isi' and case_sensitive = '0' and whole_word = '1') or
    // ('$isi' like '%'||lower(command)||'%' and case_sensitive = '0' and whole_word = '0') or
    // ('$isi' like lower(command)||'%' and case_sensitive = '0' and whole_word = '2') or
    // (command ='".str_ireplace("@$botname","",$text)."' and case_sensitive = '1' and whole_word = '1') or
    // ('".str_ireplace("@$botname","",$text)."' like '%'||command||'%' and case_sensitive = '1' and whole_word = '0') or
    // ('".str_ireplace("@$botname","",$text)."' like command||'%' and case_sensitive = '1' and whole_word = '2')
    // )
    // order by random() limit 1");
    // $st->execute();
    if(str_compare("/tesgj",$text,"insensitive")){
        $de_msgcmd_bug = true;
    }
    else{
        $de_msgcmd_bug = false;
    }
    $sdata = sdata_find_one($db_alias."_MSG_CMD",[
        'command'=>[str_ireplace("@$botname","",$text),"exact"],
        'active' => 1,
        'case_sensitive' => 1,
        'whole_word'=>1,
        'message' => "~is_not_null",
    ],[
        'command', 'message', 'reply_mode'
    ]
        // ,$de_msgcmd_bug
    );

    if(!$sdata){
        $sdata = sdata_find_one($db_alias."_MSG_CMD",[
            'command'=>[str_ireplace("@$botname","",$text),"insensitive"],
            'active' => 1,
            'case_sensitive' => 0,
            'whole_word'=>1,
            'message' => "~is_not_null",
        ],[
            'command', 'message', 'reply_mode'
        ]
            // ,$de_msgcmd_bug
        );
    }
    if(!$sdata){
        $sdata = sdata_find_one($db_alias."_MSG_CMD",[
            'command'=>[str_ireplace("@$botname","",$text),"first_sensitive"],
            'active' => 1,
            'message' => "~is_not_null",
            'case_sensitive' => 1,
            'whole_word'=>2,
        ],[
            'command', 'message', 'reply_mode'
        ]
            // ,$de_msgcmd_bug
        );
    }
    if(!$sdata){
        $sdata = sdata_find_one($db_alias."_MSG_CMD",[
            'command'=>[str_ireplace("@$botname","",$text),"contains_sensitive"],
            'active' => 1,
            'message' => "~is_not_null",
            'case_sensitive' => 1,
            'whole_word'=>0,
        ],[
            'command', 'message', 'reply_mode'
        ]
            // ,$de_msgcmd_bug
        );
    }
    if(!$sdata){
        $sdata = sdata_find_one($db_alias."_MSG_CMD",[
            'command'=>[str_ireplace("@$botname","",$text),"first_insensitive"],
            'active' => 1,
            'case_sensitive' => 0,
            'whole_word'=>2,
            'message' => "~is_not_null",
        ]
            // ,$de_msgcmd_bug
        );
    }
    if(!$sdata){
        $sdata = sdata_find_one($db_alias."_MSG_CMD",[
            'command'=>[str_ireplace("@$botname","",$text),"contains_insensitive"],
            'active' => 1,
            'case_sensitive' => 0,
            'whole_word'=>0,
            'message' => "~is_not_null",
        ],[
            'command', 'message', 'reply_mode'
        ]
            // ,$de_msgcmd_bug
        );
    }

    $output = "";
    $message_idnya = "";
    $commandnya = "";
    if($sdata){
        file_put_contents("msgcmdlog/".date("Y-m-d-H-i"),print_r([
            'update'=>$update,
            'sdata'=>$sdata,
        ],true));
    }
    
    
    // while($row = $st->fetch(PDO::FETCH_ASSOC)){
    //     $output = $row['message'];
    //     $commandnya = $row['command'];
    //     if($row['reply_mode'] == '1'){
    //         $message_idnya = $message_id;
    //     }elseif($row['reply_mode'] == '2'){
    //         $message_idnya = $message_data['reply_to_message']["message_id"];
    //     }
    // }; 
    if($sdata){
        $output = $sdata['message'];
        $commandnya = $sdata['command'];
        if($sdata['reply_mode'] == '1'){
            $message_idnya = $message_id;
        }elseif($sdata['reply_mode'] == '2'){
            $message_idnya = $message_data['reply_to_message']["message_id"];
        }
    }
    if(!empty($output) and !empty($commandnya)){
        if(substr($output,0,7) == "*photo "){
            $explode = explode("|",$output);
            $file_id = $explode[1];
            $data = array(
                'chat_id' => $chatid,
                'photo'=> $file_id,
                'caption' => str_replace("*photo ","",$explode[0]),
                'reply_to_message_id' => $message_idnya
            );	
            $hasil = KirimPerintahX($token,'sendPhoto',$data);	
        }elseif(substr($output,0,9) == "*sticker "){
            $explode = explode("|",$output);
            $stickernya = $explode[1];
            $data = array(
                'chat_id' => $chatid,
                'sticker'=> $stickernya,
                'reply_to_message_id' => $message_idnya
            );	
            $hasil = KirimPerintahX($token,'sendSticker',$data);	
        }elseif(substr($output,0,10) == "*document "){
            $explode = explode("|",$output);
            $file_id = $explode[1];
            $data = array(
                'chat_id' => $chatid,
                'document'=> $file_id,
                'reply_to_message_id' => $message_idnya
            );	
            $hasil = KirimPerintahX($token,'sendDocument',$data);	
        }elseif(substr($output,0,7) == "*voice "){
            $explode = explode("|",$output);
            $file_id = $explode[1];
            $data = array(
                'chat_id' => $chatid,
                'voice'=> $file_id,
                'reply_to_message_id' => $message_idnya
            );	
            $hasil = KirimPerintahX($token,'sendVoice',$data);	
        }elseif(substr($output,0,7) == "*location "){
            $explode = explode("|",$output);
            $explode2 = explode(",",$explode[1]);
            $data = array(
                'chat_id' => $chatid,
                'latitude'=> $explode2[0],
                'longitude'=> $explode2[1],
                'reply_to_message_id' => $message_idnya
            );	
            $hasil = KirimPerintahX($token,'sendLocation',$data);	
        }else{
            if(strtolower(substr($output,0,8)) == "~norend~"){
                $output = str_ireplace('~norend~','',$output);
            }else{
                if(strpos($output, '(ada_ket)')!== false or strpos($output, '(ada_dpn)')!== false){
                    $explode = explode($commandnya,$text);
                    $cmd_dpn = $explode[0];
                    $cmd_ket = $explode[1];							
                }
                else{
                    $cmd_dpn = "";
                    $cmd_ket = "";	
                }
                $diapit_start = strpos($output, '(isreply)');
                $diapit_end = strpos($output, '(/isreply)');
                while($diapit_start !== false and $diapit_end !== false){
                    // echo "idg_diapit_start=$diapit_start idg_diapit_end=$diapit_end ";
                    $diapit_start += strlen('(isreply)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty($message_data['reply_to_message'])){
                        $output = str_ireplace("(isreply)$diapit(/isreply)","",$output);
                    }else{
                        $output = str_ireplace("(isreply)","",str_ireplace("(/isreply)","",$output));
                    }
                    $diapit_start = strpos($output, '(isreply)');
                    $diapit_end = strpos($output, '(/isreply)');
                    // echo "idg_diapit_start=$diapit_start idg_diapit_end=$diapit_end\n";
                }
                $diapit_start = strpos($output, '(isnotreply)');
                $diapit_end = strpos($output, '(/isnotreply)');
                while($diapit_start !== false and $diapit_end !== false){
                    // echo "2idg_diapit_start=$diapit_start idg_diapit_end=$diapit_end ";
                    $diapit_start += strlen('(isnotreply)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty($message_data['reply_to_message'])){
                        $output = str_ireplace("(isnotreply)","",str_ireplace("(/isnotreply)","",$output));
                    }else{
                        $output = str_ireplace("(isnotreply)$diapit(/isnotreply)","",$output);
                    }
                    $diapit_start = strpos($output, '(isnotreply)');
                    $diapit_end = strpos($output, '(/isnotreply)');
                    // echo "2idg_diapit_start=$diapit_start idg_diapit_end=$diapit_end \n";
                }
                
                $diapit_start = strpos($output, '(ada_ket)');
                $diapit_end = strpos($output, '(/ada_ket)');
                while($diapit_start !== false and $diapit_end !== false){
                    // echo "3idg_diapit_start=$diapit_start idg_diapit_end=$diapit_end ";
                    $diapit_start += strlen('(ada_ket)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty(trim($cmd_ket))){
                        $output = str_ireplace("(ada_ket)$diapit(/ada_ket)","",$output);
                    }else{
                        $output = str_ireplace("(ada_ket)","",str_ireplace("(/ada_ket)","",$output));
                    }
                    $diapit_start = strpos($output, '(ada_ket)');
                    $diapit_end = strpos($output, '(/ada_ket)');
                    // echo "3idg_diapit_start=$diapit_start idg_diapit_end=$diapit_end \n";
                }
                $diapit_start = strpos($output, '(tdk_ada_ket)');
                $diapit_end = strpos($output, '(/tdk_ada_ket)');
                while($diapit_start !== false and $diapit_end !== false){
                    $diapit_start += strlen('(tdk_ada_ket)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty(trim($cmd_ket))){
                        $output = str_ireplace("(tdk_ada_ket)","",str_ireplace("(/tdk_ada_ket)","",$output));
                    }else{
                        $output = str_ireplace("(tdk_ada_ket)$diapit(/tdk_ada_ket)","",$output);
                    }
                    $diapit_start = strpos($output, '(tdk_ada_ket)');
                    $diapit_end = strpos($output, '(/tdk_ada_ket)');
                }
                
                $diapit_start = strpos($output, '(ada_dpn)');
                $diapit_end = strpos($output, '(/ada_dpn)');
                while($diapit_start !== false and $diapit_end !== false){
                    // echo "3idg_diapit_start=$diapit_start idg_diapit_end=$diapit_end ";
                    $diapit_start += strlen('(ada_dpn)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty(trim($cmd_dpn))){
                        $output = str_ireplace("(ada_dpn)$diapit(/ada_dpn)","",$output);
                    }else{
                        $output = str_ireplace("(ada_dpn)","",str_ireplace("(/ada_dpn)","",$output));
                    }
                    $diapit_start = strpos($output, '(ada_dpn)');
                    $diapit_end = strpos($output, '(/ada_dpn)');
                    // echo "3idg_diapit_start=$diapit_start idg_diapit_end=$diapit_end \n";
                }
                $diapit_start = strpos($output, '(tdk_ada_dpn)');
                $diapit_end = strpos($output, '(/tdk_ada_dpn)');
                while($diapit_start !== false and $diapit_end !== false){
                    $diapit_start += strlen('(tdk_ada_dpn)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty(trim($cmd_dpn))){
                        $output = str_ireplace("(tdk_ada_dpn)","",str_ireplace("(/tdk_ada_dpn)","",$output));
                    }else{
                        $output = str_ireplace("(tdk_ada_dpn)$diapit(/tdk_ada_dpn)","",$output);
                    }
                    $diapit_start = strpos($output, '(tdk_ada_dpn)');
                    $diapit_end = strpos($output, '(/tdk_ada_dpn)');
                }
                
                $diapit_start = strpos($output, '(ada_sbj_un)');
                $diapit_end = strpos($output, '(/ada_sbj_un)');
                while($diapit_start !== false and $diapit_end !== false){
                    $diapit_start += strlen('(ada_sbj_un)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty($message_data["from"]["username"])){
                        $output = str_ireplace("(ada_sbj_un)$diapit(/ada_sbj_un)","",$output);
                    }else{
                        $output = str_ireplace("(ada_sbj_un)","",str_ireplace("(/ada_sbj_un)","",$output));
                    }
                    $diapit_start = strpos($output, '(ada_sbj_un)');
                    $diapit_end = strpos($output, '(/ada_sbj_un)');
                }
                $diapit_start = strpos($output, '(tdk_ada_sbj_un)');
                $diapit_end = strpos($output, '(/tdk_ada_sbj_un)');
                while($diapit_start !== false and $diapit_end !== false){
                    $diapit_start += strlen('(tdk_ada_sbj_un)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty($message_data["from"]["username"])){
                        $output = str_ireplace("(tdk_ada_sbj_un)","",str_ireplace("(/tdk_ada_sbj_un)","",$output));
                    }else{
                        $output = str_ireplace("(tdk_ada_sbj_un)$diapit(/tdk_ada_sbj_un)","",$output);
                    }
                    $diapit_start = strpos($output, '(tdk_ada_sbj_un)');
                    $diapit_end = strpos($output, '(/tdk_ada_sbj_un)');
                }
                
                $diapit_start = strpos($output, '(ada_obj_un)');
                $diapit_end = strpos($output, '(/ada_obj_un)');
                while($diapit_start !== false and $diapit_end !== false){
                    $diapit_start += strlen('(ada_obj_un)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty($message_data['reply_to_message']["from"]["username"])){
                        $output = str_ireplace("(ada_obj_un)$diapit(/ada_obj_un)","",$output);
                    }else{
                        $output = str_ireplace("(ada_obj_un)","",str_ireplace("(/ada_obj_un)","",$output));
                    }
                    $diapit_start = strpos($output, '(ada_obj_un)');
                    $diapit_end = strpos($output, '(/ada_obj_un)');
                }
                $diapit_start = strpos($output, '(tdk_ada_obj_un)');
                $diapit_end = strpos($output, '(/tdk_ada_obj_un)');
                while($diapit_start !== false and $diapit_end !== false){
                    $diapit_start += strlen('(tdk_ada_obj_un)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty($message_data['reply_to_message']["from"]["username"])){
                        $output = str_ireplace("(tdk_ada_obj_un)","",str_ireplace("(/tdk_ada_obj_un)","",$output));
                    }else{
                        $output = str_ireplace("(tdk_ada_obj_un)$diapit(/tdk_ada_obj_un)","",$output);
                    }
                    $diapit_start = strpos($output, '(tdk_ada_obj_un)');
                    $diapit_end = strpos($output, '(/tdk_ada_obj_un)');
                }
                
                $diapit_start = strpos($output, '(ada_rep_txt)');
                $diapit_end = strpos($output, '(/ada_rep_txt)');
                while($diapit_start !== false and $diapit_end !== false){
                    $diapit_start += strlen('(ada_rep_txt)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty($message_data['reply_to_message']["text"])){
                        $output = str_ireplace("(ada_rep_txt)$diapit(/ada_rep_txt)","",$output);
                    }else{
                        $output = str_ireplace("(ada_rep_txt)","",str_ireplace("(/ada_rep_txt)","",$output));
                    }
                    $diapit_start = strpos($output, '(ada_rep_txt)');
                    $diapit_end = strpos($output, '(/ada_rep_txt)');
                }
                $diapit_start = strpos($output, '(tdk_ada_rep_txt)');
                $diapit_end = strpos($output, '(/tdk_ada_rep_txt)');
                while($diapit_start !== false and $diapit_end !== false){
                    $diapit_start += strlen('(tdk_ada_rep_txt)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if(empty($message_data['reply_to_message']["text"])){
                        $output = str_ireplace("(tdk_ada_rep_txt)","",str_ireplace("(/tdk_ada_rep_txt)","",$output));
                    }else{
                        $output = str_ireplace("(tdk_ada_rep_txt)$diapit(/tdk_ada_rep_txt)","",$output);
                    }
                    $diapit_start = strpos($output, '(tdk_ada_rep_txt)');
                    $diapit_end = strpos($output, '(/tdk_ada_rep_txt)');
                }
                
                $diapit_start = strpos($output, '(obj=sbj)');
                $diapit_end = strpos($output, '(/obj=sbj)');
                if($diapit_start !== false and $diapit_end !== false){
                    $diapit_start += strlen('(obj=sbj)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if($dari == $message_data['reply_to_message']["from"]["id"]){
                        $output = str_ireplace("(obj=sbj)","",str_ireplace("(/obj=sbj)","",$output));
                    }else{
                        $output = str_ireplace("(obj=sbj)$diapit(/obj=sbj)","",$output);
                    }
                    $diapit_start = strpos($output, '(obj=sbj)');
                    $diapit_end = strpos($output, '(/obj=sbj)');
                }
                
                $diapit_start = strpos($output, '(obj!=sbj)');
                $diapit_end = strpos($output, '(/obj!=sbj)');
                while($diapit_start !== false and $diapit_end !== false){
                    $diapit_start += strlen('(obj!=sbj)');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    if($dari != $message_data['reply_to_message']["from"]["id"]){
                        $output = str_ireplace("(obj!=sbj)","",str_ireplace("(/obj!=sbj)","",$output));
                    }else{
                        $output = str_ireplace("(obj!=sbj)$diapit(/obj!=sbj)","",$output);
                    }
                    $diapit_start = strpos($output, '(obj!=sbj)');
                    $diapit_end = strpos($output, '(/obj!=sbj)');
                }
                
                $reply_markup = json_encode([]);
                $diapit_start = strpos($output, '(btn=');
                $diapit_end = strpos($output, '(/btn)');
                while($diapit_start !== false and $diapit_end !== false){
                    $diapit_start += strlen('(btn=');
                    $diapit = substr($output, $diapit_start, $diapit_end-$diapit_start);
                    $output = str_ireplace("(btn=$diapit(/btn)","",$output);
                    $explode = explode(")",$diapit);
                    $btnurl = $explode[0];
                    $btncapt = str_replace("$btnurl)","",$diapit);
                    if(!empty($btnurl) and !empty($btncapt)){
                        $reply_markup = addUrlButton($btnurl,$btncapt,$reply_markup);
                    }
                    $diapit_start = strpos($output, '(btn=');
                    $diapit_end = strpos($output, '(/btn)');
                }
                
                
                $output = str_ireplace('(sbj_dpn)',$message_data["from"]["first_name"],$output);
                $output = str_ireplace('(sbj_blk)',$message_data["from"]["last_name"],$output);
                $output = str_ireplace('(sbj_un)',$message_data["from"]["username"],$output);
                $output = str_ireplace('(sbj_id)',$message_data["from"]["id"],$output);
                $output = str_ireplace('(sbj)',$nama,$output);
                $output = str_ireplace('(fw_id)',$message_data['forward_from']['id'] . $message_data['reply_to_message']['forward_from']['id'],$output);
                $obj_first_name = $message_data['reply_to_message']["from"]["first_name"];		
                $obj_last_name = $message_data['reply_to_message']["from"]["last_name"];
                if(empty($obj_last_name)){
                    $obj_nama = $obj_first_name;
                }else{
                    $obj_nama = $obj_first_name . " " . $obj_last_name;
                }
                if(strpos($output,"(obj=sbj_as_") !== false){
                    $explode = explode("(obj=sbj_as_",$output);
                    $obj_namanya = explode(")",$explode[1])[0];
                    $output = str_ireplace("(obj=sbj_as_$obj_namanya)",'',$output);
                    if($dari == $message_data['reply_to_message']["from"]["id"]){
                        $obj_nama = $obj_namanya;
                        $obj_first_name = $obj_nama;
                        $obj_last_name = $obj_nama;
                    }
                }
                $output = str_ireplace('(obj_dpn)',$obj_first_name,$output);
                $output = str_ireplace('(obj_blk)',$obj_last_name,$output);
                $output = str_ireplace('(obj_un)',$message_data['reply_to_message']["from"]["username"],$output);
                $output = str_ireplace('(obj_id)',$message_data['reply_to_message']["from"]["id"],$output);
                $output = str_ireplace('(obj)',$obj_nama,$output);
                $output = str_ireplace('@sbj(',"<a href='tg://user?id=$dari'>",$output);
                $output = str_ireplace('@obj(',"<a href='tg://user?id=".$message_data['reply_to_message']["from"]["id"]."'>",$output);
                $output = str_ireplace(')@','</a>',$output);
                // $output = str_ireplace('(cmd_ket)',trim(str_ireplace("@$botname","",str_ireplace($commandnya,"",$text))),$output);
                $output = str_ireplace('(cmd_ket)',trim($cmd_ket),$output);
                $output = str_ireplace('(cmd_dpn)',trim($cmd_dpn),$output);
                $output = str_ireplace('(rep_txt)',$message_data['reply_to_message']["text"],$output);
            }
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true,
                'reply_to_message_id' => $message_idnya
                );
                
            if(!empty(json_decode($reply_markup,1))){
                $data['reply_markup'] = $reply_markup;
            }
            $hasil = KirimPerintahX($token,'sendMessage',$data);
            // if($jenis == "private"){
                // $data = array(
                    // 'chat_id' => $pmgroup,
                    // 'from_chat_id'=> $chatid,
                    // 'message_id'=>$message_id
                    // );
                // $hasil = KirimPerintahX($token,'forwardMessage',$data);
            // }
        }
    }
    elseif($jenis == "private"){
        $data = array(
            'chat_id' => $pmgroup,
            'from_chat_id'=> $chatid,
            'message_id'=>$message_id
            );
        $hasil = KirimPerintahX($token,'forwardMessage',$data);
        $output = "Reply mode: $chatid"."l".$message_id;
        $data = array(
            'chat_id' => $pmgroup,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
elseif($jenis == "private"){
    $data = array(
        'chat_id' => $pmgroup,
        'from_chat_id'=> $chatid,
        'message_id'=>$message_id
        );
    $hasil = KirimPerintahX($token,'forwardMessage',$data);
    $output = "Reply mode: $chatid"."l".$message_id;
    $data = array(
        'chat_id' => $pmgroup,
        'text'=> $output,
        'parse_mode'=>'HTML',
        'disable_web_page_preview'=>true
        );
    $hasil = KirimPerintahX($token,'sendMessage',$data);
    if(isset($message_data['contact']['user_id'])){
        KirimPerintahX($token,'sendMessage',[
            'chat_id' => $chatid,
            'text'=> "UNDERCONSTRUCTION \ncolek @galihjk",
            'reply_to_message_id'=>$message_id
        ]);
        // $nomorhpnya = $message_data['contact']['phone_number'];
        // $useridnya = $message_data['contact']['user_id'];
        // $st = $app["pdo.DB_IDG"]->prepare("update idg_users set
        // telp = '$nomorhpnya'
        // where user_id = '$useridnya'");
        // $st->execute();
        // $output = "Contact saved: /id_$useridnya";
        // $data = array(
        //     'chat_id' => $admingroup,
        //     'text'=> $output,
        //     'parse_mode'=>'HTML',
        //     'disable_web_page_preview'=>true
        //     );
        // $hasil = KirimPerintahX($token,'sendMessage',$data);
    }
}
skip_blocked:

;