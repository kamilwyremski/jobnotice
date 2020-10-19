<?php

if(!isset($settings['base_url'])){
	die('Access denied!');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail_smtp = new PHPMailer();

$mail_smtp->IsSMTP();
$mail_smtp->CharSet = "utf-8";
$mail_smtp->From = $settings['smtp_mail'];
$mail_smtp->FromName = $settings['title'];
$mail_smtp->Host = $settings['smtp_host'];
$mail_smtp->Mailer = "smtp";
$mail_smtp->Username = $settings['smtp_user'];
$mail_smtp->Password = $settings['smtp_password'];
$mail_smtp->SMTPAuth = true;
$mail_smtp->Port = $settings['smtp_port'];
$mail_smtp->SMTPSecure = $settings['smtp_secure'];
$mail_smtp->IsHTML(true);

$mail_smtp->smtpConnect(
    [
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
            "allow_self_signed" => true
        ]
    ]
);

return $mail_smtp;
