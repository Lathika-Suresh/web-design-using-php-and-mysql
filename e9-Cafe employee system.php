<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect("localhost","root","","cafe_db");

// Actions
if(isset($_POST["add_emp"])) {
    $n  = mysqli_real_escape_string($conn,$_POST["name"]);
    $ro = mysqli_real_escape_string($conn,$_POST["role"]);
    $sh = mysqli_real_escape_string($conn,$_POST["shift"]);
    $ph = mysqli_real_escape_string($conn,$_POST["phone"]);
    $sa = (float)$_POST["salary"];
    $st = mysqli_real_escape_string($conn,$_POST["status"]);
    mysqli_query($conn,"INSERT INTO employees(name,role,shift,phone,salary,status) VALUES('$n','$ro','$sh','$ph',$sa,'$st')");
    header("Location:".$_SERVER['PHP_SELF']."?tab=staff"); exit();
}
if(isset($_POST["delete_emp"])) {
    mysqli_query($conn,"DELETE FROM employees WHERE id=".(int)$_POST["delete_emp"]);
    header("Location:".$_SERVER['PHP_SELF']."?tab=staff"); exit();
}
if(isset($_POST["clock_in"])) {
    $eid=(int)$_POST["eid"];
    $open=mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM attendance WHERE emp_id=$eid AND clock_out IS NULL"));
    if(!$open) mysqli_query($conn,"INSERT INTO attendance(emp_id,clock_in) VALUES($eid,NOW())");
    header("Location:".$_SERVER['PHP_SELF']."?tab=attend"); exit();
}
if(isset($_POST["clock_out"])) {
    $eid=(int)$_POST["eid"];
    mysqli_query($conn,"UPDATE attendance SET clock_out=NOW() WHERE emp_id=$eid AND clock_out IS NULL");
    header("Location:".$_SERVER['PHP_SELF']."?tab=attend"); exit();
}

$tab = $_GET["tab"] ?? "home";

$emps      = $conn ? mysqli_fetch_all(mysqli_query($conn,"SELECT * FROM employees ORDER BY name"),MYSQLI_ASSOC) : [];
$total     = count($emps);
$on_duty   = $conn ? (mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM employees WHERE status='Active'"))['c']??0) : 0;
$today_att = $conn ? mysqli_fetch_all(mysqli_query($conn,"SELECT a.*,e.name,e.role FROM attendance a JOIN employees e ON a.emp_id=e.id WHERE DATE(a.clock_in)=CURDATE() ORDER BY a.clock_in DESC"),MYSQLI_ASSOC) : [];
$clocked_in_ids = array_column(array_filter($today_att,fn($r)=>!$r['clock_out']),'emp_id');

$roles  = ["Barista","Cashier","Chef","Server","Cleaner","Manager","Baker","Supervisor"];
$shifts = ["Morning (6AM–2PM)","Afternoon (2PM–10PM)","Night (10PM–6AM)","Full Day"];
$emojis = ["Barista"=>"☕","Cashier"=>"🧾","Chef"=>"👨‍🍳","Server"=>"🍽","Cleaner"=>"🧹","Manager"=>"📋","Baker"=>"🥐","Supervisor"=>"🌟"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>☕ Blossom Café — Staff System</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,500;0,700;1,300;1,500&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --rose:#f9d5e5;--peach:#fde8d8;--latte:#f5e6d3;--cream:#fdf8f2;--butter:#fef9e7;
  --brown:#7c5c42;--mocha:#5c3d2e;--caramel:#c08040;--dusty:#d4a5a5;
  --sage:#b8d4c8;--lavender:#d8d0f0;--sky:#c8e4f8;
  --text:#3d2b1f;--muted:#9b7b6a;--border:rgba(196,148,108,0.2);
  --white:#fffcf9;--card:rgba(255,252,249,0.9);
}
body{font-family:'Nunito',sans-serif;background:var(--cream);color:var(--text);min-height:100vh;
  background-image:
    radial-gradient(ellipse 70% 50% at 20% 10%,rgba(249,213,229,0.5),transparent),
    radial-gradient(ellipse 50% 60% at 85% 80%,rgba(253,232,216,0.5),transparent),
    radial-gradient(ellipse 40% 40% at 60% 40%,rgba(216,208,240,0.2),transparent);}
body::before{content:'☕ ✿ 🌸 ☕ ✿ 🌸 ☕ ✿ 🌸 ☕ ✿ 🌸 ☕ ✿ 🌸 ☕ ✿ 🌸 ☕ ✿';
  position:fixed;top:0;left:0;right:0;height:36px;display:flex;align-items:center;justify-content:center;
  font-size:13px;background:linear-gradient(90deg,var(--rose),var(--peach),var(--latte),var(--peach),var(--rose));
  letter-spacing:6px;opacity:.7;z-index:200;pointer-events:none;}

/* NAV */
nav{margin-top:36px;background:rgba(255,252,249,0.85);backdrop-filter:blur(20px);
  border-bottom:2px solid var(--border);padding:0 40px;display:flex;align-items:center;
  justify-content:space-between;height:68px;position:sticky;top:36px;z-index:100;
  box-shadow:0 4px 24px rgba(124,92,66,0.08);}
.logo{font-family:'Fraunces',serif;font-size:1.5rem;font-weight:700;color:var(--mocha);
  display:flex;align-items:center;gap:8px;}
.logo span{font-size:1.2rem;}
.nav-tabs{display:flex;gap:4px;}
.tab-btn{padding:8px 20px;border:none;background:none;font-family:'Nunito',sans-serif;
  font-size:13px;font-weight:700;color:var(--muted);cursor:pointer;border-radius:20px;
  transition:all .25s;text-decoration:none;display:inline-block;}
.tab-btn:hover{background:var(--rose);color:var(--mocha);}
.tab-btn.active{background:linear-gradient(135deg,var(--caramel),#d4904a);color:#fff;
  box-shadow:0 4px 14px rgba(192,128,64,0.35);}

/* HERO / HOME */
.home-hero{padding:60px 40px 40px;text-align:center;position:relative;}
.home-hero::before{content:'🌸';position:absolute;left:60px;top:40px;font-size:80px;opacity:.12;animation:float 4s ease-in-out infinite;}
.home-hero::after{content:'☕';position:absolute;right:60px;top:40px;font-size:80px;opacity:.12;animation:float 4s ease-in-out infinite .5s;}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
.home-hero h1{font-family:'Fraunces',serif;font-size:clamp(2.5rem,6vw,4rem);font-weight:700;
  color:var(--mocha);line-height:1.1;margin-bottom:10px;}
.home-hero h1 em{font-style:italic;color:var(--caramel);}
.home-hero p{color:var(--muted);font-size:15px;margin-bottom:36px;}
.stat-row{display:flex;justify-content:center;gap:20px;flex-wrap:wrap;}
.stat-tile{background:var(--card);border:2px solid var(--border);border-radius:20px;
  padding:24px 36px;text-align:center;box-shadow:0 8px 24px rgba(124,92,66,0.08);
  transition:transform .25s;}
.stat-tile:hover{transform:translateY(-4px);}
.stat-num{font-family:'Fraunces',serif;font-size:2.8rem;font-weight:700;color:var(--caramel);line-height:1;}
.stat-label{font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-top:6px;}
.stat-icon{font-size:1.6rem;margin-bottom:8px;}

/* SECTION WRAPPER */
.section{max-width:1100px;margin:0 auto;padding:32px 40px 60px;}
.section-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.section-title{font-family:'Fraunces',serif;font-size:2rem;font-weight:700;color:var(--mocha);}
.section-title small{font-size:.9rem;color:var(--muted);font-family:'Nunito',sans-serif;font-weight:600;}

/* ADD FORM */
.add-card{background:var(--card);border:2px solid var(--border);border-radius:24px;
  padding:32px;margin-bottom:32px;box-shadow:0 8px 32px rgba(124,92,66,0.07);}
.add-card h3{font-family:'Fraunces',serif;font-size:1.3rem;color:var(--mocha);margin-bottom:20px;
  display:flex;align-items:center;gap:8px;}
.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;}
.field label{font-size:10px;font-weight:800;letter-spacing:2px;text-transform:uppercase;
  color:var(--muted);display:block;margin-bottom:6px;}
.field input,.field select{width:100%;background:var(--butter);border:2px solid var(--border);
  border-radius:12px;color:var(--text);font-family:'Nunito',sans-serif;font-size:13px;font-weight:600;
  padding:11px 14px;outline:none;transition:border-color .2s,box-shadow .2s;}
.field input:focus,.field select:focus{border-color:var(--caramel);box-shadow:0 0 0 3px rgba(192,128,64,0.12);}
.field select option{background:var(--cream);}
.btn-add{padding:11px 28px;background:linear-gradient(135deg,#e8a070,var(--caramel));
  color:#fff;border:none;border-radius:12px;font-family:'Nunito',sans-serif;
  font-size:13px;font-weight:800;cursor:pointer;letter-spacing:.5px;
  transition:all .25s;box-shadow:0 4px 16px rgba(192,128,64,0.3);}
.btn-add:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(192,128,64,0.4);}

/* EMPLOYEE GRID */
.emp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:16px;}
.emp-card{background:var(--card);border:2px solid var(--border);border-radius:22px;
  padding:24px;transition:all .3s;position:relative;overflow:hidden;}
.emp-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;
  border-radius:22px 22px 0 0;}
.emp-card.role-Barista::before{background:linear-gradient(90deg,#f9d5e5,#fbbccc);}
.emp-card.role-Chef::before{background:linear-gradient(90deg,#fde8d8,#f5c9a0);}
.emp-card.role-Manager::before{background:linear-gradient(90deg,var(--lavender),#c8bef0);}
.emp-card.role-Server::before{background:linear-gradient(90deg,var(--sage),#9ecfc0);}
.emp-card.role-Cashier::before{background:linear-gradient(90deg,var(--sky),#a8d8f0);}
.emp-card.role-Cleaner::before,.emp-card.role-Baker::before,.emp-card.role-Supervisor::before{background:linear-gradient(90deg,var(--latte),#e8d4b8);}
.emp-card:hover{transform:translateY(-5px);box-shadow:0 20px 48px rgba(124,92,66,0.14);border-color:rgba(192,128,64,0.3);}
.emp-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;}
.emp-avatar{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:24px;background:linear-gradient(135deg,var(--rose),var(--peach));border:3px solid var(--border);}
.status-dot{width:10px;height:10px;border-radius:50%;margin-top:4px;}
.dot-active{background:#5cb85c;box-shadow:0 0 0 3px rgba(92,184,92,0.2);}
.dot-inactive{background:#d4a5a5;box-shadow:0 0 0 3px rgba(212,165,165,0.2);}
.emp-name{font-family:'Fraunces',serif;font-size:1.15rem;font-weight:700;color:var(--mocha);margin-bottom:3px;}
.emp-role{font-size:12px;font-weight:700;color:var(--caramel);letter-spacing:.5px;margin-bottom:12px;}
.emp-info{font-size:12px;color:var(--muted);line-height:1.8;}
.emp-info span{display:flex;align-items:center;gap:6px;}
.emp-footer{display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding-top:14px;border-top:1px dashed var(--border);}
.emp-salary{font-family:'Fraunces',serif;font-size:1.1rem;font-weight:700;color:var(--brown);}
.btn-del{background:rgba(212,165,165,0.2);border:2px solid rgba(212,165,165,0.4);
  color:var(--dusty);border-radius:10px;padding:5px 12px;font-size:11px;font-weight:700;
  cursor:pointer;transition:all .2s;font-family:'Nunito',sans-serif;}
.btn-del:hover{background:rgba(212,165,165,0.4);color:#c0606060;}

/* ATTENDANCE */
.attend-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;margin-bottom:32px;}
.attend-card{background:var(--card);border:2px solid var(--border);border-radius:18px;
  padding:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;
  box-shadow:0 4px 16px rgba(124,92,66,0.06);transition:all .25s;}
.attend-card:hover{transform:translateY(-2px);}
.attend-info{flex:1;}
.attend-name{font-family:'Fraunces',serif;font-size:1rem;font-weight:700;color:var(--mocha);}
.attend-role{font-size:11px;color:var(--muted);font-weight:600;}
.btn-clock{padding:8px 18px;border:none;border-radius:10px;font-family:'Nunito',sans-serif;
  font-size:12px;font-weight:800;cursor:pointer;transition:all .2s;white-space:nowrap;}
.btn-in{background:linear-gradient(135deg,#a8e6cf,#78d4ab);color:#1a5c3a;}
.btn-in:hover{box-shadow:0 4px 14px rgba(120,212,171,0.5);}
.btn-out{background:linear-gradient(135deg,var(--rose),var(--dusty));color:var(--mocha);}
.btn-out:hover{box-shadow:0 4px 14px rgba(249,213,229,0.7);}

/* LOG TABLE */
.log-table{width:100%;border-collapse:collapse;background:var(--card);
  border-radius:18px;overflow:hidden;border:2px solid var(--border);}
.log-table th{padding:13px 20px;text-align:left;font-size:10px;letter-spacing:2px;
  text-transform:uppercase;color:var(--muted);background:var(--butter);border-bottom:2px solid var(--border);font-weight:800;}
.log-table td{padding:13px 20px;font-size:13px;font-weight:600;border-bottom:1px dashed rgba(196,148,108,0.15);}
.log-table tr:last-child td{border-bottom:none;}
.log-table tr:hover td{background:rgba(249,213,229,0.15);}
.pill{display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:800;}
.pill-in{background:rgba(168,230,207,0.3);color:#2d8a5a;}
.pill-done{background:rgba(249,213,229,0.4);color:#a05070;}

/* EMPTY */
.empty{text-align:center;padding:60px 20px;color:var(--muted);}
.empty-icon{font-size:52px;margin-bottom:12px;animation:float 3s ease-in-out infinite;}

footer{text-align:center;padding:28px;border-top:2px dashed var(--border);
  color:var(--muted);font-size:12px;font-weight:700;letter-spacing:1px;}
footer span{color:var(--caramel);}
</style>
</head>
<body>

<nav>
  <div class="logo"><span>☕</span> Blossom Café</div>
  <div class="nav-tabs">
    <a href="?" class="tab-btn <?= $tab==='home'?'active':'' ?>">🏠 Home</a>
    <a href="?tab=staff" class="tab-btn <?= $tab==='staff'?'active':'' ?>">👩‍💼 Staff</a>
    <a href="?tab=attend" class="tab-btn <?= $tab==='attend'?'active':'' ?>">🕐 Attendance</a>
  </div>
</nav>

<?php if($tab==='home'): ?>
<div class="home-hero">
  <h1>Welcome to<br><em>Blossom Café</em> ✿</h1>
  <p>Your cozy crew management system — track staff, shifts & smiles 🌸</p>
  <div class="stat-row">
    <div class="stat-tile"><div class="stat-icon">👩‍💼</div><div class="stat-num"><?=$total?></div><div class="stat-label">Total Staff</div></div>
    <div class="stat-tile"><div class="stat-icon">✅</div><div class="stat-num"><?=$on_duty?></div><div class="stat-label">Active</div></div>
    <div class="stat-tile"><div class="stat-icon">📅</div><div class="stat-num"><?=count($today_att)?></div><div class="stat-label">Today's Shifts</div></div>
    <div class="stat-tile"><div class="stat-icon">⏰</div><div class="stat-num"><?=count($clocked_in_ids)?></div><div class="stat-label">Clocked In</div></div>
  </div>
</div>

<?php elseif($tab==='staff'): ?>
<div class="section">
  <div class="section-head">
    <h2 class="section-title">Our Team ✿ <small><?=$total?> members</small></h2>
  </div>

  <div class="add-card">
    <h3>🌸 Add New Team Member</h3>
    <form method="POST">
      <div class="form-grid">
        <div class="field"><label>Full Name</label><input type="text" name="name" placeholder="e.g. Priya Sharma" required></div>
        <div class="field"><label>Role</label>
          <select name="role"><?php foreach($roles as $r): ?><option><?=$r?></option><?php endforeach; ?></select>
        </div>
        <div class="field"><label>Shift</label>
          <select name="shift"><?php foreach($shifts as $s): ?><option><?=$s?></option><?php endforeach; ?></select>
        </div>
        <div class="field"><label>Phone</label><input type="text" name="phone" placeholder="98765 43210"></div>
        <div class="field"><label>Salary (₹/mo)</label><input type="number" name="salary" placeholder="18000" min="0"></div>
        <div class="field"><label>Status</label>
          <select name="status"><option>Active</option><option>Inactive</option></select>
        </div>
      </div>
      <div style="margin-top:18px"><button type="submit" name="add_emp" class="btn-add">✿ Add to Team</button></div>
    </form>
  </div>

  <?php if(empty($emps)): ?>
  <div class="empty"><div class="empty-icon">☕</div><p>No staff yet! Add your first team member above.</p></div>
  <?php else: ?>
  <div class="emp-grid">
    <?php foreach($emps as $e): ?>
    <div class="emp-card role-<?=htmlspecialchars($e['role'])?>">
      <div class="emp-top">
        <div class="emp-avatar"><?=($emojis[$e['role']]??'👤')?></div>
        <div class="status-dot <?= $e['status']==='Active'?'dot-active':'dot-inactive' ?>"></div>
      </div>
      <div class="emp-name"><?=htmlspecialchars($e['name'])?></div>
      <div class="emp-role"><?=($emojis[$e['role']]??'').' '.htmlspecialchars($e['role'])?></div>
      <div class="emp-info">
        <span>🕐 <?=htmlspecialchars($e['shift'])?></span>
        <span>📞 <?=htmlspecialchars($e['phone'])?></span>
        <span>🌿 <?=htmlspecialchars($e['status'])?></span>
      </div>
      <div class="emp-footer">
        <div class="emp-salary">₹<?=number_format($e['salary'])?></div>
        <form method="POST" style="display:inline">
          <input type="hidden" name="delete_emp" value="<?=(int)$e['id']?>">
          <button class="btn-del" onclick="return confirm('Remove <?=htmlspecialchars($e["name"])?> from team?')">✕ Remove</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php elseif($tab==='attend'): ?>
<div class="section">
  <div class="section-head">
    <h2 class="section-title">Attendance 🕐 <small>Today's Shifts</small></h2>
  </div>

  <?php if(empty($emps)): ?>
  <div class="empty"><div class="empty-icon">🌸</div><p>No staff found. <a href="?tab=staff" style="color:var(--caramel)">Add team members first!</a></p></div>
  <?php else: ?>
  <div class="attend-grid">
    <?php foreach($emps as $e):
      $is_in = in_array($e['id'], $clocked_in_ids);
    ?>
    <div class="attend-card">
      <div style="font-size:26px"><?=($emojis[$e['role']]??'👤')?></div>
      <div class="attend-info">
        <div class="attend-name"><?=htmlspecialchars($e['name'])?></div>
        <div class="attend-role"><?=htmlspecialchars($e['role'])?> · <?= $is_in ? '<span style="color:#2d8a5a;font-weight:800">● On Duty</span>' : '<span style="color:var(--muted)">Off Duty</span>' ?></div>
      </div>
      <form method="POST">
        <input type="hidden" name="eid" value="<?=(int)$e['id']?>">
        <?php if($is_in): ?>
          <button name="clock_out" class="btn-clock btn-out">Clock Out 🌙</button>
        <?php else: ?>
          <button name="clock_in" class="btn-clock btn-in">Clock In ☀️</button>
        <?php endif; ?>
      </form>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if(!empty($today_att)): ?>
  <h3 style="font-family:'Fraunces',serif;color:var(--mocha);margin-bottom:16px;font-size:1.3rem;">📋 Today's Log</h3>
  <table class="log-table">
    <thead><tr><th>Staff</th><th>Role</th><th>Clock In</th><th>Clock Out</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach($today_att as $a): ?>
    <tr>
      <td style="font-weight:700"><?=htmlspecialchars($a['name'])?></td>
      <td><?=htmlspecialchars($a['role'])?></td>
      <td><?=date('h:i A',strtotime($a['clock_in']))?></td>
      <td><?= $a['clock_out'] ? date('h:i A',strtotime($a['clock_out'])) : '—' ?></td>
      <td><span class="pill <?= $a['clock_out']?'pill-done':'pill-in' ?>"><?= $a['clock_out']?'Done':'On Duty' ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<footer>☕ Blossom Café Staff System &nbsp;·&nbsp; <span>Made with love & lattes</span> &nbsp;·&nbsp; 2025 🌸</footer>
</body>
</html>