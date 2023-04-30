<?php
class dt_tag extends data_type {
    private $config;
    public function config($field=false){
        global $_GET, $curCfg;
        if($field){
            $this->clear_config();
            $this->config = parent::config($field);
            $this->config['item_type'] = isset($field['item_type'])?$field['item_type']:$field[5];
            $this->config['item_id'] = isset($field['item_id'])?$field['item_id']:$field[6];
        }
        return $this->config;
    }
	
	// 處理  before form submit
	public function form_validate($value){
		$sqlskip = true;
		$data = false;
		$error = false;
		return array($sqlskip, $data, $error);
	}
    // build list value
    public function list_value($values){
        global $selected_lang;
        $field = $this->config();
        $taged = [];
        $query = array(
            array(
                'item_type' => $field['item_type'],
                'item_id' => $values[$field['item_id']],
            )
        );
        $output = [];
        $res = $this->mysql->getListJoin('LEFT JOIN', array('tag_to_item', 'tag'), array('tag_id', 'tag_id'), $query, array('*', 'tag_name'));
        foreach ($res as $tagInfo) {
            if (isset($taged[$tagInfo['tag_id']]) && $taged[$tagInfo['tag_id']]) {
                continue;
            }
            $taged[$tagInfo['tag_id']] = true;
            $output[] = $tagInfo['tag_name'];
        }
        return htmlspecialchars(join(',', $output));
    }
	// build html form field
    public function form_html($value, $formerror, $name= false){
    	$field = $this->config();
        $output = '<script>
            function newtag_' . $field['field_index'] . '(field) {
                if ($(\'#newtag_\' + field).val() == \'\') {
                    alert(\'please provide newtag\');
                    return;
                }
                $(\'#tag_form_\' + field).append(\'<div style="margin-right:20px;float:left;"><input type="checkbox" class="flat" name="\' + field + \'[]" checked value="\' + $(\'#newtag_\' + field).val() + \'">&nbsp;\' + $(\'#newtag_\' + field).val() + \'</div>\');
                $(\'#newtag_\' + field).val(\'\');
                $(\'input.flat\').not(\'.form_Voucher input.flat\').iCheck({
                    checkboxClass: \'icheckbox_flat-green\',
                    radioClass: \'iradio_flat-green\'
                });
            }
        </script>';
        $output .= "\n";
    	$output .= '<div style="padding-top:8px;"></div><div id="tag_container"><div id="tag_form_'.$field['field_index'].'">';
    	$taged = [];
        $query = array(
            array(
                'item_type' => $field['item_type'],
                'item_id' => $_GET[$field['item_id']],
            )
        );

        $res = $this->mysql->getListJoin('LEFT JOIN', array('tag_to_item', 'tag'), array('tag_id', 'tag_id'), $query, array('*', 'tag_name'));
        foreach ($res as $tagInfo) {
            if (isset($taged[$tagInfo['tag_id']]) && $taged[$tagInfo['tag_id']]) {
                continue;
            }
            $taged[$tagInfo['tag_id']] = true;
            $output .= '<div style="margin-right:20px;float:left;"><input type="checkbox" class="flat" name="' . $field['field_index'] . '[]" checked value="' . $tagInfo['tag_name'] . '">&nbsp;' . $tagInfo['tag_name'] . '</div>';
        }

        $query = array(
            array(
                'member_id' => isset($field[7]) && isset($_GET[$field[7]])?$_GET[$field[7]]:0,
                'item_type' => $field['item_type']
            )
        );
        $res = $this->mysql->getListJoin('LEFT JOIN', array('tag_to_item', 'tag'), array('tag_id', 'tag_id'), $query, array('*', 'tag_name'));
        foreach ($res as $tagInfo) {
            if (isset($taged[$tagInfo['tag_id']]) && $taged[$tagInfo['tag_id']]) {
                continue;
            }
            $taged[$tagInfo['tag_id']] = true;
            $output .= '<div style="margin-right:20px;float:left;"><input type="checkbox" class="flat" name="' . $field['field_index'] . '[]" value="' . $tagInfo['tag_name'] . '">&nbsp;' . $tagInfo['tag_name'] . '</div>';
        }

    	$output .= '</div><div class="clear"></div>';
        if($res){
            $output .= '<br>';
        }
    	$output .= '<input type="text" id="newtag_'.$field['field_index'].'" class="flat" name="newtag" placeholder="新標籤">';
    	$output .= '&nbsp;&nbsp;&nbsp; <a href="javascript:void(0);" onclick="newtag_' . $field['field_index'] . '(\''.$field['field_index'].'\');"><span class="badge bg-yellow">新增</span></a></div>';
    	return $output;
    }
    // 處理 after submit form
    public function form_after_submit(){
        global $_POST, $_GET, $nowTime;
        $field = $this->config();
        $newtags = [];
        $existtags = [];
        if($_POST['newtag']){
            $_POST[$field['field_index']][] = $_POST['newtag'];
        }
        if (is_array($_POST[$field['field_index']])) {
            foreach ($_POST[$field['field_index']] as $tag) {
                $newtags[$tag] = true;
            }
        }
        $query = array(
            array(
                'item_type' => $field['item_type'],
                'item_id' => $_GET[$field['item_id']],
            )
        );
        $res = $this->mysql->getListJoin('LEFT JOIN', array('tag_to_item', 'tag'), array('tag_id', 'tag_id'), $query, array('*', 'tag_name'));

        foreach ($res as $tagInfo) {
            $existtags[$tagInfo['tag_name']] = true;
            if ($newtags[$tagInfo['tag_name']] == false) {
                $this->mysql->delete('tag_to_item', array('tag_to_item_id' => $tagInfo['tag_to_item_id']));
            }
        }
        if (is_array($_POST[$field['field_index']])) {
            foreach ($_POST[$field['field_index']] as $tag) {
                if ($existtags[$tag] == false) {
                    $newtagInfo = $this->mysql->getData('tag', array('tag_name' => $tag));
                    if ($newtagInfo == false) {
                        $this->mysql->create('tag', array('tag_name' => $tag, 'status' => 1));
                        $newtagID = $this->mysql->cid->lastInsertId();
                    } else {
                        $newtagID = $newtagInfo['tag_id'];
                    }
                    $newData = array(
                        'item_type' => $field['item_type'],
                        'item_id' => $_GET[$field['item_id']],
                        'tag_id' => $newtagID,
                        'status' => 1,
                        'date_added' => $nowTime
                    );
                    $this->mysql->create('tag_to_item', $newData);
                }
            }
        }
    }
}
?>