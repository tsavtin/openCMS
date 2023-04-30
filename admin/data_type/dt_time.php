<?php

class dt_time extends data_type {
	// build html list filter
	public function form_html($value, $formerror, $name= false){
		global $data_type;
		$field = $this->config();
		$curTime = strtotime(date('Y-m-d').' 00:00:00');
		$p = $field['extra_opt'][0][0];

		$field['extra_opt'] = array();
		for ($i=0; $i < 1440; $i+=$p) { 
			$field['extra_opt'][] = array(date('A h:i:s', $curTime+$i*60));
		}
		$field['extra_opt'][] = array(date('A h:i:s', $curTime));
		// print_r($field);
		$data_type->{'dt_select'}->config($field);
		return $data_type->{'dt_select'}->form_html($value, $formerror);
	}
}

?>