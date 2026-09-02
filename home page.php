<?php
$experiments = [
    1  => ["title" => "Online Voting System",       "desc" => "Cast votes for candidates with live results",           "icon" => "🗳️",  "color" => ["#667eea","#764ba2"], "file" => "e1.php"],
    2  => ["title" => "Voting Results Table",        "desc" => "Real-time vote tally with beautiful charts",           "icon" => "📊",  "color" => ["#f093fb","#f5576c"], "file" => "e2.php"],
    3  => ["title" => "Fibonacci Series",            "desc" => "Mathematical sequence explorer with visual table",     "icon" => "🔢",  "color" => ["#4facfe","#00f2fe"], "file" => "e3.php"],
    4  => ["title" => "Expense Tracker",             "desc" => "Log, sort and manage your daily spending",            "icon" => "💸",  "color" => ["#43e97b","#38f9d7"], "file" => "e4.php"],
    5  => ["title" => "Farm Land Crop Zones",        "desc" => "Tamil Nadu soil & crop zone explorer",               "icon" => "🌾",  "color" => ["#fa709a","#fee140"], "file" => "e5.php"],
    6  => ["title" => "Art Gallery",                 "desc" => "Fine art collection with Starry Night theme",         "icon" => "🖼️",  "color" => ["#a18cd1","#fbc2eb"], "file" => "e6.php"],
    7  => ["title" => "Credit Card Validator",       "desc" => "Luhn algorithm card checker & network detector",      "icon" => "💳",  "color" => ["#ffecd2","#fcb69f"], "file" => "e7.php"],
    8  => ["title" => "Construction Job Hiring",     "desc" => "Browse and post construction job listings",          "icon" => "🏗️",  "color" => ["#f59e0b","#ef4444"], "file" => "e8.php"],
    9  => ["title" => "Café Employee System",        "desc" => "Manage staff, shifts & attendance with pastel vibes", "icon" => "☕",  "color" => ["#f9d5e5","#e8a87c"], "file" => "e9.php"],
    10 => ["title" => "Music Player",                "desc" => "Import songs & play them on a vintage boombox",      "icon" => "🎵",  "color" => ["#f48fb1","#ffab76"], "file" => "e10.php"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My PHP Lab — Experiment Portfolio</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0a0f;--surface:#12121a;--card:#1a1a26;--card2:#1f1f2e;
  --border:rgba(255,255,255,0.07);--text:#f0f0ff;--muted:#6b6b8a;
}
html{scroll-behavior:smooth;}
body{
  font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);
  min-height:100vh;overflow-x:hidden;
}

/* ── BACKGROUND BLOBS ── */
body::before{
  content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
  background:
    radial-gradient(ellipse 60% 40% at 20% 10%,rgba(102,126,234,0.12),transparent),
    radial-gradient(ellipse 50% 35% at 80% 80%,rgba(240,147,251,0.1),transparent),
    radial-gradient(ellipse 40% 30% at 60% 40%,rgba(67,233,123,0.06),transparent);
}

/* ── HEADER ── */
header{
  position:relative;z-index:1;
  text-align:center;padding:80px 24px 60px;
}
.header-badge{
  display:inline-flex;align-items:center;gap:8px;
  padding:6px 18px;border-radius:20px;
  background:rgba(255,255,255,0.06);border:1px solid var(--border);
  font-size:12px;font-weight:600;letter-spacing:2px;text-transform:uppercase;
  color:var(--muted);margin-bottom:24px;
}
.header-badge::before{content:'';width:6px;height:6px;border-radius:50%;background:#4ade80;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.5);opacity:.5}}
header h1{
  font-family:'Syne',sans-serif;font-size:clamp(2.8rem,7vw,5.5rem);
  font-weight:800;line-height:1;letter-spacing:-2px;margin-bottom:16px;
  background:linear-gradient(135deg,#fff 20%,rgba(255,255,255,0.5));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
}
header h1 span{
  background:linear-gradient(90deg,#667eea,#f093fb,#43e97b);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
}
.header-sub{color:var(--muted);font-size:16px;font-weight:400;max-width:480px;margin:0 auto 40px;line-height:1.7;}
.header-stats{display:flex;justify-content:center;gap:32px;flex-wrap:wrap;}
.stat{text-align:center;}
.stat-n{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;
  background:linear-gradient(135deg,#667eea,#f093fb);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.stat-l{font-size:11px;color:var(--muted);letter-spacing:2px;text-transform:uppercase;margin-top:2px;}

/* ── DIVIDER ── */
.divider{height:1px;background:linear-gradient(90deg,transparent,var(--border),transparent);margin:0 48px;}

/* ── GRID ── */
.grid-section{position:relative;z-index:1;padding:60px 40px 80px;max-width:1200px;margin:0 auto;}
.grid-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:36px;flex-wrap:wrap;gap:12px;}
.grid-title{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:700;color:var(--text);}
.grid-count{font-size:13px;color:var(--muted);background:rgba(255,255,255,0.05);
  padding:4px 14px;border-radius:20px;border:1px solid var(--border);}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;}

/* ── CARD ── */
.card{
  background:var(--card);border:1px solid var(--border);border-radius:20px;
  overflow:hidden;cursor:pointer;text-decoration:none;display:block;
  transition:transform .3s cubic-bezier(.34,1.56,.64,1),border-color .3s,box-shadow .3s;
  position:relative;
}
.card:hover{
  transform:translateY(-6px);
  border-color:rgba(255,255,255,0.15);
  box-shadow:0 24px 60px rgba(0,0,0,0.4);
}
/* gradient top bar */
.card-bar{height:3px;width:100%;background:var(--grad);}
.card-body{padding:24px;}
.card-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;}
.card-icon-wrap{
  width:52px;height:52px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;font-size:24px;
  background:rgba(255,255,255,0.06);border:1px solid var(--border);
  flex-shrink:0;
}
.card-num{
  font-family:'Syne',sans-serif;font-size:11px;font-weight:700;
  letter-spacing:2px;color:var(--muted);
  background:rgba(255,255,255,0.05);border:1px solid var(--border);
  padding:4px 10px;border-radius:8px;
}
.card-title{font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:700;
  color:var(--text);margin-bottom:8px;line-height:1.2;}
.card-desc{font-size:13px;color:var(--muted);line-height:1.65;margin-bottom:20px;}
.card-footer{display:flex;align-items:center;justify-content:space-between;}
.card-file{font-size:11px;color:var(--muted);letter-spacing:1px;
  font-family:monospace;background:rgba(255,255,255,0.04);
  padding:4px 10px;border-radius:6px;border:1px solid var(--border);}
.card-arrow{
  width:34px;height:34px;border-radius:50%;
  background:var(--grad);
  display:flex;align-items:center;justify-content:center;
  font-size:14px;color:#fff;
  box-shadow:0 4px 14px rgba(0,0,0,0.3);
  transition:transform .2s;
}
.card:hover .card-arrow{transform:scale(1.15) rotate(45deg);}

/* ── HOVER GLOW ── */
.card::after{
  content:'';position:absolute;inset:0;border-radius:20px;
  opacity:0;transition:opacity .3s;pointer-events:none;
  background:linear-gradient(135deg,rgba(255,255,255,0.03),transparent);
}
.card:hover::after{opacity:1;}

/* ── FOOTER ── */
footer{
  position:relative;z-index:1;text-align:center;
  padding:32px;border-top:1px solid var(--border);
  color:var(--muted);font-size:13px;
}
footer span{color:rgba(255,255,255,0.4);}
</style>
</head>
<body>

<header>
  <div class="header-badge">PHP & MySQL Lab</div>
  <h1>Experiment<br><span>Portfolio</span></h1>
  <p class="header-sub">A collection of 10 full-stack PHP & MySQL projects — click any card to launch the experiment.</p>
  <div class="header-stats">
    <div class="stat"><div class="stat-n">10</div><div class="stat-l">Experiments</div></div>
    <div class="stat"><div class="stat-n">PHP</div><div class="stat-l">Backend</div></div>
    <div class="stat"><div class="stat-n">MySQL</div><div class="stat-l">Database</div></div>
  </div>
</header>

<div class="divider"></div>

<div class="grid-section">
  <div class="grid-header">
    <div class="grid-title">All Experiments</div>
    <div class="grid-count"><?=count($experiments)?> Projects</div>
  </div>
  <div class="grid">
    <?php foreach($experiments as $num => $exp):
      $grad = "linear-gradient(135deg,{$exp['color'][0]},{$exp['color'][1]})";
    ?>
    <a href="<?=htmlspecialchars($exp['file'])?>" class="card" style="--grad:<?=$grad?>">
      <div class="card-bar"></div>
      <div class="card-body">
        <div class="card-top">
          <div class="card-icon-wrap"><?=$exp['icon']?></div>
          <div class="card-num">EXP <?=str_pad($num,2,'0',STR_PAD_LEFT)?></div>
        </div>
        <div class="card-title"><?=htmlspecialchars($exp['title'])?></div>
        <div class="card-desc"><?=htmlspecialchars($exp['desc'])?></div>
        <div class="card-footer">
          <div class="card-file"><?=htmlspecialchars($exp['file'])?></div>
          <div class="card-arrow">→</div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<footer>
  PHP Lab Portfolio &nbsp;·&nbsp; <span>10 Experiments</span> &nbsp;·&nbsp; Built with PHP & MySQL
</footer>

</body>
</html>