<?php

$conn = mysqli_connect("localhost", "root", "", "majestic_vote_db");
if (!$conn) die("Connection Failed");

$upload_url = 'uploads/participants/';

$candidate_images = [
    "Steve"      => "https://upload.wikimedia.org/wikipedia/en/8/8b/ST3_Steve_Harrington_portrait.jpg",
    "Nancy"      => "https://upload.wikimedia.org/wikipedia/pt/3/3c/Nancy_Wheeler_%28Natalia_Dyer%29.jpg",
    "Jonathan"   => "https://upload.wikimedia.org/wikipedia/pt/thumb/a/a9/Jonathan_Byers_%28Charlie_Heaton%29.jpg/250px-Jonathan_Byers_%28Charlie_Heaton%29.jpg",
    "Joyce"      => "https://cdn.polyspeak.ai/speakmaster/poly-sdispatcher/superresolution/images/20250404/99396f09-85c9-43d2-a414-bf9a13f9f20e.webp",
    "Jim Hopper" => "https://upload.wikimedia.org/wikipedia/en/0/08/JimHopperST.png",
    "Bob"        => "https://static.tvmaze.com/uploads/images/medium_portrait/132/332043.jpg"
];

/* ── Fetch original candidates ── */
$rows  = [];
$total = 0;

$r = mysqli_query($conn, "SELECT * FROM vote ORDER BY count DESC");
while ($row = mysqli_fetch_assoc($r)) {
    $row['img']    = $candidate_images[$row['name']] ?? 'https://via.placeholder.com/300x300';
    $row['source'] = 'official';
    $rows[]        = $row;
    $total        += $row['count'];
}

/* ── Fetch participants ── */
$pt = mysqli_query($conn, "SHOW TABLES LIKE 'participants'");
if (mysqli_num_rows($pt) > 0) {
    $r2 = mysqli_query($conn, "SELECT * FROM participants ORDER BY vote_count DESC");
    while ($row = mysqli_fetch_assoc($r2)) {
        $img = $row['profile_image']
            ? $upload_url . $row['profile_image']
            : 'https://ui-avatars.com/api/?name=' . urlencode($row['full_name'])
              . '&size=300&background=131929&color=c9a84c&bold=true&length=2';
        $rows[] = [
            'name'   => $row['full_name'],
            'party'  => 'Registered Participant',
            'count'  => $row['vote_count'],
            'img'    => $img,
            'source' => 'participant',
        ];
        $total += $row['vote_count'];
    }
}

/* ── Sort unified list ── */
usort($rows, fn($a, $b) => $b['count'] - $a['count']);

$winner         = $rows[0] ?? null;
$total_official = count(array_filter($rows, fn($r) => $r['source'] === 'official'));
$total_parts    = count(array_filter($rows, fn($r) => $r['source'] === 'participant'));

/* ── Podium: always [2nd, 1st, 3rd] in array for display ── */
$p1 = $rows[0] ?? null; // 1st  → centre
$p2 = $rows[1] ?? null; // 2nd  → left
$p3 = $rows[2] ?? null; // 3rd  → right

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MajesticVote — Live Results</title>

<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Cormorant+Garamond:ital,wght@0,300;0,500;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
/* ══════════════ RESET & TOKENS ══════════════ */
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}

:root{
    --gold:        #c9a84c;
    --gold-light:  #f0d080;
    --gold-pale:   #faefc7;
    --gold-dim:    rgba(201,168,76,.13);
    --dark:        #060912;
    --surface:     #0c1020;
    --card:        #111828;
    --card-hi:     #18203a;
    --border:      rgba(201,168,76,.16);
    --border-hi:   rgba(201,168,76,.45);
    --text:        #dde6ff;
    --muted:       #6a7a9a;
    --r1:          #f0d080;
    --r2:          #b8c4d8;
    --r3:          #c07840;
}

html { scroll-behavior: smooth; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--dark);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
}

/* ══ LAYERED BACKGROUND ══ */
.bg-layer {
    position: fixed; inset: 0; z-index: 0; pointer-events: none;
    background:
        radial-gradient(ellipse 100% 60% at 0%   0%,   rgba(201,168,76,.10), transparent 50%),
        radial-gradient(ellipse 70%  50% at 100% 100%,  rgba(90,40,180,.12),  transparent 55%),
        radial-gradient(ellipse 60%  80% at 50%  40%,   rgba(6,9,18,.95),     transparent);
}

.grid-layer {
    position: fixed; inset: 0; z-index: 0; pointer-events: none;
    background-image:
        linear-gradient(rgba(201,168,76,.022) 1px, transparent 1px),
        linear-gradient(90deg, rgba(201,168,76,.022) 1px, transparent 1px);
    background-size: 72px 72px;
}

.noise-layer {
    position: fixed; inset: 0; z-index: 0; pointer-events: none; opacity:.4;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.05'/%3E%3C/svg%3E");
}

header,main,footer { position: relative; z-index: 1; }

/* ══ HEADER ══ */
header {
    text-align: center;
    padding: 90px 24px 60px;
    border-bottom: 1px solid var(--border);
    position: relative;
    overflow: hidden;
}

/* Horizontal gold ray lines behind header */
header::before {
    content: '';
    position: absolute; inset: 0;
    background: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 38px,
        rgba(201,168,76,.025) 38px,
        rgba(201,168,76,.025) 39px
    );
    pointer-events: none;
}

.crown-wrap {
    display: inline-flex; align-items: center; justify-content: center;
    width: 72px; height: 72px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,168,76,.18), rgba(201,168,76,.04));
    border: 1px solid var(--border-hi);
    margin-bottom: 24px;
    position: relative;
}
.crown-wrap::before {
    content: '';
    position: absolute; inset: -7px; border-radius: 50%;
    border: 1px dashed rgba(201,168,76,.28);
    animation: spin 30s linear infinite;
}
.crown-wrap::after {
    content: '';
    position: absolute; inset: -14px; border-radius: 50%;
    border: 1px dashed rgba(201,168,76,.12);
    animation: spin 50s linear infinite reverse;
}
@keyframes spin { to { transform: rotate(360deg); } }

.crown-wrap i { font-size: 28px; color: var(--gold); }

.live-pill {
    display: inline-flex; align-items: center; gap: 9px;
    padding: 7px 18px; border-radius: 40px;
    background: rgba(201,168,76,.07);
    border: 1px solid var(--border);
    color: var(--gold); font-size: 10px;
    letter-spacing: 3.5px; text-transform: uppercase;
    margin-bottom: 26px;
}
.live-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #4ade80; box-shadow: 0 0 10px #4ade80;
    animation: livePulse 1.8s ease-in-out infinite;
}
@keyframes livePulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.3;transform:scale(.8)} }

h1 {
    font-family: 'Cinzel', serif;
    font-size: clamp(2.2rem, 5.5vw, 4rem);
    font-weight: 700;
    letter-spacing: 4px;
    line-height: 1.15;
    margin-bottom: 16px;
    background: linear-gradient(150deg, #fff 10%, var(--gold-light) 50%, var(--gold) 90%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.header-sub {
    color: var(--muted); font-size: 13px;
    letter-spacing: 2px; text-transform: uppercase;
}

.divider-ornament {
    display: flex; align-items: center; justify-content: center;
    gap: 18px; margin-top: 34px;
}
.divider-ornament::before,.divider-ornament::after {
    content:''; width: 120px; height: 1px;
    background: linear-gradient(90deg, transparent, var(--border-hi), transparent);
}
.divider-ornament span { color: var(--gold); font-size: 16px; opacity: .65; }

/* ══ MAIN ══ */
main { max-width: 1160px; margin: auto; padding: 64px 24px 100px; }

/* ══ STATS ══ */
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 18px;
    margin-bottom: 70px;
}

.stat-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 28px 20px 24px;
    text-align: center;
    position: relative; overflow: hidden;
    transition: transform .3s, border-color .3s;
}
.stat-card::after {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
    transform: scaleX(0); transition: transform .4s;
}
.stat-card:hover { transform: translateY(-5px); border-color: var(--border-hi); }
.stat-card:hover::after { transform: scaleX(1); }

.stat-icon { font-size: 19px; color: var(--gold); opacity: .6; margin-bottom: 14px; }
.stat-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.6rem; font-weight: 700;
    color: var(--gold-light); line-height: 1;
    margin-bottom: 10px;
}
.stat-num.small { font-size: 1.3rem; line-height: 1.3; }
.stat-label {
    color: var(--muted); font-size: 9px;
    letter-spacing: 3px; text-transform: uppercase;
}

/* ══ SECTION LABEL ══ */
.section-label {
    display: flex; align-items: center; gap: 14px; margin-bottom: 40px;
}
.section-label h2 {
    font-family: 'Cinzel', serif;
    font-size: .85rem; letter-spacing: 4px;
    color: var(--gold); text-transform: uppercase; white-space: nowrap;
}
.section-label::after {
    content: ''; flex: 1; height: 1px;
    background: linear-gradient(90deg, var(--border-hi), transparent);
}

/* ══════════════════════════════════════
   PODIUM  —  fixed layout
   DOM order: slot-2nd, slot-1st, slot-3rd
   CSS uses explicit order property + flexbox
══════════════════════════════════════ */
.podium-stage {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 0;
    margin-bottom: 70px;
    position: relative;
}

/* Stage glow line */
.podium-stage::after {
    content: '';
    position: absolute; bottom: 0; left: 5%; right: 5%; height: 1px;
    background: linear-gradient(90deg, transparent, var(--border-hi), transparent);
}

/* ── Slot base ── */
.p-slot {
    display: flex; flex-direction: column; align-items: center;
    flex: 1; max-width: 320px; min-width: 0;
}

/* ── 1st place: centre, tallest ── */
.p-slot.p1 { order: 2; }
.p-slot.p2 { order: 1; }
.p-slot.p3 { order: 3; }

/* ── Avatar ── */
.p-avatar-ring {
    position: relative; margin-bottom: 16px;
}

.p-avatar {
    border-radius: 50%;
    object-fit: cover; object-position: top;
    display: block;
    transition: filter .3s;
}

/* 1st */
.p1 .p-avatar {
    width: 128px; height: 128px;
    border: 4px solid var(--r1);
    box-shadow: 0 0 0 6px rgba(240,208,128,.12), 0 0 40px rgba(240,208,128,.20);
}
/* 2nd */
.p2 .p-avatar {
    width: 96px; height: 96px;
    border: 3px solid var(--r2);
    box-shadow: 0 0 20px rgba(184,196,216,.12);
}
/* 3rd */
.p3 .p-avatar {
    width: 88px; height: 88px;
    border: 3px solid var(--r3);
    box-shadow: 0 0 18px rgba(192,120,64,.12);
}

.p-medal {
    position: absolute; bottom: 0; right: 0;
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    border: 2px solid var(--dark);
}
.p1 .p-medal { background: var(--r1); color: var(--dark); bottom: -2px; right: -2px; width: 34px; height: 34px; font-size: 17px; }
.p2 .p-medal { background: var(--r2); color: var(--dark); }
.p3 .p-medal { background: var(--r3); color: #fff; }

/* ── Info ── */
.p-info { text-align: center; margin-bottom: 18px; padding: 0 8px; }

.p-name {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 700; line-height: 1.2; margin-bottom: 5px;
}
.p1 .p-name { font-size: 1.7rem; color: var(--gold-light); }
.p2 .p-name { font-size: 1.3rem; color: var(--r2); }
.p3 .p-name { font-size: 1.25rem; color: var(--r3); }

.p-party {
    font-size: 10px; letter-spacing: 2px; text-transform: uppercase;
    color: var(--muted); margin-bottom: 8px;
}

.p-votes {
    font-family: 'Cinzel', serif; font-weight: 600;
    margin-bottom: 8px;
}
.p1 .p-votes { font-size: 1.15rem; color: var(--gold); }
.p2 .p-votes { font-size: .95rem; color: var(--r2); }
.p3 .p-votes { font-size: .9rem;  color: var(--r3); }

.p-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase;
    border: 1px solid;
}
.p-badge.official    { color: #a78bfa; background: rgba(124,58,237,.1); border-color: rgba(124,58,237,.3); }
.p-badge.participant { color: var(--gold); background: var(--gold-dim); border-color: var(--border); }

/* ── Podium block ── */
.p-block {
    width: 100%;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Cinzel', serif; font-weight: 700;
    font-size: 1.6rem; letter-spacing: 3px;
    border-radius: 14px 14px 0 0;
    position: relative; overflow: hidden;
}
/* Shimmer line at top of block */
.p-block::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, currentColor, transparent);
    opacity: .5;
}

/* 1st — tallest, gold glow */
.p1 .p-block {
    height: 130px;
    background: linear-gradient(180deg, rgba(240,208,128,.15) 0%, rgba(240,208,128,.04) 100%);
    border: 1px solid rgba(240,208,128,.35); border-bottom: none;
    color: var(--r1);
    box-shadow: inset 0 1px 0 rgba(240,208,128,.2);
}
/* 2nd */
.p2 .p-block {
    height: 90px;
    background: linear-gradient(180deg, rgba(184,196,216,.10) 0%, rgba(184,196,216,.03) 100%);
    border: 1px solid rgba(184,196,216,.22); border-bottom: none;
    color: var(--r2);
}
/* 3rd */
.p3 .p-block {
    height: 65px;
    background: linear-gradient(180deg, rgba(192,120,64,.10) 0%, rgba(192,120,64,.03) 100%);
    border: 1px solid rgba(192,120,64,.22); border-bottom: none;
    color: var(--r3);
}

/* Empty slot */
.p-slot.empty .p-block { background: rgba(255,255,255,.02); border-color: var(--border); color: var(--muted); font-size: 1rem; }

/* ══ RESULTS TABLE ══ */
.results-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 24px;
    overflow: hidden;
    position: relative;
}
/* Gold accent bar top */
.results-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent 5%, var(--gold) 30%, var(--gold-light) 50%, var(--gold) 70%, transparent 95%);
}

.tbl-top {
    padding: 30px 32px 22px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, rgba(201,168,76,.05), transparent 60%);
}
.tbl-top h3 {
    font-family: 'Cinzel', serif;
    font-size: .9rem; letter-spacing: 4px; color: var(--gold); text-transform: uppercase;
}
.tbl-pills { display: flex; gap: 10px; flex-wrap: wrap; }
.tbl-pill {
    padding: 5px 14px; border-radius: 20px;
    font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase;
    border: 1px solid var(--border); color: var(--muted);
    background: rgba(255,255,255,.02);
}
.tbl-pill.gold { border-color: var(--border-hi); color: var(--gold); background: var(--gold-dim); }

/* Filter tabs */
.filter-row {
    display: flex; border-bottom: 1px solid var(--border);
    background: rgba(255,255,255,.01);
    overflow-x: auto; padding: 0 32px;
}
.ftab {
    padding: 14px 22px; font-size: 11px; letter-spacing: 2px; text-transform: uppercase;
    color: var(--muted); cursor: pointer; border: none; background: none;
    border-bottom: 2px solid transparent; transition: .2s;
    font-family: 'DM Sans', sans-serif; white-space: nowrap; flex-shrink: 0;
}
.ftab.active, .ftab:hover { color: var(--gold); border-bottom-color: var(--gold); }

/* Table */
table { width: 100%; border-collapse: collapse; }

thead tr { background: rgba(255,255,255,.018); }
thead th {
    padding: 16px 20px; text-align: left;
    color: var(--muted); font-size: 9px; letter-spacing: 3px; text-transform: uppercase;
    font-weight: 500;
}

tbody tr {
    border-top: 1px solid rgba(201,168,76,.055);
    transition: background .2s;
}
tbody tr:hover { background: rgba(201,168,76,.04); }

/* Rank-1 special row */
tbody tr.r1 {
    background: linear-gradient(90deg, rgba(240,208,128,.07) 0%, rgba(240,208,128,.02) 40%, transparent);
}
tbody tr.r1:hover { background: linear-gradient(90deg, rgba(240,208,128,.10) 0%, rgba(240,208,128,.03) 50%, transparent); }

td { padding: 18px 20px; vertical-align: middle; }

/* Rank */
.td-rank {
    width: 60px; text-align: center;
    font-family: 'Cinzel', serif;
}
.rank-icon { font-size: 22px; line-height: 1; }
.rank-num  { color: var(--muted); font-size: 1rem; font-weight: 700; }

/* Candidate cell */
.cand-wrap { display: flex; align-items: center; gap: 15px; }
.cand-img {
    width: 54px; height: 54px; border-radius: 12px;
    object-fit: cover; object-position: top;
    border: 1.5px solid var(--border); flex-shrink: 0; transition: .3s;
}
tbody tr:hover .cand-img { border-color: var(--border-hi); transform: scale(1.06); }
tbody tr.r1 .cand-img { border-radius: 50%; width: 60px; height: 60px; border-color: rgba(240,208,128,.5); }

.cand-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.15rem; font-weight: 700; line-height: 1.2; margin-bottom: 4px;
}
tbody tr.r1 .cand-name { color: var(--gold-light); font-size: 1.25rem; }
.cand-party { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--muted); margin-bottom: 5px; }
.src-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 20px;
    font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; border: 1px solid;
}
.src-badge.official    { color: #a78bfa; background: rgba(124,58,237,.1); border-color: rgba(124,58,237,.3); }
.src-badge.participant { color: var(--gold); background: var(--gold-dim); border-color: var(--border); }

/* Votes cell */
.v-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.6rem; font-weight: 700; line-height: 1; margin-bottom: 3px;
}
tbody tr.r1 .v-num { color: var(--gold-light); }
.v-sub { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); }

/* Share cell */
.share-pct {
    font-size: 1.1rem; font-weight: 700;
    font-family: 'Cinzel', serif;
    margin-bottom: 8px;
}
tbody tr.r1 .share-pct { color: var(--gold-light); }
tbody tr.r2 .share-pct { color: var(--r2); }
tbody tr.r3 .share-pct { color: var(--r3); }
tbody tr:not(.r1):not(.r2):not(.r3) .share-pct { color: var(--muted); }

/* Bar cell */
.bar-outer {
    height: 7px; background: rgba(255,255,255,.05);
    border-radius: 30px; overflow: hidden;
}
.bar-inner {
    height: 100%; border-radius: 30px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    transition: width 1s cubic-bezier(.4,0,.2,1);
}
tbody tr.r1 .bar-inner { background: linear-gradient(90deg, var(--gold), var(--gold-pale)); box-shadow: 0 0 10px rgba(240,208,128,.35); }
tbody tr.r2 .bar-inner { background: linear-gradient(90deg, var(--r2), #dde6f4); }
tbody tr.r3 .bar-inner { background: linear-gradient(90deg, var(--r3), #e0904c); }

/* Hide on small screens */
.hide-sm { }
@media(max-width:860px) { .hide-sm { display: none; } }

/* Empty */
.empty-row td { text-align: center; padding: 60px; color: var(--muted); font-style: italic; }

/* ══ FOOTER ══ */
footer {
    position: relative; z-index: 1;
    text-align: center; padding: 32px;
    border-top: 1px solid var(--border);
    color: var(--muted); font-size: 11px; letter-spacing: 2px; text-transform: uppercase;
}

/* ══ ANIMATIONS ══ */
@keyframes fadeUp { from{opacity:0;transform:translateY(22px)} to{opacity:1;transform:translateY(0)} }
.a1{animation:fadeUp .55s .05s both}
.a2{animation:fadeUp .55s .15s both}
.a3{animation:fadeUp .55s .25s both}
.a4{animation:fadeUp .55s .35s both}

/* ══ MOBILE ══ */
@media(max-width:700px){
    .stats{grid-template-columns:1fr 1fr;}
    .podium-stage{gap:6px;}
    .p1 .p-avatar{width:100px;height:100px;}
    .p2 .p-avatar{width:76px;height:76px;}
    .p3 .p-avatar{width:70px;height:70px;}
    .p1 .p-block{height:100px;}
    .p2 .p-block{height:70px;}
    .p3 .p-block{height:52px;}
    td{padding:14px 12px;}
    .filter-row{padding:0 16px;}
    .tbl-top{padding:20px 20px 16px;}
    .cand-img{width:44px!important;height:44px!important;}
}
@media(max-width:480px){
    .stats{grid-template-columns:1fr;}
    .podium-stage{flex-direction:column;align-items:center;}
    .p-slot{order:unset!important;max-width:260px;width:100%;}
    .p-block{border-radius:12px!important;height:48px!important;}
}
</style>
</head>
<body>

<div class="bg-layer"></div>
<div class="grid-layer"></div>
<div class="noise-layer"></div>

<!-- ══ HEADER ══ -->
<header>
    <div class="crown-wrap"><i class="fas fa-crown"></i></div>
    <div class="live-pill"><span class="live-dot"></span> Live Results &nbsp;·&nbsp; 2026 Election</div>
    <h1>Official Vote Results</h1>
    <p class="header-sub">Real-time standings &nbsp;·&nbsp; Updated live as votes are tallied</p>
    <div class="divider-ornament"><span>✦</span></div>
</header>

<main>

<!-- ══ STATS ══ -->
<div class="stats a1">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-to-slot"></i></div>
        <div class="stat-num"><?= number_format($total) ?></div>
        <div class="stat-label">Total Votes</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-num"><?= count($rows) ?></div>
        <div class="stat-label">Total Candidates</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-star"></i></div>
        <div class="stat-num"><?= $total_official ?></div>
        <div class="stat-label">Official</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-check"></i></div>
        <div class="stat-num"><?= $total_parts ?></div>
        <div class="stat-label">Participants</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-crown"></i></div>
        <div class="stat-num small"><?= $winner ? htmlspecialchars($winner['name']) : '—' ?></div>
        <div class="stat-label">Current Leader</div>
    </div>
</div>

<!-- ══ PODIUM ══ -->
<?php if (!empty($rows)): ?>
<div class="a2">
    <div class="section-label">
        <h2><i class="fas fa-trophy" style="margin-right:10px;opacity:.7"></i> Top Contenders</h2>
    </div>

    <!--
        LAYOUT: we render slots in DOM order [2nd, 1st, 3rd]
        CSS order property: p2=order:1, p1=order:2, p3=order:3
        This guarantees 1st is visually in the centre regardless of data.
    -->
    <div class="podium-stage">

        <?php
        // Helper to render a podium slot
        function renderSlot($p, $cssClass, $blockHeight, $rankNum, $medalEmoji, $total) {
            $pct = ($total > 0 && $p) ? round(($p['count']/$total)*100,1) : 0;
            $isOfficial = $p ? ($p['source'] === 'official') : false;
            $empty = !$p;
            echo '<div class="p-slot ' . $cssClass . ($empty ? ' empty' : '') . '">';
            if (!$empty):
        ?>
            <div class="p-avatar-ring">
                <img class="p-avatar"
                    src="<?= htmlspecialchars($p['img']) ?>"
                    alt="<?= htmlspecialchars($p['name']) ?>"
                    onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($p['name']) ?>&size=200&background=131929&color=c9a84c&bold=true'">
                <div class="p-medal"><?= $medalEmoji ?></div>
            </div>
            <div class="p-info">
                <div class="p-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="p-party"><?= htmlspecialchars($p['party']) ?></div>
                <div class="p-votes"><?= number_format($p['count']) ?> votes · <?= $pct ?>%</div>
                <span class="p-badge <?= $isOfficial ? 'official' : 'participant' ?>">
                    <i class="fas <?= $isOfficial ? 'fa-star' : 'fa-user-check' ?>"></i>
                    <?= $isOfficial ? 'Official' : 'Participant' ?>
                </span>
            </div>
        <?php endif; ?>
            <div class="p-block"><?= $rankNum ?></div>
        </div>
        <?php
        }

        // Render: 2nd LEFT, 1st CENTRE, 3rd RIGHT
        renderSlot($p2, 'p2', 90,  2, '🥈', $total);
        renderSlot($p1, 'p1', 130, 1, '🥇', $total);
        renderSlot($p3, 'p3', 65,  3, '🥉', $total);
        ?>

    </div>
</div>
<?php endif; ?>

<!-- ══ RESULTS TABLE ══ -->
<div class="results-card a3">

    <div class="tbl-top">
        <h3><i class="fas fa-list-ol" style="margin-right:12px;opacity:.6"></i>Full Election Standings</h3>
        <div class="tbl-pills">
            <span class="tbl-pill gold"><?= count($rows) ?> Candidates</span>
            <span class="tbl-pill"><?= number_format($total) ?> Total Votes</span>
        </div>
    </div>

    <div class="filter-row">
        <button class="ftab active" data-f="all">All Candidates</button>
        <button class="ftab" data-f="official">Official Only</button>
        <button class="ftab" data-f="participant">Participants Only</button>
    </div>

    <table id="rt">
        <thead>
            <tr>
                <th style="text-align:center">Rank</th>
                <th>Candidate</th>
                <th>Votes</th>
                <th class="hide-sm">Share</th>
                <th class="hide-sm">Progress</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr class="empty-row"><td colspan="5">No votes have been cast yet.</td></tr>
        <?php else: foreach ($rows as $i => $row):
            $rank = $i + 1;
            $pct  = $total > 0 ? round(($row['count']/$total)*100,1) : 0;
            $rc   = $rank <= 3 ? "r$rank" : '';
            $ico  = match($rank){ 1=>'🥇', 2=>'🥈', 3=>'🥉', default=>"<span class='rank-num'>#$rank</span>" };
        ?>
        <tr class="<?= $rc ?>" data-src="<?= $row['source'] ?>">

            <td class="td-rank">
                <?php if ($rank <= 3): ?>
                    <span class="rank-icon"><?= $ico ?></span>
                <?php else: ?>
                    <?= $ico ?>
                <?php endif; ?>
            </td>

            <td>
                <div class="cand-wrap">
                    <img class="cand-img"
                        src="<?= htmlspecialchars($row['img']) ?>"
                        alt="<?= htmlspecialchars($row['name']) ?>"
                        onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($row['name']) ?>&size=120&background=131929&color=c9a84c&bold=true'">
                    <div>
                        <div class="cand-name"><?= htmlspecialchars($row['name']) ?></div>
                        <div class="cand-party"><?= htmlspecialchars($row['party']) ?></div>
                        <span class="src-badge <?= $row['source'] ?>">
                            <i class="fas <?= $row['source'] === 'official' ? 'fa-star' : 'fa-user-check' ?>"></i>
                            <?= $row['source'] === 'official' ? 'Official' : 'Participant' ?>
                        </span>
                    </div>
                </div>
            </td>

            <td>
                <div class="v-num"><?= number_format($row['count']) ?></div>
                <div class="v-sub">votes</div>
            </td>

            <td class="hide-sm">
                <div class="share-pct"><?= $pct ?>%</div>
            </td>

            <td class="hide-sm" style="min-width:160px">
                <div class="bar-outer">
                    <div class="bar-inner" style="width:0" data-w="<?= $pct ?>"></div>
                </div>
            </td>

        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

</div><!-- /results-card -->

</main>

<footer>MajesticVote &copy; 2026 &nbsp;·&nbsp; Official Digital Election Results Portal</footer>

<script>
/* ── Animate bars after render ── */
document.querySelectorAll('.bar-inner').forEach(b => {
    requestAnimationFrame(() =>
        setTimeout(() => b.style.width = b.dataset.w + '%', 200)
    );
});

/* ── Filter tabs ── */
const ftabs = document.querySelectorAll('.ftab');
const trows = document.querySelectorAll('#rt tbody tr[data-src]');

ftabs.forEach(tab => tab.addEventListener('click', () => {
    ftabs.forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    const f = tab.dataset.f;
    trows.forEach(r => r.style.display = (f === 'all' || r.dataset.src === f) ? '' : 'none');
}));
</script>
</body>
</html>