<?php

// password enctyption key
$encryptKey = 'ASDF12234$#%^SDFw8ert9G';

$absUrl = '/';
// if($_SERVER['HTTP_HOST'] == 'localhost'){
//     $absUrl = '/client/jcms/';
// }

// email setting , below setting for gmail this email client use phpmailer file in /includes/phpmailer
$emailConfig = array(
    'type' => 'smtp', // smtp or mail
    'smtp' => array(
        'Host' => 'smtp.zoho.com',
        'Port' => 587,
        'SMTPSecure' => "tls",
        'SMTPAuth' => TRUE,
        'Username' => 'xxx@xxx.xxx',
        'Password' => 'xxx'
    )
);

?>