<?php

date_default_timezone_set('Asia/Hong_Kong');

$imageTable = 'images';
$videoTable = 'videos';
$audioTable = 'audios';
$fileTable = 'files';

$imagefolder = 'project/' . $project_folder . '/upload/image';
$imagePrimarykey = 'image_id';

$videofolder = 'project/' . $project_folder . '/upload/video';
$videoPrimarykey = 'video_id';

$audiofolder = 'project/' . $project_folder . '/upload/audio';
$audioPrimarykey = 'audio_id';

$filefolder = 'project/' . $project_folder . '/upload/file';
$filePrimarykey = 'file_id';

if (isset($imagefolder)) {
    $admin_imagefolder = '../'.$imagefolder;
}
if (isset($filefolder)) {
    $admin_filefolder = '../'.$filefolder;
}
if (isset($videofolder)) {
    $admin_videofolder = '../'.$videofolder;
}
if (isset($audiofolder)) {
    $admin_audiofolder = '../'.$audiofolder;
}
$project_data_type = '../project/'.$project_folder.'/admin/data_type/';
if(file_exists($project_data_type)){
    if ($handle = opendir($project_data_type)) {
        while (false !== ($entry = readdir($handle))) {
            if(substr($entry, 0, 1) == '.' || $entry == 'data_type.php'){continue;}
            list($classname, $ext) = explode('.', $entry);
            $classname = str_replace('dt_', '', $classname);
            $admin_selectconfig['admin_zconfigtable_fields.field_type'][$classname] = $classname;
        }
        closedir($handle);
    }
}
if ($handle = opendir('data_type')) {
    while (false !== ($entry = readdir($handle))) {
        if(substr($entry, 0, 1) == '.' || $entry == 'data_type.php'){continue;}
        if(file_exists($project_data_type.$entry)){continue;}
        if($_GET['t'] == 'admin_zconfigtable_fields_form' && in_array(str_replace('.php', '', $entry), array('dt_audio', 'dt_tag', 'dt_tab', 'dt_related', 'dt_video', 'dt_password', 'dt_qrcode', 'dt_tabs', 'dt_related_checkbox', 'dt_related_multi', 'dt_google_places', 'dt_html', 'dt_single_password', 'dt_int', 'dt_link', 'dt_radio', 'dt_serialize', 'dt_sublist'))){continue;}
        list($classname, $ext) = explode('.', $entry);
        $classname = str_replace('dt_', '', $classname);
        $admin_selectconfig['admin_zconfigtable_fields.field_type'][$classname] = $classname;
    }
    closedir($handle);
    ksort($admin_selectconfig['admin_zconfigtable_fields.field_type']);
}

$selectconfig = array_merge($admin_selectconfig, isset($selectconfig)?$selectconfig:array());


?>