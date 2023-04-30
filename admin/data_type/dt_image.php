<?php
class dt_image extends data_type {

    private $config;
    public function config($field=false){
        if($field){
            $this->clear_config();
            $this->config = parent::config($field);
            if($field['extra_opt'] && $field['extra_opt'][0]){
                $this->config['resize_type'] = $field['extra_opt'][0][0];
                $this->config['width'] = $field['extra_opt'][0][1];
                $this->config['height'] = $field['extra_opt'][0][2];
                $this->config['water_mark'] = $field['extra_opt'][0][3];
                $this->config['mb_limit'] = $field['extra_opt'][0][4];
                $this->config['mime_type'] = $field['extra_opt'][0][5];
                $this->config['allow_rotate'] = $field['extra_opt'][0][6];
            }
        }
        return $this->config;
    }

		// build list value
	public function list_value($values){
		if ($values[$this->get_index()]) {
			$imgurl = getImageUrl($values[$this->get_index()]);
			return '<a href="'.$imgurl.'" class="fancybox" rel="fancybox_group"><img src="'.$imgurl.'"  style="height:100%; max-height:40px;"/></a>';
        } else {
        	return '';
        }
	}

	// 處理  before form submit
	public function form_validate($value){
        global $_FILES, $imageConfig, $curCfg;
        $sqlskip = true;
        $data = false;
        $error = false;
        $field = $this->config();
        $cfg = $imageConfig[$curCfg['index'] . '.' . $field['field_index']];
        $mb_limit = $field['mb_limit']?$field['mb_limit']:$cfg[5];
        if(isset($mb_limit) && $mb_limit){
            if($mb_limit*1024*1024 < filesize($_FILES[$field['field_index']]['tmp_name'])){
                $error = 'maximum upload '.$field['field_name'].' size('.$mb_limit.'MB)';
            }
        }
		return array($sqlskip, $data, $error);
	}

    public function form_after_duplicate($value = null){
        global $_FILES, $_POST, $curCfg, $imageTable, $imagePrimarykey, $admin_imagefolder, $query_data, $nowTime;
        $field = $this->config();
        if (file_exists($_FILES[$field['field_index']]['tmp_name'])){
            $this->form_after_submit();
        } else {
            $value = $value!=null?$value:$_POST[$this->get_index()];
            if ($value) {
                $this->duplicate_file($value, $curCfg['table_name'], $query_data, $field['field_index']);
            }
        }
    }

    public function duplicate_file($from_id, $table_name, $query_data, $field){
        global $imageTable, $imagePrimarykey, $admin_imagefolder, $nowTime;
        $imageData = $this->mysql->getData($imageTable, array($imagePrimarykey => $from_id));
        $folder = get_folder($admin_imagefolder, $from_id, $imageData['date_added']);
        $imagePath = "$folder/$imageData[filename_md5].$imageData[type]";
        $newName = md5($imageData['file_org']);
        $newExt = pathinfo($imageData['file_org'], PATHINFO_EXTENSION);
        $newImageData = array(
            'table' => $table_name,
            'file_org' => $imageData['file_org'],
            'filename_md5' => $newName,
            'type' => $newExt,
            'status' => 1,
            'date_added' => $nowTime,
            'width' => $imageData['width'],
            'height' => $imageData['height']
        );
        $this->mysql->create($imageTable, $newImageData);
        $lastImageId = $this->mysql->cid->lastInsertId();
        $folder = get_folder($admin_imagefolder, $lastImageId, $nowTime);
        copy($imagePath, "$folder/$newName.$newExt");
        $this->mysql->update($table_name, $query_data, array($field => $lastImageId));
    }



    // 處理 after submit form
    public function form_after_submit(){
        global $_FILES, $imageConfig, $curCfg, $imageTable, $imagePrimarykey, $admin_imagefolder, $query_data, $nowTime;
        $field = $this->config();
        if (file_exists($_FILES[$field['field_index']]['tmp_name'])) {
            // cheeck old data to delete start
            if($curCfg['table_type'] == 'setting'){
                $query = array($curCfg['table_key'] => $field['field_index']);
                if($curCfg['parent_related_key']){
                    $query[$curCfg['parent_related_key']] = $_GET[$curCfg['parent_related_key']];
                }
                $info = $this->mysql->getData($curCfg['table_name'], $query);
                $oldid = $info['value'];
            } else {
                $info = $this->mysql->getData($curCfg['table_name'], $query_data);
                $oldid = $info[$field['field_index']];
            }
            if ($oldid) {
                $this->delete_record(array($field['field_index'] => $oldid));
            }
            // cheeck old data to delete end

            $newName = md5($_FILES[$field['field_index']]['name']);
            $newExt = pathinfo($_FILES[$field['field_index']]['name'], PATHINFO_EXTENSION);
            $newImageData = array(
                'table' => $curCfg['table_name'],
                'file_org' => $_FILES[$field['field_index']]['name'],
                'filename_md5' => $newName,
                'type' => $newExt,
                'date_added' => $nowTime,
                'date_modified' => $nowTime,
                'status' => 1
            );
            $this->mysql->create($imageTable, $newImageData);
            $lastImageId = $this->mysql->cid->lastInsertId();
            $folder = get_folder($admin_imagefolder, $lastImageId, $nowTime);
           
            $imgCfg = $imageConfig[$curCfg['index'] . '.' . $field['field_index']];

            $resize_type = $field['resize_type']?$field['resize_type']:$imgCfg[0];
            $img_width = $field['width']?$field['width']:$imgCfg[1];
            $img_height = $field['height']?$field['height']:$imgCfg[2];
            $new_path = "$folder/$newName.$newExt";

            if ($resize_type == 'crop') {
                image_fix_orientation($_FILES[$field['field_index']]['tmp_name'], getExt($_FILES[$field['field_index']]['name']));
                $imgNewSize = ImageCropV3($_FILES[$field['field_index']]['tmp_name'], $new_path, $_FILES[$field['field_index']]['type'], $img_width, $img_height);
            } else if ($resize_type == 'resize') {
                image_fix_orientation($_FILES[$field['field_index']]['tmp_name'], getExt($_FILES[$field['field_index']]['name']));
                $imgNewSize = ImageResizeV3($_FILES[$field['field_index']]['tmp_name'], $new_path, $_FILES[$field['field_index']]['type'], $img_width, $img_height);
            } else if ($resize_type == 'copy') {
                move_uploaded_file($_FILES[$field['field_index']]['tmp_name'], $new_path);
                $imgNewSize = getimagesize($new_path);
            }
            if(isset($imgCfg[4]) && is_array($imgCfg[4])){
                $this->add_watermark($new_path, $imgCfg[4][0], $imgCfg[4][1], $imgCfg[4][2]);
            }
            $updateImageData = array(
                'width' => isset($imgNewSize[0])?$imgNewSize[0]:'0',
                'height' => isset($imgNewSize[1])?$imgNewSize[1]:'0'
            );
            $this->mysql->update($imageTable, array($imagePrimarykey => $lastImageId), $updateImageData);
            if($curCfg['table_type'] == 'setting'){
                $query = array($curCfg['table_key'] => $field['field_index']);
                if($curCfg['parent_related_key']){
                    $query[$curCfg['parent_related_key']] = $_GET[$curCfg['parent_related_key']];
                }
                $this->mysql->update($curCfg['table_name'], $query, array($curCfg['table_value'] => $lastImageId));
            } else {
                $this->mysql->update($curCfg['table_name'], $query_data, array($field['field_index'] => $lastImageId));
            }
            return $lastImageId;
        }
        return null;
    }

	// build html form field
    public function form_html($value, $formerror, $name = false){
    	global $imageConfig, $curCfg, $mysql, $imageTable, $imagePrimarykey, $admin_imagefolder, $_GET;
    	$field = $this->config();
        $fieldOpts = fieldOpt($field['field_options']);
    	$output = '';
        if(!isset($fieldOpts['modify_readonly'])){
            $mime_type = $field['mime_type']?$field['mime_type']:$imageConfig[$curCfg['index'] . '.' . $field['field_index']][3];
            if (isset($value[$field['field_index']]) && $value[$field['field_index']]) {
                $imageData = $mysql->getData($imageTable, array($imagePrimarykey => $value[$field['field_index']]));
                $folder = get_folder($admin_imagefolder, $value[$field['field_index']], $imageData['date_added']);
                $imgurl = "$folder/$imageData[filename_md5].$imageData[type]?t=".time();
                $output = '<input type="hidden" name="'.($name?$name:$field['field_index']).'" value="'.$value[$field['field_index']].'">';
                $output .= '<a href="'.$imgurl.'" class="fancybox"><img style="height:100%; max-height:100px; width: auto;" src="'.$imgurl.'"></a><br>';

                if($_GET['stage'] != 'duplicate'){
                    $output .= '<a href="?'.get_link().'&sub_stage=delete_image&del_field='.$field['field_index'].'&sub_stage_id='.$value[$field['field_index']].'" onclick="return confirm(\'delete image?\');"><span class="badge bg-red">delete image</span></a>';
                }
                
                
                if($field['allow_rotate']){
                    $output .= '&nbsp;<a href="?'.get_link().'&sub_stage=rotate_imgleft&del_field='.$field['field_index'].'&sub_stage_id='.$value[$field['field_index']].'""><span class="badge bg-green"><i class="fa fa-angle-left"></i>&nbsp;&nbsp;Rotate</span></a>';
                    $output .= '&nbsp;<a href="?'.get_link().'&sub_stage=rotate_imgright&del_field='.$field['field_index'].'&sub_stage_id='.$value[$field['field_index']].'""><span class="badge bg-green">Rotate&nbsp;&nbsp;<i class="fa fa-angle-right"></i></span></a>';
                }
            }
            $output .= '<div style="padding-top:8px;"></div><input type="file" name="'.($name?$name:$field['field_index']).'" accept="'.$mime_type.'">';
        } else if (isset($value[$field['field_index']]) && $value[$field['field_index']]) {
            $imageData = $mysql->getData($imageTable, array($imagePrimarykey => $value[$field['field_index']]));
            $folder = get_folder($admin_imagefolder, $value[$field['field_index']], $imageData['date_added']);
            $imgurl = "$folder/$imageData[filename_md5].$imageData[type]?t=".time();
    	    $output = '<a href="'.$imgurl.'" class="fancybox"><img style="height:100%; max-height:100px;" src="'.$imgurl.'"></a><br>';
        }
        return $output;
    }
    public function mysql_field_type(){
        return 'int(11) null';
    }
    public function delete_record($values){
        global $curCfg, $admin_imagefolder, $imageTable, $imagePrimarykey;
        $field = $this->config();
        $field_index = $field['field_index'];
        $imgData = $this->mysql->getData($imageTable, array($imagePrimarykey => $values[$field_index]));
        if($imgData){
            $this->mysql->delete($imageTable, array($imagePrimarykey => $values[$field_index]));
            $folder = get_folder($admin_imagefolder, $values[$field_index], $imgData['date_added']);
            deleteFolder($folder);
        }
    }

    public function rotate($id, $rotate){
        global $curCfg, $admin_imagefolder, $imageTable, $imagePrimarykey;
        $imgData = $this->mysql->getData($imageTable, array($imagePrimarykey => $id));
        if($imgData){
            $folder = get_folder($admin_imagefolder, $id, $imgData['date_added']);
            $newName = $imgData['filename_md5'];
            $newExt = $imgData['type'];
            $fullpath = "$folder/$newName.$newExt";

            $winfo = getimagesize($fullpath);
            $w_type = $winfo['mime'];
            if ($w_type == "image/jpeg" || $w_type == "image/pjpeg" || preg_match('/jpg$/si', $w_type)) {
                $image = imagecreatefromjpeg($fullpath) or die("couldn't create image using imagecreatefromjpeg");
                $image = imagerotate($image, $rotate, 0);
                imagejpeg($image, $fullpath, 90);
            } elseif ($w_type == "image/png" || preg_match('/png$/si', $w_type)) {
                $image = imagecreatefrompng($fullpath) or die("couldn't create image using imagecreatefrompng");
                $image = imagerotate($image, $rotate, 0);
                imagepng($image, $fullpath);
            } elseif ($w_type == "image/gif" || preg_match('/gif$/si', $w_type)) {
                $image = imagecreatefromgif($fullpath) or die("couldn't create image using imagecreatefromgif");
                $image = imagerotate($image, $rotate, 0);
                imagepng($image, $fullpath);
            } else {
                $image = imagecreatefromjpeg($fullpath) or die("couldn't create image using imagecreatefromjpeg");
                $image = imagerotate($image, $rotate, 0);
                imagejpeg($image, $fullpath, 90);
            }
        }
    }

    public function add_watermark($imgsrc, $watermarksrc, $position, $width){
        $imginfo = getimagesize($imgsrc);
        $img_type = $imginfo['mime'];
        $src_img = $w_img = null;
        if ($img_type == "image/jpeg" || $img_type == "image/pjpeg" || preg_match('/jpg$/si', $img_type)) {
            $src_img = imagecreatefromjpeg($imgsrc) or die("couldn't create image using imagecreatefromjpeg");
        } elseif ($img_type == "image/png" || preg_match('/png$/si', $img_type)) {
            $src_img = imagecreatefrompng($imgsrc) or die("couldn't create image using imagecreatefrompng");
        } elseif ($img_type == "image/gif" || preg_match('/gif$/si', $img_type)) {
            $src_img = imagecreatefromgif($imgsrc) or die("couldn't create image using imagecreatefromgif");
        } else {
            $src_img = imagecreatefromjpeg($imgsrc) or die("couldn't create image using imagecreatefromjpeg");
        }

        if($src_img === null){
            die("couldn't create image");
        }

        $winfo = getimagesize($watermarksrc);
        $w_type = $winfo['mime'];
        if ($w_type == "image/jpeg" || $w_type == "image/pjpeg" || preg_match('/jpg$/si', $w_type)) {
            $w_img = imagecreatefromjpeg($watermarksrc) or die("couldn't create image using imagecreatefromjpeg");
        } elseif ($w_type == "image/png" || preg_match('/png$/si', $w_type)) {
            $w_img = imagecreatefrompng($watermarksrc) or die("couldn't create image using imagecreatefrompng");
        } elseif ($w_type == "image/gif" || preg_match('/gif$/si', $w_type)) {
            $w_img = imagecreatefromgif($watermarksrc) or die("couldn't create image using imagecreatefromgif");
        } else {
            $w_img = imagecreatefromjpeg($watermarksrc) or die("couldn't create image using imagecreatefromjpeg");
        }
        
        if($w_img === null){
            die("couldn't create image");
        }
        if(imagesX($src_img) > imagesY($src_img)){
            $w_w = imagesX($src_img)*($width/100);
            $w_h = ($w_w/imagesX($w_img))*imagesY($w_img);
        } else {
            $w_h = imagesY($src_img)*($width/100);
            $w_w = ($w_h/imagesY($w_img))*imagesX($w_img);
        }
        
        if($position == 'tl'){
            imagecopyresampled($src_img, $w_img, 0, 0, 0, 0, $w_w, $w_h, imagesX($w_img), imagesY($w_img));
        } else if($position == 'tr'){
            imagecopyresampled($src_img, $w_img, imagesX($src_img)-$w_w, 0, 0, 0, $w_w, $w_h, imagesX($w_img), imagesY($w_img));
        } else if($position == 'bl'){
            imagecopyresampled($src_img, $w_img, 0, 0, 0, 0, $w_w, $w_h, imagesX($w_img), imagesY($w_img));
        } else if($position == 'br'){
            imagecopyresampled($src_img, $w_img, imagesX($src_img)-$w_w, imagesY($src_img)-$w_h, 0, 0, $w_w, $w_h, imagesX($w_img), imagesY($w_img));
        }
        if ($img_type == "image/jpeg" || $img_type == "image/pjpeg" || $img_type == "application/octet-stream" || preg_match('/jpg/si', $img_type)) {
            imagejpeg($src_img, $imgsrc, 90) or die("couldn't use imagejpeg");
        } elseif ($img_type == "image/png" || preg_match('/png/si', $img_type)) {
            imagepng($src_img, $imgsrc) or die("couldn't use imagepng");
        } elseif ($img_type == "image/gif" || preg_match('/gif/si', $img_type)) {
            imagegif($src_img, $imgsrc) or die("couldn't use imagegif");
        } else {
            imagejpeg($src_img, $imgsrc, 90) or die("couldn't use imagejpeg");
        }
    }

}
?>