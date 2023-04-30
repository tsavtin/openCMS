<?php
class dt_dollar extends data_type {
	public function mysql_field_type(){
        return 'decimal(10,2) null';
    }

    // 處理  before form submit
	public function form_validate($value){
		$sqlskip = false;
		$data = false;
		$error = false;
		$field = $this->config();
		$fieldOpts = fieldOpt($field['field_options']);
		if(!$value[$field['field_index']]){
			$data = 0.0;
		} else {
			$data = strtoupper($_POST[$field['field_index']]);
		}
		$data = str_replace('$', '', $data);
		$data = str_replace(',', '', $data);
		return array($sqlskip, $data, $error);
	}

    // build list value
	public function list_value($values){
		$field = $this->config();
		if($field['list_width']){
            return '<div style="width: '.$field['list_width'].'; ">$'.number_format(parent::list_value($values), 2).'</div>';
        } else {
            return '$'.number_format(parent::list_value($values), 2);
        }
		
	}

	public function form_html($value, $formerror, $name= false){
		$field = $this->config();
		if($field['list_width']){
			
		}
		return '<div class="input-group">
		  <span class="input-group-addon">$</span>
		    '.parent::form_html($value, $formerror, $name).'
		</div>
		<script>
		$(document).ready(function() {
		    $(\'[name='.$field['field_index'].']\').val(addCommas($(\'[name='.$field['field_index'].']\').val()));
			$(\'[name='.$field['field_index'].']\').click(function(){
				$(this).data(\'stage\', \'clear\');
				$(this).select();
			});

			$(\'[name='.$field['field_index'].']\').keydown(function(e){
				let ak = [8,48,49,50,51,52,53,54,55,56,57,58,110];
				if(ak.indexOf(e.which) !== -1){
					let dollar = $(this).val();
					dollar = dollar.replace(/\$|,/g, \'\');
					if((dollar == \'0\' && e.which != 110) || $(this).data(\'stage\') == \'clear\'){
						dollar = \'\';
					}
					if($(this).data(\'stage\') == \'clear\'){
						$(this).data(\'stage\', \'\');
					}
					if(e.which == 8){
						dollar = dollar.substring(0, dollar.length - 1);
					} else if(e.which == 110){
						dollar += \'.\';
					} else {
						dollar += String.fromCharCode(e.which);
					}
					$(this).val(addCommas(dollar));
				}
				return false;
			});
		});
		</script>
		';
	}
}
?>