<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';  // If using Composer for autoloading, otherwise include PHPMailer's files directly.

function sendCompletionEmail($userEmail, $userName, $reportID) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';  // SMTP server (use smtp.gmail.com for Gmail)
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com';  // SMTP username
        $mail->Password = 'your-email-password';  // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;  // Use 465 for SSL

        //Recipients
        $mail->setFrom('no-reply@yourdomain.com', 'Maintenance System');
        $mail->addAddress($userEmail, $userName);  // Add recipient

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Maintenance Report Completed';
        $mail->Body    = "
            <html>
            <head>
                <title>Maintenance Report Completed</title>
            </head>
            <body>
                <p>Dear {$userName},</p>
                <p>Your maintenance report (ID: {$reportID}) has been marked as <strong>Completed</strong>.</p>
                <p>Thank you for using our service!</p>
            </body>
            </html>
        ";

        $mail->send();
        echo 'Email has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
