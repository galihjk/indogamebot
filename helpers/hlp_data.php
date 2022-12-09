<?php

function saveData($name, $data){
    $filename="data/$name.json";
	$dir = (pathinfo($filename)['dirname'] ?? '');
	if(!is_dir($dir)){
		mkdir($dir, 0777, true);
	}
	return file_put_contents($filename, json_encode($data)); 
}

function loadData($name, $empty = []){
    $filename="data/$name.json";
	if(file_exists($filename)){
		$filedata = file_get_contents($filename);
		$data = json_decode($filedata,true);
		if($data === false){
			$data = $empty;
		}
	}
	else{
		$data = $empty;
	}
	return $data;
}

function deleteData($name){
	$filename="data/$name.json";
	unlink($filename);
}