<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect("localhost","root","","farmland_db");

$zones = [
  "Delta Zone"      => ["districts"=>["Thanjavur","Thiruvarur","Nagapattinam","Tiruchy"],"soil"=>"Alluvial Clay","crops"=>"Paddy, Sugarcane, Banana","ph"=>"6.5–7.5","organic"=>"High","water"=>"Excellent","emoji"=>"🌾"],
  "Western Zone"    => ["districts"=>["Coimbatore","Tiruppur","Erode","Salem","Namakkal"],"soil"=>"Red Loamy Soil","crops"=>"Maize, Cotton, Turmeric, Banana","ph"=>"6.0–7.0","organic"=>"Medium","water"=>"Good","emoji"=>"🌽"],
  "Southern Zone"   => ["districts"=>["Madurai","Virudhunagar","Ramanathapuram","Sivaganga","Dindigul"],"soil"=>"Black Cotton Soil","crops"=>"Cotton, Groundnut, Millets","ph"=>"7.5–8.5","organic"=>"Medium","water"=>"Moderate","emoji"=>"🌱"],
  "Northern Zone"   => ["districts"=>["Chennai","Vellore","Ranipet","Tiruvallur","Kanchipuram"],"soil"=>"Sandy Loam","crops"=>"Groundnut, Vegetables, Flowers","ph"=>"6.0–6.8","organic"=>"Low","water"=>"Moderate","emoji"=>"🥬"],
  "Hilly Zone"      => ["districts"=>["Nilgiris","Kodaikanal","Yercaud","Valparai","Ooty"],"soil"=>"Forest Loam Soil","crops"=>"Tea, Coffee, Cardamom, Pepper","ph"=>"4.5–6.0","organic"=>"Very High","water"=>"Excellent","emoji"=>"🍵"],
  "Dry Zone"        => ["districts"=>["Vellore","Dharmapuri","Krishnagiri","Tiruvannamalai"],"soil"=>"Red Ferruginous Soil","crops"=>"Mango, Tamarind, Millets","ph"=>"5.5–6.5","organic"=>"Low","water"=>"Poor","emoji"=>"🥭"],
  "Coastal Zone"    => ["districts"=>["Rameswaram","Thoothukudi","Cuddalore","Puducherry","Karaikal"],"soil"=>"Saline Sandy Soil","crops"=>"Cashew, Casuarina, Salt","ph"=>"7.0–8.5","organic"=>"Very Low","water"=>"Poor","emoji"=>"🥥"],
];

$result = null; $selZone = ""; $selDist = "";
if(isset($_POST["search"])) {
    $selDist = mysqli_real_escape_string($conn, trim($_POST["district"]));
    foreach($zones as $zone => $data) {
        foreach($data["districts"] as $d) {
            if(stripos($d, $selDist)!==false || stripos($selDist,$d)!==false) {
                $result = $data; $selZone = $zone; $selDist = $d; break 2;
            }
        }
    }
    if(!$result) { $result = "notfound"; }
}
if(isset($_POST["zone_select"])) {
    $selZone = $_POST["zone"];
    if(isset($zones[$selZone])) { $result = $zones[$selZone]; $selDist = $zones[$selZone]["districts"][0]; }
}

$waterColor = ["Excellent"=>"#22c55e","Good"=>"#84cc16","Moderate"=>"#f59e0b","Poor"=>"#ef4444","Very Low"=>"#dc2626","Very High"=>"#16a34a"];
$orgColor   = ["Very High"=>"#15803d","High"=>"#22c55e","Medium"=>"#84cc16","Low"=>"#f59e0b","Very Low"=>"#ef4444"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>TN FarmLand — Crop Zone Explorer</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --green:#16a34a;--green2:#22c55e;--green3:#4ade80;--lime:#84cc16;
  --dark:#0a1a0d;--card:#0f2a14;--card2:#122b17;
  --border:rgba(34,197,94,0.2);--text:#e8f5e9;--muted:#86a98b;--gold:#fbbf24;
}
body{font-family:'Nunito',sans-serif;background:var(--dark);color:var(--text);min-height:100vh;
  background-image:
    radial-gradient(ellipse 80% 50% at 50% -5%,rgba(22,163,74,0.18) 0%,transparent 65%),
    radial-gradient(ellipse 50% 40% at 90% 90%,rgba(132,204,22,0.08) 0%,transparent 60%),
    url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2316a34a' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");}

/* HEADER */
header{text-align:center;padding:52px 20px 32px;border-bottom:1px solid var(--border);position:relative;}
header::before{content:'🌿';font-size:48px;display:block;margin-bottom:10px;filter:drop-shadow(0 0 20px rgba(34,197,94,0.4));}
header::after{content:'';display:block;width:100px;height:2px;
  background:linear-gradient(90deg,transparent,var(--green2),transparent);margin:18px auto 0;}
.badge{display:inline-flex;align-items:center;gap:6px;background:rgba(22,163,74,0.12);
  border:1px solid var(--border);border-radius:20px;padding:5px 16px;font-size:11px;
  letter-spacing:2px;text-transform:uppercase;color:var(--green3);margin-bottom:14px;}
h1{font-family:'Playfair Display',serif;font-size:clamp(1.8rem,5vw,3rem);
  background:linear-gradient(135deg,#fff 20%,var(--green3) 60%,var(--lime));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:8px;}
header>p{color:var(--muted);font-size:14px;max-width:500px;margin:0 auto;}

main{max-width:1000px;margin:0 auto;padding:40px 20px 60px;}

/* SEARCH CARD */
.search-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:36px;margin-bottom:32px;}
.search-card h2{font-size:1.1rem;font-weight:800;margin-bottom:6px;display:flex;align-items:center;gap:8px;}
.search-card h2::before{content:'🔍';}
.search-card>p{color:var(--muted);font-size:13px;margin-bottom:24px;}
.tabs{display:flex;gap:2px;background:rgba(0,0,0,0.3);border-radius:10px;padding:4px;margin-bottom:24px;width:fit-content;}
.tab{padding:8px 20px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;
  color:var(--muted);border:none;background:none;font-family:'Nunito',sans-serif;transition:all .2s;}
.tab.active{background:var(--green);color:#fff;box-shadow:0 4px 12px rgba(22,163,74,0.4);}
.search-row{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;}
input[type=text]{flex:1;min-width:200px;background:var(--dark);border:1px solid var(--border);border-radius:10px;
  color:var(--text);font-family:'Nunito',sans-serif;font-size:14px;font-weight:600;
  padding:13px 18px;outline:none;transition:border-color .2s,box-shadow .2s;}
input[type=text]:focus{border-color:var(--green2);box-shadow:0 0 0 3px rgba(34,197,94,0.12);}
input::placeholder{color:var(--muted);}
select{flex:1;min-width:200px;background:var(--dark);border:1px solid var(--border);border-radius:10px;
  color:var(--text);font-family:'Nunito',sans-serif;font-size:14px;font-weight:600;
  padding:13px 18px;outline:none;cursor:pointer;}
select option{background:var(--card);}
.btn{padding:13px 32px;background:linear-gradient(135deg,var(--green),var(--lime));
  color:#0a1a0d;border:none;border-radius:10px;font-family:'Nunito',sans-serif;
  font-size:14px;font-weight:800;cursor:pointer;white-space:nowrap;letter-spacing:.5px;
  transition:transform .2s,box-shadow .2s;box-shadow:0 6px 20px rgba(22,163,74,0.35);}
.btn:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(22,163,74,0.5);}

/* ZONE PILLS */
.zone-pills{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px;}
.zone-pill{padding:7px 16px;background:rgba(34,197,94,0.06);border:1px solid var(--border);
  border-radius:20px;font-size:12px;font-weight:700;color:var(--muted);cursor:pointer;
  transition:all .2s;font-family:'Nunito',sans-serif;}
.zone-pill:hover{background:rgba(34,197,94,0.15);color:var(--green3);border-color:var(--green);}

/* NOT FOUND */
.notfound{text-align:center;padding:40px;background:rgba(239,68,68,0.05);
  border:1px solid rgba(239,68,68,0.2);border-radius:16px;margin-bottom:24px;}
.notfound span{font-size:36px;display:block;margin-bottom:10px;}
.notfound p{color:#fca5a5;}

/* RESULT */
.result-hero{background:linear-gradient(135deg,rgba(22,163,74,0.2),rgba(132,204,22,0.1));
  border:1px solid var(--green);border-radius:20px;padding:32px;margin-bottom:24px;position:relative;overflow:hidden;}
.result-hero::before{content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse 60% 60% at 50% 0%,rgba(34,197,94,0.1),transparent);}
.result-hero-top{display:flex;align-items:center;gap:16px;margin-bottom:20px;}
.zone-icon{font-size:52px;filter:drop-shadow(0 0 16px rgba(34,197,94,0.5));}
.zone-name{font-family:'Playfair Display',serif;font-size:1.8rem;color:var(--green3);}
.dist-name{color:var(--muted);font-size:14px;margin-top:4px;}
.soil-badge{display:inline-block;padding:6px 18px;background:rgba(251,191,36,0.15);
  border:1px solid rgba(251,191,36,0.3);border-radius:20px;color:var(--gold);font-size:13px;font-weight:700;}

.grid2{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:24px;}
.info-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px;}
.info-card h3{font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:16px;display:flex;align-items:center;gap:6px;}
.info-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(34,197,94,0.08);}
.info-row:last-child{border-bottom:none;}
.info-label{color:var(--muted);font-size:13px;}
.info-val{font-weight:700;font-size:13px;}
.pill{display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;}

.crops-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px;margin-bottom:24px;}
.crops-card h3{font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:16px;}
.crop-tags{display:flex;flex-wrap:wrap;gap:10px;}
.crop-tag{padding:8px 18px;background:rgba(34,197,94,0.1);border:1px solid var(--border);
  border-radius:20px;font-size:13px;font-weight:700;color:var(--green3);}

/* ALL ZONES TABLE */
.zones-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin-top:32px;}
.zone-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px;
  cursor:pointer;transition:all .25s;text-decoration:none;display:block;}
.zone-card:hover{border-color:var(--green);transform:translateY(-3px);box-shadow:0 12px 32px rgba(22,163,74,0.15);}
.zone-card-icon{font-size:28px;margin-bottom:10px;}
.zone-card-name{font-family:'Playfair Display',serif;font-size:1rem;color:var(--green3);margin-bottom:4px;}
.zone-card-soil{color:var(--muted);font-size:12px;margin-bottom:8px;}
.zone-card-dists{font-size:11px;color:var(--muted);line-height:1.6;}

footer{text-align:center;padding:24px;border-top:1px solid var(--border);color:var(--muted);font-size:12px;}
</style>
</head>
<body>
<header>
  <div class="badge">🌱 Tamil Nadu Agricultural Portal</div>
  <h1>FarmLand Crop Zone Explorer</h1>
  <p>Discover soil types, crop recommendations & agricultural insights for every region of Tamil Nadu.</p>
</header>

<main>
<!-- SEARCH -->
<div class="search-card">
  <h2>Find Your Zone</h2>
  <p>Search by district name or select a zone directly to get soil & crop details.</p>

  <form method="POST">
    <div class="search-row">
      <input type="text" name="district" placeholder="🔎  Enter district (e.g. Thanjavur, Coimbatore...)" value="<?=htmlspecialchars($selDist)?>">
      <button type="submit" name="search" class="btn">🌾 Search</button>
    </div>
  </form>

  <div style="margin:20px 0;color:var(--muted);font-size:12px;text-align:center;letter-spacing:1px;text-transform:uppercase;">— or browse by zone —</div>

  <form method="POST">
    <div class="search-row">
      <select name="zone">
        <option value="">Select a Zone...</option>
        <?php foreach($zones as $zn=>$zd): ?>
        <option value="<?=$zn?>" <?=$selZone===$zn?"selected":""?>><?=$zd["emoji"]?> <?=$zn?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" name="zone_select" class="btn">🗺️ Explore</button>
    </div>
  </form>

  <div class="zone-pills">
    <?php foreach($zones as $zn=>$zd): ?>
    <form method="POST" style="display:inline">
      <input type="hidden" name="zone" value="<?=$zn?>">
      <button type="submit" name="zone_select" class="zone-pill"><?=$zd["emoji"]?> <?=$zn?></button>
    </form>
    <?php endforeach; ?>
  </div>
</div>

<!-- NOT FOUND -->
<?php if($result==="notfound"): ?>
<div class="notfound">
  <span>🌵</span>
  <p>District "<strong><?=htmlspecialchars($_POST["district"])?></strong>" not found. Try: Thanjavur, Coimbatore, Madurai, Nilgiris, Vellore...</p>
</div>
<?php endif; ?>

<!-- RESULT -->
<?php if(is_array($result)): ?>
<div class="result-hero">
  <div class="result-hero-top">
    <div class="zone-icon"><?=$result["emoji"]?></div>
    <div>
      <div class="zone-name"><?=$selZone?></div>
      <div class="dist-name">📍 <?=$selDist?> &nbsp;·&nbsp; Tamil Nadu</div>
      <div style="margin-top:10px"><span class="soil-badge">🪨 <?=$result["soil"]?></span></div>
    </div>
  </div>
</div>

<div class="grid2">
  <div class="info-card">
    <h3>🧪 Soil Profile</h3>
    <div class="info-row"><span class="info-label">Soil Type</span><span class="info-val"><?=$result["soil"]?></span></div>
    <div class="info-row"><span class="info-label">pH Range</span><span class="info-val" style="color:var(--lime)"><?=$result["ph"]?></span></div>
    <div class="info-row">
      <span class="info-label">Organic Matter</span>
      <span class="pill" style="background:<?=$orgColor[$result["organic"]]?>22;color:<?=$orgColor[$result["organic"]]?>"><?=$result["organic"]?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Water Retention</span>
      <span class="pill" style="background:<?=$waterColor[$result["water"]]?>22;color:<?=$waterColor[$result["water"]]?>"><?=$result["water"]?></span>
    </div>
  </div>
  <div class="info-card">
    <h3>🗺️ Zone Districts</h3>
    <?php foreach($result["districts"] as $d): ?>
    <div class="info-row">
      <span class="info-label">📍 <?=$d?></span>
      <span class="pill" style="background:rgba(34,197,94,0.1);color:var(--green3)"><?=$selZone?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="crops-card">
  <h3>🌱 Recommended Crops</h3>
  <div class="crop-tags">
    <?php foreach(explode(",",$result["crops"]) as $crop): ?>
    <span class="crop-tag">🌿 <?=trim($crop)?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ALL ZONES OVERVIEW -->
<?php if(!is_array($result)): ?>
<div style="margin-top:8px">
  <div style="text-align:center;margin-bottom:20px">
    <span style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--muted)">All Agricultural Zones</span>
  </div>
  <div class="zones-grid">
    <?php foreach($zones as $zn=>$zd): ?>
    <form method="POST">
      <input type="hidden" name="zone" value="<?=$zn?>">
      <button type="submit" name="zone_select" class="zone-card" style="text-align:left;width:100%">
        <div class="zone-card-icon"><?=$zd["emoji"]?></div>
        <div class="zone-card-name"><?=$zn?></div>
        <div class="zone-card-soil">🪨 <?=$zd["soil"]?></div>
        <div class="zone-card-dists">📍 <?=implode(", ",$zd["districts"])?></div>
      </button>
    </form>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
</main>

<footer>🌾 TN FarmLand Explorer &copy; 2025 &nbsp;·&nbsp; Tamil Nadu Agricultural Soil & Crop Zone Portal &nbsp;·&nbsp; All rights reserved</footer>
</body>
</html>