<?php
session_start();
require_once 'includes/db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = mysqli_real_escape_string($conn, $_POST['name']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $password_raw = $_POST['password'];

    if (strlen($password_raw) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Za-z]/', $password_raw)) {
        $error = "Password must contain at least one letter.";
    } elseif (!preg_match('/[0-9]/', $password_raw)) {
        $error = "Password must contain at least one number.";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password_raw)) {
        $error = "Password must contain at least one special character.";
    } else {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered.";
        } else {
            $insert = mysqli_query($conn, "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')");
            if ($insert) {
                $success = "Account created! <a href='index.php'>Login now</a>";
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
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
    <title>LifeSync – Register</title>
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

                <!-- Register illustration — star/sparkle cluster -->
                <div class="auth-illustration reg-illustration">
                    <svg viewBox="0 0 340 130" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Three floating feature bubbles -->
                        <rect x="12" y="28" width="90" height="78" rx="20" fill="rgba(167,139,250,0.12)" stroke="rgba(167,139,250,0.28)" stroke-width="1"/>
                        <text x="57" y="62" text-anchor="middle" font-size="28">🌱</text>
                        <text x="57" y="84" text-anchor="middle" font-family="'Plus Jakarta Sans',sans-serif" font-size="11" fill="#c4b5fd" font-weight="600">Start fresh</text>
                        <text x="57" y="99" text-anchor="middle" font-family="'Plus Jakarta Sans',sans-serif" font-size="9.5" fill="rgba(196,181,253,0.55)">Track everything</text>

                        <rect x="125" y="10" width="90" height="78" rx="20" fill="rgba(245,158,11,0.10)" stroke="rgba(245,158,11,0.28)" stroke-width="1"/>
                        <text x="170" y="44" text-anchor="middle" font-size="28">✨</text>
                        <text x="170" y="66" text-anchor="middle" font-family="'Plus Jakarta Sans',sans-serif" font-size="11" fill="#fbbf24" font-weight="600">Build habits</text>
                        <text x="170" y="81" text-anchor="middle" font-family="'Plus Jakarta Sans',sans-serif" font-size="9.5" fill="rgba(251,191,36,0.55)">Grow daily</text>

                        <rect x="238" y="28" width="90" height="78" rx="20" fill="rgba(167,139,250,0.12)" stroke="rgba(167,139,250,0.28)" stroke-width="1"/>
                        <text x="283" y="62" text-anchor="middle" font-size="28">💜</text>
                        <text x="283" y="84" text-anchor="middle" font-family="'Plus Jakarta Sans',sans-serif" font-size="11" fill="#c4b5fd" font-weight="600">Live better</text>
                        <text x="283" y="99" text-anchor="middle" font-family="'Plus Jakarta Sans',sans-serif" font-size="9.5" fill="rgba(196,181,253,0.55)">Feel amazing</text>

                        <!-- Connecting dots -->
                        <circle cx="107" cy="67" r="3" fill="rgba(245,158,11,0.45)"/>
                        <circle cx="220" cy="67" r="3" fill="rgba(245,158,11,0.45)"/>
                        <line x1="107" y1="67" x2="125" y2="67" stroke="rgba(245,158,11,0.25)" stroke-width="1" stroke-dasharray="3 3"/>
                        <line x1="215" y1="67" x2="238" y2="67" stroke="rgba(245,158,11,0.25)" stroke-width="1" stroke-dasharray="3 3"/>
                    </svg>
                </div>
            </div>

            <div class="auth-form-area">
                <h1 class="auth-title">Create account <span class="auth-title__leaf">✨</span></h1>
                <p class="auth-subtitle">Join LifeSync and start your journey</p>

                <span class="auth-badge">FREE FOREVER · NO CREDIT CARD</span>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" class="auth-form" autocomplete="off">
                    <!-- Dummy hidden inputs to prevent Google Chrome/Edge aggressive autofilling -->
                    <input type="text" style="display:none;" autocomplete="false">
                    <input type="password" style="display:none;" autocomplete="false">

                    <div class="form-group">
                        <label class="form-label">Full name</label>
                        <div class="input-wrap">
                            <span class="input-wrap__icon">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6"/></svg>
                            </span>
                            <input type="text" name="name" class="form-input" placeholder="Your full name" required autocomplete="off">
                        </div>
                    </div>
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
                            <input type="password" name="password" id="passwordField" class="form-input" placeholder="Min 8 chars, letter, number & special char" minlength="8" required autocomplete="new-password">
                            <button type="button" class="input-wrap__toggle" id="togglePassword" aria-label="Show password">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z"/><circle cx="8" cy="8" r="2"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Password strength bar -->
                    <div class="pwd-strength" id="pwdStrength" style="display:none;">
                        <div class="pwd-strength__bar">
                            <div class="pwd-strength__fill" id="pwdFill"></div>
                        </div>
                        <span class="pwd-strength__label" id="pwdLabel"></span>
                    </div>

                    <button type="submit" class="btn-login" style="margin-top:18px;">
                        Create my account
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
                    </button>
                </form>

                <div class="reg-perks">
                    <span class="reg-perk">🔒 Secure &amp; private</span>
                    <span class="reg-perk">⚡ Ready in seconds</span>
                    <span class="reg-perk">💜 Free forever</span>
                </div>

                <p class="auth-switch">Already have an account? <a href="index.php">Log in</a></p>
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
        <span></span><span class="active"></span><span></span><span></span><span></span>
    </div>

    <script>
        // Password show/hide toggle
        var toggleBtn = document.getElementById('togglePassword');
        var pwdField  = document.getElementById('passwordField');
        if (toggleBtn && pwdField) {
            toggleBtn.addEventListener('click', function () {
                var isHidden = pwdField.getAttribute('type') === 'password';
                pwdField.setAttribute('type', isHidden ? 'text' : 'password');
                toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
        }

        // Password strength meter
        var pwdStrength = document.getElementById('pwdStrength');
        var pwdFill     = document.getElementById('pwdFill');
        var pwdLabel    = document.getElementById('pwdLabel');
        var colors      = ['#e24b4a','#f59e0b','#a78bfa','#10b981'];
        var labels      = ['Weak','Fair','Good','Strong'];

        function getStrength(pw) {
            var score = 0;
            if (pw.length >= 6)  score++;
            if (pw.length >= 10) score++;
            if (/[A-Z]/.test(pw) && /[0-9]/.test(pw)) score++;
            if (/[^A-Za-z0-9]/.test(pw)) score++;
            return Math.min(score, 3);
        }

        if (pwdField) {
            pwdField.addEventListener('input', function () {
                var val = pwdField.value;
                if (val.length === 0) {
                    pwdStrength.style.display = 'none';
                    return;
                }
                pwdStrength.style.display = 'flex';
                var s = getStrength(val);
                pwdFill.style.width  = ((s + 1) * 25) + '%';
                pwdFill.style.background = colors[s];
                pwdLabel.textContent = labels[s];
                pwdLabel.style.color = colors[s];
            });
        }
    </script>

</body>
</html>