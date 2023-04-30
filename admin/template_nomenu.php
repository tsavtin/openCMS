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
    <link href="css/cms_nomenu.css" rel="stylesheet">
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
        <?php echo $cms_content; ?>
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
                $('.datepicker').datetimepicker({step: 30, timepicker: false, format: datepicker_format, scrollMonth:false});
            }
            
            $('.datetimepicker').datetimepicker({step: 30, scrollMonth:false});
            <?php if (isset($message_popup)) { ?>
                alert('<?php echo $message_popup; ?>');
            <?php }; ?>
            <?php if (isset($editors)) { ?>
                tinymce.init({
                  selector: '.editor',
                  height: 600,
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
                  <?php } else { ?>
                    content_css: [
                        'vendors/tinymce/skins/css/style.css',
                    ],
                  <?php } ?>
                  
                  font_formats: '細明體=細明體;黑體=黑體,arial;Arial=arial,helvetica,sans-serif;Courier New=courier new,courier,monospace;Roboto Slab=Roboto Slab;Roboto=Roboto;Open Sans=Open Sans',
                  fontsize_formats: '8px 10px 12px 14px 16px 18px 24px 36px 48px 52px 64px 72px 96px',
                  automatic_uploads: false,  // change this value according to your HTML
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
