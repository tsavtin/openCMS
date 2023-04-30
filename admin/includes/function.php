<?php
function get_subtree_ids($table, $field, $id){
    global $categorys, $mysql, $catree, $cids;
    if(!$categorys){
        $cids = [];
        $categorys = $mysql->getList($table, null, $field);
        $catree = [];
        foreach ($categorys as $cat) {
            $catree[$cat['parent_id']][] = $cat;
        }
    }
    $cids[] = (int) $id;
    if(isset($catree[$id]) && is_array($catree[$id])){
        foreach ($catree[$id] as $value) {
            $cids[] = $value[$field];
            get_subtree_ids($table, $field, $value[$field]);
        }
    }
    return $cids;
}
function get_back_map(){
    global $_SESSION;
    if(isset($_SESSION['bp'])){
        return isset($_SESSION['bp'][count($_SESSION['bp'])-1]['bm'])?$_SESSION['bp'][count($_SESSION['bp'])-1]['bm']:'';
    } else {
        return '';
    }
}

function get_last_back_parameter(){
    global $_SESSION;
    return isset($_SESSION['bp'])?$_SESSION['bp'][count($_SESSION['bp'])-1]:'';
}

function gen_back_parameter(){
    global $_GET;
    $back_parameter = $_GET;
    unset($back_parameter['stage']);
    unset($back_parameter['cc']);
    unset($back_parameter['ca']);
    unset($back_parameter['bp']);
    unset($back_parameter['bc']);
    $back_parameter['bm'] = get_back_map().'/'.$back_parameter['t'];
    return rawurlencode(json_encode($back_parameter));
}
function get_php_file($type, $cfg){
    global $project_folder;
    $php = '';
    if($type == 'list' || $type == 'backlist' || $type == 'sublist' || $type == 'shortcut'){
        if(isset($cfg['list_url']) && $cfg['list_url'] && file_exists('../project/'.$project_folder.'/admin/'.$cfg['list_url'])){
            return 'project_file.php?'.'&file='.str_replace('.php', '', $cfg['list_url']);
        } else if(isset($cfg['table_type']) && $cfg['table_type'] && $cfg[$type.'_url'] && (file_exists('../project/'.$project_folder.'/admin/'.$cfg[$type.'_url']) || file_exists($cfg[$type.'_url']))){
            return $cfg['table_type'] . '.php?';
        } else {
            return 'list.php?';
        }
    } else if($type == 'modify' || $type == 'create' || $type == 'duplicate' || $type == 'details') {
        if(isset($cfg['modify_url']) && isset($cfg[$type.'_url']) && $cfg[$type.'_url'] && (file_exists('../project/'.$project_folder.'/admin/'.$cfg[$type.'_url']) || file_exists($cfg[$type.'_url']))){
            return 'project_file.php?file='.str_replace('.php', '', $cfg[$type.'_url']);
        } else {
            return 'content.php?';
        }
    }
}
function get_link($type='', $cfg=null, $key='') {
    if($type && !preg_match('/^[A-Z]+$/i', $type)){
        return false;
    }
    global $m, $t, $tbCfgs, $_GET, $curCfg, $project_folder, $_SESSION;
    if($cfg == null){
        $cfg = $curCfg;
    }
    $parameter = '';
    if (isset($cfg['parent_extra_key'])) {
        foreach ($cfg['parent_extra_key'] as $v) {
            $parameter .= "&$v=" . $_GET[$v];
        }
    }
    if(isset($cfg['parent_related_key'])){
        $related_keys = explode(',', $cfg['parent_related_key']);
        foreach ($related_keys as $rk){
            $_GET[$rk] = isset($_GET[$rk])? $_GET[$rk]:'';
            $related_query[$rk] = $_GET[$rk];
            $parameter .= '&'.$rk . "=" . $_GET[$rk];
        }
    }
    if(isset($cfg['extra_urlpar']) && $cfg['extra_urlpar']){
        $parameter .= $cfg['extra_urlpar'];
    }
    if(isset($_GET['tpl']) && $_GET['tpl']){
        $parameter .= '&tpl='.$_GET['tpl'];
    }
    $parameter .= "&tt=".($type?$type:'list').'&sl_id='.$_GET['sl_id'];
    if($type == 'details' || $type == 'create' || $type == 'modify' || $type == 'duplicate'){
        return get_php_file($type, $cfg).$parameter.'&m='.$m.'&t='.$cfg['table_index'].'&stage='.$type.'&bp='.gen_back_parameter();
    } else if($type == 'sublist'){
        $parameter .= '&m='.$m.'&t='.$cfg['table_index'].'&bp='.gen_back_parameter();
        return get_php_file($type, $cfg).$parameter;
    } else if($type == 'shortcut'){
        $parameter .= '&m='.$m.'&t='.$cfg['table_index'];
        return get_php_file($type, $cfg).$parameter;
    } else if($type == 'list'){
        return get_php_file($type, $cfg).'&t='.$cfg['table_index'].'&m='.$key.(isset($cfg['extra_urlpar'])?$cfg['extra_urlpar']:'').'&cc=1&ca=1'."&tt=$type";
    } else if($type == 'backlist'){
        $bp = json_decode(get_last_back_parameter(), true);
        return get_php_file($type, $tbCfgs[$bp['t']]).'&'.http_build_query($bp).'&bc=1'."&tt=$type".'&sl_id='.$_GET['sl_id'];
    } else {
        $url = '';
        if(isset($_GET['m'])) {
            $url .= "&m=$_GET[m]";
        }
        if(isset($_GET['order_from'])) {
            $_GET['order_from'] = (int)$_GET['order_from'];
            $url .= "&order_from=$_GET[order_from]";
        }
        if(isset($_GET['t'])) {
            $url .= "&t=$_GET[t]";
        }
        if(isset($_GET['stage'])) {
            $url .= "&stage=$_GET[stage]";
        }
        if(isset($_GET['keyword'])) {
            $url .= "&keyword=".rawurlencode($_GET['keyword']);
        }
        if(isset($curCfg['table_primarykey']) && isset($_GET[$curCfg['table_primarykey']])) {
            $url .= "&$curCfg[table_primarykey]=". $_GET[$curCfg['table_primarykey']];
        }

        if(isset($_GET[$curCfg['table_primarykey'].'_from']) && isset($_GET[$curCfg['table_primarykey'].'_from'])){
            $url .= "&$curCfg[table_primarykey]_from=". $_GET[$curCfg['table_primarykey'].'_from'];
        }
        if (isset($_GET['file'])) {
            $url .= '&file='.$_GET['file'];
        }
        return $parameter.$url;
    }
}

function tree_addpath($tree, $field, $name_index){
    $dataByID = [];
    foreach ($tree as $data) {
        $dataByID[$data[$field['table_primarykey']]] = $data;
    }
    foreach ($tree as $key => $data) {
        $data['path'] = $dataByID[$data[$field['table_parent_id']]][$name_index].'/'.$data[$name_index];
        $tree[$key] = $data;
    }
    return $tree;
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) 
    {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 

    // Uncomment one of the following alternatives
    $bytes /= pow(1024, $pow);
    //$bytes /= (1 << (10 * $pow)); 

    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 

function max_file_upload_in_bytes() {
    //select maximum upload size
    $max_upload = return_bytes(ini_get('upload_max_filesize'));
    //select post limit
    $max_post = return_bytes(ini_get('post_max_size'));
    //select memory limit
    $memory_limit = return_bytes(ini_get('memory_limit'));
    // return the smallest of them, this defines the real limit
    return intval($max_upload, $max_post, $memory_limit);
}

function max_file_upload_in_mbs() {
    return intval(max_file_upload_in_bytes()/1024);
}

function delete_record($id){
    global $curCfg, $mysql, $data_type, $tbCfgs, $dbprefix;
    $delete_query_data = array($curCfg['table_primarykey'] => $id);
    $delete_values = $mysql->getData($curCfg['table_name'], $delete_query_data);

    // check related data prevent delete
    // $related_fields = [];
    // foreach ($tbCfgs as $tbCfg) {
    //     foreach ($tbCfg['fields'] as $field) {
    //         if(in_array($field['field_type'], array('related', 'related2', 'related_checkbox', 'related_multiple')) && $field['related_table'] == $curCfg['table_index']){
    //             $field['admin_zconfigtable_index'] = $tbCfg['table_index'];
    //             $related_fields[] = $field;
    //         }
    //     }
    // }
    // $filter_dup = [];
    // foreach ($related_fields as $related_field) {
    //     if(!$delete_values[$related_field['related_key']] ||  $related_field['admin_zconfigtable_index'] == 'admin_zconfigtable_inlinelist'){continue;}
    //     if($related_field['field_type'] == 'related'){
    //         $related_table_name = $tbCfgs[$related_field['admin_zconfigtable_index']]['table_name'];
    //         if(!$filter_dup[$related_field['admin_zconfigtable_index']][$related_field['related_key']] && $mysql->rowCount($related_table_name, array($related_field['field_index'] => $delete_values[$related_field['related_key']]))){
    //             return 'cannot delete , data exists and related from '.$related_field['admin_zconfigtable_index'].' '. $related_field['field_index'];
    //         }
    //         $filter_dup[$related_field['admin_zconfigtable_index']][$related_field['related_key']] = true;
    //     } else if($related_field['field_type'] == 'related_multiple' || $related_field['field_type'] == 'related_checkbox' || $related_field['field_type'] == 'related2'){
    //         if(!$filter_dup[$related_field['admin_zconfigtable_index']][$related_field['related_key']]){
    //             if($related_field['related_to_table'] && $mysql->rowCount($related_field['related_to_table'], array($related_field['related_to_mykey'] => $delete_values[$related_field['related_to_mykey']]))){
    //                 return 'cannot delete , data exists and related from '.$related_field['admin_zconfigtable_index'].' '. $related_field['field_index'];
    //             } else if(!$related_field['related_to_table']) {
    //                 $searchQuery = [];
    //                 $searchQuery[] = $related_field['field_index']." LIKE '".$delete_values[$related_field['related_key']]."'";
    //                 $searchQuery[] = $related_field['field_index']." LIKE '".$delete_values[$related_field['related_key']].",%'";
    //                 $searchQuery[] = $related_field['field_index']." LIKE '%,".$delete_values[$related_field['related_key']].",%'";
    //                 $searchQuery[] = $related_field['field_index']." LIKE '%,".$delete_values[$related_field['related_key']]."'";
    //                 $stmt = $mysql->prepare("SELECT count(*) AS rowCount FROM ".$dbprefix."$related_field[admin_zconfigtable_index] WHERE ". join(' OR ', $searchQuery));
    //                 $stmt->execute();
    //                 $result = $stmt->fetchAll();
    //                 if($result[0]['rowCount']){
    //                     return 'cannot delete , data exists and related from '.$related_field['admin_zconfigtable_index'].' '. $related_field['field_index']. ' ' .$delete_values[$related_field['related_key']];
    //                 }
    //             }
    //         }
    //         $filter_dup[$related_field['admin_zconfigtable_index']][$related_field['related_key']] = true;
    //     }
    // }
    // get delte info & delete media
    
    $mysql->delete($curCfg['table_name'], $delete_query_data);
    if ($curCfg['table_order_field'] && $curCfg['table_order_type'] == 'order') {
        reOrder();
    }
    foreach ($curCfg['fields'] as $field){
        $data_type->{'dt_'.$field['field_type']}->config($field);
        $data_type->{'dt_'.$field['field_type']}->delete_record($delete_values);
    }
}

function log_admin_activity($type, $section='', $details=''){
    global $login, $mysql;
    $logdata = array(
        'admin_id' => $login->getAdminID(),
        'activity_type' => $type,
        'section' => $section,
        'date_added' => date('Y-m-d H:i:s'),
        'details' => $details
    );
    $mysql->create('admin_activity', $logdata);
}

function reOrder(){
    global $curCfg, $mysql;
    foreach (explode(',', $curCfg['table_order_field']) as $order_field) {
        if($order_field == $curCfg['table_primarykey']){continue;}
        $res = $mysql->getList($curCfg['table_name'], getParentQuery(), '*', "$order_field", 'ASC');
        $ord = 1;
        foreach ($res as $info) {
            $mysql->update($curCfg['table_name'], array($curCfg['table_primarykey'] => $info[$curCfg['table_primarykey']]), array($order_field => $ord));
            $ord++;
        }
    }
}
function is_relate_checked($tfield) {
    global $mysql, $tbCfgs, $_GET, $_POST;
    $reCfgs = $tbCfgs[$tfield['extra_opt'][0][0]];
    //$mysql->delete($tfield['extra_opt'][0][5], array($tfield['extra_opt'][0][6] => $_GET[$tfield['extra_opt'][0][6]]));
    $subList = $mysql->getList($reCfgs['table_name'], []);
    $checked = false;
    foreach ($subList as $sinfo) {
        if ($_POST[$tfield['field_index']. '_'.$tfield['related_table'] . '_' . $sinfo[$reCfgs['table_primarykey']]]) {
            $checked = true;
        }
    }
    return $checked;
}

function cloneArray($array) {
    $newArray = [];
    foreach ($array as $key => $value) {
        $newArray[$key] = $value;
    }
    return $newArray;
}
function rend_numbers($from, $to) {
    $array = [];
    for($i=$from;$i<=$to;$i++) {
        $array[] = $i;
    }
    return $array;
}
function array2csv($array, $show_header = null, $fields_name = null) {
    if (count($array) == 0) {
     return null;
    }
    ob_start();
    $df = fopen("php://output", 'w');
    $keys = [];
    $names = [];
    if($show_header) {
        foreach ($fields_name as $key => $value) {
            $names[] = $fields_name[$key];
            $keys[] = $key;
        }
        if($fields_name != null){
            fputcsv($df, $names, "\t");
        } else {
            fputcsv($df, $keys, "\t");
        }
    } else {
        foreach ($array[0] as $key => $value) {
            $names[] = $fields_name[$key];
            $keys[] = $key;
        }
    }
    foreach ($array as $row) {
        $values = [];
        foreach ($keys as $key) {
          $values[] = $row[$key];
        }
        fputcsv($df, $values, "\t");
    }
    fclose($df);
    return ob_get_clean();
}
function download_send_headers($filename) {
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");
    // force download  
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}
function get_systext($field, $lang = null) {
    global $selected_lang, $systext;
    if(isset($systext[$lang?$lang:$selected_lang][$field])) {
        return $systext[$lang?$lang:$selected_lang][$field];
    } else {
        return $field;
    }
}
function fieldOpt($opts) {
    $array = [];
    if(!is_array($opts)) {
        $opts = explode(',', $opts);
    }
    foreach ($opts as $opt) {
        $array[$opt] = true;
    }
    return $array;
}
function tableOpt($opts) {
    return fieldOpt($opts);
}

function get_time_array($time_slot, $is_24) {
    $array = array(''=>'');
    for($H=0;$H<24;$H++) {
        for($M=0;$M<60;$M+=$time_slot) {
            if($is_24) {
                $array[str_pad($H, 2, '0', 0).":".str_pad($M, 2, '0', 0).":00"] = str_pad($H, 2, '0', 0).":".str_pad($M, 2, '0', 0);
            } else {
                if($H>12) {
                    $array[str_pad($H, 2, '0', 0).":".str_pad($M, 2, '0', 0).":00"] = str_pad(($H-12), 2, '0', 0) .":".str_pad($M, 2, '0', 0)." PM";
                } else {
                    $array[str_pad($H, 2, '0', 0).":".str_pad($M, 2, '0', 0).":00"] = str_pad($H, 2, '0', 0).":".str_pad($M, 2, '0', 0)." AM";
                }
            }
        }
    }
    return $array;
}
function get_time_slot() {
    $array = array(
        '' => '',
        '15' => '15 mins',
        '30' => '30 mins',
        '60' => '1 hour',
        '120' => '2 hours',
        '180' => '3 hours',
        '240' => '4 hours',
        '300' => '5 hours',
        '360' => '6 hours',
    );
    return $array;
}


function getImageUrl($imageData, $disable_random=false) {
    global $mysql, $imageTable, $imagePrimarykey, $admin_imagefolder;
    if(!is_array($imageData)){
        $imageData = $mysql->getData($imageTable, array($imagePrimarykey => $imageData));
    }
    $folder = get_folder($admin_imagefolder, $imageData[$imagePrimarykey], $imageData['date_added']);
    if($disable_random){
        return "$folder/$imageData[filename_md5].$imageData[type]";
    } else {
        return "$folder/$imageData[filename_md5].$imageData[type]?t=".time();
    }
}
function getResizeImageUrl($imageData, $index, $w, $h, $type = 'resize', $jpgquality = 100) {
    global $admin_imagefolder;
    $folder = get_folder($admin_imagefolder, $imageData[$index], $imageData['date_added']);
    if($type == 'resize'){
        $destpath = "$folder/".$w."x".$h."_$imageData[filename_md5].$imageData[type]";
    } else {
        $destpath = "$folder/".$w."x".$h."_c_$imageData[filename_md5].$imageData[type]";
    }
    if(!file_exists($destpath) && file_exists(getImageUrl($imageData, true))){
        if ($type == 'resize') {
            ImageResizeV3(getImageUrl($imageData, true), $destpath, $imageData['type'], $w, $h);
        } else if ($type == 'crop') {
            ImageCropV3(getImageUrl($imageData, true), $destpath, $imageData['type'], $w, $h);
        }
    }
    return $destpath;
}
function getFileUrl($id) {
    global $mysql, $fileTable, $filePrimarykey, $admin_filefolder;
    $fileData = $mysql->getData($fileTable, array($filePrimarykey => $id));
    $folder = get_folder($admin_filefolder, $id, $fileData['date_added']);
    return "$folder/$fileData[filename_md5].$fileData[type]";
}

function getParentQuery() {
    global $curCfg, $_GET, $login, $dbprefix;
    $parent_query = null;
    if (isset($curCfg['parent_related_key']) && $curCfg['parent_related_key']) {
        $keys = explode(',', $curCfg['parent_related_key']);
        foreach ($keys as $key) {
            if (!isset($_GET[$key])) {
                $_GET[$key] = 0;
            }
            $parent_query[$key] = $_GET[$key];
        }
    }
    if($curCfg['table_name'] == 'admin' && $login->getAdminID() != 1) {
       $parent_query['admin_id'] = '!=1';
    }
    if($curCfg['table_name'] == 'admin_group' && $login->getAdminID() != 1) {
       $parent_query['admin_group_id'] = '!=1';
    }
    return $parent_query;
}

function getRelateValue($field, $id, $getRelateValueLevel = 0) {
    global $mysql, $tbCfgs, $selected_lang, $dbprefix, $imageTable, $imagePrimarykey, $admin_imagefolder, $_GET, $data_type;
    if($getRelateValueLevel > 5){
        die('$getRelateValueLevel > 5');
    }
    $value = '';
    $rtCfg = $tbCfgs[$field['related_table']];
    $rtOpts = tableOpt($rtCfg['option']);
    $related_table = $rtCfg['table_name'];
    if (!$field['related_key']) {
        $field['related_key'] = $field['field_index'];
    }
    if($id != ''){
        $relatedData = $mysql->getData($related_table, array($field['related_key'] => $id));
    } else if(!$field['related_extra_value']) {
        $relatedData = $mysql->getData($related_table, array($field['related_key'] => $id, $field['related_extra_key'] => $_GET[$field['related_extra_key']]));
    } else {
        $relatedData = $mysql->getData($related_table, array($field['related_key'] => $id, $field['related_extra_key'] => explode(',', $field['related_extra_value'])));
    }
    if(!$relatedData && $field['related_show_main']){
        $parentCfgs = $tbCfgs[$tbCfgs[$field['related_table']]['parent_table']];
        $relatedData = $mysql->getData($parentCfgs['table_name'], array($parentCfgs['table_primarykey'] => $id));
    }
    foreach (explode(',', $field['related_name']) as $fieldname) {
        foreach ($rtCfg['fields'] as $rekey => $reField){
            if($reField['field_index'] != $fieldname && $reField['field_index'] != $fieldname . '_' . $selected_lang){continue;}
            if($reField['field_type'] == 'image'){
                $imageData = $mysql->getData($imageTable, array($imagePrimarykey => $id));
                $folder = str_pad($relatedData[$field['related_name']], 11, "0", STD_PAD_LEFT);
                $value .= '<img height="40" src="' . getImageUrl($relatedData[$field['related_name']]) . '">';
            } else if($reField['field_type'] == 'file'){
                $data_type->{'dt_file'}->config($reField);
                $value .= $data_type->{'dt_file'}->list_value(array($reField['field_index']=>$relatedData[$field['related_name']]));
            } else if($reField['field_type'] == 'related'){
                $value .= getRelateValue($reField, $relatedData[$fieldname], $getRelateValueLevel+1).' ';
            } else if($rtOpts['support_language']){
                if($reField['field_index'] == $fieldname . '_' . $selected_lang && $relatedData[$fieldname . '_' . $selected_lang]){
                    $value .= $relatedData[$fieldname . '_' . $selected_lang].' ';
                }
            } else {
                $data_type->{'dt_'.$reField['field_type']}->config($reField);
                $value .= $data_type->{'dt_'.$reField['field_type']}->list_value(array($reField['field_index']=>$relatedData[$reField['field_index']]));
                //$value .= $relatedData[$reField['field_index']].' ';
            }
        }
    }
    return $value;
}
function deleteFolder($path) {
    if (is_dir($path) === true) {
        $files = array_diff(scandir($path), array('.', '..'));
        foreach ($files as $file) {
            deleteFolder(realpath($path) . '/' . $file);
        }
        return rmdir($path);
    } else if (is_file($path) === true) {
        return unlink($path);
    }
    return false;
}
class listRecord {
    var $perPage = 60;
    var $perIndex = 15;
    var $splitChar = '';
    var $showNextPre = true;
    var $showIndex = true;
    var $showFirstLast = false;
    var $buttonPre = 'Previous';
    var $buttonNext = 'Next';
    #index button setting
    var $buttonIndexColor = 'green';
    var $buttonIndexFontSize = '2';
    var $buttonIndexPre = '&lt;&lt;';
    var $buttonIndexNext = '&gt;&gt;';
    var $callIndex = 0;
    function pageButton($callPage, $totalRow, $link)
    {
        $perPage = $this->perPage;
        $perIndex = $this->perIndex;
        if (!$callPage) {
            $callPage = 1;
        }
        if ($totalRow % $perPage) {
            $totalPage = (($totalRow - ($totalRow % $perPage))) / $perPage + 1;
        } else {
            $totalPage = $totalRow / $perPage;
        }
        if ($totalPage <= 1) {
            return false;
        }
        if ($callPage % $perIndex) {
            $Start = $callPage - ($callPage % $perIndex) + 1;
        } else {
            $Start = $callPage - $perIndex + 1;
        }
        $End = $Start + $perIndex - 1;
        $printIcon = '';
        if ($Start > 1 && $this->showIndex == true) {
            $Call = $Start - $perIndex;
            $printIcon = "<li><a href=\"$link&$this->callIndex=$Call\">$this->buttonIndexPre</a></li>";
        }
        for ($a = $Start; $a <= $End && $a <= $totalPage; $a++) {
            if ($a == $callPage) {
                $arrayIcon[] = '<li class="active"><a href="#">' . $a . '</a></li>';
            } else {
                $arrayIcon[] = "<li><a href=\"$link&$this->callIndex=$a\">$a</a></li>";
            }
        }
        if ($this->showNextPre == true) {
            if ($callPage != 1) {
                array_unshift($arrayIcon, "<li><a href=\"$link&$this->callIndex=" . ($callPage - 1) . "\">$this->buttonPre</a></li>");
            } else {
                array_unshift($arrayIcon, "<li class=\"paginate_button previous disabled\"><a href=\"#\">$this->buttonPre</a></li>");
            }
            if ($callPage < $totalPage) {
                $arrayIcon[] = "<li><a href=\"$link&$this->callIndex=" . ($callPage + 1) . "\">$this->buttonNext</a></li>";
            } else {
                $arrayIcon[] = "<li class=\"paginate_button next disabled\"><a href=\"#\">$this->buttonNext</a></li>";
            }
        }
        if(count($arrayIcon)) {
            $printIcon .= join($this->splitChar, $arrayIcon);
            if ($totalPage > ($Start + $perIndex - 1) && $this->showIndex == true) {
                $printIcon .= "<li><a href=\"$link&$this->callIndex=" . ($Start + $perIndex) . "\">$this->buttonIndexNext</a></li>";
            }
            $printIcon = '<div class="dataTables_paginate paging_simple_numbers" id="datatable_paginate">
                            <ul class="pagination">'.$printIcon.'</ul>
                        </div>';
        }
        
        return $printIcon;
    }
}
?>