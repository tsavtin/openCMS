<?php
class dt_inlinelist extends data_type {
    private $config;
    public function config($field=false){
        if($field){
            $this->clear_config();
            $this->config = parent::config($field);
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
                    if($sfdata[3] && $value[$field['serialize_key']][$sfkey][$fkey] == ''){
                        $error = 'This field is required.';
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
            $data = json_encode($serialize_data);
        } else {
            $data = '';
        }
		return array($sqlskip, $data, $error);

	}

    // build list value
    public function list_value($values){
        $field = $this->config();
        $default_value = $this->get_default_value();

        $data = json_decode(isset($values[$field['field_index']])?$values[$field['field_index']]:$default_value, true);
        $data = $data?$data:array([]);
        $output = '<table border="1" cellpadding="3">';
        $output .= $this->serialize_list_value($field['fields_cfg'], [], $field['serialize_key'], true);
        foreach ($data as $sdata) {
            $output .= $this->serialize_list_value($field['fields_cfg'], $sdata, $field['serialize_key']);
        }
        $output .= '</table>';
        return $output;
    }

    // build html form field
    public function form_html($value, $formerror, $name= false){
        $field = $this->config();
        $fieldOpts = fieldOpt($field['field_options']);
        $javascript = '';
        $output = '';
        
        
        if($field['allow_create'] && !isset($fieldOpts['modify_readonly'])){
            $output .= '<div style="clear:both; margin-top:8px;"></div>';
            $output .= '<a href="javascript:void(0);" onclick="serialize_'.$field['serialize_key'].'();"><span class="badge bg-green">'.get_systext('list_create').'</span></a>';
        }
        $default_value = $this->get_default_value();
        $output .= '<div id="serialize_'.$field['serialize_key'].'">';
        
        if ((isset($_POST[$field['field_index']]) && $_POST[$field['field_index']]) || ($default_value && $_GET['stage'] == 'create')) {
            $data = json_decode(isset($_POST[$field['field_index']])?$_POST[$field['field_index']]:$default_value, true);
            $data = $data?$data:array([]);
            if(!$field['allow_create']){
                list($html, $js) = $this->serialize_html($field['fields_cfg'], $data[0], $field['serialize_key']);
                $output .= $html;
                $javascript .= $js;
            } else {
                list($html, $js) = $this->serialize_html($field['fields_cfg'], [], $field['serialize_key'], true);
                $output .= $html;
                foreach ($data as $sdata) {
                    list($html, $js) = $this->serialize_html($field['fields_cfg'], $sdata, $field['serialize_key']);
                    $output .= $html;
                    $javascript .= $js;
                }
            }
        } else if(!$field['allow_create']){
            list($html, $js) = $this->serialize_html($field['fields_cfg'], [], $field['serialize_key']);
            $output .= $html;
            $javascript .= $js;
        } else {
            list($html, $js) = $this->serialize_html($field['fields_cfg'], [], $field['serialize_key'], true);
            $output .= $html;
            $javascript .= $js;
        }
        $output .= '</div>';
        if ($field['field_remark']){
            $output .= '<p class="help-block">'.htmlspecialchars($field['field_remark']).'</p>';
        }
        if ($javascript) {
            $output .= '<script type="text/javascript"> $(document).ready(function() { '.$javascript.' }); </script>';
        }
        $output .= '<script>';
        $output .= 'function serialize_'.$field['serialize_key'].'() {';
        list($html, $js) = $this->serialize_html($field['fields_cfg'], [], $field['serialize_key']);
        $output .= 'let newrow = $(\''.str_replace("'", "\'", $html).'\');';
        $output .= '$(\'#serialize_'.$field['serialize_key'].'\').append(newrow);';
        $output .= 'if ($(\'.datepicker\').length > 0) { $(\'.datepicker\').datetimepicker({step: 30, timepicker: false, format: datepicker_format, scrollMonth:false}); }';
        $output .= $js.$javascript;
        $output .= '}</script>';
        return $output;
    }

    function serialize_list_value($fd, $data, $index, $header = false) {
        global $tbCfgs, $data_type;
        $field = $this->config();
        $html = '<tr>';
        $js = '';

        foreach ($fd as $sfkey => $sfdata) {
            if($sfdata[0] == 'hidden'){continue;}
            $val = isset($data[$sfkey])?$data[$sfkey]:$sfdata[4];
            if(!$val && !$header && $sfdata[3]){
                $html .= '<td style="padding: 0 3px;">';
            } else {
                $html .= '<td style="padding: 0 3px;">';
            }
            $error = '';
            if($header){
                $html .= ($sfdata[3]?'*':'') . htmlspecialchars($sfdata[0]);
            } else if($sfdata[2] && $tbCfgs['admin_zconfigtable_inlinelist']['fbid'][$sfdata[2]]){
                $reField = $tbCfgs['admin_zconfigtable_inlinelist']['fbid'][$sfdata[2]];
                $reField['field_name'] = $sfdata[0];
                $data_type->{'dt_'.$reField['field_type']}->config($reField);
                $inlineHtml = $data_type->{'dt_'.$reField['field_type']}->list_value(array($reField['field_index'] => $val), false, $index . '[' . $sfkey . '][]');
                $inlineHtml = str_replace("\r", '', $inlineHtml);
                $inlineHtml = str_replace("\n", '', $inlineHtml);
                if($reField['field_type'] != 'colorpicker'){
                    $inlineHtml = preg_replace('|<script>.*?<\/script>|is', '', $inlineHtml);
                }
                $html .= $inlineHtml;
            } else {
                $html .= htmlspecialchars($val);
            }
            $html .= '</td>';
        }
        $html .= '</tr>';
        return $html;
    }
    function serialize_html($fd, $data, $index, $header = false) {
        global $tbCfgs, $data_type;
        $field = $this->config();
        $fieldOpts = fieldOpt($field['field_options']);
        $html = '<div class="row dt_inlinelist_row" style="padding-left:10px;padding-bottom:0px;">';
        $js = '';
        // print_r($fd);
        // exit;
        $inlinelist_related = [];
        foreach ($fd as $sfkey => $sfdata) {
            $val = isset($data[$sfkey])?$data[$sfkey]:$sfdata[4];
            if(!$val && !$header && $sfdata[3]){
                $html .= '<div class="has-inlineEmpty" style="width:' . $sfdata[5] . ';float:left;">';
            } else {
                $html .= '<div style="width:' . $sfdata[5] . ';float:left;">';
            }
            $error = '';
            if($header){
                $html .= '<input style="height:29px;" type="text" readonly="readonly" class="'.$error.'form-control" value="' .($sfdata[3]?'*':'') . htmlspecialchars($sfdata[0]).'">';
            } else if($sfdata[2] && $tbCfgs['admin_zconfigtable_inlinelist']['fbid'][$sfdata[2]]){
                $reField = $tbCfgs['admin_zconfigtable_inlinelist']['fbid'][$sfdata[2]];
                $reField['field_name'] = $sfdata[0];
                if($reField['field_type'] == 'date'){
                    $reField['date_format'] = 'normal';
                }
                if(!$reField['field_options']){
                    $reField['field_options'] = $field['field_options'];
                }
                $data_type->{'dt_'.$reField['field_type']}->config($reField);

                if($reField['field_type'] == 'related'){
                    list($count, $inlineHtml) = $data_type->{'dt_related'}->getRelateSelect($reField, false, '', $val, $index . '[' . $sfkey . '][]');
                    $inlineHtml = preg_replace('|<script>.*?<\/script>|is', '', $inlineHtml);
                    $html .= str_replace('\'', '\\\'', $inlineHtml);
                    if ($count > 5 && !isset($fieldOpts['modify_readonly'])) {
                        $js .= '$(".R_'.$reField['field_index'].'").select2({maximumSelectionLength: 10, allowClear: true});';
                    }
                } else {
                    $inlineHtml = $data_type->{'dt_'.$reField['field_type']}->form_html(array($reField['field_index'] => $val), false, $index . '[' . $sfkey . '][]');
                    $inlineHtml = str_replace("\r", '', $inlineHtml);
                    $inlineHtml = str_replace("\n", '', $inlineHtml);
                    $inlineHtml = preg_replace('|<script>.*?<\/script>|is', '', $inlineHtml);
                    if($reField['field_type'] == 'colorpicker'){
                        // $inlineHtml = preg_replace('|<span class="input-group-addon.*?<\/span>|is', '', $inlineHtml);
                        $js .= '$(\'.colorpicker\').wheelColorPicker();';
                        $js .= '$(\'.colorpicker\').change(function(){$(this).parent().find(\'span\').css(\'background-color\', \'#\'+$(this).val());});';
                    }
                    $html .= $inlineHtml;
                }
            } else if ($sfdata[1] == 'text') {
                $html .= '<input '.(isset($fieldOpts['modify_readonly'])?' readonly="true" ':'').' style="height:29px;" type="text" name="' . $index . '[' . $sfkey . '][]" class="'.$error.'form-control" placeholder="' . htmlspecialchars($sfdata[0]) . '" value="' . htmlspecialchars($val) . '">';
            } else if ($sfdata[1] == 'textarea') {
                $html .= '<textarea name="' . $index . '[' . $sfkey . '][]" class="'.$error.'form-control" placeholder="' . htmlspecialchars($sfdata[0]) . '">' . htmlspecialchars($val) . '</textarea>';
            }
            $html .= '</div>';
        }
        if($this->config['allow_create'] && !$header && !isset($fieldOpts['modify_readonly'])){
            $html .= '<div style="float:left;margin-left:10px;" onclick="$(this).parent().remove();"><button type="button" class="btn btn-primary bg-red">'.get_systext('list_delete').'</button></div>';
        } else if($this->config['allow_create'] && $header && !isset($fieldOpts['modify_readonly'])){
            $html .= '<div style="float:left;width: 80px;"><input style="height:29px;" type="text" readonly="readonly" class="form-control" value="'.get_systext('list_delete').'"></div>';
        }
        $html .= '</div>';
        return array($html, $js);
    }
    public function mysql_field_type(){
        return 'text null';
    }
}
?>