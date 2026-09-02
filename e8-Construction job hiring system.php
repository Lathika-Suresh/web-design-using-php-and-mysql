<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect("localhost","root","","construction_db");

// Handle actions
if(isset($_POST["post_job"])) {
    $t  = mysqli_real_escape_string($conn,$_POST["title"]);
    $co = mysqli_real_escape_string($conn,$_POST["company"]);
    $lo = mysqli_real_escape_string($conn,$_POST["location"]);
    $ty = mysqli_real_escape_string($conn,$_POST["type"]);
    $sa = mysqli_real_escape_string($conn,$_POST["salary"]);
    $ex = mysqli_real_escape_string($conn,$_POST["experience"]);
    $de = mysqli_real_escape_string($conn,$_POST["description"]);
    $ca = mysqli_real_escape_string($conn,$_POST["category"]);
    mysqli_query($conn,"INSERT INTO jobs(title,company,location,type,salary,experience,description,category) VALUES('$t','$co','$lo','$ty','$sa','$ex','$de','$ca')");
    header("Location: ".$_SERVER['PHP_SELF']."?posted=1"); exit();
}
if(isset($_POST["apply"])) {
    $jid = (int)$_POST["job_id"];
    $nm  = mysqli_real_escape_string($conn,$_POST["name"]);
    $em  = mysqli_real_escape_string($conn,$_POST["email"]);
    $ph  = mysqli_real_escape_string($conn,$_POST["phone"]);
    $ex  = mysqli_real_escape_string($conn,$_POST["exp_years"]);
    $cv  = mysqli_real_escape_string($conn,$_POST["cover"]);
    mysqli_query($conn,"INSERT INTO applications(job_id,name,email,phone,exp_years,cover_letter) VALUES($jid,'$nm','$em','$ph','$ex','$cv')");
    header("Location: ".$_SERVER['PHP_SELF']."?applied=1"); exit();
}
if(isset($_POST["delete_job"])) {
    mysqli_query($conn,"DELETE FROM jobs WHERE id=".(int)$_POST["delete_job"]);
    header("Location: ".$_SERVER['PHP_SELF']); exit();
}

$page     = $_GET["page"] ?? "jobs";
$cat      = isset($_GET["cat"]) ? mysqli_real_escape_string($conn,$_GET["cat"]) : "";
$search   = isset($_GET["q"])   ? mysqli_real_escape_string($conn,$_GET["q"])   : "";
$apply_id = isset($_GET["apply"]) ? (int)$_GET["apply"] : 0;

$where = "WHERE 1";
if($cat)    $where .= " AND category='$cat'";
if($search) $where .= " AND (title LIKE '%$search%' OR company LIKE '%$search%' OR location LIKE '%$search%')";

$jobs     = $conn ? mysqli_fetch_all(mysqli_query($conn,"SELECT * FROM jobs $where ORDER BY posted_at DESC"),MYSQLI_ASSOC) : [];
$total    = count($jobs);
$cats_r   = $conn ? mysqli_fetch_all(mysqli_query($conn,"SELECT category,COUNT(*) as cnt FROM jobs GROUP BY category"),MYSQLI_ASSOC) : [];
$stats    = $conn ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as tj FROM jobs")) : ["tj"=>0];
$apps     = $conn ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as ta FROM applications")) : ["ta"=>0];
$apply_job= $apply_id ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM jobs WHERE id=$apply_id")) : null;

$categories = ["Civil Engineering","Steel & Iron","Electrical","Plumbing","Carpentry","Heavy Equipment","Safety","Management","Architecture","Welding"];
$icons      = ["Civil Engineering"=>"🏗","Steel & Iron"=>"⚙","Electrical"=>"⚡","Plumbing"=>"🔧","Carpentry"=>"🪚","Heavy Equipment"=>"🚜","Safety"=>"🦺","Management"=>"📋","Architecture"=>"📐","Welding"=>"🔥"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>FoundationWorks — Construction Hiring</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0c0a08;--surface:#141210;--card:#1c1916;--card2:#231f1b;
  --amber:#f59e0b;--amber2:#fbbf24;--orange:#ea580c;--stone:#78716c;
  --cream:#faf8f5;--warm:#e7e0d5;--border:rgba(245,158,11,0.15);
  --text:#f5f0e8;--muted:#a09585;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;
  background-image:
    radial-gradient(ellipse 80% 40% at 50% -5%,rgba(245,158,11,0.1),transparent),
    url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23f59e0b' fill-opacity='0.02'%3E%3Cpath d='M0 0h4v4H0V0zm8 0h4v4H8V0zm8 0h4v4h-4V0zm8 0h4v4h-4V0zm8 0h4v4h-4V0zm8 0h4v4h-4V0zm8 0h4v4h-4V0zm8 0h4v4h-4V0z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");}

/* NAV */
nav{display:flex;align-items:center;justify-content:space-between;padding:0 48px;height:68px;
  border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;
  background:rgba(12,10,8,0.92);backdrop-filter:blur(20px);}
.logo{font-family:'Bebas Neue',sans-serif;font-size:1.6rem;letter-spacing:3px;
  background:linear-gradient(90deg,var(--amber),var(--amber2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.nav-links{display:flex;gap:4px;}
.nav-btn{padding:8px 20px;font-size:12px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;
  color:var(--muted);background:none;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;
  border-radius:6px;transition:all .2s;text-decoration:none;display:inline-block;}
.nav-btn:hover,.nav-btn.active{color:var(--amber);background:rgba(245,158,11,0.08);}
.nav-post{padding:8px 22px;background:linear-gradient(135deg,var(--amber),var(--orange));
  color:#000;border:none;border-radius:8px;font-family:'DM Sans',sans-serif;font-size:12px;
  font-weight:700;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;
  transition:transform .2s,box-shadow .2s;box-shadow:0 4px 16px rgba(245,158,11,0.3);}
.nav-post:hover{transform:translateY(-1px);box-shadow:0 6px 24px rgba(245,158,11,0.45);}

/* HERO */
.hero{position:relative;padding:90px 48px 70px;overflow:hidden;}
.hero-bg{position:absolute;inset:0;
  background:linear-gradient(135deg,#1a1208 0%,#0c0a08 40%,#0f0c08 100%);}
.hero-bg::before{content:'';position:absolute;right:-100px;top:-60px;width:600px;height:600px;
  background:radial-gradient(circle,rgba(245,158,11,0.08),transparent 70%);border-radius:50%;}
.hero-bg::after{content:'⚙';position:absolute;right:80px;top:40px;font-size:300px;
  opacity:.03;line-height:1;pointer-events:none;}
.hero-content{position:relative;max-width:700px;}
.hero-eyebrow{display:inline-flex;align-items:center;gap:10px;
  padding:6px 18px;background:rgba(245,158,11,0.08);border:1px solid var(--border);
  border-radius:4px;font-size:10px;letter-spacing:3px;text-transform:uppercase;color:var(--amber);margin-bottom:24px;}
.hero-eyebrow::before{content:'';width:8px;height:8px;background:var(--amber);
  clip-path:polygon(50% 0,100% 50%,50% 100%,0 50%);}
h1{font-family:'Bebas Neue',sans-serif;font-size:clamp(3.5rem,9vw,7rem);
  line-height:.95;letter-spacing:2px;margin-bottom:20px;}
h1 span{display:block;background:linear-gradient(90deg,var(--amber),var(--amber2),var(--orange));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.hero-sub{color:var(--muted);font-size:15px;line-height:1.7;max-width:480px;margin-bottom:36px;font-weight:400;}
.hero-stats{display:flex;gap:40px;}
.hstat{text-align:left;}
.hstat-num{font-family:'Bebas Neue',sans-serif;font-size:2.2rem;letter-spacing:2px;color:var(--amber);}
.hstat-label{font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);}

/* SEARCH BAR */
.search-section{padding:0 48px 40px;}
.search-bar{display:flex;gap:12px;background:var(--card);border:1px solid var(--border);
  border-radius:12px;padding:8px 8px 8px 24px;max-width:800px;align-items:center;flex-wrap:wrap;}
.search-bar input{flex:1;min-width:200px;background:transparent;border:none;color:var(--text);
  font-family:'DM Sans',sans-serif;font-size:15px;outline:none;padding:8px 0;}
.search-bar input::placeholder{color:var(--muted);}
.search-bar select{background:var(--card2);border:1px solid var(--border);color:var(--text);
  font-family:'DM Sans',sans-serif;font-size:13px;padding:10px 16px;border-radius:8px;outline:none;}
.search-bar select option{background:var(--card);}
.btn-search{padding:12px 28px;background:linear-gradient(135deg,var(--amber),var(--orange));
  color:#000;border:none;border-radius:8px;font-family:'DM Sans',sans-serif;
  font-size:13px;font-weight:700;letter-spacing:1px;cursor:pointer;white-space:nowrap;}

/* CATEGORY PILLS */
.cats-section{padding:0 48px 40px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
.cat-label{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-right:4px;}
.cat-pill{padding:7px 18px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);
  border-radius:4px;font-size:12px;font-weight:600;color:var(--muted);text-decoration:none;
  letter-spacing:.5px;transition:all .2s;}
.cat-pill:hover,.cat-pill.active{background:rgba(245,158,11,0.12);color:var(--amber);border-color:var(--border);}

/* JOB GRID */
.jobs-section{padding:0 48px 60px;}
.section-header{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:28px;}
.section-title{font-family:'DM Serif Display',serif;font-size:1.8rem;}
.count-text{font-size:13px;color:var(--muted);}
.jobs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;}

/* JOB CARD */
.job-card{background:var(--card);border:1px solid rgba(255,255,255,0.06);border-radius:14px;
  padding:28px;transition:all .3s;position:relative;overflow:hidden;}
.job-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,var(--amber),var(--orange));transform:scaleX(0);
  transform-origin:left;transition:transform .3s;}
.job-card:hover{border-color:var(--border);transform:translateY(-3px);
  box-shadow:0 20px 48px rgba(0,0,0,0.4);}
.job-card:hover::before{transform:scaleX(1);}
.job-card-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;}
.job-icon{width:48px;height:48px;background:rgba(245,158,11,0.1);border:1px solid var(--border);
  border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;}
.job-type-badge{padding:4px 12px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;}
.badge-full{background:rgba(16,185,129,0.12);color:#34d399;}
.badge-part{background:rgba(245,158,11,0.12);color:var(--amber);}
.badge-contract{background:rgba(139,92,246,0.12);color:#a78bfa;}
.badge-temp{background:rgba(239,68,68,0.12);color:#f87171;}
.job-title{font-family:'DM Serif Display',serif;font-size:1.2rem;margin-bottom:6px;line-height:1.3;}
.job-company{font-size:13px;color:var(--amber);font-weight:600;margin-bottom:14px;}
.job-meta{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:16px;}
.job-meta span{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:5px;}
.job-desc{font-size:13px;color:var(--muted);line-height:1.6;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:20px;}
.job-footer{display:flex;justify-content:space-between;align-items:center;}
.job-salary{font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:1px;color:var(--cream);}
.btn-apply{padding:9px 22px;background:linear-gradient(135deg,var(--amber),var(--orange));
  color:#000;border:none;border-radius:6px;font-family:'DM Sans',sans-serif;
  font-size:12px;font-weight:700;letter-spacing:1px;cursor:pointer;text-decoration:none;
  transition:all .2s;display:inline-block;}
.btn-apply:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(245,158,11,0.4);}
.btn-del{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#f87171;
  padding:6px 12px;border-radius:6px;font-size:11px;cursor:pointer;font-family:'DM Sans',sans-serif;}

/* APPLY MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:200;
  display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(8px);}
.modal{background:var(--card);border:1px solid var(--border);border-radius:20px;
  padding:40px;max-width:580px;width:100%;max-height:90vh;overflow-y:auto;}
.modal h2{font-family:'DM Serif Display',serif;font-size:1.8rem;margin-bottom:4px;}
.modal-sub{color:var(--muted);font-size:13px;margin-bottom:28px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.field label{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);
  display:block;margin-bottom:7px;}
.field input,.field select,.field textarea{width:100%;background:var(--surface);border:1px solid rgba(255,255,255,0.08);
  border-radius:8px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:14px;
  padding:12px 16px;outline:none;transition:border-color .2s;}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--amber);}
.field textarea{resize:vertical;min-height:90px;}
.field.full{grid-column:1/-1;}
.modal-btns{display:flex;gap:12px;margin-top:24px;}
.btn-submit{flex:1;padding:14px;background:linear-gradient(135deg,var(--amber),var(--orange));
  color:#000;border:none;border-radius:8px;font-family:'DM Sans',sans-serif;
  font-size:14px;font-weight:700;letter-spacing:1px;cursor:pointer;}
.btn-cancel{padding:14px 24px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);
  color:var(--muted);border-radius:8px;font-family:'DM Sans',sans-serif;
  font-size:14px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;}

/* POST JOB FORM */
.post-section{max-width:760px;margin:0 auto;padding:60px 48px;}
.post-section h2{font-family:'Bebas Neue',sans-serif;font-size:3rem;letter-spacing:3px;margin-bottom:8px;
  background:linear-gradient(90deg,var(--amber),var(--amber2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.post-section>p{color:var(--muted);font-size:14px;margin-bottom:36px;}
.post-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:40px;}
.post-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}

/* NOTICES */
.notice{text-align:center;padding:16px;border-radius:10px;font-size:14px;font-weight:600;
  margin:0 48px 24px;max-width:760px;}
.notice-ok{background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.25);color:#34d399;}

/* EMPTY */
.empty{text-align:center;padding:80px 20px;color:var(--muted);}
.empty-icon{font-size:48px;margin-bottom:16px;opacity:.4;}

footer{border-top:1px solid var(--border);padding:32px 48px;display:flex;
  justify-content:space-between;align-items:center;color:var(--muted);font-size:12px;flex-wrap:wrap;gap:10px;}
footer span{color:var(--amber);}
</style>
</head>
<body>

<nav>
  <div class="logo">FoundationWorks</div>
  <div class="nav-links">
    <a href="?" class="nav-btn <?= $page!=='post'?'active':'' ?>">Browse Jobs</a>
    <a href="?page=post" class="nav-btn <?= $page==='post'?'active':'' ?>">Post a Job</a>
  </div>
  <a href="?page=post" class="nav-post">+ Hire Now</a>
</nav>

<?php if(isset($_GET["posted"])): ?>
<div class="notice notice-ok" style="margin:20px 48px">✓ Job posted successfully! It's now live on the board.</div>
<?php endif; ?>
<?php if(isset($_GET["applied"])): ?>
<div class="notice notice-ok" style="margin:20px 48px">✓ Application submitted! The employer will contact you soon.</div>
<?php endif; ?>

<?php if($page !== 'post'): ?>

<!-- HERO -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-content">
    <div class="hero-eyebrow">Tamil Nadu's #1 Construction Platform</div>
    <h1>BUILD YOUR<br><span>CAREER HERE</span></h1>
    <p class="hero-sub">From groundbreaking to skyline — find skilled workers and top construction jobs across every trade and speciality.</p>
    <div class="hero-stats">
      <div class="hstat"><div class="hstat-num"><?= $stats['tj']??0 ?></div><div class="hstat-label">Open Positions</div></div>
      <div class="hstat"><div class="hstat-num"><?= $apps['ta']??0 ?></div><div class="hstat-label">Applications</div></div>
      <div class="hstat"><div class="hstat-num"><?= count($cats_r) ?></div><div class="hstat-label">Specialities</div></div>
    </div>
  </div>
</section>

<!-- SEARCH -->
<div class="search-section">
  <form method="GET" class="search-bar">
    <input type="text" name="q" placeholder="🔍  Search job title, company, or location..." value="<?=htmlspecialchars($search)?>">
    <?php if($cat): ?><input type="hidden" name="cat" value="<?=htmlspecialchars($cat)?>"> <?php endif; ?>
    <select name="type" onchange="this.form.submit()">
      <option value="">All Types</option>
      <option value="Full-Time">Full-Time</option>
      <option value="Part-Time">Part-Time</option>
      <option value="Contract">Contract</option>
      <option value="Temporary">Temporary</option>
    </select>
    <button type="submit" class="btn-search">Search</button>
  </form>
</div>

<!-- CATEGORIES -->
<div class="cats-section">
  <span class="cat-label">Filter:</span>
  <a href="?" class="cat-pill <?= !$cat?'active':'' ?>">All Trades</a>
  <?php foreach($cats_r as $c): ?>
  <a href="?cat=<?=urlencode($c['category'])?>" class="cat-pill <?=$cat===$c['category']?'active':'' ?>">
    <?=($icons[$c['category']]??'🔧')?> <?=htmlspecialchars($c['category'])?> <sup style="color:var(--amber)"><?=$c['cnt']?></sup>
  </a>
  <?php endforeach; ?>
</div>

<!-- JOBS -->
<section class="jobs-section">
  <div class="section-header">
    <h2 class="section-title">Open Positions</h2>
    <span class="count-text"><?=$total?> job<?=$total!=1?'s':''?> found</span>
  </div>

  <?php if(empty($jobs)): ?>
  <div class="empty"><div class="empty-icon">🏗️</div><p>No jobs found. <a href="?page=post" style="color:var(--amber)">Post the first one!</a></p></div>
  <?php else: ?>
  <div class="jobs-grid">
    <?php foreach($jobs as $j):
      $type_class = ['Full-Time'=>'badge-full','Part-Time'=>'badge-part','Contract'=>'badge-contract','Temporary'=>'badge-temp'][$j['type']]??'badge-part';
    ?>
    <div class="job-card">
      <div class="job-card-top">
        <div class="job-icon"><?=($icons[$j['category']]??'🔧')?></div>
        <span class="job-type-badge <?=$type_class?>"><?=htmlspecialchars($j['type'])?></span>
      </div>
      <div class="job-title"><?=htmlspecialchars($j['title'])?></div>
      <div class="job-company">🏢 <?=htmlspecialchars($j['company'])?></div>
      <div class="job-meta">
        <span>📍 <?=htmlspecialchars($j['location'])?></span>
        <span>⏳ <?=htmlspecialchars($j['experience'])?></span>
        <span>🔨 <?=htmlspecialchars($j['category'])?></span>
      </div>
      <div class="job-desc"><?=htmlspecialchars($j['description'])?></div>
      <div class="job-footer">
        <span class="job-salary">₹<?=htmlspecialchars($j['salary'])?>/mo</span>
        <div style="display:flex;gap:8px;align-items:center">
          <form method="POST" style="display:inline">
            <input type="hidden" name="delete_job" value="<?=(int)$j['id']?>">
            <button class="btn-del" onclick="return confirm('Remove this job?')">✕</button>
          </form>
          <a href="?apply=<?=(int)$j['id']?>" class="btn-apply">Apply Now →</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<!-- APPLY MODAL -->
<?php if($apply_job): ?>
<div class="modal-overlay">
  <div class="modal">
    <h2>Apply for Position</h2>
    <p class="modal-sub">📌 <?=htmlspecialchars($apply_job['title'])?> &nbsp;·&nbsp; <?=htmlspecialchars($apply_job['company'])?></p>
    <form method="POST">
      <input type="hidden" name="job_id" value="<?=(int)$apply_job['id']?>">
      <div class="form-grid">
        <div class="field"><label>Full Name</label><input type="text" name="name" placeholder="Your full name" required></div>
        <div class="field"><label>Email</label><input type="email" name="email" placeholder="your@email.com" required></div>
        <div class="field"><label>Phone</label><input type="text" name="phone" placeholder="+91 XXXXX XXXXX" required></div>
        <div class="field"><label>Years of Experience</label>
          <select name="exp_years">
            <option>0–1 years (Fresher)</option><option>1–3 years</option>
            <option>3–5 years</option><option>5–10 years</option><option>10+ years</option>
          </select>
        </div>
        <div class="field full"><label>Cover Letter / Skills Summary</label>
          <textarea name="cover" placeholder="Describe your skills, certifications, and why you're the right fit..."></textarea>
        </div>
      </div>
      <div class="modal-btns">
        <a href="?" class="btn-cancel">← Cancel</a>
        <button type="submit" name="apply" class="btn-submit">Submit Application →</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php else: // POST JOB PAGE ?>

<section class="post-section">
  <h2>POST A JOB</h2>
  <p>Reach thousands of skilled construction workers across Tamil Nadu. Fill in the details below.</p>
  <div class="post-card">
    <form method="POST">
      <div class="post-grid">
        <div class="field"><label>Job Title</label><input type="text" name="title" placeholder="e.g. Site Engineer" required></div>
        <div class="field"><label>Company Name</label><input type="text" name="company" placeholder="e.g. BuildCore Pvt Ltd" required></div>
        <div class="field"><label>Location</label><input type="text" name="location" placeholder="e.g. Chennai, TN" required></div>
        <div class="field"><label>Employment Type</label>
          <select name="type">
            <option>Full-Time</option><option>Part-Time</option><option>Contract</option><option>Temporary</option>
          </select>
        </div>
        <div class="field"><label>Monthly Salary (₹)</label><input type="text" name="salary" placeholder="e.g. 35,000" required></div>
        <div class="field"><label>Experience Required</label>
          <select name="experience">
            <option>Fresher / No Experience</option><option>1–2 years</option>
            <option>2–5 years</option><option>5–10 years</option><option>10+ years</option>
          </select>
        </div>
        <div class="field"><label>Trade / Category</label>
          <select name="category">
            <?php foreach($categories as $c): ?><option><?=$c?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field full"><label>Job Description</label>
          <textarea name="description" style="min-height:110px" placeholder="Describe responsibilities, requirements, site location, safety requirements..." required></textarea>
        </div>
      </div>
      <button type="submit" name="post_job" class="btn-submit" style="width:100%;margin-top:24px;padding:16px;font-size:15px">🏗 Post Job — Go Live Now</button>
    </form>
  </div>
</section>
<?php endif; ?>

<footer>
  <div>FoundationWorks &copy; 2025 &nbsp;·&nbsp; <span>Tamil Nadu Construction Hiring Platform</span></div>
  <div><?=$stats['tj']??0?> Jobs · <?=$apps['ta']??0?> Applications · <?=count($cats_r)?> Trade Categories</div>
</footer>
</body>
</html>