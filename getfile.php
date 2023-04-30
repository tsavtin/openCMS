<?php
include_once 'includes/project.php';
include_once 'includes/config.php';



// $user_id = $_SESSION[$login->sessionKey . 'user_id'];
// if(!$user_id){exit;}
// $userInfo = $mysql->getData('user', array('user_id' => $user_id));
// if($_GET['type'] == 'resume'){
// 	$_GET['file_id'] = $userInfo['resume_id'];
// } else {
// 	$_GET['file_id'] = $userInfo['coverletter_id'];
// }

if($_GET['file_id']){
	$fileData = $mysql->getData($fileTable, array($filePrimarykey => $_GET['file_id']));
	$folder = get_folder($filefolder, $_GET['file_id'], $fileData['date_added']);
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

if (strtolower($fileData[type]) != 'pdf' && !$_GET['image_id']) {
	header('Content-Encoding: none');
	//header('Content-Length: '.$file_path);
	//header('Cache-control: private, must-revalidate');

	if($fileData[file_org]){
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