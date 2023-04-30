<?php

class dt_audio extends data_type {
	private $audioPrimarykey = 'audio_id';
    // 處理  before form submit
    public function form_validate($value){
        global $_FILES, $audioConfig, $curCfg;
        $sqlskip = true;
        $data = false;
        $error = false;
        $field = parent::config();
        $cfg = $audioConfig[$curCfg['index'] . '.' . $field['field_index']];
        if(isset($cfg[2]) && $cfg[2]){
            if($cfg[2]*1024*1024 < filesize($_FILES[$field['field_index']]['tmp_name'])){
                $error = 'maximum upload '.$field['field_name'].' size('.$cfg[2].'MB)';
            }
        }
        return array($sqlskip, $data, $error);
    }
	// build html form field
    public function form_html($value, $formerror, $name= false){
    	global $audioConfig, $curCfg;
        global $audioTable, $audioPrimarykey, $admin_audiofolder;
    	$field = parent::config();
    	$output = '';
        if (isset($value[$field['field_index']]) && $value[$field['field_index']]) {
            $audioData = $this->mysql->getData($audioTable, array($audioPrimarykey => $value[$field['field_index']]));
            $folder = get_folder($admin_audiofolder, $value[$field['field_index']], $audioData['date_added']);
            $audiourl = "$folder/$audioData[filename_md5].$audioData[type]";
            $output = '<audio class="audio-js" controls preload="auto" data-setup="{}">';
            $output .= '<source src="'.$audiourl.'" type="'.$audioConfig[$curCfg['index'] . '.' . $field['field_index']][1].'">';
            $output .= '</audio>';
            $output .= '<br><a href="?'.get_link().'&sub_stage=delete_audio&del_field='.$field['field_index'].'&sub_stage_id='.$value[$field['field_index']].'" onclick="return confirm(\'delete audio?\');">';
            $output .= '<span class="badge bg-red">delete audio</span></a>';
        }
        $output .= '<div style="padding-top:8px;"></div><input type="file" name="'.$field['field_index'].'" accept="'.$audioConfig[$curCfg['index'] . '.' . $field['field_index']][0].'">';
    	return $output;
    }

    // 處理 after submit form
    public function form_after_submit(){
        global $_FILES, $imageConfig, $curCfg, $audioTable, $audioPrimarykey, $admin_audiofolder, $query_data, $nowTime;
        $field = parent::config();
        if ($field['field_type'] == 'audio' && file_exists($_FILES[$field['field_index']]['tmp_name'])) {
            if($curCfg['table_type'] == 'setting'){
                $info = $this->mysql->getData($curCfg['table_name'], array($curCfg['table_key'] => $field['field_index']));
                $oldid = $info['value'];
            } else {
                $info = $this->mysql->getData($curCfg['table_name'], $query_data);
                $oldid = $info[$field['field_index']];
            }
            if ($oldid) {
                $this->delete_record(array($audioPrimarykey => $oldid));
            }
            $newName = md5($_FILES[$field['field_index']]['name']);
            $newExt = pathinfo($_FILES[$field['field_index']]['name'], PATHINFO_EXTENSION);
            $newaudioData = array(
                'table' => $curCfg['table_name'],
                'file_org' => $_FILES[$field['field_index']]['name'],
                'filename_md5' => $newName,
                'type' => $newExt,
                'date_added' => $nowTime,
                'status' => 1
            );
            $this->mysql->create($audioTable, $newaudioData);
            $lastaudioId = $this->mysql->cid->lastInsertId();
            $folder = get_folder($admin_audiofolder, $lastaudioId, $nowTime);
            move_uploaded_file($_FILES[$field['field_index']]['tmp_name'], "$folder/$newName.$newExt");

            if($curCfg['table_type'] == 'setting'){
                $this->mysql->update($curCfg['table_name'], array($curCfg['table_key'] => $field['field_index']), array('value' => $lastaudioId));
            } else {
                $this->mysql->update($curCfg['table_name'], $query_data, array($field['field_index'] => $lastaudioId));
            }
            return $lastaudioId;
        }
    }
    public function mysql_field_type(){
        return 'int(11) null';
    }
    public function delete_record($values){
        global $curCfg, $admin_audiofolder, $audioTable, $audioPrimarykey;
        $field = parent::config();
        $field_index = $field['field_index'];
        $audioData = $this->mysql->getData($audioTable, array($audioPrimarykey => $values[$field_index]));
        if($audioData){
            $this->mysql->delete($audioTable, array($audioPrimarykey => $values[$field_index]));
            $folder = get_folder($admin_audiofolder, $values[$field_index], $audioData['date_added']);
            deleteFolder($folder);
        }
    }
}
?>