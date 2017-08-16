<?php

// 应用公共文件
function send_email($to,$subject='',$content='',$copyto=array()){
	//require_once '../vendor/phpmailer/PHPMailerAutoload.php';
	require_once '../vendor/phpmailer/class.phpmailer.php';
    require_once '../vendor/phpmailer/class.smtp.php';
    $mail = new PHPMailer;
    $mail->CharSet  = 'UTF-8';
    $mail->isSMTP();
	$mail->SMTPDebug = 0;
    $mail->Host = 'postbox.jianke.com';
	$mail->Port = 25;
	if($mail->Port === 465) $mail->SMTPSecure = 'ssl';
    $mail->SMTPAuth=true;
    $mail->FromName = session('loginname.dealname');
	$mail->Username = 'gztest';
	$mail->Password = 'gztest0308';
	$mail->From= 'gztest@jianke.com';
    $mail->isHTML(true);
	foreach($to as $v){
		$mail->addAddress($v);
	}
	foreach($copyto as $v){
		$mail ->addCC($v);
	}
    $mail->Subject = $subject;
    $mail->Body = $content;
    $status = $mail->send();
    if($status) {
        return true;
    }else{
        return false;
    }
}


//将带html格式的字段转换成excel能识别的字符
function  htmltoexcel($str){
    $str = preg_replace("/<p>/i", "", $str);
    $str = preg_replace("/<\\/p>/i", "\r\n", $str);
    $str = preg_replace("/<p[^>]*>/i", "", $str);
    $str = preg_replace("/&nbsp;/i", " ", $str);
    return $str;
}

