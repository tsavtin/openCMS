<?php

include_once 'includes/config.php';

if(isset($_GET['sub_stage'])){
    foreach ($curCfg['fields'] as $field){
        if(isset($_GET['del_field']) && $_GET['sub_stage'] && $field['field_index'] == $_GET['del_field']){
            if($_GET['sub_stage'] == 'delete_image' || $_GET['sub_stage'] == 'delete_video' || $_GET['sub_stage'] == 'delete_file'){
                $data_type->{'dt_'.$field['field_type']}->config($field);
                $data_type->{'dt_'.$field['field_type']}->delete_record(array($_GET['del_field']=>$_GET['sub_stage_id']));
                $query = array($curCfg['table_key'] => $field['field_index']);
                if($curCfg['parent_related_key']){
                    $query[$curCfg['parent_related_key']] = $_GET[$curCfg['parent_related_key']];
                }
                $mysql->update($curCfg['table_name'], $query, array($curCfg['table_value'] => 0));
            } 
        } else if(isset($_GET['sub_stage_id']) && ($_GET['sub_stage'] == 'rotate_imgleft' || $_GET['sub_stage'] == 'rotate_imgright') && $field['field_index'] == $_GET['del_field']){
            if($_GET['sub_stage'] == 'rotate_imgleft'){
                $data_type->{'dt_image'}->rotate($_GET['sub_stage_id'], 90);
            } else {
                $data_type->{'dt_image'}->rotate($_GET['sub_stage_id'], -90);
            }
        }
    }
} else if (isset($_POST['form_stage']) && $_POST['form_stage'] == 'form_submit') {
    if(!$login->isAllowPermission($tbCfgs[$t]['admin_zconfigtable_id'], 'allow_modify') && $login->getAdminID() != 1){
        echo 'permission denied';
        exit;
    }
    include_once 'content_validate.php';
    if (!count($formerror)) {
        $_GET['stage'] = 'modify';
        foreach ($curCfg['fields'] as $field) {
            $fieldOpts = fieldOpt($field['field_options']);
            if (isset($fieldOpts['skipsql'])) { continue; }
            $query = array($curCfg['table_key'] => $field['field_index']);
            if($curCfg['parent_related_key']){
                foreach (explode(',', $curCfg['parent_related_key']) as $v) {
                    $query[$v] = $_GET[$v];
                }
            }
            if (!$mysql->rowCount($curCfg['table_name'], $query)) {
                $newData = array(
                    $curCfg['table_key'] => $field['field_index'], 
                    $curCfg['table_value'] => $_POST[$field['field_index']]?$_POST[$field['field_index']]:''
                );
                if($curCfg['parent_related_key']){
                    foreach (explode(',', $curCfg['parent_related_key']) as $v) {
                        $newData[$v] = $_GET[$v];
                    }
                }
                $mysql->create($curCfg['table_name'], $newData);
            } else {
                if($field['field_type'] == 'video' || $field['field_type'] == 'image' || $field['field_type'] == 'file'){continue;}
                $mysql->update($curCfg['table_name'], $query, array($curCfg['table_value'] => $_POST[$field['field_index']]));
            }
        }
        $form_message = get_systext('msg_upd') . " $curCfg[title] " . get_systext('msg_suc') . " :" . date('Y-m-d H:i:s');
        
        log_admin_activity($_GET['stage'], get_systext($curCfg['title']) . '(' . $_GET[$curCfg['table_primarykey']] . ')');

        foreach ($curCfg['fields'] as $field) {
            $fieldOpts = fieldOpt($field['field_options']);
            if (isset($fieldOpts['skipsql'])) { continue; }
            $data_type->{'dt_'.$field['field_type']}->config($field);
            $data_type->{'dt_'.$field['field_type']}->form_after_submit();
        }
        if ($_GET['stage'] == 'modify') {
            $_POST = [];
        }
        if($_GET['tpl'] == 'nomenu'){
            ?>
            <script type="text/javascript">
                window.parent.$.fancybox.close();
                window.parent.location.reload();
            </script><?php
            exit;
        }
    }
}

if (!count($_POST)) {
    $query = [];
    if($curCfg['parent_related_key']){
        $query[$curCfg['parent_related_key']] = $_GET[$curCfg['parent_related_key']];
    }
    $modifyInfo = $mysql->getList($curCfg['table_name'], $query);
    foreach ($modifyInfo as $field) {
        $_POST[$field[$curCfg['table_key']]] = $field[$curCfg['table_value']];
    }
}

ob_start();

include_once 'content_form.php';

if (isset($cms_content)) {
    $cms_content .= ob_get_contents();
} else {
    $cms_content = ob_get_contents();
}
ob_end_clean();

if($_GET['tpl'] == 'nomenu'){
    include 'template_nomenu.php';
} else {
    include 'template.php';
}
?>