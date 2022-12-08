<?php

if(!function_exists('str_contains')){
    function str_contains($haystack, $needle){
        return (strpos($haystack, $needle) !== false);
    }
}

function isDiawali($string, $diawali, $caseSensitive = true){
	if(!$caseSensitive){
		$string = strtolower($string);
		$diawali = strtolower($diawali);
	}
	if(substr($string,0,strlen($diawali)) === $diawali){
		return true;
	}
	else{
		return false;
	}
}

function isDiakhiri($string, $diakhiri, $caseSensitive = true){
	if(!$caseSensitive){
		$string = strtolower($string);
		$diakhiri = strtolower($diakhiri);
	}
	if(substr($string,-strlen($diakhiri)) === $diakhiri){
		return true;
	}
	else{
		return false;
	}
}