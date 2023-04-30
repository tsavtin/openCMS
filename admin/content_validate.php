<?php


$res = $mysql->getFields($curCfg['table_name']);
$tablekeys = [];
foreach ($res as $re) {
    $tablekeys[$re['Column_name']] = $re;
}

$res = $mysql->getColumns($curCfg['table_name']);
$tableCols = [];
foreach ($res as $re) {
    $tableCols[$re['Field']] = $re;
}

$formerror = isset($formerror)?$formerror:[];
$sqlSkip = [];
$data = [];
$field_type = '';

if($_GET[$curCfg['table_primarykey']]){
    $curdata = $mysql->getData($curCfg['table_name'], array($curCfg['table_primarykey'] => $_GET[$curCfg['table_primarykey']]));
}


foreach ($curCfg['fields'] as $field) {
    if ($field['field_index'] == 'field_type') {
        $field_type = $_POST[$field['field_index']];
    }
    if($field_type && $field['field_index'] == 'extra_opt' && 'extra_opt_'.$field_type != $field['serialize_key']){
        continue;
    }
    if((isset($tablekeys[$field['field_index']]) && $tablekeys[$field['field_index']]['Non_unique'] === 0)){
        $field['field_options'][] = 'unique';
    }
    $fieldOpts = fieldOpt($field['field_options']);
    if (!isset($fieldOpts[$_GET['stage']]) && (!isset($curCfg['table_type']) || $curCfg['table_type'] != 'setting') && !(isset($fieldOpts['create']) && $_GET['stage'] == 'duplicate')) {
        continue;
    }
    if($_GET['stage'] == 'modify' && (isset($fieldOpts['modify_show']) || isset($fieldOpts['modify_readonly']))){
        continue;
    }
    if ($_GET['stage'] == 'create' || $_GET['stage'] == 'modify' || $_GET['stage'] == 'duplicate' || $curCfg['table_type'] == 'setting') {
        $data_type->{'dt_'.$field['field_type']}->config($field);
        list($fv_skip, $pdata, $error) = $data_type->{'dt_'.$field['field_type']}->form_validate($_POST);
        if(preg_match('/decimal/is', $tableCols[$field['field_index']]['Type']) && $_POST[$field['field_index']] == ''){
            $_POST[$field['field_index']] = 0.0;
        }
        if(preg_match('/int/is', $tableCols[$field['field_index']]['Type']) && $_POST[$field['field_index']] == ''){
            $_POST[$field['field_index']] = 0;
        }
        if($fv_skip !== false){
             $sqlSkip[$field['field_index']] = $fv_skip;
        }
        if($pdata !== false){
             $_POST[$field['field_index']] = $pdata;
        }
        if($error !== false){
             $formerror[$field['field_index']] = $error;
        }
        if (!$field['field_index'] || isset($fieldOpts['skipsql'])) {
            $sqlSkip[$field['field_index']] = true;
        }
        if (isset($fieldOpts['unique']) || isset($fieldOpts['unique_wparent'])) {
            $q = array($field['field_index'] => $_POST[$field['field_index']]);
            if(isset($fieldOpts['unique_wparent']) && $curCfg['parent_related_key']){
                // 先check post , fix dupe fields unique_wparent 會empty field_index
                $q[$curCfg['parent_related_key']] = $_POST[$curCfg['parent_related_key']]?$_POST[$curCfg['parent_related_key']]:$_GET[$curCfg['parent_related_key']];
            }
            $info = $mysql->getData($curCfg['table_name'], $q);
            if ($info && count($info) && $info[$curCfg['table_primarykey']] == $_GET[$curCfg['table_primarykey']]) {
                $sqlSkip[$field['field_index']] = true;
            } else if ($info && count($info) && $_POST[$field['field_index']]) {
                $formerror[$field['field_index']] = $field['field_name'] . ' ' . get_systext('msg_exists');
            }
        }
        if (isset($fieldOpts['require']) && !isset($formerror[$field['field_index']])) {
            if ($field['field_type'] == 'related_checkbox') {
                if (!is_relate_checked($field)) {
                    $formerror[$field['field_index']] = get_systext('msg_missing_field');
                }
            } else if ($field['field_type'] == 'file' || $field['field_type'] == 'image') {
                if(!file_exists($_FILES[$field['field_index']]['tmp_name']) && !$curdata[$field['field_index']]){
                    $formerror[$field['field_index']] = get_systext('msg_missing_field');
                }
            } else if (!$_POST[$field['field_index']] && $_POST[$field['field_index']] != '0') {
                $formerror[$field['field_index']] = get_systext('msg_missing_field');
            }
        }

        if (!isset($sqlSkip[$field['field_index']])) {
            $data[$field['field_index']] = isset($_POST[$field['field_index']])?$_POST[$field['field_index']]:'';
            if(isset($tableCols[$field['field_index']]) && preg_match('/int|decimal|date/is', $tableCols[$field['field_index']]['Type']) && $tableCols[$field['field_index']]['Null'] == 'YES' && $_POST[$field['field_index']] === ''){
                $data[$field['field_index']] = null;
            }
        }
    }
}
unset($field_type);

?>