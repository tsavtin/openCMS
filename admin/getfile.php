<?php

include_once 'includes/config.php';

function key_replace($data){
    $delete_chars = array('$', '.', ',', '"', '(', ')', '+', '!', '@', '#', '%', '^', '&', '*', '=', '+', '[', ']', '{', '}', '|', '\\', '/', '`', '~', ';', ':', '<', '>', '?', '\'', '“', '”', '’');
    $data = trim($data);
    $data = str_replace('  ', ' ', $data);
    $data = str_replace('  ', ' ', $data);
    $data = str_replace('  ', ' ', $data);
    $data = str_replace('  ', ' ', $data);
    $data = str_replace('  ', ' ', $data);
    $data = str_replace(' ', '_', $data);
    $data = str_replace('-', '_', $data);
    $data = str_replace($delete_chars, '', $data);
    return $data;
}


if($_GET['file_id']){
	$fileData = $mysql->getData($fileTable, array($filePrimarykey => $_GET['file_id']));
	$folder = get_folder($admin_filefolder, $_GET['file_id'], $fileData['date_added']);
	$file_path = "$folder/$fileData[filename_md5].$fileData[type]";
} else if($_GET['video_id']){
	$fileData = $mysql->getData($videoTable, array($videoPrimarykey => $_GET['video_id']));
	$folder = get_folder($admin_videofolder, $_GET['video_id'], $fileData['date_added']);
	$file_path = "$folder/$fileData[filename_md5].$fileData[type]";
} else if($_GET['image_id']){
    $imageData = $mysql->getData($imageTable, array($imagePrimarykey => $_GET['image_id']));
    $folder = get_folder($admin_imagefolder, $_GET['image_id'], $imageData['date_added']);
    $file_path = "$folder/$imageData[filename_md5].$imageData[type]";
}

if (strtolower($fileData['type']) != 'pdf' && !$_GET['image_id'] || 1) {
	header('Content-Encoding: none');
	//header('Content-Length: '.$file_path);
	//header('Cache-control: private, must-revalidate');
	if($_GET['file_name']){
		$_GET['file_name'] = key_replace($_GET['file_name']);
		if(preg_match('/webkit/is', strtolower($_SERVER['HTTP_USER_AGENT']))){
			$encoded_filename = urlencode($_GET['file_name'].".$fileData[type]");
		} else{
			$encoded_filename = $_GET['file_name'].".$fileData[type]";
		}
		header('Content-Disposition: attachment; filename="'.$encoded_filename.'"');
	} else if($fileData['file_org']){
		header('Content-Disposition: attachment; filename="'."$fileData[file_org]".'"');
	} else {
		header('Content-Disposition: attachment; filename="'."$fileData[filename_md5].$fileData[type]".'"');
	}
	

	//header('Content-type: image/jpeg');
	header('Content-type: '.mime_content_type($file_path));
} else {
	//header('Content-Disposition: attachment; filename="'."$fileData[file_org]".'"');
	header('Content-type: '.mime_content_type($file_path));
}

readfile($file_path);

?>