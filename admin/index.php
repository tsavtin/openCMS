<?php

include_once 'includes/config.php';

ob_start();

if(isset($_GET['file'])){
	include_once 'project/'.$project_folder.'/admin/'.$_GET['file'].'.php';
}
if($admin_index || in_array($_SESSION['CertManusersession_admin_group_id'], [9])){
	if(in_array($_SESSION['CertManusersession_admin_group_id'], [9])){
	    $admin_index = './project_file.php?&file=standards_list&t=standards&m=182&cc=1&ca=1&tt=list&sl_id=';
	}
	header('location: '.$admin_index);
	exit;
}

if (isset($cms_content)) {
    $cms_content .= ob_get_contents();
} else {
    $cms_content = ob_get_contents();
}
ob_end_clean();

include 'template.php';

?>