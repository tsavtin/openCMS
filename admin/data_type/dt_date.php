<?php
class dt_date extends data_type {
	// build html list filter
	public function filter_html($value){
		$field = $this->config();
		$field['date_format'] = $field['date_format']?$field['date_format']:'Y-m-d';
		if($value == '0000-00-00'){
			$value = '';
		}

		$values = explode(',', $value);
		$output = '<input autocomplete="off" style="width:120px;" name="'.$this->get_index().'_start" size="10"
            class="form-control input-sm datepicker"
            placeholder="'.$this->get_systext().'(開始)"
            value="'.$values[0].'"><input style="width:120px;" name="'.$this->get_index().'_end" size="10"
            class="form-control input-sm datepicker"
            placeholder="'.$this->get_systext().'(結束)"
            value="'.$values[1].'">'."<script>var datepicker_format = \"".$field['date_format']."\";</script>";
		return $output;
	}
	// 處理  before form submit
	public function form_validate($value){
		list($sqlskip, $data, $error) = parent::form_validate($value);
		$field = $this->config();
		$field['date_format'] = $field['date_format']?$field['date_format']:'Y-m-d';
		if($value[$field['field_index']]){
			$data = str_replace('/', '-', $value[$field['field_index']]);
			$data = date('Y-m-d', strtotime($data));
		}
		// echo strtotime($value[$field['field_index']]);
		// echo $value[$field['field_index']];
		// echo '<br>';
		return array($sqlskip, $data, $error);
	}
	public function form_html($value, $formerror, $name= false){
		$field = $this->config();
		$field['date_format'] = $field['date_format']?$field['date_format']:'Y-m-d';
		if($value[$field['field_index']] == '0000-00-00'){
			$value[$field['field_index']] = '';
		}
		if($value[$field['field_index']]){
			$value[$field['field_index']] = date(isset($field['date_format'])&&$field['date_format']?$field['date_format']:'Y-m-d', strtotime($value[$field['field_index']])); 
		}
		return parent::form_html($value, $formerror, $name)."<script>var datepicker_format = \"".$field['date_format']."\";</script>";
	}
	public function mysql_field_type(){
        return 'date null';
    }

    // build list value
	public function list_value($values){
		$field = $this->config();
		if($values[$field['field_index']] == '0000-00-00'){
			$values[$field['field_index']] = '';
		}
		if($values[$field['field_index']]){
			$values[$field['field_index']] = date(isset($field['date_format'])&&$field['date_format']?$field['date_format']:'Y-m-d', strtotime($values[$field['field_index']])); 
		}
		return htmlspecialchars($values[$field['field_index']]);
	}
}
?>