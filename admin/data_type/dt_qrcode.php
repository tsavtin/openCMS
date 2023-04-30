<?php
class dt_qrcode extends data_type {

	// build html form field
	public function form_html($value, $formerror, $name= false){
		$field = $this->config();
		$error = '';
		if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
			$error = 'parsley-errorr ';
		}
		$datetime = '';
		if ($field['field_type'] == 'date' || $field['field_type'] == 'datetime' || $field['field_type'] == 'time') {
			$datetime = $field['field_type'].'picker';
		}
		$type = 'text';
		$default_value = $this->get_default_value();
		$output = '<input type="'.$type.'" class="'.$error.'form-control '.$datetime.'"
           placeholder="'.strip_tags(get_systext($field['field_name'])).'" style="width: 100%;"
           name="'.($name? $name: $field['field_index']).'" value="'.(isset($value[$field['field_index']])?htmlspecialchars($value[$field['field_index']]):$default_value).'">';
        return $output;
	}
}
?>