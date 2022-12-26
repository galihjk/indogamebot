<?php 
//jika merupakan sticker
if(isset($message_data['reply_to_message']['sticker'])){
	$isinya = "*sticker " . $message_data['reply_to_message']['sticker']['emoji'] . " |" . $message_data['reply_to_message']['sticker']['file_id'];
}
//jika merupakan photo
if(isset($message_data['reply_to_message']['photo'])){
	$isinya = "*photo " . $message_data['reply_to_message']['caption'] . " |" . $message_data['reply_to_message']['photo'][0]['file_id'];
}
//jika document
if(isset($message_data['reply_to_message']['document'])){
	$isinya = "*document " . $message_data['reply_to_message']['document']['file_name'] . " |" . $message_data['reply_to_message']['document']['file_id'];
}	

//jika voice
if(isset($message_data['reply_to_message']['voice'])){
	$isinya = "*voice |". $message_data['reply_to_message']['voice']['file_id'];
}	

//jika location
if(isset($message_data['reply_to_message']['location'])){
	$isinya = "*location |". $message_data['reply_to_message']['location']['longitude'] . "," . $message_data['reply_to_message']['location']['latitude'];
}