<?php

include_once 'includes/project.php';
include_once 'includes/config.php';

$index = 'image_id';
$imageData = $mysql->getData('images', array('image_id' => (int)$_GET[$index]));


if($imageData){
	header("content-type: image/$imageData[type]");
	if (isset($_GET['w']) && isset($_GET['h'])) {
		if(isset($_GET['type']) && $_GET['type'] == 'crop'){
			echo file_get_contents(getResizeImageUrl($imageData, $index, $_GET['w'], $_GET['h'], $_GET['type']));
		} else {
			echo file_get_contents(getResizeImageUrl($imageData, $index, $_GET['w'], $_GET['h']));
		}
	} else {
		echo file_get_contents(getImageUrl($imageData, $index));
	}
}

?>