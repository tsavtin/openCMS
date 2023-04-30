<?php

  include_once 'includes/config.php';

  reset ($_FILES);
  $temp = current($_FILES);
  if (is_uploaded_file($temp['tmp_name'])){

    // Sanitize input
    if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
        header("HTTP/1.0 500 Invalid file name.");
        return;
    }
    $ext = strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION));
    // Verify extension
    if (!in_array($ext, array("gif", "jpg", "png"))) {
        header("HTTP/1.0 500 Invalid extension.");
        return;
    }
    $newName = md5($temp['name']);
    $newExt = pathinfo($temp['name'], PATHINFO_EXTENSION);
    $newImageData = array(
        'table' => $curCfg['table_name'],
        'file_org' => $temp['name'],
        'filename_md5' => $newName,
        'type' => $newExt,
        'date_added' => $nowTime,
        'status' => 1
    );
    $mysql->create($imageTable, $newImageData);
    $lastImageId = $mysql->cid->lastInsertId();
    $folder = get_folder($admin_imagefolder, $lastImageId, $nowTime);
    $new_path = "$folder/$newName.$newExt";

    $actual_path = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$absUrl;

    move_uploaded_file($temp['tmp_name'], $new_path);

    $imgurl = str_replace('../', '/', $new_path);

    $imgNewSize = getimagesize($new_path);

    $updateImageData = array(
        'width' => isset($imgNewSize[0])?$imgNewSize[0]:'0',
        'height' => isset($imgNewSize[1])?$imgNewSize[1]:'0'
    );

    $mysql->update($imageTable, array($imagePrimarykey => $lastImageId), $updateImageData);
    

    // Respond to the successful upload with JSON.
    // Use a location key to specify the path to the saved image resource.
    // { location : '/your/uploaded/image/file'}
    echo json_encode(array('location' => $actual_path.$imgurl));
  } else {
    // Notify editor that the upload failed
    header("HTTP/1.0 500 Server Error");
  }
?>