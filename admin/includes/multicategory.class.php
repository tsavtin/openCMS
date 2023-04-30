<?php

class multiCat {

    var $indexKeyName = 'category_id';
    var $indexfieldName = 'name';
    var $indexParentName = 'name';

    var $imageUrl = 'images/';
    var $pathSpliter = '||';
    var $extraLink = '';
    var $tdWidth = 2500;

    function getListByParent($allData, $parentID) {
        $lists = [];
        foreach ($allData as $row) {
            if ($row[$this->indexParentName] == $parentID) {
                $lists[] = $row;
            }
        }
        return $lists;
    }

    function getTree($allData, $parentID) {
        global $curCfg, $text_system, $curCfg;
        $level = 0;
        $mainHtml = '';
        foreach ($this->getListByParent($allData, $parentID) as $key => $row) {
            $catid = $row[$this->indexKeyName];
            $numRow = $key != count($this->getListByParent($allData, $parentID)) - 1 && count($this->getListByParent($allData, $parentID)) != 1;
            $subhtml = '';
            if (count($this->getListByParent($allData, $catid))) {
                if (isset($_SESSION['open_' . $catid]) && $_SESSION['open_' . $catid] == true) {
                    if ($numRow == true) {
                        $pix = '<a href="?' . get_link() . '&stage=close&folder=' . $catid . '"><img border="0" src="' . $this->imageUrl . 'tree1.gif"></a>';
                        $top = 'background="' . $this->imageUrl . 'tree5.gif"';
                    } else {
                        $pix = '<a href="?' . get_link() . '&stage=close&folder=' . $catid . '"><img border="0"  src="' . $this->imageUrl . 'tree8.gif"></a>';
                        $top = '';
                    }
                    $subhtml = "<tr><td $top>&nbsp;</td><td colspan=\"2\">" . $this->getTree($allData, $catid) . "</td></tr>";
                    $folder = '<img alt="" src="' . $this->imageUrl . 'admin_folder.gif" border="0">';
                } else {
                    if ($numRow == true) {
                        $pix = '<a href="?' . get_link() . '&stage=open&folder=' . $catid . '"><img border="0" src="' . $this->imageUrl . 'tree10.gif"></a>';
                    } else {
                        $pix = '<a href="?' . get_link() . '&stage=open&folder=' . $catid . '"><img border="0"  src="' . $this->imageUrl . 'tree11.gif"></a>';
                    }
                    $folder = '<img alt="" src="' . $this->imageUrl . 'admin_folder02.gif" border="0">';
                }

            } else {
                if ($numRow == true) {
                    $pix = '<img src="' . $this->imageUrl . 'tree7.gif">';
                } else {
                    $pix = '<img src="' . $this->imageUrl . 'tree2.gif">';
                }
                $folder = '<img alt="" src="' . $this->imageUrl . 'admin_folder02.gif" border="0">';

            }

            if ($numRow == true) {
                $lineBG = 'background="' . $this->imageUrl . 'tree5.gif"';
            } else {
                $lineBG = '';
            }
            $strlimit = 30 - $level * 2;


            if (isset($_GET['folder']) && isset($path) && $_GET['folder'] == $path) {
                $bgcolor = "#99ccff";
            } else {
                $bgcolor = "";
            }
            $nv = explode('&', $this->extraLink);
            $newLink = [];
            foreach ($nv as $linkline) {
                if (!$linkline) {
                    continue;
                }
                list($nk, $nv2) = explode('=', $linkline);
                if ($nk == false || $nk == 'selectCat' || $nk == 'productcat_id') {
                    continue;
                }
                $newLink[$nk] = $nv2;
            }
            foreach ($newLink as $nk => $nv) {
                $nl[] = "$nk=$nv";
            }
            $show_field = '';
            foreach ($curCfg['fields'] as $field) {
                if ($field['field_type'] == 'onoff') {
                    $show_field = $row[$field['field_index']] ? get_systext('list_onoff_on') : get_systext('list_onoff_off');
                    $show_field = '<small>(<a href="javascript:void(0);" onclick="set_status('.$row[$curCfg['table_primarykey']].');">' . $show_field . '</a>)</small>';
                }
            }

            $btn_ord = $btn_cre = $btn_mod = $btn_det = $btn_del = '';

            if (isset($curCfg['oi']['allow_create']) && $curCfg['oi']['allow_create']) {
                $btn_cre = '<a href="' . get_link('create') . '&default_field=' . $this->indexParentName . '&default_value=' . $row[$curCfg['table_primarykey']] . '"><span class="badge bg-green">' . get_systext('list_create_sub') . ' ' . htmlspecialchars($curCfg['title']) . '</span></a>';
            }
            if ($curCfg['oi']['allow_modify']) {
                $btn_mod = '<a href="' . get_link('modify') . '&' . $curCfg['table_primarykey'] . '=' . $row[$curCfg['table_primarykey']] . '"><span class="badge bg-green">' . get_systext('list_modify') . '</span></a>';
            }
            if (isset($curCfg['oi']['allow_details']) && $curCfg['oi']['allow_details']) {
                $btn_det = '<a href="content.php?' . get_link() . '&stage=details&' . $curCfg['table_primarykey'] . '=' . $row[$curCfg['table_primarykey']] . '"><span class="badge bg-yellow">' . get_systext('list_details') . '</span></a>';
            }
            if ($curCfg['oi']['allow_delete'] && ($curCfg['table_name'] != 'faby_admin' || $row[$curCfg['table_primarykey']] != 1)) {
                $btn_del = '<a href="?' . get_link() . '&stage=delete&' . $curCfg['table_primarykey'] . '=' . $row[$curCfg['table_primarykey']] . '" onclick="return confirm(\'' . get_systext('list_delete') . ' ' . $row['name'] . ' ' . htmlspecialchars($curCfg['title']) . '?\');"><span class="badge bg-red">' . get_systext('list_delete') . '</span></a>';
            }
            if ($curCfg['table_order_type'] == 'order') {
                $btn_ord = '';
                if ($key != 0) {
                    $btn_ord = '<a href="?' . get_link() . '&stage=order_up&' . $curCfg['table_primarykey'] . '=' . $row[$curCfg['table_primarykey']] . '"><span class="badge bg-yellow"><i class="fa fa-fw fa-caret-square-o-up"></i></span></a>';
                }
                if ($key != count($this->getListByParent($allData, $parentID)) - 1 && count($this->getListByParent($allData, $parentID)) != 1) {
                    $btn_ord .= '<a href="?' . get_link() . '&stage=order_down&' . $curCfg['table_primarykey'] . '=' . $row[$curCfg['table_primarykey']] . '"><span class="badge bg-yellow"><i class="fa fa-fw fa-caret-square-o-down"></i></span></a>';
                }
            }
            $mainHtml .= "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
			<tr onmouseover=\"style.backgroundColor = '#f39c12';\"  onmouseout=\"style.backgroundColor = '';\"  >
			<td width=\"19\" height=\"24\">$pix</td>
			<td width=\"20\" align=\"center\"><a href=\"tree.php?parent_folder=$catid&selectCat=$catid$this->extraLink\">$folder</a></td>
			<td width=\"" . ($this->tdWidth - ($level * 19) - 20 - 19) . "\" align=\"left\" class=\"normal\">
			" . $row['name'] . "&nbsp;$show_field &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			$btn_ord $btn_cre $btn_mod $btn_det $btn_del
			</td>
			</tr>
			$subhtml
			</table>";
        }
        $level--;
        $eah = 25;
        return $mainHtml;
    }


}


?>