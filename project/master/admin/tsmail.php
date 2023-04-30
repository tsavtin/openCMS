<?php

include_once 'includes/config.php';

error_reporting(E_ALL);


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
$mail->setFrom($emailConfig['smtp']['Username'], 'testing from jedar');
//Set who the message is to be sent to
$mail->addAddress('jeff@ryl.hk');
//$mail->addAddress('reeve.chan@omlogasia.com');
//Set the subject line
$mail->Subject = 'testing';

// $mail->addStringAttachment($pdf2, 'Invoice.pdf');

$mail->Body = 'testing';

$mail->SMTPDebug = 4;
$mail->Debugoutput = 'html';

$mail->isHTML(true);

$mail->send();


?>