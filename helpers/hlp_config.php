<?php 
function getConfig($config_name, $fail = []){
    $configfile = "config/$config_name".".php";
    if(file_exists($configfile)){
        return include($configfile);
    }
    else{
        return $fail;
    }
}