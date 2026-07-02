<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "lifesync";

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = mysqli_connect($host, $user, $pass, $db);
} catch (mysqli_sql_exception $e) {
    die("
    <div style='font-family: \"Plus Jakarta Sans\", \"Segoe UI\", Arial, sans-serif; max-width: 600px; margin: 80px auto; padding: 40px; background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(229, 62, 62, 0.2); border-radius: 20px; text-align: center; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); backdrop-filter: blur(10px);'>
        <div style='width: 72px; height: 72px; background: #FFF5F5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px auto;'>
            <svg style='width: 36px; height: 36px; color: #E53E3E;' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'>
                <path stroke-linecap='round' stroke-linejoin='round' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' />
            </svg>
        </div>
        <h2 style='color: #2D3748; font-size: 24px; font-weight: 700; margin: 0 0 12px 0;'>Database Connection Offline</h2>
        <p style='color: #718096; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0;'>We could not establish a connection to the local database server. Please ensure that the <strong>MySQL</strong> service is started in your XAMPP Control Panel.</p>
        <div style='background: #F7FAFC; border: 1px solid #EDF2F7; border-radius: 12px; padding: 16px; text-align: left; margin-bottom: 24px;'>
            <div style='font-size: 12px; text-transform: uppercase; color: #A0AEC0; font-weight: 700; margin-bottom: 8px; letter-spacing: 0.5px;'>Error Details</div>
            <code style='color: #E53E3E; font-size: 13px; font-family: Consolas, monospace; word-break: break-all;'>" . htmlspecialchars($e->getMessage()) . "</code>
        </div>
        <button onclick='window.location.reload()' style='display: inline-flex; align-items: center; justify-content: center; padding: 12px 28px; background: #3F8C3F; color: #ffffff; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.2s ease-in-out; box-shadow: 0 4px 6px -1px rgba(63, 140, 63, 0.2);' onmouseover='this.style.background=\"#347534\"; this.style.transform=\"translateY(-1px)\";' onmouseout='this.style.background=\"#3F8C3F\"; this.style.transform=\"translateY(0)\";'>
            <svg style='width: 18px; height: 18px; margin-right: 8px;' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'>
                <path stroke-linecap='round' stroke-linejoin='round' d='M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18' />
            </svg>
            Retry Connection
        </button>
    </div>
    ");
}
?>
