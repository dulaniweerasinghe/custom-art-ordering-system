<?php
require_once 'includes/config.php';
require_once 'includes/PHPMailer/Exception.php';
require_once 'includes/PHPMailer/PHPMailer.php';
require_once 'includes/PHPMailer/SMTP.php';

$mail = new \PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'dulaniweerasinghe2002@gmail.com';
    $mail->Password   = 'YOUR_APP_PASSWORD_HERE'; // ← App Password දාන්න
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->SMTPDebug  = 2; // ← මේකෙන් full debug log එනවා

    $mail->setFrom('dulaniweerasinghe2002@gmail.com', 'SKETCH-CORE');
    $mail->addAddress('dulaniweerasinghe2002@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = '<h1>Test email works!</h1>';

    $mail->send();
    echo 'Email sent successfully!';

} catch (Exception $e) {
    echo 'Error: ' . $mail->ErrorInfo;
}
?>