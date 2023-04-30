<?php
class dt_textarea extends data_type {
    // build html form field
    public function form_html($value, $formerror, $name= false){
        $field = parent::config();
        $fieldOpts = fieldOpt($field['field_options']);
        $height = '200px';
        if(isset($field['extra_opt'][0][0])){
            if(preg_match('/%/', $field['extra_opt'][0][0])){
                $height = $field['extra_opt'][0][0];
            } else {
                $height = $field['extra_opt'][0][0].'px';
            }
        }
        if($name){
            $height = '25px';
        }
        $error = '';
        if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
            $error = 'parsley-errorr ';
        }
        return '<textarea '.(isset($fieldOpts['modify_readonly'])?' disabled="true" ':'').' class="'.$error.'form-control field_'.$field['field_index'].'" jf="'.$field['field_index'].'" style="height:'.$height.';width:100%;" name="'.($name? $name: $field['field_index']).'">'.(isset($value[$field['field_index']])?htmlspecialchars($value[$field['field_index']]):'').'</textarea>';
    }
    public function mysql_field_type(){
        return 'text null';
    }
    public function form_details($value){
        return isset($value[$this->get_index()])?'<pre>'.$value[$this->get_index()].'</pre>':'';
    }
    // build list value
    public function list_value($values){
        $field = parent::config();
        global $selected_lang;
        if($field['list_width']){
            return '<div style="width: '.$field['list_width'].'; ">'.nl2br(str_replace(',', ', ', isset($this->config['fieldOpts']['support_language']) ? $values[$this->get_index(). '_' . $selected_lang] : $values[$this->get_index()])).'</div>';
        } else {
            return nl2br(str_replace(',', ', ', isset($this->config['fieldOpts']['support_language']) ? $values[$this->get_index(). '_' . $selected_lang] : $values[$this->get_index()]));
        }
    }
}
?>