<?php
class dt_related_multiple extends data_type {
    private $config;
    public function mysql_field_type(){
        return 'text null';
    }
    public function delete_record($values){
        global $tbCfgs;
        $field = $this->config();
        if($field['related_to_table']){
            $keyindex = $field['related_to_mykey']?$field['related_to_mykey']:$field['related_key'];
            if($keyindex && $values[$keyindex]){
                $this->mysql->delete($field['related_to_table'], array($keyindex => $values[$keyindex]));
            }
        }
    }
    public function config($field=false){
        if($field){
            $this->clear_config();
            $this->config = parent::config($field);
        }
        return $this->config;
    }
    public function filter_html($value){
        $field = $this->config();
        global $tbCfgs, $selected_lang, $formerror, $_POST, $_GET;
        $rtCfg = $tbCfgs[$field['related_table']];
        $field['field_index'] = isset($field['field_index'])&&$field['field_index']?$field['field_index']:$field['related_table'];
        $rtOpts = tableOpt($rtCfg['option']);
        $html = '<select class="Filter_'.$field['field_index'].' form-control" name="'.$field['field_index'].'" id="'.$field['field_index'].'">';
        $html .= '<option value="">'.$field['field_name'].':'.get_systext('filter_all').'</option>';

        if (isset($rtCfg['table_type']) && $rtCfg['table_type'] == 'tree') {
            if (isset($rtCfg['parent_table']) && $rtCfg['parent_table']) {
                $related_keys = explode(',', $rtCfg['parent_related_key']);
                $related_query = [];
                foreach ($related_keys as $related_key){
                    $related_query[$related_key] = $_GET[$related_key];
                }
                $selectList = $this->mysql->getList($rtCfg['table_name'], $related_query, '*', $rtCfg['table_order_field']);
            } else {
                $selectList = $this->mysql->getList($rtCfg['table_name'], null, '*', array($rtCfg['table_order_field']));
            }
            $dataByID = [];
            foreach ($selectList as $data) {
                $dataByID[$data[$rtCfg['table_primarykey']]] = $data;
            }
        } else if (isset($rtCfg['table_type']) && $rtCfg['parent_table'] && $rtCfg['table_type'] == 'sublist') {
            $selectList = $this->mysql->getList($rtCfg['table_name'], null, '*', array($rtCfg['table_order_field']));
            $parentCfgs = $tbCfgs[$rtCfg['parent_table']];
            $mtOpts = tableOpt($parentCfgs['option']);
            if($field['related_extra_key'] && isset($field['related_extra_value']) && $field['related_extra_value'] != ''){
                $mainList = $this->mysql->getList($parentCfgs['table_name'], array($field['related_extra_key'] => explode(',', $field['related_extra_value'])),
                '*', $parentCfgs['table_order_field'], $parentCfgs['table_order_default_direction']);
            } else {
                $mainList = $this->mysql->getList($parentCfgs['table_name'], [], '*', $parentCfgs['table_order_field'], $parentCfgs['table_order_default_direction']);
            }
            $dataByID = [];
            foreach ($mainList as $data) {
                $dataByID[$data[$parentCfgs['table_primarykey']]] = $data;
            }
        } else {
            if($field['related_extra_key'] && isset($field['related_extra_value']) && $field['related_extra_value'] != ''){
                $selectList = $this->mysql->getList($rtCfg['table_name'], array($field['related_extra_key'] => explode(',', $field['related_extra_value'])),
                '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
            } else {
                $selectList = $this->mysql->getList($rtCfg['table_name'], [], '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
            }
        }
        $fieldType = '';
        foreach ($rtCfg['fields'] as $reField) {
            if ($reField['field_index'] == $field['related_name']) {
                $fieldType = $reField['field_type'];
            }
        }
        if (!$field['related_key']) {
            $field['related_key'] =$rtCfg['table_primarykey'];
        }
        foreach ($selectList as $info) {
            if (isset($rtCfg['table_type']) && $rtCfg['table_type'] == 'sublist'){
                if($parentCfgs['table_name'] == $rtCfg['table_name'] && $field['related_extra_key'] && $info[$field['related_extra_key']] == $field['related_extra_value']){
                    continue;
                }
            }
            $html .= '<option '.($fieldType == 'image'?' data-imagesrc="' . getImageUrl($info[$field['related_name']]) . '"':'');
            if ((isset($_POST[$field['field_index']]) && $info[$field['related_key']] == $_POST[$field['field_index']]) || (isset($_GET[$field['field_index']]) && $info[$field['related_key']] == $_GET[$field['field_index']])) { 
                $html .= ' selected ';
            }
            if (isset($rtCfg['table_type']) && $rtCfg['table_type'] == 'tree') {
                $parent_name = isset($dataByID[$info[$rtCfg['table_parent_id']]][$field['related_name']])?$dataByID[$info[$rtCfg['table_parent_id']]][$field['related_name']]:'';
            } else if (isset($rtCfg['table_type']) && $rtCfg['table_type'] == 'sublist') {
                $parent_name = isset($dataByID[$info[$rtCfg['parent_related_key']]][$rtCfg['parent_show_title']])?$dataByID[$info[$rtCfg['parent_related_key']]][$rtCfg['parent_show_title']]:'';
            }
            if($parent_name){
                $parent_name = '/'.$parent_name;
            }
            $values = [];
            if (preg_match('/\,/is', $field['related_name'])) {
                foreach (explode(',', $field['related_name']) as $fieldname) {
                    $key = isset($rtOpts['support_language']) && $rtOpts['support_language'] && $info[$fieldname . '_' . $selected_lang] ? $fieldname . '_' . $selected_lang : $fieldname;
                    $val = $info[$key];
                    if($rtCfg['fbi'][$key]['field_type'] == 'related'){
                        $val = getRelateValue($rtCfg['fbi'][$key], $val);
                    }
                    $values[] = $val;
                } 
                $info[$field['related_name']] = join(' ', $values);
            } else {
                $key = isset($rtOpts['support_language']) && $rtOpts['support_language'] && $info[$field['related_name'] . '_' . $selected_lang] ? $field['related_name'] . '_' . $selected_lang : $field['related_name'];
                $val = $info[$key];
                if($rtCfg['fbi'][$key]['field_type'] == 'related'){
                    $val = getRelateValue($rtCfg['fbi'][$key], $val);
                }
                $values[] = $val;
            }
            $html .= 'value="'.$info[$field['related_key']].'">123123'.$parent_name.'/'.$info[$field['related_name']].'</option>';
        }
        $html .= '</select>';
        if (count($selectList) > 20) {
            $html .= '<script type="text/javascript">';
            $html .= '$(document).ready(function() {';
            $html .= '$(".Filter_'.$field['field_index'].'").select2({';
            $html .= 'maximumSelectionLength: 10,';
            $html .= 'allowClear: true';
            $html .= '}); });';
            $html .= '</script>';
        }
        return $html;
    }

    // build html form field
    public function form_html($value, $formerror, $name= false){
        global $tbCfgs, $_GET, $selected_lang, $field, $relate_checked, $rtOpts, $data_type, $_POST;
        $field = $this->config();
        $relate_checked = [];
        global $rtCfg;
        $rtCfg = $tbCfgs[$field['related_table']];
        
        if ($field['field_index'] && isset($value[$field['field_index']])) {
            foreach (explode(',', $value[$field['field_index']]) as $index) {
                if (!$index) {
                    continue;
                }
                $relate_checked[$index] = true;
            }
        } else if($field['related_to_mykey']){
            $relateList = $this->mysql->getList($field['related_to_table'], array($field['related_to_mykey'] => $_GET[$field['related_to_mykey']]));
            foreach ($relateList as $rinfo) {
                $relate_checked[$rinfo[$rtCfg['table_primarykey']]] = true;
            }
        }

        $rtOpts = tableOpt($rtCfg['option']);
        
        if(!$field['hide_checkall'] && !$field['html_type']){
            $output = '<div style="clear:both; margin-top:8px;"></div>';
            $output .= '<input type="checkbox" data-index="'.$field['field_index'].'" class="flat icheckall"><label>ALL</label><div style="clear:both;"></div>';
        }
        
        if (isset($rtCfg['table_type']) && $rtCfg['table_type'] == 'sublist' && $rtCfg['parent_table']) {
            $parentCfgs = $tbCfgs[$rtCfg['parent_table']];
            $mtOpts = tableOpt($parentCfgs['option']);
            if(isset($field['related_extra_value']) && $field['related_extra_key'] && $field['related_extra_value'] != ''){
                $mainList = $this->mysql->getList($parentCfgs['table_name'], array($field['related_extra_key'] => explode(',', $field['related_extra_value'])),
                '*', $parentCfgs['table_order_field'], $parentCfgs['table_order_default_direction']);
            } else {
                $mainList = $this->mysql->getList($parentCfgs['table_name'], [], '*', $parentCfgs['table_order_field'], $parentCfgs['table_order_default_direction']);
            }
            if ($field['html_type'] == 1) {
                $output .= '<select multiple="multiple" class="R_'.$field['field_index'].' '. (isset($formerror[$field['field_index']])?'parsley-errorr ':'') .'form-control" jf="'.$field['field_index'].'" name="'.($name?$name:$field['field_index']).'[]" id="'.$field['field_index'].'">';
            }
            foreach ($mainList as $minfo) {
                if (!$field['html_type']) {
                    $output .= '<div style="clear:both;"></div>';
                    $output .= '<p class="help-block"  style="margin:0px;margin-top:10px;"><b>';
                    $output .= (isset($mtOpts['support_language']) && $mtOpts['support_language']) ? $minfo[$rtCfg['parent_show_title'] . '_' . $selected_lang] : $minfo[$rtCfg['parent_show_title']];
                    $output .= '</b></p>';
                }
                if($tbCfgs[$rtCfg['parent_table']]['table_index'] == $field['related_table']){
                    
                    $subList = $this->mysql->getList($rtCfg['table_name'], array($rtCfg['parent_related_key'] => $minfo[$rtCfg['table_primarykey']]),
                    '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
                } else {
                    
                    $related_keys = explode(',', $rtCfg['parent_related_key']);
                    $related_query = [];
                    foreach ($related_keys as $related_key){
                        $related_query[$related_key] = $minfo[$parentCfgs['table_primarykey']];
                    }
                    $subList = $this->mysql->getList($rtCfg['table_name'], $related_query,
                    '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
                }

                foreach ($subList as $sinfo) {
                    $checked = '';
                    $selected = '';
                    if ((isset($relate_checked[$sinfo[$rtCfg['table_primarykey']]]) && $relate_checked[$sinfo[$rtCfg['table_primarykey']]]) || 
                               (isset($value[$field['field_index']. '_'.$field['related_table'] . '_' . $sinfo[$rtCfg['table_primarykey']]]) && $value[$field['field_index']. '_'.$field['related_table'] . '_' . $sinfo[$rtCfg['table_primarykey']]] && $field['html_type'] == false)){
                        $checked = 'checked';
                        $selected = 'selected';
                    }
                    if ($field['html_type'] == false){
                        $output .= '<p class="help-block" style="float:left;margin:0px;margin-right:20px;">';
                        $output .= '<input type="checkbox" class="flat" '.$checked.'
                                   name="'. $field['field_index']. '_' . $field['related_table'] . '_' . $sinfo[$rtCfg['table_primarykey']].'"
                                   id="'. $field['field_index']. '_'.$field['related_table'] . '_' . $sinfo[$rtCfg['table_primarykey']].'" value="1">';
                        $output .= '<label for="'.$field['related_table'] . '_' . $sinfo[$rtCfg['table_primarykey']].'"> ';
                        $output .= isset($rtOpts['support_language']) && $rtOpts['support_language'] ? $sinfo[$field['related_name'] . '_' . $selected_lang] : $sinfo[$field['related_name']];
                        $output .= '</label></p>';
                    } else if ($field['html_type'] == 1){
                        $output .= '<option  value="'.$sinfo[$rtCfg['table_primarykey']].'" '.$selected.'>'.(isset($rtOpts['support_language']) && $rtOpts['support_language'] ? $sinfo[$field['related_name'] . '_' . $selected_lang] : $sinfo[$field['related_name']]).'</option>';
                    }
                }
            }
            if (!$field['html_type']){
                $output .= '<div style="clear:both;"></div>';
            } else if ($field['html_type'] == 1) {
                $output .= '</select>';
                if(count($subList) > 5) {
                    $output .= '<script type="text/javascript">';
                    $output .= '$(document).ready(function() {';
                    $output .= '$(".R_'.$field['field_index'].'").select2({';
                    $output .= 'maximumSelectionLength: 10,';
                    $output .= 'allowClear: true';
                    $output .= '}); });';
                    $output .= '</script>';
                }
            }
        } else if (isset($rtCfg['table_type']) && $rtCfg['table_type'] == 'tree') {
            global $dataByID, $subList;
            if($field['related_key'] && isset($_GET[$field['related_key']])){
                if($field['related_extra_key'] && $field['related_extra_value']){
                    $subList = $this->mysql->getList($rtCfg['table_name'], array(
                        $field['related_key'] => $_GET[$field['related_key']],
                        $field['related_extra_key'] => explode(',', $field['related_extra_value'])
                    ));
                } else {
                    $subList = $this->mysql->getList($rtCfg['table_name'], array($field['related_key'] => $_GET[$field['related_key']]));
                }
            } else if($field['related_extra_key'] && $field['related_extra_value'] !== ''){
                $subList = $this->mysql->getList($rtCfg['table_name'], array($field['related_extra_key'] => $field['related_extra_value']));
            } else {
                $subList = $this->mysql->getList($rtCfg['table_name'], []);
            }
            $dataByID = [];
            foreach ($subList as $data) {
                $dataByID[$data[$rtCfg['table_primarykey']]] = $data;
            }
            function getListByParent2($allData, $parentID) {
                global $rtCfg;
                $lists = [];
                foreach ($allData as $row) {
                    if ($row[$rtCfg['table_parent_id']] == $parentID) {
                        $lists[] = $row;
                    }
                }
                return $lists;
            }
            
            function getTree($parentID){
                global $rtCfg, $subList, $field, $rtOpts, $level, $relate_checked, $selected_lang;
                $mainHtml = '';
                foreach (getListByParent2($subList, $parentID) as $key => $row) {
                    $catid = $row[$rtCfg['table_primarykey']];
                    $numRow = $key != count(getListByParent2($subList, $parentID)) - 1 && count(getListByParent2($subList, $parentID)) != 1;
                    $subhtml = '';
                    if (count(getListByParent2($subList, $catid))) {
                        if ($numRow == true) {
                            $pix = '<img border="0" src="images/tree1.gif">';
                            $top = 'background="images/tree5.gif"';
                        } else {
                            $pix = '<img border="0"  src="images/tree8.gif">';
                            $top = '';
                        }
                        $subhtml = "<tr><td $top></td><td colspan=\"2\">" . getTree($catid) . "</td></tr>";
                        $folder = '<img alt="" src="images/admin_folder.gif" border="0">';
                    } else {
                        if ($numRow == true) {
                            $pix = '<img src="images/tree7.gif">';
                        } else {
                            $pix = '<img src="images/tree2.gif">';
                        }
                        $folder = '<img alt="" src="images/admin_folder02.gif" border="0">';
                    }

                    if ($numRow == true) {
                        $lineBG = 'background="images/tree5.gif"';
                    } else {
                        $lineBG = '';
                    }
                    $strlimit = 30 - $level * 2;


                    if (isset($_GET['folder']) && isset($path) && $_GET['folder'] == $path) {
                        $bgcolor = "#99ccff";
                    } else {
                        $bgcolor = "";
                    }

                    $label = strip_tags(isset($rtOpts['support_language']) ? $row[$field['related_name'] . '_' . $selected_lang] : $row[$field['related_name']]);

                    if(!$label){continue;}
                    $checked = '';
                    $output = '';
                    if ((isset($relate_checked[$row[$rtCfg['table_primarykey']]]) && $relate_checked[$row[$rtCfg['table_primarykey']]]) || (isset($value[$field['field_index'].'_'.$field['related_table'].'_' . $row[$rtCfg['table_primarykey']]]) && $value[$field['field_index'].'_'.$field['related_table'].'_'.$row[$rtCfg['table_primarykey']]] && $field['html_type'] == false)){
                        $checked = 'checked';
                    }

                    $output .= '<p class="help-block" style="float:left;margin:0px;margin-right:20px;">';
                    $output .= '<input type="checkbox" class="flat" name="'. $field['field_index']. '_'.$field['related_table'].'_'.$row[$rtCfg['table_primarykey']].'" '.$checked;
                    $output .= ' id="'. $field['field_index']. '_'.$field['related_table'] . '_' . $row[$rtCfg['table_primarykey']].'" value="1">';
                    $output .= '<label for="'.$field['related_table'] . '_' . $row[$rtCfg['table_primarykey']].'">'.$label.'</label></p>';

                    $mainHtml .= "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr onmouseover=\"style.backgroundColor = '#f39c12';\"  onmouseout=\"style.backgroundColor = '';\"  >
                    <td width=\"19\" height=\"25\">$pix</td>
                    <td width=\"20\" align=\"center\">$folder</td>
                    <td width=\"" . (450 - ($level * 19) - 20 - 19) . "\" align=\"left\" class=\"normal\">

                    $output </td>
                    </tr>
                    $subhtml
                    </table>";
                }
                $level--;
                return $mainHtml;
            }
            $output = getTree(0);
        } else {
            if($rtCfg['table_index'] == 'admin_zconfigtable_fields'){
                $related_extra_value = array_flip(explode(',', $field['related_extra_value']));
                foreach ($tbCfgs as $tbc) {
                    if($tbc['admin_zconfigtable_id'] == $_GET[$field['related_filterkey']]){
                        foreach ($tbc['fields'] as $v) {
                            if(isset($related_extra_value[$v['field_type']])){
                                $subList[] = $v;
                            }
                        }
                        break;
                    }
                }
            } else if($field['related_filterkey']){
                $related_filterValue = $_GET[$field['related_filterkey']]?$_GET[$field['related_filterkey']]:0;
                if($field['related_extra_key'] && $field['related_extra_value']){
                    $subList = $this->mysql->getList($rtCfg['table_name'], array(
                        $field['related_filterkey'] => $related_filterValue,
                        $field['related_extra_key'] => explode(',', $field['related_extra_value'])
                    ), '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
                } else {
                    $subList = $this->mysql->getList($rtCfg['table_name'], array($field['related_filterkey'] => $related_filterValue), '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
                }
            } else if($field['related_extra_key'] && $field['related_extra_value']) {
                $subList = $this->mysql->getList($rtCfg['table_name'], array(
                    $field['related_extra_key'] => explode(',', $field['related_extra_value'])
                ), '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
            } else {
                $subList = $this->mysql->getList($rtCfg['table_name'], [], '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
            }
            $dataByID = [];
            foreach ($subList as $data) {
                $dataByID[$data[$rtCfg['table_primarykey']]] = $data;
            }
            if ($field['html_type'] == 1) {
                $output .= '<select multiple="multiple" class="R_'.$field['field_index'].' '. (isset($formerror[$field['field_index']])?'parsley-errorr ':'') .'form-control" jf="'.$field['field_index'].'" name="'.($name?$name:$field['field_index']).'[]" id="'.$field['field_index'].'">';
            }
            foreach ($subList as $sinfo) {

                $parent_name = isset($sinfo[$rtCfg['table_parent_id']]) && isset($dataByID[$sinfo[$rtCfg['table_parent_id']]][$field['related_name']])?$dataByID[$sinfo[$rtCfg['table_parent_id']]][$field['related_name']].'/':'';
                $rnvalues = [];
                foreach (explode(",", $field['related_name']) as $fieldname) {
                    $rnkey = isset($rtOpts['support_language']) && $rtOpts['support_language'] && $sinfo[$fieldname . '_' . $selected_lang] ? $fieldname . '_' . $selected_lang : $fieldname;
                    $data_type->{'dt_'.$rtCfg['fbi'][$rnkey]['field_type']}->config($rtCfg['fbi'][$rnkey]);
                    $rnvalues[] = $data_type->{'dt_'.$rtCfg['fbi'][$rnkey]['field_type']}->list_value(array($rnkey=>$sinfo[$rnkey]));
                }
                $label = join(',', $rnvalues);

                //$label = strip_tags(isset($rtOpts['support_language']) ? $sinfo[$field['related_name'] . '_' . $selected_lang] : $sinfo[$field['related_name']]);
                //echo $sinfo[$field['related_name']];
                if(!$label){continue;}
                $checked = '';
                $selected = '';
                if ((isset($relate_checked[$sinfo[$rtCfg['table_primarykey']]]) && $relate_checked[$sinfo[$rtCfg['table_primarykey']]]) || (isset($value[$field['field_index'].'_'.$field['related_table'] . '_' . $sinfo[$rtCfg['table_primarykey']]]) && $value[$field['field_index'].'_'.$field['related_table'].'_' . $sinfo[$rtCfg['table_primarykey']]] && $field['html_type'] == false) || ($field['html_type'] == 1 && in_array($sinfo[$rtCfg['table_primarykey']], $value[$field['field_index']]))){
                    $checked = 'checked';
                    $selected = 'selected';
                }
                if ($field['html_type'] == false){
                    $output .= '<p class="help-block" style="float:left;margin:0px;margin-right:20px;">';
                    $output .= '<input type="checkbox" class="flat" name="'. $field['field_index']. '_'.$field['related_table'].'_'.$sinfo[$rtCfg['table_primarykey']].'" '.$checked;
                    $output .= ' id="'. $field['field_index']. '_'.$field['related_table'] . '_' . $sinfo[$rtCfg['table_primarykey']].'" value="1">';
                    $output .= '<label for="'.$field['field_index']. '_'.$field['related_table'] . '_' . $sinfo[$rtCfg['table_primarykey']].'">'.$parent_name.$label.'</label></p>';
                } else if ($field['html_type'] == 1){
                    $output .= '<option  value="'.$sinfo[$rtCfg['table_primarykey']].'" '.$selected.'>'.$parent_name.$label.'</option>';
                }
            }
            if (!$field['html_type']){
                $output .= '<div style="clear:both;"></div>';
            } else if ($field['html_type'] == 1) {
                $output .= '</select>';
                if(count($subList) > 5) {
                    $output .= '<script type="text/javascript">';
                    $output .= '$(document).ready(function() {';
                    $output .= '$(".R_'.$field['field_index'].'").select2({';
                    $output .= 'maximumSelectionLength: 10,';
                    $output .= 'allowClear: true';
                    $output .= '}); });';
                    $output .= '</script>';
                }
            }
        }
        return $output;
    }

    // 處理  before form submit
    public function form_validate($value){
        $sqlskip = false;
        $data = false;
        $error = false;
        $field = $this->config();
        if($field['field_index']){
            $data = $this->process_data();
        }
        return array($sqlskip, $data, $error);
    }

    // 處理 after submit form
    public function form_after_duplicate(){
        $field = $this->config();
        $fieldOpts = fieldOpt($field['field_options']);
        if(!$field['field_index'] || $fieldOpts['skipsql']){
            $this->process_data();
        }
    }

    // 處理 after submit form
    public function form_after_submit(){
        $field = $this->config();
        if(!$field['field_index']){
            $this->process_data();
        }
    }

    private function process_data(){
        global $_POST, $_GET, $tbCfgs;
        $field = $this->config();
        $datas = [];
        $rtCfg = $tbCfgs[$field['related_table']];
        if (isset($rtCfg['table_type']) && $rtCfg['table_type'] == 'sublist' && $rtCfg['parent_table']) {
            if($field['related_to_table']){
                $this->mysql->delete($field['related_to_table'], array($field['related_to_mykey'] => $_GET[$field['related_to_mykey']]));
            }
            if($field['related_extra_key'] && isset($field['related_extra_value'])){
                $mainList = $this->mysql->getList($tbCfgs[$rtCfg['parent_table']]['table_name'], array(
                    $field['related_extra_key'] => explode(',', $field['related_extra_value'])
                ), '*', $tbCfgs[$rtCfg['parent_table']]['table_order_field'], $tbCfgs[$rtCfg['parent_table']]['table_order_default_direction']);
            } else {
                $mainList = $this->mysql->getList($tbCfgs[$rtCfg['parent_table']]['table_name'], [], '*', $tbCfgs[$rtCfg['parent_table']]['table_order_field'], $tbCfgs[$rtCfg['parent_table']]['table_order_default_direction']);
            }
            foreach ($mainList as $minfo) {
                if($tbCfgs[$rtCfg['parent_table']]['table_name'] == $tbCfgs[$field['related_table']]['table_name']){
                    $subList = $this->mysql->getList($rtCfg['table_name'], array($rtCfg['parent_related_key'] => $minfo[$rtCfg['table_primarykey']]), '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
                } else {
                    $subList = $this->mysql->getList($rtCfg['table_name'], array($rtCfg['parent_related_key'] => $minfo[$rtCfg['parent_related_key']]), '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
                }
                foreach ($subList as $sinfo) {
                    if (($_POST[$field['field_index']. '_'.$field['related_table'] . '_' . $sinfo[$rtCfg['table_primarykey']]] && !$field['html_type']) || ($field['html_type'] == 1 && in_array($sinfo[$rtCfg['table_primarykey']], $_POST[$field['field_index']]))) {
                        if($field['related_to_table'] && $_GET[$field['related_to_mykey']]){
                            $this->mysql->create($field['related_to_table'], array($field['related_to_mykey'] => $_GET[$field['related_to_mykey']], $rtCfg['table_primarykey'] => $sinfo[$rtCfg['table_primarykey']]));
                        }
                        $datas[] = $sinfo[$rtCfg['table_primarykey']];
                    }
                }
            }
        } else {
            if($field['related_to_table']){
                $this->mysql->delete($field['related_to_table'], array($field['related_to_mykey'] => $_GET[$field['related_to_mykey']]));
            }
            if($rtCfg['table_index'] == 'admin_zconfigtable_fields'){
                $related_extra_value = array_flip(explode(',', $field['related_extra_value']));
                foreach ($tbCfgs as $tbc) {
                    if($tbc['admin_zconfigtable_id'] == $_GET[$field['related_filterkey']]){
                        foreach ($tbc['fields'] as $v) {
                            if(isset($related_extra_value[$v['field_type']])){
                                $subList[] = $v;
                            }
                        }
                        break;
                    }
                }
            } else if($field['related_filterkey']){
                $related_filterValue = $_GET[$field['related_filterkey']]?$_GET[$field['related_filterkey']]:0;
                if($field['related_extra_key'] && $field['related_extra_value']){
                    $subList = $this->mysql->getList($rtCfg['table_name'], array(
                        $field['related_filterkey'] => $related_filterValue,
                        $field['related_extra_key'] => explode(',', $field['related_extra_value'])
                    ), '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
                } else {
                    $subList = $this->mysql->getList($rtCfg['table_name'], array($field['related_filterkey'] => $related_filterValue), '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
                }
            } else if($field['related_extra_key'] && $field['related_extra_value']) {
                $subList = $this->mysql->getList($rtCfg['table_name'], array(
                    $field['related_extra_key'] => explode(',', $field['related_extra_value'])
                ), '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
            } else {
                $subList = $this->mysql->getList($rtCfg['table_name'], [], '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
            }
            foreach ($subList as $sinfo) {
                if (($_POST[$field['field_index']. '_'.$field['related_table'] . '_' . $sinfo[$rtCfg['table_primarykey']]] && !$field['html_type']) || ($field['html_type'] == 1 && in_array($sinfo[$rtCfg['table_primarykey']], $_POST[$field['field_index']]))) {
                    if($field['related_to_table'] && $_GET[$field['related_to_mykey']]){
                        $this->mysql->create($field['related_to_table'], array($field['related_to_mykey'] => $_GET[$field['related_to_mykey']], $rtCfg['table_primarykey'] => $sinfo[$rtCfg['table_primarykey']]));
                    }
                    $datas[] = $sinfo[$rtCfg['table_primarykey']];
                }
            }
        }
        return $datas?join(',', $datas):false;
    }

    // build list value
    public function list_value($values){
        global $_POST, $_GET, $tbCfgs, $selected_lang, $data_type;
        global $tbCfgs, $selected_lang, $dbprefix, $imageTable, $imagePrimarykey, $admin_imagefolder;
        $field = $this->config();
        $rtCfg = $tbCfgs[$field['related_table']];
        $rtOpts = tableOpt($rtCfg['option']);
        if ($field['field_index'] && isset($values[$field['field_index']])) {
            foreach (explode(',', $values[$field['field_index']]) as $index) {
                if (!$index) {
                    continue;
                }
                $relate_checked[$index] = true;
            }
        } else if(!$field['field_index'] && $field['related_to_table']){
            $relateList = $this->mysql->getList($field['related_to_table'], array($field['related_to_mykey'] => $values[$field['related_to_mykey']]));
            foreach ($relateList as $rinfo) {
                $relate_checked[$rinfo[$rtCfg['table_primarykey']]] = true;
            }
        }
        
        $key = $field['related_key']?$field['related_key']:$rtCfg['table_primarykey'];
        $list = $this->mysql->getList($rtCfg['table_name'], array($key => array_keys($relate_checked)), '*', $rtCfg['table_order_field'], $rtCfg['table_order_default_direction']);
        foreach ($list as $info) {
            $rnvalues = [];
            foreach (explode(",", $field['related_name']) as $fieldname) {

                $rnkey = isset($rtOpts['support_language']) && $rtOpts['support_language'] && $info[$fieldname . '_' . $selected_lang] ? $fieldname . '_' . $selected_lang : $fieldname;
                $data_type->{'dt_'.$rtCfg['fbi'][$rnkey]['field_type']}->config($rtCfg['fbi'][$rnkey]);
                $rnvalues[] = $data_type->{'dt_'.$rtCfg['fbi'][$rnkey]['field_type']}->list_value(array($rnkey=>$info[$rnkey]));
            }
            $datas[] = join(',', $rnvalues);
        }
        return join(', ', $datas);
    }
}
?>