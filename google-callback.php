<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Verify Google configuration variables are defined
if (!defined('GOOGLE_CLIENT_ID') || !defined('GOOGLE_CLIENT_SECRET') || !defined('GOOGLE_REDIRECT_URI')) {
    $_SESSION['error'] = "Google configuration constants are missing.";
    header("Location: index.php");
    exit();
}

if (!isset($_GET['code'])) {
    $_SESSION['error'] = "Authorization code from Google was not received.";
    header("Location: index.php");
    exit();
}

$code = $_GET['code'];

// 1. Exchange the code for an Access Token
$token_url = "https://oauth2.googleapis.com/token";
$post_fields = [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL verification for local dev environments (XAMPP lack of CA bundle)
$response = curl_exec($ch);

if (curl_errno($ch)) {
    $_SESSION['error'] = "Token Exchange Request Error: " . curl_error($ch);
    curl_close($ch);
    header("Location: index.php");
    exit();
}
curl_close($ch);

$token_data = json_decode($response, true);
if (isset($token_data['error'])) {
    $_SESSION['error'] = "Google Token Error: " . htmlspecialchars($token_data['error_description'] ?? $token_data['error']);
    header("Location: index.php");
    exit();
}

if (!isset($token_data['access_token'])) {
    $_SESSION['error'] = "Failed to retrieve access token from Google.";
    header("Location: index.php");
    exit();
}

$access_token = $token_data['access_token'];

// 2. Retrieve user information from Google APIs using the access token
$userinfo_url = "https://www.googleapis.com/oauth2/v3/userinfo?access_token=" . urlencode($access_token);
$ch = curl_init($userinfo_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL verification for local dev environments
$response = curl_exec($ch);

if (curl_errno($ch)) {
    $_SESSION['error'] = "Profile Retrieve Request Error: " . curl_error($ch);
    curl_close($ch);
    header("Location: index.php");
    exit();
}
curl_close($ch);

$profile = json_decode($response, true);
if (!isset($profile['email'])) {
    $_SESSION['error'] = "Failed to retrieve email address from Google Profile.";
    header("Location: index.php");
    exit();
}

$email = mysqli_real_escape_string($conn, $profile['email']);
$name  = mysqli_real_escape_string($conn, $profile['name'] ?? $profile['given_name'] ?? 'Google User');

// 3. Check if user already exists in the database
$stmt = mysqli_prepare($conn, "SELECT id, name FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user) {
    // User exists - sign them in
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    header("Location: dashboard.php");
    exit();
} else {
    // User does not exist - sign them up
    // Generate a secure random password since they login via Google
    $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($insert_stmt, 'sss', $name, $email, $random_password);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $_SESSION['user_id']   = mysqli_insert_id($conn);
        $_SESSION['user_name'] = $name;
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Database registration error. Please try again.";
        header("Location: index.php");
        exit();
    }
}
?>
