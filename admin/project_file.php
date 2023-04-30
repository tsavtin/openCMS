<?php

include_once 'includes/config.php';
$_GET['file'] = str_replace('\\', '', $_GET['file']);
$_GET['file'] = str_replace('/', '', $_GET['file']);
if (file_exists('../project/'.$project_folder.'/admin/'.$_GET['file'].'.php')) {
	include_once '../project/'.$project_folder.'/admin/'.$_GET['file'].'.php';
} else if (file_exists($_GET['file'].'.php')) {
	include_once $_GET['file'].'.php';
}
?>