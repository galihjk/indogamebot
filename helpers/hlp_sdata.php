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
    $ids = sdata_get_filtered_ids($table, $filter, $limit);
    $result = [];
    foreach($ids as $id){
        $result[] = sdata_update($table, $id, $sdata_update);
    }
    return $result;
}

function sdata_find($table, $filter, $limit, $return_fields = [], $order = [], $debug = false){
    if(!file_exists("sdata/$table/")){
        return [];
    }
    if(empty($return_fields)){
        $scandir = scandir("sdata/$table/");
        foreach($scandir as $item){
            if(str_contains($item, '.')) continue;
            $return_fields[] = $item;
        }
    }
    
    $ordered_ids = [];
    if(!empty($order)){
        foreach($order as $field=>$ordertype){
            $sortdata = [];
            if(empty($ordered_ids)){
                $scandir = scandir("sdata/$table/$field");
                foreach($scandir as $item){
                    if(str_contains($item, '.')) continue;
                    $ordered_ids[] = $item;
                }
            }
            foreach($ordered_ids as $id){
                $val = sdata_get_one($table, $id, [$field])[$field];
                if(!empty($filter[$field])){
                    $type = "exact";
                    $find_field = $field;
                    if(is_array($filter[$field])){
                        $find_val = $filter[$field][0];
                        $type = $filter[$field][1];
                    }
                    else{
                        $find_val = $filter[$field];
                    }
                    if(!str_compare($val, $find_val, $type)) continue;
                    if($debug){
                        echo "<p>";
                        print_r([$val, $find_val, $type, str_compare($val, $find_val, $type)]);
                        echo "</p>";
                    }
                }
                $sortdata[$id] = (is_numeric($val) ? (int) $val : strtolower($val));
            }
            if($debug){
                echo "<p>";
                print_r($sortdata);
                echo "</p>";
            }
            if($ordertype == "asc") asort($sortdata);
            if($ordertype == "desc") arsort($sortdata);
            $ordered_ids = array_keys($sortdata);
            if($debug){
                echo "<p> $ordertype ";
                print_r($sortdata);
                echo "</p>";
                echo "<p>ordered_ids=";
                print_r($ordered_ids);
                echo "</p>";
            }
            if($debug){
                echo "<p> limit$limit ";
                echo " ordered_ids=";
                print_r($ordered_ids);
                echo "</p>";
            }
        }
    }

    $ids = sdata_get_filtered_ids($table, $filter, $limit, $ordered_ids, $debug);
    // if(!empty($order)){
    //     foreach($order as $field=>$ordertype){
    //         sdata_sort($ids, $table, $field, $ordertype);
    //     }
    // }
    $sdata_got = [];
    foreach($ids as $id){
        $sdata_got[$id] = sdata_get_one($table, $id, $return_fields);
    }
    return $sdata_got;
}

function sdata_sort(&$ids, $table, $field, $ordertype){
    $sortdata = [];
    foreach($ids as $id){
        $val = sdata_get_one($table, $id, [$field]);
        $sortdata[$id] = (is_numeric($val[$field]) ? (int) $val[$field] : strtolower($val[$field]));
    }
    if($ordertype == "asc") asort($sortdata);
    if($ordertype == "desc") arsort($sortdata);
    $ids = array_keys($sortdata);
}

function sdata_get_filtered_ids($table, $filter, $limit, $ordered_ids = [], $debug = false){
    $ids = [];
    $filtercheck = $filter;
    $currentcount = 0;
    $current_id_check = 0;
    sdata_filtercheck($table, $ids, $filtercheck, $current_id_check, $currentcount, $limit, $ordered_ids, $debug);
    return $ids;
}

function sdata_filtercheck($table, &$ids, $filtercheck, &$current_id_check, &$currentcount, $limit, $ordered_ids = [], $debug = false){
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
        if($debug) file_put_contents("msgcmdlog/".date("YmdHis").".txt","find_field=[$find_field] find_val=[$find_val] type=[$type]\n\n", FILE_APPEND | LOCK_EX);
        if(!empty($find_field)){
            unset($filtercheck[$find_field]);

            if(!empty($current_id_check)){
                if(!file_exists("sdata/$table/$find_field/$current_id_check")) return false;
                // $currentval = file_get_contents("sdata/$table/$find_field/$current_id_check");
                $currentval = sdata_get_one($table, $current_id_check, [$find_field])[$find_field];
                // echo "<p>currentval$currentval</p>";
                if(str_compare($currentval, $find_val, $type)){
                    if($debug) file_put_contents("msgcmdlog/".date("YmdHis").".txt","TURE1\n".print_r([$currentval, $find_val, $type],true)."\n\n", FILE_APPEND | LOCK_EX);
                    return sdata_filtercheck($table, $ids, $filtercheck, $current_id_check, $currentcount, $limit, $ordered_ids, $debug);
                    // $ids[] = $item;
                }
                else{
                    if($debug) file_put_contents("msgcmdlog/".date("YmdHis").".txt","FALSE1\n".print_r([$currentval, $find_val, $type],true)."\n\n", FILE_APPEND | LOCK_EX);
                    return false;
                }
            }
            else{
                $chekids = $ordered_ids;
                if(empty($chekids)){
                    $scandir = scandir("sdata/$table/$find_field/");
                    foreach($scandir as $item){
                        if(str_contains($item, '.')) continue;
                        $chekids[] = $item;
                    }
                }
                if($debug) file_put_contents("msgcmdlog/".date("YmdHis").".txt","chekids=[".print_r($chekids,true)."], find_field=[$find_field] find_val=[$find_val] type=[$type]\n\n", FILE_APPEND | LOCK_EX);
                foreach($chekids as $item){
                    // $currentval = file_get_contents("sdata/$table/$find_field/$item");
                    $currentval = sdata_get_one($table, $item, [$find_field])[$find_field];
                    // echo "<p>currentval$currentval</p>";
                    $id_get = "";
                    if(str_compare($currentval, $find_val, $type)){
                        if($debug) file_put_contents("msgcmdlog/".date("YmdHis").".txt","TURE2\n".print_r([$currentval, $find_val, $type],true)."\n\n", FILE_APPEND | LOCK_EX);
                        $id_get = sdata_filtercheck($table, $ids, $filtercheck, $item, $currentcount, $limit, $ordered_ids, $debug);
                        // $ids[] = $item;
                    }
                    else{
                        if($debug) file_put_contents("msgcmdlog/".date("YmdHis").".txt","FALSE2\n".print_r([$currentval, $find_val, $type],true)."\n\n", FILE_APPEND | LOCK_EX);
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
    $data = sdata_find($table, $filter, 1, $return_fields, [], $debug);
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