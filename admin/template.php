<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo get_systext(is_array($project_name)?$project_name[$selected_lang]:$project_name); ?> <?php echo get_systext('login_admin'); ?></title>
    <!-- Bootstrap -->
    <link href="vendors/bootstrap/dist/css/bootstrap.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="vendors/select2/dist/css/select2.min.css" rel="stylesheet">
    <!-- Custom Theme Style -->
    <link href="build/css/custom.css?v=1" rel="stylesheet">
    <link href="vendors/tinymce/skins/css/style.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="vendors/jquery/dist/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="vendors/datetimepicker/jquery.datetimepicker.css"/>
    <link href="vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <!-- Custom Theme Style -->
    <link href="css/cms.css" rel="stylesheet">
    <?php 
        if(file_exists('../project/'.$project_folder.'/admin/css')){
            foreach (scandir('../project/'.$project_folder.'/admin/css') as $file) {
                if( substr($file, strlen($file)-4) == '.css'){
                    ?><link href="<?php echo '../project/'.$project_folder.'/admin/css/'.$file; ?>" rel="stylesheet"><?php
                }
            }
        }
    ?>
</head>
<body class="nav-md">

<div class="container body">
    <div class="main_container">
        <div class="col-md-3 left_col">
            <div class="left_col scroll-view">
                <div class="navbar nav_title" style="border: 0;">
                    <a href="index.php"
                       class="site_title"><span><?php echo get_systext(is_array($project_name)?$project_name[$selected_lang]:$project_name); ?> <?php echo get_systext('login_admin'); ?></span></a>
                </div>
                <div class="clearfix"></div>
                <!-- sidebar menu -->
                <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
                    <div class="menu_section">
                        <ul class="nav side-menu">
                            <?php foreach ($cmsconfig as $key => $row) { ?>
                                <?php if(!$row['table_index'] && (!isset($row['submenu']) || !count($row['submenu']))){continue;} ?>
                                <?php if (isset($row['submenu'])) { $isActive = false; ?>
                                    <?php foreach ($row['submenu'] as $skey => $subrow) if(isset($m) && "$m" == $skey){ $isActive = true; } ?>
                                    <li class="<?php if ($isActive) { echo ' active'; } ?>">
                                        <a><i class="fa fa-circle-o<?php if ($isActive) { echo ' fa-circle'; } ?>"></i><?php echo htmlspecialchars(get_systext($row['menu_title'])); ?>
                                            <span class="fa fa-chevron-down"></span></a>
                                        <ul class="nav child_menu <?php if ($isActive) { echo ' menu-open active'; } ?>">
                                            <?php foreach ($row['submenu'] as $skey => $subrow) { ?>
                                                <li class="<?php if ("$m" == $skey) { echo 'active'; } ?>">
                                                    <a href="<?php echo get_link('list', $tbCfgs[$subrow['table_index']], $subrow['id']); ?>">
                                                        <?php echo htmlspecialchars(get_systext($subrow['menu_title']?$subrow['menu_title']:$tbCfgs[$subrow['table_index']]['table_title'])); ?></a>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </li>
                                <?php } else { ?>
                                    <li class="<?php if (isset($m) && $m == $row['id']) { echo 'active'; } ?>">
                                        <a href="<?php echo get_link('list', $tbCfgs[$row['table_index']], $row['id']); ?>">
                                            <i class="fa fa-circle-o<?php if (isset($m) && $m == $row['id']) { echo ' fa-circle'; } ?>"></i> 
                                                <span><?php echo htmlspecialchars(get_systext($row['menu_title']?$row['menu_title']:$tbCfgs[$row['table_index']]['table_title'])); ?></span>
                                        </a>
                                    </li>
                                <?php } ?>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
                <!-- /sidebar menu -->
            </div>
        </div>
        <!-- top navigation -->
        <div class="top_nav">
            <div class="nav_menu">
                <nav>
                    <div class="nav toggle">
                        <a id="menu_toggle"><i class="fa fa-bars"></i></a>
                    </div>
                    <div class="top_logo pull-left" style="">
                        <?php 
                            $logo = '../project/' . $project_folder . '/images/logo.png';
                            if (file_exists($logo)) {
                                $img = '<img src="'.$logo.'" height="45" style="margin:7px;">';
                                if($project_website){
                                    echo '<a href="'.$project_website.'">'.$img.'</a>';
                                } else {
                                    echo $img;
                                }
                            }
                        ?>
                    </div>
                    <ul class="nav navbar-nav navbar-right">
                        <li class="">
                            <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown"
                               aria-expanded="false">
                                <?php echo get_systext('change_password'); ?>, <?php echo get_systext('login_logout'); ?>
                                <span class=" fa fa-angle-down"></span>
                            </a>
                            <ul class="dropdown-menu dropdown-usermenu pull-right">
                                <li><a href="change_password.php"><?php echo get_systext('change_password'); ?></a></li>
                                <li><a href="?logout=1"><i class="fa fa-sign-out pull-right"></i> <?php echo get_systext('login_logout'); ?></a></li>
                            </ul>
                        </li>
                        <?php if (isset($support_multi_lang)) { ?>
                            <?php if ($selected_lang != 'en' && isset($support_lang['en'])) { ?>
                                <li role="presentation" class="dropdown"><a href="index.php?lang=en" class="to_register">EN</a></li>
                            <?php } ?>
                            <?php if ($selected_lang != 'tc' && isset($support_lang['tc'])) { ?>
                                <li role="presentation" class="dropdown"><a href="index.php?lang=tc" class="to_register">繁體</a></li>
                            <?php } ?>
                            <?php if ($selected_lang != 'sc' && isset($support_lang['sc'])) { ?>
                                <li role="presentation" class="dropdown"><a href="index.php?lang=sc" class="to_register">简体</a></li>
                            <?php } ?>
                        <?php } ?>
                    </ul>
                </nav>
            </div>
        </div>
        <!-- /top navigation -->
        <!-- page content -->
        <div class="right_col">
            <?php echo $cms_content; ?>
        </div>
        <!-- /page content -->
    </div>
</div>
<!-- Bootstrap -->
<script src="vendors/bootstrap/dist/js/bootstrap.min.js"></script>
<!-- iCheck -->
<script src="vendors/iCheck/icheck.min.js"></script>
<!-- bootstrap-daterangepicker -->
<script src="vendors/moment/moment.min.js"></script>
<script src="vendors/datepicker/daterangepicker.js"></script>
<!-- Select2 -->
<script src="vendors/select2/dist/js/select2.full.min.js"></script>
<!-- Custom Theme Scripts -->
<script src="build/js/custom.js"></script>
<script src="vendors/datetimepicker/jquery.datetimepicker.full.js"></script>

<link rel="stylesheet" href="vendors/fancyBox3/jquery.fancybox.min.css" />
<script src="vendors/fancyBox3/jquery.fancybox.min.js"></script>


<script type="text/javascript" src="vendors/tinymce/tinymce.min.js"></script>

<script type="text/javascript" src="vendors/ColorPicker/jquery.wheelcolorpicker.js"></script>
<link type="text/css" rel="stylesheet" href="vendors/ColorPicker/css/wheelcolorpicker.css" />
<!-- jquery ui -->
<link href="vendors/jquery-ui/jquery-ui.css" rel="stylesheet">
<script src="vendors/jquery-ui/jquery-ui.js"></script>

<script>
    $(document).ready(function () {

        $('input, textarea, select').change(function(){
            $('#update_btn').addClass('bg-green');
        });
        $('input[type=checkbox]:not(.table_records,#check-all)').on('ifClicked', function() {
            $('#update_btn').addClass('bg-green');
        });
        $('.table_records,#check-all').on('ifClicked', function() {
            // alert($('[name=del_records]').val())

            setTimeout(function(){
                if($('[name=del_records]').val() != ''){
                    $('#export_btn').addClass('bg-green');
                    $('#delete_selected_btn').addClass('bg-green');
                } else {
                    $('#export_btn').removeClass('bg-green');
                    $('#delete_selected_btn').removeClass('bg-green');
                }
            }, 30);
                
        });
        $('.fancybox').fancybox({
            afterShow: function() {
              $('.fancybox-inner').find('video').trigger('play');
            }
        });
        $(".select2_single").select2({
            placeholder: "Select a state",
            allowClear: true
        });
        $(".select2_group").select2({});
        $(".select2_multiple").select2({
            maximumSelectionLength: 4,
            placeholder: "With Max Selection limit 4",
            allowClear: true
        });
        if ($('.datepicker').length > 0) {
            $('.datepicker').datetimepicker({timepicker: false, format: datepicker_format, scrollMonth:false});
        }
        
        $('.datetimepicker').datetimepicker({step: 30, scrollMonth:false});

        <?php if (isset($message_popup)) { ?>
            alert('<?php echo $message_popup; ?>');
        <?php }; ?>
        <?php if (isset($editors)) { ?>
            tinymce.init({
              selector: '.editor',
              height: 450,
              theme: 'silver',
              cleanup : false,
              convert_urls: false,
              images_upload_url: 'tinymce_upload.php',
              convert_fonts_to_spans : false,
              formats: {
                 bold: {inline : 'b'},
                 underline: {inline : 'u'},
                 forecolor: {inline : 'font', attributes : {color : '%value'}},
                 strikethrough: {inline : 's'},
              },
              plugins: [
                'advlist autolink lists link charmap print preview hr anchor pagebreak',
                'searchreplace wordcount visualblocks visualchars code fullscreen',
                'insertdatetime media image nonbreaking save table contextmenu directionality',
                'emoticons template paste textcolor colorpicker textpattern imagetools'
              ],
              toolbar1: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
              toolbar2: 'fontselect | fontsizeselect | forecolor backcolor emoticons',
              image_advtab: true,
              templates: [
                { title: 'Image with caption', content: '<div style="background-color:#000;"><img src="../project/mpa/html/assets/images/blank.png" width="100%"/><div style="padding: 10px; color:#fff;">CAPTION HERE</div></div>' },
              ],
              block_formats: 'Block quote=blockquote;',
              <?php if(file_exists('../project/'.$project_folder.'/admin/css/tinymce.css')){ ?>
                content_css: [
                    'vendors/tinymce/skins/css/style.css',
                    '../project/<?php echo $project_folder; ?>/admin/css/tinymce.css'
                ],
              <?php } else if(file_exists('vendors/tinymce/skins/css/style.css')) { ?>
                content_css: [
                    'vendors/tinymce/skins/css/style.css',
                ],
              <?php } ?>
              
              font_formats: '細明體=細明體;黑體=黑體,arial;Arial=arial,helvetica,sans-serif;Courier New=courier new,courier,monospace;Roboto Slab=Roboto Slab;Roboto=Roboto;Open Sans=Open Sans',
              fontsize_formats: '8px 10px 12px 14px 16px 18px 24px 36px 48px 52px 64px 72px 96px',
              automatic_uploads: true,  // change this value according to your HTML
              entity_encoding : "raw"
             });
        <?php }; ?>
    });

</script>
<!-- /Select2 -->
<div class="lds_overlay">
    <div class="lds-spinner">
        <div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
    </div>
    <script type="text/javascript">
        $('.lds_overlay').click(function(e){
            e.preventDefault();
        });
    </script>
</div>
</body>
</html>
