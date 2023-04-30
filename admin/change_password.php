<?php

include_once 'includes/config.php';

$_GET['admin_id'] = $login->getAdminID();
$_GET['stage'] = 'modify';
$t = 'change_password';
$curCfg = $tbCfgs['change_password'];

$formerror = [];
if(isset($_POST['form_stage']) && $_POST['form_stage'] == 'form_submit'){
	$info = $mysql->getData('admin', array('admin_id'=>$login->getAdminID()));
	if(!$_POST['current']){
		$formerror['current'] = get_systext('msg_missing_field');
	} else if(md5($_POST['current'] . $encryptKey) != $info['password']){
		$formerror['current'] = get_systext('msg_invalid_password');
	} else if(!$_POST['password']){
		$formerror['password'] = get_systext('msg_missing_field');
	} else if($_POST['password'] != $_POST['password_confirm']){
		$formerror['password'] = get_systext('msg_missing_field');
		$formerror['password'] = get_systext('new_password') . ' ' . get_systext('msg_notmatch');
	} else {
		foreach ($curCfg['fields'] as $key => $field) {
			if($field['field_index'] == 'current'){
				unset($curCfg['fields'][$key]);
			}
		}
	}
	if($formerror){
		unset($_POST['form_stage']);
	}
}
$curCfg['table_type'] = 'setting';

include 'content.php';

?>