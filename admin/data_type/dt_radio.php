<?php
class dt_radio extends data_type {
	private $config;

	public function config($field=false){
        if($field){
            $this->clear_config();
            $this->config = parent::config($field);
            $this->config['radio_option'] = isset($field['radio_option'])?$field['radio_option']:false;
        }
        return $this->config;
    }

	// build list value
	public function list_value($values){
		global $selected_lang;
		$field = $this->config();

		if($field['extra_opt']){
			foreach ($field['extra_opt'] as $key => $a) {
				if($a[1] && $a[2] && $selected_lang){
					if($selected_lang == 'en'){
						$name = get_systext($a[0]);
					} else if($selected_lang == 'tc'){
						$name = get_systext($a[1]);
					} else if($selected_lang == 'sc'){
						$name = get_systext($a[2]);
					}
					if("$key" == $values[$field['field_index']]){
						return get_systext($name);
					}
				} else {
					$v = $a[0];
					$key = $a[1]!==''?$a[1]:$key;
					if("$key" == $values[$field['field_index']]){
						return get_systext($v);
					}
				}
			}
		}
	}
	public function filter_html($value){
        global $selected_lang;
    	$field = $this->config();
    	$error = '';
		if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
			$error = 'parsley-errorr ';
		}
    	$output = '<select class="'.$error.'form-control" name="'.$this->get_index().'">';
		$output .= '<option value="">'.$this->get_systext().':ALL'.'</option>';

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
			$k = $a[1]!==''?$a[1]:$a[0];
			$output .= '<option'.(isset($value) && "$k" == $value ? ' selected' : '').' value="'.$k.'">'.$name.'</option>';
		}
        $output .= '</select>';
        return $output;
	}


	// build list value
	public function list_update_field($value, $formerror, $primaryid){
		$field = $this->config();
		return $this->form_html($value, $formerror, $field['field_index'].'_'.$primaryid);
	}

	// build html form field
    public function form_html($value, $formerror, $name= false){
        global $selected_lang;
        $prefix = $name;
    	$field = parent::config();
    	$fieldOpts = fieldOpt($field['field_options']);
    	$default_value = $this->get_default_value();
    	$checkeds = [];
    	$error = '';
    	$output = '<div style="clear:both; margin-top:8px;"></div>';
		if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
			$error = 'parsley-errorr ';
		}
		$chs = isset($value[$field['field_index']])?explode(',', $value[$field['field_index']]):[];
		foreach ($chs as $index) {
			$checkeds[$index] = 1;
		}
		if($field['extra_opt']){
			foreach ($field['extra_opt'] as $key => $a) {
				if($a[1] && $a[2] && $selected_lang){
					if($selected_lang == 'en'){
						$name = $a[0];
					} else if($selected_lang == 'tc'){
						$name = $a[1];
					} else if($selected_lang == 'sc'){
						$name = $a[2];
					}
					$checkindex = $key;
				} else {
					$name = $a[0];
					$checkindex = $a[0];
				}
				$key = $a[1]!==''?$a[1]:$key;
				$name = get_systext($name);
				$checked = '';

				if("$key" == (isset($value[$field['field_index']])?$value[$field['field_index']]:"$default_value")){
					$checked = 'checked';
				}
				
				$output .= '<p class="help-block" style="float:left;margin:0px;margin-right:10px;">';
				$output .= '<input type="radio" '.(isset($fieldOpts['modify_readonly'])?' disabled="true" ':'').' '.$checked.'
	                       name="'.($prefix?$prefix:$field['field_index']).'"
	                       id="'.($prefix?$prefix:$field['field_index']). '_' . $checkindex.'" value="'.$key.'">';
	            $output .= '<label for="'.($prefix?$prefix:$field['field_index']). '_' . $checkindex.'" class="icon"></label><label for="'.($prefix?$prefix:$field['field_index']). '_' . $checkindex.'"> &nbsp;'.$name.'</label></p>';
			}
		}
		
        return $output;
    }
}
?>