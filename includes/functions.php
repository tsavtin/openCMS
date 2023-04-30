<?php


function arrayIsAssoc(array $arr){
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

// This function returns the time elapsed string in different languages
function time_elapsed_string($datetime, $full = false) {
    global $nowTime, $selected_lang;
    // Create DateTime objects for current and given time
    $now = new DateTime($nowTime);
    $ago = new DateTime($datetime);
    // Calculate the difference between the two times
    $diff = $now->diff($ago);

    // Calculate the number of weeks
    $diff->w = floor($diff->d / 7);
    // Subtract the number of weeks from the total days
    $diff->d -= $diff->w * 7;

    // Set strings based on language
    if($selected_lang == 'en'){
        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
    } else if($selected_lang == 'tc'){
        $string = array(
            'y' => '年',
            'm' => '個月',
            'w' => '個星期',
            'd' => '日',
            'h' => '小時',
            'i' => '分鐘',
            's' => '秒',
        );
    } else if($selected_lang == 'sc'){
        $string = array(
            'y' => '年',
            'm' => '个月',
            'w' => '个星期',
            'd' => '日',
            'h' => '小时',
            'i' => '分钟',
            's' => '秒',
        );
    }
    
    // Loop through each string and add it to the output if it is greater than 0
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 && $selected_lang == 'en' ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    // If full is false, only return the first value
    if (!$full) $string = array_slice($string, 0, 1);
    // Return the output with the appropriate language
    if($selected_lang == 'en'){
        return $string ? implode(', ', $string) . ' ago' : 'Today';
    } else{
        return $string ? implode(', ', $string) . '前' : '今日';
    }
}

function create_image($_file, $resize_type, $width = 0, $height = 0, $update_time = null, $table = null){
    global $imageTable, $imagePrimarykey, $mysql, $imagefolder, $admin_imagefolder, $nowTime, $login;

    image_fix_orientation($_file['tmp_name'], getExt($_file['name']));
    $newName = md5($_file['name']);
    $newExt = pathinfo($_file['name'], PATHINFO_EXTENSION);
    $newImageData = array(
        'table' => $table,
        'file_org' => $_file['name'],
        'filename_md5' => $newName,
        'type' => $newExt,
        'status' => 1,
        'date_added' => $nowTime,
        'date_modified' => $_file['update_time']?$_file['update_time']:null
    );
    $mysql->create($imageTable, $newImageData);
    $lastId = $mysql->cid->lastInsertId();
    if($_SESSION[$login->sessionKey . 'admin_id'] && $_GET['t']){
        $folder = get_folder($admin_imagefolder, $lastId, $nowTime);
    } else {
        $folder = get_folder($imagefolder, $lastId, $nowTime);
    }

    if ($resize_type == 'crop') {
        $imgNewSize = ImageCropV3($_file['tmp_name'], "$folder/$newName.$newExt", $_file['type'], $width, $height);
    } else if ($resize_type == 'resize') {
        $imgNewSize = ImageResizeV3($_file['tmp_name'], "$folder/$newName.$newExt", $_file['type'], $width, $height);
    } else if ($resize_type == 'copy') {
        copy($_file['tmp_name'], "$folder/$newName.$newExt");
        $imgNewSize = array(0, 0);
    }
    $updateImageData = array(
        'width' => $imgNewSize[0],
        'height' => $imgNewSize[1]
    );
    $mysql->update($imageTable, array($imagePrimarykey => $lastId), $updateImageData);
    return $lastId;
}

function create_video($_file){
    global $videoTable, $videoPrimarykey, $mysql, $videofolder, $nowTime;
    $newName = md5($_file['name']);
    $newExt = pathinfo($_file['name'], PATHINFO_EXTENSION);
    $newvideoData = array(
        'filename_md5' => $newName,
        'type' => $newExt,
        'status' => 1,
        'date_added' => $nowTime
    );
    $mysql->create($videoTable, $newvideoData);
    $lastId = $mysql->cid->lastInsertId();
    $folder = get_folder($videofolder, $lastId, $nowTime);
    move_uploaded_file($_file['tmp_name'], "$folder/$newName.$newExt");
    return $lastId;
}

function create_audio($_file){
    global $audioTable, $audioPrimarykey, $mysql, $audiofolder, $nowTime;
    $newName = md5($_file['name']);
    $newExt = pathinfo($_file['name'], PATHINFO_EXTENSION);
    $newaudioData = array(
        'filename_md5' => $newName,
        'type' => $newExt,
        'status' => 1,
        'date_added' => $nowTime
    );
    $mysql->create($audioTable, $newaudioData);
    $lastId = $mysql->cid->lastInsertId();
    $folder = get_folder($audiofolder, $lastId, $nowTime);
    move_uploaded_file($_file['tmp_name'], "$folder/$newName.$newExt");
    return $lastId;
}

function create_file($_file){
    global $fileTable, $filePrimarykey, $mysql, $filefolder, $nowTime;
    $newName = md5($_file['name']);
    $newExt = pathinfo($_file['name'], PATHINFO_EXTENSION);
    $newData = array(
        'filename_md5' => $newName,
        'type' => $newExt,
        'status' => 1,
        'date_added' => $nowTime
    );
    $mysql->create($fileTable, $newData);
    $lastId = $mysql->cid->lastInsertId();
    $folder = get_folder($filefolder, $lastId, $nowTime);
    move_uploaded_file($_file['tmp_name'], "$folder/$newName.$newExt");
    return $lastId;
}



function ImageResizeV3($src_name, $dst_name, $img_type, $new_width, $new_height) {
    if ($img_type == "image/jpeg" || $img_type == "image/pjpeg" || preg_match('/jpg/si', $img_type)) {
        $src_img = imagecreatefromjpeg($src_name);
    } elseif ($img_type == "image/png" || preg_match('/png/si', $img_type)) {
        $src_img = imagecreatefrompng($src_name);
    } elseif ($img_type == "image/gif" || preg_match('/gif/si', $img_type)) {
        $src_img = imagecreatefromgif($src_name);
    } else {
        $src_img = imagecreatefromjpeg($src_name);
    }
    if (($img_type == "image/jpeg") || ($img_type == "image/png") || $img_type == "image/pjpeg" || $img_type == "image/gif" || $img_type == "application/octet-stream" || true) {
        $orig_width = imagesX($src_img);
        $orig_height = imagesY($src_img);
        if ($orig_width < $new_width && $orig_height < $new_height) {
            $new_w = $orig_width;
            $new_h = $orig_height;
        } elseif (($orig_width - $new_width) / $new_width >= ($orig_height - $new_height) / $new_height) {
            $new_w = $new_width;
            $resize_factor = $new_width / $orig_width;
            $new_h = $orig_height * $resize_factor;
        } elseif (($orig_width - $new_width) / $new_width <= ($orig_height - $new_height) / $new_height) {
            $new_h = $new_height;
            $resize_factor = $new_height / $orig_height;
            $new_w = $orig_width * $resize_factor;
        }
        $dst_img = imagecreatetruecolor($new_w, $new_h);
        if ($img_type == "image/png" || preg_match('/png/si', $img_type)) {
            imagesavealpha($dst_img, true);
            $color = imagecolorallocatealpha($dst_img, 0, 0, 0, 127);
            imagefill($dst_img, 0, 0, $color);
        }
        $imgaea['width'] = $new_w;
        $imagea['height'] = $new_h;
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, imagesX($src_img), imagesY($src_img));
        if ($img_type == "image/jpeg" || $img_type == "image/pjpeg" || $img_type == "application/octet-stream" || preg_match('/jpg/si', $img_type)) {
            imagejpeg($dst_img, $dst_name, 90);
        } elseif ($img_type == "image/png" || preg_match('/png/si', $img_type)) {
            imagepng($dst_img, $dst_name);
        } elseif ($img_type == "image/gif" || preg_match('/gif/si', $img_type)) {
            imagegif($dst_img, $dst_name);
        } else {
            imagejpeg($dst_img, $dst_name, 90);
        }
        return array($new_w, $new_h);
    }
}
function ImageCropV3($src_name, $dst_name, $img_type, $new_width, $new_height, $jpgquality = 100) {
    if ($img_type == "image/jpeg" || $img_type == "image/pjpeg" || preg_match('/jpg/si', $img_type)) {
        $src_img = imagecreatefromjpeg($src_name);
    } elseif ($img_type == "image/png" || preg_match('/png/si', $img_type)) {
        $src_img = imagecreatefrompng($src_name) or die("couldn't create image using imagecreatefrompng");
    } elseif ($img_type == "image/gif" || preg_match('/gif/si', $img_type)) {
        $src_img = imagecreatefromgif($src_name) or die("couldn't create image using imagecreatefromgif");
    } else {
        $src_img = imagecreatefromjpeg($src_name);
    }
    if (($img_type == "image/jpeg") || ($img_type == "image/png") || $img_type == "image/pjpeg" || $img_type == "image/gif" || $img_type == "application/octet-stream" || true) {
        $orig_width = imagesX($src_img);
        $orig_height = imagesY($src_img);
        //if width is more than height, make width 60 pixels and
        //make height proportional
        if (($orig_width - $new_width) / $new_width >= ($orig_height - $new_height) / $new_height) {
            $srcHeight = $orig_height;
            $srcWidth = ($orig_height / $new_height) * $new_width;
            $startY = 0;
            $startX = ($orig_width - $srcWidth) / 2;
        } elseif (($orig_width - $new_width) / $new_width <= ($orig_height - $new_height) / $new_height) {
            //else use the height and make width proportional
            $srcHeight = ($orig_width / $new_width) * $new_height;
            $srcWidth = $orig_width;
            $startY = ($orig_height - $srcHeight) / 2;
            $startX = 0;
        }
        $dst_img = imagecreatetruecolor($new_width, $new_height);
        //$dst_img = imagecreate(30,44);
        imagecopyresampled($dst_img, $src_img, 0, 0, $startX, $startY, $new_width, $new_height, $srcWidth, $srcHeight);
        //make a thumbnail using different functions based on mimetype of original
        if ($img_type == "image/jpeg" || $img_type == "image/pjpeg" || $img_type == "application/octet-stream" || preg_match('/jpg/si', $img_type)) {
            imagejpeg($dst_img, $dst_name, $jpgquality);
        } elseif ($img_type == "image/png" || preg_match('/png/si', $img_type)) {
            imagepng($dst_img, $dst_name) or die("couldn't use imagepng");
        } elseif ($img_type == "image/gif" || preg_match('/gif/si', $img_type)) {
            imagegif($dst_img, $dst_name) or die("couldn't use imagegif");
        } else {
            imagejpeg($dst_img, $dst_name, $jpgquality);
        }
        return array($new_width, $new_height);
    }
}


function getExt($filename,$tolow=1) {
    $pos1 = strrpos($filename,".");
    $ext = substr($filename,$pos1+1);
    if($tolow) {
        return strtolower($ext);
    } else {
        return $ext;
    }
}

function image_fix_orientation($filename, $ext = FALSE) {
    if (! $ext) {
        $ext = getExt($filename);
    }
    if(!function_exists('exif_read_data')){return 0;}
    $exif = exif_read_data($filename);
    if (!empty($exif['Orientation'])) {
        $image = FALSE;
        if ($ext == "jpg") {
            $image = imagecreatefromjpeg($filename);
        } elseif ($ext == "png") {
            $image = imagecreatefrompng($filename);
        }
        switch ($exif['Orientation']) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;

            case 6:
                $image = imagerotate($image, -90, 0);
                break;

            case 8:
                $image = imagerotate($image, 90, 0);
                break;
        }

        if ($ext == "jpg") {
            imagejpeg($image, $filename, 90);
        } elseif ($ext == "png") {
            imagepng($image, $filename, 90);
        }
    }
    return getimagesize($filename);
}

function generateRandomString($length = 10, $strs = false) {
    $characters = $strs?$strs:'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
 ?>