<?php

include_once 'includes/config.php';


$formerror = array();
if(isset($_POST['form_stage']) && $_POST['form_stage'] == 'form_submit'){
	if(isset($_POST['cnt_btns_btn']) && $_POST['cnt_btns_btn'] == 'btn_test_template'){


		if(!$_POST['test_email']){
			$formerror['test_email'] = get_systext('msg_missing_field');
		} else if(filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL) === false) {
			$formerror['test_email'] = get_systext('msg_invaild_email');
		} else if(!$_POST['test_language']) {
			$formerror['test_language'] = get_systext('msg_missing_field');
		} else {
			$mail = new PHPMailerOAuth;
			$mail->isSMTP();
			$mail->Host = $emailConfig['smtp']['Host'];
			$mail->Port = $emailConfig['smtp']['Port'];
			$mail->SMTPSecure = $emailConfig['smtp']['SMTPSecure'];
			$mail->SMTPAuth = $emailConfig['smtp']['SMTPAuth'];
			$mail->Username = $emailConfig['smtp']['Username'];
			$mail->Password = $emailConfig['smtp']['Password'];

			try {

				//Set who the message is to be sent to````````````	
				$mail->addAddress($_POST['test_email']);
				//Set the subject line

				if($_POST['test_language'] == 'ENG'){
					$mail->Subject = $_POST['subject_en'];
					$mail->Body = $_POST['message_en'];
				} else if($_POST['test_language'] == '简体'){
					$mail->Subject = $_POST['subject_sc'];
					$mail->Body = $_POST['message_sc'];
				} else if($_POST['test_language'] == '繁體'){
					$mail->Subject = $_POST['subject_tc'];
					$mail->Body = $_POST['message_tc'];
				}
				
				$mail->isHTML(true);
				$mail->send();



			  	$form_message = " $curCfg[title] " . get_systext('msg_sent') . " :" . date('Y-m-d H:i:s');
			} catch (phpmailerException $e) {
				$form_message = $e->errorMessage(); //Pretty error messages from PHPMailer
			} catch (Exception $e) {
			 	$form_message = $e->getMessage(); //Boring error messages from anything else!
			}
		}
		unset($_POST['form_stage']);
	}
}

include 'content.php';
?>