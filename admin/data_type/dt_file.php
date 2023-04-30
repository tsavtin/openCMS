<?php
class dt_file extends data_type {


    private $config;
    public function config($field=false){
        if($field){
            $this->clear_config();
            $this->config = parent::config($field);
            if($field['extra_opt'] && $field['extra_opt'][0]){
                $this->config['mb_limit'] = $field['extra_opt'][0][0];
                $this->config['mime_type'] = $field['extra_opt'][0][1];
            }
        }
        return $this->config;
    }

    // 處理  before form submit
    public function form_validate($value){
        global $_FILES, $fileConfig, $curCfg;
        $sqlskip = true;
        $data = false;
        $error = false;
        $field = $this->config();
        $cfg = $fileConfig[$curCfg['index'] . '.' . $field['field_index']];
        $mb_limit = $field['mb_limit']?$field['mb_limit']:$cfg[2];
        if(isset($mb_limit) && $mb_limit){
            if($mb_limit*1024*1024 < filesize($_FILES[$field['field_index']]['tmp_name'])){
                $error = 'maximum upload '.$field['field_name'].' size('.$mb_limit.'MB)';
            }
        }
        return array($sqlskip, $data, $error);
    }

    public function list_value($value){
        global $fileConfig, $curCfg;
        $field = $this->config();
        $output = '';
        if (isset($value[$field['field_index']]) && $value[$field['field_index']]) {
            $output = '<a href="getfile.php?file_id='.$value[$field['field_index']].'" target="_blank"><span class="badge bg-green">'.get_systext('btn_download').'</span></a>';
        }
        return $output;
    }
    public function form_details($value){
        return $this->list_value($value);
    }

	// build html form field
    public function form_html($value, $formerror, $name= false){
    	global $fileConfig, $curCfg;
    	$field = $this->config();

        $mime_type = $field['mime_type']?$field['mime_type']:$fileConfig[$curCfg['index'] . '.' . $field['field_index']][0];
    	$output = '';
    	if (isset($value[$field['field_index']]) && $value[$field['field_index']]) {
            $output = '<a href="getfile.php?file_id='.$value[$field['field_index']].'" target="_blank"><span class="badge bg-green">'.get_systext('btn_download').' '.get_systext($field['field_name']).'</span></a><br><br>';
            $output .= '<a href="?'.get_link().'&sub_stage=delete_file&del_field='.$field['field_index'].'&sub_stage_id='.$value[$field['field_index']].'" onclick="return confirm(\'delete file?\');">';
            $output .= '<span class="badge bg-red">delete file</span></a>';
        }
        $output .= '<div style="padding-top:8px;"></div><input type="file" name="'.$field['field_index'].'" accept="'.$mime_type.'">';
    	return $output;
    }

    // 處理 after submit form
    public function form_after_submit(){
        global $_FILES, $curCfg, $fileTable, $filePrimarykey, $admin_filefolder, $query_data, $nowTime;
        $field = $this->config();
        if (file_exists($_FILES[$field['field_index']]['tmp_name'])) {
            // cheeck old data to delete start
            if($curCfg['table_type'] == 'setting'){
                $query = array($curCfg['table_key'] => $field['field_index']);
                if($curCfg['parent_related_key']){
                    $query[$curCfg['parent_related_key']] = $_GET[$curCfg['parent_related_key']];
                }
                $info = $this->mysql->getData($curCfg['table_name'], $query);
                $oldid = $info[$curCfg['table_value']];
            } else {
                $info = $this->mysql->getData($curCfg['table_name'], $query_data);
                $oldid = $info[$field['field_index']];
            }
            if ($oldid) {
                $this->delete_record(array($field['field_index'] => $oldid));
            }
            // cheeck old data to delete end

            $newName = md5($_FILES[$field['field_index']]['name']);
            $newExt = pathinfo($_FILES[$field['field_index']]['name'], PATHINFO_EXTENSION);
            $newFileData = array(
                'table' => $curCfg['table_name'],
                'file_org' => $_FILES[$field['field_index']]['name'],
                'filename_md5' => $newName,
                'type' => $newExt,
                'date_added' => $nowTime,
                'date_modified' => $nowTime,
                'status' => 1
            );
            $this->mysql->create($fileTable, $newFileData);
            $lastFileId = $this->mysql->cid->lastInsertId();
            $folder = get_folder($admin_filefolder, $lastFileId, $nowTime);
            move_uploaded_file($_FILES[$field['field_index']]['tmp_name'], "$folder/$newName.$newExt");
            if($curCfg['table_type'] == 'setting'){
                $query = array($curCfg['table_key'] => $field['field_index']);
                if($curCfg['parent_related_key']){
                    $query[$curCfg['parent_related_key']] = $_GET[$curCfg['parent_related_key']];
                }
                $this->mysql->update($curCfg['table_name'], $query, array($curCfg['table_value'] => $lastFileId));
            } else {
                $this->mysql->update($curCfg['table_name'], $query_data, array($field['field_index'] => $lastFileId));
            }
            return $lastFileId;
        }
        return null;
    }
    public function mysql_field_type(){
        return 'int(11) null';
    }
    public function delete_record($values){
        global $curCfg, $admin_filefolder, $fileTable, $filePrimarykey;
        $field = $this->config();
        $field_index = $field['field_index'];
        $fileData = $this->mysql->getData($fileTable, array($filePrimarykey => $values[$field_index]));
        if($fileData){
            $this->mysql->delete($fileTable, array($filePrimarykey => $values[$field_index]));
            $folder = get_folder($admin_filefolder, $values[$field_index], $fileData['date_added']);
            deleteFolder($folder);
        }
    }
}
?>