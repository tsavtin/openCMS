<?php

error_reporting(0);
ini_set('display_errors', true);

include_once 'includes/project.php';

$parse_url = parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

if ($parse_url['port']) {
	$actual_link = $parse_url['scheme']."://$parse_url[host]".":".$parse_url['port'].str_replace(basename($parse_url['path']), '', $parse_url['path']);
} else {
	$actual_link = $parse_url['scheme']."://$parse_url[host]".str_replace(basename($parse_url['path']), '', $parse_url['path']);
}


function get_api(){
	global $_GET;
	return $_GET['c'];
}
function get_action(){
	global $_GET;
	return $_GET['a'];
}
function get_parm($key){
	global $_POST, $_GET;
	return $_POST[$key]?$_POST[$key]:$_GET[$key];
}
global $result;
$result = array(
    'rscode' => 400,
	'rscode_reason' => 'api control not exists'
);

//Something to write to txt log
$log  = date('Y-m-d H:i:s').$_SERVER['REQUEST_URI'].PHP_EOL;
//Save string to log, use FILE_APPEND to append.
file_put_contents('../logs/log_'.date("j.n.Y").'.log', $log, FILE_APPEND);

$skip_access_token = false;

function isReqLogin($api, $action){
	if(($api == 'mem' || $api == 'memdev') && in_array($action, ['login', 'member_sms_validate', 'social_login', 'email_login', 'email_login_validate', 'guest_register', 'wechat_login', 'wechat_openid'])){
		return false;
	} else if($api == 'vip' && in_array($action, ['list'])){
		return false;
	} else if($api == 'expert' && in_array($action, ['list'])){
		return false;
	} else if($api == 'media' && in_array($action, ['list', 'post_notification', 'post_notification_dev'])){
		return false;
	} else if($api == 'post' && in_array($action, ['list', 'list_comment'])){
		return false;
	} else if($api == 'content' && in_array($action, ['banner_top', 'history', 'banner_popup'])){
		return false;
	} else if($api == 'version'){
		return false;
	} else if($api == 'setting' && in_array($action, ['allow_payment'])){
		return false;
	}
	return true;
}

if(file_exists('project/'.$project_folder.'/api/'.get_api().'.php')){
	include_once 'includes/config.php';
	$member_id = get_parm('member_id');
	$access_token = get_parm('access_token');

	if(isReqLogin(get_api(), get_action()) || ($member_id && $access_token)){
		if(!$member_id){
			$result['rscode'] = 200;
			$result['rscode_reason'] = 'no member_id';
		} else if(!$mysql->rowCount('member', ['member_id' => $member_id])){
			$result['rscode'] = 200;
			$result['rscode_reason'] = 'member not exist';
		} else if(!$mysql->rowCount('member', ['member_id' => $member_id, 'status' => 1])){
			$result['rscode'] = 200;
			$result['rscode_reason'] = 'Invalid login';
		} else if(!$access_token){
			$result['rscode'] = 200;
			$result['rscode_reason'] = 'no access_token';
		}  else if(!$mysql->rowCount('member', ['member_id' => $member_id, 'access_token' => $access_token]) && !$skip_access_token){
			$result['rscode'] = 200;
			$result['rscode_reason'] = 'Invalid access_token';
		}
		if($result['rscode'] == 200){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($result);
			exit;
		}
	} else {
		$_GET['member_id'] = $_GET['member_id']?$_GET['member_id']:0;
	}
	include_once 'project/'.$project_folder.'/api/'.get_api().'.php';
}

header('Content-Type: application/json; charset=utf-8');
//header('Access-Control-Allow-Origin: *');

function change_null($array){
	foreach ($array as $k => $v) {
		if($v === null){
			$array[$k] = '';
		} else if(is_array($v)){
			$array[$k] = change_null($v);
		}
	}
	return $array;
}
//$result = change_null($result);

echo json_encode($result, JSON_PARTIAL_OUTPUT_ON_ERROR);

?>