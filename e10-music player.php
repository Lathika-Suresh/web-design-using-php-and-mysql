<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect("localhost","root","","musicplayer_db");
if(!$conn) die("Database Connection Failed");

$uploadDir = "uploads/";
if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS songs(
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    filename VARCHAR(255),
    play_count INT DEFAULT 0,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// UPLOAD — copied exactly from working code
if(isset($_POST['upload'])){
    if(isset($_FILES['song']) && $_FILES['song']['error']==0){
        $allowed=['mp3','wav','ogg','m4a','flac','aac'];
        $ext=strtolower(pathinfo($_FILES['song']['name'],PATHINFO_EXTENSION));
        if(in_array($ext,$allowed)){
            $title=mysqli_real_escape_string($conn,pathinfo($_FILES['song']['name'],PATHINFO_FILENAME));
            $newName=time()."_".rand(1000,9999).".".$ext;
            if(move_uploaded_file($_FILES['song']['tmp_name'],$uploadDir.$newName)){
                mysqli_query($conn,"INSERT INTO songs(title,filename) VALUES('$title','$newName')");
            }
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']); exit();
}

// DELETE
if(isset($_POST['delete'])){
    $id=(int)$_POST['delete'];
    $q=mysqli_query($conn,"SELECT * FROM songs WHERE id=$id");
    $song=mysqli_fetch_assoc($q);
    if($song){
        if(file_exists($uploadDir.$song['filename'])) unlink($uploadDir.$song['filename']);
        mysqli_query($conn,"DELETE FROM songs WHERE id=$id");
    }
    header("Location: ".$_SERVER['PHP_SELF']); exit();
}

// PING PLAYED
if(isset($_POST['ping'])){
    mysqli_query($conn,"UPDATE songs SET play_count=play_count+1,played_at=NOW() WHERE id=".(int)$_POST['ping']);
    exit("ok");
}

$res=$conn ? mysqli_query($conn,"SELECT * FROM songs ORDER BY id DESC") : null;
$songs=[];
if($res) while($row=mysqli_fetch_assoc($res)) $songs[]=$row;

$res2=$conn ? mysqli_query($conn,"SELECT * FROM songs WHERE play_count>0 ORDER BY played_at DESC LIMIT 6") : null;
$recent=[];
if($res2) while($row=mysqli_fetch_assoc($res2)) $recent[]=$row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>🎀 Tape & Tune</title>
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --rose:#f48fb1;--rosedark:#e57399;--coral:#ffab76;
  --blush:#fce4ec;--ink:#3d2c2c;--muted:#b08080;
  --border:rgba(200,150,150,0.25);--paper:#fdf9f6;
}
body{font-family:'Nunito',sans-serif;background:var(--paper);color:var(--ink);min-height:100vh;
  background-image:
    radial-gradient(ellipse 70% 50% at 15% 10%,rgba(252,228,236,0.8),transparent),
    radial-gradient(ellipse 60% 45% at 85% 80%,rgba(237,231,246,0.6),transparent);}

.fn{position:fixed;font-size:18px;pointer-events:none;z-index:0;opacity:.4;
  animation:fup linear infinite;}
@keyframes fup{0%{transform:translateY(100vh) rotate(0deg);opacity:.5}100%{transform:translateY(-5vh) rotate(360deg);opacity:0}}

/* HEADER */
header{text-align:center;padding:28px 20px 8px;position:relative;z-index:1;}
.tag{display:inline-block;background:rgba(244,143,177,0.15);border:2px dashed var(--rose);
  border-radius:30px;padding:4px 18px;font-size:10px;font-weight:800;letter-spacing:3px;
  text-transform:uppercase;color:var(--rose);margin-bottom:10px;}
header h1{font-family:'Caveat',cursive;font-size:clamp(2.2rem,7vw,3.6rem);font-weight:700;color:var(--ink);}
header h1 em{color:var(--rose);font-style:normal;}
header p{color:var(--muted);font-size:13px;margin-top:3px;}

/* ── UPLOAD SECTION — plain and simple, no modal, no JS tricks ── */
.upload-section{
  max-width:500px;margin:14px auto 18px;padding:0 20px;
  text-align:center;position:relative;z-index:1;
}
.upload-box{
  background:rgba(255,255,255,0.8);
  border:3px dashed var(--rose);
  border-radius:20px;
  padding:24px 20px;
  box-shadow:0 8px 24px rgba(244,143,177,0.15);
}
.upload-box p{
  font-family:'Caveat',cursive;font-size:1.2rem;
  color:var(--muted);margin-bottom:14px;
}
/* Style the file input */
.upload-box input[type=file]{
  display:block;
  width:100%;
  margin-bottom:14px;
  font-family:'Nunito',sans-serif;
  font-size:13px;
  color:var(--ink);
  cursor:pointer;
}
.upload-box input[type=file]::file-selector-button{
  background:linear-gradient(135deg,var(--rose),var(--coral));
  color:#fff;border:none;border-radius:20px;
  padding:8px 18px;font-family:'Nunito',sans-serif;
  font-size:13px;font-weight:700;cursor:pointer;margin-right:10px;
}
.upload-box input[type=file]::file-selector-button:hover{
  background:var(--rosedark);
}
.import-btn{
  background:linear-gradient(135deg,var(--rose),var(--coral));
  color:#fff;border:none;border-radius:30px;
  padding:12px 32px;font-family:'Nunito',sans-serif;
  font-size:15px;font-weight:800;cursor:pointer;
  box-shadow:0 5px 16px rgba(244,143,177,0.4);
  transition:all .2s;
}
.import-btn:hover{transform:translateY(-2px);box-shadow:0 7px 22px rgba(244,143,177,0.6);}

/* BOOMBOX */
.player-wrap{max-width:500px;margin:0 auto;padding:0 20px 14px;position:relative;z-index:1;}
.boombox{background:linear-gradient(160deg,#ddd0d0 0%,#bca8a8 35%,#cbb8b8 100%);
  border-radius:24px;padding:16px 18px 12px;border:3px solid #a89090;
  box-shadow:0 14px 40px rgba(80,40,40,.22),inset 0 2px 0 rgba(255,255,255,.35);
  position:relative;}
.antenna{position:absolute;top:-42px;left:28px;width:5px;height:46px;
  background:linear-gradient(to top,#907070,#c0a8a8);border-radius:3px;}
.antenna::before{content:'';position:absolute;top:-6px;left:50%;transform:translateX(-50%);
  width:11px;height:11px;border-radius:50%;background:#c8a0a0;border:2px solid #a07878;}
.bb-top{display:flex;align-items:center;gap:6px;background:rgba(0,0,0,.12);
  border-radius:10px;padding:6px 10px;margin-bottom:10px;}
.led{width:8px;height:8px;border-radius:50%;}
.led.r{background:#e88888;}
.led.g{background:#555;transition:background .3s,box-shadow .3s;}
.led.g.lit{background:#88c888;box-shadow:0 0 8px rgba(136,200,136,.8);}
.rail{flex:1;height:5px;background:rgba(0,0,0,.15);border-radius:3px;}
.vu{display:flex;gap:2px;align-items:flex-end;height:16px;}
.vub{width:4px;background:var(--rose);border-radius:2px;height:4px;transition:height .12s;}
.bb-mid{display:grid;grid-template-columns:1fr 138px 1fr;gap:10px;align-items:center;margin-bottom:10px;}
.spk{aspect-ratio:1;background:#3a2828;border-radius:50%;border:4px solid #5a3a3a;
  display:flex;align-items:center;justify-content:center;
  box-shadow:inset 0 3px 10px rgba(0,0,0,.5);}
.spk-in{width:62%;height:62%;border-radius:50%;
  background:repeating-radial-gradient(circle,transparent 0,transparent 5px,rgba(255,255,255,.05) 5px,rgba(255,255,255,.05) 6px);
  border:2px solid rgba(255,255,255,.07);}
.spk.on .spk-in{animation:vib .3s ease-in-out infinite alternate;}
@keyframes vib{from{transform:scale(.91)}to{transform:scale(1.09)}}
.slot{background:#1e1212;border-radius:14px;padding:9px;border:2px solid #2e1818;
  box-shadow:inset 0 4px 10px rgba(0,0,0,.5);}
.reels{background:#110a0a;border-radius:8px;padding:7px 5px;
  display:flex;align-items:center;justify-content:space-around;margin-bottom:5px;}
.reel{width:25px;height:25px;border-radius:50%;background:#2e1818;border:3px solid #4a2828;position:relative;}
.reel::after{content:'';position:absolute;inset:5px;border-radius:50%;background:#907070;}
.reel.spin{animation:rspin 1s linear infinite;}
@keyframes rspin{to{transform:rotate(360deg)}}
.slot-title{font-family:'Caveat',cursive;font-size:11px;color:var(--rose);
  text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:116px;}
.prog-wrap{margin-bottom:7px;}
.prog-bar{width:100%;height:7px;background:rgba(0,0,0,.2);border-radius:4px;cursor:pointer;}
.prog-fill{height:100%;background:linear-gradient(90deg,var(--rose),var(--coral));border-radius:4px;width:0%;}
.times{display:flex;justify-content:space-between;margin-top:2px;}
.times span{font-size:10px;color:#8a6060;font-weight:700;font-family:'Caveat',cursive;}
.ctrls{display:flex;justify-content:center;align-items:center;gap:8px;margin-bottom:7px;}
.cbtn{width:35px;height:35px;border-radius:50%;border:2px solid #a88080;background:#ceb8b8;
  cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;
  box-shadow:0 3px 6px rgba(0,0,0,.12);transition:all .15s;user-select:none;}
.cbtn:hover{background:#decccc;transform:scale(1.1);}
.cbtn:active{transform:scale(.95);}
.cbtn.big{width:48px;height:48px;font-size:20px;
  background:linear-gradient(135deg,var(--rose),var(--coral));
  border-color:var(--rosedark);color:#fff;
  box-shadow:0 5px 18px rgba(244,143,177,.5);}
.cbtn.big:hover{box-shadow:0 7px 24px rgba(244,143,177,.7);}
.extras{display:flex;align-items:center;justify-content:center;gap:10px;}
.extras span{font-size:14px;}
.extras input[type=range]{width:90px;accent-color:var(--rose);}
.shuf{font-size:12px;padding:4px 12px;border:2px solid var(--border);border-radius:20px;
  background:none;cursor:pointer;color:var(--muted);font-family:'Nunito',sans-serif;font-weight:700;}
.shuf.on{background:var(--blush);border-color:var(--rose);color:var(--rosedark);}

/* SHELF */
.shelf-wrap{max-width:560px;margin:0 auto;padding:0 20px 16px;position:relative;z-index:1;}
.shelf-label{font-family:'Caveat',cursive;font-size:1.4rem;font-weight:700;color:var(--ink);margin-bottom:8px;}
.shelf{background:linear-gradient(180deg,#c49a6c 0%,#a07040 40%,#8b5e3c 100%);
  border-radius:10px 10px 4px 4px;padding:12px 12px 0;
  box-shadow:0 8px 24px rgba(80,40,10,.35),inset 0 2px 0 rgba(255,255,255,.15);
  border:2px solid #6b4226;position:relative;}
.shelf::before{content:'';position:absolute;inset:0;border-radius:10px 10px 4px 4px;
  background:repeating-linear-gradient(90deg,transparent,transparent 38px,rgba(0,0,0,.03) 38px,rgba(0,0,0,.03) 39px);
  pointer-events:none;}
.shelf-row{display:flex;gap:8px;align-items:flex-end;min-height:86px;overflow-x:auto;
  padding-bottom:0;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.2) transparent;}
.shelf-plank{height:15px;background:linear-gradient(180deg,#5c3317 0%,#3d200a 100%);
  margin:0 -12px;border-radius:0 0 4px 4px;
  box-shadow:0 4px 12px rgba(0,0,0,.4),inset 0 2px 0 rgba(255,255,255,.08);
  border-top:2px solid #7a4020;}
.mcass{flex-shrink:0;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:2px;
  padding-bottom:2px;transition:transform .2s cubic-bezier(.34,1.56,.64,1);}
.mcass:hover{transform:translateY(-6px);}
.mcass-title{font-family:'Caveat',cursive;font-size:10px;font-weight:700;
  color:rgba(255,255,255,.85);text-align:center;max-width:68px;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  text-shadow:0 1px 3px rgba(0,0,0,.5);}
.shelf-empty{font-family:'Caveat',cursive;font-size:.95rem;color:rgba(255,255,255,.5);
  padding:18px;text-align:center;width:100%;}

/* PLAYLIST */
.pl-wrap{max-width:560px;margin:0 auto;padding:0 20px 50px;position:relative;z-index:1;}
.pl-label{font-family:'Caveat',cursive;font-size:1.4rem;font-weight:700;color:var(--ink);margin-bottom:8px;}
.srow{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.72);
  border:2px solid var(--border);border-radius:14px;padding:9px 12px;margin-bottom:7px;
  cursor:pointer;transition:all .2s;backdrop-filter:blur(6px);}
.srow:hover{border-color:var(--rose);transform:translateX(4px);}
.srow.active{border-color:var(--rose);background:rgba(252,228,236,.65);}
.snum{width:24px;height:24px;border-radius:50%;background:var(--blush);
  display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:var(--rose);flex-shrink:0;}
.sinfo{flex:1;min-width:0;}
.stitle{font-size:13px;font-weight:700;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.srow.active .stitle{color:var(--rose);}
.sdur{font-size:10px;color:var(--muted);margin-top:1px;}
.eq{display:none;gap:2px;align-items:flex-end;height:14px;flex-shrink:0;}
.srow.active .eq{display:flex;}
.eqb{width:3px;border-radius:1px;background:var(--rose);animation:eqa .5s ease-in-out infinite alternate;}
.eqb:nth-child(1){height:5px;} .eqb:nth-child(2){height:11px;animation-delay:.1s;}
.eqb:nth-child(3){height:7px;animation-delay:.2s;} .eqb:nth-child(4){height:13px;animation-delay:.05s;}
.delbtn{background:none;border:none;font-size:13px;cursor:pointer;color:var(--muted);
  padding:3px 5px;border-radius:8px;flex-shrink:0;}
.delbtn:hover{background:rgba(244,143,177,.2);color:var(--rose);}
.empty-pl{text-align:center;padding:34px;color:var(--muted);}
.empty-pl span{font-size:36px;display:block;margin-bottom:8px;}
.empty-pl p{font-family:'Caveat',cursive;font-size:1.1rem;}
footer{text-align:center;padding:16px;font-family:'Caveat',cursive;
  font-size:.9rem;color:var(--muted);border-top:2px dashed var(--border);}
</style>
</head>
<body>

<span class="fn" style="left:5%;animation-duration:8s">🎵</span>
<span class="fn" style="left:22%;animation-duration:11s;animation-delay:3s">🎶</span>
<span class="fn" style="left:78%;animation-duration:9s;animation-delay:1.5s">🎵</span>
<span class="fn" style="left:91%;animation-duration:13s;animation-delay:5s">🎶</span>

<header>
  <div class="tag">✨ your personal radio</div>
  <h1>Tape <em>&</em> Tune 🎀</h1>
  <p>import your songs and let the music play~</p>
</header>

<!-- ══════════════════════════════════════════
     UPLOAD — plain form, exactly like working code
══════════════════════════════════════════ -->
<div class="upload-section">
  <div class="upload-box">
    <p>🎵 Choose a song to import</p>
    <form method="POST" enctype="multipart/form-data">
      <input type="file" name="song" accept="audio/*" required>
      <button type="submit" name="upload" class="import-btn">
        🎀 Import Music
      </button>
    </form>
  </div>
</div>

<!-- BOOMBOX PLAYER -->
<div class="player-wrap">
  <div class="boombox">
    <div class="antenna"></div>
    <div class="bb-top">
      <div class="led r"></div>
      <div class="led g" id="ledG"></div>
      <div class="rail"></div>
      <div class="vu" id="vu">
        <div class="vub"></div><div class="vub"></div><div class="vub"></div>
        <div class="vub"></div><div class="vub"></div><div class="vub"></div>
      </div>
      <div class="rail"></div>
    </div>
    <div class="bb-mid">
      <div class="spk" id="spkL"><div class="spk-in"></div></div>
      <div class="slot">
        <div class="reels">
          <div class="reel" id="rl"></div>
          <div style="font-size:9px;color:#705050;font-weight:800">▶</div>
          <div class="reel" id="rr"></div>
        </div>
        <div class="slot-title" id="slotTitle">No song loaded</div>
      </div>
      <div class="spk" id="spkR"><div class="spk-in"></div></div>
    </div>
    <div class="prog-wrap">
      <div class="prog-bar" id="progBar">
        <div class="prog-fill" id="progFill"></div>
      </div>
      <div class="times"><span id="tCur">0:00</span><span id="tDur">0:00</span></div>
    </div>
    <div class="ctrls">
      <button class="cbtn" onclick="prev()">⏮</button>
      <button class="cbtn" onclick="skip(-10)" style="font-size:11px;font-weight:800">-10s</button>
      <button class="cbtn big" id="playBtn" onclick="togglePlay()">▶</button>
      <button class="cbtn" onclick="skip(10)" style="font-size:11px;font-weight:800">+10s</button>
      <button class="cbtn" onclick="next()">⏭</button>
    </div>
    <div class="extras">
      <span>🔈</span>
      <input type="range" min="0" max="1" step="0.02" value="0.8" id="vol"
             oninput="audio.volume=parseFloat(this.value)">
      <span>🔊</span>
      <button class="shuf" id="shufBtn" onclick="toggleShuffle()">🔀 Shuffle</button>
    </div>
  </div>
</div>

<!-- RECENTLY PLAYED SHELF -->
<div class="shelf-wrap">
  <div class="shelf-label">📼 Recently Played</div>
  <div class="shelf">
    <div class="shelf-row" id="shelfRow">
      <?php if(empty($recent)): ?>
      <div class="shelf-empty" id="shelfEmpty">play a song to fill the shelf~ 🎶</div>
      <?php else:
        $cc=[['#e8544a','#c0392b','#f5deb3'],['#7b68ee','#5a4db5','#e8d5f5'],
             ['#2ecc71','#27ae60','#d5f5e3'],['#e67e22','#d35400','#fdebd0'],
             ['#e91e8c','#c2185b','#fce4ec'],['#00bcd4','#0097a7','#e0f7fa']];
        foreach($recent as $ri=>$rs): $c=$cc[$ri%6];
      ?>
      <div class="mcass" data-id="<?=(int)$rs['id']?>"
           onclick="playById(<?=(int)$rs['id']?>)"
           title="<?=htmlspecialchars($rs['title'])?>">
        <svg width="68" height="44" viewBox="0 0 68 44" xmlns="http://www.w3.org/2000/svg">
          <rect x="2" y="3" width="64" height="38" rx="5" fill="<?=$c[0]?>" stroke="<?=$c[1]?>" stroke-width="1.5"/>
          <rect x="7" y="6" width="54" height="22" rx="4" fill="<?=$c[2]?>" stroke="<?=$c[1]?>" stroke-width="1" opacity=".9"/>
          <text x="34" y="17" text-anchor="middle" font-family="Caveat,cursive" font-size="7" font-weight="700" fill="<?=$c[1]?>">TAPE</text>
          <circle cx="22" cy="30" r="6" fill="rgba(0,0,0,.25)"/><circle cx="22" cy="30" r="3" fill="rgba(255,255,255,.2)"/>
          <circle cx="46" cy="30" r="6" fill="rgba(0,0,0,.25)"/><circle cx="46" cy="30" r="3" fill="rgba(255,255,255,.2)"/>
          <rect x="28" y="26" width="12" height="8" rx="2" fill="rgba(0,0,0,.3)"/>
        </svg>
        <div class="mcass-title"><?=htmlspecialchars(mb_strimwidth($rs['title'],0,10,'…'))?></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
    <div class="shelf-plank"></div>
  </div>
</div>

<!-- PLAYLIST -->
<div class="pl-wrap">
  <div class="pl-label">🎶 All Songs <span style="font-size:13px;color:var(--muted);font-family:'Nunito'">(<?=count($songs)?>)</span></div>
  <?php if(empty($songs)): ?>
  <div class="empty-pl"><span>🎀</span><p>No songs yet! Import one above~</p></div>
  <?php else: foreach($songs as $i=>$s): ?>
  <div class="srow" id="row-<?=(int)$s['id']?>" onclick="playSong(<?=$i?>)">
    <div class="snum"><?=$i+1?></div>
    <div class="sinfo">
      <div class="stitle"><?=htmlspecialchars($s['title'])?></div>
      <div class="sdur" id="dur-<?=(int)$s['id']?>">🎵</div>
    </div>
    <div class="eq"><div class="eqb"></div><div class="eqb"></div><div class="eqb"></div><div class="eqb"></div></div>
    <form method="POST" style="display:inline" onclick="event.stopPropagation()">
      <input type="hidden" name="delete" value="<?=(int)$s['id']?>">
      <button class="delbtn" type="submit" onclick="return confirm('Remove this song?')">🗑</button>
    </form>
  </div>
  <?php endforeach; endif; ?>
</div>

<footer>🎀 made with love & lofi energy · Tape & Tune 2025</footer>

<audio id="audio" preload="auto"></audio>

<script>
// URL = "uploads/filename" — exactly like the working simple code
const SONGS = <?=json_encode(array_map(function($s){
    return [
        'id'    => (int)$s['id'],
        'title' => $s['title'],
        'url'   => 'uploads/'.$s['filename']
    ];
},$songs))?>;

const audio    = document.getElementById('audio');
const playBtn  = document.getElementById('playBtn');
const progFill = document.getElementById('progFill');
const progBar  = document.getElementById('progBar');
const slotTitle= document.getElementById('slotTitle');
const tCur     = document.getElementById('tCur');
const tDur     = document.getElementById('tDur');
const rl       = document.getElementById('rl');
const rr       = document.getElementById('rr');
const spkL     = document.getElementById('spkL');
const spkR     = document.getElementById('spkR');
const ledG     = document.getElementById('ledG');

let cur=-1, shuffle=false, vuTimer=null;
audio.volume=0.8;

function fmt(s){s=Math.floor(s||0);return Math.floor(s/60)+':'+(s%60<10?'0':'')+s%60;}

function animVU(on){
  clearInterval(vuTimer);
  const b=document.querySelectorAll('.vub');
  if(on) vuTimer=setInterval(()=>b.forEach(x=>x.style.height=Math.floor(Math.random()*13+3)+'px'),160);
  else   b.forEach(x=>x.style.height='4px');
}

function setUI(on){
  playBtn.textContent=on?'⏸':'▶';
  rl.classList.toggle('spin',on);
  rr.classList.toggle('spin',on);
  spkL.classList.toggle('on',on);
  spkR.classList.toggle('on',on);
  ledG.classList.toggle('lit',on);
  animVU(on);
}

function setActive(idx){
  document.querySelectorAll('.srow').forEach(r=>r.classList.remove('active'));
  if(idx>=0&&SONGS[idx]){
    const r=document.getElementById('row-'+SONGS[idx].id);
    if(r){r.classList.add('active');r.scrollIntoView({block:'nearest',behavior:'smooth'});}
  }
}

// PLAY SONG — same logic as working simple code
function playSong(index){
  if(!SONGS.length) return;
  index=((index%SONGS.length)+SONGS.length)%SONGS.length;
  cur=index;
  const s=SONGS[index];

  audio.src=s.url;          // "uploads/filename.mp3"
  slotTitle.textContent=s.title;
  setActive(index);
  progFill.style.width='0%';
  tCur.textContent='0:00';
  tDur.textContent='0:00';

  audio.play();             // same as working simple code — no .then(), no .catch()
  setUI(true);
  pingPlayed(s.id);
  addToShelf(s);
}

function pingPlayed(id){
  const fd=new FormData();fd.append('ping',id);
  fetch(window.location.href,{method:'POST',body:fd}).catch(()=>{});
}

const COLORS=[
  ['#e8544a','#c0392b','#f5deb3'],['#7b68ee','#5a4db5','#e8d5f5'],
  ['#2ecc71','#27ae60','#d5f5e3'],['#e67e22','#d35400','#fdebd0'],
  ['#e91e8c','#c2185b','#fce4ec'],['#00bcd4','#0097a7','#e0f7fa']
];
let shelfN=<?=count($recent)?>;

function addToShelf(song){
  const row=document.getElementById('shelfRow');
  const emp=document.getElementById('shelfEmpty');
  if(emp) emp.remove();
  if(document.querySelector('.mcass[data-id="'+song.id+'"]')) return;
  const c=COLORS[shelfN++%6];
  const short=song.title.length>10?song.title.substring(0,10)+'…':song.title;
  const div=document.createElement('div');
  div.className='mcass';div.setAttribute('data-id',song.id);div.title=song.title;
  div.onclick=()=>playById(song.id);
  div.innerHTML=`<svg width="68" height="44" viewBox="0 0 68 44" xmlns="http://www.w3.org/2000/svg">
    <rect x="2" y="3" width="64" height="38" rx="5" fill="${c[0]}" stroke="${c[1]}" stroke-width="1.5"/>
    <rect x="7" y="6" width="54" height="22" rx="4" fill="${c[2]}" stroke="${c[1]}" stroke-width="1" opacity=".9"/>
    <text x="34" y="17" text-anchor="middle" font-family="Caveat,cursive" font-size="7" font-weight="700" fill="${c[1]}">TAPE</text>
    <circle cx="22" cy="30" r="6" fill="rgba(0,0,0,.25)"/><circle cx="22" cy="30" r="3" fill="rgba(255,255,255,.2)"/>
    <circle cx="46" cy="30" r="6" fill="rgba(0,0,0,.25)"/><circle cx="46" cy="30" r="3" fill="rgba(255,255,255,.2)"/>
    <rect x="28" y="26" width="12" height="8" rx="2" fill="rgba(0,0,0,.3)"/>
  </svg><div class="mcass-title">${short}</div>`;
  row.insertBefore(div,row.firstChild);
}

function playById(id){const i=SONGS.findIndex(s=>s.id===id);if(i>=0)playSong(i);}

function togglePlay(){
  if(!SONGS.length) return;
  if(cur<0){playSong(0);return;}
  if(audio.paused){audio.play();setUI(true);}
  else{audio.pause();setUI(false);}
}

function next(){
  if(!SONGS.length) return;
  if(shuffle){let n;do{n=Math.floor(Math.random()*SONGS.length);}while(n===cur&&SONGS.length>1);playSong(n);}
  else playSong(cur<0?0:(cur+1)%SONGS.length);
}
function prev(){playSong(cur<=0?SONGS.length-1:cur-1);}
function skip(s){if(audio.duration)audio.currentTime=Math.max(0,Math.min(audio.currentTime+s,audio.duration));}
function toggleShuffle(){shuffle=!shuffle;document.getElementById('shufBtn').classList.toggle('on',shuffle);}

progBar.addEventListener('click',function(e){
  if(!audio.duration)return;
  const r=this.getBoundingClientRect();
  audio.currentTime=((e.clientX-r.left)/r.width)*audio.duration;
});

audio.addEventListener('timeupdate',()=>{
  if(!audio.duration)return;
  progFill.style.width=(audio.currentTime/audio.duration*100)+'%';
  tCur.textContent=fmt(audio.currentTime);
});
audio.addEventListener('loadedmetadata',()=>{tDur.textContent=fmt(audio.duration);});
audio.addEventListener('ended',next);

// Load durations
SONGS.forEach(s=>{
  const el=document.getElementById('dur-'+s.id);
  if(!el)return;
  const t=new Audio();t.preload='metadata';t.src=s.url;
  t.onloadedmetadata=()=>{el.textContent=fmt(t.duration);t.src='';};
});
</script>
</body>
</html>