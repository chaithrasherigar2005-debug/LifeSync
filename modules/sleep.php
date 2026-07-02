<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../includes/auth.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sleep'])) {
    $sleep_time = mysqli_real_escape_string($conn, $_POST['sleep_time']);
    $wake_time  = mysqli_real_escape_string($conn, $_POST['wake_time']);
    $quality    = mysqli_real_escape_string($conn, $_POST['quality']);
    $date       = mysqli_real_escape_string($conn, $_POST['date']);

    // Calculate hours slept
    $s = strtotime($date . ' ' . $sleep_time);
    $w = strtotime($date . ' ' . $wake_time);
    if ($w <= $s) $w += 86400; // next day wake up
    $hours = round(($w - $s) / 3600, 2);

    mysqli_query($conn, "INSERT INTO sleep_logs (user_id, sleep_time, wake_time, hours_slept, quality, date)
        VALUES ($user_id, '$sleep_time', '$wake_time', $hours, '$quality', '$date')");
    $msg = "✅ Sleep logged! ($hours hours)";
}

// Fetch last 7 days sleep
$week_labels = [];
$week_hours  = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $week_labels[] = date('D', strtotime($d));
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT hours_slept FROM sleep_logs WHERE user_id=$user_id AND date='$d' ORDER BY id DESC LIMIT 1"));
    $week_hours[] = $r ? floatval($r['hours_slept']) : 0;
}

$recent = mysqli_query($conn, "SELECT * FROM sleep_logs WHERE user_id=$user_id ORDER BY date DESC LIMIT 7");

// Stats: average over last 7 nights logged, best night, last night quality
$avg_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(hours_slept) as a FROM (SELECT hours_slept FROM sleep_logs WHERE user_id=$user_id ORDER BY date DESC LIMIT 7) t"));
$avg_sleep = $avg_row['a'] ? round($avg_row['a'], 1) : 0;

$last_sleep = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sleep_logs WHERE user_id=$user_id ORDER BY id DESC LIMIT 1"));

$nights_logged = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM sleep_logs WHERE user_id=$user_id"))['c'] ?? 0;

// Smart suggestion
$suggestion = '';
if ($last_sleep) {
    if ($last_sleep['hours_slept'] < 6) {
        $suggestion = "⚠️ You slept less than 6 hours! Try to sleep earlier tonight — poor sleep reduces productivity.";
    } elseif ($last_sleep['hours_slept'] >= 7 && $last_sleep['hours_slept'] <= 9) {
        $suggestion = "✅ Great sleep! You're in the optimal range (7–9 hours).";
    } elseif ($last_sleep['hours_slept'] > 9) {
        $suggestion = "💤 You may be oversleeping. Aim for 7–9 hours for best productivity.";
    }
}

include '../includes/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── LifeSync Sleep Tracker — Midnight Violet × Gold ── */
.sl-wrap { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(160deg, #f8f6ff 0%, #f1ecfe 100%); min-height: 100vh; padding-bottom: 40px; color: #1e0f4a; position: relative; overflow-x: hidden; }

/* Floating mascots */
.mascot-bg{position:fixed;z-index:0;pointer-events:none;filter:drop-shadow(0 4px 10px rgba(124,58,237,0.18));}
.mascot-bg--1{top:8%;left:3%;font-size:46px;opacity:0.30;animation:mascotFloat 7s ease-in-out infinite;}
.mascot-bg--2{bottom:6%;right:3%;font-size:42px;opacity:0.28;animation:mascotFloat 8s ease-in-out infinite 1.2s;}
.mascot-bg--3{top:22%;right:2%;font-size:36px;opacity:0.24;animation:mascotFloat 9s ease-in-out infinite .6s;}
.mascot-bg--4{top:62%;left:1.5%;font-size:40px;opacity:0.26;animation:mascotFloat 7.5s ease-in-out infinite 2s;}
.mascot-bg--5{bottom:38%;right:4%;font-size:30px;opacity:0.22;animation:mascotFloat 8.5s ease-in-out infinite 1.6s;}
.mascot-bg--6{top:2%;right:14%;font-size:28px;opacity:0.22;animation:mascotFloat 6.5s ease-in-out infinite .9s;}
.mascot-bg--7{bottom:2%;left:12%;font-size:32px;opacity:0.22;animation:mascotFloat 9.5s ease-in-out infinite 2.4s;}
.mascot-bg--8{top:84%;right:16%;font-size:26px;opacity:0.20;animation:mascotFloat 7.2s ease-in-out infinite 1.1s;}

@keyframes mascotFloat{0%,100%{transform:translateY(0) rotate(-4deg);}50%{transform:translateY(-22px) rotate(4deg);}}

/* Header */
.sl-hdr { position:relative; background: linear-gradient(125deg,#1e0f4a 0%, #2d1472 60%, #4c1d95 100%); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; border-radius: 0 0 24px 24px; overflow: hidden; }
.sl-hdr::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,transparent 0%,#f59e0b 30%,#fbbf24 50%,#f59e0b 70%,transparent 100%); }
.sl-hdr-left { display: flex; align-items: center; gap: 14px; }
.sl-hdr-ico { width: 44px; height: 44px; background: #7c3aed; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.sl-hdr h2 { font-family: 'Fredoka', sans-serif; font-size: 22px; color: #fff; margin: 0; line-height: 1; }
.sl-hdr p  { font-size: 12px; color: #c4b5fd; margin: 2px 0 0; }
.sl-avatar { width: 36px; height: 36px; background: #4c1d95; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #c4b5fd; font-size: 14px; font-weight: 700; border: 2px solid #a78bfa44; }

/* Alerts */
.sl-alert { margin: 14px 20px 0; padding: 10px 16px; border-radius: 0 10px 10px 0; font-size: 13px; border-left: 4px solid #7c3aed; background: #f3eeff; color: #1e0f4a; }
.sl-alert.info { border-left-color: #818cf8; background: #eef2ff; color: #4338ca; }

/* Stat cards */
.sl-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; padding: 18px 20px 0; }
.sl-sc { background: #fff; border-radius: 16px; padding: 16px; border: 1.5px solid #e4d9ff; position: relative; overflow: hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.sl-sc::before { content:''; position:absolute; top:-18px; right:-18px; width:64px; height:64px; border-radius:50%; opacity:.12; }
.sl-sc.b::before { background:#7c3aed; }
.sl-sc.g::before { background:#16a34a; }
.sl-sc.o::before { background:#f59e0b; }
.sl-sc.c::before { background:#0ea5e9; }
.sl-sc-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.sl-sc-ico { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; }
.sl-sc.b .sl-sc-ico { background:#f3eeff; }
.sl-sc.g .sl-sc-ico { background:#e9f8ef; }
.sl-sc.o .sl-sc-ico { background:#fffbeb; }
.sl-sc.c .sl-sc-ico { background:#e0f2fe; }
.sl-sc-delta { font-size:11px; font-weight:700; padding:3px 7px; border-radius:20px; }
.sl-sc.b .sl-sc-delta { background:#f3eeff; color:#7c3aed; }
.sl-sc.g .sl-sc-delta { background:#e9f8ef; color:#16a34a; }
.sl-sc.o .sl-sc-delta { background:#fffbeb; color:#d97706; }
.sl-sc.c .sl-sc-delta { background:#e0f2fe; color:#0369a1; }
.sl-sc-val { font-family:'Fredoka',sans-serif; font-size:22px; color:#1e0f4a; line-height:1; }
.sl-sc-lbl { font-size:11px; color:#7c6fa0; margin-top:3px; text-transform:uppercase; letter-spacing:.5px; }

/* Body grid */
.sl-body { display:grid; grid-template-columns:1fr 1fr; gap:14px; padding:16px 20px 0; }
.sl-card { background:#fff; border-radius:18px; border:1.5px solid #e4d9ff; padding:20px; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.sl-card-title { font-family:'Fredoka',sans-serif; font-size:16px; color:#1e0f4a; display:flex; align-items:center; gap:8px; margin-bottom:16px; }

/* Form elements */
.sl-fg { margin-bottom:11px; }
.sl-lbl { display:block; font-size:11px; font-weight:700; color:#3b1f6a; text-transform:uppercase; letter-spacing:.7px; margin-bottom:4px; }
.sl-inp, .sl-sel { width:100%; padding:9px 12px; background:#f3eeff; border:1.5px solid #e4d9ff; border-radius:10px; font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; color:#1e0f4a; outline:none; transition:border-color .15s, background .15s; box-sizing:border-box; }
.sl-inp:focus, .sl-sel:focus { border-color:#7c3aed; background:#fff; box-shadow:0 0 0 4px rgba(124,58,237,0.10); }

.sl-btn-p { width:100%; padding:11px; border:none; border-radius:12px; font-size:14px; font-weight:700; font-family:'Plus Jakarta Sans',sans-serif; cursor:pointer; background:linear-gradient(120deg,#4c1d95 0%, #7c3aed 60%, #f59e0b 130%); color:#fff; margin-top:2px; box-shadow: 0 10px 22px rgba(124,58,237,0.28); transition:all .15s; }
.sl-btn-p:hover { transform: translateY(-1px); box-shadow: 0 14px 28px rgba(124,58,237,0.36); }

/* Recent logs table */
.sl-tbl-wrap { padding:14px 20px 0; }
.sl-tbl-card { background:#fff; border-radius:18px; border:1.5px solid #e4d9ff; overflow:hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.sl-tbl-hdr  { padding:14px 20px; background:linear-gradient(125deg,#1e0f4a 0%, #2d1472 100%); }
.sl-tbl-hdr h3 { font-family:'Fredoka',sans-serif; font-size:16px; color:#fff; margin:0; }

table.sl-dt { width:100%; border-collapse:collapse; }
.sl-dt thead th { padding:9px 16px; font-size:10.5px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.5px; text-align:left; background:#faf8ff; border-bottom:1.5px solid #e4d9ff; }
.sl-dt tbody td { padding:11px 16px; font-size:13px; border-bottom:1px solid #f1edfb; color:#2e2253; }
.sl-dt tbody tr:last-child td { border-bottom:none; }
.sl-dt tbody tr:hover { background:#faf8ff; }

.sl-badge { display:inline-block; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; }
.sl-badge.excellent { background:#eaf3de; color:#27500A; }
.sl-badge.good { background:#f3eeff; color:#4c2f9e; }
.sl-badge.average { background:#fffbeb; color:#92400e; }
.sl-badge.poor { background:#fee2e2; color:#b91c1c; }
.sl-empty { text-align:center; padding:36px 20px; color:#9d87c0; font-size:13px; }
</style>

<div class="sl-wrap">

<span class="mascot-bg mascot-bg--1">😴</span>
<span class="mascot-bg mascot-bg--2">🌙</span>
<span class="mascot-bg mascot-bg--3">⭐</span>
<span class="mascot-bg mascot-bg--4">💤</span>
<span class="mascot-bg mascot-bg--5">🛏️</span>
<span class="mascot-bg mascot-bg--6">✨</span>
<span class="mascot-bg mascot-bg--7">☁️</span>
<span class="mascot-bg mascot-bg--8">🌛</span>

    <!-- Header -->
    <div class="sl-hdr">
        <div class="sl-hdr-left">
            <div class="sl-hdr-ico">😴</div>
            <div>
                <h2>Sleep Tracker</h2>
                <p><?php echo date('l, d F Y'); ?></p>
            </div>
        </div>
        <div class="sl-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
    </div>

    <?php if ($msg): ?>
    <div class="sl-alert"><?php echo $msg; ?></div>
    <?php endif; ?>
    <?php if ($suggestion): ?>
    <div class="sl-alert info"><?php echo $suggestion; ?></div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="sl-stats">
        <div class="sl-sc b">
            <div class="sl-sc-top">
                <div class="sl-sc-ico">🌙</div>
                <span class="sl-sc-delta">Last night</span>
            </div>
            <div class="sl-sc-val"><?php echo $last_sleep ? $last_sleep['hours_slept'] . 'h' : '—'; ?></div>
            <div class="sl-sc-lbl">Hours Slept</div>
        </div>
        <div class="sl-sc c">
            <div class="sl-sc-top">
                <div class="sl-sc-ico">📊</div>
                <span class="sl-sc-delta">7-night</span>
            </div>
            <div class="sl-sc-val"><?php echo $avg_sleep > 0 ? $avg_sleep . 'h' : '—'; ?></div>
            <div class="sl-sc-lbl">Average Sleep</div>
        </div>
        <div class="sl-sc o">
            <div class="sl-sc-top">
                <div class="sl-sc-ico">😊</div>
                <span class="sl-sc-delta">Last night</span>
            </div>
            <div class="sl-sc-val"><?php echo $last_sleep ? ucfirst($last_sleep['quality']) : '—'; ?></div>
            <div class="sl-sc-lbl">Sleep Quality</div>
        </div>
        <div class="sl-sc g">
            <div class="sl-sc-top">
                <div class="sl-sc-ico">📝</div>
                <span class="sl-sc-delta">All-time</span>
            </div>
            <div class="sl-sc-val"><?php echo $nights_logged; ?></div>
            <div class="sl-sc-lbl">Nights Logged</div>
        </div>
    </div>

    <!-- Log Sleep + Weekly Chart -->
    <div class="sl-body">

        <div class="sl-card">
            <div class="sl-card-title">🌙 Log Sleep</div>
            <form method="POST">
                <div class="sl-fg">
                    <label class="sl-lbl">Sleep Time</label>
                    <input type="time" name="sleep_time" class="sl-inp" value="22:30" required>
                </div>
                <div class="sl-fg">
                    <label class="sl-lbl">Wake Up Time</label>
                    <input type="time" name="wake_time" class="sl-inp" value="06:30" required>
                </div>
                <div class="sl-fg">
                    <label class="sl-lbl">Sleep Quality</label>
                    <select name="quality" class="sl-sel">
                        <option value="excellent">Excellent 😄</option>
                        <option value="good" selected>Good 🙂</option>
                        <option value="average">Average 😐</option>
                        <option value="poor">Poor 😞</option>
                    </select>
                </div>
                <div class="sl-fg">
                    <label class="sl-lbl">Date (night of)</label>
                    <input type="date" name="date" class="sl-inp" value="<?php echo $today; ?>" required>
                </div>
                <button type="submit" name="add_sleep" class="sl-btn-p">Log Sleep</button>
            </form>
        </div>

        <div class="sl-card">
            <div class="sl-card-title">📈 Weekly Sleep (hours)</div>
            <canvas id="sleepChart" height="200"></canvas>
            <script>
            new Chart(document.getElementById('sleepChart'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($week_labels); ?>,
                    datasets: [{
                        label: 'Hours Slept',
                        data: <?php echo json_encode($week_hours); ?>,
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(167,139,250,0.15)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#7c3aed'
                    }, {
                        label: 'Optimal (8h)',
                        data: [8,8,8,8,8,8,8],
                        borderColor: '#f59e0b',
                        borderDash: [5,5],
                        pointRadius: 0
                    }]
                },
                options: {
                    scales: {
                        y: { min: 0, max: 12, ticks: { color: '#7c6fa0' }, grid: { color: 'rgba(124,58,237,0.06)' } },
                        x: { ticks: { color: '#7c6fa0' }, grid: { display: false } }
                    },
                    plugins: { legend: { labels: { color: '#3b1f6a' } } }
                }
            });
            </script>
        </div>
    </div>

    <!-- Recent Sleep Logs -->
    <div class="sl-tbl-wrap">
        <div class="sl-tbl-card">
            <div class="sl-tbl-hdr">
                <h3>Recent Sleep Logs</h3>
            </div>
            <table class="sl-dt">
                <thead>
                    <tr>
                        <th>Date</th><th>Slept At</th><th>Woke At</th><th>Hours</th><th>Quality</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($recent) === 0): ?>
                <tr><td colspan="5" style="padding:0;border-bottom:none;"><div class="sl-empty">No sleep logs yet — log your first night above 🌙</div></td></tr>
                <?php endif; ?>
                <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                <tr>
                    <td style="color:#7c6fa0;font-size:12px;"><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['sleep_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['wake_time']); ?></td>
                    <td><strong><?php echo $row['hours_slept']; ?>h</strong></td>
                    <td><span class="sl-badge <?php echo $row['quality']; ?>"><?php echo ucfirst($row['quality']); ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.sl-wrap -->

<?php include '../includes/footer.php'; ?>
