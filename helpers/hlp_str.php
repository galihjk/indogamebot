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

function str_compare($string_haystack, $string_needle, $type){
	if($string_needle == "~is_not_null") return (strlen((string) $string_haystack) !== 0);
	if($type == "exact") return ($string_haystack == $string_needle);
	if($type == "insensitive") return (strtolower($string_haystack) == strtolower($string_needle));
	if($type == "contains_sensitive") return str_contains($string_haystack, $string_needle);
	if($type == "contains_insensitive") return str_contains(strtolower($string_haystack), strtolower($string_needle));
	if($type == "first_sensitive") return isDiawali($string_haystack, $string_needle, true);
	if($type == "first_insensitive") return isDiawali($string_haystack, $string_needle, false);
	if($type == "last_sensitive") return isDiakhiri($string_haystack, $string_needle, true);
	if($type == "last_insensitive") return isDiakhiri($string_haystack, $string_needle, false);
	echo "Type ERROR: '$type'";
	return false;
}