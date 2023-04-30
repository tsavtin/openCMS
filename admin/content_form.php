<?php $editors = []; ?>
<div class="clearfix"></div>
<div class="row">
    <div class="<?php echo $curCfg['contentwidth_class']?$curCfg['contentwidth_class']:'col-md-12'; ?> col-xs-12">
        <div class="x_panel">
            <!--  list header start -->
            <div class="breadcrumb">
            <?php foreach ($_SESSION['bp'] as $bp) { ?>
                /<?php $json = json_decode($bp, true); echo $tbCfgs[$json['t']]['table_title']; ?>
            <?php } ?>
            </div>
            <!--  list header end -->
            <?php if($_GET['tt'] != 'duplicate'){ ?>
            <div class="x_title">
                <h2>
                    <?php if (isset($_GET['stage']) && $_GET['stage'] == 'create') { echo get_systext('msg_cre'); } ?>
                    <?php echo get_systext($curCfg['menu_title']); ?>
                    <?php if (isset($_GET[$curCfg['table_primarykey']]) && isset($curCfg['oi']['sublist_shortcut'])) { ?>
                        <br>
                        <a href="javascript:void(0);"><span class="badge bg-red"><?php echo get_systext($curCfg['title']); ?>&nbsp;<?php echo get_systext('list_details'); ?></span></a>
                        <?php foreach ($tbCfgs[$curCfg['table_index']]['fields'] as $field) { ?>
                            <?php $fieldOpts = fieldOpt($field['field_options']); ?>
                            <?php $fieldClass = []; ?>
                            <?php if(isset($fieldOpts['show_desktop'])){$fieldClass[] = 'show_desktop';} ?>
                            <?php if ($field['field_type'] == 'sublist') { ?>
                                <?php $data_type->{'dt_'.$field['field_type']}->config($field); ?>
                                <?php echo $data_type->{'dt_'.$field['field_type']}->list_value(array($curCfg['table_primarykey'] => $_GET[$curCfg['table_primarykey']]), true); ?>
                            <?php } ?>
                        <?php }  ?>
                        <a href="<?php echo get_link('backlist'); ?>"><span class="badge bg-green"><?php echo get_systext('btn_back'); ?><!--  <?php echo get_systext($curCfg['title']); ?> <?php echo get_systext('btn_list'); ?> --></span></a>
                    <?php } ?>
                </h2>
                <div class="clearfix"></div>
            </div>
            <?php } ?>
            
            <div class="x_content">
                <form class="form-horizontal form-label-left form_<?php echo $_GET['t']; ?>" action="?<?php echo get_link('form'); ?>" method="POST" enctype="multipart/form-data" id="enter_form" autocomplete="off">
                    <input type="hidden" name="form_stage" value="form_submit">
                    <?php if (isset($form_message) && count($form_message)) { ?>
                        <div class="form-group has-success">
                            <label class="control-label" for="inputSuccess"><i class="fa fa-check"></i> <?php echo $form_message; ?></label>
                        </div>
                    <?php } ?>
                    <?php
                    $cols = 0;
                    foreach ($curCfg['fields'] as $field) { ?>
                        <?php if($field['field_type'] == 'sublist'){continue;}?>
                        <?php $fieldOpts = fieldOpt($field['field_options']); ?>
                        <?php if ((!isset($_GET['stage']) || !isset($fieldOpts[$_GET['stage']])) && (!isset($curCfg['table_type']) || $curCfg['table_type'] != 'setting') && !(isset($fieldOpts['modify_show']) && $_GET['stage'] == 'modify')) {
                            continue;
                        } ?>
                        <?php if($field['field_type'] == 'hidden' || $field['field_type'] == 'html'){ $data_type->{'dt_'.$field['field_type']}->config($field); echo $data_type->{'dt_'.$field['field_type']}->form_html($_POST, isset($formerror)?$formerror:[]); continue;} ?>
                        <div class="form-group <?php echo $field['form_width']; ?> <?php if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) { ?> has-error<?php } ?> <?php echo $field['field_index'].' '.$curCfg['table_index']; ?>">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12"><?php if(isset($fieldOpts['require'])){ ?><span style="color: red;">*</span>&nbsp;<?php } ?><?php echo get_systext($field['field_name']); ?>
                                <?php !$data_type->{'dt_'.$field['field_type']}?die('data_type '.$field['field_type'].' not exist'):''; ?>
                                <?php $fieldCfg = $data_type->{'dt_'.$field['field_type']}->config($field); ?>
                                <?php if ($field['field_tips']){ ?>
                                    <i class="fa fa-info-circle tips_<?php echo $field['field_index']; ?>" onmouseout="layer.close(differentindex);" onmouseover="tips('<?php echo preg_replace('/\r|\n/is', '', addslashes(str_replace('"', "'", $field['field_tips']))); ?>', '.tips_<?php echo $field['field_index']; ?>');"></i>
                                <?php } else if ($field['field_type'] == 'image') { ?>
                                    <?php if($field['field_remark']){ ?>
                                        <br><small><?php echo $field['field_remark']; ?></small>
                                    <?php } else { ?>
                                        <br><small>(<?php echo $fieldCfg['width']?$fieldCfg['width']:$imageConfig[$curCfg['index'] . '.' . $field['field_index']][1]; ?>
                                        x<?php echo $fieldCfg['height']?$fieldCfg['height']:$imageConfig[$curCfg['index'] . '.' . $field['field_index']][2]; ?><?php if($fieldCfg['mb_limit'] || (isset($imageConfig[$curCfg['index'] . '.' . $field['field_index']][5]) && $imageConfig[$curCfg['index'] . '.' . $field['field_index']][5])){ ?> MAX <?php echo $fieldCfg['mb_limit']?$fieldCfg['mb_limit']:$imageConfig[$curCfg['index'] . '.' . $field['field_index']][5]; ?>MB<?php } ?>)
                                        </small>
                                    <?php } ?>
                                <?php } else if ($field['field_type'] == 'video') { ?>
                                    <br>(<?php echo str_replace('.', '', $videoConfig[$curCfg['index'] . '.' . $field['field_index']][0]); ?><?php if(isset($videoConfig[$curCfg['index'] . '.' . $field['field_index']][2]) && $videoConfig[$curCfg['index'] . '.' . $field['field_index']][2]){ ?> MAX <?php echo $videoConfig[$curCfg['index'] . '.' . $field['field_index']][2]; ?>MB<?php } ?>)
                                <?php } else if ($field['field_type'] == 'editor' && $field['field_remark']) { ?>
                                        <br><small><?php echo $field['field_remark']; ?></small>
                                <?php } ?>

                                <?php echo ($field['field_name']?' : ':''); ?>
                            </label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <?php if ((isset($_GET['stage']) && $_GET['stage'] == 'details') || (isset($fieldOpts['modify_show']) && $_GET['stage'] == 'modify')){ ?>
                                    <div style="padding-top:9px;"></div>
                                    <?php echo $data_type->{'dt_'.$field['field_type']}->list_value($_POST); ?>
                                <?php } else if ((isset($_GET['stage']) && ($_GET['stage'] == 'create' || $_GET['stage'] == 'modify' || $_GET['stage'] == 'duplicate')) || $curCfg['table_type'] == 'setting') { ?>
                                    <?php echo $data_type->{'dt_'.$field['field_type']}->form_html($_POST, isset($formerror)?$formerror:[]); ?>
                                <?php } ?>
                                <!-- create modify  -->
                                <?php if (isset($formerror) && isset($formerror[$field['field_index']]) && $formerror[$field['field_index']]) { ?>
                                    <ul class="parsley-errors-list filled" id="parsley-id-5">
                                        <li class="parsley-required"><?php echo $formerror[$field['field_index']]; ?></li>
                                    </ul>
                                <?php } ?>
                            </div>
                        </div>
                        <?php if ($fieldOpts['after_newline']) { ?><div style="clear: both;"></div><?php } ?>
                    <?php } ?>

                    <?php if ((isset($_GET['stage']) && ($_GET['stage'] == 'create' || $_GET['stage'] == 'modify' || $_GET['stage'] == 'details' || $_GET['stage'] == 'duplicate')) || $curCfg['table_type'] == 'setting') { ?>
                        <div class="clear"></div>
                        <div class="ln_solid"></div>
                        <div class="form-group">
                            <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                <?php if ((!isset($curCfg['table_type']) || $curCfg['table_type'] != 'setting')  && $_GET['tpl'] != 'nomenu') { ?>
                                    <button type="button" class="btn btn-primary"
                                            onclick="window.location='<?php echo get_link('backlist'); ?>';">
                                        <?php echo get_systext('con_back'); ?>
                                    </button>
                                <?php } ?>
                                <?php if(!isset($_GET['stage']) || $_GET['stage'] != 'details'){ ?>
                                <button type="button" onclick="$('#enter_form').submit();" class="btn btn-success">
                                    <?php 
                                    if(isset($_GET['stage']) && $_GET['stage'] == 'create'){
                                        echo get_systext('list_create');
                                    } else if(isset($_GET['stage']) && $_GET['stage'] == 'modify'){
                                        echo get_systext('list_modify');
                                    } else if(isset($_GET['stage']) && $_GET['stage'] == 'duplicate'){
                                        echo get_systext('list_duplicate');
                                    } else {
                                        echo get_systext('con_submit');
                                    } ?></button>
                                <?php } ?>
                                
                                <?php if(isset($curCfg['cnt_btns']) && $curCfg['cnt_btns']){ ?>
                                    <?php foreach ($curCfg['cnt_btns'] as $btn) { ?>
                                        <button type="submit" class="btn btn-primary" name="cnt_btns_btn" value="<?php echo $btn; ?>"><?php echo get_systext($btn); ?></button>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
$(document).keypress(function(event){
    if (event.which == '13') {
        console.log($(':focus'));
        $(':focus').next().focus();
    }
});

$(document).ready(function(event){
    setTimeout(function() {
        $('.has-success').animate({ opacity: 0 });
    }, 3000);
});
</script>