<?php
class dt_password extends data_type {

	// 處理  before form submit
	public function form_validate($value){
		global $encryptKey;
		$field = parent::config();
		$sqlskip = false;
		$data = false;
		$error = false;
		if ($value[$field['field_index']] != $value[$field['field_index'] . '_confirm']) {
            $error = $field['field_name'] . ' ' . get_systext('msg_notmatch');
        } else if (!$value[$field['field_index']]) {
            $sqlskip = true;
        } else {
            $data = md5($value[$field['field_index']] . $encryptKey);
        }
		return array($sqlskip, $data, $error);
	}

	// build html form field
    public function form_html($value, $formerror, $name= false){
    	$field = $this->config();
    	$error = '';
		if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
			$error = 'parsley-errorr ';
		}
		$output = '<input type="password" style="position: absolute; z-index: -1;" disable="true" name="1234123412341234">';
		$output .= '<input type="password" class="'.$error.'form-control" placeholder="'.get_systext($field['field_name']).'" name="'.$field['field_index'].'"></div>';
        $output .= '<label class="control-label col-md-3 col-sm-3 col-xs-12">'.get_systext('field_confirm_password').'</label>';
        $output .= '<div class="col-md-9 col-sm-9 col-xs-12">';
        $output .= '<input type="password" class="'.$error.'form-control" placeholder="'.get_systext('field_confirm_password').'" name="'.$field['field_index'].'_confirm">';
    	return $output;
    }
}
?>