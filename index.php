<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$error = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

$google_login_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'email profile',
    'state'         => 'google_login'
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <title>LifeSync – Login</title>
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
    <span class="float-leaf fl-5">🍃</span>
    <span class="float-leaf fl-6">🌿</span>

    <div class="auth-page">

        <!-- Left feature rail -->
        <div class="feature-rail">
            <div class="feature-card">
                <div class="feature-card__icon">🥗</div>
                <div class="feature-card__title">Food</div>
                <div class="feature-card__desc">Eat healthy,<br>stay strong.</div>
                <span class="feature-card__dot"></span>
            </div>
            <div class="feature-card">
                <div class="feature-card__icon">👛</div>
                <div class="feature-card__title">Expense</div>
                <div class="feature-card__desc">Track more,<br>worry less.</div>
                <span class="feature-card__dot"></span>
            </div>
            <div class="feature-card">
                <div class="feature-card__icon">🧘‍♀️</div>
                <div class="feature-card__title">Habit</div>
                <div class="feature-card__desc">Small steps,<br>big changes.</div>
                <span class="feature-card__dot"></span>
            </div>
            <div class="feature-card">
                <div class="feature-card__icon">📔</div>
                <div class="feature-card__title">Digital Diary</div>
                <div class="feature-card__desc">Write it down,<br>clear your mind.</div>
                <span class="feature-card__dot"></span>
            </div>
        </div>

        <!-- Center auth card -->
        <div class="auth-container">

            <div class="auth-top-band">
                <div class="auth-brand">
                    <svg class="auth-brand__logo" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M24 44C24 44 12 36 12 23C12 14 17 8 24 6C31 8 36 14 36 23C36 36 24 44 24 44Z" fill="#a78bfa"/>
                        <path d="M24 6C24 6 26 16 24 26C22 16 24 6 24 6Z" fill="#c4b5fd" opacity="0.8"/>
                        <circle cx="24" cy="10" r="6" fill="#f59e0b"/>
                    </svg>
                    <span class="auth-brand__name"><span class="life-part">Life</span><span class="sync-part">Sync</span></span>
                </div>
                <p class="auth-tagline">Sync your life. Live better.</p>

                <div class="auth-illustration">
                    <img src="assets/images/couple-illustration.png" alt="People living a balanced, synced life">
                </div>
            </div>

            <div class="auth-form-area">
                <h1 class="auth-title">Welcome back <span class="auth-title__leaf">✨</span></h1>
                <p class="auth-subtitle">Glad to see you again!</p>

                <span class="auth-badge">PERSONAL LIFE MANAGEMENT</span>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" class="auth-form" autocomplete="off">
                    <!-- Dummy hidden inputs to prevent Google Chrome/Edge aggressive autofilling -->
                    <input type="text" style="display:none;" autocomplete="false">
                    <input type="password" style="display:none;" autocomplete="false">

                    <div class="form-group">
                        <label class="form-label">Email address</label>
                        <div class="input-wrap">
                            <span class="input-wrap__icon">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 3h14v10H1z"/><path d="M1 4l7 5 7-5"/></svg>
                            </span>
                            <input type="email" name="email" class="form-input" placeholder="you@email.com" required autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrap">
                            <span class="input-wrap__icon">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="7" width="10" height="7" rx="2"/><path d="M5 7V5a3 3 0 0 1 6 0v2"/></svg>
                            </span>
                            <input type="password" name="password" id="passwordField" class="form-input" placeholder="••••••••" required autocomplete="new-password">
                            <button type="button" class="input-wrap__toggle" id="togglePassword" aria-label="Show password">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z"/><circle cx="8" cy="8" r="2"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-forgot">
                        <a href="forgot-password.php">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn-login">
                        Log in to LifeSync
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
                    </button>
                </form>

                <div class="auth-divider">or continue with</div>

                <div class="social-row">
                    <a href="<?php echo htmlspecialchars($google_login_url); ?>" class="btn-social btn-social--full">
                        <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M23.04 12.27c0-.79-.07-1.54-.2-2.27H12v4.3h6.18a5.28 5.28 0 0 1-2.29 3.47v2.88h3.7c2.17-2 3.42-4.93 3.42-8.38z"/><path fill="#34A853" d="M12 24c3.1 0 5.7-1.03 7.6-2.79l-3.7-2.88c-1.03.69-2.35 1.1-3.9 1.1-3 0-5.54-2.02-6.45-4.74H1.74v2.97A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.55 14.69A7.2 7.2 0 0 1 5.18 12c0-.93.16-1.84.37-2.69V6.34H1.74A12 12 0 0 0 0 12c0 1.94.46 3.78 1.74 5.66l3.81-2.97z"/><path fill="#EA4335" d="M12 4.77c1.68 0 3.19.58 4.38 1.71l3.29-3.29C17.7 1.19 15.1 0 12 0 7.31 0 3.26 2.69 1.74 6.34l3.81 2.97C6.46 6.79 9 4.77 12 4.77z"/></svg>
                        Continue with Google
                    </a>
                </div>

                <p class="auth-switch">Don't have an account? <a href="register.php">Register here</a></p>
            </div>

        </div>

        <!-- Right feature rail -->
        <div class="feature-rail">
            <div class="feature-card">
                <div class="feature-card__icon">📖</div>
                <div class="feature-card__title">Reading</div>
                <div class="feature-card__desc">Read more,<br>grow more.</div>
                <span class="feature-card__dot"></span>
            </div>
            <div class="feature-card">
                <div class="feature-card__icon">🌙</div>
                <div class="feature-card__title">Sleep</div>
                <div class="feature-card__desc">Rest well,<br>rise better.</div>
                <span class="feature-card__dot"></span>
            </div>
            <div class="feature-card">
                <div class="feature-card__icon">🏋️‍♂️</div>
                <div class="feature-card__title">Workout</div>
                <div class="feature-card__desc">Move daily,<br>feel amazing.</div>
                <span class="feature-card__dot"></span>
            </div>
            <div class="feature-card">
                <div class="feature-card__icon">🌱</div>
                <div class="feature-card__title">More to come</div>
                <div class="feature-card__desc">New features<br>coming soon.</div>
                <span class="feature-card__dot"></span>
            </div>
        </div>

    </div>

    <div class="auth-dots">
        <span></span><span></span><span class="active"></span><span></span><span></span>
    </div>

    <script>
        var toggleBtn = document.getElementById('togglePassword');
        var pwdField  = document.getElementById('passwordField');
        if (toggleBtn && pwdField) {
            toggleBtn.addEventListener('click', function () {
                var isHidden = pwdField.getAttribute('type') === 'password';
                pwdField.setAttribute('type', isHidden ? 'text' : 'password');
                toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
        }
    </script>

</body>
</html>