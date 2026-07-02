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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_workout'])) {
    $type = mysqli_real_escape_string($conn, $_POST['workout_type']);
    $dur  = intval($_POST['duration']);
    $cal  = intval($_POST['calories_burned']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    mysqli_query($conn, "INSERT INTO workouts (user_id, workout_type, duration_minutes, calories_burned, date) VALUES ($user_id, '$type', $dur, $cal, '$date')");
    $msg = "✅ Workout logged!";
}

$workouts = mysqli_query($conn, "SELECT * FROM workouts WHERE user_id=$user_id ORDER BY date DESC LIMIT 10");

// Weekly chart data
$week_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(duration_minutes) as t FROM workouts WHERE user_id=$user_id AND date='$d'"));
    $week_data[] = $r['t'] ?? 0;
}

// Stats
$total_workouts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM workouts WHERE user_id=$user_id"))['c'] ?? 0;
$week_minutes   = array_sum($week_data);
$week_calories  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(calories_burned) as t FROM workouts WHERE user_id=$user_id AND date >= '" . date('Y-m-d', strtotime('-6 days')) . "'"))['t'] ?? 0;
$last_workout   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM workouts WHERE user_id=$user_id ORDER BY date DESC, id DESC LIMIT 1"));

include '../includes/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── LifeSync Workout Tracker — Midnight Violet × Gold ── */
.wk-wrap { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(160deg, #f8f6ff 0%, #f1ecfe 100%); min-height: 100vh; padding-bottom: 40px; color: #1e0f4a; position: relative; overflow-x: hidden; }

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
.wk-hdr { position:relative; background: linear-gradient(125deg,#1e0f4a 0%, #2d1472 60%, #4c1d95 100%); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; border-radius: 0 0 24px 24px; overflow: hidden; }
.wk-hdr::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,transparent 0%,#f59e0b 30%,#fbbf24 50%,#f59e0b 70%,transparent 100%); }
.wk-hdr-left { display: flex; align-items: center; gap: 14px; }
.wk-hdr-ico { width: 44px; height: 44px; background: #7c3aed; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.wk-hdr h2 { font-family: 'Fredoka', sans-serif; font-size: 22px; color: #fff; margin: 0; line-height: 1; }
.wk-hdr p  { font-size: 12px; color: #c4b5fd; margin: 2px 0 0; }
.wk-avatar { width: 36px; height: 36px; background: #4c1d95; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #c4b5fd; font-size: 14px; font-weight: 700; border: 2px solid #a78bfa44; }

/* Alert */
.wk-alert { margin: 14px 20px 0; padding: 10px 16px; border-radius: 0 10px 10px 0; font-size: 13px; border-left: 4px solid #7c3aed; background: #f3eeff; color: #1e0f4a; }

/* Stat cards */
.wk-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; padding: 18px 20px 0; }
.wk-sc { background: #fff; border-radius: 16px; padding: 16px; border: 1.5px solid #e4d9ff; position: relative; overflow: hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.wk-sc::before { content:''; position:absolute; top:-18px; right:-18px; width:64px; height:64px; border-radius:50%; opacity:.12; }
.wk-sc.b::before { background:#7c3aed; }
.wk-sc.g::before { background:#16a34a; }
.wk-sc.o::before { background:#f59e0b; }
.wk-sc.r::before { background:#e74c3c; }
.wk-sc-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.wk-sc-ico { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; }
.wk-sc.b .wk-sc-ico { background:#f3eeff; }
.wk-sc.g .wk-sc-ico { background:#e9f8ef; }
.wk-sc.o .wk-sc-ico { background:#fffbeb; }
.wk-sc.r .wk-sc-ico { background:#fef0f0; }
.wk-sc-delta { font-size:11px; font-weight:700; padding:3px 7px; border-radius:20px; }
.wk-sc.b .wk-sc-delta { background:#f3eeff; color:#7c3aed; }
.wk-sc.g .wk-sc-delta { background:#e9f8ef; color:#16a34a; }
.wk-sc.o .wk-sc-delta { background:#fffbeb; color:#d97706; }
.wk-sc.r .wk-sc-delta { background:#fef0f0; color:#c0392b; }
.wk-sc-val { font-family:'Fredoka',sans-serif; font-size:22px; color:#1e0f4a; line-height:1; }
.wk-sc-lbl { font-size:11px; color:#7c6fa0; margin-top:3px; text-transform:uppercase; letter-spacing:.5px; }

/* Body */
.wk-body { padding: 16px 20px 0; }
.wk-card { background:#fff; border-radius:18px; border:1.5px solid #e4d9ff; padding:20px; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.wk-card-title { font-family:'Fredoka',sans-serif; font-size:16px; color:#1e0f4a; display:flex; align-items:center; gap:8px; margin-bottom:16px; }

/* Add workout form */
.wk-add-grid { display:grid; grid-template-columns: 1.6fr 1fr 1fr 1fr auto; gap:10px; align-items:end; }
.wk-fg { display:flex; flex-direction:column; gap:4px; }
.wk-lbl { font-size:10.5px; font-weight:700; color:#3b1f6a; text-transform:uppercase; letter-spacing:.6px; }
.wk-inp, .wk-sel { padding:9px 12px; background:#f3eeff; border:1.5px solid #e4d9ff; border-radius:10px; font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; color:#1e0f4a; outline:none; width:100%; box-sizing:border-box; transition:border-color .15s, background .15s; }
.wk-inp:focus, .wk-sel:focus { border-color:#7c3aed; background:#fff; box-shadow:0 0 0 4px rgba(124,58,237,0.10); }
.wk-add-btn { padding:9px 22px; border:none; border-radius:10px; background:linear-gradient(120deg,#4c1d95 0%, #7c3aed 60%, #f59e0b 130%); color:#fff; font-size:13px; font-weight:700; font-family:'Plus Jakarta Sans',sans-serif; cursor:pointer; white-space:nowrap; height:38px; box-shadow: 0 8px 18px rgba(124,58,237,0.28); transition:transform .15s, box-shadow .15s; }
.wk-add-btn:hover { transform: translateY(-1px); box-shadow: 0 12px 24px rgba(124,58,237,0.36); }

/* Recent table */
.wk-tbl-wrap { padding:14px 20px 0; }
.wk-tbl-card { background:#fff; border-radius:18px; border:1.5px solid #e4d9ff; overflow:hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.wk-tbl-hdr  { padding:14px 20px; background:linear-gradient(125deg,#1e0f4a 0%, #2d1472 100%); }
.wk-tbl-hdr h3 { font-family:'Fredoka',sans-serif; font-size:16px; color:#fff; margin:0; }

table.wk-dt { width:100%; border-collapse:collapse; }
.wk-dt thead th { padding:9px 16px; font-size:10.5px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.5px; text-align:left; background:#faf8ff; border-bottom:1.5px solid #e4d9ff; }
.wk-dt tbody td { padding:11px 16px; font-size:13px; border-bottom:1px solid #f1edfb; color:#2e2253; }
.wk-dt tbody tr:last-child td { border-bottom:none; }
.wk-dt tbody tr:hover { background:#faf8ff; }
.wk-tg { padding:2px 8px; border-radius:6px; font-size:11px; background:#f3eeff; color:#7c3aed; font-weight:600; }
.wk-empty { text-align:center; padding:36px 20px; color:#9d87c0; font-size:13px; }
</style>

<div class="wk-wrap">

<span class="mascot-bg mascot-bg--1">🏋️</span>
<span class="mascot-bg mascot-bg--2">💪</span>
<span class="mascot-bg mascot-bg--3">🏃</span>
<span class="mascot-bg mascot-bg--4">🔥</span>
<span class="mascot-bg mascot-bg--5">⚡</span>
<span class="mascot-bg mascot-bg--6">🚴</span>
<span class="mascot-bg mascot-bg--7">🧘</span>
<span class="mascot-bg mascot-bg--8">✨</span>

    <!-- Header -->
    <div class="wk-hdr">
        <div class="wk-hdr-left">
            <div class="wk-hdr-ico">🏋️</div>
            <div>
                <h2>Workout Tracker</h2>
                <p>Log your exercises and track active minutes</p>
            </div>
        </div>
        <div class="wk-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
    </div>

    <?php if ($msg): ?>
    <div class="wk-alert"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="wk-stats">
        <div class="wk-sc b">
            <div class="wk-sc-top">
                <div class="wk-sc-ico">📋</div>
                <span class="wk-sc-delta">All-time</span>
            </div>
            <div class="wk-sc-val"><?php echo $total_workouts; ?></div>
            <div class="wk-sc-lbl">Total Workouts</div>
        </div>
        <div class="wk-sc g">
            <div class="wk-sc-top">
                <div class="wk-sc-ico">⏱️</div>
                <span class="wk-sc-delta">Last 7 days</span>
            </div>
            <div class="wk-sc-val"><?php echo $week_minutes; ?>m</div>
            <div class="wk-sc-lbl">Active Minutes</div>
        </div>
        <div class="wk-sc o">
            <div class="wk-sc-top">
                <div class="wk-sc-ico">🔥</div>
                <span class="wk-sc-delta">Last 7 days</span>
            </div>
            <div class="wk-sc-val"><?php echo number_format($week_calories); ?></div>
            <div class="wk-sc-lbl">Calories Burned</div>
        </div>
        <div class="wk-sc r">
            <div class="wk-sc-top">
                <div class="wk-sc-ico">🏃</div>
                <span class="wk-sc-delta">Most recent</span>
            </div>
            <div class="wk-sc-val" style="font-size:16px;"><?php echo $last_workout ? htmlspecialchars($last_workout['workout_type']) : '—'; ?></div>
            <div class="wk-sc-lbl">Last Workout</div>
        </div>
    </div>

    <!-- Log Workout -->
    <div class="wk-body">
        <div class="wk-card">
            <div class="wk-card-title">➕ Log Workout</div>
            <form method="POST" class="wk-add-grid">
                <div class="wk-fg">
                    <label class="wk-lbl">Workout Type</label>
                    <select name="workout_type" class="wk-sel" required>
                        <option>Running</option><option>Gym / Weights</option><option>Yoga</option>
                        <option>Cycling</option><option>Swimming</option><option>Walking</option>
                        <option>Home Workout</option><option>Sports</option><option>Other</option>
                    </select>
                </div>
                <div class="wk-fg">
                    <label class="wk-lbl">Duration (min)</label>
                    <input type="number" name="duration" class="wk-inp" min="1" placeholder="30" required>
                </div>
                <div class="wk-fg">
                    <label class="wk-lbl">Calories (est.)</label>
                    <input type="number" name="calories_burned" class="wk-inp" min="0" placeholder="200">
                </div>
                <div class="wk-fg">
                    <label class="wk-lbl">Date</label>
                    <input type="date" name="date" class="wk-inp" value="<?php echo $today; ?>" required>
                </div>
                <button type="submit" name="add_workout" class="wk-add-btn">Log</button>
            </form>
        </div>
    </div>

    <!-- Weekly Chart -->
    <div class="wk-body">
        <div class="wk-card">
            <div class="wk-card-title">📊 Last 7 Days (minutes)</div>
            <canvas id="workoutChart" height="100"></canvas>
            <script>
            new Chart(document.getElementById('workoutChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_map(fn($i) => date('D', strtotime("-".($i)." days")), array_reverse(range(0,6)))); ?>,
                    datasets: [{ label: 'Minutes', data: <?php echo json_encode($week_data); ?>, backgroundColor: '#7c3aed', borderRadius: 6 }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { ticks: { color: '#7c6fa0' }, grid: { color: 'rgba(124,58,237,0.06)' } },
                        x: { ticks: { color: '#7c6fa0' }, grid: { display: false } }
                    }
                }
            });
            </script>
        </div>
    </div>

    <!-- Recent Workouts -->
    <div class="wk-tbl-wrap">
        <div class="wk-tbl-card">
            <div class="wk-tbl-hdr">
                <h3>Recent Workouts</h3>
            </div>
            <table class="wk-dt">
                <thead>
                    <tr><th>Date</th><th>Type</th><th>Duration</th><th>Calories</th></tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($workouts) === 0): ?>
                <tr><td colspan="4" style="padding:0;border-bottom:none;"><div class="wk-empty">No workouts yet — log your first session above 💪</div></td></tr>
                <?php endif; ?>
                <?php while ($row = mysqli_fetch_assoc($workouts)): ?>
                <tr>
                    <td style="color:#7c6fa0;font-size:12px;"><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><span class="wk-tg"><?php echo htmlspecialchars($row['workout_type']); ?></span></td>
                    <td><?php echo $row['duration_minutes']; ?> min</td>
                    <td><?php echo $row['calories_burned'] ? $row['calories_burned'].' kcal' : '-'; ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.wk-wrap -->

<?php include '../includes/footer.php'; ?>
