<?php
class data_type {
	// $cdata = class , toots
	protected $cdata;
	protected $version = 1.0;
	private $config;
	private $config_table;
	private $selected_lang;

	public $var = 1;

	public function __construct($cdata=null) {
		if(isset($cdata->cdata)){
			$this->cdata = $cdata->cdata;
		} else {
			$this->cdata = $cdata;
		}
	}

	public function __get($key) {
		if(is_object($this->cdata)){
			return (isset($this->cdata->cdata[$key]) ? $this->cdata->cdata[$key] : null);
		} else {
			return (isset($this->cdata[$key]) ? $this->cdata[$key] : null);
		}
	}

	public function __set($key, $value) {
		$this->cdata[$key] = $value;
	}

	public function config($field=false){
		if($field){
			$this->clear_config();
			$this->config = $field;
		}
		return $this->config;
	}

	public function getFilterFieldName($field){
        return $field['field_index'];
    }

	private function get_table_name(){
		return isset($this->config_table) ? $this->config_table['table_name'] : null;
	}
	public function get_table_primarykey(){
		return isset($this->config_table) ? $this->config_table['table_primarykey'] : null;
	}

	public function get_text(){
		return isset($this->config) ? $this->config['field_name'] : null;
	}

	public function get_systext(){
		return isset($this->config) ? get_systext($this->get_text()) : null;
	}

	public function get_index(){
		return isset($this->config) ? $this->config['field_index'] : null;
	}

	public function get_default_value(){
		return isset($this->config) && isset($this->config['field_default']) ? $this->config['field_default'] : null;
	}

	public function clear_config(){
		$this->config = array();
	}

	// build html list filter
	public function filter_html($value){
		$output = '';
	}
	// build list value
	public function list_value($values){
		global $selected_lang;
		return htmlspecialchars(isset($this->config['fieldOpts']['support_language']) ? $values[$this->get_index(). '_' . $selected_lang] : $values[$this->get_index()]);
	}
	public function filter_value($values){
		return $this->list_value($values);
	}

	// build list update
	public function list_update_field($value, $formerror, $primaryid){
		return $this->form_html($value, $formerror, $this->config['field_index'].'_'.$primaryid);
	}
	// build list update value
	public function list_update_value($values, $primaryid){
		$field = $this->config();
		return $values[$field['field_index'].'_'.$primaryid];
	}
	// build html value
	public function show_value($value){
		
	}
	// build html form field
	public function form_html($value, $formerror, $name= false){
		$field = $this->config();
		$fieldOpts = fieldOpt($field['field_options']);
		$error = '';
		$maxlength = $field['length_limit']?'maxlength="'.$field['length_limit'].'"':'';
		if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
			$error = 'parsley-errorr ';
		}
		$datetime = '';
		$type = 'text';
		if ($field['field_type'] == 'date' || $field['field_type'] == 'datetime' || $field['field_type'] == 'time') {
			$datetime = $field['field_type'].'picker';
		} else if($field['field_type'] == 'number'){
			$type = 'number';
		} else if($field['field_type'] == 'hidden'){
			$type = 'hidden';
		}
		$default_value = $this->get_default_value();
		if(in_array($_GET['tt'], array('list', 'search')) && $field['list_width']){
			$width = $field['list_width'];
		} else {
			$width = '100%';
		}
		$output = '<input autocomplete="off" type="'.$type.'" class="'.$error.'form-control '.$datetime.' field_'.$field['field_index'].'" '.$maxlength.' jf="'.$field['field_index'].'"
           placeholder="'.strip_tags($field['field_remark']?$field['field_remark']:get_systext($field['field_name'])).'" style="width: '.$width.';" '.(isset($fieldOpts['modify_readonly'])?' readonly="true" ':'').'
           name="'.($name? $name: $field['field_index']).'" value="'.(isset($value[$field['field_index']])?htmlspecialchars($value[$field['field_index']]):$default_value).'">';
        return $output;
	}
	// 處理  before form submit
	public function form_validate($value){
		$sqlskip = false;
		$data = false;
		$error = false;
		$field = $this->config();
		$fieldOpts = fieldOpt($field['field_options']);
		if (isset($fieldOpts['email']) && $value[$field['field_index']] && filter_var($_POST[$field['field_index']], FILTER_VALIDATE_EMAIL) === false){
			$error = get_systext('msg_invaild_email');
		}
		if(isset($fieldOpts['uppercase'])){
			$data = strtoupper($_POST[$field['field_index']]);
		}
		if(strpos(strtolower($value[$field['field_index']]), 'href') !== false){
			preg_match_all('~<a(.*?)href="([^"]+)"(.*?)>~', strtolower($value[$field['field_index']]), $matches);
			foreach ($matches[2] as $v) {
				$tag = false;
				foreach (['//', 'http://', 'https://', 'http&#x3a;//', 'https&#x3a;//', 'mailto:', 'tel:', '#', '{{active_link}}'] as $k){
					if(substr($v, 0, strlen($k)) == $k){
						$tag = true;
					}
				}
				if(!$tag){
					$sqlskip = true;
					$error = 'Invalid link href attribute : Field '.$field['field_index'];
					break;
				}
			}
		}
		if($data === false && $error === false && $sqlskip === false){
			$data = $_POST[$field['field_index']];
		}
		return array($sqlskip, $data, $error);
	}

	// 處理 after submit form
	public function form_after_submit(){
		
	}

	// 處理 duplicate after submit form
	public function form_after_duplicate(){
		
	}

	public function form_details($value){
		return isset($value[$this->get_index()])?$value[$this->get_index()]:'';
	}

	public function mysql_field_type(){
		return 'varchar(255) null';
	}
	public function debug(){
		var_dump($this->mysql);
		exit;
	}
	public function delete_record($values){
		
	}
	public function key_replace($data){
        $delete_chars = array('$', '.', ',', '"', '(', ')', '+', '!', '@', '#', '%', '^', '&', '*', '=', '+', '[', ']', '{', '}', '|', '\\', '/', '`', '~', ';', ':', '<', '>', '?', '\'', '“', '”', '’');
        $data = trim($data);
        $data = str_replace('  ', ' ', $data);
        $data = str_replace('  ', ' ', $data);
        $data = str_replace('  ', ' ', $data);
        $data = str_replace('  ', ' ', $data);
        $data = str_replace('  ', ' ', $data);
        $data = str_replace(' ', '_', $data);
        $data = str_replace($delete_chars, '', $data);
        $data = strtolower($data);
        return $data;
    }
}
?>