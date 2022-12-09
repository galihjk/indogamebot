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
    foreach($sdata_update as $field=>$val){
        file_put_contents("sdata/$table/$field/$id",$val);
    }
}


function sdata_find($table, $filter, $limit, $return_fields = [], $type = "exact"){
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
    $ids = [];
    $filtercheck = $filter;
    $currentcount = 0;
    $current_id_check = 0;
    sdata_filtercheck($table, $ids, $filtercheck, $current_id_check, $currentcount, $limit, $type);
    $sdata_got = [];
    foreach($ids as $id){
        $sdata_got[$id] = sdata_get_one($table, $id, $return_fields);
    }
    return $sdata_got;
}

function sdata_filtercheck($table, &$ids, &$filtercheck, &$current_id_check, &$currentcount, $limit, $type){
    // echo "<pre>";
    // print_r($ids);
    // print_r($filtercheck);
    // echo "</pre>";
    // echo "table$table, current_id_check$current_id_check, currentcount$currentcount, limit$limit, type$type";
    if(!empty($filtercheck)){
        $find_field = "";
        $find_val = "";
        foreach($filtercheck as $k=>$v){
            $find_field = $k;
            $find_val = $v;
            break;
        }
        // echo "<p>find_field$find_field find_val$find_val</p>";
        if(!empty($find_field)){
            unset($filtercheck[$find_field]);

            if(!empty($current_id_check)){
                if(!file_exists("sdata/$table/$find_field/$current_id_check")) return false;
                $currentval = file_get_contents("sdata/$table/$find_field/$current_id_check");
                // echo "<p>currentval$currentval</p>";
                if(str_compare($currentval, $find_val, $type)){
                    return sdata_filtercheck($table, $ids, $filtercheck, $current_id_check, $currentcount, $limit, $type);
                    // $ids[] = $item;
                }
                else{
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
                    if(str_compare($currentval, $find_val, $type)){
                        $id_get = sdata_filtercheck($table, $ids, $filtercheck, $item, $currentcount, $limit, $type);
                        // $ids[] = $item;
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

function sdata_find_one($table, $filter, $return_fields = [], $type = "exact"){
    $data = sdata_find($table, $filter, 1, $return_fields, $type);
    $sdata_got = [];
    if(!empty($data)){
        foreach($data as $item){
            $sdata_got = $item;
        }
    }
    return $sdata_got;
}