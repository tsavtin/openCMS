<?php
class dt_colorpicker extends data_type {
	// build html form field
	public function form_html($value, $formerror, $name= false){
		$field = $this->config();
		$error = '';
		if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
			$error = 'parsley-errorr ';
		}
		$datetime = '';
		if ($field['field_type'] == 'date' || $field['field_type'] == 'datetime' || $field['field_type'] == 'time') {
			$datetime = $field['field_type'].'picker';
		}
		$color = strpos($value[$field['field_index']], '#')==false?'#'.$value[$field['field_index']]:$value[$field['field_index']];
		$type = 'text';
		$default_value = $this->get_default_value();
		$output = '<div class="input-group">
		  <span class="input-group-addon '.($name? $name: $field['field_index']).'_color" style="background-color:'.$color.';">&nbsp;</span><input type="'.$type.'" class="'.$error.'form-control '.$datetime.' field_'.$field['field_index'].' colorpicker"  data-wheelcolorpicker jf="'.$field['field_index'].'"
           placeholder="'.strip_tags($field['field_remark']?$field['field_remark']:get_systext($field['field_name'])).'" style="width: 100%;"
           name="'.($name? $name: $field['field_index']).'" value="'.(isset($value[$field['field_index']])?htmlspecialchars($value[$field['field_index']]):$default_value).'"></div>

           <script>
           	$(\'[name='.($name? $name: $field['field_index']).']\').change(function(){
           		$(\'.'.($name? $name: $field['field_index']).'_color\').css(\'background-color\', \'#\'+$(this).val());
           	});
           	</script>';
        return $output;
	}
	
	// build list value
	public function list_value($values){
		$value = parent::list_value($values);
		return '<div style="display: inline-block; width: 1.5em; background-color: #'.$value.';">&nbsp;</div> '.$value;
	}
}
?>