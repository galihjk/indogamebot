<?php 

//jika merupakan sticker
if(isset($message_data['sticker'])){
	$textnya = "*sticker " . $message_data['sticker']['emoji'] . " |" . $message_data['sticker']['file_id'];
}
//jika merupakan photo
if(isset($message_data['photo'])){
	$textnya = "*photo " . $message_data['caption'] . " |" . $message_data['photo'][0]['file_id'];
}
//jika document
if(isset($message_data['document'])){
	$textnya = "*document " . $message_data['document']['file_name'] . " |" . $message_data['document']['file_id'];
}	

//jika voice
if(isset($message_data['voice'])){
	$textnya = "*voice |". $message_data['voice']['file_id'];
}	

//jika location
if(isset($message_data['location'])){
	$textnya = "*location |". $message_data['location']['longitude'] . "," . $message_data['location']['latitude'];
}	