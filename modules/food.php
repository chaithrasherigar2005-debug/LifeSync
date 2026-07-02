<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location:index.php');
    exit;
}

require_once '../includes/auth.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$today   = date('Y-m-d');
$msg     = '';

// Ensure calorie_goals table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS calorie_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    daily_goal INT NOT NULL DEFAULT 2000,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Fetch calorie goal
$goal_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT daily_goal FROM calorie_goals WHERE user_id=$user_id"));
$cal_goal = $goal_row ? intval($goal_row['daily_goal']) : 2000;

// Update calorie goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_goal'])) {
    $new_goal = intval($_POST['calorie_goal']);
    if ($new_goal >= 500 && $new_goal <= 10000) {
        $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM calorie_goals WHERE user_id=$user_id"));
        if ($existing) {
            mysqli_query($conn, "UPDATE calorie_goals SET daily_goal=$new_goal WHERE user_id=$user_id");
        } else {
            mysqli_query($conn, "INSERT INTO calorie_goals (user_id, daily_goal) VALUES ($user_id, $new_goal)");
        }
        $cal_goal = $new_goal;
        $msg = "🎯 Daily calorie goal updated to {$new_goal} kcal!";
    }
}

// ── Day navigation ──
$view_date = $today;
if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
    $candidate = $_GET['date'];
    // Don't allow navigating into the future
    if ($candidate <= $today) {
        $view_date = $candidate;
    }
}

// Log food entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_food'])) {
    $name  = mysqli_real_escape_string($conn, $_POST['food_name']);
    $meal  = mysqli_real_escape_string($conn, $_POST['meal']);
    $kcal  = intval($_POST['calories']);
    $prot  = intval($_POST['protein']);
    $carbs = intval($_POST['carbs']);
    $fats  = intval($_POST['fats']);
    $date  = mysqli_real_escape_string($conn, $_POST['date']);

    mysqli_query($conn, "INSERT INTO food_logs (user_id, food_name, meal, calories, protein, carbs, fats, date)
                         VALUES ($user_id, '$name', '$meal', $kcal, $prot, $carbs, $fats, '$date')");
    $msg = "✅ " . htmlspecialchars($_POST['food_name']) . " logged!";
    // Keep viewing the day the entry was logged for
    $view_date = $date;
}

// Log water
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_water'])) {
    $glasses   = intval($_POST['glasses']);
    $water_date = mysqli_real_escape_string($conn, $_POST['water_date'] ?? $view_date);
    $existing  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM water_logs WHERE user_id=$user_id AND date='$water_date'"));
    if ($existing) {
        mysqli_query($conn, "UPDATE water_logs SET glasses=$glasses WHERE user_id=$user_id AND date='$water_date'");
    } else {
        mysqli_query($conn, "INSERT INTO water_logs (user_id, glasses, date) VALUES ($user_id, $glasses, '$water_date')");
    }
    $msg = "💧 Water intake updated!";
    $view_date = $water_date;
}

// ── Day nav helpers ──
$is_today    = ($view_date === $today);
$prev_date   = date('Y-m-d', strtotime($view_date . ' -1 day'));
$next_date   = date('Y-m-d', strtotime($view_date . ' +1 day'));
$can_go_next = ($next_date <= $today);
$view_date_display = $is_today ? 'Today' : date('D, d M Y', strtotime($view_date));

// Fetch food logs for the viewed date
$logs_res = mysqli_query($conn, "SELECT * FROM food_logs WHERE user_id=$user_id AND date='$view_date' ORDER BY id ASC");
$logs = [];
while ($r = mysqli_fetch_assoc($logs_res)) $logs[] = $r;

$total_kcal = array_sum(array_column($logs, 'calories'));
$total_p    = array_sum(array_column($logs, 'protein'));
$total_c    = array_sum(array_column($logs, 'carbs'));
$total_f    = array_sum(array_column($logs, 'fats'));

$water_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT glasses FROM water_logs WHERE user_id=$user_id AND date='$view_date'"));
$water      = $water_row['glasses'] ?? 0;

$cal_pct  = min(round(($total_kcal / $cal_goal) * 100), 100);

$meals_map = ['breakfast' => [], 'lunch' => [], 'dinner' => [], 'snacks' => []];
foreach ($logs as $log) {
    if (isset($meals_map[$log['meal']])) $meals_map[$log['meal']][] = $log;
}

include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── LifeSync Food Tracker — Midnight Violet × Gold ── */
.ft-wrap { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(160deg, #f8f6ff 0%, #f1ecfe 100%); min-height: 100vh; padding-bottom: 40px; color: #1e0f4a; }

.ft-hdr { position:relative; background: linear-gradient(125deg,#1e0f4a 0%, #2d1472 60%, #4c1d95 100%); padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; border-radius: 0 0 24px 24px; overflow: hidden; }
.ft-hdr::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,transparent 0%,#f59e0b 30%,#fbbf24 50%,#f59e0b 70%,transparent 100%); }
.ft-hdr-left { display: flex; align-items: center; gap: 12px; }
.ft-hdr-ico { width: 42px; height: 42px; background: #7c3aed; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.ft-hdr h2 { font-family: 'Fredoka', sans-serif; font-size: 22px; color: #fff; margin: 0; line-height: 1.1; }
.ft-hdr p  { font-size: 12px; color: #c4b5fd; margin: 2px 0 0; }
.ft-av { width: 36px; height: 36px; background: #4c1d95; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #c4b5fd; font-size: 13px; font-weight: 700; border: 2px solid #a78bfa44; }

.ft-alert { margin: 12px 20px 0; padding: 10px 16px; border-radius: 0 10px 10px 0; font-size: 13px; border-left: 4px solid #7c3aed; background: #f3eeff; color: #1e0f4a; }

.ft-summary { display: grid; grid-template-columns: auto 1fr; gap: 16px; padding: 16px 20px 0; align-items: center; }
.ft-ring-wrap { position: relative; width: 130px; height: 130px; flex-shrink: 0; }
.ft-ring-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); text-align: center; }
.ft-ring-cal { font-family: 'Fredoka', sans-serif; font-size: 22px; color: #1e0f4a; line-height: 1; }
.ft-ring-lbl { font-size: 10px; color: #7c6fa0; text-transform: uppercase; letter-spacing: .5px; }
.ft-macro-cards { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; }
.ft-mc { background: #fff; border: 1.5px solid #e4d9ff; border-radius: 14px; padding: 12px 14px; position: relative; overflow: hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.ft-mc::before { content:''; position: absolute; top:-14px; right:-14px; width:50px; height:50px; border-radius:50%; opacity:.12; }
.ft-mc.p::before { background: #7c3aed; }
.ft-mc.c::before { background: #d97706; }
.ft-mc.f::before { background: #0ea5e9; }
.ft-mc-val { font-family: 'Fredoka', sans-serif; font-size: 20px; line-height: 1; margin-bottom: 2px; }
.ft-mc.p .ft-mc-val { color: #6d28d9; }
.ft-mc.c .ft-mc-val { color: #d97706; }
.ft-mc.f .ft-mc-val { color: #0369a1; }
.ft-mc-lbl { font-size: 10px; color: #7c6fa0; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
.ft-mc-bar { height: 5px; border-radius: 4px; background: #e4d9ff; overflow: hidden; }
.ft-mc-bar-fill { height: 100%; border-radius: 4px; }
.ft-mc.p .ft-mc-bar-fill { background: #7c3aed; }
.ft-mc.c .ft-mc-bar-fill { background: #d97706; }
.ft-mc.f .ft-mc-bar-fill { background: #0ea5e9; }

.ft-meals { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 14px 20px 0; }
.ft-meal-card { background: #fff; border: 1.5px solid #e4d9ff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.ft-meal-hdr { background: linear-gradient(125deg,#1e0f4a 0%, #4c1d95 100%); padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; }
.ft-meal-hdr-left { display: flex; align-items: center; gap: 8px; }
.ft-meal-ico { width: 28px; height: 28px; background: rgba(196,181,253,0.18); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; }
.ft-meal-name { font-family: 'Fredoka', sans-serif; font-size: 14px; color: #fff; }
.ft-meal-badge { font-size: 11px; font-weight: 700; background: rgba(196,181,253,0.18); color: #c4b5fd; padding: 3px 8px; border-radius: 20px; }
.ft-meal-body { padding: 10px 14px; }
.ft-food-row { display: flex; align-items: center; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1edfb; }
.ft-food-row:last-child { border-bottom: none; }
.ft-food-name { color: #2e2253; font-weight: 600; font-size: 12.5px; }
.ft-food-macros { display: flex; gap: 6px; align-items: center; }
.ft-tag { font-size: 10.5px; padding: 2px 6px; border-radius: 6px; font-weight: 700; }
.ft-tag.p { background: #f3eeff; color: #6d28d9; }
.ft-tag.c { background: #fffbeb; color: #92400e; }
.ft-tag.f { background: #e0f2fe; color: #075985; }
.ft-food-kcal { font-size: 12px; color: #7c6fa0; font-weight: 700; min-width: 46px; text-align: right; }
.ft-empty { color: #b3a9d6; font-size: 12px; padding: 10px 0; text-align: center; }

/* Add food form */
.ft-add-section { padding: 12px 20px 0; }
.ft-add-card { background: #fff; border: 1.5px solid #e4d9ff; border-radius: 16px; padding: 18px 20px; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.ft-add-title { font-family: 'Fredoka', sans-serif; font-size: 16px; color: #1e0f4a; margin-bottom: 14px; }
.ft-add-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr auto; gap: 8px; align-items: end; }
.ft-fg { display: flex; flex-direction: column; gap: 4px; position: relative; }
.ft-lbl { font-size: 10.5px; font-weight: 700; color: #3b1f6a; text-transform: uppercase; letter-spacing: .6px; }

.ft-inp, .ft-sel {
    padding: 8px 10px;
    background: #f3eeff;
    border: 1.5px solid #e4d9ff;
    border-radius: 10px;
    font-size: 13px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    color: #1e0f4a;
    outline: none;
    width: 100%;
    -webkit-appearance: none;
    appearance: none;
    box-sizing: border-box;
    transition: border-color .15s, background .15s;
}
.ft-inp:focus, .ft-sel:focus { border-color: #7c3aed; background: #fff; box-shadow: 0 0 0 4px rgba(124,58,237,0.10); }
.ft-inp::placeholder { color: #9d87c0; }
.ft-sel option { background-color: #ffffff; color: #1e0f4a; font-size: 13px; }

.ft-add-btn { padding: 8px 18px; border: none; border-radius: 10px; background: linear-gradient(120deg,#4c1d95 0%, #7c3aed 60%, #f59e0b 130%); color: #fff; font-size: 13px; font-weight: 700; font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; white-space: nowrap; height: 38px; box-shadow: 0 8px 18px rgba(124,58,237,0.28); transition: transform .15s, box-shadow .15s; }
.ft-add-btn:hover { transform: translateY(-1px); box-shadow: 0 12px 24px rgba(124,58,237,0.36); }

/* Autocomplete dropdown */
.ft-autocomplete {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1.5px solid #e4d9ff;
    border-top: none;
    border-radius: 0 0 10px 10px;
    z-index: 999;
    max-height: 220px;
    overflow-y: auto;
    box-shadow: 0 10px 28px rgba(76,29,149,0.16);
    display: none;
}
.ft-autocomplete.open { display: block; }
.ft-ac-item {
    padding: 9px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f1edfb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: background .1s;
}
.ft-ac-item:last-child { border-bottom: none; }
.ft-ac-item:hover, .ft-ac-item.active { background: #f3eeff; }
.ft-ac-name { font-size: 13px; font-weight: 600; color: #1e0f4a; }
.ft-ac-meta { display: flex; gap: 6px; align-items: center; }
.ft-ac-kcal { font-size: 11.5px; font-weight: 700; color: #6d28d9; background: #f3eeff; padding: 2px 7px; border-radius: 20px; }
.ft-ac-tags { font-size: 10.5px; color: #7c6fa0; }
.ft-ac-empty { padding: 10px 12px; font-size: 12.5px; color: #9d87c0; text-align: center; }

/* Water */
.ft-water-section { padding: 12px 20px 0; }
.ft-water-card { background: #fff; border: 1.5px solid #e4d9ff; border-radius: 16px; padding: 16px 20px; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.ft-water-title { font-family: 'Fredoka', sans-serif; font-size: 16px; color: #1e0f4a; margin-bottom: 12px; }
.ft-water-row { display: flex; align-items: center; gap: 16px; }
.ft-water-cups { display: flex; gap: 6px; flex-wrap: wrap; flex: 1; }
.ft-water-cup { width: 36px; height: 36px; border-radius: 8px; border: 1.5px solid #bae6fd; background: #f0f9ff; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 16px; transition: all .15s; }
.ft-water-cup.filled { background: #0ea5e9; border-color: #0369a1; }
.ft-water-info { font-size: 13px; color: #7c6fa0; text-align: right; min-width: 80px; }
.ft-water-info strong { display: block; font-family: 'Fredoka', sans-serif; font-size: 20px; color: #0ea5e9; }
</style>

<div class="ft-wrap">

    <div class="ft-hdr">
        <div class="ft-hdr-left">
            <div class="ft-hdr-ico">🥗</div>
            <div>
                <h2>Food Tracker</h2>
                <p>Today — <?php echo date('F j, Y'); ?></p>
            </div>
        </div>
        <div class="ft-av"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
    </div>

    <?php if ($msg): ?>
    <div class="ft-alert"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Summary -->
    <div class="ft-summary">
        <div class="ft-ring-wrap">
            <canvas id="ringChart" width="130" height="130"></canvas>
            <div class="ft-ring-center">
                <div class="ft-ring-cal"><?php echo $total_kcal; ?></div>
                <div class="ft-ring-lbl">kcal eaten</div>
            </div>
        </div>
        <div>
            <div style="margin: 12px 0; display:flex; justify-content:space-between;">
                <span style="font-size:12px;color:rgba(147, 197, 253, 0.75);">Daily goal: <strong style="color:#60a5fa;"><?php echo $cal_goal; ?> kcal</strong></span>
                <span style="font-size:12px;color:rgba(147, 197, 253, 0.75);">Remaining: <strong style="color:#4ade80;"><?php echo max($cal_goal - $total_kcal, 0); ?> kcal</strong></span>
            </div>
            <div class="ft-macro-cards">
                <div class="ft-mc p">
                    <div class="ft-mc-lbl">Protein</div>
                    <div class="ft-mc-val"><?php echo $total_p; ?>g</div>
                    <div class="ft-mc-bar"><div class="ft-mc-bar-fill" style="width:<?php echo min(round($total_p/150*100),100); ?>%"></div></div>
                </div>
                <div class="ft-mc c">
                    <div class="ft-mc-lbl">Carbs</div>
                    <div class="ft-mc-val"><?php echo $total_c; ?>g</div>
                    <div class="ft-mc-bar"><div class="ft-mc-bar-fill" style="width:<?php echo min(round($total_c/250*100),100); ?>%"></div></div>
                </div>
                <div class="ft-mc f">
                    <div class="ft-mc-lbl">Fats</div>
                    <div class="ft-mc-val"><?php echo $total_f; ?>g</div>
                    <div class="ft-mc-bar"><div class="ft-mc-bar-fill" style="width:<?php echo min(round($total_f/65*100),100); ?>%"></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Meal Cards -->
    <?php
    $meal_icons  = ['breakfast'=>'🌅','lunch'=>'☀️','dinner'=>'🌙','snacks'=>'🍎'];
    $meal_labels = ['breakfast'=>'Breakfast','lunch'=>'Lunch','dinner'=>'Dinner','snacks'=>'Snacks'];
    ?>
    <div class="ft-meals">
    <?php foreach ($meals_map as $meal_key => $items):
        $meal_kcal = array_sum(array_column($items, 'calories'));
    ?>
        <div class="ft-meal-card">
            <div class="ft-meal-hdr">
                <div class="ft-meal-hdr-left">
                    <div class="ft-meal-ico"><?php echo $meal_icons[$meal_key]; ?></div>
                    <span class="ft-meal-name"><?php echo $meal_labels[$meal_key]; ?></span>
                </div>
                <span class="ft-meal-badge"><?php echo $meal_kcal; ?> kcal</span>
            </div>
            <div class="ft-meal-body">
                <?php if (empty($items)): ?>
                    <div class="ft-empty">Nothing logged yet</div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <div class="ft-food-row">
                        <span class="ft-food-name"><?php echo htmlspecialchars($item['food_name']); ?></span>
                        <div class="ft-food-macros">
                            <span class="ft-tag p">P <?php echo $item['protein']; ?>g</span>
                            <span class="ft-tag c">C <?php echo $item['carbs']; ?>g</span>
                            <span class="ft-tag f">F <?php echo $item['fats']; ?>g</span>
                            <span class="ft-food-kcal"><?php echo $item['calories']; ?> kcal</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <!-- Log Food Form -->
    <div class="ft-add-section">
        <div class="ft-add-card">
            <div class="ft-add-title">➕ Log Food</div>
            <form method="POST" id="food-form" autocomplete="off">
                <div class="ft-add-grid">
                    <!-- Food name with autocomplete -->
                    <div class="ft-fg" id="ac-wrap">
                        <label class="ft-lbl">Food Name</label>
                        <input type="text" name="food_name" id="food-search" class="ft-inp"
                               placeholder="Search food e.g. Rice, Egg..." required>
                        <div class="ft-autocomplete" id="ac-dropdown"></div>
                    </div>
                    <div class="ft-fg">
                        <label class="ft-lbl">Meal</label>
                        <select name="meal" class="ft-sel" required>
                            <option value="breakfast">Breakfast</option>
                            <option value="lunch">Lunch</option>
                            <option value="dinner">Dinner</option>
                            <option value="snacks">Snacks</option>
                        </select>
                    </div>
                    <div class="ft-fg">
                        <label class="ft-lbl">Calories</label>
                        <input type="number" name="calories" id="f-kcal" class="ft-inp" placeholder="kcal" min="0" required>
                    </div>
                    <div class="ft-fg">
                        <label class="ft-lbl">Protein (g)</label>
                        <input type="number" name="protein" id="f-prot" class="ft-inp" placeholder="0" min="0" value="0">
                    </div>
                    <div class="ft-fg">
                        <label class="ft-lbl">Carbs (g)</label>
                        <input type="number" name="carbs" id="f-carbs" class="ft-inp" placeholder="0" min="0" value="0">
                    </div>
                    <div class="ft-fg">
                        <label class="ft-lbl">Fats (g)</label>
                        <input type="number" name="fats" id="f-fats" class="ft-inp" placeholder="0" min="0" value="0">
                    </div>
                    <div class="ft-fg">
                        <label class="ft-lbl">&nbsp;</label>
                        <input type="hidden" name="date" value="<?php echo $view_date; ?>">
                        <button type="submit" name="log_food" class="ft-add-btn">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Water Tracker -->
    <div class="ft-water-section">
        <div class="ft-water-card">
            <div class="ft-water-title">💧 Water Intake</div>
            <div class="ft-water-row">
                <div class="ft-water-cups" id="water-cups"></div>
                <div class="ft-water-info">
                    <strong id="w-count"><?php echo $water; ?></strong>
                    <span>/ 8 glasses</span>
                </div>
            </div>
            <form method="POST" id="water-form" style="display:none;">
                <input type="hidden" name="glasses" id="water-hidden" value="<?php echo $water; ?>">
                <input type="hidden" name="set_water" value="1">
            </form>
        </div>
    </div>

    <!-- Calorie Goal Form -->
    <div class="ft-water-section" style="margin-top: 20px;">
        <div class="ft-water-card">
            <div class="ft-water-title" style="margin-bottom: 12px;">🎯 Set Daily Calorie Goal</div>
            <form method="POST">
                <div style="display: flex; gap: 12px; align-items: end;">
                    <div style="flex: 1;">
                        <label class="ft-lbl" style="margin-bottom: 6px;">Daily Target (kcal)</label>
                        <input type="number" name="calorie_goal" class="ft-inp" value="<?php echo $cal_goal; ?>" min="500" max="10000" style="width: 100%;" required>
                    </div>
                    <button type="submit" name="set_goal" class="ft-add-btn" style="width: auto; padding: 10px 20px; height: 38px;">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Nutrition History -->
    <?php
    $history_res = mysqli_query($conn, "
        SELECT date, SUM(calories) as total_calories, SUM(protein) as total_protein, SUM(carbs) as total_carbs, SUM(fats) as total_fats
        FROM food_logs
        WHERE user_id = $user_id
        GROUP BY date
        UNION
        SELECT date, 0 as total_calories, 0 as total_protein, 0 as total_carbs, 0 as total_fats
        FROM water_logs
        WHERE user_id = $user_id AND date NOT IN (SELECT DISTINCT date FROM food_logs WHERE user_id = $user_id)
        GROUP BY date
        ORDER BY date DESC
        LIMIT 30
    ");
    ?>
    <div class="ft-tbl-wrap" style="margin-top: 24px; width: 100%;">
        <div class="rd-tbl-card" style="overflow: hidden;">
            <div class="rd-tbl-hdr" style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 16px; font-family: 'Fredoka', sans-serif;">📅 Nutrition History (Past 30 Days)</h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="rd-dt" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 10px 16px; text-align: left;">Date</th>
                            <th style="padding: 10px 16px; text-align: right;">Calories</th>
                            <th style="padding: 10px 16px; text-align: center;">Macros (P/C/F)</th>
                            <th style="padding: 10px 16px; text-align: center;">Water</th>
                            <th style="padding: 10px 16px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($history_res) === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 24px; color: rgba(147, 197, 253, 0.4);">No food or water logged in the past 30 days.</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($h_row = mysqli_fetch_assoc($history_res)): 
                            $h_date = $h_row['date'];
                            // Fetch water
                            $h_water_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT glasses FROM water_logs WHERE user_id = $user_id AND date = '$h_date'"));
                            $h_water = $h_water_row['glasses'] ?? 0;
                            // Format date
                            $h_date_disp = date('D, d M Y', strtotime($h_date));
                            $is_h_today = ($h_date === date('Y-m-d'));
                        ?>
                            <tr>
                                <td style="padding: 12px 16px; font-weight: 600;">
                                    <?php echo $is_h_today ? 'Today' : $h_date_disp; ?>
                                </td>
                                <td style="padding: 12px 16px; text-align: right; font-weight: bold; color: <?php echo $h_row['total_calories'] > $cal_goal ? '#f87171' : '#4ade80'; ?>;">
                                    <?php echo $h_row['total_calories']; ?> / <?php echo $cal_goal; ?> kcal
                                </td>
                                <td style="padding: 12px 16px; text-align: center; font-size: 12px; color: rgba(240, 244, 255, 0.8);">
                                    <span style="border-bottom: 2px solid #7c3aed; padding-bottom: 2px;">P: <?php echo intval($h_row['total_protein']); ?>g</span> &middot;
                                    <span style="border-bottom: 2px solid #d97706; padding-bottom: 2px;">C: <?php echo intval($h_row['total_carbs']); ?>g</span> &middot;
                                    <span style="border-bottom: 2px solid #0ea5e9; padding-bottom: 2px;">F: <?php echo intval($h_row['total_fats']); ?>g</span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    💧 <?php echo $h_water; ?> / 8
                                </td>
                                <td style="padding: 12px 16px; text-align: right;">
                                    <a href="?date=<?php echo $h_date; ?>" class="rd-del-btn" style="background: rgba(96, 165, 250, 0.1); border: 1px solid rgba(96, 165, 250, 0.3); color: #60a5fa; padding: 4px 8px; border-radius: 6px; font-size: 12px; text-decoration: none;">View Day</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Calorie ring ──
(function(){
    const pct  = <?php echo $cal_pct; ?> / 100;
    const over = <?php echo $total_kcal; ?> >= <?php echo $cal_goal; ?>;
    new Chart(document.getElementById('ringChart'), {
        type: 'doughnut',
        data: { datasets: [{ data: [pct, 1-pct], backgroundColor: [over ? '#e74c3c' : '#3B6D11', '#e0ecd4'], borderWidth: 0 }] },
        options: { cutout: '78%', plugins: { legend: { display: false }, tooltip: { enabled: false } }, animation: { duration: 400 } }
    });
})();

// ── Water cups ──
let water = <?php echo $water; ?>;
function renderWater() {
    const wrap = document.getElementById('water-cups');
    wrap.innerHTML = '';
    for (let i = 0; i < 8; i++) {
        const d = document.createElement('div');
        d.className = 'ft-water-cup' + (i < water ? ' filled' : '');
        d.textContent = '💧';
        d.onclick = () => {
            water = (i < water) ? i : i + 1;
            document.getElementById('water-hidden').value = water;
            document.getElementById('water-form').submit();
        };
        wrap.appendChild(d);
    }
    document.getElementById('w-count').textContent = water;
}
renderWater();

// ── Food autocomplete with auto-fill ──
// Common Indian & international foods with nutrition per serving
const FOOD_DB = [
    {name:'Idli (2 pieces)',          kcal:130, p:4,  c:26, f:1},
    {name:'Dosa (plain)',             kcal:168, p:4,  c:30, f:4},
    {name:'Masala Dosa',              kcal:230, p:5,  c:40, f:6},
    {name:'Upma (1 cup)',             kcal:180, p:5,  c:30, f:5},
    {name:'Poha (1 cup)',             kcal:250, p:5,  c:45, f:6},
    {name:'Paratha (1 piece)',        kcal:260, p:5,  c:36, f:11},
    {name:'Chapati / Roti',           kcal:100, p:3,  c:18, f:2},
    {name:'Dal Rice (1 plate)',       kcal:480, p:18, c:82, f:8},
    {name:'Rajma Chawal',             kcal:520, p:20, c:88, f:8},
    {name:'Chole Bhature',            kcal:650, p:18, c:90, f:24},
    {name:'Chicken Curry (1 bowl)',   kcal:420, p:35, c:12, f:22},
    {name:'Chicken Biryani (1 plate)',kcal:550, p:32, c:68, f:14},
    {name:'Paneer Butter Masala',     kcal:380, p:18, c:20, f:26},
    {name:'Egg Bhurji (2 eggs)',      kcal:220, p:16, c:4,  f:16},
    {name:'Boiled Egg',               kcal:78,  p:6,  c:1,  f:5},
    {name:'Oats (1 cup cooked)',      kcal:166, p:6,  c:28, f:4},
    {name:'Banana (medium)',          kcal:105, p:1,  c:27, f:0},
    {name:'Apple (medium)',           kcal:95,  p:0,  c:25, f:0},
    {name:'Orange (medium)',          kcal:62,  p:1,  c:15, f:0},
    {name:'Mango (1 cup)',            kcal:99,  p:1,  c:25, f:1},
    {name:'Milk (1 glass 250ml)',     kcal:150, p:8,  c:12, f:8},
    {name:'Curd / Yogurt (1 cup)',    kcal:100, p:8,  c:11, f:3},
    {name:'Lassi (sweet, 1 glass)',   kcal:220, p:7,  c:32, f:7},
    {name:'Chai with milk (1 cup)',   kcal:60,  p:2,  c:8,  f:2},
    {name:'Samosa (1 piece)',         kcal:260, p:5,  c:32, f:13},
    {name:'Vada Pav',                 kcal:290, p:7,  c:42, f:10},
    {name:'Bread slice (white)',      kcal:70,  p:2,  c:13, f:1},
    {name:'Peanut Butter (1 tbsp)',   kcal:94,  p:4,  c:3,  f:8},
    {name:'Rice (1 cup cooked)',      kcal:206, p:4,  c:45, f:0},
    {name:'Moong Dal (1 cup)',        kcal:212, p:14, c:38, f:1},
    {name:'Palak Paneer (1 bowl)',    kcal:320, p:16, c:14, f:22},
    {name:'Vegetable Pulao',          kcal:310, p:8,  c:58, f:6},
    {name:'Fish Fry (1 piece)',       kcal:190, p:22, c:8,  f:8},
    {name:'Mutton Curry (1 bowl)',    kcal:480, p:38, c:10, f:32},
    {name:'Pasta (1 cup cooked)',     kcal:220, p:8,  c:43, f:2},
    {name:'Pizza slice (cheese)',     kcal:285, p:12, c:36, f:10},
    {name:'Burger (veg)',             kcal:310, p:8,  c:42, f:12},
    {name:'French Fries (medium)',    kcal:365, p:4,  c:48, f:17},
    {name:'Chocolate (1 bar 40g)',    kcal:210, p:3,  c:24, f:12},
    {name:'Almonds (10 pieces)',      kcal:70,  p:3,  c:2,  f:6},
    {name:'Walnuts (4 halves)',       kcal:87,  p:2,  c:2,  f:9},
    {name:'Sprouts (1 cup)',          kcal:82,  p:8,  c:14, f:1},
    {name:'Corn (1 cup)',             kcal:132, p:5,  c:29, f:2},
    {name:'Watermelon (1 cup)',       kcal:46,  p:1,  c:11, f:0},
    {name:'Grapes (1 cup)',           kcal:104, p:1,  c:27, f:0},
];

const searchEl  = document.getElementById('food-search');
const dropdown  = document.getElementById('ac-dropdown');
let acIndex = -1;
let acResults = [];

function fillForm(item) {
    searchEl.value                          = item.name;
    document.getElementById('f-kcal').value  = item.kcal;
    document.getElementById('f-prot').value  = item.p;
    document.getElementById('f-carbs').value = item.c;
    document.getElementById('f-fats').value  = item.f;
    closeDropdown();
}

function closeDropdown() {
    dropdown.classList.remove('open');
    dropdown.innerHTML = '';
    acIndex = -1;
}

function renderDropdown(results) {
    acResults = results;
    acIndex = -1;
    if (!results.length) {
        dropdown.innerHTML = '<div class="ft-ac-empty">No match — you can still type manually</div>';
        dropdown.classList.add('open');
        return;
    }
    dropdown.innerHTML = results.map((item, i) => `
        <div class="ft-ac-item" data-i="${i}">
            <span class="ft-ac-name">${item.name}</span>
            <div class="ft-ac-meta">
                <span class="ft-ac-kcal">${item.kcal} kcal</span>
                <span class="ft-ac-tags">P${item.p} C${item.c} F${item.f}</span>
            </div>
        </div>`).join('');
    dropdown.querySelectorAll('.ft-ac-item').forEach(el => {
        el.addEventListener('mousedown', e => {
            e.preventDefault();
            fillForm(acResults[parseInt(el.dataset.i)]);
        });
    });
    dropdown.classList.add('open');
}

searchEl.addEventListener('input', () => {
    const q = searchEl.value.trim().toLowerCase();
    // Clear macro fields when user changes food name
    document.getElementById('f-kcal').value  = '';
    document.getElementById('f-prot').value  = '0';
    document.getElementById('f-carbs').value = '0';
    document.getElementById('f-fats').value  = '0';

    if (q.length < 1) { closeDropdown(); return; }
    const results = FOOD_DB.filter(f => f.name.toLowerCase().includes(q)).slice(0, 8);
    renderDropdown(results);
});

searchEl.addEventListener('keydown', e => {
    const items = dropdown.querySelectorAll('.ft-ac-item');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        acIndex = Math.min(acIndex + 1, items.length - 1);
        items.forEach((el, i) => el.classList.toggle('active', i === acIndex));
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        acIndex = Math.max(acIndex - 1, 0);
        items.forEach((el, i) => el.classList.toggle('active', i === acIndex));
    } else if (e.key === 'Enter' && acIndex >= 0) {
        e.preventDefault();
        fillForm(acResults[acIndex]);
    } else if (e.key === 'Escape') {
        closeDropdown();
    }
});

searchEl.addEventListener('blur', () => setTimeout(closeDropdown, 150));
</script>

<?php include '../includes/footer.php'; ?>