<?php
$systext = array();
$systext['en'] = array();
$systext['tc'] = array();
$systext['sc'] = array();

$txts = $mysql->getList('admin_text_config');
foreach ($txts as $txt) {
	$systext['en'][$txt['text_index']] = $txt['text_en'];
	$systext['tc'][$txt['text_index']] = $txt['text_tc'];
	$systext['sc'][$txt['text_index']] = $txt['text_sc'];
}
?>