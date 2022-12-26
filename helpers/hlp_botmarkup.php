<?php
function makebutton($array,$maxcol = 5){
	$arrayrow = array();
	$arraycol = array();
	$curcol = 1;
	foreach($array as $key=>$val){
		$button = array();
		$button['text'] = $val;
		$button['callback_data'] = $key;
		$arraycol[] = $button;
		$curcol++;
		if($curcol>$maxcol){
			$arrayrow[] = $arraycol;
			$arraycol = array();
			$curcol = 1;
		}
	}
	if(!empty($arraycol)){
		$arrayrow[] = $arraycol;
	}
	$keyboard['inline_keyboard'] = $arrayrow;
	$encodedKeyboard = json_encode($keyboard);
	return $encodedKeyboard;
}

function addUrlButton($url, $text, $encodedKeyboard = ""){
	if(empty($encodedKeyboard)){
		$keyboard['inline_keyboard'] = [];
	}
	else{
		$keyboard = json_decode($encodedKeyboard,1);
	}
	if(strpos($url,":/") === false){
		if(strpos($url,".") === false){
			$url = "http://t.me/$url";
		}
		else{
			$url = "http://$url";
		}
	}
	$arraycol = [
		'text' => $text,
		'url' => $url
	];
	$arrayrow = [
		$arraycol,
	];
	$keyboard['inline_keyboard'][] = $arrayrow;
	$encodedKeyboard = json_encode($keyboard);
	return $encodedKeyboard;
}

function addUrlButtons($buttons, $encodedKeyboard = ""){
	if(empty($encodedKeyboard)){
		$keyboard['inline_keyboard'] = [];
	}
	else{
		$keyboard = json_decode($encodedKeyboard,1);
	}
	if(strpos($url,":/") === false){
		if(strpos($url,".") === false){
			$url = "http://t.me/$url";
		}
		else{
			$url = "http://$url";
		}
	}
	$arraycol = [
		'text' => $text,
		'url' => $url
	];
	$arrayrow = [
		$arraycol,
	];
	$keyboard['inline_keyboard'][] = $arrayrow;
	$encodedKeyboard = json_encode($keyboard);
	return $encodedKeyboard;
}
