<?php
class dt_token extends data_type {
	// 處理  before form submit
	// build html form field
	public function get_default_value(){
		global $curCfg, $mysql;
		$field = $this->config();
		$default_value = isset($field['field_default']) ? $field['field_default'] : null;
		$count = 0;
		if($_GET['stage'] == 'create'){
			if(isset($fieldOpts['unique']) && !$default_value){
				while(!$default_value || $mysql->rowCount($curCfg['table_name'], array($field['field_index'] => $default_value))){
					$count++;
					if($count > 10000){
						break;
					}
					$default_value = rand(1099999,9999999);
				}
			} else {
				$default_value = rand(1099999,9999999);
			}
		}
		return $default_value;
	}
}
?>