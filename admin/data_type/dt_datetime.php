<?php
class dt_datetime extends data_type {
	public function mysql_field_type(){
        return 'datetime null';
    }
    // build html list filter
	public function filter_html($value){
		$field = $this->config();
		$field['date_format'] = $field['date_format']?$field['date_format']:'Y-m-d H:i:s';
		if($value == '0000-00-00 00:00:00'){
			$value = '';
		}

		$values = explode(',', $value);
		$output = '<input autocomplete="off" style="width:120px;" name="'.$this->get_index().'_start" size="10"
            class="form-control input-sm datetimepicker"
            placeholder="'.$this->get_systext().'(開始)"
            value="'.$values[0].'"><input style="width:120px;" name="'.$this->get_index().'_end" size="10"
            class="form-control input-sm datetimepicker"
            placeholder="'.$this->get_systext().'(結束)"
            value="'.$values[1].'">'."<script>var datepicker_format = \"".$field['date_format']."\";</script>";
		return $output;
	}
    public function form_html($value, $formerror, $name= false){
		$field = $this->config();
		if($value[$field['field_index']] == '0000-00-00 00:00:00'){
			$value[$field['field_index']] = '';
		}
		return parent::form_html($value, $formerror, $name);
	}
}
?>