<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'classes/PHPMailer/Exception.php';
require 'classes/PHPMailer/PHPMailer.php';
require 'classes/PHPMailer/SMTP.php';

function sendemail($main, $to, $subject = '', $message = '', $url = '', $response = '') {
    $mail = new PHPMailer(true);
        
    try {
        $mail->isSMTP();
        $mail->Host = $main->mail_host;
        $mail->SMTPAuth = true;
        $mail->Username = $main->mail_email;
        $mail->Password = $main->mail_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = $main->mail_charset;

        $mail->setFrom($main->mail_email);
        $mail->addAddress($to);
        $mail->addBCC($main->mail_cc);

        $mail->isHTML(true);
        $mail->Subject = $main->title . ($subject != '' ? ' - ' . $subject : '');
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        $main->header('', $url . '?' . $response);

    } catch (Exception $e) {
        $main->header('', '?err=Error enviando correo electrónico. Contacte el administrador.' . $mail->ErrorInfo);
        
    }
}