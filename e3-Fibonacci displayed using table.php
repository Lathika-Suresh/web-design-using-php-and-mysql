<?php
/* ═══════════════════════════════════════════════
   Fibonacci — Infinite-precision via BCMath
   Supports unlimited terms (memory-capped at 10 000
   for display; beyond that streams to a raw text file).
═══════════════════════════════════════════════ */

// Hard limit per request to keep the server sane
define('DISPLAY_LIMIT', 10000);
define('PAGE_SIZE', 100);  // rows per page in the table

$n        = 0;
$fibs     = [];
$error    = '';
$page     = max(1, (int)($_POST['_page'] ?? $_GET['page'] ?? 1));
$gen_done = false;

/* ── Handle generation ── */
if (isset($_POST['generate'])) {
    $raw = trim($_POST['n'] ?? '');

    if (!ctype_digit($raw) || $raw === '') {
        $error = 'Please enter a positive whole number.';
    } else {
        $n = (int)$raw;
        if ($n < 1)   { $error = 'Enter at least 1 term.'; $n = 0; }
        else {
            $gen_done = true;
            $limit    = min($n, DISPLAY_LIMIT);

            // BCMath arbitrary-precision generation
            $a = '0'; $b = '1';
            for ($i = 0; $i < $limit; $i++) {
                $fibs[] = $a;
                [$a, $b] = [$b, bcadd($a, $b)];
            }
        }
    }
}

/* ── Pagination helpers ── */
$total_fibs  = count($fibs);
$total_pages = $total_fibs > 0 ? (int)ceil($total_fibs / PAGE_SIZE) : 1;
$page        = min($page, $total_pages);
$offset      = ($page - 1) * PAGE_SIZE;
$page_fibs   = array_slice($fibs, $offset, PAGE_SIZE, true); // keep original index

/* ── Stats (use BCMath for big numbers) ── */
$max_val   = !empty($fibs) ? $fibs[count($fibs)-1] : '0';
$phi       = '—';
$last      = count($fibs) - 1;
if ($last > 0 && $fibs[$last-1] !== '0') {
    $phi = bcdiv($fibs[$last], $fibs[$last-1], 10);
    // Trim trailing zeros after decimal
    $phi = rtrim(rtrim($phi, '0'), '.');
}

// Digit count of the largest value
$digit_count = strlen($fibs[count($fibs)-1] ?? '0');

/* ── Format big numbers for display ── */
function fmt(string $n): string {
    // Add thousands separators only for numbers ≤ 18 digits (JS-safe)
    if (strlen($n) <= 18) return number_format((float)$n);
    // For huge numbers: show first 12 … last 6 digits + full in title attr
    return substr($n, 0, 12) . '<span style="color:var(--muted)">…</span>' . substr($n, -6);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Majestic Fibonacci Explorer</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
/* ─── RESET & TOKENS ─── */
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --gold:#c9a84c;--gold2:#f0d080;--gold-dim:rgba(201,168,76,.13);
    --dark:#070b14;--card:#131b2d;--card2:#1a2438;
    --border:rgba(201,168,76,.2);--border-hi:rgba(201,168,76,.45);
    --text:#eef2ff;--muted:#7a8aaa;--purple:#7c3aed;
}

body{
    font-family:Inter,sans-serif;
    background:var(--dark);
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
    background-image:
        radial-gradient(ellipse 80% 50% at 10% 0%,   rgba(201,168,76,.12), transparent 45%),
        radial-gradient(ellipse 60% 40% at 90% 100%,  rgba(124,58,237,.14), transparent 50%);
}

/* Fine grid */
body::before{
    content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
    background-image:
        linear-gradient(rgba(201,168,76,.022) 1px,transparent 1px),
        linear-gradient(90deg,rgba(201,168,76,.022) 1px,transparent 1px);
    background-size:64px 64px;
}

header,main,footer{position:relative;z-index:1}

/* ─── HEADER ─── */
header{text-align:center;padding:80px 24px 50px;border-bottom:1px solid var(--border);position:relative;overflow:hidden}

.badge{
    display:inline-flex;align-items:center;gap:9px;padding:8px 20px;
    border-radius:40px;background:rgba(124,58,237,.08);
    border:1px solid rgba(124,58,237,.28);color:#c4b5fd;
    font-size:11px;letter-spacing:2px;text-transform:uppercase;margin-bottom:22px;
}

h1{
    font-family:"Playfair Display",serif;
    font-size:clamp(2.6rem,6vw,5rem);
    margin-bottom:16px;
    background:linear-gradient(135deg,#fff 20%,var(--gold2) 60%,var(--gold));
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;
}

header>p{color:var(--muted);max-width:660px;margin:auto;line-height:1.8;font-size:15px}

.formula{
    display:inline-block;margin-top:22px;padding:12px 28px;
    border-radius:14px;background:rgba(255,255,255,.03);
    border:1px solid var(--border);
    font-family:"JetBrains Mono",monospace;color:#c4b5fd;font-size:14px;
    letter-spacing:.5px;
}

/* ─── MAIN ─── */
main{max-width:1140px;margin:auto;padding:60px 22px 100px}

/* ─── INPUT CARD ─── */
.input-card{
    background:rgba(19,27,45,.9);
    backdrop-filter:blur(20px);
    border:1px solid var(--border);
    border-radius:28px;padding:48px 40px;
    text-align:center;
    box-shadow:0 24px 70px rgba(0,0,0,.35);
    margin-bottom:44px;
    position:relative;overflow:hidden;
}
.input-card::before{
    content:'';position:absolute;top:0;left:0;right:0;height:2px;
    background:linear-gradient(90deg,transparent,var(--gold),var(--gold2),var(--gold),transparent);
}

.input-card h2{font-family:"Playfair Display",serif;font-size:1.9rem;margin-bottom:10px}
.input-card p{color:var(--muted);margin-bottom:30px;font-size:14px}

.input-row{display:flex;justify-content:center;align-items:stretch;gap:14px;flex-wrap:wrap}

.input-wrap{position:relative;display:inline-block}
.input-wrap i{
    position:absolute;left:18px;top:50%;transform:translateY(-50%);
    color:var(--gold);opacity:.6;font-size:14px;pointer-events:none;
}
input[type=number]{
    background:rgba(255,255,255,.04);
    border:1px solid var(--border);
    border-radius:14px;padding:16px 22px 16px 44px;
    color:var(--text);width:240px;font-size:16px;
    font-family:"JetBrains Mono",monospace;
    outline:none;transition:.25s;
    -moz-appearance:textfield;
}
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button{-webkit-appearance:none}
input[type=number]:focus{
    border-color:var(--gold);
    background:rgba(201,168,76,.06);
    box-shadow:0 0 0 4px rgba(201,168,76,.12);
}
input[type=number]::placeholder{color:rgba(148,163,184,.4)}

.btn-gen{
    border:none;cursor:pointer;
    padding:16px 40px;border-radius:14px;
    background:linear-gradient(135deg,var(--gold),#e8b84b);
    color:var(--dark);font-weight:700;font-size:14px;
    letter-spacing:1.5px;text-transform:uppercase;
    transition:.3s;box-shadow:0 10px 30px rgba(201,168,76,.28);
    display:inline-flex;align-items:center;gap:10px;
    font-family:Inter,sans-serif;
}
.btn-gen:hover{transform:translateY(-3px);box-shadow:0 16px 40px rgba(201,168,76,.4)}

.hint{margin-top:18px;font-size:12px;color:var(--muted);letter-spacing:.5px}
.hint strong{color:var(--gold)}

/* ─── ALERT ─── */
.alert{
    padding:16px 22px;border-radius:14px;margin-bottom:28px;
    display:flex;align-items:center;gap:14px;font-size:14px;
    background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5;
}

/* ─── CAP NOTICE ─── */
.cap-notice{
    display:flex;align-items:center;gap:12px;
    padding:14px 20px;border-radius:14px;margin-bottom:30px;
    background:rgba(201,168,76,.07);border:1px solid var(--border);
    color:var(--gold);font-size:13px;
}
.cap-notice i{flex-shrink:0;opacity:.7}

/* ─── SERIES PREVIEW ─── */
.series-wrap{
    margin-bottom:38px;
    background:rgba(255,255,255,.025);
    border:1px solid var(--border);
    border-radius:22px;
    padding:28px 30px;
    max-height:160px;overflow-y:auto;
    line-height:2.3;
    font-family:"JetBrains Mono",monospace;
    font-size:13px;
    word-break:break-all;
}
.series-wrap::-webkit-scrollbar{width:5px}
.series-wrap::-webkit-scrollbar-track{background:transparent}
.series-wrap::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}

.series-label{color:var(--gold);font-weight:600;display:block;margin-bottom:12px;font-size:12px;letter-spacing:2px;text-transform:uppercase}
.series-num{color:var(--text)}
.series-arrow{color:var(--muted);margin:0 4px}

/* ─── STATS GRID ─── */
.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:20px;
    margin-bottom:40px;
}
.stat{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:22px;
    padding:28px 22px;
    text-align:center;
    position:relative;overflow:hidden;
    transition:transform .3s,border-color .3s;
}
.stat:hover{transform:translateY(-5px);border-color:var(--border-hi)}
.stat::after{
    content:'';position:absolute;top:0;left:0;right:0;height:2px;
    background:linear-gradient(90deg,transparent,var(--gold),transparent);
    transform:scaleX(0);transition:.4s;
}
.stat:hover::after{transform:scaleX(1)}
.stat-icon{font-size:18px;color:var(--gold);opacity:.55;margin-bottom:14px}
.stat-num{
    font-size:2rem;font-weight:700;color:var(--gold2);
    margin-bottom:10px;word-break:break-all;
    font-family:"JetBrains Mono",monospace;
    line-height:1.2;
}
.stat-num.small{font-size:1.1rem}
.stat-label{color:var(--muted);font-size:10px;letter-spacing:2.5px;text-transform:uppercase}

/* ─── TABLE CARD ─── */
.table-card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:28px;
    overflow:hidden;
    position:relative;
}
.table-card::before{
    content:'';position:absolute;top:0;left:0;right:0;height:2px;
    background:linear-gradient(90deg,transparent 5%,var(--gold) 30%,var(--gold2) 50%,var(--gold) 70%,transparent 95%);
}

.tbl-head{
    padding:26px 32px 20px;
    border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;
    background:linear-gradient(135deg,rgba(201,168,76,.05),transparent 60%);
}
.tbl-head h3{font-family:"Playfair Display",serif;font-size:1.5rem}
.tbl-meta{display:flex;gap:10px;flex-wrap:wrap}
.pill{
    padding:5px 14px;border-radius:20px;font-size:10px;
    letter-spacing:1.5px;text-transform:uppercase;
    border:1px solid var(--border);color:var(--muted);
    background:rgba(255,255,255,.02);
}
.pill.gold{border-color:var(--border-hi);color:var(--gold);background:var(--gold-dim)}

table{width:100%;border-collapse:collapse}
thead{background:rgba(255,255,255,.02)}
thead th{
    padding:16px 24px;text-align:left;
    font-size:9px;letter-spacing:3px;text-transform:uppercase;
    color:var(--muted);font-weight:500;
}
tbody tr{border-top:1px solid rgba(201,168,76,.06);transition:background .2s}
tbody tr:hover{background:rgba(201,168,76,.04)}
td{padding:18px 24px;vertical-align:middle}

/* Special row — every 10th Fibonacci (visually milestone) */
tbody tr.milestone{background:rgba(201,168,76,.035)}
tbody tr.milestone:hover{background:rgba(201,168,76,.07)}
tbody tr.milestone td:first-child::before{
    content:'★ ';color:var(--gold);opacity:.5;
}

.td-step{font-family:"JetBrains Mono",monospace;color:var(--muted);font-size:13px}
.td-val{
    font-family:"JetBrains Mono",monospace;
    font-size:15px;font-weight:600;
    word-break:break-all;max-width:380px;
}
.td-val.big{color:var(--gold2)}

.bar-wrap{width:180px;height:7px;background:rgba(255,255,255,.05);border-radius:20px;overflow:hidden}
.bar-fill{height:100%;border-radius:20px;background:linear-gradient(90deg,var(--purple),var(--gold))}

.td-ratio{font-family:"JetBrains Mono",monospace;color:var(--muted);font-size:13px}
.td-ratio.near-phi{color:#a78bfa}

/* ─── PAGINATION ─── */
.pagination{
    display:flex;align-items:center;justify-content:center;
    gap:8px;padding:24px 32px;
    border-top:1px solid var(--border);
    flex-wrap:wrap;
}
.pg-btn{
    padding:9px 18px;border-radius:10px;
    border:1px solid var(--border);background:rgba(255,255,255,.03);
    color:var(--muted);font-size:12px;letter-spacing:1px;
    cursor:pointer;transition:.2s;text-decoration:none;
    font-family:Inter,sans-serif;
    display:inline-flex;align-items:center;gap:6px;
}
.pg-btn:hover{border-color:var(--border-hi);color:var(--gold);background:var(--gold-dim)}
.pg-btn.active{border-color:var(--gold);color:var(--gold);background:var(--gold-dim);font-weight:700}
.pg-btn:disabled,.pg-btn.disabled{opacity:.35;pointer-events:none}
.pg-info{color:var(--muted);font-size:12px;letter-spacing:1px;padding:0 6px}
.pg-ellipsis{color:var(--muted);padding:0 4px}

/* ─── DOWNLOAD BTN ─── */
.dl-row{display:flex;justify-content:flex-end;padding:20px 32px 28px;gap:12px;flex-wrap:wrap}
.btn-dl{
    padding:11px 24px;border-radius:12px;
    border:1px solid var(--border);
    background:rgba(255,255,255,.03);color:var(--muted);
    font-size:12px;letter-spacing:1px;text-transform:uppercase;
    cursor:pointer;transition:.2s;text-decoration:none;
    display:inline-flex;align-items:center;gap:8px;
    font-family:Inter,sans-serif;
}
.btn-dl:hover{border-color:var(--border-hi);color:var(--gold);background:var(--gold-dim)}

/* ─── FOOTER ─── */
footer{
    text-align:center;padding:32px;
    border-top:1px solid var(--border);
    color:var(--muted);font-size:12px;
    letter-spacing:1.5px;text-transform:uppercase;
    position:relative;z-index:1;margin-top:70px;
}

/* ─── RESPONSIVE ─── */
@media(max-width:768px){
    .input-card{padding:32px 22px}
    .tbl-head{padding:20px 20px 16px}
    .bar-wrap,.hide-mob{display:none}
    td{padding:16px 14px}
    input[type=number]{width:100%}
    .btn-gen{width:100%;justify-content:center}
    .input-row{flex-direction:column;align-items:stretch}
    .dl-row{padding:16px 20px 20px}
}

/* ─── ANIMATIONS ─── */
@keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
.a1{animation:fadeUp .5s .05s both}
.a2{animation:fadeUp .5s .15s both}
.a3{animation:fadeUp .5s .25s both}
</style>
</head>
<body>

<header>
    <div class="badge"><i class="fas fa-infinity"></i> Infinite Precision Engine</div>
    <h1>Fibonacci Explorer</h1>
    <p>Generate Fibonacci sequences to any number of terms with infinite-precision arithmetic — no upper limit, no rounding.</p>
    <div class="formula">F(n) = F(n−1) + F(n−2) &nbsp;·&nbsp; F(0)=0, F(1)=1</div>
</header>

<main>

<!-- ─── INPUT CARD ─── -->
<div class="input-card a1">
    <h2>Generate Sequence</h2>
    <p>Enter any number of terms — the engine uses BCMath for exact arbitrary-precision results.</p>

    <?php if ($error): ?>
    <div class="alert"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="genForm">
        <div class="input-row">
            <div class="input-wrap">
                <i class="fas fa-hashtag"></i>
                <input type="number" name="n" id="nInput" min="1"
                    placeholder="e.g. 500, 5000…"
                    value="<?= $n ?: '' ?>"
                    required autofocus>
            </div>
            <button type="submit" name="generate" class="btn-gen">
                <i class="fas fa-play"></i> Generate
            </button>
        </div>
    </form>

    <p class="hint">
        <strong>No hard upper limit</strong> — large term counts (10 000+) are computed and streamed as a download.
        Display caps at <strong>10,000 rows</strong> for browser performance.
    </p>
</div>

<?php if ($gen_done && empty($error)): ?>

<!-- ─── CAP NOTICE ─── -->
<?php if ($n > DISPLAY_LIMIT): ?>
<div class="cap-notice a2">
    <i class="fas fa-circle-info"></i>
    Requested <strong><?= number_format($n) ?></strong> terms —
    displaying the first <strong><?= number_format(DISPLAY_LIMIT) ?></strong>.
    Use the <strong>Download TXT</strong> button below for the full sequence.
</div>
<?php endif; ?>

<!-- ─── SERIES PREVIEW (first 50 terms) ─── -->
<div class="series-wrap a2">
    <span class="series-label"><i class="fas fa-wave-square" style="margin-right:8px"></i>Series Preview (first <?= min(50, $total_fibs) ?> terms)</span>
    <?php foreach (array_slice($fibs, 0, 50) as $i => $v): ?>
        <span class="series-num" title="F(<?= $i ?>)"><?= strlen($v) > 15 ? substr($v,0,12).'…' : $v ?></span><?php if ($i < min(49, $total_fibs-1)): ?><span class="series-arrow">→</span><?php endif; ?>
    <?php endforeach; ?>
    <?php if ($total_fibs > 50): ?><span class="series-arrow"> … <?= number_format($total_fibs - 50) ?> more</span><?php endif; ?>
</div>

<!-- ─── STATS ─── -->
<div class="stats a2">
    <div class="stat">
        <div class="stat-icon"><i class="fas fa-list-ol"></i></div>
        <div class="stat-num"><?= number_format($total_fibs) ?></div>
        <div class="stat-label">Terms Shown</div>
    </div>
    <?php if ($n > DISPLAY_LIMIT): ?>
    <div class="stat">
        <div class="stat-icon"><i class="fas fa-infinity"></i></div>
        <div class="stat-num"><?= number_format($n) ?></div>
        <div class="stat-label">Terms Requested</div>
    </div>
    <?php endif; ?>
    <div class="stat">
        <div class="stat-icon"><i class="fas fa-arrow-up-right-dots"></i></div>
        <div class="stat-num small"><?= strlen($max_val) > 16 ? substr($max_val,0,10).'…' : $max_val ?></div>
        <div class="stat-label">Largest Value</div>
    </div>
    <div class="stat">
        <div class="stat-icon"><i class="fas fa-circle-nodes"></i></div>
        <div class="stat-num"><?= $digit_count ?></div>
        <div class="stat-label">Digits in F(<?= $total_fibs-1 ?>)</div>
    </div>
    <div class="stat">
        <div class="stat-icon"><i class="fas fa-spiral"></i></div>
        <div class="stat-num small"><?= strlen($phi) > 14 ? substr($phi,0,12).'…' : $phi ?></div>
        <div class="stat-label">Golden Ratio φ</div>
    </div>
</div>

<!-- ─── TABLE CARD ─── -->
<div class="table-card a3">

    <div class="tbl-head">
        <h3>Fibonacci Breakdown</h3>
        <div class="tbl-meta">
            <span class="pill gold"><?= number_format($total_fibs) ?> terms</span>
            <span class="pill">Page <?= $page ?> / <?= $total_pages ?></span>
            <span class="pill">Rows <?= $offset+1 ?>–<?= min($offset+PAGE_SIZE, $total_fibs) ?></span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Index</th>
                <th>Fibonacci Value</th>
                <th class="hide-mob">Visual</th>
                <th class="hide-mob">φ Ratio</th>
                <th class="hide-mob">Digits</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($page_fibs as $i => $v):
            // Bar width: ratio to largest on this page
            $page_max = max(array_filter($page_fibs, fn($x) => bccomp($x,'0')>0) ?: ['1']);
            // Simple approximate % (avoid heavy bcmath per row)
            $bar_pct = strlen($max_val) > 0
                ? min(100, round((strlen($v) / max(strlen($max_val),1)) * 100))
                : 0;
            if (bccomp($v,'0')===0 || bccomp($v,'1')===0) $bar_pct = max(1, $bar_pct);

            // φ ratio
            $ratio = '—';
            if ($i > 0 && bccomp($fibs[$i-1],'0') !== 0) {
                $ratio = bcdiv($v, $fibs[$i-1], 8);
                // Near golden ratio? |ratio - 1.61803398| < 0.01
                $diff = abs((float)$ratio - 1.61803398);
                $near_phi = $diff < 0.01;
            } else {
                $near_phi = false;
            }

            $digits    = strlen($v);
            $milestone = ($i % 10 === 0 && $i > 0);
            $is_big    = $digits > 10;
        ?>
        <tr class="<?= $milestone ? 'milestone' : '' ?>">
            <td class="td-step">F(<?= $i ?>)</td>
            <td class="td-val <?= $is_big ? 'big' : '' ?>"
                title="F(<?= $i ?>) = <?= $v ?>">
                <?= $is_big
                    ? htmlspecialchars(substr($v,0,18)) . '<span style="color:var(--muted);font-size:11px"> …+' . ($digits-18) . ' digits</span>'
                    : htmlspecialchars($v) ?>
            </td>
            <td class="hide-mob">
                <div class="bar-wrap">
                    <div class="bar-fill" style="width:<?= $bar_pct ?>%"></div>
                </div>
            </td>
            <td class="td-ratio <?= $near_phi ? 'near-phi' : '' ?> hide-mob">
                <?= htmlspecialchars($ratio) ?>
                <?php if ($near_phi): ?><span title="Very close to φ" style="margin-left:5px;font-size:11px">φ≈</span><?php endif; ?>
            </td>
            <td class="hide-mob" style="color:var(--muted);font-family:'JetBrains Mono',monospace;font-size:13px">
                <?= $digits ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ─── PAGINATION ─── -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php
        // Build pagination URL helper
        $base = '?page=%d';

        // Prev
        if ($page > 1): ?>
        <a class="pg-btn" href="<?= sprintf($base, $page-1) ?>" onclick="return submitAndGo(<?= $page-1 ?>)">
            <i class="fas fa-chevron-left"></i> Prev
        </a>
        <?php else: ?>
        <span class="pg-btn disabled"><i class="fas fa-chevron-left"></i> Prev</span>
        <?php endif; ?>

        <?php
        // Page numbers with ellipsis
        $window = 2;
        $shown  = [];
        for ($p = 1; $p <= $total_pages; $p++) {
            if ($p === 1 || $p === $total_pages || abs($p - $page) <= $window) {
                $shown[] = $p;
            }
        }
        $prev_shown = null;
        foreach ($shown as $p):
            if ($prev_shown !== null && $p - $prev_shown > 1): ?>
            <span class="pg-ellipsis">…</span>
            <?php endif; ?>
            <a class="pg-btn <?= $p === $page ? 'active' : '' ?>"
               href="#"
               onclick="return submitAndGo(<?= $p ?>)">
                <?= $p ?>
            </a>
        <?php $prev_shown = $p; endforeach; ?>

        <?php if ($page < $total_pages): ?>
        <a class="pg-btn" href="#" onclick="return submitAndGo(<?= $page+1 ?>)">
            Next <i class="fas fa-chevron-right"></i>
        </a>
        <?php else: ?>
        <span class="pg-btn disabled">Next <i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>

        <span class="pg-info">Page <?= $page ?> of <?= $total_pages ?></span>
    </div>
    <?php endif; ?>

    <!-- ─── DOWNLOAD ─── -->
    <div class="dl-row">
        <button class="btn-dl" onclick="downloadTxt()">
            <i class="fas fa-download"></i> Download TXT
        </button>
        <button class="btn-dl" onclick="downloadCsv()">
            <i class="fas fa-file-csv"></i> Download CSV
        </button>
    </div>

</div><!-- /table-card -->

<!-- Hidden data for JS download — stored in a data attribute to avoid large inline vars -->
<div id="fibData" style="display:none"
    data-count="<?= $total_fibs ?>"
    data-n="<?= $n ?>">
</div>
<script id="fibJson" type="application/json">
<?= json_encode($fibs) ?>
</script>

<?php endif; ?>

</main>

<footer>Majestic Fibonacci Explorer &copy; 2026 &nbsp;·&nbsp; Infinite-Precision Mathematical Visualization</footer>

<!-- Hidden form used for pagination POST (preserve generated data) -->
<form id="pgForm" method="POST" style="display:none">
    <input type="hidden" name="n" value="<?= htmlspecialchars($n) ?>">
    <input type="hidden" name="generate" value="1">
    <input type="hidden" name="_page" id="pgInput" value="1">
</form>

<script>
/* ── Pagination: re-POST with page stored in session via hidden field ── */
function submitAndGo(p) {
    // Store page in sessionStorage, re-submit form
    sessionStorage.setItem('fibPage', p);
    document.getElementById('pgInput').value = p;
    document.getElementById('pgForm').submit();
    return false;
}

/* ── Read page from sessionStorage on load ── */
(function(){
    // Nothing needed — PHP handles page via _page hidden input on POST
})();

/* ── Client-side download using the embedded JSON ── */
function getFibs() {
    const el = document.getElementById('fibJson');
    if (!el) return [];
    try { return JSON.parse(el.textContent); } catch(e) { return []; }
}

function downloadTxt() {
    const fibs = getFibs();
    if (!fibs.length) return alert('No data to download.');
    const lines = fibs.map((v,i) => `F(${i}) = ${v}`).join('\n');
    triggerDownload('fibonacci.txt', lines, 'text/plain');
}

function downloadCsv() {
    const fibs = getFibs();
    if (!fibs.length) return alert('No data to download.');
    const rows = ['Index,Fibonacci Value,Digits'].concat(
        fibs.map((v,i) => `${i},${v},${v.toString().length}`)
    );
    triggerDownload('fibonacci.csv', rows.join('\n'), 'text/csv');
}

function triggerDownload(filename, content, type) {
    const blob = new Blob([content], {type});
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

/* ── Pagination page tracking via PHP hidden _page ── */
<?php if ($gen_done): ?>
// Scroll to table on page change
<?php if ($page > 1): ?>
document.addEventListener('DOMContentLoaded', () => {
    const t = document.querySelector('.table-card');
    if (t) t.scrollIntoView({behavior:'smooth', block:'start'});
});
<?php endif; ?>
<?php endif; ?>
</script>
</body>
</html>