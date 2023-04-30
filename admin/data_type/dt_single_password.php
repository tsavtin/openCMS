<?php
class dt_single_password extends data_type {

	// build html form field
	public function form_html($value, $formerror, $name= false){
		$field = parent::config();
		$error = '';
		if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
			$error = 'parsley-errorr ';
		}
		$output = '<input type="password" class="'.$error.'form-control "
           placeholder="'.strip_tags(get_systext($field['field_name'])).'" style="width: 100%;"
           name="'.($name? $name: $field['field_index']).'">';
        return $output;
	}
}
?>