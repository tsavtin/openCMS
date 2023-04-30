<?php
if (isset($_GET['lang']) && $_GET['lang']) {
    $_SESSION['lang'] = $_GET['lang'];
}

$selected_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : "";

if ($selected_lang == false) {
    session_regenerate_id('lang');
    $selected_lang = $_SESSION['lang'] = $default_lang?$default_lang:'en';
}
?>