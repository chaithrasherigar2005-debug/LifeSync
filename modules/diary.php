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
$today   = date('Y-m-d');
$msg     = '';

/* ---- Upload directory ---- */
$upload_dir = __DIR__ . '/../uploads/diary/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

/* ---- Handle ADD entry ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entry'])) {
    $date    = mysqli_real_escape_string($conn, $_POST['date'] ?? $today);
    $mood    = mysqli_real_escape_string($conn, $_POST['mood'] ?? 'neutral');
    $title   = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
    $content = mysqli_real_escape_string($conn, trim($_POST['content'] ?? ''));
    $image_path = NULL;

    // Handle image upload
    if (isset($_FILES['diary_image']) && $_FILES['diary_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $ftype   = mime_content_type($_FILES['diary_image']['tmp_name']);
        if (in_array($ftype, $allowed) && $_FILES['diary_image']['size'] <= 5*1024*1024) {
            $ext     = pathinfo($_FILES['diary_image']['name'], PATHINFO_EXTENSION);
            $fname   = 'diary_' . $user_id . '_' . time() . '.' . strtolower($ext);
            if (move_uploaded_file($_FILES['diary_image']['tmp_name'], $upload_dir . $fname)) {
                $image_path = mysqli_real_escape_string($conn, 'uploads/diary/' . $fname);
            }
        } else {
            $msg = '⚠️ Image must be JPG/PNG/GIF/WEBP and under 5MB.';
        }
    }

    if ($msg === '' && $content !== '') {
        // Check if entry exists for today
        $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM diary WHERE user_id=$user_id AND date='$date' LIMIT 1"));
        if ($existing) {
            $img_sql = $image_path ? ", image_path='$image_path'" : '';
            mysqli_query($conn, "UPDATE diary SET mood='$mood', title='$title', content='$content'$img_sql WHERE id={$existing['id']}");
            $msg = '✅ Entry updated!';
        } else {
            $ip_val = $image_path ? "'$image_path'" : 'NULL';
            mysqli_query($conn, "INSERT INTO diary (user_id,date,mood,title,content,image_path) VALUES ($user_id,'$date','$mood','$title','$content',$ip_val)");
            $msg = '✅ Entry saved!';
        }
    } elseif ($msg === '') {
        $msg = '⚠️ Please write something before saving.';
    }
}

/* ---- Handle DELETE entry ---- */
if (isset($_GET['delete_entry'])) {
    $eid = intval($_GET['delete_entry']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_path FROM diary WHERE id=$eid AND user_id=$user_id"));
    if ($row) {
        if ($row['image_path'] && file_exists(__DIR__ . '/../' . $row['image_path'])) {
            unlink(__DIR__ . '/../' . $row['image_path']);
        }
        mysqli_query($conn, "DELETE FROM diary WHERE id=$eid AND user_id=$user_id");
    }
    header("Location: diary.php");
    exit;
}

/* ---- Handle DELETE image only ---- */
if (isset($_GET['remove_image'])) {
    $eid = intval($_GET['remove_image']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_path FROM diary WHERE id=$eid AND user_id=$user_id"));
    if ($row && $row['image_path']) {
        if (file_exists(__DIR__ . '/../' . $row['image_path'])) unlink(__DIR__ . '/../' . $row['image_path']);
        mysqli_query($conn, "UPDATE diary SET image_path=NULL WHERE id=$eid AND user_id=$user_id");
    }
    header("Location: diary.php");
    exit;
}

/* ---- Fetch entries ---- */
$entries_res = mysqli_query($conn, "SELECT * FROM diary WHERE user_id=$user_id ORDER BY date DESC LIMIT 30");
$entries = [];
while ($r = mysqli_fetch_assoc($entries_res)) $entries[] = $r;

/* ---- Today's entry (for prefill) ---- */
$today_entry = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM diary WHERE user_id=$user_id AND date='$today' LIMIT 1"));

$moods = [
    'happy'    => ['emoji'=>'😊','label'=>'Happy',    'color'=>'#f59e0b', 'bg'=>'#fffbeb'],
    'excited'  => ['emoji'=>'🤩','label'=>'Excited',  'color'=>'#ec4899', 'bg'=>'#fdf2f8'],
    'grateful' => ['emoji'=>'🙏','label'=>'Grateful', 'color'=>'#7c3aed', 'bg'=>'#f3eeff'],
    'neutral'  => ['emoji'=>'😐','label'=>'Neutral',  'color'=>'#94a3b8', 'bg'=>'#f8fafc'],
    'anxious'  => ['emoji'=>'😰','label'=>'Anxious',  'color'=>'#0ea5e9', 'bg'=>'#e0f2fe'],
    'sad'      => ['emoji'=>'😢','label'=>'Sad',      'color'=>'#3b82f6', 'bg'=>'#eff6ff'],
    'angry'    => ['emoji'=>'😠','label'=>'Angry',    'color'=>'#e74c3c', 'bg'=>'#fef0f0'],
];
$selected_mood = $today_entry['mood'] ?? 'neutral';

// Stats
$total_entries = count($entries);
$mood_counts = array_count_values(array_column($entries, 'mood'));
$top_mood = '';
if (!empty($mood_counts)) {
    arsort($mood_counts);
    $top_mood = array_key_first($mood_counts);
}
// streak: consecutive days with an entry, counting back from today
$streak = 0;
$check_date = $today;
$entry_dates = array_column($entries, 'date');
while (in_array($check_date, $entry_dates)) {
    $streak++;
    $check_date = date('Y-m-d', strtotime("$check_date -1 day"));
}

include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── LifeSync Digital Diary — Midnight Violet × Gold ── */
.dy-wrap { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(160deg, #f8f6ff 0%, #f1ecfe 100%); min-height: 100vh; padding-bottom: 40px; color: #1e0f4a; position: relative; overflow-x: hidden; }

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
.dy-hdr { position:relative; background: linear-gradient(125deg,#1e0f4a 0%, #2d1472 60%, #4c1d95 100%); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; border-radius: 0 0 24px 24px; overflow: hidden; }
.dy-hdr::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,transparent 0%,#f59e0b 30%,#fbbf24 50%,#f59e0b 70%,transparent 100%); }
.dy-hdr-left { display: flex; align-items: center; gap: 14px; }
.dy-hdr-ico { width: 44px; height: 44px; background: #7c3aed; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.dy-hdr h2 { font-family: 'Fredoka', sans-serif; font-size: 22px; color: #fff; margin: 0; line-height: 1; }
.dy-hdr p  { font-size: 12px; color: #c4b5fd; margin: 2px 0 0; }
.dy-avatar { width: 36px; height: 36px; background: #4c1d95; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #c4b5fd; font-size: 14px; font-weight: 700; border: 2px solid #a78bfa44; }

/* Alerts */
.dy-alert { margin: 14px 20px 0; padding: 10px 16px; border-radius: 0 10px 10px 0; font-size: 13px; border-left: 4px solid #7c3aed; background: #f3eeff; color: #1e0f4a; }
.dy-alert.warn { border-left-color: #f87171; background: #fef2f2; color: #b91c1c; }

/* Stat cards */
.dy-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; padding: 18px 20px 0; }
.dy-sc { background: #fff; border-radius: 16px; padding: 16px; border: 1.5px solid #e4d9ff; position: relative; overflow: hidden; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.dy-sc::before { content:''; position:absolute; top:-18px; right:-18px; width:64px; height:64px; border-radius:50%; opacity:.12; }
.dy-sc.b::before { background:#7c3aed; }
.dy-sc.o::before { background:#f59e0b; }
.dy-sc.p::before { background:#ec4899; }
.dy-sc-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.dy-sc-ico { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; }
.dy-sc.b .dy-sc-ico { background:#f3eeff; }
.dy-sc.o .dy-sc-ico { background:#fffbeb; }
.dy-sc.p .dy-sc-ico { background:#fdf2f8; }
.dy-sc-val { font-family:'Fredoka',sans-serif; font-size:22px; color:#1e0f4a; line-height:1; }
.dy-sc-lbl { font-size:11px; color:#7c6fa0; margin-top:3px; text-transform:uppercase; letter-spacing:.5px; }

/* Card */
.dy-body { padding: 16px 20px 0; }
.dy-card { background:#fff; border-radius:18px; border:1.5px solid #e4d9ff; padding:20px; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.dy-card-title { font-family:'Fredoka',sans-serif; font-size:16px; color:#1e0f4a; display:flex; align-items:center; gap:8px; margin-bottom:16px; }

/* Form elements */
.dy-fg { margin-bottom:14px; }
.dy-lbl { display:block; font-size:11px; font-weight:700; color:#3b1f6a; text-transform:uppercase; letter-spacing:.7px; margin-bottom:6px; }
.dy-inp, textarea.dy-inp { width:100%; padding:9px 12px; background:#f3eeff; border:1.5px solid #e4d9ff; border-radius:10px; font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; color:#1e0f4a; outline:none; transition:border-color .15s, background .15s; box-sizing:border-box; }
.dy-inp:focus { border-color:#7c3aed; background:#fff; box-shadow:0 0 0 4px rgba(124,58,237,0.10); }
.dy-inp::placeholder { color:#9d87c0; }
textarea.dy-inp { resize: vertical; font-family: 'Plus Jakarta Sans', sans-serif; }
.dy-word-count { font-size: 11px; color: #9d87c0; text-align: right; margin-top: 4px; }

.dy-btn-p { padding:11px 26px; border:none; border-radius:12px; font-size:14px; font-weight:700; font-family:'Plus Jakarta Sans',sans-serif; cursor:pointer; background:linear-gradient(120deg,#4c1d95 0%, #7c3aed 60%, #f59e0b 130%); color:#fff; box-shadow: 0 10px 22px rgba(124,58,237,0.28); transition:all .15s; }
.dy-btn-p:hover { transform: translateY(-1px); box-shadow: 0 14px 28px rgba(124,58,237,0.36); }

/* Mood selector */
.dy-mood-row { display: flex; gap: 8px; flex-wrap: wrap; }
.dy-mood-btn { display: flex; flex-direction: column; align-items: center; gap: 3px; padding: 9px 12px; border-radius: 12px; border: 1.5px solid #e4d9ff; cursor: pointer; background: #fff; transition: all .15s; min-width: 64px; }
.dy-mood-btn:hover { border-color: var(--mood-color); }
.dy-mood-btn.active { background: var(--mood-bg); border-color: var(--mood-color); }
.dy-mood-emoji { font-size: 21px; }
.dy-mood-lbl { font-size: 10px; font-weight: 700; color: #7c6fa0; }

/* Image upload */
.dy-img-upload-area { border: 1.5px dashed #a78bfa; border-radius: 14px; padding: 22px; text-align: center; cursor: pointer; background: #f8f6ff; margin-bottom: 6px; position: relative; }
.dy-img-upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.dy-img-upload-icon { font-size: 28px; margin-bottom: 6px; }
.dy-img-upload-text { font-size: 12px; color: #7c6fa0; }
.dy-img-upload-text strong { color: #4c1d95; }

.dy-img-preview-wrap { position: relative; display: inline-block; margin-bottom: 10px; }
.dy-img-preview { max-width: 220px; border-radius: 12px; border: 1.5px solid #e4d9ff; display: block; }
.dy-img-preview-remove { position: absolute; top: -8px; right: -8px; width: 26px; height: 26px; border-radius: 50%; border: none; background: #f87171; color: #fff; font-size: 13px; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
.dy-replace-note { font-size: 11px; color: #7c6fa0; margin-bottom: 10px; }

/* Section label */
.dy-section-lbl { padding: 18px 20px 10px; font-family:'Fredoka',sans-serif; font-size:16px; color:#1e0f4a; display:flex; align-items:center; gap:8px; }

/* Past entries */
.dy-entries-wrap { padding: 0 20px; display: flex; flex-direction: column; gap: 12px; }
.dy-entry-card { background: #fff; border: 1.5px solid #e4d9ff; border-left: 4px solid var(--mood-border); border-radius: 16px; padding: 16px 18px; box-shadow: 0 2px 8px rgba(76,29,149,0.06); }
.dy-entry-hdr { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
.dy-entry-date { font-size: 11px; color: #7c6fa0; text-transform: uppercase; letter-spacing: .4px; }
.dy-entry-title { font-family: 'Fredoka', sans-serif; font-size: 15px; color: #1e0f4a; margin-top: 2px; }
.dy-mood-pill { font-size: 11px; font-weight: 700; padding: 4px 11px; border-radius: 20px; white-space: nowrap; }
.dy-entry-content { font-size: 13px; color: #2e2253; line-height: 1.55; margin-bottom: 10px; white-space: pre-wrap; }
.dy-entry-image img { max-width: 100%; border-radius: 12px; cursor: pointer; margin-bottom: 10px; }
.dy-entry-actions { display: flex; gap: 8px; }
.dy-entry-action-btn { border: none; background: #f3eeff; color: #4c1d95; font-size: 11.5px; padding: 5px 11px; border-radius: 8px; cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif; }
.dy-entry-action-btn.danger { background: #fef2f2; color: #b91c1c; }
.dy-empty { text-align: center; padding: 36px 20px; color: #9d87c0; font-size: 13px; }

/* Lightbox */
.dy-lightbox { position: fixed; inset: 0; background: rgba(30,15,74,0.88); display: none; align-items: center; justify-content: center; z-index: 9999; }
.dy-lightbox.open { display: flex; }
.dy-lightbox img { max-width: 92%; max-height: 88%; border-radius: 10px; }
.dy-lightbox-close { position: absolute; top: 20px; right: 24px; background: #fff; color: #1e0f4a; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 16px; cursor: pointer; }
</style>

<div class="dy-wrap">

<span class="mascot-bg mascot-bg--1">📔</span>
<span class="mascot-bg mascot-bg--2">✍️</span>
<span class="mascot-bg mascot-bg--3">💭</span>
<span class="mascot-bg mascot-bg--4">✨</span>
<span class="mascot-bg mascot-bg--5">🌸</span>
<span class="mascot-bg mascot-bg--6">📸</span>
<span class="mascot-bg mascot-bg--7">🖋️</span>
<span class="mascot-bg mascot-bg--8">💜</span>

    <!-- Header -->
    <div class="dy-hdr">
        <div class="dy-hdr-left">
            <div class="dy-hdr-ico">📔</div>
            <div>
                <h2>Digital Diary</h2>
                <p><?php echo date('l, d F Y'); ?></p>
            </div>
        </div>
        <div class="dy-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
    </div>

    <?php if ($msg): ?>
    <div class="dy-alert <?php echo str_contains($msg,'✅') ? '' : 'warn'; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="dy-stats">
        <div class="dy-sc b">
            <div class="dy-sc-top">
                <div class="dy-sc-ico">📔</div>
            </div>
            <div class="dy-sc-val"><?php echo $total_entries; ?></div>
            <div class="dy-sc-lbl">Total Entries</div>
        </div>
        <div class="dy-sc o">
            <div class="dy-sc-top">
                <div class="dy-sc-ico">🔥</div>
            </div>
            <div class="dy-sc-val"><?php echo $streak; ?></div>
            <div class="dy-sc-lbl">Day Streak</div>
        </div>
        <div class="dy-sc p">
            <div class="dy-sc-top">
                <div class="dy-sc-ico"><?php echo $top_mood ? $moods[$top_mood]['emoji'] : '😐'; ?></div>
            </div>
            <div class="dy-sc-val" style="font-size:17px;"><?php echo $top_mood ? $moods[$top_mood]['label'] : '—'; ?></div>
            <div class="dy-sc-lbl">Most Common Mood</div>
        </div>
    </div>

    <!-- Write/Edit Entry -->
    <div class="dy-body">
        <div class="dy-card">
            <div class="dy-card-title"><?php echo $today_entry ? '✏️ Edit Today\'s Entry' : '✍️ Write Today\'s Entry'; ?></div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="save_entry" value="1">

                <div class="dy-fg">
                    <label class="dy-lbl">Date</label>
                    <input type="date" name="date" class="dy-inp" value="<?php echo $today; ?>">
                </div>

                <div class="dy-fg">
                    <label class="dy-lbl">How are you feeling?</label>
                    <div class="dy-mood-row" id="moodRow">
                        <?php foreach ($moods as $key => $m): ?>
                        <div class="dy-mood-btn <?php echo $selected_mood===$key?'active':''; ?>"
                             style="--mood-color:<?php echo $m['color']; ?>;--mood-bg:<?php echo $m['bg']; ?>"
                             onclick="selectMood('<?php echo $key; ?>',this)">
                            <span class="dy-mood-emoji"><?php echo $m['emoji']; ?></span>
                            <span class="dy-mood-lbl"><?php echo $m['label']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="mood" id="moodInput" value="<?php echo htmlspecialchars($selected_mood); ?>">
                </div>

                <div class="dy-fg">
                    <label class="dy-lbl">Title (optional)</label>
                    <input type="text" name="title" class="dy-inp" placeholder="Give today a headline…" value="<?php echo htmlspecialchars($today_entry['title'] ?? ''); ?>">
                </div>

                <div class="dy-fg">
                    <label class="dy-lbl">Your thoughts *</label>
                    <textarea name="content" id="diaryContent" rows="5" class="dy-inp" placeholder="Write about your day, thoughts, feelings…"><?php echo htmlspecialchars($today_entry['content'] ?? ''); ?></textarea>
                    <div class="dy-word-count" id="wordCount">0 words</div>
                </div>

                <!-- Image upload -->
                <div class="dy-fg">
                    <label class="dy-lbl">📸 Add a photo (optional)</label>

                    <?php if (!empty($today_entry['image_path'])): ?>
                    <!-- Existing image -->
                    <div class="dy-img-preview-wrap" id="existingImgWrap">
                        <img src="<?php echo htmlspecialchars('../'.$today_entry['image_path']); ?>" class="dy-img-preview" alt="diary image">
                        <a href="?remove_image=<?php echo $today_entry['id']; ?>" onclick="return confirm('Remove image?')">
                            <button type="button" class="dy-img-preview-remove">✕</button>
                        </a>
                    </div>
                    <div class="dy-replace-note">Replace with a new image:</div>
                    <?php endif; ?>

                    <!-- Upload area -->
                    <div class="dy-img-upload-area" id="uploadArea" onclick="document.getElementById('diaryImage').click()">
                        <div class="dy-img-upload-icon">🖼️</div>
                        <div class="dy-img-upload-text">
                            <strong>Tap to choose from gallery</strong><br>
                            JPG, PNG, GIF, WEBP &middot; Max 5MB
                        </div>
                        <input type="file" name="diary_image" id="diaryImage" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewImage(this)">
                    </div>

                    <!-- New image preview -->
                    <div id="newImgPreview" style="display:none;margin-top:10px;">
                        <div style="font-size:11px;color:#7c6fa0;margin-bottom:6px;">Preview:</div>
                        <div class="dy-img-preview-wrap">
                            <img id="previewImg" src="" alt="preview" class="dy-img-preview">
                            <button type="button" class="dy-img-preview-remove" onclick="clearImagePreview()">✕</button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="dy-btn-p">
                    <?php echo $today_entry ? '💾 Update Entry' : '💾 Save Entry'; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Past Entries -->
    <div class="dy-section-lbl">📚 Past Entries</div>
    <div class="dy-entries-wrap">
        <?php if (empty($entries)): ?>
        <div class="dy-empty">No diary entries yet — start writing above 📔</div>
        <?php else: ?>
        <?php foreach ($entries as $e):
            $m = $moods[$e['mood']] ?? $moods['neutral'];
        ?>
        <div class="dy-entry-card" style="--mood-border:<?php echo $m['color']; ?>">
            <div class="dy-entry-hdr">
                <div>
                    <div class="dy-entry-date"><?php echo date('D, d M Y', strtotime($e['date'])); ?></div>
                    <?php if ($e['title']): ?>
                    <div class="dy-entry-title"><?php echo htmlspecialchars($e['title']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="dy-mood-pill" style="background:<?php echo $m['bg']; ?>;color:<?php echo $m['color']; ?>">
                    <?php echo $m['emoji']; ?> <?php echo $m['label']; ?>
                </div>
            </div>
            <div class="dy-entry-content"><?php
                $preview = mb_strlen($e['content']) > 200 ? mb_substr($e['content'],0,200).'…' : $e['content'];
                echo htmlspecialchars($preview);
            ?></div>

            <?php if (!empty($e['image_path'])): ?>
            <div class="dy-entry-image">
                <img src="<?php echo htmlspecialchars('../'.$e['image_path']); ?>"
                     alt="diary photo"
                     onclick="openLightbox('<?php echo htmlspecialchars('../'.$e['image_path']); ?>')">
            </div>
            <?php endif; ?>

            <div class="dy-entry-actions">
                <?php if (!empty($e['image_path'])): ?>
                <a href="?remove_image=<?php echo $e['id']; ?>" onclick="return confirm('Remove image from this entry?')">
                    <button class="dy-entry-action-btn danger">🗑 Remove photo</button>
                </a>
                <?php endif; ?>
                <a href="?delete_entry=<?php echo $e['id']; ?>" onclick="return confirm('Delete this entry permanently?')">
                    <button class="dy-entry-action-btn danger">🗑 Delete entry</button>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div><!-- /.dy-wrap -->

<!-- Lightbox -->
<div class="dy-lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="dy-lightbox-close" onclick="closeLightbox()">✕</button>
    <img id="lightboxImg" src="" alt="">
</div>

<script>
function selectMood(key, el) {
    document.getElementById('moodInput').value = key;
    document.querySelectorAll('.dy-mood-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('newImgPreview').style.display = 'block';
            document.getElementById('uploadArea').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function clearImagePreview() {
    document.getElementById('diaryImage').value = '';
    document.getElementById('newImgPreview').style.display = 'none';
    document.getElementById('uploadArea').style.display = 'block';
}

// Word counter
const diaryContent = document.getElementById('diaryContent');
const wordCount = document.getElementById('wordCount');
function updateWordCount() {
    const words = diaryContent.value.trim().split(/\s+/).filter(w => w.length > 0);
    wordCount.textContent = words.length + ' word' + (words.length !== 1 ? 's' : '');
}
diaryContent.addEventListener('input', updateWordCount);
updateWordCount();

// Lightbox
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('open');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
}
</script>

<?php include '../includes/footer.php'; ?>
