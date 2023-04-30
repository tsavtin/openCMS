<?php
class dt_link extends data_type {
	// build list value
	public function list_value($values){
		$output = '<a href="'.$values[$this->get_index()].'" target="_blank">'.$values[$this->get_index()].'</a>';
		return $output;
	}
}
?>