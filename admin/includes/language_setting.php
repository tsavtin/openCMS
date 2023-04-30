<?php

if (isset($_GET['lang']) && $_GET['lang']) {
    $_SESSION['lang_admin'] = $_GET['lang'];
}

$selected_lang = isset($_SESSION['lang_admin']) ? $_SESSION['lang_admin'] : "";

if ($selected_lang == false) {
    session_regenerate_id('lang');
    $selected_lang = $_SESSION['lang_admin'] = isset($default_lang)?$default_lang:'en';
}
?>