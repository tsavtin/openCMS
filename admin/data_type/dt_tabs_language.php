<?php
class dt_tabs_language extends data_type {
    private $config;
    public function config($field=false){
        if($field){
            $this->clear_config();
            $this->config = parent::config($field);
            $this->config['allow_create'] = isset($field['extra']['allow_create'])?$field['extra']['allow_create']:true;
            $this->config['tabs'] = isset($field['tabs'])?$field['tabs']:$field[5];
            $this->config['field_default'] = isset($field['field_default'])?$field['field_default']:$field[6];
        }
        return $this->config;
    }

	public function form_details($value){
        global $mysql;
		$field = $this->config();
        $output = '<div class="dt-buttons btn-group">';
        $hidejs = '';
        $fn_name = "ct_".generateRandomString(5);

        foreach ($mysql->getList('language', null, '*','sort_order', 'ASC') as $v) {
            $tab = 'section_'.$v['code'];
            $label = $v['name'];
            $output .= '<a class="btn btn-default btn-sm btn_'.$tab.'" tabindex="0" aria-controls="datatable-buttons" href="javascript:void(0);"';
            $output .= 'onclick="'.$fn_name.'(\''.$tab.'\');"><span>'.$label.'</span></a>';
            $hidejs .= "$('.$tab').hide();";
            $hidejs .= "$('.btn_$tab').removeClass('active');";
        }
        $output .= '<script type="text/javascript">';
        $output .= 'function '.$fn_name.'(tab) {';
        $output .= $hidejs;
        $output .= "$('.' + tab).show();\n";
        $output .= "$('.btn_' + tab).addClass('active');}\n";
        $output .= '$(function () {';
        $output .= $hidejs;
        $output .= $fn_name.'(\''.$field['field_default'].'\');';
        $output .= '});';
        $output .= '</script></div>';
        return $output;
	}

    // build html form field
    public function form_html($value, $formerror, $name= false){
        $field = $this->config();
        return $this->form_details($value);
    }
}
?>