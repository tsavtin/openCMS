<?php
class dt_html extends data_type {
	// build html form field
    public function form_html($value, $formerror, $name= false){
    	$field = parent::config();
    	$field['field_name'] = str_replace('field_index_val', $field['field_index'], $field['field_name']);
    	$field['field_name'] = str_replace('id_val', $value[$this->config_table['table_primarykey']], $field['field_name']);
    	return $field['field_name'];
    }
    public function mysql_field_type(){
		return 'text null';
	}
	public function form_validate($value){
		$sqlskip = true;
		$data = false;
		$error = false;
		return array($sqlskip, $data, $error);
	}
}
?>