<?php 

include_once 'includes/config.php';

if(isset($_FILES['files'])){
    $files = [];
    foreach ($_FILES['files']['name'] as $key => $value) {
        $files[] = array(
            'name' => $_FILES['files']['name'][$key],
            'type' => $_FILES['files']['type'][$key],
            'tmp_name' => $_FILES['files']['tmp_name'][$key],
            'error' => $_FILES['files']['error'][$key],
            'size' => $_FILES['files']['size'][$key]
        );
    }

    foreach ($files as $file){
        $_FILES[$curCfg['image_field']] = $file;
        $data = [];
        if ($curCfg['table_order_field'] && $curCfg['table_order_type'] == 'order') {
            foreach (explode(',', $curCfg['table_order_field']) as $order_field) {
                if($order_field == $curCfg['table_primarykey']){continue;}
                    $parent_query = getParentQuery();
                if (isset($curCfg['table_type']) && $curCfg['table_type'] == 'tree' && isset($curCfg['table_parent_id'])) {
                    $parent_query[$curCfg['table_parent_id']] = $_POST[$curCfg['table_parent_id']];
                }
                $lastData = $mysql->getData($curCfg['table_name'], $parent_query, '*', array($order_field), 'DESC');
                $lastData[$order_field]++;
                $data[$order_field] = $lastData[$order_field];
            }
        }
        if ($curCfg['table_type'] == 'sublist' || $curCfg['table_type'] == 'imagelist' || (isset($curCfg['parent_related_key']) && $curCfg['parent_related_key'])) {
            $keys = explode(',', $curCfg['parent_related_key']);
            foreach ($keys as $rk) {
                $data[$rk] = $_GET[$rk];
            }
        }
        if (isset($curCfg['table_create_field']) && $curCfg['table_create_field']) {
            $data[$curCfg['table_create_field']] = date('Y-m-d H:i:s');
        }
        if(isset($curCfg['fbi']['status'])){
            $data['status'] = 1;
        }
        $mysql->create($curCfg['table_name'], $data);
        $_GET[$curCfg['table_primarykey']] = $mysql->cid->lastInsertId();
        $query_data[$curCfg['table_primarykey']] = $mysql->cid->lastInsertId();
        $form_message = get_systext('msg_cre') . " $curCfg[title] " . get_systext('msg_suc') . " :" . date('Y-m-d H:i:s');

        // after process data
        foreach ($curCfg['fields'] as $field) {
            $data_type->{'dt_'.$field['field_type']}->config($field);
            $data_type->{'dt_'.$field['field_type']}->form_after_submit();
        }
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

include_once 'list.php';

?>