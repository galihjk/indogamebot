<?php

$sdata_global = [];

include_once("helpers/hlp_str.php");

function sdata_insert($table, $sdata_insert){
    $folder = "";
    $checkfields = array_keys($sdata_insert);
    foreach($checkfields as $field){
        if($field == "id"){
            echo "Field 'id' is not allowed";
            return false;
        }
        $folder = "sdata/$table/$field/";
        if(!file_exists($folder)){
            mkdir($folder, 0777, true);
        }
    }
    $id = 1;
    $last_id_file = "sdata/$table/last.id";
    if(file_exists($last_id_file)){
        $last_id = file_get_contents($last_id_file);
        $id = $last_id+1;
    }
    foreach($sdata_insert as $field=>$val){
        file_put_contents("sdata/$table/$field/$id",$val);
    }
    file_put_contents("sdata/$table/last.id",$id);
    return $id;
}

function sdata_get_one($table, $id, $fields = [], $refresh = false){
    global $sdata_global;
    $sdata_got = ['id'=>$id];
    if(!$refresh){
        if(empty($sdata_global[$table][$id])){
            $refresh = true;
        }
    }
    if(!$refresh){
        if(!empty($fields)){
            foreach($fields as $field){
                if(empty($sdata_global[$table][$id][$field])){
                    $refresh = true;
                    break;
                }
            }
        }
    }
    if(!$refresh){
        if(empty($fields)){
            return $sdata_global[$table][$id];
        }
        else{
            foreach($fields as $field){
                $sdata_got[$field] = $sdata_global[$table][$id][$field];
            }
            return $sdata_got;
        }
    }
    if($refresh){
        if(empty($fields)){
            $scandir = scandir("sdata/$table/");
            foreach($scandir as $item){
                if(str_contains($item, '.')) continue;
                $fields[] = $item;
            }
        }
        foreach($fields as $field){
            if($field == 'id') continue;
            $value = "";
            $value_file = "sdata/$table/$field/$id";
            if(file_exists($value_file)){
                $value = file_get_contents($value_file);
            }
            $sdata_got[$field] = $value;
        }
        $sdata_global[$table][$id] = $sdata_got;
        return $sdata_got;
    }
    die("eh?");
}

function sdata_update($table, $id, $sdata_update){
    $checkfields = array_keys($sdata_update);
    foreach($checkfields as $field){
        $folder = "sdata/$table/$field/";
        if(!file_exists($folder)){
            mkdir($folder, 0777, true);
        }
    }
    $result = [];
    foreach($sdata_update as $field=>$val){
        file_put_contents("sdata/$table/$field/$id",$val);
        $result["sdata/$table/$field/$id"] = true;
    }
    return $result;
}

function sdata_update_filtered($table, $filter, $sdata_update, $limit){
    $ids = sdata_get_filtered_ids($table, $filter, $limit, "exact");
    $result = [];
    foreach($ids as $id){
        $result[] = sdata_update($table, $id, $sdata_update);
    }
    return $result;
}

function sdata_find($table, $filter, $limit, $return_fields = [], $debug = false){
    if(!file_exists("sdata/$table/$find_field")){
        return [];
    }
    if(empty($return_fields)){
        $scandir = scandir("sdata/$table/");
        foreach($scandir as $item){
            if(str_contains($item, '.')) continue;
            $return_fields[] = $item;
        }
    }
    $ids = sdata_get_filtered_ids($table, $filter, $limit, $debug);
    $sdata_got = [];
    foreach($ids as $id){
        $sdata_got[$id] = sdata_get_one($table, $id, $return_fields);
    }
    return $sdata_got;
}

function sdata_get_filtered_ids($table, $filter, $limit, $debug = false){
    $ids = [];
    $filtercheck = $filter;
    $currentcount = 0;
    $current_id_check = 0;
    sdata_filtercheck($table, $ids, $filtercheck, $current_id_check, $currentcount, $limit, $debug);
    return $ids;
}

function sdata_filtercheck($table, &$ids, &$filtercheck, &$current_id_check, &$currentcount, $limit, $debug = false){
    // echo "<pre>";
    // print_r($ids);
    // print_r($filtercheck);
    // echo "</pre>";
    // echo "table$table, current_id_check$current_id_check, currentcount$currentcount, limit$limit, type$type";
    if(!empty($filtercheck)){
        $find_field = "";
        $find_val = "";
        foreach($filtercheck as $k=>$v){
            if($k == 'id'){
                header("HTTP/1.1 500 Internal Server Error");
                die("ERROR filter check mengandung 'id'.".print_r($filtercheck,true));
            }
            $type = "exact";
            $find_field = $k;
            if(is_array($v)){
                $find_val = $v[0];
                $type = $v[1];
            }
            else{
                $find_val = $v;
            }
            break;
        }
        // echo "<p>find_field$find_field find_val$find_val</p>";
        if(!empty($find_field)){
            unset($filtercheck[$find_field]);

            if(!empty($current_id_check)){
                if(!file_exists("sdata/$table/$find_field/$current_id_check")) return false;
                $currentval = file_get_contents("sdata/$table/$find_field/$current_id_check");
                // echo "<p>currentval$currentval</p>";
                if(str_compare($find_val, $currentval, $type)){
                    if($debug) file_put_contents("msgcmdlog/".date("YmdHi").rand(0,999)."x.txt",print_r([$currentval, $find_val, $type],true));
                    return sdata_filtercheck($table, $ids, $filtercheck, $current_id_check, $currentcount, $limit);
                    // $ids[] = $item;
                }
                else{
                    if($debug) file_put_contents("msgcmdlog/XX".date("YmdHi").rand(0,999)."XX.txt",print_r([$currentval, $find_val, $type],true));
                    return false;
                }
            }
            else{
                $scandir = scandir("sdata/$table/$find_field/");
                foreach($scandir as $item){
                    if(str_contains($item, '.')) continue;
                    $currentval = file_get_contents("sdata/$table/$find_field/$item");
                    // echo "<p>currentval$currentval</p>";
                    $id_get = "";
                    if(str_compare($find_val, $currentval, $type)){
                        if($debug) file_put_contents("msgcmdlog/".date("YmdHi").rand(0,999).".txt",print_r([$currentval, $find_val, $type],true));
                        $id_get = sdata_filtercheck($table, $ids, $filtercheck, $item, $currentcount, $limit);
                        // $ids[] = $item;
                    }
                    else{
                        if($debug) file_put_contents("msgcmdlog/XXX".date("YmdHi").rand(0,999).".txt",print_r([$currentval, $find_val, $type],true));
                    }
                    // echo "<p>id_get$id_get</p>";
                    if(!empty($id_get)){
                        $ids[] = $id_get;
                        $currentcount++;
                        if($currentcount >= $limit){
                            return false;
                        }
                    }
                }
            }
        }
        else{
            return $current_id_check;
        }
    }
    else{
        return $current_id_check;
    }
}

function sdata_find_one($table, $filter, $return_fields = [], $debug = false){
    $data = sdata_find($table, $filter, 1, $return_fields, $debug);
    $sdata_got = [];
    if(!empty($data)){
        foreach($data as $item){
            $sdata_got = $item;
        }
    }
    return $sdata_got;
}

function sdata_count($table, $field){
    $folder = "sdata/$table/$field/";
    if(!file_exists($folder)) return 0;
    $scandir = scandir($folder);
    if(empty($scandir)){
        return 0;
    }
    else{
        return count($scandir)-2;
    }
}

function sdata_delete($table, $id){
    $fields = [];
    $scandir = scandir("sdata/$table/");
    foreach($scandir as $item){
        if(str_contains($item, '.')) continue;
        $fields[] = $item;
    }
    foreach($fields as $field){
        $folder = "sdata_deleted/$table/$field/";
        if(!file_exists($folder)){
            mkdir($folder, 0777, true);
        }
        rename("sdata/$table/$field/$id", "$folder$id");
    }
    return true;
}

function sdata_delete_filtered($table, $filter, $limit){
    $ids = sdata_get_filtered_ids($table, $filter, $limit);
    $result = [];
    foreach($ids as $id){
        $result[] = sdata_delete($table, $id);
    }
    return $result;
}