<?php
class dt_onoff extends data_type {

	public function mysql_field_type(){
        return 'tinyint(3) null';
    }

	// build html list filter
	public function filter_html($value){
		$output = '<select class="form-control" name="'.$this->get_index().'">';
		$output .= '<option value="">'.$this->get_systext().':'.get_systext('filter_all').'</option>';
		$output .= '<option'.($value === 1 || $value === '1' ? ' selected' : '').' value="1">'.$this->get_systext().':'.get_systext('list_onoff_on').'</option>';
		$output .= '<option'.($value === 0 || $value === '0' ? ' selected' : '').' value="0">'.$this->get_systext().':'.get_systext('list_onoff_off').'</option>';
        $output .= '</select>';
        return $output;
	}

	// build list value
	public function list_value($value){
		return $value[$this->get_index()] ? get_systext('list_onoff_on') : get_systext('list_onoff_off');
	}
	public function form_details($value){
        return $this->list_value($value);
    }

	public function list_update_field($value, $formerror, $primaryid){
		$field = $this->config();
    	$checked = '';
    	if (isset($value[$field['field_index']]) && $value[$field['field_index']] == 1){
    		$checked = 'checked';
    	}
    	return '<input type="checkbox" class="flat formicheck" name="'.$field['field_index'].'_'.$primaryid.'" '.$checked.' value="1">';
	}

	// 處理  before form submit
	public function form_validate($value){
		$field = parent::config();
		$sqlskip = false;
		$data = false;
		$error = false;
		if(!isset($value[$field['field_index']]) ||  $value[$field['field_index']] != 1){
			$data = 0;
		} else {
			$data = 1;
		}
		return array($sqlskip, $data, $error);
	}

	// build html form field
    public function form_html($value, $formerror, $name= false){
    	$field = $this->config();
    	$fieldOpts = fieldOpt($field['field_options']);
    	$checked = '';
    	if (isset($_POST[$field['field_index']]) && $_POST[$field['field_index']] == 1){
    		$checked = 'checked';
    	}
    	return '<div style="padding-top:8px;"></div><input type="checkbox" '.(isset($fieldOpts['modify_readonly'])?' disabled="true" ':'').' name="'.$field['field_index'].'" '.$checked.' value="1" id="'.$field['field_index'].'"><label for="'.$field['field_index'].'" class="icon"></label>';
    }
}
?>