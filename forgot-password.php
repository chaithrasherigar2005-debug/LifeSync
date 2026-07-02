<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$step        = 'request';
$message     = '';
$messageType = '';

if (isset($_GET['restart'])) {
    unset($_SESSION['otp_email'], $_SESSION['otp_name'], $_SESSION['otp_requested'],
          $_SESSION['mail_debug_error'], $_SESSION['dev_otp']);
}

// ── STEP 1: submit email → generate OTP ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_otp') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message     = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, name, email FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($user) {
            $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpHash = hash('sha256', $otp);

            $invalidate = mysqli_prepare($conn, "UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0");
            mysqli_stmt_bind_param($invalidate, 's', $user['email']);
            mysqli_stmt_execute($invalidate);

            $insert = mysqli_prepare($conn,
                "INSERT INTO password_resets (email, token_hash, expires_at)
                 VALUES (?, ?, NOW() + INTERVAL 10 MINUTE)");
            mysqli_stmt_bind_param($insert, 'ss', $user['email'], $otpHash);
            mysqli_stmt_execute($insert);

            send_otp_email($user['email'], $user['name'] ?? '', $otp);

            $_SESSION['otp_email'] = $user['email'];
            $_SESSION['otp_name']  = $user['name'] ?? '';
            $_SESSION['otp_requested'] = true;
            header('Location: forgot-password.php');
            exit();
        } else {
            $message     = 'This email address is not registered in our system.';
            $messageType = 'error';
        }
    }
}

// ── STEP 2: submit OTP + new password ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_otp') {
    $email     = $_SESSION['otp_email'] ?? '';
    $otpInput  = trim($_POST['otp'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password_confirm'] ?? '';

    if (!$email) {
        $message = 'Your session expired. Please request a new code.';
        $messageType = 'error';
        unset($_SESSION['otp_requested']);
    } elseif ($otpInput === '') {
        $message = 'Please enter the 6-digit code.';
        $messageType = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
        $messageType = 'error';
    } elseif (!preg_match('/[A-Za-z]/', $password)) {
        $message = 'Password must contain at least one letter.';
        $messageType = 'error';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $message = 'Password must contain at least one number.';
        $messageType = 'error';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $message = 'Password must contain at least one special character.';
        $messageType = 'error';
    } elseif ($password !== $password2) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        $otpHash = hash('sha256', $otpInput);
        $stmt = mysqli_prepare($conn,
            "SELECT id FROM password_resets
             WHERE email = ? AND token_hash = ? AND used = 0 AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $email, $otpHash);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$row) {
            $message = 'That code is invalid or has expired. Request a new one below.';
            $messageType = 'error';
        } else {
            $hashed   = password_hash($password, PASSWORD_DEFAULT);
            $update   = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
            mysqli_stmt_bind_param($update, 'ss', $hashed, $email);
            mysqli_stmt_execute($update);

            $markUsed = mysqli_prepare($conn, "UPDATE password_resets SET used = 1 WHERE email = ?");
            mysqli_stmt_bind_param($markUsed, 's', $email);
            mysqli_stmt_execute($markUsed);

            unset($_SESSION['otp_email'], $_SESSION['otp_name'],
                  $_SESSION['otp_requested'], $_SESSION['dev_otp']);
            $step = 'done';
        }
    }
}

if ($step !== 'done') {
    if (!empty($_SESSION['otp_requested']) && !empty($_SESSION['otp_email'])) {
        $step = 'verify';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <title>LifeSync – Forgot Password</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="auth-body">

    <div class="bg-dots"></div>
    <div class="bg-orb bg-orb--1"></div>
    <div class="bg-orb bg-orb--2"></div>
    <div class="bg-orb bg-orb--3"></div>
    <span class="float-leaf fl-1">🌿</span>
    <span class="float-leaf fl-2">🍃</span>
    <span class="float-leaf fl-3">🌱</span>
    <span class="float-leaf fl-4">🍀</span>

    <div class="auth-page">
        <div class="auth-container" style="max-width:440px;">

            <div class="auth-top-band" style="padding-bottom:8px;">
                <div class="auth-brand">
                    <svg class="auth-brand__logo" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M24 44C24 44 12 36 12 23C12 14 17 8 24 6C31 8 36 14 36 23C36 36 24 44 24 44Z" fill="#3F8C3F"/>
                        <path d="M24 6C24 6 26 16 24 26C22 16 24 6 24 6Z" fill="#6BB04F" opacity="0.7"/>
                        <circle cx="24" cy="10" r="6" fill="#5B4FCF"/>
                    </svg>
                    <span class="auth-brand__name"><span class="life-part">Life</span><span class="sync-part">Sync</span></span>
                </div>
                <p class="auth-tagline">Sync your life. Live better.</p>
            </div>

            <div class="auth-form-area">

                <?php if ($step === 'request'): ?>

                    <h1 class="auth-title">Forgot password? <span class="auth-title__leaf">🔑</span></h1>
                    <p class="auth-subtitle">Enter your email and we'll send you a 6-digit code.</p>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form" autocomplete="off">
                        <!-- Dummy hidden inputs to prevent Google Chrome/Edge aggressive autofilling -->
                        <input type="text" style="display:none;" autocomplete="false">
                        <input type="password" style="display:none;" autocomplete="false">
                        <input type="hidden" name="action" value="request_otp">
                        <div class="form-group">
                            <label class="form-label">Email address</label>
                            <div class="input-wrap">
                                <span class="input-wrap__icon">
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 3h14v10H1z"/><path d="M1 4l7 5 7-5"/></svg>
                                </span>
                                <input type="email" name="email" class="form-input" placeholder="you@email.com" required autocomplete="off">
                            </div>
                        </div>
                        <button type="submit" class="btn-login">
                            Send code
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
                        </button>
                    </form>

                <?php elseif ($step === 'verify'): ?>

                    <h1 class="auth-title">Enter your code <span class="auth-title__leaf">📩</span></h1>
                    <p class="auth-subtitle">
                        We sent a 6-digit code to <strong><?php echo htmlspecialchars($_SESSION['otp_email']); ?></strong>.
                        It expires in 10 minutes.
                    </p>

                    <?php if (!empty($_SESSION['dev_otp'])): ?>
                        <div class="alert" style="background:#fff8e1;border:2px dashed #f59e0b;
                             color:#92400e;border-radius:10px;padding:12px 16px;
                             font-size:15px;text-align:center;margin-bottom:16px;">
                            🛠️ <strong>Dev mode — your OTP is:
                            <span style="font-size:22px;letter-spacing:6px;">
                                <?php echo htmlspecialchars($_SESSION['dev_otp']); ?>
                            </span></strong><br>
                            <small>(This box won't appear on a live server)</small>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['mail_debug_error'])): ?>
                        <div class="alert alert-error" style="background:#fff5f5; border:1px solid rgba(229, 62, 62, 0.2);
                             color:#e53e3e; border-radius:10px; padding:12px 16px;
                             font-size:14px; margin-bottom:16px;">
                            ✉️ <strong>Failed to dispatch OTP email:</strong><br>
                            <span style="font-size:12px; font-family: monospace; display:block; margin-top:4px; opacity:0.8; word-break:break-all;">
                                <?php echo htmlspecialchars($_SESSION['mail_debug_error']); ?>
                            </span>
                            <div style="margin-top:8px; font-size:12px;">
                                <a href="forgot-password.php?restart=1" style="color:#5B4FCF; font-weight:600; text-decoration:underline;">Try again</a>
                                or check your configuration.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form" autocomplete="off">
                        <!-- Dummy hidden inputs to prevent Google Chrome/Edge aggressive autofilling -->
                        <input type="text" style="display:none;" autocomplete="false">
                        <input type="password" style="display:none;" autocomplete="false">
                        <input type="hidden" name="action" value="verify_otp">

                        <div class="form-group">
                            <label class="form-label">6-digit code</label>
                            <div class="input-wrap">
                                <span class="input-wrap__icon">
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="4" width="12" height="9" rx="2"/><path d="M2 5l6 4 6-4"/></svg>
                                </span>
                                <input type="text" name="otp" class="form-input" placeholder="123456"
                                       inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required
                                       style="letter-spacing:6px;font-weight:700;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">New password</label>
                            <div class="input-wrap">
                                <span class="input-wrap__icon">
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="7" width="10" height="7" rx="2"/><path d="M5 7V5a3 3 0 0 1 6 0v2"/></svg>
                                </span>
                                <input type="password" name="password" class="form-input"
                                       placeholder="Min 8 chars, letter, number & special char" required minlength="8" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm new password</label>
                            <div class="input-wrap">
                                <span class="input-wrap__icon">
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="7" width="10" height="7" rx="2"/><path d="M5 7V5a3 3 0 0 1 6 0v2"/></svg>
                                </span>
                                <input type="password" name="password_confirm" class="form-input"
                                       placeholder="Re-enter password" required minlength="8" autocomplete="new-password">
                            </div>
                        </div>

                        <button type="submit" class="btn-login">
                            Reset password
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
                        </button>
                    </form>

                    <p class="auth-switch" style="margin-top:16px;">
                        Wrong email? <a href="forgot-password.php?restart=1">Use a different one</a>
                    </p>

                <?php elseif ($step === 'done'): ?>

                    <h1 class="auth-title">Password updated <span class="auth-title__leaf">✅</span></h1>
                    <p class="auth-subtitle">You can now log in with your new password.</p>
                    <a href="index.php" class="btn-login" style="text-decoration:none;margin-top:8px;">
                        Back to login
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
                    </a>

                <?php endif; ?>

                <?php if ($step !== 'done'): ?>
                    <p class="auth-switch" style="margin-top:18px;">Remembered it? <a href="index.php">Back to login</a></p>
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>
</html>