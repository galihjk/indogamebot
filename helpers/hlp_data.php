<?php

$data_global = [];

include_once("helpers/hlp_str.php");

function data_insert($table, $data_insert){
    $folder = "";
    $checkcols = array_keys($data_insert);
    foreach($checkcols as $field){
        $folder = "data/$table/$field/";
        if(!file_exists($folder)){
            mkdir($folder, 0777, true);
        }
    }
    $id = 1;
    $last_id_file = "data/$table/last.id";
    if(file_exists($last_id_file)){
        $last_id = file_get_contents($last_id_file);
        $id = $last_id+1;
    }
    foreach($data_insert as $field=>$val){
        file_put_contents("data/$table/$field/$id",$val);
    }
    file_put_contents("data/$table/last.id",$id);
    return $id;
}

function data_get_one($table, $id, $fields = [], $refresh = false){
    global $data_global;
    if(!$refresh){
        if(empty($data_global[$table][$id])){
            $refresh = true;
        }
    }
    if(!$refresh){
        if(!empty($fields)){
            foreach($fields as $field){
                if(empty($data_global[$table][$id][$field])){
                    $refresh = true;
                    break;
                }
            }
        }
    }
    if(!$refresh){
        if(empty($fields)){
            return $data_global[$table][$id];
        }
        else{
            $data_got = [];
            foreach($fields as $field){
                $data_got[$field] = $data_global[$table][$id][$field];
            }
            return $data_got;
        }
    }
    if($refresh){
        $data_got = [];
        if(empty($fields)){
            $scandir = scandir("data/$table/");
            foreach($scandir as $item){
                if(str_contains($item, '.')) continue;
                $fields[] = $item;
            }
        }
        foreach($fields as $field){
            $value = "";
            $value_file = "data/$table/$field/$id";
            if(file_exists($value_file)){
                $value = file_get_contents($value_file);
            }
            $data_got[$field] = $value;
        }
        $data_global[$table][$id] = $data_got;
        return $data_got;
    }
    die("eh?");
}

function data_update($table, $id, $data_update){
    $checkcols = array_keys($data_update);
    foreach($checkcols as $field){
        $folder = "data/$table/$field/";
        if(!file_exists($folder)){
            mkdir($folder, 0777, true);
        }
    }
    foreach($data_update as $field=>$val){
        file_put_contents("data/$table/$field/$id",$val);
    }
}


function data_find($table, $filter, $limit, $return_fields = [], $type = "exact"){
    if(!file_exists("data/$table/$find_field")){
        return [];
    }
    if(empty($return_fields)){
        $scandir = scandir("data/$table/");
        foreach($scandir as $item){
            if(str_contains($item, '.')) continue;
            $return_fields[] = $item;
        }
    }
    $ids = [];
    $filtercheck = $filter;
    $currentcount = 0;
    $current_id_check = 0;
    data_filtercheck($table, $ids, $filtercheck, $current_id_check, $currentcount, $limit, $type);

    // foreach($filter as $find_field=>$find_val){
    //     $scandir = scandir("data/$table/$find_field/");
        
    //     foreach($scandir as $item){
    //         if(str_contains($item, '.')) continue;
    //         $currentval = file_get_contents("data/$table/$find_field/$item");
    //         if($type == "exact"){
    //             if($currentval == $find_val){
    //                 $ids[] = $item;
    //             }
    //         }
    //         elseif($type == "insensitive"){
    //             if(strtolower($currentval) == strtolower($find_val)){
    //                 $ids[] = $item;
    //             }
    //         }
    //         elseif($type == "contains_sensitive"){
    //             if(str_contains($currentval,$find_val)){
    //                 $ids[] = $item;
    //             }
    //         }
    //         elseif($type == "contains_insensitive"){
    //             if(str_contains(strtolower($currentval),strtolower($find_val))){
    //                 $ids[] = $item;
    //             }
    //         }
    //         elseif($type == "first_sensitive"){
    //             if(isDiawali($currentval, $find_val, true)){
    //                 $ids[] = $item;
    //             }
    //         }
    //         elseif($type == "first_insensitive"){
    //             if(isDiawali($currentval, $find_val, false)){
    //                 $ids[] = $item;
    //             }
    //         }
    //         elseif($type == "first_sensitive"){
    //             if(isDiakhiri($currentval, $find_val, true)){
    //                 $ids[] = $item;
    //             }
    //         }
    //         elseif($type == "first_insensitive"){
    //             if(isDiakhiri($currentval, $find_val, false)){
    //                 $ids[] = $item;
    //             }
    //         }
    //         else{
    //             echo "invalid type [$type]";
    //             return [];
    //         }
    //         if($currentcount >= $limit){
    //             break;
    //         }
    //         else{
    //             $currentcount++;
    //         }
    //     }
    // }
    $data_got = [];
    foreach($ids as $id){
        $data_got[$id] = data_get_one($table, $id, $return_fields);
    }
    return $data_got;
}

function data_filtercheck($table, &$ids, &$filtercheck, &$current_id_check, &$currentcount, $limit, $type){
    echo "<pre>";
    print_r($ids);
    print_r($filtercheck);
    echo "</pre>";
    echo "table$table, current_id_check$current_id_check, currentcount$currentcount, limit$limit, type$type";
    if(!empty($filtercheck)){
        $find_field = "";
        $find_val = "";
        foreach($filtercheck as $k=>$v){
            $find_field = $k;
            $find_val = $v;
            break;
        }
        echo "<p>find_field$find_field find_val$find_val</p>";
        if(!empty($find_field)){
            unset($filtercheck[$find_field]);

            if(!empty($current_id_check)){
                if(!file_exists("data/$table/$find_field/$current_id_check")) return false;
                $currentval = file_get_contents("data/$table/$find_field/$current_id_check");
                echo "<p>currentval$currentval</p>";
                if($type == "exact"){
                    if($currentval == $find_val){
                        return data_filtercheck($table, $ids, $filtercheck, $current_id_check, $currentcount, $limit, $type);
                        // $ids[] = $item;
                    }
                    else{
                        return false;
                    }
                }
            }
            else{
                $scandir = scandir("data/$table/$find_field/");
                foreach($scandir as $item){
                    if(str_contains($item, '.')) continue;
                    $currentval = file_get_contents("data/$table/$find_field/$item");
                    echo "<p>currentval$currentval</p>";
                    $id_get = "";
                    if($type == "exact"){
                        if($currentval == $find_val){
                            $id_get = data_filtercheck($table, $ids, $filtercheck, $item, $currentcount, $limit, $type);
                            // $ids[] = $item;
                        }
                    }
                    echo "<p>id_get$id_get</p>";
                    /*
                        elseif($type == "insensitive"){
                            if(strtolower($currentval) == strtolower($find_val)){
                                $ids[] = $item;
                            }
                        }
                        elseif($type == "contains_sensitive"){
                            if(str_contains($currentval,$find_val)){
                                $ids[] = $item;
                            }
                        }
                        elseif($type == "contains_insensitive"){
                            if(str_contains(strtolower($currentval),strtolower($find_val))){
                                $ids[] = $item;
                            }
                        }
                        elseif($type == "first_sensitive"){
                            if(isDiawali($currentval, $find_val, true)){
                                $ids[] = $item;
                            }
                        }
                        elseif($type == "first_insensitive"){
                            if(isDiawali($currentval, $find_val, false)){
                                $ids[] = $item;
                            }
                        }
                        elseif($type == "first_sensitive"){
                            if(isDiakhiri($currentval, $find_val, true)){
                                $ids[] = $item;
                            }
                        }
                        elseif($type == "first_insensitive"){
                            if(isDiakhiri($currentval, $find_val, false)){
                                $ids[] = $item;
                            }
                        }
                    */
                    // else{
                    //     echo "invalid type [$type]";
                    //     return false;
                    // }
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