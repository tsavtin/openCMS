                <?php 

                if($_GET['sl_id']){
                    $sl_info = $mysql->getData('admin_zconfigtable_fields', ['admin_zconfigtable_fields_id' => $_GET['sl_id']]);
                    $parentTN = $tbIndexByID[$sl_info['admin_zconfigtable_id']];
                } else {
                    $parentTN = $curCfg['parent_table'];
                }
                if ($parentTN && isset($tbCfgs[$parentTN]['oi']['sublist_shortcut'])) { ?>
                <div class="x_title">
                    <h2>
                            <?php
                            if($_GET['sl_id']){
                                $sl_info = $mysql->getData('admin_zconfigtable_fields', ['admin_zconfigtable_fields_id' => $_GET['sl_id']]);
                                $parentTN = $tbIndexByID[$sl_info['admin_zconfigtable_id']];
                            } else {
                                $parentTN = $curCfg['parent_table'];
                            }
                            echo get_systext($tbCfgs[$parentTN]['title']).':';
                            $parentInfo = $mysql->getData($tbCfgs[$parentTN]['table_name'], array($tbCfgs[$parentTN]['table_primarykey'] => $_GET[$curCfg['parent_related_key']]));
                            if($_GET['sl_id']){
                                foreach ($tbCfgs[$parentTN]['fields'] as $field) {
                                    if($_GET['sl_id'] == $field['admin_zconfigtable_fields_id']){
                                        $fieldInfo = $field;
                                        break;
                                    }
                                }
                                $key = $fieldInfo['related_my_key']?$fieldInfo['related_my_key']:$fieldInfo['related_key'];
                                $primarykey = $key . '=' . $_GET[$fieldInfo['related_key']];
                            } else if(false) {
                                
                                $primarykey = $tbCfgs[$parentTN]['table_primarykey'] . '=' . $_GET[$tbCfgs[$parentTN]['table_primarykey']];
                            }
                            
                            $mtOpts = tableOpt($tbCfgs[$parentTN]['option']);

                            if (is_string($curCfg['parent_show_title']) && !preg_match('/,/', $curCfg['parent_show_title'])) {
                                echo isset($mtOpts['support_language'])&&$mtOpts['support_language']? get_systext($parentInfo[$curCfg['parent_show_title'] . '_' . $selected_lang]) : get_systext($parentInfo[$curCfg['parent_show_title']]);
                            } else {
                                if(!is_array($curCfg['parent_show_title'])){
                                    $curCfg['parent_show_title'] = explode(',', $curCfg['parent_show_title']);
                                }
                                $titles = array();
                                foreach ($curCfg['parent_show_title'] as $title) {
                                    if(isset($tbCfgs[$parentTN]['fbi'][$title]['field_type']) && $tbCfgs[$parentTN]['fbi'][$title]['field_type'] == 'text'){
                                        if($parentInfo[$title]){
                                            $titles[] = $parentInfo[$title];
                                        }
                                    } else if(isset($tbCfgs[$parentTN]['fbi'][$title]['field_type']) && $tbCfgs[$parentTN]['fbi'][$title]['field_type'] == 'related'){
                                        if(getRelateValue($tbCfgs[$parentTN]['fbi'][$title], $parentInfo[$title])){
                                            $titles[] = getRelateValue($tbCfgs[$parentTN]['fbi'][$title], $parentInfo[$title]);
                                        }
                                    }
                                }
                                echo join(',', $titles);
                            }
                            ?><br>
                            <a href="<?php echo isset($tbCfgs[$parentTN]['modify_url']) && $tbCfgs[$parentTN]['modify_url']?'project_file.php?file='.str_replace('.php', '', $tbCfgs[$parentTN]['modify_url']):'content.php?'; ?><?php echo "&m=$m&t=".$tbCfgs[$parentTN]['table_index']; ?>&stage=modify&<?php echo $primarykey ?>"><span class="badge bg-green"><?php echo get_systext($tbCfgs[$parentTN]['title']); ?>&nbsp;<?php echo get_systext('list_details'); ?></span></a>
                            <?php foreach ($tbCfgs[$parentTN]['fields'] as $field) { ?>
                                <?php $fieldOpts = fieldOpt($field['field_options']); ?>
                                <?php $fieldClass = array(); ?>
                                <?php if(isset($fieldOpts['show_desktop'])){$fieldClass[] = 'show_desktop';} ?>
                                <?php if ($field['field_type'] == 'sublist') { ?>
                                    <?php $data_type->{'dt_'.$field['field_type']}->config($field); ?>
                                    <?php echo $data_type->{'dt_'.$field['field_type']}->list_value(array($tbCfgs[$parentTN]['table_primarykey'] => $_GET[$tbCfgs[$parentTN]['table_primarykey']]), true); ?>
                                <?php } ?>
                            <?php }  ?>

                            <a href="<?php echo get_link('backlist'); ?>"><span
                                    class="badge bg-green"><?php echo get_systext('btn_back'); ?><!--  <?php echo get_systext($tbCfgs[$parentTN]['title']); ?> <?php echo get_systext('btn_list'); ?> --></span></a>
                        &nbsp;&nbsp;
                    </h2>
                    <div class="clearfix"></div>
                </div>
                <?php } ?>