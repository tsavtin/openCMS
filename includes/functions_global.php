<?php

function get_folder($folder, $id, $datetime){
    if(!$id){return;}
    $idfolder = str_pad($id, 5, "0", STD_PAD_LEFT);
    $year = date('Y', strtotime($datetime));
    $month = date('m', strtotime($datetime));
    if (!is_writable($folder)){
        echo 'Folder not writable:'.$folder;
        exit;
    }
    if(!file_exists("$folder/$year")){
        mkdir("$folder/$year");
        @chmod("$folder/$year", 0777);
    }
    if(!file_exists("$folder/$year/$month")){
        mkdir("$folder/$year/$month");
        @chmod("$folder/$year/$month", 0777);
    }
    if(!file_exists("$folder/$year/$month")){
        mkdir("$folder/$year/$month");
        @chmod("$folder/$year/$month", 0777);
    }
    if(!file_exists("$folder/$year/$month/$idfolder")){
        mkdir("$folder/$year/$month/$idfolder");
        @chmod("$folder/$year/$month/$idfolder", 0777);
    }
    return "$folder/$year/$month/$idfolder";
}

?>