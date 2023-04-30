<?php
class dt_text extends data_type {
	// build html list filter
	public function filter_html($value){
		global $curCfg, $mysql, $extraQuery;

		$field = $this->config();

		if($extraQuery){
			$where_statment = $mysql->getWhereStatment($extraQuery);
			$sql = "SELECT `$field[field_index]`  FROM $mysql->sql_prefix$curCfg[table_name] $where_statment GROUP BY `$field[field_index]` ORDER BY `$field[field_index]` ASC";
			$stmt = $mysql->prepare($sql);
		    $stmt->execute($mysql->getQuerys($extraQuery));
		} else {
			$sql = "SELECT `$field[field_index]`  FROM $mysql->sql_prefix$curCfg[table_name] GROUP BY `$field[field_index]` ORDER BY `$field[field_index]` ASC";
			$stmt = $mysql->prepare($sql);
		    $stmt->execute();
		}

	    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$output = '';

		$output = '<select class="R_'.$field['field_index'].' form-control" jf="'.$field['field_index'].'" name="'.$field['field_index'].'" id="'.$field['field_index'].'">';
		$output .= '<option value="">'.$field['field_name'].':'.get_systext('filter_all').'</option>';
		foreach ($res as $re){
			if (isset($_POST[$field['field_index']]) && $re[$field['field_index']] == $_POST[$field['field_index']]) { 
                $selected = ' selected '; 
            } else {
            	$selected = '  '; 
            }
			$output .= '<option '.$selected.' value="'.$re[$field['field_index']].'">'.$re[$field['field_index']].'</option>';
		}
		$output .= '</select>';
		return $output;
	}
}
?>