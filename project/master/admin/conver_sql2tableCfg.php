<?php

foreach ($curCfg['fields'] as $pos => $field) {
	if($field['field_index'] == 'import_database'){
		$stmt = $mysql->prepare('SHOW DATABASES');
    	$stmt->execute();
    	foreach ($stmt->fetchAll() as $database) {
    		if(in_array($database['Database'], array('information_schema', 'mysql', 'performance_schema'))){continue;}
    		$field['extra_opt'][] = array($database['Database']);
    	}
	} else if($field['field_index'] == 'import_tables' && $_POST['import_database']){
		$stmt = $mysql->prepare('SHOW TABLES FROM '.$_POST['import_database']);
	    $stmt->execute();
	    foreach ($stmt->fetchAll() as $table){
	    	$field['extra_opt'][] = array($table['Tables_in_'.$_POST['import_database']]);
	    }
	}
	$curCfg['fields'][$pos] = $field;
	$curCfg['fbi'][$field['field_index']] = $field;
}

if($_POST['form_stage'] == 'form_submit' && $_POST['import_database']){
	$tables = array();
	foreach ($_POST as $key => $value) {
		if(substr($key, 0, strlen('import_tables_')) == 'import_tables_'){
			$tables[] = str_replace('import_tables_', '', $key);
		}
	}
	function field_type($type){
		if(stripos($type, 'int') !== false){
			return 'int';
		} else if(stripos($type, 'text') !== false){
			return 'textarea';
		} else if(stripos($type, 'datetime') !== false){
			return 'datetime';
		} else if(stripos($type, 'date') !== false){
			return 'date';
		} else {
			return 'text';
		}
	}
	foreach ($tables as $table) {
		$stmt = $mysql->prepare("SHOW COLUMNS FROM ".$_POST['import_database'].".$table");
        $stmt->execute();
        if(substr($table, 0, strlen($_POST['import_database'].'_')) == $_POST['import_database'].'_'){
        	$table = str_replace($_POST['import_database'].'_', '', $table);
        }
        $newData = array(
            'config_type' => 1,
            'perpage_nums' => 30,
            'table_index' => $table,
            'table_title' => $table,
            'table_name' => $table,
            'table_primarykey' => $table.'_id',
            'table_order_field' => $table.'_id',
            'table_order_default_direction' => 'ASC',
            'listwidth_class' => 'col-md-12',
            'table_option' => 'allow_list,allow_create,allow_delete,allow_modify',
            'status' => 1,
            'date_added' => $nowTime
        );

        $mysql->create('admin_zconfigtable', $newData);
        $admin_zconfigtable_id = $mysql->lastInsertId();
        $order = 1;
        foreach ($stmt->fetchAll() as $v) {
            $mysql->create('admin_zconfigtable_fields', array(
	            'field_index' => $v['Field'],
	            'field_name' => $v['Field'],
	            'field_type' => field_type($v['Type']),
	            'field_options' => 'modify,create,list',
	            'list_order' => $order,
	            'sort_order' => $order,
	            'date_added' => $nowTime,
	            'admin_zconfigtable_id' =>  $admin_zconfigtable_id
	        ));
            $order++;
        }

        foreach(['admin_added' => 'int', 'admin_modified' => 'int', 'date_added' => 'datetime', 'date_modified' => 'datetime', 'status' => 'onoff'] as $Field => $Type){
	        $mysql->create('admin_zconfigtable_fields', array(
	            'field_index' => $Field,
	            'field_name' => $Field,
	            'field_type' => $Type,
	            'field_options' => 'modify,create,list',
	            'list_order' => $order,
	            'sort_order' => $order,
	            'date_added' => $nowTime,
	            'admin_zconfigtable_id' =>  $admin_zconfigtable_id
	        ));
	        $order++;
        }
	        
	}
}

include 'setting.php';
?>