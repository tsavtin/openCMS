<?php
class dt_serialize extends data_type {
    private $config;
    public function config($field=false){
        if($field){
            $this->clear_config();
            $this->config = parent::config($field);
            $this->config['field_default'] = $field['field_default'];
            $this->config['fields_cfg'] = isset($field['fields_cfg'])?$field['fields_cfg']:$field[5];
            $this->config['field_remark'] = isset($field['field_remark'])?$field['field_remark']:$field[6];
            $this->config['allow_create'] = isset($field['allow_create'])&&$field['allow_create']!=''?$field['allow_create']:false;
            $this->config['serialize_key'] = isset($field['serialize_key']) && $field['serialize_key']?$field['serialize_key']:$field['field_index'];
            $this->config['sqlskip'] = isset($field[7]['sqlskip'])?$field[7]['sqlskip']:false;
        }
        return $this->config;
    }

	// 處理  before form submit
	public function form_validate($value){
		list($sqlskip, $data, $error) = parent::form_validate($value);
		$field = $this->config();
		$serialize_data = [];
        foreach ($field['fields_cfg'] as $sfkey => $sfdata) {
            if(isset($value[$field['serialize_key']][$sfkey]) && $value[$field['serialize_key']][$sfkey]){
                foreach ($value[$field['serialize_key']][$sfkey] as $fkey => $fvalue) {
                    if (!isset($serialize_data[$fkey])) {
                        $serialize_data[$fkey] = [];
                    }
                    $serialize_data[$fkey][$sfkey] = $value[$field['serialize_key']][$sfkey][$fkey];
                }
            }
        }
        if(!$serialize_data && $field['sqlskip']){
            $sqlskip = true;
        } else {
            $sqlskip = false;
        }
        if($serialize_data){
            $data = serialize($serialize_data);
        } else {
            $data = '';
        }
		return array($sqlskip, $data, $error);
	}

    // build html form field
    public function form_html($value, $formerror, $name= false){
        $field = $this->config();
        $output = '<script>';
        $output .= 'function serialize_'.$field['serialize_key'].'() {';
        $output .= '$(\'#serialize_'.$field['serialize_key'].'\').append(\''.$this->serialize_html($field['fields_cfg'], [], $field['serialize_key']).'\');';
        $output .= '}</script>';
        
        if($field['allow_create']){
            $output .= '<div style="clear:both; margin-top:8px;"></div>';
            $output .= '<a href="javascript:void(0);" onclick="serialize_'.$field['serialize_key'].'();"><span class="badge bg-green">'.get_systext('list_create').'</span></a>';
        }
        $default_value = $this->get_default_value();
        $output .= '<div id="serialize_'.$field['serialize_key'].'">';
        if ((isset($_POST[$field['field_index']]) && $_POST[$field['field_index']]) || ($default_value && $_GET['stage'] == 'create')) {
            $data = @unserialize(isset($_POST[$field['field_index']])?$_POST[$field['field_index']]:$default_value);
            $data = $data?$data:array([]);
            if(!$field['allow_create']){
                $output .= $this->serialize_html($field['fields_cfg'], $data[0], $field['serialize_key']);
            } else {
                foreach ($data as $sdata) {
                    $output .= $this->serialize_html($field['fields_cfg'], $sdata, $field['serialize_key']);
                }
            }
        } else if(!$field['allow_create']){
            $output .= $this->serialize_html($field['fields_cfg'], [], $field['serialize_key']);
        }
        $output .= '</div>';
        if ($field['field_remark']){
            $output .= '<p class="help-block">'.htmlspecialchars($field['field_remark']).'</p>';
        }
        return $output;
    }
    function serialize_html($fd, $data, $index) {
        $field = $this->config();
        $html = '<div class="row dt_serialize_row" style="padding-left:10px;padding-bottom:0px;">';
        foreach ($fd as $sfkey => $sfdata) {
            $html .= '<div style="width:' . $sfdata[2] . ';float:left;">';
            $val = isset($data[$sfkey])?$data[$sfkey]:'';
            $error = '';
            //if ($formerror[$index]) { $error = 'parsley-errorr '; }
            if ($sfdata[1] == 'text') {
                $html .= '<input style="height:29px;" type="text" name="' . $index . '[' . $sfkey . '][]" class="'.$error.'form-control" placeholder="' . htmlspecialchars($sfdata[0]) . '" value="' . htmlspecialchars($val) . '">';
            } else if ($sfdata[1] == 'textarea') {
                $html .= '<textarea name="' . $index . '[' . $sfkey . '][]" class="'.$error.'form-control" placeholder="' . htmlspecialchars($sfdata[0]) . '">' . htmlspecialchars($val) . '</textarea>';
            }
            $html .= '</div>';
        }
        if($this->config['allow_create']){
            $html .= '<div style="float:left;margin-left:10px;" onclick="$(this).parent().remove();"><button type="button" class="btn btn-primary bg-red">'.get_systext('list_delete').'</button></div>';
        }
        $html .= '</div>';
        return $html;
    }
    public function mysql_field_type(){
        return 'text null';
    }
}
?>