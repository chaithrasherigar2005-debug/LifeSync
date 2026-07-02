<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_initials = '';
if (!empty($_SESSION['user_name'])) {
    $user_initials = strtoupper(substr($_SESSION['user_name'], 0, 2));
} elseif (!empty($_SESSION['username'])) {
    $user_initials = strtoupper(substr($_SESSION['username'], 0, 2));
} else {
    $user_initials = 'US';
}

$current_page = basename($_SERVER['SCRIPT_NAME']);
$page_title = 'LifeSync';
$page_subtitle = 'Personal Management System';

switch ($current_page) {
    case 'expenses.php':
        $page_title = 'Expense Tracker 💸';
        $page_subtitle = 'Track your monthly budget and balance';
        break;
    case 'food.php':
        $page_title = 'Food Tracker 🥗';
        $page_subtitle = 'Manage meals, macros, and water intake';
        break;
    case 'workout.php':
        $page_title = 'Workout Tracker 🏋️';
        $page_subtitle = 'Log exercise duration and calories';
        break;
    case 'sleep.php':
        $page_title = 'Sleep Tracker 😴';
        $page_subtitle = 'Record sleep quality and hours slept';
        break;
    case 'habits.php':
        $page_title = 'Habits Tracker ✅';
        $page_subtitle = 'Build consistency daily';
        break;
    case 'reading.php':
        $page_title = 'Reading Tracker 📚';
        $page_subtitle = 'Track books and pages read';
        break;
    case 'diary.php':
        $page_title = 'Digital Diary 📔';
        $page_subtitle = 'Write down your daily thoughts and mood';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> — LifeSync</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ── Unified Dashboard Styling for Modules ── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { color-scheme: dark only; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #020817 0%, #0a1430 22%, #0f1f4a 45%, #13265c 65%, #0b1838 100%) !important;
            position: relative;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px 80px;
            color: #fff !important;
        }

        /* Hide old navigation bar completely */
        nav.navbar, .navbar {
            display: none !important;
        }

        /* Background elements */
        .bg-dots {
            position: fixed; inset: 0;
            background-image: radial-gradient(rgba(96,165,250,0.20) 1.2px, transparent 1.2px);
            background-size: 28px 28px;
            -webkit-mask-image: radial-gradient(ellipse 70% 60% at 30% 20%, #000 0%, rgba(0,0,0,0.3) 55%, transparent 85%);
            mask-image: radial-gradient(ellipse 70% 60% at 30% 20%, #000 0%, rgba(0,0,0,0.3) 55%, transparent 85%);
            pointer-events: none; z-index: 0;
        }
        .bg-orb { position: fixed; border-radius: 50%; filter: blur(90px); pointer-events: none; z-index: 0; }
        .bg-orb--1 { width: 460px; height: 460px; background: rgba(37,99,235,0.28); top: -160px; left: -130px; }
        .bg-orb--2 { width: 380px; height: 380px; background: rgba(245,158,11,0.12); bottom: -130px; right: -110px; }
        .bg-orb--3 { width: 280px; height: 280px; background: rgba(96,165,250,0.10); top: 40%; right: 6%; }
        
        .float-leaf {
            position: fixed; font-size: 30px; opacity: 0.25; z-index: 0;
            filter: drop-shadow(0 4px 10px rgba(80,20,160,0.4));
            animation: floatLeaf 9s ease-in-out infinite; pointer-events: none;
        }
        .fl-1{top:7%;left:6%;animation-delay:0s;}
        .fl-2{bottom:13%;left:8%;animation-delay:2s;font-size:24px;}
        .fl-3{top:13%;right:7%;animation-delay:1s;}
        .fl-4{bottom:9%;right:9%;animation-delay:3s;font-size:26px;}
        @keyframes floatLeaf { 0%,100%{transform:translateY(0) rotate(0deg);} 50%{transform:translateY(-18px) rotate(12deg);} }

        .mascot-bg {
            position: fixed; z-index: 0; pointer-events: none;
            filter: drop-shadow(0 6px 14px rgba(80,20,160,0.35));
            animation: mascotFloat 8s ease-in-out infinite;
        }
        @keyframes mascotFloat { 0%,100%{transform:translateY(0) rotate(-4deg);} 50%{transform:translateY(-22px) rotate(4deg);} }

        /* Top nav */
        .top-nav {
            position: relative; z-index: 10;
            width: 100%; max-width: 700px;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
        }
        .top-nav__brand {
            font-family: 'Fredoka', sans-serif; font-size: 28px; font-weight: 700;
            display: flex; align-items: center; gap: 8px;
        }
        .top-nav__brand .life-part { color: #f0f4ff; }
        .top-nav__brand .sync-part { color: #60a5fa; }
        .top-nav__avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #f59e0b);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700; color: #fff;
            border: 2px solid rgba(96,165,250,0.35);
            cursor: pointer;
        }
        .top-nav__menu-btn {
            background: rgba(255,255,255,0.06); border: 1px solid rgba(96,165,250,0.22);
            border-radius: 12px; padding: 8px 12px; cursor: pointer;
            color: rgba(147,197,253,0.85); font-size: 13px; font-weight: 600;
            font-family: inherit; display: flex; align-items: center; gap: 6px;
            transition: background .15s; text-decoration: none;
        }
        .top-nav__menu-btn:hover { background: rgba(96,165,250,0.15); color: #fff; }

        .shimmer-line {
            position: relative; z-index: 1;
            width: 100%; max-width: 700px; height: 3px;
            background: linear-gradient(90deg, transparent 0%, #f59e0b 30%, #fbbf24 50%, #f59e0b 70%, transparent 100%);
            border-radius: 3px 3px 0 0;
        }

        .dash-card {
            position: relative; z-index: 1;
            width: 100%; max-width: 700px;
            background: #0a1228 !important;
            border-radius: 0 0 34px 34px;
            overflow: hidden;
            box-shadow:
                0 0 0 1px rgba(96,165,250,0.18),
                0 40px 100px rgba(0,0,0,0.65),
                0 0 60px rgba(37,99,235,0.18);
        }

        .dash-header {
            background: linear-gradient(160deg, #0a1430 0%, #13265c 55%, #1a3470 100%);
            padding: 24px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(96,165,250,0.12);
        }
        .dash-header-title {
            font-family: 'Fredoka', sans-serif; font-size: 24px; font-weight: 700;
            color: #f0f4ff; margin: 0;
        }
        .dash-header-subtitle {
            font-size: 12px; color: rgba(147,197,253, 0.6); margin-top: 3px;
        }

        /* ── Override light theme styles to matching dark theme ── */
        .ft-wrap, .et-wrap, .wk-wrap, .dy-wrap, .sl-wrap, .hb-wrap, .rd-wrap {
            background: transparent !important;
            min-height: auto !important;
            padding: 0 0 20px !important;
            color: #fff !important;
        }

        /* Hide internal headers since header.php provides it */
        .ft-hdr, .et-header, .wk-hdr, .dy-hdr, .sl-hdr, .ht-hdr, .rd-hdr {
            display: none !important;
        }

        /* Inner cards */
        .ft-mc, .ft-meal-card, .ft-add-card, .ft-water-card,
        .et-sc, .et-card, .wk-sc, .wk-card, .sl-sc, .sl-card,
        .ht-sc, .ht-card, .ht-progress-wrap, .ht-item,
        .rd-sc, .rd-card, .rd-book-card, .rd-tbl-card,
        .dy-sc, .dy-card, .dy-entry-card {
            background: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid rgba(96, 165, 250, 0.12) !important;
            border-radius: 20px !important;
            color: #f0f4ff !important;
            box-shadow: none !important;
        }

        /* Titles & text color adjustments */
        h2, h3, .ft-meal-name, .et-card-title, .wk-card-title, .dy-card-title,
        .rd-card-title, .ft-add-title, .ft-water-title, .dy-section-lbl,
        .ht-progress-title, .ht-grid-title, .ht-card-title, .rd-section-lbl {
            color: #e9e0ff !important;
        }
        .ft-food-name, .ft-meal-badge, .ft-word-count, .dy-entry-title, .rd-book-title, .ht-name {
            color: #f0f4ff !important;
        }

        /* Form inputs */
        .ft-inp, .ft-sel, .et-inp, .et-sel, .wk-inp, .wk-sel, .dy-inp, .sl-inp, .sl-sel, .ht-inp, .rd-inp, .rd-sel {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(96, 165, 250, 0.2) !important;
            color: #fff !important;
            border-radius: 10px !important;
        }
        .ft-inp:focus, .ft-sel:focus, .et-inp:focus, .et-sel:focus,
        .wk-inp:focus, .wk-sel:focus, .dy-inp:focus, .sl-inp:focus, .sl-sel:focus, .ht-inp:focus, .rd-inp:focus, .rd-sel:focus {
            background: rgba(255, 255, 255, 0.09) !important;
            border-color: #60a5fa !important;
        }
        .ft-inp::placeholder, .et-inp::placeholder, .wk-inp::placeholder, .dy-inp::placeholder, .ht-inp::placeholder, .rd-inp::placeholder {
            color: rgba(147, 197, 253, 0.4) !important;
        }
        .ft-sel option, .et-sel option, .wk-sel option, .sl-sel option, .rd-sel option {
            background: #0a1228 !important;
            color: #fff !important;
        }

        /* Labels */
        .ft-lbl, .et-lbl, .wk-lbl, .dy-lbl, .hb-lbl, .rd-lbl, .sl-lbl, .ht-lbl {
            color: rgba(147, 197, 253, 0.7) !important;
        }

        /* Alert boxes */
        .ft-alert, .et-alert, .wk-alert, .dy-alert, .sl-alert, .ht-alert, .rd-alert {
            background: rgba(96, 165, 250, 0.1) !important;
            border-left: 4px solid #60a5fa !important;
            color: #fff !important;
            border-radius: 0 12px 12px 0 !important;
            margin: 16px 20px 0 !important;
        }

        /* Water Intake */
        .ft-water-info strong {
            color: #60a5fa !important;
        }
        .ft-water-cup {
            background: rgba(255, 255, 255, 0.05) !important;
            border-color: rgba(96, 165, 250, 0.2) !important;
        }
        .ft-water-cup.filled {
            background: #2563eb !important;
            border-color: #60a5fa !important;
        }

        /* Tables & List elements */
        table.ft-dt, table.et-dt, table.wk-dt, table.sl-dt, table.hb-dt, table.rd-dt {
            color: #f0f4ff !important;
        }
        thead th {
            background: rgba(255, 255, 255, 0.02) !important;
            border-bottom: 1.5px solid rgba(96, 165, 250, 0.15) !important;
            color: rgba(147, 197, 253, 0.6) !important;
        }
        tbody td, .rd-dt tbody td {
            border-bottom: 1px solid rgba(96, 165, 250, 0.08) !important;
            color: #e2e8f0 !important;
        }
        tbody tr:hover {
            background: rgba(96, 165, 250, 0.04) !important;
        }

        /* Autocomplete */
        .ft-autocomplete {
            background: #0a1228 !important;
            border: 1px solid rgba(96, 165, 250, 0.2) !important;
        }
        .ft-ac-item {
            border-bottom: 1px solid rgba(96, 165, 250, 0.08) !important;
        }
        .ft-ac-item:hover, .ft-ac-item.active {
            background: rgba(96, 165, 250, 0.1) !important;
        }
        .ft-ac-name {
            color: #fff !important;
        }

        /* Stats & KPI values */
        .ft-ring-cal, .et-sc-val, .wk-sc-val, .dy-sc-val, .sl-sc-val, .ht-sc-val, .rd-sc-val {
            color: #60a5fa !important;
        }
        .ft-ring-lbl, .et-sc-lbl, .wk-sc-lbl, .dy-sc-lbl, .sl-sc-lbl, .ht-sc-lbl, .rd-sc-lbl {
            color: rgba(147, 197, 253, 0.5) !important;
        }
        .ft-mc-bar, .et-bbar-bg, .ht-progress-bg, .rd-bbar-bg, .rd-mini-bar-bg {
            background: rgba(255, 255, 255, 0.1) !important;
        }

        /* Diary & Habit buttons and checkboxes */
        .dy-mood-btn {
            background: rgba(255, 255, 255, 0.04) !important;
            border-color: rgba(96, 165, 250, 0.12) !important;
        }
        .dy-mood-btn.active {
            background: var(--mood-bg) !important;
            border-color: var(--mood-color) !important;
        }
        .dy-mood-btn:hover {
            border-color: var(--mood-color) !important;
        }

        .dy-img-upload-area {
            background: rgba(255, 255, 255, 0.02) !important;
            border-color: rgba(96, 165, 250, 0.2) !important;
        }
        .dy-img-upload-text strong {
            color: #60a5fa !important;
        }

        /* Grid layout alignments */
        .ft-meals, .et-stats, .wk-stats, .dy-stats, .sl-stats, .ht-stats, .rd-stats {
            margin: 16px 20px 0 !important;
            padding: 0 !important;
        }
        .ft-summary, .et-body, .wk-body, .dy-body, .sl-body, .ht-body, .rd-body {
            padding: 16px 20px 0 !important;
        }
        .ft-add-section, .wk-tbl-wrap, .et-tbl-wrap, .dy-entries-wrap, .sl-tbl-wrap, .ht-grid-wrap, .rd-reading-list, .rd-tbl-wrap, .rd-add-section {
            padding: 16px 20px 0 !important;
        }

        /* Footer styling overrides inside the card */
        .divider {
            height: 1px;
            background: rgba(96, 165, 250, 0.12);
            margin: 20px 0 0;
        }
        .nav-footer {
            background: #060c1b;
            padding: 18px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
        }
        .nav-footer__links {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        .nav-footer__links a {
            color: rgba(147, 197, 253, 0.55);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.15s;
        }
        .nav-footer__links a:hover {
            color: #60a5fa;
        }
        .dash-dots {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
        }
        .dash-dots span {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: rgba(96,165,250,0.22);
        }
        .dash-dots span.active {
            background: #60a5fa;
            box-shadow: 0 0 8px #60a5fa;
        }
    </style>
</head>
<body>
    <div class="bg-dots"></div>
    <div class="bg-orb bg-orb--1"></div>
    <div class="bg-orb bg-orb--2"></div>
    <div class="bg-orb bg-orb--3"></div>
    
    <span class="float-leaf fl-1">🍃</span>
    <span class="float-leaf fl-2">🌱</span>
    <span class="float-leaf fl-3">🌿</span>
    <span class="float-leaf fl-4">🍀</span>

    <!-- Unified Dashboard Top Navigation -->
    <div class="top-nav">
        <div class="top-nav__brand">
            <span class="life-part">Life</span><span class="sync-part">Sync</span>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="/lifesync/dashboard.php" class="top-nav__menu-btn">🏠 Dashboard</a>
            <a href="/lifesync/logout.php" class="top-nav__menu-btn">🚪 Log out</a>
            <div class="top-nav__avatar"><?php echo $user_initials; ?></div>
        </div>
    </div>

    <div class="shimmer-line"></div>
    
    <!-- Central Glassmorphic Card Container -->
    <div class="dash-card">
        
        <!-- Standardized Header Band -->
        <div class="dash-header">
            <div>
                <h2 class="dash-header-title"><?php echo $page_title; ?></h2>
                <div class="dash-header-subtitle"><?php echo $page_subtitle; ?></div>
            </div>
            <div class="dash-date"><?php echo date('l, d F Y'); ?></div>
        </div>
        
        <!-- Main body containing content -->
        <div class="dash-body">