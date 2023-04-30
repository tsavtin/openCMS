<?php

class dt_sublist extends data_type {
	private $config;

    public function config($field=false){
        if($field){
            $this->clear_config();
            $this->config = parent::config($field);
            $this->config['related_table'] = isset($field['related_table'])?$field['related_table']:$field[5];
            $this->config['related_key'] = isset($field['related_key'])?$field['related_key']:$field[6];
            $this->config['related_my_key'] = isset($field['related_my_key'])?$field['related_my_key']:$field[7];
            $this->config['allow_delete'] = isset($field['allow_delete'])?$field['allow_delete']:'';
        }
        return $this->config;
    }

	// build list value
	public function list_value($values, $showname = false){
		global $tbCfgs;
		$field = $this->config();
		$rtCfg = $tbCfgs[$field['related_table']];
		$extra_urlpar = [];
		if($rtCfg['extra_urlpar']){
			foreach (explode('&', $rtCfg['extra_urlpar']) as $par) {
				if(!$par){continue;}
				list($pk,$pv) = explode('=', $par);
				$extra_urlpar[$pk] = $pv;
			}
		}
		$phpfile = get_link('sublist', $rtCfg);
		$table = isset($tbCfgs[$field['related_table']]['table_name'])?$tbCfgs[$field['related_table']]['table_name']:$field['related_table'];
		$link = '';
		if(strpos($field['related_key'], ',') ===False){
			if($_GET['t'] == $field['related_table']){
				$key = $field['related_key']?$field['related_key']:$field['related_my_key'];
			} else {
				$key = $field['related_my_key']?$field['related_my_key']:$field['related_key'];
			}
			if($extra_urlpar[$key] != ''){
				$value = $extra_urlpar[$key];
			} else if($values[$key] != ''){
				$value = $values[$key];
			} else if($_GET[$key] != ''){
				$value = $_GET[$key];
			}
			if(!$field['hide_count']){
				$rowCount = ' ('.$this->mysql->rowCount($table, array($field['related_key'] => $value)).')';
			}
			$link = '&'.$field['related_key'].'='.$value;
		} else {
			$query = [];
			foreach (explode(',', $field['related_key']) as $key) {
				if($extra_urlpar[$key] != ''){
					$value = $extra_urlpar[$key];
				} else if($values[$key] != ''){
					$value = $values[$key];
				} else if($_GET[$key] != ''){
					$value = $_GET[$key];
				}
				$query[$key] = $value;
				$link .= '&'.$key.'='.$value;
			}
			if(!$field['hide_count']){
				$rowCount = ' ('.$this->mysql->rowCount($table, $query).')';
			}
		}
		if($field['related_my_key']){
			if($extra_urlpar[$field['related_my_key']] != ''){
				$value = $extra_urlpar[$field['related_my_key']];
			} else if($values[$field['related_my_key']] != ''){
				$value = $values[$field['related_my_key']];
			} else if($_GET[$field['related_my_key']] != ''){
				$value = $_GET[$field['related_my_key']];
			}
			$link .= '&'.$field['related_my_key'].'='.$value;
		}
		if($showname){
			$rowCount = $field['field_name'].$rowCount;
		} else {
			$rowCount = str_replace(array('(', ')'), '', $rowCount);
		}
		if($_GET['t'] == $field['related_table']){
			return ' <a href="javascript:void(0);" style="cursor: default;"><span class="badge bg-red">'.$rowCount.'</span></a>';
		} else {
			return ' <a href="'.$phpfile.'&sublist='.$field['related_table'].$link.'&sl_id='.$field['admin_zconfigtable_fields_id'].'&cc=1"><span class="badge bg-green">'.$rowCount.'</span></a>';
		}
		
	}

	// 處理 duplicate befor submit form
	public function form_after_duplicate(){
		global $_GET, $curCfg, $tbCfgs, $data_type, $dbprefix, $mysqldb;
		global $_POST, $imageConfig, $curCfg, $imageTable, $imagePrimarykey, $admin_imagefolder, $query_data;
		$field = $this->config();
		$fieldOpts = fieldOpt($field['field_options']);
		if(!$fieldOpts['duplicate']){return;}
		$table = isset($tbCfgs[$field['related_table']]['table_name'])?$tbCfgs[$field['related_table']]['table_name']:$field['related_table'];
		$relatedCfgs = $tbCfgs[$field['related_table']];

		$value = $field['related_my_key']?$_GET[$field['related_my_key'].'_from']:$_GET[$field['related_key'].'_from'];
		$primarykey = $relatedCfgs['table_primarykey'];
		$list = $this->mysql->getList($table, array($field['related_key'] => $value));

		foreach ($list as $row) {
			unset($row[$primarykey]);
			$row[$curCfg['table_primarykey']] = $_GET[$curCfg['table_primarykey']];
			if($_GET['stage'] == 'duplicate' && $_POST['db_name']){
                if(strpos($_POST['db_name'], '.') !== -1){
                    list($db_name, $prefix) = explode('.', $_POST['db_name']);
                } else {
                    list($db_name, $prefix) = [$_POST['db_name'], $_POST['db_name']];
                }
                $this->mysql->change_db($db_name, $prefix.'_');
            }
			$this->mysql->create($table, $row);
			$lastSublistId = $this->mysql->cid->lastInsertId();
			if($_GET['stage'] == 'duplicate' && $_POST['db_name']){
                $this->mysql->change_db($mysqldb, $dbprefix);
            }
	        foreach ($relatedCfgs['fields'] as $sublistfield) {
	        	$sublistfieldv = isset($row[$sublistfield['field_index']])?$row[$sublistfield['field_index']]:null;
	        	if($sublistfieldv && $sublistfield['field_type'] == 'image'){
	        		$data_type->{'dt_'.$sublistfield['field_type']}->config($sublistfield);
            		$data_type->{'dt_'.$sublistfield['field_type']}->duplicate_file($sublistfieldv, $table, array($primarykey => $lastSublistId), $sublistfield['field_index']);
	        	}
	        }
		}
	}

	public function delete_record($values){
		global $tbCfgs, $data_type, $imagePrimarykey;
		$field = $this->config();
		if(!$field['allow_delete']){return;}
		$table = isset($tbCfgs[$field['related_table']]['table_name'])?$tbCfgs[$field['related_table']]['table_name']:$field['related_table'];
		$relatedCfgs = $tbCfgs[$table];
		$value = $field['related_my_key']?$values[$field['related_my_key']]:$values[$field['related_key']];


		if($field['related_my_key']){
			$delete_query[$field['related_key']] = $values[$field['related_my_key']];
		} else {
			$extra_urlpar = [];
			$delete_query = [];
			foreach (explode(',', $field['related_key']) as $key) {
				if($extra_urlpar[$key] != ''){
					$value = $extra_urlpar[$key];
				} else if($values[$key] != ''){
					$value = $values[$key];
				} else if($_GET[$key] != ''){
					$value = $_GET[$key];
				}
				$delete_query[$key] = $value;
			}
		}

		$this->mysql->delete($table, $delete_query);
		
		foreach ($relatedCfgs['fields'] as $sublistfield) {
        	$sublistfieldv = isset($row[$sublistfield['field_index']])?$row[$sublistfield['field_index']]:null;
        	if($sublistfieldv && $sublistfield['field_type'] == 'image'){
	            $data_type->{'dt_'.$sublistfield['field_type']}->config($sublistfield);
        		$data_type->{'dt_'.$sublistfield['field_type']}->delete_record(array($imagePrimarykey => $sublistfieldv));
        	}
        }
	}
}
?>