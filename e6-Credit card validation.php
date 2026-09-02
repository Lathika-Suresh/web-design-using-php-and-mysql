<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect("localhost","root","","cardchecker_db");

// ── Luhn Algorithm ──────────────────────────────────────────────
function luhn(string $n): bool {
    $n = preg_replace('/\D/','',$n); $sum=0; $alt=false;
    for($i=strlen($n)-1;$i>=0;$i--){
        $d=(int)$n[$i]; if($alt){$d*=2; if($d>9)$d-=9;} $sum+=$d; $alt=!$alt;
    }
    return $sum%10===0;
}

// ── Card Detection ───────────────────────────────────────────────
function detectCard(string $n): array {
    $n=preg_replace('/\D/','',$n);
    $networks=[
        'Visa'            =>'/^4/',
        'Mastercard'      =>'/^5[1-5]|^2[2-7]/',
        'Amex'            =>'/^3[47]/',
        'Discover'        =>'/^6(?:011|5)/',
        'JCB'             =>'/^35(?:2[89]|[3-8])/',
        'Diners Club'     =>'/^3(?:0[0-5]|[68])/',
        'RuPay'           =>'/^6[0-9]{15}/',
        'UnionPay'        =>'/^62/',
        'Maestro'         =>'/^(?:5018|5020|5038|6304|6759|6761|6763)/',
    ];
    $network='Unknown';
    foreach($networks as $name=>$pat) if(preg_match($pat,$n)){$network=$name;break;}

    $mii_map=['0'=>'ISO/TC 68','1'=>'Airlines','2'=>'Airlines/Finance','3'=>'Travel/Entertainment',
              '4'=>'Banking/Finance','5'=>'Banking/Finance','6'=>'Merch/Banking','7'=>'Petroleum',
              '8'=>'Healthcare/Telecom','9'=>'National Assignment'];
    $mii=$mii_map[$n[0]]??'Unknown';
    $len=strlen($n);
    $valid_lengths=['Visa'=>[13,16],'Mastercard'=>[16],'Amex'=>[15],'Discover'=>[16],'JCB'=>[16],'Diners Club'=>[14],'UnionPay'=>[16,19],'Maestro'=>[12,13,14,15,16,18,19],'RuPay'=>[16]];
    $lenOk=isset($valid_lengths[$network]) ? in_array($len,$valid_lengths[$network]) : ($len>=13&&$len<=19);
    return ['network'=>$network,'mii'=>$mii,'bin'=>substr($n,0,6),'pan'=>substr($n,6),'length'=>$len,'length_ok'=>$lenOk,'first'=>$n[0]??''];
}

// ── Handle Submission ────────────────────────────────────────────
$result=null; $raw=''; $error='';
if(isset($_POST['validate'])){
    $raw=trim($_POST['cardnum']??'');
    $clean=preg_replace('/\D/','',$raw);
    if(strlen($clean)<13||strlen($clean)>19){ $error='Card number must be 13–19 digits.'; }
    else {
        $luhn=luhn($clean); $info=detectCard($clean);
        $status=$luhn&&$info['length_ok']?'VALID':'INVALID';
        $result=array_merge($info,['luhn'=>$luhn,'status'=>$status,'clean'=>$clean]);
        if($conn){
            $c=mysqli_real_escape_string($conn,$clean);
            $s=mysqli_real_escape_string($conn,$status);
            $net=mysqli_real_escape_string($conn,$info['network']);
            mysqli_query($conn,"INSERT INTO checks(card_masked,network,status) VALUES('".substr($c,0,6)."xxxxxx".substr($c,-4)."','$net','$s')");
        }
    }
}

// ── Recent checks from DB ─────────────────────────────────────────
$history=$conn?mysqli_fetch_all(mysqli_query($conn,"SELECT * FROM checks ORDER BY checked_at DESC LIMIT 5"),MYSQLI_ASSOC):[];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CardShield — Credit Card Validator</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#06080f;--surface:#0d1117;--card:#111827;--card2:#161f2e;
  --blue:#3b82f6;--cyan:#06b6d4;--green:#10b981;--red:#ef4444;--amber:#f59e0b;
  --border:rgba(59,130,246,0.15);--text:#f0f4ff;--muted:#6b7a99;
}
body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;
  background-image:
    radial-gradient(ellipse 80% 60% at 50% -20%,rgba(59,130,246,0.12),transparent),
    radial-gradient(ellipse 40% 40% at 90% 80%,rgba(6,182,212,0.06),transparent);}

/* NAV */
nav{display:flex;align-items:center;justify-content:space-between;padding:0 48px;height:64px;
  border-bottom:1px solid var(--border);background:rgba(6,8,15,0.8);backdrop-filter:blur(16px);
  position:sticky;top:0;z-index:100;}
.nav-logo{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;
  background:linear-gradient(90deg,var(--blue),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent;
  letter-spacing:1px;}
.nav-logo span{color:var(--text);-webkit-text-fill-color:var(--text);}
.nav-right{display:flex;align-items:center;gap:24px;}
.nav-right a{font-size:13px;color:var(--muted);text-decoration:none;letter-spacing:.5px;transition:color .2s;}
.nav-right a:hover{color:var(--text);}
.nav-badge{padding:6px 14px;background:rgba(59,130,246,0.1);border:1px solid var(--border);
  border-radius:20px;font-size:11px;color:var(--blue);letter-spacing:1px;}

/* HERO */
.hero{text-align:center;padding:72px 20px 48px;}
.hero-tag{display:inline-flex;align-items:center;gap:8px;padding:6px 18px;
  background:rgba(6,182,212,0.08);border:1px solid rgba(6,182,212,0.2);
  border-radius:20px;font-size:11px;letter-spacing:2px;text-transform:uppercase;
  color:var(--cyan);margin-bottom:24px;}
.hero-tag::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--cyan);animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(1.4)}}
.hero h1{font-family:'Syne',sans-serif;font-size:clamp(2.4rem,6vw,4.2rem);font-weight:800;
  line-height:1.05;margin-bottom:16px;letter-spacing:-1px;}
.hero h1 span{background:linear-gradient(90deg,var(--blue),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.hero p{color:var(--muted);font-size:16px;max-width:520px;margin:0 auto 40px;line-height:1.7;font-weight:400;}

/* CARD VISUAL */
.card-preview{width:360px;height:220px;margin:0 auto 48px;border-radius:20px;position:relative;overflow:hidden;
  background:linear-gradient(135deg,#1e3a5f,#0f2444,#1a1a3e);
  box-shadow:0 32px 80px rgba(0,0,0,0.5),0 0 0 1px rgba(255,255,255,0.06);
  transition:all .4s;}
.card-preview::before{content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse 60% 60% at 80% 20%,rgba(59,130,246,0.25),transparent),
             radial-gradient(ellipse 40% 40% at 20% 80%,rgba(6,182,212,0.15),transparent);}
.card-chip{position:absolute;left:28px;top:70px;width:44px;height:34px;border-radius:6px;
  background:linear-gradient(135deg,#f0c060,#c8962a);box-shadow:0 2px 8px rgba(0,0,0,0.3);}
.card-chip::after{content:'';position:absolute;inset:4px;border-radius:3px;border:1px solid rgba(255,255,255,0.3);}
.card-network{position:absolute;right:24px;top:22px;font-family:'Syne',sans-serif;font-size:15px;font-weight:800;
  color:rgba(255,255,255,0.9);letter-spacing:1px;}
.card-number{position:absolute;bottom:64px;left:0;right:0;text-align:center;
  font-size:18px;letter-spacing:4px;color:rgba(255,255,255,0.85);font-weight:500;}
.card-label{position:absolute;bottom:28px;left:28px;font-size:9px;letter-spacing:2px;
  text-transform:uppercase;color:rgba(255,255,255,0.4);}
.card-valid{position:absolute;bottom:28px;right:28px;font-size:12px;color:rgba(255,255,255,0.6);}
.card-preview.valid-card{background:linear-gradient(135deg,#0f3d2e,#0a2a1e,#0d3b2e);}
.card-preview.invalid-card{background:linear-gradient(135deg,#3d0f0f,#2a0a0a,#3b0d0d);}

/* INPUT FORM */
.form-wrap{max-width:640px;margin:0 auto;padding:0 20px;}
.input-group{position:relative;margin-bottom:16px;}
.card-input{width:100%;padding:18px 24px 18px 60px;background:var(--card);
  border:1.5px solid var(--border);border-radius:14px;color:var(--text);
  font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:500;
  letter-spacing:4px;outline:none;transition:border-color .25s,box-shadow .25s;}
.card-input:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(59,130,246,0.1);}
.card-input::placeholder{font-size:16px;letter-spacing:2px;color:var(--muted);}
.input-icon{position:absolute;left:20px;top:50%;transform:translateY(-50%);font-size:22px;pointer-events:none;}
.btn-validate{width:100%;padding:17px;background:linear-gradient(135deg,var(--blue),var(--cyan));
  color:#fff;border:none;border-radius:14px;font-family:'Space Grotesk',sans-serif;
  font-size:15px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;
  cursor:pointer;transition:all .25s;box-shadow:0 8px 32px rgba(59,130,246,0.3);}
.btn-validate:hover{transform:translateY(-2px);box-shadow:0 12px 40px rgba(59,130,246,0.5);}
.error-msg{padding:14px 20px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);
  border-radius:10px;color:#fca5a5;font-size:14px;margin-bottom:16px;text-align:center;}

/* RESULT */
.result-wrap{max-width:800px;margin:40px auto;padding:0 20px;}
.result-status{text-align:center;margin-bottom:32px;}
.status-badge{display:inline-flex;align-items:center;gap:10px;padding:12px 32px;
  border-radius:50px;font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;letter-spacing:2px;}
.status-valid{background:rgba(16,185,129,0.12);border:2px solid rgba(16,185,129,0.4);color:var(--green);}
.status-invalid{background:rgba(239,68,68,0.12);border:2px solid rgba(239,68,68,0.4);color:var(--red);}
.result-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:24px;}
.info-tile{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px 22px;transition:border-color .2s;}
.info-tile:hover{border-color:rgba(59,130,246,0.3);}
.tile-label{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;}
.tile-val{font-size:15px;font-weight:600;color:var(--text);}
.tile-val.ok{color:var(--green);}
.tile-val.fail{color:var(--red);}
.tile-val.net{color:var(--cyan);}

/* INFO SECTION */
.info-section{max-width:800px;margin:48px auto;padding:0 20px;}
.info-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:36px;}
.info-card h2{font-family:'Syne',sans-serif;font-size:1.4rem;margin-bottom:20px;
  background:linear-gradient(90deg,var(--blue),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.info-steps{display:flex;flex-direction:column;gap:14px;}
.step{display:flex;gap:16px;align-items:flex-start;}
.step-num{min-width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--cyan));
  display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;}
.step p{font-size:14px;color:var(--muted);line-height:1.6;padding-top:6px;}
.step p strong{color:var(--text);}

/* HISTORY */
.history-section{max-width:800px;margin:0 auto 60px;padding:0 20px;}
.section-title{font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:700;margin-bottom:16px;
  display:flex;align-items:center;gap:10px;}
.section-title::before{content:'';width:4px;height:20px;background:linear-gradient(var(--blue),var(--cyan));border-radius:2px;}
.history-table{width:100%;border-collapse:collapse;background:var(--card);border-radius:14px;overflow:hidden;border:1px solid var(--border);}
.history-table th{padding:12px 20px;text-align:left;font-size:10px;letter-spacing:2px;text-transform:uppercase;
  color:var(--muted);border-bottom:1px solid var(--border);}
.history-table td{padding:14px 20px;font-size:13px;border-bottom:1px solid rgba(59,130,246,0.06);}
.history-table tr:last-child td{border-bottom:none;}
.badge-ok{display:inline-block;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(16,185,129,0.12);color:var(--green);}
.badge-fail{display:inline-block;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(239,68,68,0.12);color:var(--red);}

footer{text-align:center;padding:28px;border-top:1px solid var(--border);color:var(--muted);font-size:12px;letter-spacing:1px;}
footer span{color:var(--blue);}
</style>
</head>
<body>

<nav>
  <div class="nav-logo">Card<span>Shield</span></div>
  <div class="nav-right">
    <a href="#">Validator</a>
    <a href="#">BIN Lookup</a>
    <a href="#">Docs</a>
    <span class="nav-badge">🔒 Secure Tool</span>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-tag">Free & Instant Validation</div>
  <h1>Credit Card<br><span>Validator & Checker</span></h1>
  <p>Verify any credit or debit card number using the Luhn algorithm. Detect network, BIN, MII and card length — instantly and securely.</p>

  <!-- Live Card Preview -->
  <div class="card-preview <?= $result ? ($result['status']==='VALID'?'valid-card':'invalid-card') : '' ?>" id="cardPreview">
    <div class="card-chip"></div>
    <div class="card-network" id="prevNetwork"><?= $result ? htmlspecialchars($result['network']) : 'NETWORK' ?></div>
    <div class="card-number" id="prevNumber">
      <?php if($result): echo wordwrap(str_repeat('•',(int)$result['length']-4),4,' ',true).' '.substr($result['clean'],-4);
      else: echo '•••• •••• •••• ••••'; endif; ?>
    </div>
    <div class="card-label">Card Number</div>
    <div class="card-valid"><?= $result ? ($result['status']==='VALID'?'✓ VALID':'✗ INVALID') : 'PENDING' ?></div>
  </div>

  <!-- Input Form -->
  <div class="form-wrap">
    <?php if($error): ?><div class="error-msg">⚠ <?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="POST">
      <div class="input-group">
        <span class="input-icon">💳</span>
        <input class="card-input" type="text" name="cardnum" id="cardInput"
               maxlength="23" placeholder="Enter card number..."
               value="<?= $result ? chunk_split($result['clean'],4,' ') : htmlspecialchars($raw) ?>"
               autocomplete="off" oninput="livePreview(this.value)">
      </div>
      <button type="submit" name="validate" class="btn-validate">🔍 Validate Credit Card</button>
    </form>
  </div>
</section>

<!-- RESULT -->
<?php if($result): ?>
<div class="result-wrap">
  <div class="result-status">
    <div class="status-badge <?= $result['status']==='VALID'?'status-valid':'status-invalid' ?>">
      <?= $result['status']==='VALID' ? '✓' : '✗' ?>
      &nbsp; <?= $result['status'] ?> CARD
    </div>
  </div>
  <div class="result-grid">
    <div class="info-tile">
      <div class="tile-label">Luhn Algorithm</div>
      <div class="tile-val <?= $result['luhn']?'ok':'fail' ?>"><?= $result['luhn']?'✓ Passed':'✗ Failed' ?></div>
    </div>
    <div class="info-tile">
      <div class="tile-label">Network / Brand</div>
      <div class="tile-val net"><?= htmlspecialchars($result['network']) ?></div>
    </div>
    <div class="info-tile">
      <div class="tile-label">BIN / IIN (First 6)</div>
      <div class="tile-val"><?= htmlspecialchars($result['bin']) ?></div>
    </div>
    <div class="info-tile">
      <div class="tile-label">Card Length</div>
      <div class="tile-val <?= $result['length_ok']?'ok':'fail' ?>"><?= $result['length'] ?> digits <?= $result['length_ok']?'✓':'✗' ?></div>
    </div>
    <div class="info-tile">
      <div class="tile-label">MII Industry</div>
      <div class="tile-val"><?= htmlspecialchars($result['mii']) ?></div>
    </div>
    <div class="info-tile">
      <div class="tile-label">First Digit (MII)</div>
      <div class="tile-val"><?= htmlspecialchars($result['first']) ?></div>
    </div>
    <div class="info-tile">
      <div class="tile-label">PAN (Account Segment)</div>
      <div class="tile-val" style="letter-spacing:1px">••••<?= substr($result['clean'],-4) ?></div>
    </div>
    <div class="info-tile">
      <div class="tile-label">Checksum</div>
      <div class="tile-val <?= $result['luhn']?'ok':'fail' ?>"><?= $result['luhn']?'Valid':'Invalid' ?></div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- HOW IT WORKS -->
<section class="info-section">
  <div class="info-card">
    <h2>How to Use CardShield Validator</h2>
    <div class="info-steps">
      <div class="step"><div class="step-num">1</div><p>Enter your full credit or debit card number in the field above. Spaces and hyphens are handled automatically.</p></div>
      <div class="step"><div class="step-num">2</div><p>Click <strong>Validate Credit Card</strong> to run the Luhn algorithm and network detection instantly.</p></div>
      <div class="step"><div class="step-num">3</div><p>Review results: <strong>Luhn Check</strong>, <strong>Network</strong>, <strong>BIN/IIN</strong>, <strong>MII Industry</strong>, and card length validity.</p></div>
      <div class="step"><div class="step-num">4</div><p><strong>No card data is stored</strong> — only a masked version (e.g. 411111xxxxxx1111) is logged for analytics.</p></div>
    </div>
  </div>
</section>

<!-- HISTORY -->
<?php if(!empty($history)): ?>
<div class="history-section">
  <div class="section-title">Recent Checks</div>
  <table class="history-table">
    <thead><tr><th>Masked Card</th><th>Network</th><th>Status</th><th>Time</th></tr></thead>
    <tbody>
    <?php foreach($history as $h): ?>
    <tr>
      <td style="font-family:monospace;letter-spacing:1px"><?=htmlspecialchars($h['card_masked'])?></td>
      <td><?=htmlspecialchars($h['network'])?></td>
      <td><span class="<?=$h['status']==='VALID'?'badge-ok':'badge-fail' ?>"><?=$h['status']?></span></td>
      <td style="color:var(--muted);font-size:12px"><?=date('H:i:s',strtotime($h['checked_at']))?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<footer>CardShield &copy; 2025 &nbsp;·&nbsp; <span>Luhn Algorithm Validator</span> &nbsp;·&nbsp; No card data is stored or transmitted</footer>

<script>
const networks={4:'VISA',5:'Mastercard',3:'Amex/Diners',6:'Discover/RuPay',2:'Mastercard'};
function livePreview(v){
  const n=v.replace(/\D/g,'');
  const preview=document.getElementById('prevNumber');
  const net=document.getElementById('prevNetwork');
  const card=document.getElementById('cardPreview');
  const chunks=n.match(/.{1,4}/g)||[];
  preview.textContent=chunks.join(' ')||(n||'•••• •••• •••• ••••');
  net.textContent=networks[parseInt(n[0])]||'NETWORK';
  card.className='card-preview';
}
// Auto-format input
document.getElementById('cardInput').addEventListener('input',function(){
  let v=this.value.replace(/\D/g,'').substring(0,19);
  this.value=v.match(/.{1,4}/g)?.join(' ')||v;
});
</script>
</body>
</html>