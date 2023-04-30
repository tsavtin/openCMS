<?php
class dt_slider extends data_type {
	// build html form field
	public function form_html($value, $formerror, $name= false){
		$field = $this->config();
		$error = '';
		$maxlength = $field['length_limit']?'maxlength="'.$field['length_limit'].'"':'';
		if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) {
			$error = 'parsley-errorr ';
		}
		$datetime = '';
		$type = 'text';
		$default_value = $this->get_default_value();
		list($min, $max) = explode(',', isset($value[$field['field_index']])?$value[$field['field_index']]:$default_value);

		$min = $min?$min:0;
		$max = $max?$max:0;
		
		$jslabel = $label = $field['label'];
		$label = str_replace('{min}', number_format($min, 0), $label);
		$label = str_replace('{max}', number_format($max, 0), $label);

		$jslabel = str_replace('{min}', '" + ui.values[ 0 ].numberFormat(0, \'.\', \',\') + "', $jslabel);
		$jslabel = str_replace('{max}', '" + ui.values[ 1 ].numberFormat(0, \'.\', \',\') + "', $jslabel);

		$output = '
		<input type="hidden" name="'.$field['field_index'].'" value="'.$min.','.$max.'">
		<div class="field_'.$field['field_index'].'_label">'.$label.'</div><div class="field_'.$field['field_index'].'"></div>';
        $output .= '<script>$(document).ready(function () { 
        	$( ".field_'.$field['field_index'].'" ).slider({
        		range: true,
        		min: '.$field['min'].',
      			max: '.$field['max'].',
      			step: '.$field['step'].',
        		values: [ '.$min.', '.$max.' ],
        		slide: function( event, ui ) {
			        $( ".field_'.$field['field_index'].'_label" ).html( "'.$jslabel.'" );
			        $( "[name='.$field['field_index'].']" ).val(ui.values[ 0 ]+","+ui.values[ 1 ]);
			    }
        	}); });

        	Number.prototype.numberFormat = function(c, d, t){
			var n = this, 
			    c = isNaN(c = Math.abs(c)) ? 2 : c, 
			    d = d == undefined ? "." : d, 
			    t = t == undefined ? "," : t, 
			    s = n < 0 ? "-" : "", 
			    i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))), 
			    j = (j = i.length) > 3 ? j % 3 : 0;
			   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
			 };
        	</script>';
        return $output;
	}
}
?>