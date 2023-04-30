<?php
class dt_editor extends data_type {

	// build html form field
    public function form_html($value, $formerror, $name= false){
    	$field = parent::config();
    	$error = '';
		if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
			$error = 'parsley-errorr ';
		}
    	return '<textarea class="'.$error.'form-control editor" style="height:200px;" name="'.$field['field_index'].'">'.(isset($value[$field['field_index']])?$value[$field['field_index']]:'').'</textarea>';
    }
    public function mysql_field_type(){
		return 'longtext null';
	}
}
?>