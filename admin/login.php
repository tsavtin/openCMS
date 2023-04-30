<?php

error_reporting(0);
ini_set('display_errors', true);
// error_reporting(E_ALL);

require '../includes/vendor/phpmailer/phpmailer/src/Exception.php';
require '../includes/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../includes/vendor/phpmailer/phpmailer/src/SMTP.php';

include_once '../includes/project.php';
include_once '../includes/mysql.class.php';

if (file_exists('../project/' . $project_folder . '/config/dbconfig.php')) {
    include_once '../project/' . $project_folder . '/config/dbconfig.php';
}

if($mysqlhost && $mysqluser && $mysqldb && $dbprefix) {
    $mysql = new mysqlclass($mysqlhost, $mysqluser, $mysqlpass, $mysqldb, $dbprefix);
}

if (file_exists('../project/' . $project_folder . '/config/globalconfig.php')) {
    include_once '../project/' . $project_folder . '/config/globalconfig.php';
}
if($mysqlhost && $mysqluser && $mysqldb && $dbprefix) {
    $mysql = new mysqlclass($mysqlhost, $mysqluser, $mysqlpass, $mysqldb, $dbprefix);
}

if (file_exists('../project/' . $project_folder . '/config/textconfig.php')) {
    include_once '../project/' . $project_folder . '/config/textconfig.php';
} else {
    include_once '../project/default/config/textconfig.php';
}

foreach ($_POST as $key => $value) {
    $_POST[$key] = strip_tags($_POST[$key]);
    $_POST[$key] = str_replace('$', '&#x24;', $_POST[$key]);
    $_POST[$key] = preg_replace('/ on.*?=/i', '', $_POST[$key]);
    $_POST[$key] = preg_replace('/\/on.*?=/i', '', $_POST[$key]);
    $_POST[$key] = preg_replace('/\son.*?=/i', '', $_POST[$key]);
}

foreach ($_GET as $key => $value) {
    $_GET[$key] = strip_tags($_GET[$key]);
    $_GET[$key] = str_replace('$', '&#x24;', $_GET[$key]);
    $_GET[$key] = preg_replace('/ on.*?=/i', '', $_GET[$key]);
    $_GET[$key] = preg_replace('/\/on.*?=/i', '', $_GET[$key]);
    $_GET[$key] = preg_replace('/\son.*?=/i', '', $_GET[$key]);
}

session_start();

include_once '../includes/language_setting.php';

if (!function_exists("get_sys_text")) {
    function get_systext($field, $lang = null) {
        global $selected_lang, $systext;
        if(isset($systext[$lang?$lang:$selected_lang][$field])){
            return $systext[$lang?$lang:$selected_lang][$field];
        } else {
            return $field;
        }
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if($_POST['stage'] == 'submit' && $_GET['stage'] == 'forget_password'){
  $admin = $mysql->getData('admin', array('login_name' => $_POST['cms_login'], 'status' => 1));
  if($admin){
    $newdata = array(
        'token' => substr(md5(time()), 0, 10),
        'token_time' => date('Y-m-d H:i:s')
    );
    $mysql->update('admin', array('email'=>$admin['email']), $newdata);
    


    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = $emailConfig['smtp']['Host'];
    $mail->Port = $emailConfig['smtp']['Port'];
    $mail->SMTPSecure = $emailConfig['smtp']['SMTPSecure'];
    $mail->SMTPAuth = $emailConfig['smtp']['SMTPAuth'];
    $mail->Username = $emailConfig['smtp']['Username'];
    $mail->Password = $emailConfig['smtp']['Password'];

    $mail->CharSet = 'UTF-8';
    //Set who the message is to be sent from
    $mail->setFrom($emailConfig['smtp']['Username'], $project_name);
    //Set who the message is to be sent to
    $mail->addAddress($admin['email']);

    $mailInfo = $mysql->getData('email_template', array('email_type' => 'forget_password'));

    // print_r($mailInfo);
    // exit;

    //Set the subject line
    $mail->Subject =  $mailInfo['subject']?$mailInfo['subject']:'reset_pass';

    $actual_path = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$absUrl;

    $link = $actual_path.'admin/login.php?stage=reset_pass&email='.$admin['email'].'&reset_key='.$newdata['token'];
    $mail->Body = $mailInfo['message']?str_replace('{{reset_pass_link}}', $link, $mailInfo['message']):$link;
    $mail->isHTML(true);
    $mail->send();

    $message = 'reset password email sent to your inbox';
  }
}

if($_GET['stage'] == 'reset_pass'){
  $info = $mysql->getData('admin', array('email' => $_GET['email'], 'status' => 1));
  if(!$info){
      $message = 'invalid token';
  } else if($info['token'] != $_GET['reset_key'] || strtotime($info['token_time']) < strtotime(date('Y-m-d H:i:s'))-60*60){
    $message = 'token expired';
  } else if($_POST['stage'] == 'submit'){
    if($_POST['password_new'] != $_POST['password_confirm']){
      $message = 'confirm password not match';
    } else {
      $mysql->update('admin', array('admin_id' => $info['admin_id']), array(
        'password' => md5($_POST['password_new'] . $encryptKey)
      ));
      $message = 'reset password success!';
    }
  }
}

if(!isset($_GET['redirect'])){
  $_GET['redirect'] = '';
}

?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo get_systext(is_array($project_name)?$project_name[$selected_lang]:$project_name); ?> <?php echo get_systext('login_admin'); ?></title>

    <!-- Bootstrap -->
    <link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="build/css/custom.css" rel="stylesheet">
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

  <body class="login">
    <div>
      <a class="hiddenanchor" id="signup"></a>
      <a class="hiddenanchor" id="signin"></a>

      <div class="login_wrapper">
        <div class="animate form login_form">
          <section class="login_content">
            <?php 
              $logo = '../project/' . $project_folder . '/images/logo.png';
              if (file_exists($logo)) {
                  echo '<img src="'.$logo.'" width="100%" style="margin-bottom:50px;">';
              }
            ?>
            <?php if($_GET['stage'] == 'reset_pass'){ ?>
            <form action="./login.php?stage=reset_pass" method="post" name="login_form">
              <input type="hidden" name="stage" value="submit"/>
              <input type="hidden" name="email" value="<?php echo $_GET['email']?$_GET['email']:$_POST['email']; ?>"/>
              <input type="hidden" name="reset_key" value="<?php echo $_GET['reset_key']?$_GET['reset_key']:$_POST['reset_key']; ?>"/>
              <!-- 0.3.3 -->
              <h1 style="color: black;"><?php echo get_systext(is_array($project_name)?$project_name[$selected_lang]:$project_name); ?> <?php echo get_systext('login_admin'); ?></h1>
              <?php if(!$message){ ?>
              <div>Reset Password</div>
              <?php } ?>
              <div>
                <span class="message"><?php echo $message; ?></span>
                <?php if(!$message){ ?>
                  <input type="password" class="form-control input-lg" required="true" placeholder="<?php echo get_systext('login_password_new'); ?>" name="password_new"/>
                  <input type="password" class="form-control input-lg" required="true" placeholder="<?php echo get_systext('login_password_confirm'); ?>" name="password_confirm"/>
                <?php } ?>
                <!-- 0.3.2 -->
              </div>
              <?php if(!$message){  ?>
              <div>
                <!-- 0.3.2 -->
                <a class="btn btn-default submit btn-lg" href="javascript:void(0);" onclick="document.forms['login_form'].submit();">Reset</a>
              </div>
              <script type="text/javascript">
                $('[name="password_new"],[name="password_confirm"]').keydown(function(){
                  let newval = $('[name="password_new"]').val();
                  let conformval = $('[name="password_confirm"]').val();
                  if(newval && conformval && newval != conformval){
                    $('.message').html('new password and confirm password not match');
                  } else {
                    $('.message').html('');
                  }
                });
              </script>
              <?php } ?>
              <div class="clearfix"></div>
              <!-- 0.3.4 -->
              <div>
                <?php if ($support_multi_lang) { ?>
                  <p class="change_link">
                    <?php if($selected_lang != 'en' && $support_lang['en']){ ?><a href="login.php?redirect=<?php echo $_GET['redirect']; ?>&lang=en" class="to_register">EN</a><?php } ?>
                    <?php if($selected_lang != 'tc' && $support_lang['tc']){ ?><a href="login.php?redirect=<?php echo $_GET['redirect']; ?>&lang=tc" class="to_register">繁體</a><?php } ?>
                    <?php if($selected_lang != 'sc' && $support_lang['sc']){ ?><a href="login.php?redirect=<?php echo $_GET['redirect']; ?>&lang=sc" class="to_register">简体</a><?php } ?>
                  </p>
                <?php } ?>
                <div class="clearfix"></div>
              </div>
            </form>
            <?php } else if($_GET['stage'] == 'forget_password'){ ?>
            <form action="./login.php?stage=forget_password" method="post" name="login_form">
              <input type="hidden" name="stage" value="submit"/>
              <!-- 0.3.3 -->
              <h1 style="color: black;"><?php echo get_systext(is_array($project_name)?$project_name[$selected_lang]:$project_name); ?> <?php echo get_systext('login_admin'); ?></h1>
              <div>Forget Password</div>
              <div>
                <?php if($message){ echo $message; } else { ?>
                  <!-- 0.3.2 -->
                  <input type="text" class="form-control input-lg" placeholder="<?php echo get_systext('login_username'); ?>" name="cms_login"/>
                <?php } ?>
                
              </div>
              <?php if(!$message){  ?>
              <div>
                <!-- 0.3.2 -->
                <a class="btn btn-default submit btn-lg" href="javascript:void(0);" onclick="document.forms['login_form'].submit();">Send</a>
              </div>
              <?php } ?>
              <div class="clearfix"></div>
              <!-- 0.3.4 -->
              <div>
                <?php if ($support_multi_lang) { ?>
                  <p class="change_link">
                    <?php if($selected_lang != 'en' && $support_lang['en']){ ?><a href="login.php?redirect=<?php echo $_GET['redirect']; ?>&lang=en" class="to_register">EN</a><?php } ?>
                    <?php if($selected_lang != 'tc' && $support_lang['tc']){ ?><a href="login.php?redirect=<?php echo $_GET['redirect']; ?>&lang=tc" class="to_register">繁體</a><?php } ?>
                    <?php if($selected_lang != 'sc' && $support_lang['sc']){ ?><a href="login.php?redirect=<?php echo $_GET['redirect']; ?>&lang=sc" class="to_register">简体</a><?php } ?>
                  </p>
                <?php } ?>
                <div class="clearfix"></div>
              </div>
            </form>
            <?php } else { ?>
            <form action="index.php" method="post" name="login_form">
              <input type="hidden" name="stage" value="login"/>
              <!-- 0.3.3 -->
              <h1 style="color: black;"><?php echo get_systext(is_array($project_name)?$project_name[$selected_lang]:$project_name); ?> <?php echo get_systext('login_admin'); ?></h1>
              <div ><?php if ($_GET['message']) { echo htmlspecialchars($_GET['message']).'<br><br>'; } ?></div>
              <div>
                <!-- 0.3.2 -->
                <input type="text" class="form-control input-lg" placeholder="<?php echo get_systext('login_username'); ?>" name="cms_login"/>
              </div>
              <div>
                <!-- 0.3.2 -->
                <input type="password" class="form-control input-lg" placeholder="<?php echo get_systext('login_password'); ?>" onkeypress="if(event.keyCode == 13){submit(this);}" name="cms_password"/>
                <a style="float: left; margin-top: -15px;" href="./login.php?stage=forget_password"><?php echo get_systext('login_forget_password'); ?></a>
              </div>
              <div style="clear: both;">
                <!-- 0.3.2 -->
                <a class="btn btn-default submit btn-lg" href="javascript:void(0);" onclick="document.forms['login_form'].submit();"><?php echo get_systext('login_login'); ?></a>
                
              </div>
              <div class="clearfix"></div>
              <!-- 0.3.4 -->
              <div>
                <?php if ($support_multi_lang) { ?>
                  <p class="change_link">
                    <?php if($selected_lang != 'en' && $support_lang['en']){ ?><a href="login.php?redirect=<?php echo $_GET['redirect']; ?>&lang=en" class="to_register">EN</a><?php } ?>
                    <?php if($selected_lang != 'tc' && $support_lang['tc']){ ?><a href="login.php?redirect=<?php echo $_GET['redirect']; ?>&lang=tc" class="to_register">繁體</a><?php } ?>
                    <?php if($selected_lang != 'sc' && $support_lang['sc']){ ?><a href="login.php?redirect=<?php echo $_GET['redirect']; ?>&lang=sc" class="to_register">简体</a><?php } ?>
                  </p>
                <?php } ?>
                <div class="clearfix"></div>
              </div>
            </form>
            <?php } ?>
          </section>
        </div>
      </div>
    </div>
  </body>
</html>