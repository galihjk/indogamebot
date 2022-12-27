<?php 

if($game['started'] == 1){
    if($game['step'] == "setor"){
        $output = "";
        foreach($game['players'] as $playerid=>$player){
            $output .= $player['nama']."\n";
        }
        $output .= "Setor angka untuk $emoji_check <b>LUCKY NUMBER</b>";
        $button = array();
        $button['luck1'] = '1';
        $button['luck2'] = '2';
        $button['luck3'] = '3';
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_markup' => makebutton($button)
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);

        $output = "";
        foreach($game['players'] as $playerid=>$player){
            $output .= $player['nama']."\n";
        }
        $output .= "Setor angka untuk $emoji_skull <b>CURSED NUMBER</b>";
        $button = array();
        $button['cursed1'] = '1';
        $button['cursed2'] = '2';
        $button['cursed3'] = '3';
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_markup' => makebutton($button)
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
        $gamedata['lncn'][$chatid]['step'] = "sedangsetor";
        $gamedata['lncn'][$chatid]['steptime'] = 0;
    }
    elseif($game['step'] == "sedangsetor"){
        $sudah_semua = true;
        foreach($game['players'] as $player){
            if(empty($player['ln_stored']) or empty($player['cn_stored'])){
                $sudah_semua = false;
                break;
            }
        }
        if($sudah_semua){
            $gamedata['lncn'][$chatid]['step'] = "sudahsetor";
            $gamedata['lncn'][$chatid]['steptime'] = 0;
        }
    }
    elseif($game['step'] == "sudahsetor"){
        $total_ln = 0;
        $total_cn = 0;
        foreach($game['players'] as $player){
            $total_ln += $player['ln_stored'];
            $total_cn += $player['cn_stored'];
        }
        if($total_ln == $total_cn){
            $output = "WAW! Total angka untuk LUCKY NUMBER dan CURSED NUMBER yang kalian setorkan SAMA, yaitu $total_cn!\n";
            foreach($game['players'] as $player){
                $output .= $player['nama'].": ".$player['ln_stored']."/".$player['cn_stored']."\n";
            }
            $output .= "\nAyo setor angka lagi!";
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
            $gamedata['lncn'][$chatid]['step'] = "setor";
            $gamedata['lncn'][$chatid]['steptime'] = 0;
        }
        else{
            $gamedata['lncn'][$chatid]['ln'] = $total_ln;
            $gamedata['lncn'][$chatid]['cn'] = $total_cn;
            $gamedata['lncn'][$chatid]['unpicked'] = array();
            for ($x = count($game['players']); $x <= (3*count($game['players'])); $x++) {
                $gamedata['lncn'][$chatid]['unpicked'][$x] = $x;
            }
            $cnt_unpicked = count($gamedata['lncn'][$chatid]['unpicked']);
            $index = 1;
            foreach($gamedata['lncn'][$chatid]['unpicked'] as $key=>$val){
                if($index == 1){
                    if($val != $total_ln and $val != $total_cn){
                        unset($gamedata['lncn'][$chatid]['unpicked'][$key]);
                    }
                }elseif($index == 2){
                    if($val != $total_ln and $val != $total_cn){
                        unset($gamedata['lncn'][$chatid]['unpicked'][$key]);
                    }
                }elseif($index == $cnt_unpicked-1){
                    if($val != $total_ln and $val != $total_cn){
                        unset($gamedata['lncn'][$chatid]['unpicked'][$key]);
                    }
                }elseif($index == $cnt_unpicked){
                    if($val != $total_ln and $val != $total_cn){
                        unset($gamedata['lncn'][$chatid]['unpicked'][$key]);
                    }
                }
                $index++;
            }
            $cnt_unpicked = count($gamedata['lncn'][$chatid]['unpicked']);
            $gamedata['lncn'][$chatid]['numcount'] = $cnt_unpicked;
            $data = array(
                'chat_id' => $chatid,
                'text'=> "LUCKY NUMBER dan CURSED NUMBER telah ditentukan berdasarkan total angka yang disetorkan.",
                'parse_mode'=>'HTML',
                'disable_web_page_preview'=>true
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
            $gamedata['lncn'][$chatid]['step'] = "pilihnumber";
            $gamedata['lncn'][$chatid]['steptime'] = 0;
        }
    }
    elseif($game['step'] == "pilihnumber"){
        $output = "";
        foreach($game['players'] as $playerid=>$player){
            $output .= $player['nama'].":\n\n";
        }
        $sekian = floor($game['numcount']/count($game['players']));
        $output .= "Pilih $sekian angka!";
        $button = array();
        foreach($game['unpicked'] as $val){
            $button["pick$val"] = $val;
        }
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true,
            'reply_markup' => makebutton($button)
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
        
        $gamedata['lncn'][$chatid]['step'] = "sedangpilihnumber";
        $gamedata['lncn'][$chatid]['steptime'] = 0;
    }
    elseif($game['step'] == "sedangpilihnumber"){
        $sudahpilih = false;
        if(!empty($game['lucky']) and !empty($game['cursed'])){
            $sudahpilih = true;
        }
        if(!$sudahpilih){
            $playercount = count($game['players']);
            if(empty($game['lucky']) and empty($game['cursed'])){
                $sekian = floor($game['numcount']/$playercount);
            }
            else{
                $sekian = floor($game['numcount']/($playercount-1));
            }
            $ada_yg_blm = false;
            foreach($game['players'] as $playerid=>$player){
                if($player['got_lc'] != "ln"
                and $player['got_lc'] != "cn"
                and count($player['picked'])<$sekian){
                    $ada_yg_blm = true;
                    break;
                }
            }
            if(!$ada_yg_blm){
                $sudahpilih = true;
            }
        }
        if($sudahpilih){
            $output = "==<b>SELESAI</b>==\n\n";
            if(!empty($game['lucky']) and !empty($game['cursed'])){
                $output .= "<b>" . strip_tags($game['lucky']) . "</b> akan memberikan TRUTH OR DARE kepada <b>" . $game['cursed'] . "</b>, bersiaplah!";
            }elseif(empty($game['lucky']) and !empty($game['cursed'])){
                $output .= "Karena tidak ada yang mendapatkan $emoji_check LUCKY NUMBER (".$game['ln']."), <b>SEMUA</b> pemain akan memberikan TRUTH OR DARE kepada <b>" . $game['cursed'] . "</b>, bersiaplah!";
            }elseif(!empty($game['lucky']) and empty($game['cursed'])){
                $output .= "Karena tidak ada yang mendapatkan $emoji_skull CURSED NUMBER (".$game['cn']."), <b>".strip_tags($game['lucky'])."</b> akan memberikan TRUTH OR DARE kepada <b>SEMUA</b> pemain, bersiaplah!";
            }else{
                $output .= "Hmmm, ada yang aneh..";
            }
            $data = array(
                'chat_id' => $chatid,
                'text'=> $output,
                'parse_mode'=>'HTML',
                'reply_markup' => $forcereply
                );
            $hasil = KirimPerintahX($token,'sendMessage',$data);
            $gamedata['lncn'][$chatid]['step'] = "maukasihdare";
            $gamedata['lncn'][$chatid]['steptime'] = 0;
        }
        
    }
    
    elseif($game['step'] == "maukasihdare" and $game['steptime'] >= 5){
        if(!empty($game['lucky']) and !empty($game['cursed'])){
            $output = $game['lucky'] . ", ayo berikan TRUTH OR DARE kepada " . $game['cursed'] . "!\n(<b>WAJIB</b> balas di sini)";
            $darer = "";
            $dared = "";
            foreach($game['players'] as $playerid=>$player){
                if($player['got_lc'] == "ln"){
                    $darer = $playerid;
                }
                elseif($player['got_lc'] == "cn"){
                    $dared = $playerid;
                }
                if(!empty($darer) and !empty($dared)){
                    break;
                }
            }
            $gamedata['lncn'][$chatid]['darer'][$darer] = $darer;
            $gamedata['lncn'][$chatid]['dared'][$dared] = $dared;
        }elseif(empty($game['lucky']) and !empty($game['cursed'])){
            $output = "";
            foreach($game['players'] as $playerid=>$player){
                if($player['got_lc'] != "cn"){
                    $output .= $player['nama'] . "\n";
                    $gamedata['lncn'][$chatid]['darer'][$playerid] = $playerid;
                }
                elseif($player['got_lc'] == "cn"){
                    $gamedata['lncn'][$chatid]['dared'][$playerid] = $playerid;
                }
            }
            $output .= "ayo berikan TRUTH OR DARE kepada " . $game['cursed'] . "!\n(<b>WAJIB</b> balas di sini)";
        }elseif(!empty($game['lucky']) and empty($game['cursed'])){
            $output = $game['lucky'] . ", ayo berikan TRUTH OR DARE kepada semua pemain lainnya!\n(<b>WAJIB</b> balas di sini)";
            foreach($game['players'] as $playerid=>$player){
                if($player['got_lc'] == "ln"){
                    $gamedata['lncn'][$chatid]['darer'][$playerid] = $playerid;
                }
                elseif($player['got_lc'] != "ln"){
                    $gamedata['lncn'][$chatid]['dared'][$playerid] = $playerid;
                }
            }
        }else{
            $output = "Tidak ada TRUTH OR DARE kali ini..";
        }
        $data = array(
            'chat_id' => $chatid,
            'text'=> $output,
            'parse_mode'=>'HTML',
            // 'reply_markup' => $forcereply
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
        $gamedata['lncn'][$chatid]['step'] = "sedangkasihdare";
        $gamedata['lncn'][$chatid]['steptime'] = 0;
    }
    elseif($game['step'] == "selesai"){
        $data = array(
            'chat_id' => $chatid,
            'text'=> "Permainan berakhir, ayo laksanakan TRUTH OR DARE nya, abis itu /join_lncn lagi..",
            'parse_mode'=>'HTML',
            'disable_web_page_preview'=>true
            );
        $hasil = KirimPerintahX($token,'sendMessage',$data);
        $gamedata['lncn'][$chatid]['step'] = "selesai";
        $gamedata['lncn'][$chatid]['steptime'] = 0;
        $gamedata['lncn'][$chatid]['started'] = 0;
        $gamedata['lncn'][$chatid]['starting_time'] = 0;
        $gamedata['lncn'][$chatid]['players'] = array();
    }
    $gamedata['lncn'][$chatid]['steptime']+=$jeda;
}
elseif(!empty($gamedata['lncn'][$chatid]['players'])){
    $gamedata['lncn'][$chatid]['starting_time']+=$jeda;
}
