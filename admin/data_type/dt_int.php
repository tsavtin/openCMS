<?php
class dt_int extends data_type {
	public function mysql_field_type(){
        return 'int(11) null';
    }
}
?>