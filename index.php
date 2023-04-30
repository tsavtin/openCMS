<?php

include_once 'includes/project.php';


if(isset($_GET['clear'])){
	$_SESSION = array();
}

if(!file_exists('project/'.$project_folder.'/html/')){
	header('location: ./admin/');
	exit;
}

if(!isset($_GET['file']) || !$_GET['file']){
	$_GET['file'] = 'index';
}
$files = explode('/', $_GET['file']);

unset($_GET['file']);

$allow_files = array();

if ($handle = opendir('project/'.$project_folder.'/html/')) {
    /* This is the correct way to loop over the directory. */
    while (false !== ($entry = readdir($handle))) {
    	if(substr($entry, 0, 1) == '.' || is_dir('project/'.$project_folder.'/html/'.$entry)){continue;}
        $allow_files[] = str_replace('.php', '', $entry);
    }
    closedir($handle);
}

if(!in_array($files[0], $allow_files)){
	echo htmlspecialchars($files[0]).' not allow action';
	exit;
}


if(file_exists('project/'.$project_folder.'/html/'.$files[0].'.php')){
	include_once 'includes/config.php';
	include_once 'project/'.$project_folder.'/html/'.$files[0].'.php';
} else {
	echo htmlspecialchars($files[0]).' not exist';
}

?>