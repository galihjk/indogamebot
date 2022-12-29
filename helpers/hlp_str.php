<?php

if(!function_exists('str_contains')){
    function str_contains($haystack, $needle){
		if((string)$needle === ""){
			return true;
		}
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
	if((string)$string_haystack === "~is_not_null") return (strlen((string) $string_needle) !== 0);
	if((string)$string_needle === "~is_not_null") return (strlen((string) $string_haystack) !== 0);
	// if(file_exists("msgcmdlog/".date("YmdHis").".txt")){
	// 	$resultdebug = "string_haystack=[$string_haystack], string_needle=[$string_needle], type=[$type]\n";
	// 	$resultdebug .= "if($type == \"exact\" and $string_haystack != \"~is_not_null\" and $string_needle != \"~is_not_null\")==>";
	// 	$resultdebug .= (($type == "exact" and $string_haystack != "~is_not_null" and $string_needle != "~is_not_null") ? "TRUE" : "FALSE")."\n";
	// 	$resultdebug .= "(string) $string_haystack === (string) $string_needle ";
	// 	if((string) $string_haystack === (string) $string_needle){
	// 		$resultdebug .= "==>>TURE\n\n";
	// 	}
	// 	else{
	// 		$resultdebug .= "==>>FALSE\n\n";
	// 	}
	// 	file_put_contents("msgcmdlog/".date("YmdHis").".txt",
	// 	$resultdebug
	// 	, FILE_APPEND | LOCK_EX);
	// }
	if($type == "exact"){
		// if(file_exists("msgcmdlog/".date("YmdHis").".txt")){
		// 	file_put_contents("msgcmdlog/".date("YmdHis").".txt","EXACT!", FILE_APPEND | LOCK_EX);
		// }
		return ((string) $string_haystack === (string) $string_needle);
	} 
	if($type == "*") return true;
	if($type == "insensitive") return (strtolower($string_haystack) == strtolower($string_needle));
	if($type == "contains_sensitive") return str_contains($string_haystack, $string_needle);
	if($type == "r_contains_sensitive") return str_contains($string_needle, $string_haystack);
	if($type == "contains_insensitive") return str_contains(strtolower($string_haystack), strtolower($string_needle));
	if($type == "r_contains_insensitive") return str_contains(strtolower($string_needle), strtolower($string_haystack));
	if($type == "first_sensitive") return isDiawali($string_haystack, $string_needle, true);
	if($type == "r_first_sensitive") return isDiawali($string_needle, $string_haystack, true);
	if($type == "first_insensitive") return isDiawali($string_haystack, $string_needle, false);
	if($type == "r_first_insensitive") return isDiawali($string_needle, $string_haystack, false);
	if($type == "last_sensitive") return isDiakhiri($string_haystack, $string_needle, true);
	if($type == "r_last_sensitive") return isDiakhiri($string_needle, $string_haystack, true);
	if($type == "last_insensitive") return isDiakhiri($string_haystack, $string_needle, false);
	if($type == "r_last_insensitive") return isDiakhiri($string_needle, $string_haystack, false);
	echo "Type ERROR: '<pre>".print_r($type,true)."</pre>'";
	file_put_contents("msgcmdlog/str_compare".date("YmdH").".txt","Type ERROR: '<pre>".print_r([$type,$string_haystack, $string_needle],true)."</pre>'");
	return false;
}