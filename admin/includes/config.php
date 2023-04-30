<?php

error_reporting(0);

date_default_timezone_set('Asia/Hong_Kong');

$cookie_secure = false;
$cookie_httponly = true;
$cookie_maxlifetime = 60 * 60 * 24; // 24 hours

if(PHP_VERSION_ID < 70300) {
    session_set_cookie_params($cookie_maxlifetime, '/', $_SERVER['HTTP_HOST'], $cookie_secure, $cookie_httponly);
} else {
    session_set_cookie_params([
        'lifetime' => $cookie_maxlifetime,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $cookie_secure,
        'httponly' => $cookie_httponly
    ]);
}

ini_set('display_errors', true);
//error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_ERROR);
error_reporting(E_ERROR);
// error_reporting(E_ALL);

if (!defined('STD_PAD_LEFT')) {
    define('STD_PAD_LEFT', 0);
}
function filter_inputstr($str){
    $str = strip_tags($str, '<p><a><i><b><u><hr><h1><h2><h3><h4><h5><h6><img><table><tbody><tr><th><td><br><ul><li><ol><sup><sub><span><div><font><nobr>');
    $str = str_replace('$', '&#x24;', $str);
    $str = preg_replace('/ on.*?=/i', '', $str);
    $str = preg_replace('/\/on.*?=/i', '', $str);
    $str = preg_replace('/\son.*?=/i', '', $str);
    return $str;
}
function loop_array($ar){
    foreach($ar as $k => $v){
        if(is_array($v)){
            loop_array($v);
        } else {
            $v = filter_inputstr($v);
        }
        $ar[$k] = $v;
    }
    return $ar;
}
foreach ($_POST as $key => $value) {
    if(is_array($_POST[$key])){
        $_POST[$key] = loop_array($_POST[$key]);
    } else {
        $_POST[$key] = filter_inputstr($_POST[$key]);
    }
}

foreach ($_GET as $key => $value) {
    if(is_array($_GET[$key])){
        $_GET[$key] = loop_array($_GET[$key]);
    } else {
        $_GET[$key] = filter_inputstr($_GET[$key]);
    }
}

$_GET['m'] = (int)$_GET['m'];

session_start();

$sysCfg = array();
$config['Website_Title'] = 'CMS';

include_once '../includes/project.php';
include_once './includes/language_setting.php';
include_once '../includes/mysql.class.php';
include_once '../includes/functions.php';
include_once '../includes/functions_global.php';

if(file_exists('../project/' . $project_folder . '/config/dbconfig.php')){
    include_once '../project/' . $project_folder . '/config/dbconfig.php';
}

if($mysqlhost && $mysqluser && $mysqldb && $dbprefix) {
  $mysql = new mysqlclass($mysqlhost, $mysqluser, $mysqlpass, $mysqldb, $dbprefix);
}

if(file_exists('../project/' . $project_folder . '/config/globalconfig.php')){
    include_once '../project/' . $project_folder . '/config/globalconfig.php';
}

if($mysqlhost && $mysqluser && $mysqldb && $dbprefix) {
  $mysql = new mysqlclass($mysqlhost, $mysqluser, $mysqlpass, $mysqldb, $dbprefix);
}

include_once 'includes/function.php';

if (file_exists('../project/' . $project_folder . '/config/textconfig.php')) {
    include_once '../project/' . $project_folder . '/config/textconfig.php';
} else {
    include_once '../project/default/config/textconfig.php';
}

include_once './includes/cmsconfig.php';

include_once './includes/login.class.php';

require '../includes/vendor/phpmailer/phpmailer/src/Exception.php';
require '../includes/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../includes/vendor/phpmailer/phpmailer/src/SMTP.php';

$nowTime = date("Y-m-d H:i:s");


// process back parameter 
if(isset($_GET['bp'])){
    if(!isset($_SESSION['bp'])){
        $_SESSION['bm'] = array();
        $_SESSION['bp'] = array();
    }
    $bp = json_decode(rawurldecode($_GET['bp']), true);
    if(!isset($_SESSION['bm'][$bp['bm']])){
        array_push($_SESSION['bp'], $_GET['bp']);
        $_SESSION['bm'][$bp['bm']] = true;
    }
}
if(isset($_GET['bc'])){
    $bp = json_decode(array_pop($_SESSION['bp']), true);
    unset($_SESSION['bm'][$bp['bm']]);
}
if(isset($_GET['ca'])){
    unset($_SESSION['bm']);
    unset($_SESSION['bp']);
}
unset($_GET['bp']);
unset($_GET['bc']);
unset($_GET['ca']);

// process back parameter 

// rend admin menu
$menuCFG = $mysql->getListJoin('LEFT JOIN', array('admin_zmenu', 'admin_zconfigtable'), array('admin_zconfigtable_id', 'admin_zconfigtable_id'), array(array('status'=>1)), array('*', array('table_index')), array(array('sort_order')), array(array('ASC')));
$cmsconfig_data = array();
foreach ($menuCFG as $info) {
    $cmsconfig_data[$info['parent_id']][] = $info;
}
function rend_menu($parent_id){
    global $cmsconfig_data, $selected_lang;
    $data = array();
    foreach ($cmsconfig_data[$parent_id] as $info) {
        $ndata = array(
            'id' => $info['admin_zmenu_id'],
            'tid' => $info['admin_zconfigtable_id'],
            'pid' => $info['parent_id'],
            'menu_title' => $info['name_'.$selected_lang]
        );
        if(isset($cmsconfig_data[$info['admin_zmenu_id']])){
            $ndata['submenu'] = rend_menu($info['admin_zmenu_id']);
        }
        $ndata['table_name'] = isset($info['table_name'])?$info['table_name']:'';
        $ndata['table_index'] = isset($info['table_index'])?$info['table_index']:'';
        $data[$info['admin_zmenu_id']] = $ndata;
    }
    return $data;
}

$cmsconfig = rend_menu(0);

// rend table config
$tbs = $mysql->getList('admin_zconfigtable', array('status'=>1));
if($mysql->columnExists('admin_zconfigtable_fields', 'list_order') && ($_GET['tt'] == 'list' || $_GET['tt'] == 'sublist' || $_GET['tt'] == 'search' || $_GET['tt'] == 'shortcut' || $_GET['tt'] == 'backlist')){
    $fds = $mysql->getList('admin_zconfigtable_fields', array(), '*', 'list_order', 'ASC');
} else {
    $fds = $mysql->getList('admin_zconfigtable_fields', array(), '*', 'sort_order', 'ASC');
}

$tbIndexByID = array();
$tbCfgs = array();
$fdsCfgByTb = array();
$fieldIndexByID = array();
$fieldExtraOpt = array();
$fieldinlineListOpt = array();
$PRIS = array();
foreach ($fds as $info) {
    $fdsCfgByTb[$info['admin_zconfigtable_id']][] = $info;
    $fieldIndexByID[$info['admin_zconfigtable_fields_id']] = $info['field_index'];
    if($info['admin_zconfigtable_id'] == 34 && $info['field_index'] == 'extra_opt'){
        $fieldExtraOpt[str_replace('extra_opt ', '', $info['field_name'])] = json_decode($info['extra_opt'], true);
    }
}
$rend_language = false;
//$allow_tables = array();
foreach ($tbs as $info) {
    $allow_tables[] = $info['table_index'];
    $fields = array();
    if(isset($fdsCfgByTb[$info['admin_zconfigtable_id']])){
        foreach ($fdsCfgByTb[$info['admin_zconfigtable_id']] as $field) {
            $fd = $field;
            $fd['field_id'] = $field['admin_zconfigtable_fields_id'];
            $fd['field_options'] = $field['field_options']?explode(',', $field['field_options']):array();
            $rend_language = $rend_language?$rend_language:in_array('support_language', $fd['field_options']);
            $fd['extra_opt'] = json_decode($field['extra_opt'], true);
            $extra = $fd['extra_opt'];
            if($fieldExtraOpt[$fd['field_type']]){
                foreach ($fieldExtraOpt[$fd['field_type']] as $k => $v) {
                    $fd[$v[0]] = $extra[0][$k];
                }
            }
            if($fd['field_type'] == 'tabs'){
                foreach ($extra as $sfkey => $tabs) {
                    $fd['tabs'][$sfkey] = join(',', $tabs);
                }
            } else if($fd['field_type'] == 'inlinelist'){
                foreach ($extra as $sfkey => $sfdata) {
                    $fd['fields_cfg'][$sfkey] = $sfdata;
                }
                list($fd['allow_create'], $fd['serialize_key']) = explode(',', $field['serialize_opt']);
            }
            $fields[] = $fd;
        }
    }
    if(isset($fdsCfgByTb[$info['related_id']])){
        $tmp_fields = $fields;
        $fields = array();
        foreach ($fdsCfgByTb[$info['related_id']] as $field) {
            $field_replaced = false;
            foreach ($tmp_fields as $tmp_field) {
                if($field['field_index'] == $tmp_field['field_index']){
                    $fields[] = $tmp_field;
                    $field_replaced = true;
                    break;
                }
            }
            if($field_replaced){ continue; }
            $fd = $field;
            $fd['field_id'] = $field['admin_zconfigtable_fields_id'];
            $fd['field_options'] = $field['field_options']?explode(',', $field['field_options']):array();
            $fd['extra_opt'] = json_decode($field['extra_opt'], true);
            $extra = $fd['extra_opt'];
            if($fieldExtraOpt[$fd['field_type']]){
                foreach ($fieldExtraOpt[$fd['field_type']] as $k => $v) {
                    $fd[$v[0]] = $extra[0][$k];
                }
            }
            if($fd['field_type'] == 'tabs'){
                foreach ($extra as $sfkey => $tabs) {
                    $fd['tabs'][$sfkey] = join(',', $tabs);
                }
            } else if($fd['field_type'] == 'inlinelist'){
                foreach ($extra as $sfkey => $sfdata) {
                    $fd['fields_cfg'][$sfkey] = $sfdata;
                }
                list($fd['allow_create'], $fd['serialize_key']) = explode(',', $field['serialize_opt']);
            }
            $fields[] = $fd;
        }

        // add new fields
        foreach ($tmp_fields as $tmp_field) {
            $field_replaced = false;
            foreach ($fields as $field) {
                if($field['field_index'] == $tmp_field['field_index']){
                    $field_replaced = true;
                    break;
                }
            }
            if(!$field_replaced){
                $fields[] = $tmp_field;
            }
        }
    }
    
    $tbIndexByID[$info['admin_zconfigtable_id']] = $info['table_index'];
    $ncfg = $info;
    $ncfg['title'] = $info['table_title'];
    $ncfg['menu_title'] = $info['table_title'];
    $ncfg['option'] = explode(',', $info['table_option']);
    $ncfg['list_filter'] = $info['list_filter']?explode(',', $info['list_filter']):'';
    $ncfg['search_field'] = $info['search_field']?explode(',', $info['search_field']):'';
    $ncfg['cnt_btns'] = $info['cnt_btns']?explode(',', $info['cnt_btns']):'';
    $ncfg['list_update'] = explode(',', $info['list_update']);
    $ncfg['export_field'] = explode(',', $info['export_field']);
    $ncfg['default_filter'] = json_decode($info['default_filter'], true);
    $ncfg['fields'] = $fields;
    $tbCfgs[$info['table_index']?$info['table_index']:$info['admin_zconfigtable_id']] = $ncfg;

    $convert_field = array('list_update');
    foreach ($convert_field as $fd_index) {
        if ($tbCfgs[$info['table_index']][$fd_index]) {
            $convertItem = array();
            foreach ($tbCfgs[$info['table_index']][$fd_index] as $item) {
                if(isset($fieldIndexByID[$item])){
                    $convertItem[] = $fieldIndexByID[$item];
                }
            }
            $tbCfgs[$info['table_index']][$fd_index] = $convertItem;
        }
    }

    // rend master permission
    $PRIS[$info['admin_zconfigtable_id']] = tableOpt(explode(',', $info['table_option']));
}

// rend language field
function remove_value($array, $value){
    if(($key = array_search($value, $array)) !== false){
        unset($array[$key]);
    }
    return $array;
}
if($rend_language && $mysql->tableExists('language') && true){
    $langauges = $mysql->getList('language', null, '*','sort_order', 'ASC');
    foreach ($tbCfgs as $table_index => $table) {
        $lang_fields = array();
        $tabs_language = -1;
        foreach ($table['fields'] as $k => $field) {
            if($field['field_type'] == 'tabs_language'){
                $tabs_language = $k+1;
            }
            $fieldOpts = fieldOpt($field['field_options']);
            if (isset($fieldOpts['support_language']) && $fieldOpts['support_language']) {
                $remove_field_options = false;
                foreach ($langauges as $lang) {
                    $new = $field;
                    if($lang['code']){
                        $new['field_index'] .= '_'.$lang['code'];
                        if(!$remove_field_options){
                            $table['fields'][$k] = $new;
                            $table['fields'][$k]['field_options'] = remove_value($field['field_options'], 'create');
                            $table['fields'][$k]['field_options'] = remove_value($field['field_options'], 'modify_show');
                            $table['fields'][$k]['field_options'] = remove_value($field['field_options'], 'modify');
                        }
                        if($lang['code'] != 'en'){
                            $new['field_options'] = remove_value($new['field_options'], 'list');
                        }
                        //$remove_field_options = true;
                        $lang_fields[$lang['code']][] = $new;
                    }
                    unset($table['fields'][$k]);
                }
            }
        }
        
        if($lang_fields){
            $new_fields = array();
            foreach ($lang_fields as $code => $field){
                if($tabs_language == -1){
                    $new_fields[] = array('field_type' => 'tabs_language', 'field_options' => array('create', 'modify'), 'field_default' => 'section_'.$code);
                    $tabs_language = $k+1;
                }
                $new_fields[] = array('field_type' => 'html', 'field_name' => '<div class="section_'.$code.'">', 'field_options' => remove_value($field[0]['field_options'], 'list'));
                $new_fields = array_merge($new_fields, $field);
                $new_fields[] = array('field_type' => 'html', 'field_name' => '</div>', 'field_options' => remove_value($field[0]['field_options'], 'list'));
            }
            array_splice($table['fields'], $tabs_language, 0 , $new_fields);
        }
        $tbCfgs[$table_index] = $table;
    }
}

// security check $_GET['t'], $_GET['m']
if(isset($_GET['t']) && !in_array($_GET['t'], $allow_tables)){
    echo 'not allow_tables';
    exit;
}
$_GET['m'] = (int)$_GET['m'];

if($_GET['stage'] && !preg_match('/^[A-Z]|_+$/i', $_GET['stage'])){
    return false;
}

$login = new loginModule();
//$login->sessionKey = $project_folder . md5($_SERVER['REQUEST_URI']) . 'usersession_';
$login->sessionKey = $project_folder  . 'usersession_';
$login->proname = $project_folder . 'cms';
$login->tableName = 'admin';
if (isset($_GET['logout'])) {
   $login->logoutTag = $_GET['logout'];
}

$cms_login = $cms_password = $signin_checkbox = '';
if(isset($_POST['cms_login'])){
    $cms_login = $_POST['cms_login'];
}
if(isset($_POST['cms_password'])){
    $cms_password = $_POST['cms_password'];
}
if(isset($_POST['signin-checkbox'])){
    $signin_checkbox = $_POST['signin-checkbox'];
}

$login->setMysql($mysql);
//$_SESSION[$login->sessionKey . 'admin_id'] = 1;
$login->check($cms_login ,$cms_password ,$signin_checkbox);

if (isset($_GET['logout'])) {
    $login->logOut();
}

$regGolbal = array('m', 't');
foreach ($regGolbal as $value) {
    if (isset($_GET[$value])) {
        $$value = $_GET[$value];
    }
    if (isset($_POST[$value])) {
        $$value = $_POST[$value];
    }
}
foreach ($cmsconfig as $mc => $scs) {
    if (isset($scs['table_index'])) {
        $tbCfgs[$scs['table_index']]['menu_title'] = $scs['menu_title'];
        $tbCfgs[$scs['table_index']]['title'] = $tbCfgs[$scs['table_index']]['title']?$tbCfgs[$scs['table_index']]['title']:$scs['menu_title'];
    } else if (isset($scs['submenu'])) {
        foreach ($scs['submenu'] as $sc => $scv) {
            $tbCfgs[$scv['table_index']]['menu_title'] = $scv['menu_title'];
            $tbCfgs[$scv['table_index']]['title'] = $tbCfgs[$scv['table_index']]['title']?$tbCfgs[$scv['table_index']]['title']:$scv['menu_title'];
        }
    }
}
foreach ($tbCfgs as $tname => $toption){
    if (isset($tbCfgs[$tname]['option'])) {
       $tbCfgs[$tname]['option_hash'] = tableOpt($tbCfgs[$tname]['option']);
    }
}

// // check perssion table
function getPriActID($section, $action){
    global $PRIS;
    if (!isset($PRIS[$section]) || !isset($PRIS[$section][$action])) {
       return null;
    }
    return $PRIS[$section][$action];
}


if($_GET['t'] == 'admin_group'){
    // delete table_option by admin_zconfigtable_id
    $stm = $mysql->prepare('DELETE FROM '.$mysql->sql_prefix.'admin_zconfigtable_option WHERE admin_zconfigtable_id NOT IN('.str_repeat('?,', count(array_keys($tbIndexByID)) - 1).'?)');
    $stm->execute(array_keys($tbIndexByID));

    //  delete table_option admin_zconfigtable_id, option
    $delete_optIDs = [];
    $exist_opt = [];
    foreach ($mysql->getList('admin_zconfigtable_option') as $v) {
        if(!$PRIS[$v['admin_zconfigtable_id']][$v['name']]){
            $delete_optIDs[] = $v['admin_zconfigtable_option_id'];
        } else {
            $exist_opt[$v['admin_zconfigtable_id']][$v['name']] = $v['admin_zconfigtable_option_id'];
        }
    }
    if($delete_optIDs){
        $mysql->delete('admin_zconfigtable_option', ['admin_zconfigtable_option_id' => $delete_optIDs]);
    }

    // create no exist admin_zconfigtable_option
    foreach ($PRIS as $admin_zconfigtable_id => $option) {
        foreach ($option as $k => $b) {
            if(!$exist_opt[$admin_zconfigtable_id][$k]){
                $mysql->create('admin_zconfigtable_option', [
                    'admin_zconfigtable_id' => $admin_zconfigtable_id,
                    'name' => $k
                ]);
                $exist_opt[$admin_zconfigtable_id][$k] = $mysql->lastInsertId();
            }
        }
    }
}

if($login->getAdminID() != 1){
    foreach ($cmsconfig as $mc => $scs) {
        if (isset($scs['table_index']) && $scs['table_index']) {
            if(!$login->isAllowPermission($scs['tid'], 'allow_list')){
                unset($cmsconfig[$mc]);
            }
        } else if (isset($scs['submenu']) && $scs['submenu']) {
            foreach ($scs['submenu'] as $sc => $scv) {
                if(!$login->isAllowPermission($scv['tid'], 'allow_list')){
                    unset($cmsconfig[$mc]['submenu'][$sc]);
                }
            }
        }
    }
    foreach ($tbCfgs as $table_index => $table){
        $tbOpt = isset($table['option'])?$table['option']:array();
        $opts = array();
        foreach ($tbOpt as $opt) {
            if($login->isAllowPermission($table['admin_zconfigtable_id'], $opt)){
                $opts[] = $opt;
            }
        }
        $tbCfgs[$table_index]['option'] = $opts;
        $tbCfgs[$table_index]['option_hash'] = tableOpt($opts);
    }
}

foreach ($tbCfgs as $tn => $table) {
    if(isset($table['fields']) && is_array($table['fields'])){
        // fbi // field by index
        $tbCfgs[$tn]['fbi'] = array();
        $tbCfgs[$tn]['fbid'] = array();
        foreach ($table['fields'] as $field) {
            $tbCfgs[$tn]['fbi'][$field['field_index']] = $field;
            $tbCfgs[$tn]['fbid'][$field['field_id']] = $field;
        }
    }
    if (isset($table['option']) && is_array($table['option'])) {
        // oi // option by index
        $tbCfgs[$tn]['oi'] = array();
        foreach ($tbCfgs[$tn]['option'] as $opt) {
            $tbCfgs[$tn]['oi'][$opt] = true;
        }
    }
}

if(isset($t)){
    $curCfg = $tbCfgs[$t];
    if($_GET[$curCfg['table_primarykey']]){
        $_GET[$curCfg['table_primarykey']] = (int)$_GET[$curCfg['table_primarykey']];
    }
    if($_POST[$curCfg['table_primarykey']]){
        $_POST[$curCfg['table_primarykey']] = (int)$_POST[$curCfg['table_primarykey']];
    }
    $curCfg['table_name'] = isset($curCfg['table_name'])?$curCfg['table_name']:$t;
    $curCfg['index'] = $t;
}

// init data_type class and sub class
include_once('data_type/data_type.php');
$data_type = new data_type();
$data_type->mysql = $mysql;

if (isset($curCfg)) {
    $data_type->config_table = $curCfg;
}

$project_data_type = '../project/'.$project_folder.'/admin/data_type/';
if ($handle = opendir('data_type')) {
    while (false !== ($entry = readdir($handle))) {
        if(substr($entry, 0, 1) == '.' || $entry == 'data_type.php'){continue;}
        if(file_exists($project_data_type.$entry)){
            include_once($project_data_type.$entry);
        } else {
            include_once("data_type/$entry");
        }
        list($classname, $ext) = explode('.', $entry);
        $data_type->$classname = new $classname($data_type);
    }
    closedir($handle);
}
?>