<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$today   = date('Y-m-d');
$month   = date('Y-m');

$total_habits  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM habits WHERE user_id=$user_id"))['c'];
$done_habits   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM habit_logs WHERE user_id=$user_id AND date='$today' AND completed=1"))['c'];
$habit_score   = $total_habits > 0 ? ($done_habits/$total_habits)*40 : 0;

$sleep_row     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT hours_slept FROM sleep_logs WHERE user_id=$user_id AND date='$today' ORDER BY id DESC LIMIT 1"));
$sleep_hrs     = $sleep_row ? floatval($sleep_row['hours_slept']) : 0;
$sleep_score   = min(($sleep_hrs/8)*30, 30);

$workout_done  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM workouts WHERE user_id=$user_id AND date='$today'"))['c'] > 0;
$workout_score = $workout_done ? 20 : 0;

$budget_row    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT limit_amount FROM budget_limits WHERE user_id=$user_id AND month='$month' LIMIT 1"));
$budget_limit  = $budget_row ? floatval($budget_row['limit_amount']) : 0;
$spent         = floatval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(amount) t FROM expenses WHERE user_id=$user_id AND type='expense' AND date LIKE '$month%'"))['t'] ?? 0);
$budget_penalty = ($budget_limit > 0 && $spent > $budget_limit) ? -10 : 0;

$score = max(0, min(100, round($habit_score + $sleep_score + $workout_score + $budget_penalty)));

if      ($score >= 80) { $slabel='Excellent';  $scolor='#4ade80'; }
elseif  ($score >= 60) { $slabel='Good';       $scolor='#60a5fa'; }
elseif  ($score >= 40) { $slabel='Average';    $scolor='#fbbf24'; }
else                   { $slabel='Needs Work'; $scolor='#f87171'; }

$r = 28; $circ = 2*M_PI*$r;
$dash = round(($score/100)*$circ, 2);

$expense_today  = floatval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(amount) t FROM expenses WHERE user_id=$user_id AND type='expense' AND date='$today'"))['t']??0);
$income_month   = floatval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(amount) t FROM expenses WHERE user_id=$user_id AND type='income' AND date LIKE '$month%'"))['t']??0);
$calories_today = intval(mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(calories) t FROM meals WHERE user_id=$user_id AND date='$today'"))['t']??0);
$mood_today     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT mood FROM diary WHERE user_id=$user_id AND date='$today' ORDER BY id DESC LIMIT 1"))['mood']??null;
$reading_book   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT book_title,pages_read,total_pages FROM reading_logs WHERE user_id=$user_id AND status='reading' ORDER BY created_at DESC LIMIT 1"));

$mood_emoji = ['happy'=>'😊','sad'=>'😢','anxious'=>'😰','excited'=>'🤩','neutral'=>'😐','angry'=>'😠','grateful'=>'🙏'];
$hour  = (int)date('H');
$greet = $hour<12 ? 'Good morning' : ($hour<17 ? 'Good afternoon' : 'Good evening');
$user_initials = strtoupper(substr($_SESSION['user_name'], 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — LifeSync</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
html{color-scheme:dark only;}

body{
    font-family:'Plus Jakarta Sans',sans-serif;
    min-height:100vh;
    background:linear-gradient(135deg,#020817 0%,#0a1430 22%,#0f1f4a 45%,#13265c 65%,#0b1838 100%);
    position:relative;
    overflow-x:hidden;
    display:flex;
    flex-direction:column;
    align-items:center;
    padding:40px 20px 80px;
}

/* ── Same background decorations as login ── */
.bg-dots{
    position:fixed;inset:0;
    background-image:radial-gradient(rgba(96,165,250,0.20) 1.2px,transparent 1.2px);
    background-size:28px 28px;
    -webkit-mask-image:radial-gradient(ellipse 70% 60% at 30% 20%,#000 0%,rgba(0,0,0,0.3) 55%,transparent 85%);
    mask-image:radial-gradient(ellipse 70% 60% at 30% 20%,#000 0%,rgba(0,0,0,0.3) 55%,transparent 85%);
    pointer-events:none;z-index:0;
}
.bg-orb{position:fixed;border-radius:50%;filter:blur(90px);pointer-events:none;z-index:0;}
.bg-orb--1{width:460px;height:460px;background:rgba(37,99,235,0.28);top:-160px;left:-130px;}
.bg-orb--2{width:380px;height:380px;background:rgba(245,158,11,0.12);bottom:-130px;right:-110px;}
.bg-orb--3{width:280px;height:280px;background:rgba(96,165,250,0.10);top:40%;right:6%;}
.float-leaf{position:fixed;font-size:30px;opacity:0.25;z-index:0;filter:drop-shadow(0 4px 10px rgba(80,20,160,0.4));animation:floatLeaf 9s ease-in-out infinite;pointer-events:none;}
.fl-1{top:7%;left:6%;animation-delay:0s;}
.fl-2{bottom:13%;left:8%;animation-delay:2s;font-size:24px;}
.fl-3{top:13%;right:7%;animation-delay:1s;}
.fl-4{bottom:9%;right:9%;animation-delay:3s;font-size:26px;}
.fl-5{top:46%;left:3%;animation-delay:1.6s;font-size:20px;opacity:0.14;}
.fl-6{top:50%;right:4%;animation-delay:2.6s;font-size:20px;opacity:0.14;}
@keyframes floatLeaf{0%,100%{transform:translateY(0) rotate(0deg);}50%{transform:translateY(-18px) rotate(12deg);}}

/* ── Animated cartoon mascots — spread across full viewport ── */
.mascot-bg{position:fixed;z-index:0;pointer-events:none;filter:drop-shadow(0 6px 14px rgba(80,20,160,0.35));}
.mascot-bg--1{top:8%;left:3%;font-size:46px;opacity:0.20;animation:mascotFloat 7s ease-in-out infinite;}
.mascot-bg--2{bottom:6%;right:3%;font-size:42px;opacity:0.18;animation:mascotFloat 8s ease-in-out infinite 1.2s;}
.mascot-bg--3{top:22%;right:2%;font-size:36px;opacity:0.16;animation:mascotFloat 9s ease-in-out infinite .6s;}
.mascot-bg--4{top:62%;left:1.5%;font-size:40px;opacity:0.17;animation:mascotFloat 7.5s ease-in-out infinite 2s;}
.mascot-bg--5{bottom:38%;right:4%;font-size:30px;opacity:0.15;animation:mascotFloat 8.5s ease-in-out infinite 1.6s;}
.mascot-bg--6{top:2%;right:14%;font-size:28px;opacity:0.15;animation:mascotFloat 6.5s ease-in-out infinite .9s;}
.mascot-bg--7{bottom:2%;left:12%;font-size:32px;opacity:0.15;animation:mascotFloat 9.5s ease-in-out infinite 2.4s;}
.mascot-bg--8{top:84%;right:16%;font-size:26px;opacity:0.13;animation:mascotFloat 7.2s ease-in-out infinite 1.1s;}
.mascot-bg--9{top:48%;left:10%;font-size:24px;opacity:0.12;animation:mascotFloat 8.8s ease-in-out infinite .4s;}
.mascot-bg--10{bottom:14%;left:2%;font-size:34px;opacity:0.16;animation:mascotFloat 7.8s ease-in-out infinite 1.8s;}
.mascot-bg--11{top:4%;left:18%;font-size:30px;opacity:0.14;animation:mascotFloat 8.2s ease-in-out infinite .3s;}
.mascot-bg--12{bottom:4%;right:18%;font-size:32px;opacity:0.15;animation:mascotFloat 7.4s ease-in-out infinite 1.4s;}
.mascot-bg--13{top:38%;right:1%;font-size:28px;opacity:0.13;animation:mascotFloat 9.2s ease-in-out infinite 2.1s;}
.mascot-bg--14{top:14%;left:1%;font-size:24px;opacity:0.12;animation:mascotFloat 6.8s ease-in-out infinite .7s;}
.mascot-bg--15{bottom:24%;left:0.5%;font-size:26px;opacity:0.13;animation:mascotFloat 8.6s ease-in-out infinite 1.9s;}
.mascot-bg--16{top:90%;left:8%;font-size:22px;opacity:0.11;animation:mascotFloat 7.6s ease-in-out infinite 1s;}
.mascot-bg--17{top:0.5%;right:0.5%;font-size:20px;opacity:0.10;animation:mascotFloat 9.8s ease-in-out infinite 2.6s;}
.mascot-bg--18{bottom:0.5%;right:0.5%;font-size:22px;opacity:0.11;animation:mascotFloat 6.2s ease-in-out infinite .5s;}
@keyframes mascotFloat{0%,100%{transform:translateY(0) rotate(-4deg);}50%{transform:translateY(-22px) rotate(4deg);}}

.greeting-mascot{
    display:inline-block;font-size:30px;margin-left:6px;
    animation:mascotWave 1.8s ease-in-out infinite;
    transform-origin:70% 70%;
}
@keyframes mascotWave{
    0%,100%{transform:rotate(0deg);}
    15%{transform:rotate(14deg);}
    30%{transform:rotate(-8deg);}
    45%{transform:rotate(14deg);}
    60%{transform:rotate(-4deg);}
    75%{transform:rotate(0deg);}
}

.kpi-mascot{
    display:block;font-size:20px;margin-bottom:2px;
    animation:mascotBounce 2.4s ease-in-out infinite;
}
@keyframes mascotBounce{
    0%,100%{transform:translateY(0) scale(1);}
    50%{transform:translateY(-5px) scale(1.08);}
}
.kpi-card:nth-child(2) .kpi-mascot{animation-delay:.15s;}
.kpi-card:nth-child(3) .kpi-mascot{animation-delay:.3s;}
.kpi-card:nth-child(4) .kpi-mascot{animation-delay:.45s;}
.kpi-card:nth-child(5) .kpi-mascot{animation-delay:.6s;}
.kpi-card:nth-child(6) .kpi-mascot{animation-delay:.75s;}

/* ── Top nav bar ── */
.top-nav{
    position:relative;z-index:10;
    width:100%;max-width:700px;
    display:flex;align-items:center;justify-content:space-between;
    margin-bottom:24px;
}
.top-nav__brand{
    font-family:'Fredoka',sans-serif;font-size:28px;font-weight:700;
    display:flex;align-items:center;gap:8px;
}
.top-nav__brand .life-part{color:#f0f4ff;}
.top-nav__brand .sync-part{color:#60a5fa;}
.top-nav__avatar{
    width:40px;height:40px;border-radius:50%;
    background:linear-gradient(135deg,#2563eb,#f59e0b);
    display:flex;align-items:center;justify-content:center;
    font-size:14px;font-weight:700;color:#fff;
    border:2px solid rgba(96,165,250,0.35);
    cursor:pointer;position:relative;
}
.top-nav__menu-btn{
    background:rgba(255,255,255,0.06);border:1px solid rgba(96,165,250,0.22);
    border-radius:12px;padding:8px 12px;cursor:pointer;
    color:rgba(147,197,253,0.70);font-size:13px;font-weight:600;
    font-family:inherit;display:flex;align-items:center;gap:6px;
    transition:background .15s;
}
.top-nav__menu-btn:hover{background:rgba(96,165,250,0.15);}

/* ── Gold shimmer top line (same as login card) ── */
.shimmer-line{
    position:relative;z-index:1;
    width:100%;max-width:700px;height:3px;
    background:linear-gradient(90deg,transparent 0%,#f59e0b 30%,#fbbf24 50%,#f59e0b 70%,transparent 100%);
    border-radius:3px 3px 0 0;
}

/* ── The main card — matches auth-container style ── */
.dash-card{
    position:relative;z-index:1;
    width:100%;max-width:700px;
    background:#0a1228;
    border-radius:0 0 34px 34px;
    overflow:hidden;
    box-shadow:
        0 0 0 1px rgba(96,165,250,0.18),
        0 40px 100px rgba(0,0,0,0.65),
        0 0 60px rgba(37,99,235,0.18);
}

/* ── Dark header band — like auth-top-band ── */
.dash-header{
    background:linear-gradient(160deg,#0a1430 0%,#13265c 55%,#1a3470 100%);
    padding:28px 28px 24px;
    display:flex;align-items:center;justify-content:space-between;
    gap:16px;flex-wrap:wrap;
}
.dash-greeting-tag{
    font-size:10.5px;font-weight:700;letter-spacing:.12em;
    text-transform:uppercase;color:rgba(245,158,11,0.85);margin-bottom:4px;
}
.dash-name{
    font-family:'Fredoka',sans-serif;font-size:26px;font-weight:700;
    color:#f0f4ff;line-height:1.1;
}
.dash-date{font-size:12.5px;color:rgba(147,197,253,0.50);margin-top:3px;}

/* Score ring — same pill style as login elements */
.score-pill{
    display:flex;align-items:center;gap:14px;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(96,165,250,0.20);
    border-radius:20px;padding:12px 18px;
    flex-shrink:0;
}
.score-ring-wrap{position:relative;width:66px;height:66px;flex-shrink:0;}
.score-ring-wrap svg{width:100%;height:100%;}
.score-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.score-num{font-family:'Fredoka',sans-serif;font-size:19px;font-weight:700;line-height:1;}
.score-denom{font-size:9px;color:rgba(147,197,253,0.45);line-height:1;}
.score-right-label{font-family:'Fredoka',sans-serif;font-size:17px;font-weight:700;line-height:1.1;}
.score-right-sub{font-size:11px;color:rgba(147,197,253,0.50);margin-top:2px;}

/* ── Body of the card ── */
.dash-body{padding:24px 28px 30px;display:flex;flex-direction:column;gap:22px;}

/* Section label — like auth-badge but subtle */
.section-label{
    font-size:10px;font-weight:700;letter-spacing:.13em;
    text-transform:uppercase;color:rgba(96,165,250,0.50);
    margin-bottom:-10px;
}

/* ── KPI grid ── */
.kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
.kpi-card{
    padding:14px 14px 12px;
    border-radius:18px;
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(96,165,250,0.10);
    cursor:pointer;
    transition:transform .15s,border-color .15s,background .15s;
    position:relative;overflow:hidden;
}
.kpi-card:hover{transform:translateY(-2px);background:rgba(96,165,250,0.08);border-color:rgba(96,165,250,0.24);}
.kpi-icon{font-size:20px;display:block;margin-bottom:8px;}
.kpi-value{font-family:'Fredoka',sans-serif;font-size:1.35rem;font-weight:700;line-height:1;margin-bottom:3px;}
.kpi-label{font-size:11px;color:rgba(147,197,253,0.55);font-weight:500;}
.kpi-card.red   .kpi-value{color:#f87171;}
.kpi-card.green .kpi-value{color:#4ade80;}
.kpi-card.amber .kpi-value{color:#fbbf24;}
.kpi-card.cyan  .kpi-value{color:#22d3ee;}
.kpi-card.purple .kpi-value{color:#60a5fa;}
.kpi-card.pink  .kpi-value{color:#f472b6;}

/* ── Breakdown card — like .card in auth page ── */
.inner-card{
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(96,165,250,0.12);
    border-radius:20px;
    padding:18px 18px 16px;
}
.inner-card h3{
    font-family:'Fredoka',sans-serif;font-size:15px;font-weight:600;
    color:#e9e0ff;margin-bottom:14px;
}
.breakdown-row{display:flex;flex-direction:column;gap:11px;}
.breakdown-tile-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;}
.breakdown-tile-name{font-size:12.5px;font-weight:600;color:rgba(147,197,253,0.75);}
.breakdown-tile-score{font-size:12.5px;font-weight:700;}
.bar{height:6px;border-radius:999px;background:rgba(96,165,250,0.10);overflow:hidden;}
.bar-fill{height:100%;border-radius:999px;transition:width .6s cubic-bezier(.4,0,.2,1);}

/* ── Reading banner — like the amber-bordered card on login ── */
.reading-banner{
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(245,158,11,0.22);
    border-left:3px solid #f59e0b;
    border-radius:20px;
    padding:16px 18px;
}
.reading-eyebrow{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.10em;color:#f59e0b;margin-bottom:.2rem;}
.reading-title{font-size:.95rem;font-weight:700;color:#f0f4ff;margin-bottom:.75rem;}
.reading-meta{display:flex;justify-content:space-between;font-size:.72rem;color:rgba(147,197,253,.50);margin-bottom:.4rem;}
.btn-sm{
    display:inline-flex;align-items:center;
    padding:7px 14px;border-radius:11px;font-size:12px;font-weight:700;
    font-family:inherit;cursor:pointer;text-decoration:none;
    background:rgba(96,165,250,0.12);color:#c4b5fd;
    border:1px solid rgba(96,165,250,0.22);
    transition:background .15s;white-space:nowrap;
}
.btn-sm:hover{background:rgba(96,165,250,0.22);}
.reading-top{display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.8rem;}

/* ── Module shortcuts — like feature-card on login ── */
.shortcuts{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
.shortcut{
    display:flex;flex-direction:column;align-items:center;text-align:center;
    padding:16px 8px 13px;
    border-radius:20px;
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(96,165,250,0.10);
    text-decoration:none;
    transition:transform .15s,border-color .15s,background .15s;
}
.shortcut:hover{transform:translateY(-3px);background:rgba(96,165,250,0.10);border-color:rgba(96,165,250,0.28);}
.shortcut-icon{
    width:50px;height:50px;border-radius:16px;
    display:flex;align-items:center;justify-content:center;
    font-size:22px;margin-bottom:9px;
    border:1px solid rgba(96,165,250,0.13);
    background:rgba(255,255,255,0.04);
}
.shortcut-name{font-family:'Fredoka',sans-serif;font-size:13.5px;font-weight:600;color:#c4b5fd;margin-bottom:2px;}
.shortcut-desc{font-size:10.5px;color:rgba(147,197,253,0.40);line-height:1.3;}

/* ── Bottom nav dots (like auth-dots) ── */
.dash-dots{
    display:flex;justify-content:center;gap:8px;
    margin-top:28px;position:relative;z-index:1;
}
.dash-dots span{width:7px;height:7px;border-radius:50%;background:rgba(96,165,250,0.25);}
.dash-dots span.active{width:22px;border-radius:4px;background:linear-gradient(90deg,#2563eb,#f59e0b);}

/* ── Divider ── */
.divider{height:1px;background:rgba(96,165,250,0.10);margin:0 -28px;}

/* ── Logout link ── */
.nav-footer{
    display:flex;justify-content:space-between;align-items:center;
    padding:16px 28px 20px;
    background:linear-gradient(160deg,#0a1430 0%,#0f1f4a 100%);
}
.nav-footer a{
    font-size:12.5px;font-weight:600;color:rgba(147,197,253,0.50);
    text-decoration:none;transition:color .15s;
}
.nav-footer a:hover{color:#c4b5fd;}
.nav-footer__links{display:flex;gap:20px;}

@media(max-width:520px){
    body{padding:20px 12px 60px;}
    .kpi-grid{grid-template-columns:repeat(2,1fr);}
    .shortcuts{grid-template-columns:repeat(3,1fr);}
    .dash-header{gap:12px;}
    .score-pill{width:100%;}
    .dash-body{padding:20px 18px 24px;}
    .divider{margin:0 -18px;}
    .nav-footer{padding:14px 18px 18px;}
}
</style>
</head>
<body>

<!-- Same background as login page -->
<div class="bg-dots"></div>
<div class="bg-orb bg-orb--1"></div>
<div class="bg-orb bg-orb--2"></div>
<div class="bg-orb bg-orb--3"></div>
<span class="float-leaf fl-1">🌿</span>
<span class="float-leaf fl-2">🍃</span>
<span class="float-leaf fl-3">🌿</span>
<span class="float-leaf fl-4">🍀</span>
<span class="float-leaf fl-5">✨</span>
<span class="float-leaf fl-6">✨</span>
<span class="mascot-bg mascot-bg--1">🦉</span>
<span class="mascot-bg mascot-bg--2">🐢</span>
<span class="mascot-bg mascot-bg--3">🐝</span>
<span class="mascot-bg mascot-bg--4">🦋</span>
<span class="mascot-bg mascot-bg--5">🐿️</span>
<span class="mascot-bg mascot-bg--6">⭐</span>
<span class="mascot-bg mascot-bg--7">🍄</span>
<span class="mascot-bg mascot-bg--8">🐞</span>
<span class="mascot-bg mascot-bg--9">🌸</span>
<span class="mascot-bg mascot-bg--10">🐦</span>
<span class="mascot-bg mascot-bg--11">🐌</span>
<span class="mascot-bg mascot-bg--12">🦔</span>
<span class="mascot-bg mascot-bg--13">🐬</span>
<span class="mascot-bg mascot-bg--14">🍂</span>
<span class="mascot-bg mascot-bg--15">🐙</span>
<span class="mascot-bg mascot-bg--16">🦄</span>
<span class="mascot-bg mascot-bg--17">✨</span>
<span class="mascot-bg mascot-bg--18">✨</span>

<!-- Top nav (outside card, like login page top area) -->
<div class="top-nav">
    <div class="top-nav__brand">
        <span class="life-part">Life</span><span class="sync-part">Sync</span>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="logout.php" class="top-nav__menu-btn">🚪 Log out</a>
        <div class="top-nav__avatar"><?php echo $user_initials; ?></div>
    </div>
</div>

<!-- Gold shimmer line — identical to top of auth-container -->
<div class="shimmer-line"></div>

<!-- THE MAIN CARD -->
<div class="dash-card">

    <!-- Dark header band — matches auth-top-band -->
    <div class="dash-header">
        <div>
            <div class="dash-greeting-tag"><?php echo $greet; ?></div>
            <div class="dash-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋<span class="greeting-mascot">🐨</span></div>
            <div class="dash-date"><?php echo date('l, d F Y'); ?></div>
        </div>

        <div class="score-pill">
            <div class="score-ring-wrap">
                <svg viewBox="0 0 66 66">
                    <circle cx="33" cy="33" r="<?php echo $r;?>" fill="none" stroke="rgba(96,165,250,0.15)" stroke-width="6"/>
                    <circle cx="33" cy="33" r="<?php echo $r;?>" fill="none"
                        stroke="<?php echo $scolor;?>" stroke-width="6"
                        stroke-linecap="round"
                        stroke-dasharray="<?php echo $dash.' '.round($circ,2);?>"
                        transform="rotate(-90 33 33)"/>
                </svg>
                <div class="score-center">
                    <div class="score-num" style="color:<?php echo $scolor;?>"><?php echo $score;?></div>
                    <div class="score-denom">/100</div>
                </div>
            </div>
            <div>
                <div class="score-right-label" style="color:<?php echo $scolor;?>"><?php echo $slabel;?></div>
                <div class="score-right-sub">Productivity Score</div>
            </div>
        </div>
    </div>

    <!-- White body area — matches auth-form-area -->
    <div class="dash-body">

        <!-- KPI CARDS -->
        <p class="section-label">Today at a glance</p>
        <div class="kpi-grid">
            <div class="kpi-card red" onclick="location.href='modules/expenses.php'">
                <span class="kpi-mascot">🐷</span>
                <span class="kpi-icon">💸</span>
                <div class="kpi-value">₹<?php echo number_format($expense_today,0);?></div>
                <div class="kpi-label">Spent Today</div>
            </div>
            <div class="kpi-card green" onclick="location.href='modules/expenses.php'">
                <span class="kpi-mascot">🐸</span>
                <span class="kpi-icon">📈</span>
                <div class="kpi-value">₹<?php echo number_format($income_month,0);?></div>
                <div class="kpi-label">Income <?php echo date('M');?></div>
            </div>
            <div class="kpi-card amber" onclick="location.href='modules/food.php'">
                <span class="kpi-mascot">🐹</span>
                <span class="kpi-icon">🥗</span>
                <div class="kpi-value"><?php echo $calories_today;?></div>
                <div class="kpi-label">Calories</div>
            </div>
            <div class="kpi-card cyan" onclick="location.href='modules/sleep.php'">
                <span class="kpi-mascot">🐼</span>
                <span class="kpi-icon">😴</span>
                <div class="kpi-value"><?php echo $sleep_hrs>0 ? $sleep_hrs.'h' : '—';?></div>
                <div class="kpi-label">Sleep</div>
            </div>
            <div class="kpi-card purple" onclick="location.href='modules/habits.php'">
                <span class="kpi-mascot">🐰</span>
                <span class="kpi-icon">✅</span>
                <div class="kpi-value"><?php echo $done_habits;?><span style="font-size:.85rem;font-weight:500;color:rgba(147,197,253,.45)">/<?php echo $total_habits;?></span></div>
                <div class="kpi-label">Habits</div>
            </div>
            <div class="kpi-card pink" onclick="location.href='modules/diary.php'">
                <span class="kpi-mascot">🦊</span>
                <span class="kpi-icon"><?php echo $mood_today ? ($mood_emoji[$mood_today]??'📔') : '📔';?></span>
                <div class="kpi-value" style="font-size:.9rem;font-weight:700;letter-spacing:0;"><?php echo $mood_today ? ucfirst($mood_today) : 'No log';?></div>
                <div class="kpi-label">Mood</div>
            </div>
        </div>

        <!-- SCORE BREAKDOWN -->
        <div class="inner-card">
            <h3>Productivity Breakdown</h3>
            <div class="breakdown-row">
                <div>
                    <div class="breakdown-tile-top">
                        <span class="breakdown-tile-name">✅ Habits</span>
                        <span class="breakdown-tile-score" style="color:#60a5fa"><?php echo round($habit_score);?>/40</span>
                    </div>
                    <div class="bar"><div class="bar-fill" style="width:<?php echo $total_habits>0?round($habit_score/40*100):0;?>%;background:#60a5fa"></div></div>
                </div>
                <div>
                    <div class="breakdown-tile-top">
                        <span class="breakdown-tile-name">😴 Sleep</span>
                        <span class="breakdown-tile-score" style="color:#22d3ee"><?php echo round($sleep_score);?>/30</span>
                    </div>
                    <div class="bar"><div class="bar-fill" style="width:<?php echo round($sleep_score/30*100);?>%;background:#22d3ee"></div></div>
                </div>
                <div>
                    <div class="breakdown-tile-top">
                        <span class="breakdown-tile-name">🏋️ Workout</span>
                        <span class="breakdown-tile-score" style="color:#4ade80"><?php echo $workout_score;?>/20</span>
                    </div>
                    <div class="bar"><div class="bar-fill" style="width:<?php echo $workout_done?100:0;?>%;background:#4ade80"></div></div>
                </div>
                <div>
                    <div class="breakdown-tile-top">
                        <span class="breakdown-tile-name">💰 Budget</span>
                        <span class="breakdown-tile-score" style="color:<?php echo $budget_penalty<0?'#f87171':'#fbbf24';?>"><?php echo $budget_penalty>=0?'OK':'-10';?></span>
                    </div>
                    <div class="bar"><div class="bar-fill" style="width:<?php echo $budget_penalty==0?100:0;?>%;background:#fbbf24"></div></div>
                </div>
            </div>
        </div>

        <?php if ($reading_book):
            $prog = $reading_book['total_pages']>0 ? min(100,round($reading_book['pages_read']/$reading_book['total_pages']*100)) : 0;
        ?>
        <div class="reading-banner">
            <div class="reading-top">
                <div>
                    <div class="reading-eyebrow">📚 Currently Reading</div>
                    <div class="reading-title"><?php echo htmlspecialchars($reading_book['book_title']);?></div>
                </div>
                <a href="modules/reading.php" class="btn-sm">Log →</a>
            </div>
            <div class="reading-meta">
                <span><?php echo $reading_book['pages_read'];?> / <?php echo $reading_book['total_pages'];?> pages</span>
                <span><?php echo $prog;?>%</span>
            </div>
            <div class="bar" style="height:5px"><div class="bar-fill" style="width:<?php echo $prog;?>%;background:#f59e0b"></div></div>
        </div>
        <?php endif; ?>

        <!-- MODULE SHORTCUTS -->
        <p class="section-label">Modules</p>
        <div class="shortcuts">
            <a href="modules/expenses.php" class="shortcut">
                <div class="shortcut-icon" style="background:rgba(248,113,113,.10)">💸</div>
                <div class="shortcut-name">Expenses</div>
                <div class="shortcut-desc">Income & spending</div>
            </a>
            <a href="modules/food.php" class="shortcut">
                <div class="shortcut-icon" style="background:rgba(251,191,36,.10)">🥗</div>
                <div class="shortcut-name">Food</div>
                <div class="shortcut-desc">Meals & calories</div>
            </a>
            <a href="modules/workout.php" class="shortcut">
                <div class="shortcut-icon" style="background:rgba(74,222,128,.10)">🏋️</div>
                <div class="shortcut-name">Workout</div>
                <div class="shortcut-desc">Exercise & streaks</div>
            </a>
            <a href="modules/sleep.php" class="shortcut">
                <div class="shortcut-icon" style="background:rgba(34,211,238,.10)">😴</div>
                <div class="shortcut-name">Sleep</div>
                <div class="shortcut-desc">Rest quality</div>
            </a>
            <a href="modules/habits.php" class="shortcut">
                <div class="shortcut-icon" style="background:rgba(96,165,250,.10)">✅</div>
                <div class="shortcut-name">Habits</div>
                <div class="shortcut-desc">Daily streaks</div>
            </a>
            <a href="modules/reading.php" class="shortcut">
                <div class="shortcut-icon" style="background:rgba(251,191,36,.10)">📚</div>
                <div class="shortcut-name">Reading</div>
                <div class="shortcut-desc">Books & sessions</div>
            </a>
            <a href="modules/diary.php" class="shortcut">
                <div class="shortcut-icon" style="background:rgba(244,114,182,.10)">📔</div>
                <div class="shortcut-name">Diary</div>
                <div class="shortcut-desc">Journal & mood</div>
            </a>
        </div>

    </div><!-- /.dash-body -->

    <div class="divider"></div>

    <!-- Footer nav inside card — matches auth-switch style -->
    <div class="nav-footer">
        <div class="nav-footer__links">
            <a href="modules/expenses.php">Expenses</a>
            <a href="modules/habits.php">Habits</a>
            <a href="modules/diary.php">Diary</a>
            <a href="settings.php">Settings</a>
        </div>
        <a href="logout.php" style="color:rgba(248,113,113,0.60);">Log out →</a>
    </div>

</div><!-- /.dash-card -->

<!-- Pagination dots — same as auth page -->
<div class="dash-dots">
    <span></span>
    <span class="active"></span>
    <span></span>
</div>

</body>
</html>