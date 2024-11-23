<?php
require_once __DIR__ . '/vendor/autoload.php'; 
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendLogoutEmail() {    
    $mailUsername = $_ENV['MAIL_USERNAME'];
    $mailPassword = $_ENV['MAIL_PASSWORD'];

    $mail = new PHPMailer(true);

    try {        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  
        $mail->SMTPAuth = true;
        $mail->Username = $mailUsername;  
        $mail->Password = $mailPassword;  
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom($mailUsername, 'Your Name');
        $mail->addAddress('voxvulgaris2993@gmail.com', 'Recipient');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Logout Notification';
        $mail->Body    = 'The user has logged out from the system.';

        // Send email
        $mail->send();
        echo 'Email sent successfully!';
    } catch (Exception $e) {
        echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>