<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Load PHPMailer directly (no Composer needed) ──────────────────────────
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

define('MAIL_FROM','srichaithra529@gmail.com');
define('MAIL_PASSWORD', 'ggjs emgs jjme iznx');

// ── LOCAL_DEV = true  → OTP shown on screen (no email needed, perfect for XAMPP)
// ── LOCAL_DEV = false → sends real email via Gmail SMTP (for live server)
define('LOCAL_DEV', false);

// ── Google OAuth 2.0 Credentials (Replace with your actual keys from Google Console) ──
define('GOOGLE_CLIENT_ID', 'your-google-client-id-here.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'your-google-client-secret-here');
define('GOOGLE_REDIRECT_URI', 'http://localhost/lifesync/google-callback.php');

function send_otp_email(string $toEmail, string $toName, string $otp): void
{
    if (LOCAL_DEV) {
        // Store OTP in session — forgot-password.php will show it on screen
        $_SESSION['dev_otp'] = $otp;
        unset($_SESSION['mail_debug_error']);
        return;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_FROM;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom(MAIL_FROM, 'LifeSync');
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "Your LifeSync reset code: {$otp}";
        $mail->Body    = "
        <div style='font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:32px;
                    background:#f8f8ff;border-radius:12px;'>
            <div style='text-align:center;margin-bottom:24px;'>
                <span style='font-size:26px;font-weight:800;'>
                    <span style='color:#3F8C3F;'>Life</span><span style='color:#5B4FCF;'>Sync</span>
                </span>
            </div>
            <h2 style='color:#1a1a2e;'>Password Reset</h2>
            <p>Hi " . htmlspecialchars($toName ?: 'there') . ",</p>
            <p>Use the code below to reset your password. It expires in <strong>10 minutes</strong>.</p>
            <div style='font-size:40px;font-weight:900;letter-spacing:14px;color:#5B4FCF;
                        padding:20px;background:#ededff;border-radius:10px;
                        text-align:center;margin:24px 0;'>{$otp}</div>
            <p style='color:#aaa;font-size:13px;'>If you didn't request this, ignore this email.</p>
        </div>";
        $mail->AltBody = "Your LifeSync reset code is: {$otp}. It expires in 10 minutes.";

        $mail->send();
        unset($_SESSION['mail_debug_error']);

    } catch (Exception $e) {
        $_SESSION['mail_debug_error'] = $mail->ErrorInfo;
    }
}