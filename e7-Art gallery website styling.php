<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect("localhost","root","","artgallery_db");

if(isset($_POST["add_art"]) && $conn) {
    $t=mysqli_real_escape_string($conn,$_POST["title"]);
    $a=mysqli_real_escape_string($conn,$_POST["artist"]);
    $y=(int)$_POST["year"];
    $s=mysqli_real_escape_string($conn,$_POST["style"]);
    $p=(float)$_POST["price"];
    $img=mysqli_real_escape_string($conn,$_POST["img_url"]);
    $desc=mysqli_real_escape_string($conn,$_POST["description"]);
    mysqli_query($conn,"INSERT INTO artworks(title,artist,year,style,price,img_url,description) VALUES('$t','$a',$y,'$s',$p,'$img','$desc')");
    header("Location:".$_SERVER['PHP_SELF']."#gallery"); exit();
}
if(isset($_POST["delete_art"])) {
    mysqli_query($conn,"DELETE FROM artworks WHERE id=".(int)$_POST["delete_art"]);
    header("Location:".$_SERVER['PHP_SELF']); exit();
}

// Single artwork view
$view_id = isset($_GET["view"]) ? (int)$_GET["view"] : 0;
$view_art = $view_id && $conn ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM artworks WHERE id=$view_id")) : null;

$filter  = isset($_GET["style"]) ? mysqli_real_escape_string($conn,$_GET["style"]) : "";
$search  = isset($_GET["q"])     ? mysqli_real_escape_string($conn,$_GET["q"])     : "";
$sort    = in_array($_GET["sort"]??"",["price_asc","price_desc","year_desc","title_asc"]) ? $_GET["sort"] : "id_desc";
$sortSQL = ["price_asc"=>"price ASC","price_desc"=>"price DESC","year_desc"=>"year DESC","title_asc"=>"title ASC","id_desc"=>"id DESC"][$sort];

$where="WHERE 1";
if($filter) $where.=" AND style='$filter'";
if($search) $where.=" AND (title LIKE '%$search%' OR artist LIKE '%$search%')";
$artworks = $conn ? mysqli_fetch_all(mysqli_query($conn,"SELECT * FROM artworks $where ORDER BY $sortSQL"),MYSQLI_ASSOC) : [];
$styles   = $conn ? mysqli_fetch_all(mysqli_query($conn,"SELECT DISTINCT style FROM artworks ORDER BY style"),MYSQLI_ASSOC) : [];
$total    = count($artworks);

// Prev / Next for artwork viewer
$all_ids  = $conn ? mysqli_fetch_all(mysqli_query($conn,"SELECT id FROM artworks ORDER BY id DESC"),MYSQLI_ASSOC) : [];
$all_ids  = array_column($all_ids,'id');
$cur_pos  = $view_art ? array_search($view_id,$all_ids) : false;
$prev_id  = ($cur_pos !== false && $cur_pos > 0) ? $all_ids[$cur_pos-1] : null;
$next_id  = ($cur_pos !== false && $cur_pos < count($all_ids)-1) ? $all_ids[$cur_pos+1] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $view_art ? htmlspecialchars($view_art['title']).' — Atelier' : 'Atelier — Fine Art Gallery' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,500&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --cream:#f5f0e8;--warm:#ede4d0;--canvas:#faf7f2;
  --ink:#1a1410;--charcoal:#2d2520;--umber:#5c4a35;
  --gold:#c9a84c;--gold2:#e8c97a;--rust:#b5451b;
  --border:rgba(92,74,53,0.18);
}
body{font-family:'Raleway',sans-serif;background:var(--canvas);color:var(--ink);min-height:100vh;
  background-image:url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.025'/%3E%3C/svg%3E");}

/* ANNOUNCE */
.announce{background:var(--ink);color:var(--gold2);text-align:center;padding:11px;
  font-size:10px;letter-spacing:3.5px;text-transform:uppercase;}
.announce span{color:var(--gold);}

/* NAV */
nav{background:rgba(250,247,242,0.95);border-bottom:1px solid var(--border);padding:0 48px;
  display:flex;align-items:center;justify-content:space-between;height:72px;
  position:sticky;top:0;z-index:200;backdrop-filter:blur(16px);}
.logo{font-family:'Cormorant Garamond',serif;font-size:1.9rem;font-weight:300;
  letter-spacing:7px;text-transform:uppercase;color:var(--ink);text-decoration:none;}
.logo span{color:var(--gold);}
.nav-right{display:flex;align-items:center;gap:32px;}
.nav-right a{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--umber);
  text-decoration:none;transition:color .2s;font-weight:500;}
.nav-right a:hover{color:var(--rust);}
.search-bar{display:flex;border:1px solid var(--border);overflow:hidden;}
.search-bar input{padding:9px 16px;background:transparent;border:none;font-family:'Raleway',sans-serif;
  font-size:12px;color:var(--ink);outline:none;width:200px;}
.search-bar button{padding:9px 18px;background:var(--ink);color:var(--cream);border:none;
  font-size:11px;letter-spacing:1px;cursor:pointer;transition:background .2s;}
.search-bar button:hover{background:var(--umber);}

/* ═══════════════════════════════════════════
   HERO — Starry Night
═══════════════════════════════════════════ */
.hero{position:relative;height:90vh;overflow:hidden;display:flex;align-items:flex-end;}
.hero-bg{position:absolute;inset:0;
  background:linear-gradient(160deg,#060d1f 0%,#0d1b3e 20%,#0f3460 45%,#1a4a6e 65%,#080808 100%);}
.hero-bg::before{content:'';position:absolute;inset:0;
  background:
    radial-gradient(ellipse 55% 35% at 25% 65%,rgba(255,210,80,0.18),transparent),
    radial-gradient(ellipse 35% 55% at 72% 28%,rgba(80,140,255,0.22),transparent),
    radial-gradient(ellipse 25% 25% at 18% 22%,rgba(255,255,180,0.28),transparent),
    radial-gradient(ellipse 40% 20% at 60% 75%,rgba(30,80,160,0.3),transparent);}
.stars{position:absolute;inset:0;overflow:hidden;}
.stars::before,.stars::after{content:'';position:absolute;inset:0;}
.stars::before{background:
  radial-gradient(circle 3px at 8% 12%,rgba(255,255,210,0.95),transparent),
  radial-gradient(circle 2px at 18% 8%,rgba(255,255,220,0.8),transparent),
  radial-gradient(circle 4px at 30% 6%,rgba(255,235,100,0.9),transparent),
  radial-gradient(circle 2px at 42% 10%,rgba(210,225,255,0.85),transparent),
  radial-gradient(circle 3px at 55% 5%,rgba(255,255,200,0.9),transparent),
  radial-gradient(circle 2px at 68% 9%,rgba(255,255,210,0.75),transparent),
  radial-gradient(circle 5px at 78% 7%,rgba(255,228,80,0.95),transparent),
  radial-gradient(circle 2px at 88% 14%,rgba(200,215,255,0.8),transparent),
  radial-gradient(circle 3px at 12% 30%,rgba(255,255,200,0.7),transparent),
  radial-gradient(circle 2px at 35% 28%,rgba(210,225,255,0.75),transparent),
  radial-gradient(circle 4px at 50% 22%,rgba(255,240,120,0.85),transparent),
  radial-gradient(circle 2px at 72% 25%,rgba(255,255,210,0.7),transparent),
  radial-gradient(circle 3px at 92% 20%,rgba(200,215,255,0.8),transparent);}
.stars::after{background:
  radial-gradient(circle 2px at 5% 45%,rgba(255,255,200,0.6),transparent),
  radial-gradient(circle 1px at 22% 42%,rgba(255,255,220,0.5),transparent),
  radial-gradient(circle 2px at 45% 38%,rgba(210,225,255,0.6),transparent),
  radial-gradient(circle 3px at 62% 42%,rgba(255,230,90,0.7),transparent),
  radial-gradient(circle 1px at 80% 38%,rgba(255,255,200,0.5),transparent),
  radial-gradient(circle 2px at 95% 44%,rgba(200,215,255,0.65),transparent);
  animation:twinkle 4s ease-in-out infinite alternate;}
@keyframes twinkle{0%{opacity:.6}100%{opacity:1}}
.swirl{position:absolute;border-radius:50%;filter:blur(40px);animation:drift 12s ease-in-out infinite;}
.swirl-1{width:400px;height:200px;background:rgba(30,70,160,0.25);top:15%;left:20%;animation-delay:0s;}
.swirl-2{width:300px;height:150px;background:rgba(255,200,60,0.12);top:25%;left:50%;animation-delay:-4s;}
.swirl-3{width:350px;height:180px;background:rgba(20,60,140,0.2);top:10%;right:10%;animation-delay:-8s;}
@keyframes drift{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(20px,-15px) scale(1.05)}}
.hero-content{position:relative;z-index:2;padding:64px;max-width:680px;}
.eyebrow{font-size:10px;letter-spacing:5px;text-transform:uppercase;color:var(--gold2);
  margin-bottom:22px;display:flex;align-items:center;gap:14px;}
.eyebrow::before{content:'';width:48px;height:1px;background:linear-gradient(90deg,transparent,var(--gold));}
.hero h2{font-family:'Cormorant Garamond',serif;font-size:clamp(3rem,7.5vw,6rem);
  font-weight:300;color:#fff;line-height:.98;margin-bottom:18px;letter-spacing:-1px;}
.hero h2 em{font-style:italic;color:var(--gold2);display:block;}
.hero-sub{color:rgba(255,255,255,0.55);font-size:14px;font-weight:300;line-height:1.8;
  margin-bottom:36px;max-width:420px;}
.hero-btns{display:flex;gap:14px;}
.btn-gold{padding:15px 40px;background:var(--gold);color:var(--ink);border:none;
  font-family:'Raleway',sans-serif;font-size:11px;font-weight:700;letter-spacing:3px;
  text-transform:uppercase;cursor:pointer;text-decoration:none;display:inline-block;
  transition:all .3s;clip-path:polygon(0 0,calc(100% - 10px) 0,100% 10px,100% 100%,10px 100%,0 calc(100% - 10px));}
.btn-gold:hover{background:var(--gold2);transform:translateY(-2px);box-shadow:0 10px 32px rgba(201,168,76,0.4);}
.btn-ghost{padding:15px 40px;background:transparent;color:rgba(255,255,255,0.8);
  border:1px solid rgba(255,255,255,0.25);font-family:'Raleway',sans-serif;font-size:11px;
  font-weight:500;letter-spacing:3px;text-transform:uppercase;text-decoration:none;display:inline-block;
  transition:all .3s;}
.btn-ghost:hover{border-color:var(--gold);color:var(--gold);}
.hero-seal{position:absolute;right:64px;top:50%;transform:translateY(-50%);
  width:120px;height:120px;border-radius:50%;border:1px solid rgba(201,168,76,0.5);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  color:rgba(232,201,122,0.8);font-size:9px;letter-spacing:2.5px;text-align:center;
  text-transform:uppercase;line-height:1.8;animation:rotateSeal 25s linear infinite;}
@keyframes rotateSeal{to{transform:translateY(-50%) rotate(360deg)}}

/* FILTER */
.filter-bar{background:var(--warm);border-top:1px solid var(--border);border-bottom:1px solid var(--border);
  padding:18px 48px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;justify-content:space-between;}
.filter-left{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.flabel{font-size:10px;letter-spacing:2.5px;text-transform:uppercase;color:var(--umber);font-weight:700;}
.pill-link{padding:6px 20px;font-size:10px;letter-spacing:2px;text-transform:uppercase;
  color:var(--umber);border:1px solid var(--border);background:transparent;
  text-decoration:none;transition:all .25s;font-weight:600;}
.pill-link:hover,.pill-link.active{background:var(--ink);color:var(--cream);border-color:var(--ink);}
.filter-bar select{padding:8px 16px;background:transparent;border:1px solid var(--border);
  font-family:'Raleway',sans-serif;font-size:11px;color:var(--ink);cursor:pointer;outline:none;letter-spacing:1px;}

/* GALLERY */
.gallery-wrap{padding:64px 48px;}
.g-header{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:44px;}
.g-title{font-family:'Cormorant Garamond',serif;font-size:2.4rem;font-weight:300;letter-spacing:3px;}
.g-count{font-size:10px;color:var(--umber);letter-spacing:3px;text-transform:uppercase;}
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:3px;}

/* ART CARD */
.art-card{position:relative;overflow:hidden;background:var(--ink);cursor:pointer;}
.art-card img{width:100%;aspect-ratio:4/3;object-fit:cover;display:block;
  transition:transform .7s cubic-bezier(.25,.46,.45,.94),filter .4s;}
.art-card:hover img{transform:scale(1.06);filter:saturate(1.15) brightness(0.85);}
.art-overlay{position:absolute;inset:0;
  background:linear-gradient(to top,rgba(15,10,8,0.96) 0%,rgba(15,10,8,0.4) 40%,transparent 65%);
  opacity:0;transition:opacity .4s;display:flex;flex-direction:column;
  justify-content:flex-end;padding:28px;}
.art-card:hover .art-overlay{opacity:1;}
.art-tag{font-size:9px;letter-spacing:3px;text-transform:uppercase;color:var(--gold);margin-bottom:7px;}
.art-name{font-family:'Cormorant Garamond',serif;font-size:1.5rem;font-weight:400;color:#fff;margin-bottom:3px;}
.art-by{font-size:12px;color:rgba(255,255,255,0.55);margin-bottom:16px;font-style:italic;}
.art-row{display:flex;justify-content:space-between;align-items:center;gap:8px;}
.art-price{font-family:'Cormorant Garamond',serif;font-size:1.2rem;color:var(--gold2);}
.art-actions{display:flex;gap:8px;align-items:center;}
.btn-view{padding:8px 18px;background:var(--gold);color:var(--ink);border:none;
  font-family:'Raleway',sans-serif;font-size:10px;font-weight:700;letter-spacing:2px;
  text-transform:uppercase;cursor:pointer;text-decoration:none;display:inline-block;transition:all .2s;}
.btn-view:hover{background:var(--gold2);}
.btn-rm{background:rgba(181,69,27,0.7);border:none;color:#fff;padding:8px 12px;
  font-size:10px;cursor:pointer;letter-spacing:1px;transition:background .2s;}
.btn-rm:hover{background:var(--rust);}

/* ═══════════════════════════════════════════
   ARTWORK DETAIL PAGE
═══════════════════════════════════════════ */
.detail-wrap{min-height:100vh;background:var(--canvas);}
.detail-back{display:inline-flex;align-items:center;gap:10px;padding:20px 48px;
  font-size:11px;letter-spacing:2.5px;text-transform:uppercase;color:var(--umber);
  text-decoration:none;font-weight:600;transition:color .2s;border-bottom:1px solid var(--border);
  width:100%;background:var(--warm);}
.detail-back:hover{color:var(--rust);}
.detail-back::before{content:'←';font-size:16px;}
.detail-main{display:grid;grid-template-columns:1fr 1fr;min-height:calc(100vh - 113px);}
.detail-image-wrap{position:relative;background:var(--charcoal);overflow:hidden;}
.detail-image-wrap img{width:100%;height:100%;object-fit:contain;display:block;
  background:#1a1410;padding:40px;transition:transform .6s;}
.detail-image-wrap img:hover{transform:scale(1.02);}
.img-frame{position:absolute;inset:20px;border:1px solid rgba(201,168,76,0.2);pointer-events:none;}
.detail-info{padding:64px 56px;display:flex;flex-direction:column;justify-content:center;
  border-left:1px solid var(--border);background:var(--canvas);}
.detail-style{font-size:10px;letter-spacing:4px;text-transform:uppercase;color:var(--gold);
  margin-bottom:20px;display:flex;align-items:center;gap:12px;}
.detail-style::before{content:'';width:32px;height:1px;background:var(--gold);}
.detail-title{font-family:'Cormorant Garamond',serif;font-size:clamp(2.2rem,4vw,3.5rem);
  font-weight:400;line-height:1.05;margin-bottom:10px;letter-spacing:-0.5px;}
.detail-artist{font-family:'Cormorant Garamond',serif;font-size:1.3rem;font-style:italic;
  color:var(--umber);margin-bottom:36px;}
.detail-divider{width:60px;height:1px;background:linear-gradient(90deg,var(--gold),transparent);margin-bottom:36px;}
.detail-desc{font-size:15px;line-height:1.85;color:var(--umber);margin-bottom:40px;font-weight:400;}
.detail-meta{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:40px;
  padding:28px;background:var(--warm);border:1px solid var(--border);}
.meta-item{display:flex;flex-direction:column;gap:6px;}
.meta-label{font-size:9px;letter-spacing:3px;text-transform:uppercase;color:var(--umber);font-weight:700;}
.meta-val{font-family:'Cormorant Garamond',serif;font-size:1.1rem;color:var(--ink);}
.detail-price{font-family:'Cormorant Garamond',serif;font-size:2.4rem;font-weight:400;
  color:var(--ink);margin-bottom:28px;}
.detail-price span{font-size:1rem;color:var(--umber);font-family:'Raleway',sans-serif;
  font-weight:400;letter-spacing:1px;vertical-align:middle;}
.detail-nav{display:flex;gap:12px;margin-top:8px;}
.detail-nav a{padding:12px 28px;font-size:11px;font-weight:600;letter-spacing:2px;
  text-transform:uppercase;text-decoration:none;border:1px solid var(--border);
  color:var(--umber);transition:all .25s;}
.detail-nav a:hover{background:var(--ink);color:var(--cream);border-color:var(--ink);}
.detail-nav a.gold-btn{background:var(--gold);color:var(--ink);border-color:var(--gold);}
.detail-nav a.gold-btn:hover{background:var(--gold2);}
@media(max-width:768px){
  .detail-main{grid-template-columns:1fr;}.detail-info{padding:40px 28px;}
  .hero-seal{display:none;}.detail-image-wrap{min-height:50vh;}
}

/* ADD SECTION */
.add-section{background:var(--ink);color:var(--cream);padding:72px 48px;}
.add-title{font-family:'Cormorant Garamond',serif;font-size:2.4rem;font-weight:300;
  letter-spacing:4px;text-transform:uppercase;margin-bottom:8px;color:var(--gold2);}
.add-sub{color:rgba(255,255,255,0.35);font-size:13px;margin-bottom:40px;letter-spacing:.5px;}
.add-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;}
.field label{font-size:9px;letter-spacing:2.5px;text-transform:uppercase;
  color:rgba(255,255,255,0.35);display:block;margin-bottom:8px;font-weight:700;}
.field input,.field select,.field textarea{width:100%;background:rgba(255,255,255,0.05);
  border:1px solid rgba(255,255,255,0.1);color:var(--cream);font-family:'Raleway',sans-serif;
  font-size:13px;padding:13px 16px;outline:none;transition:border-color .25s,box-shadow .25s;}
.field input:focus,.field select:focus,.field textarea:focus{
  border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,168,76,0.1);}
.field textarea{resize:vertical;min-height:90px;}
.field select option{background:#2d2520;}
.field.full{grid-column:1/-1;}
.btn-submit{margin-top:28px;padding:15px 52px;background:var(--gold);color:var(--ink);border:none;
  font-family:'Raleway',sans-serif;font-size:11px;font-weight:700;letter-spacing:3px;
  text-transform:uppercase;cursor:pointer;transition:all .3s;
  clip-path:polygon(0 0,calc(100% - 10px) 0,100% 10px,100% 100%,10px 100%,0 calc(100% - 10px));}
.btn-submit:hover{background:var(--gold2);transform:translateY(-2px);}

.empty{text-align:center;padding:96px 20px;color:var(--umber);}
.empty-icon{font-size:52px;display:block;margin-bottom:18px;opacity:.4;}
footer{background:var(--ink);color:rgba(255,255,255,0.25);text-align:center;
  padding:36px;font-size:10px;letter-spacing:3px;text-transform:uppercase;}
footer em{color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:14px;font-style:italic;}
</style>
</head>
<body>

<?php if($view_art): ?>
<!-- ══════════════════════════════════════════════
     ARTWORK DETAIL VIEW
══════════════════════════════════════════════ -->
<div class="announce">Atelier Fine Art Gallery &nbsp;—&nbsp; <span>Original Works</span> &nbsp;—&nbsp; Est. 1892</div>
<nav>
  <a href="?" class="logo">AT<span>E</span>LIER</a>
  <div class="nav-right">
    <a href="?#gallery">Gallery</a>
    <a href="?#add">Submit Work</a>
    <form method="GET" class="search-bar">
      <input type="text" name="q" placeholder="Search collection..." value="<?=htmlspecialchars($search)?>">
      <button type="submit">Search</button>
    </form>
  </div>
</nav>

<div class="detail-wrap">
  <a href="?" class="detail-back">Back to Collection</a>
  <div class="detail-main">

    <!-- IMAGE PANEL -->
    <div class="detail-image-wrap">
      <img src="<?=htmlspecialchars($view_art['img_url'])?>"
           alt="<?=htmlspecialchars($view_art['title'])?>"
           onerror="this.src='https://images.unsplash.com/photo-1578301978693-85fa9c0320b9?w=900&q=80'">
      <div class="img-frame"></div>
    </div>

    <!-- INFO PANEL -->
    <div class="detail-info">
      <div class="detail-style"><?=htmlspecialchars($view_art['style'])?></div>
      <h1 class="detail-title"><?=htmlspecialchars($view_art['title'])?></h1>
      <div class="detail-artist">— <?=htmlspecialchars($view_art['artist'])?></div>
      <div class="detail-divider"></div>
      <?php if($view_art['description']): ?>
      <p class="detail-desc"><?=nl2br(htmlspecialchars($view_art['description']))?></p>
      <?php endif; ?>
      <div class="detail-meta">
        <div class="meta-item"><span class="meta-label">Year Created</span><span class="meta-val"><?=$view_art['year']?></span></div>
        <div class="meta-item"><span class="meta-label">Art Style</span><span class="meta-val"><?=htmlspecialchars($view_art['style'])?></span></div>
        <div class="meta-item"><span class="meta-label">Artist</span><span class="meta-val"><?=htmlspecialchars($view_art['artist'])?></span></div>
        <div class="meta-item"><span class="meta-label">Catalogue No.</span><span class="meta-val">#<?=str_pad($view_art['id'],4,'0',STR_PAD_LEFT)?></span></div>
      </div>
      <div class="detail-price">
        ₹<?=number_format($view_art['price'])?> <span>/ Original Work</span>
      </div>
      <div class="detail-nav">
        <?php if($prev_id): ?><a href="?view=<?=$prev_id?>">← Previous</a><?php endif; ?>
        <?php if($next_id): ?><a href="?view=<?=$next_id?>">Next →</a><?php endif; ?>
        <form method="POST" style="display:inline">
          <input type="hidden" name="delete_art" value="<?=(int)$view_art['id']?>">
          <button class="btn-rm" onclick="return confirm('Remove this artwork from the collection?')" style="padding:12px 20px;font-size:10px;letter-spacing:1.5px">✕ Remove</button>
        </form>
      </div>
    </div>

  </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════
     MAIN GALLERY PAGE
══════════════════════════════════════════════ -->
<div class="announce">Summer Exhibition 2025 — <span>New Works by Emerging Artists</span> — Now Open</div>

<nav>
  <a href="?" class="logo">AT<span>E</span>LIER</a>
  <div class="nav-right">
    <a href="#gallery">Gallery</a>
    <a href="#add">Submit Work</a>
    <form method="GET" class="search-bar">
      <input type="text" name="q" placeholder="Search artist or title..." value="<?=htmlspecialchars($search)?>">
      <button type="submit">Search</button>
    </form>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="stars"></div>
  <div class="swirl swirl-1"></div>
  <div class="swirl swirl-2"></div>
  <div class="swirl swirl-3"></div>
  <div class="hero-content">
    <div class="eyebrow">Featured Collection · 2025</div>
    <h2>Where <em>Starry Nights</em> Meet Living Walls</h2>
    <p class="hero-sub">From Van Gogh's swirling cosmos to Monet's luminous gardens — discover original works that transform every space into a masterpiece.</p>
    <div class="hero-btns">
      <a href="#gallery" class="btn-gold">Explore Gallery</a>
      <a href="#add" class="btn-ghost">Submit Artwork</a>
    </div>
  </div>
  <div class="hero-seal">✦<br>Est.<br>1892<br>Fine Art</div>
</section>

<!-- FILTER BAR -->
<div class="filter-bar" id="gallery">
  <div class="filter-left">
    <span class="flabel">Style :</span>
    <a href="?" class="pill-link <?=!$filter?'active':''?>">All</a>
    <?php foreach($styles as $s): ?>
    <a href="?style=<?=urlencode($s['style'])?>" class="pill-link <?=$filter===$s['style']?'active':''?>"><?=htmlspecialchars($s['style'])?></a>
    <?php endforeach; ?>
  </div>
  <form method="GET">
    <?php if($filter): ?><input type="hidden" name="style" value="<?=htmlspecialchars($filter)?>"><?php endif; ?>
    <select name="sort" onchange="this.form.submit()">
      <option value="id_desc"   <?=$sort==='id_desc'?   'selected':''?>>Recently Added</option>
      <option value="price_asc" <?=$sort==='price_asc'? 'selected':''?>>Price: Low to High</option>
      <option value="price_desc"<?=$sort==='price_desc'?'selected':''?>>Price: High to Low</option>
      <option value="year_desc" <?=$sort==='year_desc'? 'selected':''?>>Newest Works</option>
      <option value="title_asc" <?=$sort==='title_asc'? 'selected':''?>>Alphabetical</option>
    </select>
  </form>
</div>

<!-- GALLERY GRID -->
<section class="gallery-wrap">
  <div class="g-header">
    <h2 class="g-title">The Collection</h2>
    <span class="g-count"><?=$total?> Work<?=$total!=1?'s':''?> Available</span>
  </div>

  <?php if(empty($artworks)): ?>
  <div class="empty"><span class="empty-icon">🖼️</span>No artworks found. Be the first to add a masterpiece below.</div>
  <?php else: ?>
  <div class="gallery-grid">
    <?php foreach($artworks as $art): ?>
    <div class="art-card">
      <a href="?view=<?=(int)$art['id']?>">
        <img src="<?=htmlspecialchars($art['img_url'])?>" alt="<?=htmlspecialchars($art['title'])?>"
             onerror="this.src='https://images.unsplash.com/photo-1578301978693-85fa9c0320b9?w=600&q=80'">
      </a>
      <div class="art-overlay">
        <div class="art-tag"><?=htmlspecialchars($art['style'])?></div>
        <div class="art-name"><?=htmlspecialchars($art['title'])?></div>
        <div class="art-by">— <?=htmlspecialchars($art['artist'])?> · <?=$art['year']?></div>
        <div class="art-row">
          <span class="art-price">₹<?=number_format($art['price'])?></span>
          <div class="art-actions">
            <a href="?view=<?=(int)$art['id']?>" class="btn-view">View Work</a>
            <form method="POST" style="display:inline">
              <input type="hidden" name="delete_art" value="<?=(int)$art['id']?>">
              <button class="btn-rm" onclick="return confirm('Remove this artwork?')">✕</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<!-- ADD ARTWORK -->
<section class="add-section" id="add">
  <h3 class="add-title">Submit an Artwork</h3>
  <p class="add-sub">Contribute to the collection — all styles and periods welcome.</p>
  <form method="POST">
    <div class="add-grid">
      <div class="field"><label>Title</label><input type="text" name="title" placeholder="e.g. The Starry Night" required></div>
      <div class="field"><label>Artist</label><input type="text" name="artist" placeholder="e.g. Vincent van Gogh" required></div>
      <div class="field"><label>Year</label><input type="number" name="year" placeholder="e.g. 1889" min="1000" max="2025" required></div>
      <div class="field"><label>Style</label>
        <select name="style">
          <option>Post-Impressionism</option><option>Impressionism</option>
          <option>Baroque</option><option>Renaissance</option><option>Abstract</option>
          <option>Surrealism</option><option>Romanticism</option><option>Modern</option>
          <option>Photography</option><option>Sculpture</option>
        </select>
      </div>
      <div class="field"><label>Price (₹)</label><input type="number" name="price" step="100" min="0" placeholder="e.g. 125000" required></div>
      <div class="field full"><label>Image URL</label><input type="text" name="img_url" placeholder="https://... (direct image link)"></div>
      <div class="field full"><label>Description</label><textarea name="description" placeholder="About this work, its context, medium, inspiration..."></textarea></div>
    </div>
    <button type="submit" name="add_art" class="btn-submit">+ Add to Collection</button>
  </form>
</section>

<?php endif; ?>

<footer><em>Atelier Fine Art Gallery</em> &nbsp;·&nbsp; Est. 1892 &nbsp;·&nbsp; All works © their respective artists</footer>
</body>
</html>