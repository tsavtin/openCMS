<?php
class dt_select extends data_type {
	private $config;
    public function config($field=false){
        if($field){
            $this->clear_config();
            $this->config = parent::config($field);
            $this->config['select_option'] = isset($field['select_option'])?$field['select_option']:false;
        }
        return $this->config;
    }
	public function filter_html($value){
        global $selectconfig, $selected_lang;
    	$field = $this->config();
    	$error = '';
		if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
			$error = 'parsley-errorr ';
		}
    	$output = '<select class="'.$error.'form-control" name="'.$this->get_index().'">';
		$output .= '<option value="">'.$this->get_systext().':ALL'.'</option>';

		if(isset($selectconfig[$this->config_table['table_name'] . '.' . $this->get_index()])) {
			foreach ($selectconfig[$this->config_table['table_name'] . '.' . $this->get_index()] as $k => $v) {
				$output .= '<option'.(isset($value) && "$k" == $value ? ' selected' : '').' value="'.$k.'">'.$v.'</option>';
			}
		} else if($field['extra_opt']){
			foreach ($field['extra_opt'] as $a) {
				$v = $a[0];
				if($a[1] && $a[2] && $selected_lang){
					if($selected_lang == 'en'){
						$name = $a[0];
					} else if($selected_lang == 'tc'){
						$name = $a[1];
					} else if($selected_lang == 'sc'){
						$name = $a[2];
					}
				} else {
					$name = $a[0];
				}
				$k = $a[1]?$a[1]:$a[0];
				$output .= '<option'.(isset($value) && "$v" == $value ? ' selected' : '').' value="'.$k.'">'.$name.'</option>';
			}
		}
        $output .= '</select>';
        return $output;
	}

	// build list value
	public function list_update_field($value, $formerror, $primaryid){
		$field = $this->config();
		return $this->form_html($value, $formerror, $field['field_index'].'_'.$primaryid);
	}

	// build list update value
    public function list_update_value($values, $primaryid){
        $field = $this->config();
        return $values[$field['field_index']. '_' . $primaryid];
    }

	// build list value
	public function list_value($values){
		global $selectconfig, $selected_lang;
		$field = $this->config();

		if(isset($selectconfig[$this->config_table['table_name'] . '.' . $this->get_index()])) {

			return htmlspecialchars(isset($selectconfig[$this->config_table['table_name'] . '.' . $this->get_index()][$values[$this->get_index()]])?$selectconfig[$this->config_table['table_name'] . '.' . $this->get_index()][$values[$this->get_index()]]:null);
		} else if($field['extra_opt']){
			foreach ($field['extra_opt'] as $key => $a) {
				if($a[1] && $a[2] && $selected_lang){
					if($selected_lang == 'en'){
						$name = $a[0];
					} else if($selected_lang == 'tc'){
						$name = $a[1];
					} else if($selected_lang == 'sc'){
						$name = $a[2];
					}
					if("$key" == $values[$field['field_index']]){
						return htmlspecialchars($name);
					}
				} else if($a[1]) {
					$v = $a[1];
					if("$v" == $values[$field['field_index']]){
						return htmlspecialchars($a[0]);
					}
				} else {
					$v = $a[0];
					if("$v" == $values[$field['field_index']]){
						return htmlspecialchars($v);
					}
				}
			}
		} else {
			return htmlspecialchars($values[$this->get_index()]);
		}
	}

	public function form_details($values){
		return $this->list_value($values);
	}
	public function filter_name($str){
		return str_replace(array('-', ' '), '', $str);
	}
	// build html form field
    public function form_html($value, $formerror, $name= false){
        global $selectconfig, $selected_lang;
    	$field = $this->config();
    	$error = '';
		if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
			$error = 'parsley-errorr ';
		}
		$onchange = '';
		$script = '';
		if(isset($field['select_option']) && $field['select_option']){
			$default = isset($value[$field['field_index']])?'$(".'.$this->filter_name($field['field_index']).'_'.$this->filter_name($value[$field['field_index']]).'").show();':'';
			$default .= isset($value[$field['field_index']])?'$(".'.$this->filter_name($field['field_index']).'_'.$this->filter_name($value[$field['field_index']]).'").find(\'input,select,textarea\').prop("disabled", false);':'';
			$onchange = 'onchange="'.$this->filter_name($field['field_index']).'_rtdselect(\''.$this->filter_name($field['select_key']?$field['select_key']:$field['select_option']).'\', this)"';
			$script = '<script>
			function '.$this->filter_name($field['field_index']).'_rtdselect(tag, obj){
				$(".'.$this->filter_name($field['field_index']).'_rtdselect").hide();
				$(".'.$this->filter_name($field['field_index']).'_rtdselect").find(\'input,select,textarea\').prop("disabled", true);
				$(".'.$this->filter_name($field['field_index']).'_"+$(obj).val()).show();
				$(".'.$this->filter_name($field['field_index']).'_"+$(obj).val()).find(\'input,select,textarea\').prop("disabled", false);
			}
			$(document).ready(function () {
				$(".'.$this->filter_name($field['field_index']).'_rtdselect").hide();
				$(".'.$this->filter_name($field['field_index']).'_rtdselect").find(\'input,select,textarea\').prop("disabled", true);
				'.$default.'
			});
			</script>';
		}
    	$output = '<select class="'.$error.'form-control" name="'.($name?$name:$this->get_index()).'" '.$onchange.'>';
		$output .= '<option value=""></option>';
		if(isset($selectconfig[$this->config_table['table_name'] . '.' . $this->get_index()])) {
			foreach ($selectconfig[$this->config_table['table_name'] . '.' . $this->get_index()] as $k => $v) {
				$output .= '<option'.(isset($value[$field['field_index']]) && "$k" == $value[$field['field_index']] ? ' selected' : '').' value="'.$k.'">'.$v.'</option>';
			}
		} else if($field['extra_opt']){
			foreach ($field['extra_opt'] as $key => $a) {
				if($a[1] && $a[2] && $selected_lang){
					if($selected_lang == 'en'){
						$name = $a[0];
					} else if($selected_lang == 'tc'){
						$name = $a[1];
					} else if($selected_lang == 'sc'){
						$name = $a[2];
					}
					$output .= '<option'.(isset($value[$field['field_index']]) && "$key" == $value[$field['field_index']] ? ' selected' : '').' value="'.$key.'">'.$name.'</option>';
				} else {
					$k = $a[1]!=''?$a[1]:$a[0];
					$v = $a[0];
					$output .= '<option'.(isset($value[$field['field_index']]) && "$k" == $value[$field['field_index']] ? ' selected' : '').' value="'.$k.'">'.$v.'</option>';
				}
			}
		}
		
        $output .= '</select>';
        $output .= $script;
        return $output;
    }
}
?>