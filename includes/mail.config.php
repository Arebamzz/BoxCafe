<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

require_once __DIR__ . '/../config/app.php';
define('BOXKL_NOTIFY_EMAIL', box_config('notify_email'));

/** ✅ Put your sender Gmail + App Password here */
define('BOXKL_SMTP_USER', box_config('smtp_user'));
define('BOXKL_SMTP_PASS', box_config('smtp_password')); 

function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);

    // 0=off, 2=detail
    $mail->SMTPDebug = 0;
    $mail->Debugoutput = 'error_log';

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;

    $mail->Username   = BOXKL_SMTP_USER;
    $mail->Password   = BOXKL_SMTP_PASS;

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // sender mesti sama dengan Username (Gmail strict)
    $mail->setFrom(BOXKL_SMTP_USER, 'BOX Cafe');

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return $mail;
}


function send_mail(string $to, string $subject, string $html): bool {
    try {
        $mail = createMailer();
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        return $mail->send();
    } catch (Throwable $e) {
        error_log('[MAIL_FAIL] ' . $e->getMessage());
        return false;
    }
}
