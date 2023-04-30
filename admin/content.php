<?php

include_once 'includes/config.php';

if(isset($_GET[$curCfg['table_primarykey']]) && $_GET[$curCfg['table_primarykey']] != ''){
    $query_data = array($curCfg['table_primarykey'] => $_GET[$curCfg['table_primarykey']]);
} else {
    $query_data = array($curCfg['table_primarykey'] => 0);
}

if(isset($curCfg['list_url']) && $curCfg['list_url'] && file_exists('../project/'.$project_folder.'/admin/'.$curCfg['list_url'])){
    $backphpfile = 'project_file.php?file='.str_replace('.php', '', $curCfg['list_url']);
} else if(isset($curCfg['table_type']) && $curCfg['table_type'] && file_exists($curCfg['table_type'] . '.php')){
    $backphpfile = $curCfg['table_type'] . '.php?';
} else {
    $backphpfile = 'list.php?';
}

if(isset($_GET['sub_stage'])){
    // delete permission check
    if(!$login->isAllowPermission($tbCfgs[$t]['admin_zconfigtable_id'], 'allow_modify') && $login->getAdminID() != 1){
        echo 'permission denied';
        exit;
    }
    foreach ($curCfg['fields'] as $field){
        if(isset($_GET['del_field']) && isset($_GET['sub_stage_id']) && 
            ($_GET['sub_stage'] == 'delete_image' || $_GET['sub_stage'] == 'delete_video' || $_GET['sub_stage'] == 'delete_file') 
            && $field['field_index'] == $_GET['del_field']){

            $data_type->{'dt_'.$field['field_type']}->config($field);
            $data_type->{'dt_'.$field['field_type']}->delete_record(array($_GET['del_field']=>$_GET['sub_stage_id']));
            //delete_image($_GET['image_id']);
            $mysql->update($curCfg['table_name'], $query_data, array($_GET['del_field'] => 0));
            log_admin_activity($_GET['sub_stage'], $_GET['del_field'].'(' . $_GET[$curCfg['table_primarykey']] . ', ' . $_GET['sub_stage_id'] . ')');

        } else if(isset($_GET['sub_stage_id']) && ($_GET['sub_stage'] == 'rotate_imgleft' || $_GET['sub_stage'] == 'rotate_imgright') && $field['field_index'] == $_GET['del_field']){
            if($_GET['sub_stage'] == 'rotate_imgleft'){
                $data_type->{'dt_image'}->rotate($_GET['sub_stage_id'], 90);
            } else {
                $data_type->{'dt_image'}->rotate($_GET['sub_stage_id'], -90);
            }
            log_admin_activity($_GET['sub_stage'], $_GET['del_field'].'(' . $_GET[$curCfg['table_primarykey']] . ', ' . $_GET['sub_stage_id'] . ')');
        }
    }
} else if (isset($_POST['form_stage']) && $_POST['form_stage'] == 'form_submit' && isset($_POST['cnt_btns_btn'])) {
    // create database
    if($_POST['cnt_btns_btn'] == 'create_db' && $curCfg['table_name'] == 'admin_zconfigtable'){
        if (!$_POST['table_primarykey']) {
            $message_popup = 'please provide table_primarykey';
        }
        if(!isset($message_popup)){
            $created_fields = [];
            $create_fields = [];
            $create_fields[] = '`'.$_POST['table_primarykey'].'` int(11) UNSIGNED NOT NULL AUTO_INCREMENT';
            $created_fields[$_POST['table_primarykey']] = true;
            if($tbCfgs[$_POST['table_index']]['fields']){
                foreach ($tbCfgs[$_POST['table_index']]['fields'] as $field) {
                    $fieldOpts = fieldOpt($field['field_options']);
                    if ((isset($fieldOpts['skipsql']) && $fieldOpts['skipsql']) || $created_fields[$field['field_index']] || $field['field_type'] == 'html') {
                        continue;
                    }
                    if(isset($field['field_index']) && $field['field_index'] && $field['field_index'] != $_POST['table_primarykey'] && $field['field_index'] != $_POST['table_create_field'] && $field['field_index'] != $_POST['table_modify_field']){
                        $created_fields[$field['field_index']] = true;
                        if(!$field['field_type']){
                            echo 'no field type:field_index:'.$field['field_index'];
                            exit;
                        }
                        $create_fields[] = '`'.$field['field_index'].'` '.$data_type->{'dt_'.$field['field_type']}->mysql_field_type();
                    }
                }
            }
            if(isset($_POST['table_create_field']) && $_POST['table_create_field'] && !$created_fields[$_POST['table_create_field']]){
                $create_fields[] = '`'.$_POST['table_create_field'].'` datetime  NULL';
                $created_fields[$_POST['table_create_field']] = true;
            }
            if(isset($_POST['table_modify_field']) && $_POST['table_modify_field'] && !$created_fields[$_POST['table_modify_field']]){
                $create_fields[] = '`'.$_POST['table_modify_field'].'` datetime  NULL';
                $created_fields[$_POST['table_modify_field']] = true;
            }
            if(!$created_fields['admin_added'] && !$created_fields['admin_added']){
                $create_fields[] = '`admin_added` int(11) NULL';
                $created_fields['admin_added'] = true;
            }
            if(!$created_fields['admin_modified'] && !$created_fields['admin_modified']){
                $create_fields[] = '`admin_modified` int(11) NULL';
                $created_fields['admin_modified'] = true;
            }
            $create_fields[] = ' PRIMARY KEY (`'.$_POST['table_primarykey'].'`)';
            $sql = 'CREATE TABLE `'.$mysql->getPrefix().$_POST['table_name'].'` ('.join(',', $create_fields).') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';

            try {
                $stmt = $mysql->prepare($sql);
                $stmt->execute();
            } catch (Exception $e) {
                die("error in the query:$sql<br>".$e);
            }
            foreach ($tbCfgs[$_POST['table_index']]['fields'] as $field) {
                if(isset($field['field_type']) && $field['field_type'] == 'related_multiple'){
                    if($field['related_to_table']){
                        if($mysql->tableExists($field['related_to_table'])){ continue; }
                        $create_fields = [];
                        $create_fields[] = '`'.$field['related_to_table'].'_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT';
                        $create_fields[] = '`'.$field['related_key'].'` int(11) NOT NULL';
                        $create_fields[] = '`'.$field['related_to_mykey'].'` int(11) NOT NULL';
                        $create_fields[] = ' PRIMARY KEY (`'.$field['related_to_table'].'_id`)';
                        $sql = 'CREATE TABLE `'.$mysql->getPrefix().$field['related_to_table'].'` ('.join(',', $create_fields).') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
                        try {
                            $stmt = $mysql->prepare($sql);
                            $stmt->execute();
                        } catch (Exception $e) {
                            die("error in the query:$sql<br>".$e);
                        }
                        $sql = 'ALTER TABLE `'.$mysql->getPrefix().$field['related_to_table'].'` ADD KEY `'.$field['related_key'].'` (`'.$field['related_key'].'`),  ADD KEY `'.$field['related_to_mykey'].'` (`'.$field['related_to_mykey'].'`);';
                        try {
                            $stmt = $mysql->prepare($sql);
                            $stmt->execute();
                        } catch (Exception $e) {
                            die("error in the query:$sql<br>".$e);
                        }
                    }
                }
            }
            $message_popup = 'create database success!';
        }
    } else if($_POST['cnt_btns_btn'] == 'modify_fields' && $curCfg['table_name'] == 'admin_zconfigtable' && $mysql->tableExists($_POST['table_name'])){
        $message_popup = 'modify_fields';
        $default_fields = [];
        foreach (array('table_primarykey', 'table_create_field', 'table_modify_field', 'table_create_admin', 'table_modified_admin', 'parent_related_key') as $v) {
            if($_POST[$v]){
                if(strpos($_POST[$v], ',') !== false){
                    foreach(explode(',', $_POST[$v]) as $vv){
                        $default_fields[trim($vv)] = 1;
                    }
                } else {
                    $default_fields[$_POST[$v]] = 1;
                }
            }
        }

        foreach ($tbCfgs[$_POST['table_index']]['fields'] as $field) {
            $fieldOpts = fieldOpt($field['field_options']);
            if ((isset($fieldOpts['skipsql']) && $fieldOpts['skipsql']) || $field['field_type'] == 'html') {
                continue;
            }
            if(isset($field['field_index']) && $field['field_index']){
                if(!$field['field_type']){
                    echo 'no field type:field_index:'.$field['field_index'];
                    exit;
                }
                $default_fields[$field['field_index']] = $field;
            }
        }

        $exist_fields = [];
        $columns = $mysql->getColumns($_POST['table_name']);
        foreach ($columns as $k => $v) {
            if(count($columns) == $k+1){
                $lastField = $v['Field'];
            }
            $exist_fields[$v['Field']] = $v;
        }
        foreach ($exist_fields as $v => $t) {
            if(!$default_fields[$v]){
                $mysql->prepare('ALTER TABLE `'.$mysql->getPrefix().$_POST['table_name'].'` DROP `'.$v.'`;')->execute();
            }
        }
        foreach ($default_fields as $v => $t) {
            if(!$exist_fields[$v]){
                $exist_fields[$v] = 1;
                $mysql->prepare('ALTER TABLE `'.$mysql->getPrefix().$_POST['table_name'].'` ADD `'.$v.'` '.$data_type->{'dt_'.$t['field_type']}->mysql_field_type(). ' AFTER `'.$lastField.'`')->execute();
                $lastField = $v;
            } else if($t && $t['field_type'] && $v != $_POST['table_primarykey']) {
                $mysql->prepare('ALTER TABLE `'.$mysql->getPrefix().$_POST['table_name'].'` CHANGE `'.$v.'`  `'.$v.'` '.$data_type->{'dt_'.$t['field_type']}->mysql_field_type())->execute();
            }
        }
        $message_popup = 'modify fields success!';
    }
} else if (isset($_POST['form_stage']) && $_POST['form_stage'] == 'form_submit') {
    include_once 'content_validate.php';
    if (isset($formerror) && !count($formerror)) {
        if ($_GET['stage'] == 'create' || $_GET['stage'] == 'duplicate') {
            // create & duplicate permission check
            if(!$login->isAllowPermission($tbCfgs[$t]['admin_zconfigtable_id'], 'allow_'.$_GET['stage']) && $login->getAdminID() != 1){
                echo 'permission denied';
                exit;
            }
            if($_GET['stage'] == 'duplicate' && $_GET['t'] == 'admin_zconfigtable'){
                foreach ($curCfg['fields'] as $key => $field) {
                    if($field['field_name'] == 'List'){
                        unset($curCfg['fields'][$key]);
                    }
                }
            }
            if ($curCfg['table_order_field'] && $curCfg['table_order_type'] == 'order') {
                if($_GET['order_from']){
                    $parent_query = getParentQuery();
                    if (isset($curCfg['table_type']) && $curCfg['table_type'] == 'tree' && isset($curCfg['table_parent_id'])) {
                        $parent_query[$curCfg['table_parent_id']] = $_POST[$curCfg['table_parent_id']];
                    }
                    $order_from = $mysql->getData($curCfg['table_name'], array($curCfg['table_primarykey'] => $_GET['order_from']));
                    $data[$curCfg['table_order_field']] = $order_from[$curCfg['table_order_field']];
                    $order_list = $mysql->getList($curCfg['table_name'], $parent_query, '*', $curCfg['table_order_field'], 'DESC');
                    foreach ($order_list as $oi) {
                        if($oi[$curCfg['table_order_field']] >= $order_from[$curCfg['table_order_field']]){
                            $mysql->update($curCfg['table_name'], array($curCfg['table_primarykey'] => $oi[$curCfg['table_primarykey']]), array($curCfg['table_order_field'] => $oi[$curCfg['table_order_field']]+1));
                        }
                    }
                } else {
                    foreach (explode(',', $curCfg['table_order_field']) as $order_field) {
                        if($order_field == $curCfg['table_primarykey'] || !$order_field){continue;}
                        $parent_query = getParentQuery();
                        
                        // 當 duplicate admin_zconfigtable_fields , 改變 admin_zconfigtable_id
                        if($_GET['stage'] == 'duplicate' && $_GET['t'] == 'admin_zconfigtable_fields' && $_POST['admin_zconfigtable_id']){
                            $parent_query['admin_zconfigtable_id'] = $_POST['admin_zconfigtable_id'];
                        }
                        if (isset($curCfg['table_type']) && $curCfg['table_type'] == 'tree' && isset($curCfg['table_parent_id'])) {
                            $parent_query[$curCfg['table_parent_id']] = $_POST[$curCfg['table_parent_id']];
                        }
                        $lastrecord = $mysql->getData($curCfg['table_name'], $parent_query, '*', $order_field, 'DESC');
                        $lastrecord[$order_field]++;
                        $data[$order_field] = $lastrecord[$order_field];
                    }
                }
                
            }
            if ($curCfg['table_type'] == 'sublist' || (isset($curCfg['parent_related_key']) && $curCfg['parent_related_key'])) {
                $related_keys = explode(',', $curCfg['parent_related_key']);
                foreach ($related_keys as $related_key){
                    $data[$related_key] = $data[$related_key]?$data[$related_key]:$_GET[$related_key];
                }
            }
            if (isset($curCfg['table_create_field']) && $curCfg['table_create_field']) {
                $data[$curCfg['table_create_field']] = $nowTime;
            }
            if (isset($curCfg['table_create_admin']) && $curCfg['table_create_admin']) {
                $data[$curCfg['table_create_admin']] = $login->getAdminID();
            }
            if (isset($curCfg['table_modify_field']) && $curCfg['table_modify_field']) {
                $data[$curCfg['table_modify_field']] = $nowTime;
            }
            if (isset($curCfg['table_modified_admin']) && $curCfg['table_modified_admin']) {
                $data[$curCfg['table_modified_admin']] = $login->getAdminID();
            }
            if($_GET['stage'] == 'duplicate' && $_POST['db_name']){
                if(strpos($_POST['db_name'], '.') !== -1){
                    list($db_name, $prefix) = explode('.', $_POST['db_name']);
                } else {
                    list($db_name, $prefix) = [$_POST['db_name'], $_POST['db_name']];
                }
                $mysql->change_db($db_name, $prefix.'_');
            }
            if($mysql->create($curCfg['table_name'], $data)){
                $_GET[$curCfg['table_primarykey']] = $mysql->cid->lastInsertId();
                $query_data[$curCfg['table_primarykey']] = $mysql->cid->lastInsertId();
                $form_message = get_systext('msg_cre') . " $curCfg[title] " . get_systext('msg_suc') . " :" . date('Y-m-d H:i:s');
            } else {
                $form_message = $mysql->getErrorMsg();
                unset($curCfg['oi']['submit_backlist']);
            }
            if($_GET['stage'] == 'duplicate' && $_POST['db_name']){
                $mysql->change_db($mysqldb, $dbprefix);
            }
        } else if ($_GET['stage'] == 'modify') {
            
            if(!$login->isAllowPermission($tbCfgs[$t]['admin_zconfigtable_id'], 'allow_modify') && $login->getAdminID() != 1){
                echo 'permission denied';
                exit;
            }
            if (isset($curCfg['table_modify_field']) && $curCfg['table_modify_field']) {
                $data[$curCfg['table_modify_field']] = date('Y-m-d H:i:s');
            }
            if (isset($curCfg['table_modified_admin']) && $curCfg['table_modified_admin']) {
                $data[$curCfg['table_modified_admin']] = $login->getAdminID();
            }
            if($data){
                if($mysql->update($curCfg['table_name'], $query_data, $data)){
                    $form_message = get_systext('msg_upd') . " $curCfg[title] " . get_systext('msg_suc') . " :" . date('Y-m-d H:i:s');
                    if (isset($curCfg['table_type']) && $curCfg['table_type'] == 'publish') {
                        $mysql->update($curCfg['table_name'], array($curCfg['table_primarykey'] => $_GET[$curCfg['table_primarykey']]), array('status' => 0));
                    }
                } else {
                    $form_message = $mysql->getErrorMsg();
                    unset($curCfg['oi']['submit_backlist']);
                }
            }
        }
        ob_start();
        //print_r($data);
        $details = ob_get_clean();
        log_admin_activity($_GET['stage'], get_systext($curCfg['title']) . '(' . $_GET[$curCfg['table_primarykey']] . ')', $details);

        // after process data
        foreach ($curCfg['fields'] as $field) {
            
            $fieldOpts = fieldOpt($field['field_options']);
            if (!isset($fieldOpts[$_GET['stage']])) {
                continue;
            }
            if ($_GET['stage'] == 'duplicate') {
                $data_type->{'dt_'.$field['field_type']}->config($field);
                $data_type->{'dt_'.$field['field_type']}->form_after_duplicate();
            }
            if ($_GET['stage'] == 'create' || $_GET['stage'] == 'modify') {
                $data_type->{'dt_'.$field['field_type']}->config($field);
                $data_type->{'dt_'.$field['field_type']}->form_after_submit();
            }
        }
        
        if(!$mysql->getErrorMsg()){
            $_POST = [];
            if($_GET['stage'] == 'create' && isset($curCfg['oi']['continue_create'])){
                $_GET['stage'] = 'create';
            } else {
                $_GET['stage'] = 'modify';
            }
        } else {
            echo $mysql->getErrorMsg();
        }
        if($_GET['tpl'] == 'nomenu'){
            ?>
            <script type="text/javascript">
                window.parent.$.fancybox.close();
                window.parent.location.reload();
            </script><?php
            exit;
        }
        
        if (isset($curCfg['oi']['submit_backlist'])){
            header("location: ".get_link('backlist'));
            exit;
        }
    }
}
if ($_GET['stage'] == 'modify' || $_GET['stage'] == 'details' || $_GET['stage'] == 'duplicate') {
    $modifyInfo = $mysql->getData($curCfg['table_name'], $query_data);
    foreach ($curCfg['fields'] as $field) {
        $fieldOpts = fieldOpt($field['field_options']);
        if (!isset($fieldOpts['modify']) && !isset($fieldOpts['details']) && !isset($fieldOpts['modify_show'])) {
            continue;
        }
        if(isset($modifyInfo[$field['field_index']]) && !isset($_POST[$field['field_index']])){
            $_POST[$field['field_index']] = $modifyInfo[$field['field_index']];
        }
    }
}

if (isset($_GET['stage']) && $_GET['stage'] == 'create') {
    //  process default data
    foreach ($curCfg['fields'] as $field) {
        if (isset($field['field_default']) && $field['field_default']) {
            $_POST[$field['field_index']] = $field['field_default'];
        }
    }
}

if (isset($_GET['stage']) && $_GET['stage'] == 'create' && isset($_GET['default_field']) && $_GET['default_field']) {
    $_POST[$_GET['default_field']] = $_GET['default_value'];
}

if($_GET['t'] == 'admin_zconfigtable' && $_POST['table_name']){
    if($mysql->tableExists($_POST['table_name'])){
        $curCfg['cnt_btns'] = array('modify_fields');
    } else if($_POST['table_type'] != 'setting') {
        $curCfg['cnt_btns'] = array('create_db');
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