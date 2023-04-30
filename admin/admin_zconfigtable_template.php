<?php

include_once 'includes/config.php';

if($login->getAdminID() != 1){
    echo 'permission denied';
    exit;
}

if (isset($_POST['form_stage']) && $_POST['form_stage'] == 'form_submit') {
	include_once 'content_validate.php';

	
	if (!count($formerror) && $_POST['cnt_btns_btn'] == 'btn_build_tableconfig') {
        $fields = [];
        if($_POST['table_order_type'] == 'order'){
            $table_order_field = 'sort_order';
            $table_order_default_direction = 'ASC';
        } else if($_POST['table_order_type'] == 'date'){
            $table_order_field = 'sort_order';
            $table_order_default_direction = 'DESC';
        } else if($_POST['table_order_type'] == 'id'){
            $table_order_field = $_POST['table_index'].'_id';
            $table_order_default_direction = 'DESC';
        }
        $langs = [];
        if($_POST['language'] && ($_POST['images_language'] || $_POST['videos_language'] || $_POST['files_language'] || $_POST['text_language'])){
            $lang_defindex = false;
            $tabs = [];
            foreach (json_decode($_POST['language'], true) as $lang) {
                if(!$lang_defindex){$lang_defindex = $lang[1];}
                $langs[$lang[1]] = $lang[0];
                $tabs[] = array('langtab_'.$lang[1], $lang[0]);
            }
            $fields[] = array(
                'field_index' => '',
                'field_name' => '',
                'field_default' => 'langtab_'.$lang_defindex,
                'field_type' => 'tabs',
                'field_options' => 'modify,create,duplicate',
                'extra_opt' => json_encode($tabs)
            );
            foreach ($langs as $key => $lang) {
                $fields[] = array(
                    'field_index' => '',
                    'field_name' => '<div class="langtab_'.$key.'">',
                    'field_type' => 'html',
                    'field_options' => 'modify,create,duplicate'
                );
                if($_POST['images_language']){
                    foreach (json_decode($_POST['images_language'], true) as $image) {
                        $opts = str_getcsv($image[2], ',', "'");
                        $imgopts = [];
                        $imgopts[0] = $opts[0];
                        $imgopts[1] = $opts[1];
                        $imgopts[2] = $opts[2];
                        $imgopts[5] = $opts[3];
                        $imgopts[4] = $opts[5];
                        $imgopts[3] = $opts[4];
                        $fields[] = array(
                            'field_index' => $image[0].'_'.$key,
                            'field_name' => $image[1].' '.$lang,
                            'field_type' => 'image',
                            'field_options' => 'modify,create,duplicate'.($image[3]?',list':''),
                            'extra_opt' => json_encode(array($imgopts))
                        );
                    }
                }
                if($_POST['videos_language']){
                    foreach (json_decode($_POST['videos_none_language'], true) as $video) {
                        $opts = str_getcsv($video[2], ',', "'");
                        $vidopts = [];
                        $vidopts[0] = $opts[1];
                        $vidopts[1] = $opts[0];
                        $fields[] = array(
                            'field_index' => $video[0].'_'.$key,
                            'field_name' => $video[1].' '.$lang,
                            'field_type' => 'video',
                            'field_options' => 'modify,create,duplicate'.($video[3]?',list':''),
                            'extra_opt' => json_encode(array($vidopts))
                        );
                    }
                }
                if($_POST['files_language']){
                    foreach (json_decode($_POST['files_none_language'], true) as $file) {
                        $opts = str_getcsv($file[2], ',', "'");
                        $fleopts = [];
                        $fleopts[0] = $opts[1];
                        $fleopts[1] = $opts[0];
                        $fields[] = array(
                            'field_index' => $file[0].'_'.$key,
                            'field_name' => $file[1].' '.$lang,
                            'field_type' => 'file',
                            'field_options' => 'modify,create,duplicate'.($file[3]?',list':''),
                            'extra_opt' => json_encode(array($fleopts))
                        );
                    }
                    
                }
                if($_POST['text_language']){
                    foreach (json_decode($_POST['text_language'], true) as $text){
                        $opts = str_getcsv($text[2], ',', "'");
                        $fields[] = array(
                            'field_index' => $text[0].'_'.$key,
                            'field_name' => $text[1].' '.$lang,
                            'field_type' => $text[2],
                            'field_options' => 'modify,create,duplicate'.($text[3]?',list':'')
                        );
                    }
                }
                $fields[] = array(
                    'field_index' => '',
                    'field_name' => '</div>',
                    'field_type' => 'html',
                    'field_options' => 'modify,create,duplicate'
                );
            }

        }
        foreach (@json_decode($_POST['images_none_language'], true) as $image) {
            $opts = str_getcsv($image[2], ',', "'");
            $imgopts = [];
            $imgopts[0] = $opts[0];
            $imgopts[1] = $opts[1];
            $imgopts[2] = $opts[2];
            $imgopts[5] = $opts[3];
            $imgopts[4] = $opts[5];
            $imgopts[3] = $opts[4];
            $fields[] = array(
                'field_index' => $image[0],
                'field_name' => $image[1],
                'field_type' => 'image',
                'field_options' => 'modify,create,duplicate'.($image[3]?',list':''),
                'extra_opt' => json_encode(array($imgopts))
            );
        }
        foreach (@json_decode($_POST['videos_none_language'], true) as $video) {
            $opts = str_getcsv($video[2], ',', "'");
            $vidopts = [];
            $vidopts[0] = $opts[0];
            $vidopts[1] = $opts[1];
            $fields[] = array(
                'field_index' => $video[0],
                'field_name' => $video[1],
                'field_type' => 'video',
                'field_options' => 'modify,create,duplicate'.($video[3]?',list':''),
                'extra_opt' => json_encode(array($vidopts))
            );
        }
        foreach (@json_decode($_POST['files_none_language'], true) as $file) {
            $opts = str_getcsv($file[2], ',', "'");
            $fleopts = [];
            $fleopts[0] = $opts[1];
            $fleopts[1] = $opts[2];

            $fields[] = array(
                'field_index' => $file[0],
                'field_name' => $file[1],
                'field_type' => 'file',
                'field_options' => 'modify,create,duplicate'.($file[3]?',list':''),
                'extra_opt' => json_encode(array($fleopts))
            );
        }
        foreach (@json_decode($_POST['text_none_language'], true) as $text){
            $opts = str_getcsv($text[2], ',', "'");
            $fields[] = array(
                'field_index' => $text[0],
                'field_name' => $text[1]?$text[1]:$text[0],
                'field_type' => $text[2]?$text[2]:'text',
                'field_options' => 'modify,create,duplicate'.($text[3]?',list':'')
            );
        }

        if($_POST['table_order_type'] == 'order'){
            $fields[] = array(
                'field_index' => 'sort_order',
                'field_name' => '排序',
                'field_type' => 'int',
                'field_options' => 'list,sorting,duplicate',
                'list_width' => '100px',
                'date_added' => $nowTime
            );
        } else if($_POST['table_order_type'] == 'date'){
            $fields[] = array(
                'field_index' => 'show_date',
                'field_name' => '日期',
                'field_type' => 'date',
                'field_options' => 'list,sorting,duplicate',
                'list_width' => '100px',
                'date_added' => $nowTime
            );
        }

        // else if($_POST['table_order_type'] == 'id'){
        //     $fields[] = array(
        //         'field_index' => $_POST['table_index'].'_id',
        //         'field_name' => '#ID',
        //         'field_type' => 'int',
        //         'field_options' => '',
        //         'list_width' => '100px',
        //         'date_added' => $nowTime
        //     );
        // }

        $fields[] = array(
            'field_index' => 'status',
            'field_name' => '啟用',
            'field_type' => 'onoff',
            'field_default' => 1,
            'field_options' => 'modify,create,duplicate,list'
        );
        // print_r(json_decode('a:1:{i:0;a:7:{i:0;s:6:"resize";i:1;s:4:"2000";i:2;s:4:"2000";i:3;s:0:"";i:4;s:2:"10";i:5;s:9:".jpg,.png";i:6;s:1:"0";}}'));
        // exit;

        $newData = array(
            'config_type' => 1,
            'perpage_nums' => 30,
            'table_index' => $_POST['table_index'],
            'table_title' => $_POST['table_title'],
            'table_name' => $_POST['table_index'],
            'table_primarykey' => $_POST['table_index'].'_id',
            'table_order_field' => $table_order_field,
            'table_order_default_direction' => $table_order_default_direction,
            'table_order_type' => $_POST['table_order_type'],
            'listwidth_class' => 'col-md-12',
            'table_option' => 'allow_list,allow_create,allow_delete,allow_modify',
            'status' => 1,
            'date_added' => $nowTime,
            'language' => $_POST['language']
        );

        $mysql->create('admin_zconfigtable', $newData);
        $admin_zconfigtable_id = $mysql->lastInsertId();

        $order = 1;
        foreach ($fields as $key => $field) {
            $field['list_order'] = $field['sort_order'] = $order++;
            $field['date_added'] = $nowTime;
            $field['admin_zconfigtable_id'] = $admin_zconfigtable_id;
            $mysql->create('admin_zconfigtable_fields', $field);
            $field['admin_zconfigtable_fields_id'] = $mysql->lastInsertId();
            if($field['field_index'] == 'status'){
                $mysql->update('admin_zconfigtable', array('admin_zconfigtable_id' => $admin_zconfigtable_id), array('list_update' => $field['admin_zconfigtable_fields_id']));
            }
            $fields[$key] = $field;
        }

    }
    if(!$_POST['saveas_template'] && ($_GET['stage'] == 'create' || $_GET['stage'] == 'duplicate')){
    	unset($_POST['form_stage']);
    }
}



include 'content.php';


?>