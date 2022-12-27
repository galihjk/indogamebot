<?php

include_once("helpers/hlp_config.php");
include_once("helpers/hlp_data.php");
include_once("helpers/hlp_sdata.php");
include_once("helpers/hlp_bot.php");
include_once("helpers/hlp_botmarkup.php");
include_once("emoji.php");
$bot_config = getConfig('bot', []);
foreach($bot_config as $bot=>$config){
    $token = $config['token'];
    $developer = $config['developer'];
    $admingroup = $config['admingroup'];
    $pmgroup = $config['pmgroup'];
    $db_alias = $config['alias'];
    $groups = explode(", ",(sdata_find_one($db_alias."_SETTINGS",['key'=>'allowed_groups'])['value'] ?? ""));
    $maingroup = $groups[0] ?? "";
    $scoregroup = $config['scoregroup'] ?? "";
    $botname = $config['botname'] ?? $bot;
    $welcome_message = (sdata_find_one($db_alias."_SETTINGS",['key'=>'welcome_msgcmd'])['value'] ?? "");
    //clear_user====
    $jmluser = sdata_count($db_alias."_USERS", "user_id");
    $max = 9999;
    $kelebihan = $jmluser - $max;
    if($kelebihan>0){
        //underc
    }
    //==============
    // STICKER================
    // $stickerset = unserialize((sdata_find_one($db_alias."_SETTINGS",['key'=>'welcome_msgcmd'])['value'] ?? serialize([])));
    // $max_sticker = 500;
    // if(count($stickerset) > $max_sticker){
    //     $kelebihan = count($stickerset) - $max_sticker;
    //     echo "new stickers: $kelebihan\n";
    //     foreach($stickerset as $key=>$val){
    //         unset($stickerset[$key]);
    //         $kelebihan--;
    //         if($kelebihan < 1){
    //             break;
    //         }
    //     }
    // }


    $update_interval_def = 90; 
    $update_interval_rnd = 60;
    
    $forcereply = array('force_reply' => true, 'selective' => true);
    $forcereply = json_encode($forcereply);
    // $starting_timeout = loadData("starting_timeout_$bot",600);
    // $starting_notif = loadData("starting_notif_$bot",90);
    // $update_interval_def = loadData("update_interval_def_$bot",90);
    // $update_interval_rnd = loadData("update_interval_rnd_$bot",60);
    $update_interval = loadData("update_interval_def_$bot",$update_interval_def);
    $updateuser = loadData("updateuser_$bot",[]);
    $debugmode = loadData("debugmode_$bot",false);
    $gamedata = loadData("gamedata_$bot",[]);
    $impersonate = loadData("impersonate_$bot",[]);
    $activeusers = loadData("activeusers_$bot",[]);
    $blocked = loadData("blocked_$bot",[]);
    $update_id = loadData("updid_$bot",0);
    $admin_mentions = loadData("admin_mentions_$bot",0);

    //untuk setiap pesan yang masuk:=========================================================
    $updates = DapatkanUpdate($update_id, $token);
    foreach($updates as $update){
        include("main_update.php");
        $update_id = 1+$update["update_id"];
    }

    //untuk setiap game    
    if(!empty($gamedata['lncn'])){
		foreach($gamedata['lncn'] as $chatid=>$game){
            include("game_lncn.php");
        }
    }

    //update user 
	if($update_interval>0){
		$update_interval -= $jeda;
	}
    else{
		$update_interval = $update_interval_def + rand(0,$update_interval_rnd);
		foreach($updateuser as $key=>$val){
			if(!empty($val)){
				if($key == "admin"){
					foreach($val as $key2=>$val2){
                        sdata_update_filtered($db_alias."_USERS",[
                            'user_id' => $key2
                        ],[
                            'admin_last_active' => time(),
                            'admin_active' => 1,
                            'user_name' => $val2['user_name'],
                            'name' => $val2['name'],
                        ],1);
					}
				}elseif($key == "group"){
					foreach($val as $key2=>$val2){
                        sdata_update_filtered($db_alias."_USERS",[
                            'user_id' => $key2
                        ],[
                            'last_active' => time(),
                            'group_active' => 1,
                            'user_name' => $val2['user_name'],
                            'name' => $val2['name'],
                        ],1);
					}
				}elseif($key == "private"){
					foreach($val as $key2=>$val2){
                        sdata_update_filtered($db_alias."_USERS",[
                            'user_id' => $key2
                        ],[
                            'private_active' => 1,
                            'user_name' => $val2['user_name'],
                            'name' => $val2['name'],
                        ],1);
					}
				}					
			}
		}
		$updateuser = [];
	}
    
    saveData("update_interval_def_$bot",$update_interval_def);
    saveData("updateuser_$bot",$updateuser);
    saveData("debugmode_$bot",$debugmode);
    saveData("gamedata_$bot",$gamedata);
    saveData("impersonate_$bot",$impersonate);
    saveData("activeusers_$bot",$activeusers);
    saveData("blocked_$bot",$blocked);
    saveData("updid_$bot",$update_id);
    saveData("admin_mentions_$bot",$admin_mentions);
}

