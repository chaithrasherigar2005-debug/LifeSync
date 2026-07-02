<?php
/**
 * includes/mailer.php
 * ---------------------------------------------------------------
 * Thin wrapper around PHPMailer so the rest of the app just calls
 * send_reset_email($toEmail, $resetLink).
 *
 * You said you already have PHPMailer set up on another project —
 * just point the require path below at your vendor/autoload.php
 * (Composer) OR your manual PHPMailer class files, and fill in the
 * SMTP block with the same credentials you used there.
 * ---------------------------------------------------------------
 */

// --- Manual PHPMailer files (no Composer needed) ------------------
// Files live at includes/PHPMailer/src/{PHPMailer,SMTP,Exception}.php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // If you later switch to Composer, this takes priority automatically.
    require __DIR__ . '/../vendor/autoload.php';
} else {
    require __DIR__ . '/PHPMailer/src/Exception.php';
    require __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/PHPMailer/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send the password-reset email.
 *
 * @param string $toEmail    recipient address
 * @param string $toName     recipient display name (optional)
 * @param string $resetLink  full URL to reset-password.php?token=...
 * @return bool true on success, false on failure
 */
function send_reset_email(string $toEmail, string $toName, string $resetLink): bool
{
    $mail = new PHPMailer(true);

    try {
        // ---- SMTP settings: replace with the same ones you used before ----
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';   // e.g. smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sherigarchaithra88@gmail.com';
        $mail->Password   = 'kznz aaef wlut kwir';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // or ENCRYPTION_SMTPS for port 465
        $mail->Port       = 587;                             // 465 if using SMTPS

        $mail->setFrom('no-reply@lifesync.app', 'LifeSync');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Reset your LifeSync password';
        $mail->Body    = build_reset_email_html($toName, $resetLink);
        $mail->AltBody = "Hi $toName,\n\nWe received a request to reset your LifeSync password. "
                        . "Open this link to set a new one (valid for 30 minutes):\n$resetLink\n\n"
                        . "If you didn't request this, you can safely ignore this email.";

        $mail->send();
        return true;
    } catch (Exception $e) {
    echo '<pre>Mail error: ' . htmlspecialchars($mail->ErrorInfo) . '</pre>';
    return false;
}
}

function build_reset_email_html(string $name, string $resetLink): string
{
    $safeName = htmlspecialchars($name ?: 'there');
    $safeLink = htmlspecialchars($resetLink);
    return <<<HTML
    <div style="font-family:'Segoe UI',Arial,sans-serif;max-width:480px;margin:0 auto;padding:32px 24px;background:#faf8ff;border-radius:16px;">
        <h2 style="color:#241b3b;margin:0 0 12px;">Reset your password</h2>
        <p style="color:#544c68;font-size:14px;line-height:1.6;">
            Hi {$safeName}, we received a request to reset the password on your LifeSync account.
            This link is valid for 30 minutes.
        </p>
        <p style="text-align:center;margin:28px 0;">
            <a href="{$safeLink}" style="background:linear-gradient(120deg,#6f53d4,#ec928a);color:#fff;
               text-decoration:none;padding:14px 28px;border-radius:14px;font-weight:700;display:inline-block;">
               Reset Password
            </a>
        </p>
        <p style="color:#9890ad;font-size:12.5px;line-height:1.6;">
            If you didn't request this, you can safely ignore this email — your password won't change.
        </p>
    </div>
    HTML;
}
