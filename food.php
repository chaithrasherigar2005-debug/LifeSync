
<style>
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=JetBrains+Mono:wght@500&display=swap');

*{box-sizing:border-box;margin:0;padding:0;}

.dash{
  --bg:#1a1535;
  --bg2:#231d3f;
  --panel:#2d2555;
  --panel2:#352d66;
  --purple:#9b7fe8;
  --violet:#c084fc;
  --lavender:#d8b4fe;
  --pink:#f0abfc;
  --accent:#b57bee;
  --star:#fff9d6;
  --text:#ede9fc;
  --muted:#9d93c4;
  --line:rgba(180,160,255,0.18);
  font-family:'Nunito',sans-serif;
  background:var(--bg);
  color:var(--text);
  border-radius:28px;
  padding:1.5rem;
  min-height:600px;
  position:relative;
  overflow:hidden;
}

/* Twinkling stars background */
.stars-canvas{position:absolute;inset:0;pointer-events:none;z-index:0;}

/* Hero */
.hero{
  position:relative;
  z-index:2;
  background:linear-gradient(135deg,#2e1f6e 0%,#4a2d8e 50%,#3b1f72 100%);
  border-radius:24px;
  padding:1.5rem;
  margin-bottom:1.2rem;
  border:1px solid rgba(155,127,232,0.3);
  overflow:hidden;
}
.hero-nebula{position:absolute;inset:0;pointer-events:none;}
.hero-inner{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;}
.hero-text .eyebrow{font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--lavender);opacity:.9;}
.hero-text .name{font-size:1.6rem;font-weight:900;color:#fff;margin:.3rem 0 .1rem;}
.hero-text .date{font-size:.85rem;color:var(--muted);}

.score-bubble{
  background:rgba(255,255,255,0.1);
  border:1px solid rgba(200,170,255,0.3);
  backdrop-filter:blur(8px);
  border-radius:18px;
  padding:.8rem 1.2rem;
  display:flex;align-items:center;gap:1rem;
}
.ring-wrap{position:relative;width:64px;height:64px;flex-shrink:0;}
.ring-wrap svg{width:100%;height:100%;}
.ring-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.ring-num{font-size:1.2rem;font-weight:900;color:#fff;line-height:1;}
.ring-sub{font-size:.55rem;color:rgba(255,255,255,.7);}
.score-info .label{font-weight:800;font-size:.95rem;color:#fff;}
.score-info .sub{font-size:.72rem;color:var(--muted);}

/* Cartoon character */
.cartoon-wrap{position:absolute;right:1.2rem;bottom:0;width:90px;height:100px;z-index:2;}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
@keyframes blink{0%,92%,100%{transform:scaleY(1)}96%{transform:scaleY(0.1)}}
.floating-char{animation:float 3s ease-in-out infinite;}

/* Section label */
.section-label{
  font-size:.85rem;font-weight:800;
  color:var(--violet);
  text-transform:uppercase;letter-spacing:.07em;
  margin:1.2rem 0 .7rem;
  position:relative;z-index:2;
}

/* KPI grid */
.kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:.5rem;position:relative;z-index:2;}
.kpi{
  background:var(--panel);
  border:1px solid var(--line);
  border-radius:16px;
  padding:.9rem 1rem;
  cursor:pointer;
  transition:transform .15s,border-color .15s;
}
.kpi:hover{transform:translateY(-2px);border-color:rgba(155,127,232,.5);}
.kpi-icon{font-size:1.4rem;margin-bottom:.4rem;}
.kpi-val{font-size:1.15rem;font-weight:900;color:#fff;line-height:1;}
.kpi-lbl{font-size:.7rem;color:var(--muted);margin-top:.25rem;font-weight:600;}

/* Score breakdown */
.breakdown{background:var(--panel);border:1px solid var(--line);border-radius:18px;padding:1.1rem 1.2rem;margin-bottom:1rem;position:relative;z-index:2;}
.breakdown-row{display:flex;align-items:center;gap:.75rem;margin-bottom:.7rem;}
.breakdown-row:last-child{margin-bottom:0;}
.br-label{font-size:.8rem;font-weight:700;color:var(--text);width:90px;flex-shrink:0;}
.br-bar{flex:1;height:8px;background:rgba(255,255,255,.08);border-radius:99px;overflow:hidden;}
.br-fill{height:100%;border-radius:99px;transition:width .5s ease;}
.br-score{font-size:.78rem;font-family:'JetBrains Mono',monospace;color:var(--muted);width:38px;text-align:right;flex-shrink:0;}

/* Modules grid */
.mod-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.7rem;position:relative;z-index:2;}
.mod{
  background:var(--panel);
  border:1px solid var(--line);
  border-radius:14px;
  padding:.9rem .7rem;
  text-align:center;
  cursor:pointer;
  transition:transform .15s,background .15s;
  text-decoration:none;
}
.mod:hover{transform:translateY(-3px);background:var(--panel2);}
.mod-icon{font-size:1.5rem;margin-bottom:.4rem;}
.mod-name{font-size:.75rem;font-weight:800;color:#fff;}
.mod-desc{font-size:.65rem;color:var(--muted);margin-top:.15rem;}

/* Reading card */
.reading{
  background:linear-gradient(120deg,#3b2070,#2d1f5e);
  border:1px solid rgba(180,140,255,.25);
  border-radius:18px;
  padding:1.1rem 1.2rem;
  margin-bottom:1rem;
  position:relative;z-index:2;
}
.reading-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.8rem;flex-wrap:wrap;gap:.5rem;}
.reading-eyebrow{font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--violet);}
.reading-title{font-size:.95rem;font-weight:800;color:#fff;}
.reading-btn{font-size:.75rem;font-weight:700;color:var(--lavender);text-decoration:none;border:1px solid rgba(180,140,255,.4);border-radius:8px;padding:.25rem .7rem;}
.pill-bar{height:7px;background:rgba(255,255,255,.08);border-radius:99px;overflow:hidden;}
.pill-fill{height:100%;border-radius:99px;}

/* Animated stars */
@keyframes twinkle{0%,100%{opacity:.2}50%{opacity:1}}
@keyframes shoot{0%{transform:translateX(0) translateY(0);opacity:1}100%{transform:translateX(120px) translateY(40px);opacity:0}}
</style>

<div class="dash">
  <h2 class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);">Personal productivity dashboard with night sky theme</h2>

  <!-- Animated star canvas -->
  <svg class="stars-canvas" xmlns="http://www.w3.org/2000/svg">
    <circle cx="50" cy="30" r="1.2" fill="#fff9d6" opacity=".7" style="animation:twinkle 2.1s ease-in-out infinite"/>
    <circle cx="120" cy="80" r=".8" fill="#d8b4fe" opacity=".6" style="animation:twinkle 3s ease-in-out infinite .4s"/>
    <circle cx="200" cy="20" r="1.5" fill="#fff" opacity=".5" style="animation:twinkle 2.5s ease-in-out infinite .9s"/>
    <circle cx="280" cy="55" r="1" fill="#c084fc" opacity=".7" style="animation:twinkle 1.8s ease-in-out infinite .2s"/>
    <circle cx="360" cy="15" r=".9" fill="#fff9d6" opacity=".5" style="animation:twinkle 2.8s ease-in-out infinite .6s"/>
    <circle cx="420" cy="70" r="1.3" fill="#fff" opacity=".4" style="animation:twinkle 3.2s ease-in-out infinite 1s"/>
    <circle cx="500" cy="35" r="1" fill="#d8b4fe" opacity=".6" style="animation:twinkle 2.3s ease-in-out infinite .3s"/>
    <circle cx="580" cy="50" r=".7" fill="#fff" opacity=".5" style="animation:twinkle 1.9s ease-in-out infinite .7s"/>
    <circle cx="600" cy="10" r="1.4" fill="#c084fc" opacity=".5" style="animation:twinkle 2.6s ease-in-out infinite .1s"/>
    <circle cx="30" cy="180" r=".9" fill="#fff9d6" opacity=".4" style="animation:twinkle 3.1s ease-in-out infinite .5s"/>
    <circle cx="650" cy="180" r="1" fill="#fff" opacity=".45" style="animation:twinkle 2s ease-in-out infinite .8s"/>
    <circle cx="310" cy="400" r=".8" fill="#d8b4fe" opacity=".3" style="animation:twinkle 2.4s ease-in-out infinite 1.2s"/>
    <circle cx="450" cy="300" r="1.1" fill="#fff" opacity=".35" style="animation:twinkle 2.9s ease-in-out infinite .35s"/>
    <circle cx="75" cy="350" r=".7" fill="#fff9d6" opacity=".4" style="animation:twinkle 2.2s ease-in-out infinite .75s"/>
    <!-- Shooting star -->
    <g style="animation:shoot 4s ease-in-out infinite 3s">
      <line x1="80" y1="40" x2="100" y2="48" stroke="#fff9d6" stroke-width="1.5" opacity=".8" stroke-linecap="round"/>
    </g>
  </svg>

  <!-- Hero Card -->
  <div class="hero">
    <!-- Nebula blobs -->
    <svg class="hero-nebula" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 160" preserveAspectRatio="xMidYMid slice">
      <ellipse cx="80" cy="80" rx="120" ry="80" fill="rgba(120,60,200,0.25)"/>
      <ellipse cx="420" cy="50" rx="100" ry="60" fill="rgba(180,80,220,0.18)"/>
      <ellipse cx="260" cy="130" rx="140" ry="50" fill="rgba(80,40,160,0.2)"/>
      <!-- Mini moon -->
      <circle cx="450" cy="30" r="18" fill="#e8deff" opacity=".12"/>
      <circle cx="455" cy="28" r="18" fill="#2e1f6e"/>
      <!-- Stars in hero -->
      <circle cx="30" cy="20" r="1.2" fill="#fff9d6" opacity=".8" style="animation:twinkle 2s infinite"/>
      <circle cx="380" cy="15" r="1" fill="#fff" opacity=".7" style="animation:twinkle 2.5s infinite .5s"/>
      <circle cx="160" cy="10" r=".8" fill="#c084fc" opacity=".9" style="animation:twinkle 1.8s infinite .3s"/>
    </svg>

    <div class="hero-inner">
      <div class="hero-text">
        <div class="eyebrow">✨ Good evening</div>
        <div class="name">Welcome back!</div>
        <div class="date" id="hero-date">Wednesday, Jun 17</div>
      </div>

      <div class="score-bubble">
        <div class="ring-wrap">
          <svg viewBox="0 0 68 68" xmlns="http://www.w3.org/2000/svg">
            <circle cx="34" cy="34" r="28" fill="none" stroke="rgba(200,180,255,0.2)" stroke-width="7"/>
            <circle cx="34" cy="34" r="28" fill="none" stroke="url(#ring-grad)" stroke-width="7"
              stroke-dasharray="113 176" stroke-linecap="round" transform="rotate(-90 34 34)"/>
            <defs>
              <linearGradient id="ring-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#c084fc"/>
                <stop offset="100%" stop-color="#818cf8"/>
              </linearGradient>
            </defs>
          </svg>
          <div class="ring-center">
            <div class="ring-num">72</div>
            <div class="ring-sub">/100</div>
          </div>
        </div>
        <div class="score-info">
          <div class="label">🌿 Good</div>
          <div class="sub">Productivity Score</div>
        </div>
      </div>
    </div>

    <!-- Floating cartoon astronaut character -->
    <div class="cartoon-wrap">
      <svg class="floating-char" viewBox="0 0 90 110" xmlns="http://www.w3.org/2000/svg">
        <!-- Planet/Moon base -->
        <ellipse cx="45" cy="100" rx="38" ry="10" fill="#6d4fae" opacity=".5"/>
        <ellipse cx="45" cy="98" rx="30" ry="8" fill="#8b68cc" opacity=".6"/>

        <!-- Body (spacesuit) -->
        <rect x="28" y="55" width="34" height="32" rx="14" fill="#d8c8ff"/>
        <!-- Suit detail -->
        <rect x="35" y="62" width="20" height="14" rx="5" fill="#b9a0f0"/>
        <circle cx="45" cy="69" r="5" fill="#7c5cbf"/>
        <!-- Control panel buttons -->
        <circle cx="39" cy="66" r="2" fill="#f0abfc"/>
        <circle cx="45" cy="64" r="2" fill="#86efac"/>
        <circle cx="51" cy="66" r="2" fill="#fbbf24"/>

        <!-- Arms -->
        <ellipse cx="22" cy="65" rx="8" ry="6" fill="#d8c8ff" transform="rotate(-20 22 65)"/>
        <ellipse cx="68" cy="65" rx="8" ry="6" fill="#d8c8ff" transform="rotate(20 68 65)"/>
        <!-- Gloves -->
        <circle cx="17" cy="70" r="5" fill="#c084fc"/>
        <circle cx="73" cy="70" r="5" fill="#c084fc"/>

        <!-- Helmet -->
        <circle cx="45" cy="42" r="22" fill="#e9e0ff"/>
        <!-- Helmet visor -->
        <ellipse cx="45" cy="43" rx="15" ry="14" fill="#4a2d8e" opacity=".85"/>
        <!-- Stars reflection in visor -->
        <circle cx="37" cy="38" r="1.2" fill="#fff9d6" opacity=".8"/>
        <circle cx="50" cy="35" r=".8" fill="#c084fc" opacity=".9"/>
        <circle cx="54" cy="44" r="1" fill="#fff" opacity=".7"/>
        <!-- Cute eyes -->
        <ellipse cx="40" cy="43" rx="4" ry="4.5" fill="#fff" style="animation:blink 4s infinite"/>
        <ellipse cx="50" cy="43" rx="4" ry="4.5" fill="#fff" style="animation:blink 4s infinite"/>
        <circle cx="41" cy="44" r="2.5" fill="#2d1b69"/>
        <circle cx="51" cy="44" r="2.5" fill="#2d1b69"/>
        <!-- Eye shine -->
        <circle cx="42" cy="42.5" r="1" fill="#fff"/>
        <circle cx="52" cy="42.5" r="1" fill="#fff"/>
        <!-- Smile -->
        <path d="M40 49 Q45 53 50 49" stroke="#fff" stroke-width="1.5" fill="none" stroke-linecap="round"/>
        <!-- Helmet ring -->
        <circle cx="45" cy="42" r="22" fill="none" stroke="#c084fc" stroke-width="2.5" opacity=".4"/>
        <!-- Antenna -->
        <line x1="45" y1="20" x2="45" y2="10" stroke="#c084fc" stroke-width="2" stroke-linecap="round"/>
        <circle cx="45" cy="9" r="3.5" fill="#f0abfc"/>
        <!-- Legs -->
        <rect x="33" y="83" width="10" height="15" rx="5" fill="#c4b0f5"/>
        <rect x="47" y="83" width="10" height="15" rx="5" fill="#c4b0f5"/>
        <!-- Boots -->
        <ellipse cx="38" cy="99" rx="8" ry="4" fill="#9b7fe8"/>
        <ellipse cx="52" cy="99" rx="8" ry="4" fill="#9b7fe8"/>
      </svg>
    </div>
  </div>

  <!-- KPI cards -->
  <div class="section-label">Today at a glance</div>
  <div class="kpi-grid">
    <div class="kpi" onclick="location.href='modules/expenses.php'">
      <div class="kpi-icon">💸</div>
      <div class="kpi-val">₹0</div>
      <div class="kpi-lbl">Spent Today</div>
    </div>
    <div class="kpi" onclick="location.href='modules/expenses.php'">
      <div class="kpi-icon">📈</div>
      <div class="kpi-val">₹0</div>
      <div class="kpi-lbl">Income – Jun</div>
    </div>
    <div class="kpi" onclick="location.href='modules/food.php'">
      <div class="kpi-icon">🥗</div>
      <div class="kpi-val">0</div>
      <div class="kpi-lbl">Calories Today</div>
    </div>
    <div class="kpi" onclick="location.href='modules/sleep.php'">
      <div class="kpi-icon">😴</div>
      <div class="kpi-val">—</div>
      <div class="kpi-lbl">Sleep Last Night</div>
    </div>
    <div class="kpi" onclick="location.href='modules/habits.php'">
      <div class="kpi-icon">✅</div>
      <div class="kpi-val">0<span style="font-size:.85rem;color:var(--muted)">/5</span></div>
      <div class="kpi-lbl">Habits Done</div>
    </div>
    <div class="kpi" onclick="location.href='modules/diary.php'">
      <div class="kpi-icon">📔</div>
      <div class="kpi-val" style="font-size:.9rem;color:var(--violet)">Not logged</div>
      <div class="kpi-lbl">Today's Mood</div>
    </div>
  </div>

  <!-- Score Breakdown -->
  <div class="section-label">Score breakdown</div>
  <div class="breakdown">
    <div class="breakdown-row">
      <div class="br-label">✅ Habits</div>
      <div class="br-bar"><div class="br-fill" style="width:60%;background:linear-gradient(90deg,#9333ea,#c084fc)"></div></div>
      <div class="br-score" style="color:#c084fc">24/40</div>
    </div>
    <div class="breakdown-row">
      <div class="br-label">😴 Sleep</div>
      <div class="br-bar"><div class="br-fill" style="width:75%;background:linear-gradient(90deg,#0ea5e9,#7dd3fc)"></div></div>
      <div class="br-score" style="color:#7dd3fc">23/30</div>
    </div>
    <div class="breakdown-row">
      <div class="br-label">🏋️ Workout</div>
      <div class="br-bar"><div class="br-fill" style="width:100%;background:linear-gradient(90deg,#16a34a,#4ade80)"></div></div>
      <div class="br-score" style="color:#4ade80">20/20</div>
    </div>
    <div class="breakdown-row">
      <div class="br-label">💰 Budget</div>
      <div class="br-bar"><div class="br-fill" style="width:100%;background:linear-gradient(90deg,#d97706,#fbbf24)"></div></div>
      <div class="br-score" style="color:#fbbf24">OK</div>
    </div>
  </div>

  <!-- Currently Reading (preview) -->
  <div class="reading">
    <div class="reading-top">
      <div>
        <div class="reading-eyebrow">📚 Currently Reading</div>
        <div class="reading-title">Atomic Habits</div>
      </div>
      <a href="modules/reading.php" class="reading-btn">Log session →</a>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--muted);margin-bottom:.5rem;">
      <span>180 / 320 pages</span><span>56%</span>
    </div>
    <div class="pill-bar"><div class="pill-fill" style="width:56%;background:linear-gradient(90deg,#9333ea,#f0abfc)"></div></div>
  </div>

  <!-- Modules -->
  <div class="section-label">Modules</div>
  <div class="mod-grid">
    <a href="modules/expenses.php" class="mod">
      <div class="mod-icon">💸</div>
      <div class="mod-name">Expenses</div>
      <div class="mod-desc">Income & spending</div>
    </a>
    <a href="modules/food.php" class="mod">
      <div class="mod-icon">🥗</div>
      <div class="mod-name">Food</div>
      <div class="mod-desc">Meals & calories</div>
    </a>
    <a href="modules/workout.php" class="mod">
      <div class="mod-icon">🏋️</div>
      <div class="mod-name">Workout</div>
      <div class="mod-desc">Exercise & streaks</div>
    </a>
    <a href="modules/sleep.php" class="mod">
      <div class="mod-icon">😴</div>
      <div class="mod-name">Sleep</div>
      <div class="mod-desc">Rest quality</div>
    </a>
    <a href="modules/habits.php" class="mod">
      <div class="mod-icon">✅</div>
      <div class="mod-name">Habits</div>
      <div class="mod-desc">Daily streaks</div>
    </a>
    <a href="modules/reading.php" class="mod">
      <div class="mod-icon">📚</div>
      <div class="mod-name">Reading</div>
      <div class="mod-desc">Books & sessions</div>
    </a>
    <a href="modules/diary.php" class="mod">
      <div class="mod-icon">📔</div>
      <div class="mod-name">Diary</div>
      <div class="mod-desc">Journal & mood</div>
    </a>
    <div class="mod" style="border-style:dashed;opacity:.5;">
      <div class="mod-icon">＋</div>
      <div class="mod-name">More</div>
      <div class="mod-desc">Coming soon</div>
    </div>
  </div>
</div>
