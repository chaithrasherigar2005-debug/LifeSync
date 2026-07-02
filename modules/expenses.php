<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/auth.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$month = date('Y-m');
$msg = '';
$msg_type = 'success';

// Add expense/income
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $type     = $_POST['type'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $amount   = floatval($_POST['amount']);
    $desc     = mysqli_real_escape_string($conn, $_POST['description']);
    $date     = mysqli_real_escape_string($conn, $_POST['date']);

    mysqli_query($conn, "INSERT INTO expenses (user_id, type, category, amount, description, date) VALUES ($user_id, '$type', '$category', $amount, '$desc', '$date')");
    $msg = "✅ Entry added successfully!";
}

// Set budget limit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_budget'])) {
    $limit = floatval($_POST['budget_limit']);
    $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM budget_limits WHERE user_id=$user_id AND month='$month'"));
    if ($existing) {
        mysqli_query($conn, "UPDATE budget_limits SET limit_amount=$limit WHERE user_id=$user_id AND month='$month'");
    } else {
        mysqli_query($conn, "INSERT INTO budget_limits (user_id, month, limit_amount) VALUES ($user_id, '$month', $limit)");
    }
    $msg = "✅ Budget limit set!";
}

// Fetch this month's data
$expenses = mysqli_query($conn, "SELECT * FROM expenses WHERE user_id=$user_id AND date LIKE '$month%' ORDER BY date DESC");
$total_income  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as t FROM expenses WHERE user_id=$user_id AND type='income'  AND date LIKE '$month%'"))['t'] ?? 0;
$total_expense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as t FROM expenses WHERE user_id=$user_id AND type='expense' AND date LIKE '$month%'"))['t'] ?? 0;
$budget_row    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT limit_amount FROM budget_limits WHERE user_id=$user_id AND month='$month'"));
$budget_limit  = $budget_row['limit_amount'] ?? 0;
$balance       = $total_income - $total_expense;

// Category-wise for doughnut chart
$cat_data_res = mysqli_query($conn, "SELECT category, SUM(amount) as total FROM expenses WHERE user_id=$user_id AND type='expense' AND date LIKE '$month%' GROUP BY category");
$cat_labels = []; $cat_amounts = [];
while ($row = mysqli_fetch_assoc($cat_data_res)) {
    $cat_labels[]  = $row['category'];
    $cat_amounts[] = (float)$row['total'];
}

// Budget bar percentage
$budget_pct = ($budget_limit > 0) ? min(round(($total_expense / $budget_limit) * 100), 100) : 0;
$budget_status = '';
if ($budget_limit > 0) {
    $budget_status = $total_expense > $budget_limit ? '⚠️ Over!' : '✅ OK';
}

include '../includes/header.php';
?>

<style>
/* ── LifeSync Expense Tracker Page Styles — Midnight Violet × Gold ── */
.et-wrap { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(160deg, #f8f6ff 0%, #f1ecfe 100%); min-height: 100vh; padding-bottom: 40px; color: #1e0f4a; position: relative; overflow-x: hidden; }

/* ── Animated cartoon mascots — spread across full viewport ── */
.mascot-bg{position:fixed;z-index:0;pointer-events:none;filter:drop-shadow(0 4px 10px rgba(124,58,237,0.18));}
.mascot-bg--1{top:8%;left:3%;font-size:46px;opacity:0.30;animation:mascotFloat 7s ease-in-out infinite;}
.mascot-bg--2{bottom:6%;right:3%;font-size:42px;opacity:0.28;animation:mascotFloat 8s ease-in-out infinite 1.2s;}
.mascot-bg--3{top:22%;right:2%;font-size:36px;opacity:0.24;animation:mascotFloat 9s ease-in-out infinite .6s;}
.mascot-bg--4{top:62%;left:1.5%;font-size:40px;opacity:0.26;animation:mascotFloat 7.5s ease-in-out infinite 2s;}
.mascot-bg--5{bottom:38%;right:4%;font-size:30px;opacity:0.22;animation:mascotFloat 8.5s ease-in-out infinite 1.6s;}
.mascot-bg--6{top:2%;right:14%;font-size:28px;opacity:0.22;animation:mascotFloat 6.5s ease-in-out infinite .9s;}
.mascot-bg--7{bottom:2%;left:12%;font-size:32px;opacity:0.22;animation:mascotFloat 9.5s ease-in-out infinite 2.4s;}
.mascot-bg--8{top:84%;right:16%;font-size:26px;opacity:0.20;animation:mascotFloat 7.2s ease-in-out infinite 1.1s;}
.mascot-bg--9{top:48%;left:10%;font-size:24px;opacity:0.18;animation:mascotFloat 8.8s ease-in-out infinite .4s;}
.mascot-bg--10{bottom:14%;left:2%;font-size:34px;opacity:0.24;animation:mascotFloat 7.8s ease-in-out infinite 1.8s;}
.mascot-bg--11{top:4%;left:18%;font-size:30px;opacity:0.20;animation:mascotFloat 8.2s ease-in-out infinite .3s;}
.mascot-bg--12{bottom:4%;right:18%;font-size:32px;opacity:0.22;animation:mascotFloat 7.4s ease-in-out infinite 1.4s;}
.mascot-bg--13{top:38%;right:1%;font-size:28px;opacity:0.20;animation:mascotFloat 9.2s ease-in-out infinite 2.1s;}
.mascot-bg--14{top:14%;left:1%;font-size:24px;opacity:0.18;animation:mascotFloat 6.8s ease-in-out infinite .7s;}
.mascot-bg--15{bottom:24%;left:0.5%;font-size:26px;opacity:0.20;animation:mascotFloat 8.6s ease-in-out infinite 1.9s;}
.mascot-bg--16{top:90%;left:8%;font-size:22px;opacity:0.18;animation:mascotFloat 7.6s ease-in-out infinite 1s;}
.mascot-bg--17{top:0.5%;right:0.5%;font-size:20px;opacity:0.16;animation:mascotFloat 9.8s ease-in-out infinite 2.6s;}
.mascot-bg--18{bottom:0.5%;right:0.5%;font-size:22px;opacity:0.18;animation:mascotFloat 6.2s ease-in-out infinite .5s;}
@keyframes mascotFloat{0%,100%{transform:translateY(0) rotate(-4deg);}50%{transform:translateY(-22px) rotate(4deg);}}

/* Header */
.et-header { position:relative; background: linear-gradient(125deg,#1e0f4a 0%, #2d1472 60%, #4c1d95 100%); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; border-radius: 0 0 24px 24px; overflow: hidden; }
.et-header::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,transparent 0%,#f59e0b 30%,#fbbf24 50%,#f59e0b 70%,transparent 100%); }
.et-header-left { display: flex; align-items: center; gap: 14px; }
.et-hdr-icon { width: 44px; height: 44px; background: #7c3aed; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.et-header h2 { font-family: 'Fredoka', sans-serif; font-size: 22px; color: #fff; margin: 0; line-height: 1; }
.et-header p  { font-size: 12px; color: #c4b5fd; margin: 2px 0 0; }
.et-hdr-right { display: flex; align-items: center; gap: 10px; }
.et-avatar { width: 36px; height: 36px; background: #4c1d95; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #c4b5fd; font-size: 14px; font-weight: 700; border: 2px solid #a78bfa44; }

/* Alert */
.et-alert { margin: 14px 20px 0; padding: 10px 16px; border-radius: 0 10px 10px 0; font-size: 13px; border-left: 4px solid #7c3aed; background: #f3eeff; color: #1e0f4a; }

/* Stat cards */
.et-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; padding: 18px 20px 0; }
.et-sc { background: #fff; border-radius: 16px; padding: 16px; border: 1.5px solid #e4d9ff; position: relative; overflow: hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.et-sc::before { content:''; position:absolute; top:-18px; right:-18px; width:64px; height:64px; border-radius:50%; opacity:.12; }
.et-sc.g::before { background:#16a34a; }
.et-sc.r::before { background:#c0392b; }
.et-sc.b::before { background:#7c3aed; }
.et-sc-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.et-sc-ico { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; }
.et-sc.g .et-sc-ico { background:#e9f8ef; }
.et-sc.r .et-sc-ico { background:#fef0f0; }
.et-sc.b .et-sc-ico { background:#f3eeff; }
.et-sc-delta { font-size:11px; font-weight:700; padding:3px 7px; border-radius:20px; }
.et-sc.g .et-sc-delta { background:#e9f8ef; color:#16a34a; }
.et-sc.r .et-sc-delta { background:#fef0f0; color:#c0392b; }
.et-sc.b .et-sc-delta { background:#f3eeff; color:#7c3aed; }
.et-sc-val { font-family:'Fredoka',sans-serif; font-size:22px; color:#1e0f4a; line-height:1; }
.et-sc-lbl { font-size:11px; color:#7c6fa0; margin-top:3px; text-transform:uppercase; letter-spacing:.5px; }

/* Body grid */
.et-body { display:grid; grid-template-columns:1fr 1fr; gap:14px; padding:16px 20px 0; }
.et-card { background:#fff; border-radius:18px; border:1.5px solid #e4d9ff; padding:20px; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.et-card-title { font-family:'Fredoka',sans-serif; font-size:16px; color:#1e0f4a; display:flex; align-items:center; gap:8px; margin-bottom:16px; }

/* Type toggle */
.et-type-row { display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:12px; }
.et-type-btn { padding:9px; border-radius:10px; border:1.5px solid #e4d9ff; background:#f8f6ff; color:#7c6fa0; font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; cursor:pointer; font-weight:500; transition:all .15s; }
.et-type-btn.active-expense { border-color:#e74c3c; color:#c0392b; background:#fff5f5; }
.et-type-btn.active-income  { border-color:#16a34a; color:#16a34a; background:#e9f8ef; }

/* Form elements */
.et-fg { margin-bottom:11px; }
.et-lbl { display:block; font-size:11px; font-weight:700; color:#3b1f6a; text-transform:uppercase; letter-spacing:.7px; margin-bottom:4px; }
.et-inp, .et-sel { width:100%; padding:9px 12px; background:#f3eeff; border:1.5px solid #e4d9ff; border-radius:10px; font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; color:#1e0f4a; outline:none; transition:border-color .15s, background .15s; box-sizing:border-box; }
.et-inp:focus, .et-sel:focus { border-color:#7c3aed; background:#fff; box-shadow:0 0 0 4px rgba(124,58,237,0.10); }
.et-inp::placeholder { color:#9d87c0; }

/* Buttons */
.et-btn-p { width:100%; padding:11px; border:none; border-radius:12px; font-size:14px; font-weight:700; font-family:'Plus Jakarta Sans',sans-serif; cursor:pointer; background:linear-gradient(120deg,#4c1d95 0%, #7c3aed 60%, #f59e0b 130%); color:#fff; margin-top:2px; box-shadow: 0 10px 22px rgba(124,58,237,0.28); transition:all .15s; }
.et-btn-p:hover { transform: translateY(-1px); box-shadow: 0 14px 28px rgba(124,58,237,0.36); }
.et-btn-s { width:100%; padding:11px; border:1.5px solid #d6c5fb; border-radius:12px; font-size:14px; font-weight:700; font-family:'Plus Jakarta Sans',sans-serif; cursor:pointer; background:#f3eeff; color:#5b21b6; margin-top:2px; transition:all .15s; }
.et-btn-s:hover { background:#e9deff; }

/* Budget bar */
.et-bbar-wrap { margin-top:16px; padding:14px; background:#f8f6ff; border-radius:12px; border:1.5px solid #e4d9ff; }
.et-bbar-row { display:flex; justify-content:space-between; font-size:12px; color:#7c6fa0; margin-bottom:8px; }
.et-bbar-bg  { background:#e4d9ff; border-radius:8px; height:8px; overflow:hidden; }
.et-bbar-fill { height:100%; border-radius:8px; }
.et-bbar-pct { font-size:12px; color:#3b1f6a; font-weight:700; margin-top:6px; text-align:right; }

/* Pie legend */
.et-chart-wrap { margin-top:16px; }
.et-pie-legend { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; }
.et-leg-item { display:flex; align-items:center; gap:5px; font-size:11.5px; color:#3b1f6a; background:#f8f6ff; border:1px solid #e4d9ff; padding:3px 8px; border-radius:20px; }
.et-leg-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }

/* Transactions table */
.et-tbl-wrap { padding:14px 20px 0; }
.et-tbl-card { background:#fff; border-radius:18px; border:1.5px solid #e4d9ff; overflow:hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.et-tbl-hdr  { padding:14px 20px; background:linear-gradient(125deg,#1e0f4a 0%, #2d1472 100%); display:flex; align-items:center; justify-content:space-between; }
.et-tbl-hdr h3 { font-family:'Fredoka',sans-serif; font-size:16px; color:#fff; margin:0; }
.et-flt { display:flex; gap:5px; }
.et-fb { padding:4px 11px; border-radius:20px; border:1px solid #7c3aed; background:transparent; color:#c4b5fd; font-size:11.5px; cursor:pointer; font-family:'Plus Jakarta Sans',sans-serif; transition:all .15s; font-weight:600; }
.et-fb:hover, .et-fb.act { background:#a78bfa; color:#1e0f4a; border-color:#a78bfa; }

table.et-dt { width:100%; border-collapse:collapse; table-layout:fixed; }
.et-dt thead th { padding:9px 16px; font-size:10.5px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.5px; text-align:left; background:#faf8ff; border-bottom:1.5px solid #e4d9ff; }
.et-dt tbody td { padding:11px 16px; font-size:13px; border-bottom:1px solid #f1edfb; color:#2e2253; }
.et-dt tbody tr:last-child td { border-bottom:none; }
.et-dt tbody tr:hover { background:#faf8ff; }

.et-badge { padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; }
.et-badge.expense { background:#fff0f0; color:#c0392b; }
.et-badge.income  { background:#e9f8ef; color:#16a34a; }
.et-tg { padding:2px 8px; border-radius:6px; font-size:11px; background:#f3eeff; color:#7c3aed; font-weight:600; }
.et-amt-in { color:#16a34a; font-weight:700; }
.et-amt-ex { color:#c0392b; font-weight:700; }
.et-empty { text-align:center; padding:36px 20px; color:#9d87c0; font-size:13px; }
</style>

<div class="et-wrap">

<span class="mascot-bg mascot-bg--1">🐷</span>
<span class="mascot-bg mascot-bg--2">🌱</span>
<span class="mascot-bg mascot-bg--3">🍃</span>
<span class="mascot-bg mascot-bg--4">🪙</span>
<span class="mascot-bg mascot-bg--5">🐝</span>
<span class="mascot-bg mascot-bg--6">🦋</span>
<span class="mascot-bg mascot-bg--7">🌿</span>
<span class="mascot-bg mascot-bg--8">🐞</span>
<span class="mascot-bg mascot-bg--9">🌸</span>
<span class="mascot-bg mascot-bg--10">💰</span>
<span class="mascot-bg mascot-bg--11">🐌</span>
<span class="mascot-bg mascot-bg--12">🦔</span>
<span class="mascot-bg mascot-bg--13">🐦</span>
<span class="mascot-bg mascot-bg--14">🍀</span>
<span class="mascot-bg mascot-bg--15">🐢</span>
<span class="mascot-bg mascot-bg--16">🌼</span>
<span class="mascot-bg mascot-bg--17">✨</span>
<span class="mascot-bg mascot-bg--18">✨</span>

    <!-- Header -->
    <div class="et-header">
        <div class="et-header-left">
            <div class="et-hdr-icon">💸</div>
            <div>
                <h2>Expense Tracker</h2>
                <p><?php echo date('F Y'); ?></p>
            </div>
        </div>
        <div class="et-hdr-right">
            <div class="et-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="et-alert"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="et-stats">
        <div class="et-sc g">
            <div class="et-sc-top">
                <div class="et-sc-ico">📥</div>
                <span class="et-sc-delta">This month</span>
            </div>
            <div class="et-sc-val">₹<?php echo number_format($total_income, 0); ?></div>
            <div class="et-sc-lbl">Total Income</div>
        </div>
        <div class="et-sc r">
            <div class="et-sc-top">
                <div class="et-sc-ico">📤</div>
                <span class="et-sc-delta">This month</span>
            </div>
            <div class="et-sc-val">₹<?php echo number_format($total_expense, 0); ?></div>
            <div class="et-sc-lbl">Total Expenses</div>
        </div>
        <div class="et-sc <?php echo $balance >= 0 ? 'g' : 'r'; ?>">
            <div class="et-sc-top">
                <div class="et-sc-ico">💰</div>
                <span class="et-sc-delta"><?php echo $balance >= 0 ? '+ saved' : '− deficit'; ?></span>
            </div>
            <div class="et-sc-val">₹<?php echo number_format(abs($balance), 0); ?></div>
            <div class="et-sc-lbl">Net Balance</div>
        </div>
        <div class="et-sc b">
            <div class="et-sc-top">
                <div class="et-sc-ico">🎯</div>
                <span class="et-sc-delta"><?php echo $budget_limit > 0 ? $budget_status : '—'; ?></span>
            </div>
            <div class="et-sc-val"><?php echo $budget_limit > 0 ? '₹'.number_format($budget_limit,0) : '—'; ?></div>
            <div class="et-sc-lbl">Budget Limit</div>
        </div>
    </div>

    <!-- Add Entry + Budget Side by Side -->
    <div class="et-body">

        <!-- Add Entry Form -->
        <div class="et-card">
            <div class="et-card-title">➕ Add Entry</div>
            <form method="POST" id="entry-form">
                <input type="hidden" name="type" id="hidden-type" value="expense">
                <div class="et-fg">
                    <label class="et-lbl">Type</label>
                    <div class="et-type-row">
                        <button type="button" class="et-type-btn active-expense" id="tb-exp" onclick="setType('expense')">📤 Expense</button>
                        <button type="button" class="et-type-btn" id="tb-inc" onclick="setType('income')">📥 Income</button>
                    </div>
                </div>
                <div class="et-fg">
                    <label class="et-lbl">Category</label>
                    <select name="category" class="et-sel" required>
                        <option>Food</option><option>Travel</option><option>Shopping</option>
                        <option>Education</option><option>Health</option><option>Entertainment</option>
                        <option>Rent</option><option>Salary</option><option>Other</option>
                    </select>
                </div>
                <div class="et-fg">
                    <label class="et-lbl">Amount (₹)</label>
                    <input type="number" name="amount" class="et-inp" step="0.01" min="0" placeholder="0.00" required>
                </div>
                <div class="et-fg">
                    <label class="et-lbl">Description</label>
                    <input type="text" name="description" class="et-inp" placeholder="Optional note">
                </div>
                <div class="et-fg">
                    <label class="et-lbl">Date</label>
                    <input type="date" name="date" class="et-inp" value="<?php echo $today; ?>" required>
                </div>
                <button type="submit" name="add_expense" class="et-btn-p">Add Entry</button>
            </form>
        </div>

        <!-- Budget + Chart -->
        <div class="et-card">
            <div class="et-card-title">🎯 Monthly Budget</div>
            <form method="POST">
                <div class="et-fg">
                    <label class="et-lbl">Budget limit for <?php echo date('F Y'); ?> (₹)</label>
                    <input type="number" name="budget_limit" class="et-inp" value="<?php echo $budget_limit > 0 ? $budget_limit : ''; ?>" min="0" placeholder="e.g. 20000" required>
                </div>
                <button type="submit" name="set_budget" class="et-btn-s">Set Limit</button>
            </form>

            <?php if ($budget_limit > 0): ?>
            <?php
                $bar_color = $total_expense > $budget_limit ? '#e74c3c' : ($budget_pct > 75 ? '#d97706' : '#16a34a');
            ?>
            <div class="et-bbar-wrap">
                <div class="et-bbar-row">
                    <span>Spent: ₹<?php echo number_format($total_expense, 0); ?></span>
                    <span>Limit: ₹<?php echo number_format($budget_limit, 0); ?></span>
                </div>
                <div class="et-bbar-bg">
                    <div class="et-bbar-fill" style="width:<?php echo $budget_pct; ?>%;background:<?php echo $bar_color; ?>;"></div>
                </div>
                <div class="et-bbar-pct"><?php echo $budget_pct; ?>% used</div>
            </div>
            <?php endif; ?>

            <?php if (count($cat_labels) > 0): ?>
            <div class="et-chart-wrap">
                <div style="position:relative;height:185px;margin-top:6px;">
                    <canvas id="expenseChart"></canvas>
                </div>
                <div class="et-pie-legend" id="pie-legend"></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="et-tbl-wrap">
        <div class="et-tbl-card">
            <div class="et-tbl-hdr">
                <h3>This Month's Transactions</h3>
                <div class="et-flt">
                    <button class="et-fb act" onclick="filterRows('all',this)">All</button>
                    <button class="et-fb" onclick="filterRows('expense',this)">Expenses</button>
                    <button class="et-fb" onclick="filterRows('income',this)">Income</button>
                </div>
            </div>
            <table class="et-dt">
                <thead>
                    <tr>
                        <th style="width:18%">Date</th>
                        <th style="width:15%">Type</th>
                        <th style="width:16%">Category</th>
                        <th style="width:31%">Description</th>
                        <th style="width:20%;text-align:right">Amount</th>
                    </tr>
                </thead>
                <tbody id="tx-body">
                <?php while ($row = mysqli_fetch_assoc($expenses)): ?>
                <tr data-type="<?php echo $row['type']; ?>">
                    <td style="color:#6b7a99;font-size:12px;"><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><span class="et-badge <?php echo $row['type']; ?>"><?php echo ucfirst($row['type']); ?></span></td>
                    <td><span class="et-tg"><?php echo htmlspecialchars($row['category']); ?></span></td>
                    <td style="color:#3b5998;font-size:12.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?php echo htmlspecialchars($row['description']) ?: '—'; ?>
                    </td>
                    <td style="text-align:right" class="<?php echo $row['type'] === 'income' ? 'et-amt-in' : 'et-amt-ex'; ?>">
                        <?php echo $row['type'] === 'income' ? '+' : '−'; ?>₹<?php echo number_format($row['amount'], 0); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <div class="et-empty" id="tx-empty" style="display:none">No transactions yet — add one above 🌱</div>
        </div>
    </div>

</div><!-- /.et-wrap -->

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Type toggle
function setType(t) {
    document.getElementById('hidden-type').value = t;
    document.getElementById('tb-exp').className = 'et-type-btn' + (t === 'expense' ? ' active-expense' : '');
    document.getElementById('tb-inc').className = 'et-type-btn' + (t === 'income'  ? ' active-income'  : '');
}

// Row filter
function filterRows(f, btn) {
    document.querySelectorAll('.et-fb').forEach(b => b.classList.remove('act'));
    btn.classList.add('act');
    const rows = document.querySelectorAll('#tx-body tr');
    let visible = 0;
    rows.forEach(r => {
        const show = f === 'all' || r.dataset.type === f;
        r.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('tx-empty').style.display = visible === 0 ? 'block' : 'none';
}

// Doughnut chart
<?php if (count($cat_labels) > 0): ?>
const COLS = ['#7c3aed','#06b6d4','#f59e0b','#e74c3c','#16a34a','#ec4899','#a78bfa','#fb923c'];
const catLabels  = <?php echo json_encode($cat_labels); ?>;
const catAmounts = <?php echo json_encode($cat_amounts); ?>;

const pieChart = new Chart(document.getElementById('expenseChart'), {
    type: 'doughnut',
    data: {
        labels: catLabels,
        datasets: [{
            data: catAmounts,
            backgroundColor: COLS.slice(0, catLabels.length),
            borderWidth: 3,
            borderColor: '#1e0f4a'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ₹' + Math.round(ctx.parsed).toLocaleString('en-IN') } }
        }
    }
});

const leg = document.getElementById('pie-legend');
catLabels.forEach((l, i) => {
    leg.innerHTML += `<div class="et-leg-item"><div class="et-leg-dot" style="background:${COLS[i]}"></div>${l}</div>`;
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>