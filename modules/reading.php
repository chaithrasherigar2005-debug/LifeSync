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

// Add new book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $title  = mysqli_real_escape_string($conn, $_POST['book_title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $pages  = intval($_POST['total_pages']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $started = $_POST['started_date'] ? "'" . mysqli_real_escape_string($conn, $_POST['started_date']) . "'" : 'NULL';
    mysqli_query($conn, "INSERT INTO reading_logs (user_id, book_title, author, total_pages, status, started_date)
        VALUES ($user_id, '$title', '$author', $pages, '$status', $started)");
    $msg = "✅ Book added!";
}

// Log reading session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_session'])) {
    $book_id  = intval($_POST['book_id']);
    $pages    = intval($_POST['pages_this_session']);
    $minutes  = intval($_POST['minutes_spent']);
    $date     = mysqli_real_escape_string($conn, $_POST['date']);
    mysqli_query($conn, "INSERT INTO reading_sessions (user_id, book_id, pages_this_session, minutes_spent, date)
        VALUES ($user_id, $book_id, $pages, $minutes, '$date')");
    // Update pages_read
    mysqli_query($conn, "UPDATE reading_logs SET pages_read = pages_read + $pages WHERE id=$book_id AND user_id=$user_id");
    // Auto-complete if done
    $book = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reading_logs WHERE id=$book_id"));
    if ($book && $book['pages_read'] >= $book['total_pages'] && $book['total_pages'] > 0) {
        mysqli_query($conn, "UPDATE reading_logs SET status='completed', finished_date='$date' WHERE id=$book_id");
    }
    $msg = "✅ Session logged!";
}

// Update rating / notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $book_id = intval($_POST['book_id']);
    $rating  = intval($_POST['rating']);
    $notes   = mysqli_real_escape_string($conn, $_POST['notes']);
    $status  = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE reading_logs SET rating=$rating, notes='$notes', status='$status' WHERE id=$book_id AND user_id=$user_id");
    $msg = "✅ Updated!";
}

// Delete book
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM reading_logs WHERE id=$id AND user_id=$user_id");
    header("Location: reading.php"); exit();
}

// Fetch books
$books_res = mysqli_query($conn, "SELECT * FROM reading_logs WHERE user_id=$user_id ORDER BY FIELD(status,'reading','want_to_read','completed','dropped'), created_at DESC");
$books = [];
while ($r = mysqli_fetch_assoc($books_res)) $books[] = $r;

// Stats
$total_books     = count($books);
$completed_books = count(array_filter($books, fn($b) => $b['status'] === 'completed'));
$currently_reading = array_filter($books, fn($b) => $b['status'] === 'reading');
$pages_this_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(pages_this_session) as t FROM reading_sessions WHERE user_id=$user_id AND date LIKE '".date('Y-m')."%'"))['t'] ?? 0;
$mins_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(minutes_spent) as t FROM reading_sessions WHERE user_id=$user_id AND date='$today'"))['t'] ?? 0;

// 7-day chart
$week_labels = []; $week_pages = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $week_labels[] = date('D', strtotime($d));
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(pages_this_session) as t FROM reading_sessions WHERE user_id=$user_id AND date='$d'"));
    $week_pages[] = intval($r['t'] ?? 0);
}

// Active books for session dropdown
$active_books = array_filter($books, fn($b) => $b['status'] === 'reading');

$status_colors = ['reading'=>'#0ea5e9','completed'=>'#16a34a','want_to_read'=>'#f59e0b','dropped'=>'#e74c3c'];
$status_bg     = ['reading'=>'#e0f2fe','completed'=>'#e9f8ef','want_to_read'=>'#fffbeb','dropped'=>'#fef0f0'];
$status_labels = ['reading'=>'Reading','completed'=>'Completed','want_to_read'=>'Want to Read','dropped'=>'Dropped'];

include '../includes/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── LifeSync Reading Tracker — Midnight Violet × Gold ── */
.rd-wrap { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(160deg, #f8f6ff 0%, #f1ecfe 100%); min-height: 100vh; padding-bottom: 40px; color: #1e0f4a; position: relative; overflow-x: hidden; }

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
.rd-hdr { position:relative; background: linear-gradient(125deg,#1e0f4a 0%, #2d1472 60%, #4c1d95 100%); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; border-radius: 0 0 24px 24px; overflow: hidden; }
.rd-hdr::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,transparent 0%,#f59e0b 30%,#fbbf24 50%,#f59e0b 70%,transparent 100%); }
.rd-hdr-left { display: flex; align-items: center; gap: 14px; }
.rd-hdr-ico { width: 44px; height: 44px; background: #7c3aed; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.rd-hdr h2 { font-family: 'Fredoka', sans-serif; font-size: 22px; color: #fff; margin: 0; line-height: 1; }
.rd-hdr p  { font-size: 12px; color: #c4b5fd; margin: 2px 0 0; }
.rd-avatar { width: 36px; height: 36px; background: #4c1d95; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #c4b5fd; font-size: 14px; font-weight: 700; border: 2px solid #a78bfa44; }

/* Alert */
.rd-alert { margin: 14px 20px 0; padding: 10px 16px; border-radius: 0 10px 10px 0; font-size: 13px; border-left: 4px solid #7c3aed; background: #f3eeff; color: #1e0f4a; }

/* Stat cards */
.rd-stats { display: grid; grid-template-columns: repeat(5,1fr); gap: 12px; padding: 18px 20px 0; }
.rd-sc { background: #fff; border-radius: 16px; padding: 16px; border: 1.5px solid #e4d9ff; position: relative; overflow: hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.rd-sc::before { content:''; position:absolute; top:-18px; right:-18px; width:64px; height:64px; border-radius:50%; opacity:.12; }
.rd-sc.b::before { background:#7c3aed; }
.rd-sc.g::before { background:#16a34a; }
.rd-sc.c::before { background:#0ea5e9; }
.rd-sc.o::before { background:#f59e0b; }
.rd-sc.p::before { background:#ec4899; }
.rd-sc-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.rd-sc-ico { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; }
.rd-sc.b .rd-sc-ico { background:#f3eeff; }
.rd-sc.g .rd-sc-ico { background:#e9f8ef; }
.rd-sc.c .rd-sc-ico { background:#e0f2fe; }
.rd-sc.o .rd-sc-ico { background:#fffbeb; }
.rd-sc.p .rd-sc-ico { background:#fdf2f8; }
.rd-sc-val { font-family:'Fredoka',sans-serif; font-size:22px; color:#1e0f4a; line-height:1; }
.rd-sc-lbl { font-size:11px; color:#7c6fa0; margin-top:3px; text-transform:uppercase; letter-spacing:.5px; }

/* Body grid */
.rd-body { display:grid; grid-template-columns:1fr 1fr; gap:14px; padding:16px 20px 0; }
.rd-card { background:#fff; border-radius:18px; border:1.5px solid #e4d9ff; padding:20px; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.rd-card-title { font-family:'Fredoka',sans-serif; font-size:16px; color:#1e0f4a; display:flex; align-items:center; gap:8px; margin-bottom:16px; }

/* Form elements */
.rd-fg { margin-bottom:11px; }
.rd-lbl { display:block; font-size:11px; font-weight:700; color:#3b1f6a; text-transform:uppercase; letter-spacing:.7px; margin-bottom:4px; }
.rd-inp, .rd-sel { width:100%; padding:9px 12px; background:#f3eeff; border:1.5px solid #e4d9ff; border-radius:10px; font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; color:#1e0f4a; outline:none; transition:border-color .15s, background .15s; box-sizing:border-box; }
.rd-inp:focus, .rd-sel:focus { border-color:#7c3aed; background:#fff; box-shadow:0 0 0 4px rgba(124,58,237,0.10); }
.rd-inp::placeholder { color:#9d87c0; }

.rd-btn-p { width:100%; padding:11px; border:none; border-radius:12px; font-size:14px; font-weight:700; font-family:'Plus Jakarta Sans',sans-serif; cursor:pointer; background:linear-gradient(120deg,#4c1d95 0%, #7c3aed 60%, #f59e0b 130%); color:#fff; margin-top:2px; box-shadow: 0 10px 22px rgba(124,58,237,0.28); transition:all .15s; }
.rd-btn-p:hover { transform: translateY(-1px); box-shadow: 0 14px 28px rgba(124,58,237,0.36); }
.rd-empty-note { color:#9d87c0; font-size:13px; text-align:center; padding:16px 0; }

/* Add book form */
.rd-add-section { padding: 14px 20px 0; }
.rd-add-grid { display:grid; grid-template-columns: repeat(5, 1fr); gap:10px; align-items:end; }

/* Section label */
.rd-section-lbl { padding: 18px 20px 10px; font-family:'Fredoka',sans-serif; font-size:16px; color:#1e0f4a; display:flex; align-items:center; gap:8px; }

/* Currently reading */
.rd-reading-list { padding: 0 20px; display:flex; flex-direction:column; gap:12px; margin-bottom: 6px; }
.rd-book-card { background:#fff; border-radius:16px; border:1.5px solid #e4d9ff; border-left: 4px solid #0ea5e9; padding:16px 18px; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.rd-book-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:12px; }
.rd-book-title { font-size:15px; font-weight:700; color:#1e0f4a; margin-bottom:2px; }
.rd-book-author { font-size:12px; color:#7c6fa0; }
.rd-book-actions { display:flex; gap:8px; align-items:center; }
.rd-badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.rd-delete { text-decoration:none; opacity:.55; font-size:15px; transition:opacity .15s; }
.rd-delete:hover { opacity:1; }
.rd-book-progress-row { display:flex; justify-content:space-between; font-size:11.5px; color:#7c6fa0; margin-bottom:6px; }
.rd-bbar-bg { background:#e4d9ff; border-radius:8px; height:8px; overflow:hidden; }
.rd-bbar-fill { height:100%; border-radius:8px; }

/* All books table */
.rd-tbl-wrap { padding:14px 20px 0; }
.rd-tbl-card { background:#fff; border-radius:18px; border:1.5px solid #e4d9ff; overflow:hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.rd-tbl-hdr  { padding:14px 20px; background:linear-gradient(125deg,#1e0f4a 0%, #2d1472 100%); }
.rd-tbl-hdr h3 { font-family:'Fredoka',sans-serif; font-size:16px; color:#fff; margin:0; }

table.rd-dt { width:100%; border-collapse:collapse; }
.rd-dt thead th { padding:9px 16px; font-size:10.5px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.5px; text-align:left; background:#faf8ff; border-bottom:1.5px solid #e4d9ff; }
.rd-dt tbody td { padding:11px 16px; font-size:13px; border-bottom:1px solid #f1edfb; color:#2e2253; vertical-align: middle; }
.rd-dt tbody tr:last-child td { border-bottom:none; }
.rd-dt tbody tr:hover { background:#faf8ff; }
.rd-mini-bar-bg { background:#e4d9ff; border-radius:6px; height:5px; overflow:hidden; flex:1; }
.rd-mini-bar-fill { height:100%; border-radius:6px; }
.rd-pct { font-size:11px; color:#7c6fa0; font-weight:700; }
.rd-stars { color:#f59e0b; }
.rd-del-btn { border:1px solid #fecaca; background:#fef2f2; color:#b91c1c; font-size:11.5px; padding:5px 10px; border-radius:8px; cursor:pointer; text-decoration:none; }
.rd-empty { text-align:center; padding:36px 20px; color:#9d87c0; font-size:13px; }
</style>

<div class="rd-wrap">

<span class="mascot-bg mascot-bg--1">📚</span>
<span class="mascot-bg mascot-bg--2">📖</span>
<span class="mascot-bg mascot-bg--3">🔖</span>
<span class="mascot-bg mascot-bg--4">✨</span>
<span class="mascot-bg mascot-bg--5">📝</span>
<span class="mascot-bg mascot-bg--6">🌟</span>
<span class="mascot-bg mascot-bg--7">📕</span>
<span class="mascot-bg mascot-bg--8">📗</span>

    <!-- Header -->
    <div class="rd-hdr">
        <div class="rd-hdr-left">
            <div class="rd-hdr-ico">📚</div>
            <div>
                <h2>Reading Tracker</h2>
                <p>Track your books, build your reading habit</p>
            </div>
        </div>
        <div class="rd-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
    </div>

    <?php if ($msg): ?>
    <div class="rd-alert"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="rd-stats">
        <div class="rd-sc o">
            <div class="rd-sc-top">
                <div class="rd-sc-ico">📚</div>
            </div>
            <div class="rd-sc-val"><?php echo $total_books; ?></div>
            <div class="rd-sc-lbl">Total Books</div>
        </div>
        <div class="rd-sc g">
            <div class="rd-sc-top">
                <div class="rd-sc-ico">✅</div>
            </div>
            <div class="rd-sc-val"><?php echo $completed_books; ?></div>
            <div class="rd-sc-lbl">Completed</div>
        </div>
        <div class="rd-sc c">
            <div class="rd-sc-top">
                <div class="rd-sc-ico">📖</div>
            </div>
            <div class="rd-sc-val"><?php echo count($currently_reading); ?></div>
            <div class="rd-sc-lbl">Currently Reading</div>
        </div>
        <div class="rd-sc b">
            <div class="rd-sc-top">
                <div class="rd-sc-ico">📄</div>
            </div>
            <div class="rd-sc-val"><?php echo number_format($pages_this_month); ?></div>
            <div class="rd-sc-lbl">Pages This Month</div>
        </div>
        <div class="rd-sc p">
            <div class="rd-sc-top">
                <div class="rd-sc-ico">⏱️</div>
            </div>
            <div class="rd-sc-val"><?php echo $mins_today; ?>m</div>
            <div class="rd-sc-lbl">Read Today</div>
        </div>
    </div>

    <!-- Weekly Chart + Log Session -->
    <div class="rd-body">
        <div class="rd-card">
            <div class="rd-card-title">📈 Pages Read — Last 7 Days</div>
            <canvas id="readChart" height="160"></canvas>
            <script>
            new Chart(document.getElementById('readChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($week_labels); ?>,
                    datasets: [{
                        label: 'Pages',
                        data: <?php echo json_encode($week_pages); ?>,
                        backgroundColor: 'rgba(124,58,237,0.25)',
                        borderColor: '#7c3aed',
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { color: '#7c6fa0' }, grid: { color: 'rgba(124,58,237,0.06)' } },
                        x: { ticks: { color: '#7c6fa0' }, grid: { display: false } }
                    }
                }
            });
            </script>
        </div>

        <div class="rd-card">
            <div class="rd-card-title">⏱️ Log Reading Session</div>
            <?php if (empty($active_books)): ?>
                <p class="rd-empty-note">No books marked as "Reading" yet — add a book first.</p>
            <?php else: ?>
            <form method="POST">
                <div class="rd-fg">
                    <label class="rd-lbl">Book</label>
                    <select name="book_id" class="rd-sel" required>
                        <?php foreach ($active_books as $b): ?>
                        <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['book_title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rd-fg">
                    <label class="rd-lbl">Pages Read This Session</label>
                    <input type="number" name="pages_this_session" class="rd-inp" min="1" placeholder="e.g. 30" required>
                </div>
                <div class="rd-fg">
                    <label class="rd-lbl">Time Spent (minutes)</label>
                    <input type="number" name="minutes_spent" class="rd-inp" min="1" placeholder="e.g. 45">
                </div>
                <div class="rd-fg">
                    <label class="rd-lbl">Date</label>
                    <input type="date" name="date" class="rd-inp" value="<?php echo $today; ?>" required>
                </div>
                <button type="submit" name="log_session" class="rd-btn-p">Log Session</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add New Book -->
    <div class="rd-add-section">
        <div class="rd-card">
            <div class="rd-card-title">➕ Add New Book</div>
            <form method="POST">
                <div class="rd-add-grid">
                    <div class="rd-fg" style="margin-bottom:0">
                        <label class="rd-lbl">Book Title</label>
                        <input type="text" name="book_title" class="rd-inp" placeholder="e.g. Atomic Habits" required>
                    </div>
                    <div class="rd-fg" style="margin-bottom:0">
                        <label class="rd-lbl">Author</label>
                        <input type="text" name="author" class="rd-inp" placeholder="e.g. James Clear">
                    </div>
                    <div class="rd-fg" style="margin-bottom:0">
                        <label class="rd-lbl">Total Pages</label>
                        <input type="number" name="total_pages" class="rd-inp" min="1" placeholder="320">
                    </div>
                    <div class="rd-fg" style="margin-bottom:0">
                        <label class="rd-lbl">Status</label>
                        <select name="status" class="rd-sel">
                            <option value="reading">Currently Reading</option>
                            <option value="want_to_read">Want to Read</option>
                            <option value="completed">Already Completed</option>
                        </select>
                    </div>
                    <div class="rd-fg" style="margin-bottom:0">
                        <label class="rd-lbl">Started Date</label>
                        <input type="date" name="started_date" class="rd-inp" value="<?php echo $today; ?>">
                    </div>
                </div>
                <button type="submit" name="add_book" class="rd-btn-p" style="margin-top:14px;width:auto;padding:11px 28px;">Add Book</button>
            </form>
        </div>
    </div>

    <?php $reading_now = array_filter($books, fn($b) => $b['status'] === 'reading'); ?>
    <?php if (!empty($reading_now)): ?>
    <!-- Currently Reading -->
    <div class="rd-section-lbl">📖 Currently Reading</div>
    <div class="rd-reading-list">
        <?php foreach ($reading_now as $book): ?>
        <?php
            $progress = ($book['total_pages'] > 0) ? min(100, round($book['pages_read'] / $book['total_pages'] * 100)) : 0;
            $remaining = max(0, $book['total_pages'] - $book['pages_read']);
        ?>
        <div class="rd-book-card">
            <div class="rd-book-top">
                <div>
                    <div class="rd-book-title"><?php echo htmlspecialchars($book['book_title']); ?></div>
                    <div class="rd-book-author"><?php echo htmlspecialchars($book['author']); ?></div>
                </div>
                <div class="rd-book-actions">
                    <span class="rd-badge" style="background:#e0f2fe;color:#0369a1;">Reading</span>
                    <a href="?delete=<?php echo $book['id']; ?>" class="rd-delete" onclick="return confirm('Remove this book?')">🗑️</a>
                </div>
            </div>
            <div class="rd-book-progress-row">
                <span><?php echo $book['pages_read']; ?> / <?php echo $book['total_pages']; ?> pages</span>
                <span><?php echo $progress; ?>% &middot; <?php echo $remaining; ?> pages left</span>
            </div>
            <div class="rd-bbar-bg">
                <div class="rd-bbar-fill" style="width:<?php echo $progress; ?>%;background:#0ea5e9;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- All Books Table -->
    <div class="rd-tbl-wrap">
        <div class="rd-tbl-card">
            <div class="rd-tbl-hdr">
                <h3>Your Library</h3>
            </div>
            <table class="rd-dt">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Rating</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($books)): ?>
                <tr><td colspan="6" style="padding:0;border-bottom:none;"><div class="rd-empty">No books yet — start adding books above 📖</div></td></tr>
                <?php endif; ?>
                <?php foreach ($books as $book): ?>
                <?php $progress = ($book['total_pages'] > 0) ? min(100, round($book['pages_read'] / $book['total_pages'] * 100)) : 0; ?>
                <tr>
                    <td style="font-weight:700;color:#1e0f4a;"><?php echo htmlspecialchars($book['book_title']); ?></td>
                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                    <td style="min-width:130px">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="rd-mini-bar-bg">
                                <div class="rd-mini-bar-fill" style="width:<?php echo $progress; ?>%;background:<?php echo $status_colors[$book['status']]; ?>;"></div>
                            </div>
                            <span class="rd-pct"><?php echo $progress; ?>%</span>
                        </div>
                    </td>
                    <td>
                        <span class="rd-badge" style="background:<?php echo $status_bg[$book['status']]; ?>;color:<?php echo $status_colors[$book['status']]; ?>;">
                            <?php echo $status_labels[$book['status']]; ?>
                        </span>
                    </td>
                    <td class="rd-stars"><?php echo $book['rating'] ? str_repeat('★', $book['rating']) : '—'; ?></td>
                    <td>
                        <a href="?delete=<?php echo $book['id']; ?>" class="rd-del-btn" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.rd-wrap -->

<?php include '../includes/footer.php'; ?>
