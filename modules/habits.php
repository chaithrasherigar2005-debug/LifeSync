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

// Add new habit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_habit'])) {
    $habit_name = mysqli_real_escape_string($conn, $_POST['habit_name']);
    mysqli_query($conn, "INSERT INTO habits (user_id, habit_name) VALUES ($user_id, '$habit_name')");
    $msg = "✅ Habit added!";
}

// Mark habit complete/incomplete today
if (isset($_GET['toggle']) && isset($_GET['habit_id'])) {
    $habit_id = intval($_GET['habit_id']);
    $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM habit_logs WHERE habit_id=$habit_id AND user_id=$user_id AND date='$today'"));

    if ($existing) {
        $new_status = $existing['completed'] ? 0 : 1;
        mysqli_query($conn, "UPDATE habit_logs SET completed=$new_status WHERE id={$existing['id']}");
    } else {
        mysqli_query($conn, "INSERT INTO habit_logs (habit_id, user_id, date, completed) VALUES ($habit_id, $user_id, '$today', 1)");
        $new_status = 1;
    }

    // Update streak
    if ($new_status == 1) {
        // Count consecutive days
        $streak = 0;
        $check_date = $today;
        while (true) {
            $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT completed FROM habit_logs WHERE habit_id=$habit_id AND user_id=$user_id AND date='$check_date' AND completed=1"));
            if ($r) {
                $streak++;
                $check_date = date('Y-m-d', strtotime("$check_date -1 day"));
            } else {
                break;
            }
        }
        mysqli_query($conn, "UPDATE habits SET current_streak=$streak, best_streak=GREATEST(best_streak, $streak) WHERE id=$habit_id AND user_id=$user_id");
    } else {
        mysqli_query($conn, "UPDATE habits SET current_streak=0 WHERE id=$habit_id AND user_id=$user_id");
    }

    header("Location: habits.php");
    exit();
}

// Delete habit
if (isset($_GET['delete'])) {
    $habit_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM habits WHERE id=$habit_id AND user_id=$user_id");
    header("Location: habits.php");
    exit();
}

// Fetch habits with today's status
$habits_res = mysqli_query($conn, "
    SELECT h.*, 
           COALESCE(hl.completed, 0) as done_today
    FROM habits h
    LEFT JOIN habit_logs hl ON hl.habit_id = h.id AND hl.user_id = $user_id AND hl.date = '$today'
    WHERE h.user_id = $user_id
    ORDER BY h.habit_name
");

$habits = [];
while ($row = mysqli_fetch_assoc($habits_res)) {
    $habits[] = $row;
}

$total = count($habits);
$done  = count(array_filter($habits, fn($h) => $h['done_today']));
$percent = $total > 0 ? round($done / $total * 100) : 0;
$best_overall = $total > 0 ? max(array_column($habits, 'best_streak')) : 0;
$active_streaks = count(array_filter($habits, fn($h) => $h['current_streak'] > 0));

include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── LifeSync Habits Tracker — Midnight Violet × Gold ── */
.ht-wrap { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(160deg, #f8f6ff 0%, #f1ecfe 100%); min-height: 100vh; padding-bottom: 40px; color: #1e0f4a; position: relative; overflow-x: hidden; }

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
.ht-hdr { position:relative; background: linear-gradient(125deg,#1e0f4a 0%, #2d1472 60%, #4c1d95 100%); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; border-radius: 0 0 24px 24px; overflow: hidden; }
.ht-hdr::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,transparent 0%,#f59e0b 30%,#fbbf24 50%,#f59e0b 70%,transparent 100%); }
.ht-hdr-left { display: flex; align-items: center; gap: 14px; }
.ht-hdr-ico { width: 44px; height: 44px; background: #7c3aed; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.ht-hdr h2 { font-family: 'Fredoka', sans-serif; font-size: 22px; color: #fff; margin: 0; line-height: 1; }
.ht-hdr p  { font-size: 12px; color: #c4b5fd; margin: 2px 0 0; }
.ht-avatar { width: 36px; height: 36px; background: #4c1d95; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #c4b5fd; font-size: 14px; font-weight: 700; border: 2px solid #a78bfa44; }

/* Alert */
.ht-alert { margin: 14px 20px 0; padding: 10px 16px; border-radius: 0 10px 10px 0; font-size: 13px; border-left: 4px solid #7c3aed; background: #f3eeff; color: #1e0f4a; }

/* Stat cards */
.ht-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; padding: 18px 20px 0; }
.ht-sc { background: #fff; border-radius: 16px; padding: 16px; border: 1.5px solid #e4d9ff; position: relative; overflow: hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.ht-sc::before { content:''; position:absolute; top:-18px; right:-18px; width:64px; height:64px; border-radius:50%; opacity:.12; }
.ht-sc.g::before { background:#16a34a; }
.ht-sc.b::before { background:#7c3aed; }
.ht-sc.o::before { background:#f59e0b; }
.ht-sc-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.ht-sc-ico { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; }
.ht-sc.g .ht-sc-ico { background:#e9f8ef; }
.ht-sc.b .ht-sc-ico { background:#f3eeff; }
.ht-sc.o .ht-sc-ico { background:#fffbeb; }
.ht-sc-delta { font-size:11px; font-weight:700; padding:3px 7px; border-radius:20px; }
.ht-sc.g .ht-sc-delta { background:#e9f8ef; color:#16a34a; }
.ht-sc.b .ht-sc-delta { background:#f3eeff; color:#7c3aed; }
.ht-sc.o .ht-sc-delta { background:#fffbeb; color:#d97706; }
.ht-sc-val { font-family:'Fredoka',sans-serif; font-size:22px; color:#1e0f4a; line-height:1; }
.ht-sc-lbl { font-size:11px; color:#7c6fa0; margin-top:3px; text-transform:uppercase; letter-spacing:.5px; }

/* Progress bar */
.ht-progress-wrap { margin:14px 20px 0; background:#fff; border-radius:16px; border:1.5px solid #e4d9ff; padding:16px 20px; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.ht-progress-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.ht-progress-title { font-family:'Fredoka',sans-serif; font-size:15px; color:#1e0f4a; }
.ht-progress-count { font-size:12.5px; color:#7c6fa0; font-weight:600; }
.ht-progress-count strong { color:#7c3aed; }
.ht-progress-bg { background:#e4d9ff; border-radius:8px; height:10px; overflow:hidden; }
.ht-progress-fill { height:100%; border-radius:8px; background: linear-gradient(90deg,#4c1d95,#7c3aed,#f59e0b); transition: width .3s; }

/* Body */
.ht-body { padding: 16px 20px 0; }
.ht-card { background:#fff; border-radius:18px; border:1.5px solid #e4d9ff; padding:20px; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.ht-card-title { font-family:'Fredoka',sans-serif; font-size:16px; color:#1e0f4a; display:flex; align-items:center; gap:8px; margin-bottom:16px; }

/* Add habit form */
.ht-add-row { display:flex; gap:10px; align-items:stretch; }
.ht-inp { flex:1; padding:11px 14px; background:#f3eeff; border:1.5px solid #e4d9ff; border-radius:10px; font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; color:#1e0f4a; outline:none; transition:border-color .15s, background .15s; box-sizing:border-box; }
.ht-inp:focus { border-color:#7c3aed; background:#fff; box-shadow:0 0 0 4px rgba(124,58,237,0.10); }
.ht-inp::placeholder { color:#9d87c0; }
.ht-add-btn { padding:11px 22px; border:none; border-radius:10px; background:linear-gradient(120deg,#4c1d95 0%, #7c3aed 60%, #f59e0b 130%); color:#fff; font-size:13px; font-weight:700; font-family:'Plus Jakarta Sans',sans-serif; cursor:pointer; white-space:nowrap; box-shadow: 0 8px 18px rgba(124,58,237,0.28); transition:transform .15s, box-shadow .15s; }
.ht-add-btn:hover { transform: translateY(-1px); box-shadow: 0 12px 24px rgba(124,58,237,0.36); }

/* Habits grid */
.ht-grid-wrap { padding: 14px 20px 0; }
.ht-grid-title { font-family:'Fredoka',sans-serif; font-size:16px; color:#1e0f4a; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.ht-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: 12px; }
.ht-item { background: #fff; border: 1.5px solid #e4d9ff; border-radius: 16px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 8px rgba(76,29,149,0.06); transition: all .15s; }
.ht-item.completed { border-color: #7c3aed; background: linear-gradient(160deg,#f8f6ff 0%,#f3eeff 100%); }
.ht-item-main { display: flex; align-items: center; gap: 12px; }
.ht-check { font-size: 24px; text-decoration: none; line-height:1; transition: transform .15s; }
.ht-check:hover { transform: scale(1.15); }
.ht-name { font-weight: 700; color: #1e0f4a; margin: 0; font-size: 14px; }
.ht-streak { font-size: 11.5px; color: #7c6fa0; margin: 3px 0 0; display:flex; gap:10px; }
.ht-streak-pill { display:inline-flex; align-items:center; gap:3px; }
.ht-delete { text-decoration: none; opacity: .55; font-size: 15px; transition: opacity .15s; }
.ht-delete:hover { opacity: 1; }
.ht-empty { text-align: center; padding: 36px 20px; color: #9d87c0; font-size: 13px; }
</style>

<div class="ht-wrap">

<span class="mascot-bg mascot-bg--1">✅</span>
<span class="mascot-bg mascot-bg--2">🔥</span>
<span class="mascot-bg mascot-bg--3">🏆</span>
<span class="mascot-bg mascot-bg--4">🌱</span>
<span class="mascot-bg mascot-bg--5">⭐</span>
<span class="mascot-bg mascot-bg--6">🎯</span>
<span class="mascot-bg mascot-bg--7">💪</span>
<span class="mascot-bg mascot-bg--8">✨</span>

    <!-- Header -->
    <div class="ht-hdr">
        <div class="ht-hdr-left">
            <div class="ht-hdr-ico">✅</div>
            <div>
                <h2>Habits Tracker</h2>
                <p>Build consistency, one day at a time</p>
            </div>
        </div>
        <div class="ht-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
    </div>

    <?php if ($msg): ?>
    <div class="ht-alert"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="ht-stats">
        <div class="ht-sc b">
            <div class="ht-sc-top">
                <div class="ht-sc-ico">📋</div>
                <span class="ht-sc-delta">Total</span>
            </div>
            <div class="ht-sc-val"><?php echo $total; ?></div>
            <div class="ht-sc-lbl">Habits Tracked</div>
        </div>
        <div class="ht-sc g">
            <div class="ht-sc-top">
                <div class="ht-sc-ico">✅</div>
                <span class="ht-sc-delta">Today</span>
            </div>
            <div class="ht-sc-val"><?php echo $done; ?>/<?php echo $total; ?></div>
            <div class="ht-sc-lbl">Completed Today</div>
        </div>
        <div class="ht-sc o">
            <div class="ht-sc-top">
                <div class="ht-sc-ico">🔥</div>
                <span class="ht-sc-delta">Live</span>
            </div>
            <div class="ht-sc-val"><?php echo $active_streaks; ?></div>
            <div class="ht-sc-lbl">Active Streaks</div>
        </div>
        <div class="ht-sc g">
            <div class="ht-sc-top">
                <div class="ht-sc-ico">🏆</div>
                <span class="ht-sc-delta">All-time</span>
            </div>
            <div class="ht-sc-val"><?php echo $best_overall; ?></div>
            <div class="ht-sc-lbl">Best Streak</div>
        </div>
    </div>

    <!-- Today's Progress -->
    <div class="ht-progress-wrap">
        <div class="ht-progress-row">
            <div class="ht-progress-title">📊 Today's Progress</div>
            <div class="ht-progress-count"><strong><?php echo $done; ?>/<?php echo $total; ?></strong> habits &middot; <?php echo $percent; ?>%</div>
        </div>
        <div class="ht-progress-bg">
            <div class="ht-progress-fill" style="width: <?php echo $percent; ?>%"></div>
        </div>
    </div>

    <!-- Add Habit -->
    <div class="ht-body">
        <div class="ht-card">
            <div class="ht-card-title">➕ Add New Habit</div>
            <form method="POST" class="ht-add-row">
                <input type="text" name="habit_name" class="ht-inp" placeholder="e.g. Read 30 minutes, Drink 8 glasses of water..." required>
                <button type="submit" name="add_habit" class="ht-add-btn">Add Habit</button>
            </form>
        </div>
    </div>

    <!-- Habits List -->
    <div class="ht-grid-wrap">
        <div class="ht-grid-title">📋 Your Habits</div>
        <div class="ht-grid">
            <?php foreach ($habits as $habit): ?>
            <div class="ht-item <?php echo $habit['done_today'] ? 'completed' : ''; ?>">
                <div class="ht-item-main">
                    <a href="?toggle=1&habit_id=<?php echo $habit['id']; ?>" class="ht-check">
                        <?php echo $habit['done_today'] ? '✅' : '⬜'; ?>
                    </a>
                    <div>
                        <p class="ht-name"><?php echo htmlspecialchars($habit['habit_name']); ?></p>
                        <p class="ht-streak">
                            <span class="ht-streak-pill">🔥 <?php echo $habit['current_streak']; ?> day streak</span>
                            <span class="ht-streak-pill">🏆 Best: <?php echo $habit['best_streak']; ?> days</span>
                        </p>
                    </div>
                </div>
                <a href="?delete=<?php echo $habit['id']; ?>" class="ht-delete" onclick="return confirm('Delete this habit?')">🗑️</a>
            </div>
            <?php endforeach; ?>

            <?php if (empty($habits)): ?>
            <div class="ht-empty">No habits yet — add your first habit above 👆</div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.ht-wrap -->

<?php include '../includes/footer.php'; ?>
