<?php
class dt_video extends data_type {

    // 處理  before form submit
    public function form_validate($value){
        global $_FILES, $videoConfig, $curCfg;
        $sqlskip = true;
        $data = false;
        $error = false;
        $field = parent::config();
        $cfg = $videoConfig[$curCfg['index'] . '.' . $field['field_index']];
        if(isset($cfg[2]) && $cfg[2]){
            if($cfg[2]*1024*1024 < filesize($_FILES[$field['field_index']]['tmp_name'])){
                $error = 'maximum upload '.$field['field_name'].' size('.$cfg[2].'MB)';
            }
        }
        return array($sqlskip, $data, $error);
    }
    // build html form field
    public function form_html($value, $formerror, $name= false){
        global $videoConfig, $curCfg;
        global $videoTable, $videoPrimarykey, $admin_videofolder;
        $field = parent::config();
        $output = '';
        if (isset($value[$field['field_index']]) && $value[$field['field_index']]) {
            $videoData = $this->mysql->getData($videoTable, array($videoPrimarykey => $value[$field['field_index']]));
            $folder = get_folder($admin_videofolder, $value[$field['field_index']], $videoData['date_added']);
            $videourl = "$folder/$videoData[filename_md5].$videoData[type]";
            $output = '<video class="video-js" controls preload="auto" width="300" height="200" data-setup="{}">';
            $output .= '<source src="'.$videourl.'" type="video/'.$videoData['type'].'">';
            $output .= '</video>';
            $output .= '<br><a href="?'.get_link().'&sub_stage=delete_video&del_field='.$field['field_index'].'&sub_stage_id='.$value[$field['field_index']].'" onclick="return confirm(\'delete video?\');">';
            $output .= '<span class="badge bg-red">delete video</span></a>';
        }
        $output .= '<div style="padding-top:8px;"></div><input type="file" name="'.$field['field_index'].'" accept="'.$videoConfig[$curCfg['index'] . '.' . $field['field_index']][0].'">';
        return $output;
    }

    // 處理 after submit form
    public function form_after_submit(){
        global $_FILES, $imageConfig, $curCfg, $videoTable, $videoPrimarykey, $admin_videofolder, $query_data, $nowTime;
        $field = parent::config();
        if ($field['field_type'] == 'video' && file_exists($_FILES[$field['field_index']]['tmp_name'])) {
            if($curCfg['table_type'] == 'setting'){
                $query = array($curCfg['table_key'] => $field['field_index']);
                if($curCfg['parent_related_key']){
                    $query[$curCfg['parent_related_key']] = $_GET[$curCfg['parent_related_key']];
                }
                $info = $this->mysql->getData($curCfg['table_name'], $query);
                $oldid = $info['value'];
            } else {
                $info = $this->mysql->getData($curCfg['table_name'], $query_data);
                $oldid = $info[$field['field_index']];
            }
            if ($oldid) {
                $this->delete_record(array($videoPrimarykey => $oldid));
            }
            
            $newName = md5($_FILES[$field['field_index']]['name']);
            $newExt = pathinfo($_FILES[$field['field_index']]['name'], PATHINFO_EXTENSION);
            $newVideoData = array(
                'table' => $curCfg['table_name'],
                'file_org' => $_FILES[$field['field_index']]['name'],
                'filename_md5' => $newName,
                'type' => $newExt,
                'status' => 1,
                'date_added' => $nowTime
            );
            $this->mysql->create($videoTable, $newVideoData);
            $lastVideoId = $this->mysql->cid->lastInsertId();
            $folder = get_folder($admin_videofolder, $lastVideoId, $nowTime);
            move_uploaded_file($_FILES[$field['field_index']]['tmp_name'], "$folder/$newName.$newExt");

            if($curCfg['table_type'] == 'setting'){
                $query = array($curCfg['table_key'] => $field['field_index']);
                if($curCfg['parent_related_key']){
                    $query[$curCfg['parent_related_key']] = $_GET[$curCfg['parent_related_key']];
                }
                $this->mysql->update($curCfg['table_name'], $query, array($curCfg['table_value'] => $lastVideoId));
            } else {
                $this->mysql->update($curCfg['table_name'], $query_data, array($field['field_index'] => $lastVideoId));
            }
            return $lastVideoId;
        }
    }
    public function mysql_field_type(){
        return 'int(11) null';
    }
    public function delete_record($values){
        global $curCfg, $admin_videofolder, $videoTable, $videoPrimarykey;
        $field = parent::config();
        $field_index = $field['field_index'];
        $videoData = $this->mysql->getData($videoTable, array($videoPrimarykey => $values[$field_index]));
        if($videoData){
            $this->mysql->delete($videoTable, array($videoPrimarykey => $values[$field_index]));
            $folder = get_folder($admin_videofolder, $values[$field_index], $videoData['date_added']);
            deleteFolder($folder);
        }
    }
    public function list_value($values){
        global $videoConfig, $curCfg;
        global $videoTable, $videoPrimarykey, $admin_videofolder;

        if ($values[$this->get_index()]) {
            
            $videoData = $this->mysql->getData($videoTable, array($videoPrimarykey => $values[$this->get_index()]));
            $folder = get_folder($admin_videofolder, $values[$this->get_index()], $videoData['date_added']);
            $videourl = "$folder/$videoData[filename_md5].$videoData[type]";

            $output = '<video id="video_'.$values[$this->get_index()].'" class="video-js" controls preload="auto" width="600" height="400" data-setup="{}" style="display:none;">';
            $output .= '<source src="'.$videourl.'" type="video/'.$videoData['type'].'">';
            $output .= '</video>';

            return $output.'<a href="#video_'.$values[$this->get_index()].'" class="fancybox">VIEW VIDEO</a><br><a href="getfile.php?video_id='.$values[$this->get_index()].'" class="fancybox">DOWNLOAD</a>';
        } else {
            return '';
        }
    }
}
?>